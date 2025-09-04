<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用一般ユーザーを作成
        $this->user = $this->createTestUser();
    }

    /**
     * テスト用一般ユーザーを作成
     */
    protected function createTestUser()
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_user_with_attendance_data_can_login_and_view_their_attendance()
    {
        // 1. 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 休憩データを作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 3. テストユーザーでログインしてアクセス
        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertViewIs('attendance_list');
        $response->assertViewHas('attendanceData');

        // 4. 勤怠データが表示されていることを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00'); // 休憩時間
        $response->assertSee('8:00'); // 労働時間
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_displayed_when_accessing_attendance_list_page()
    {
        // 1. テストユーザーでログインしてアクセス
        $response = $this->actingAs($this->user)->get('/attendance/list');

        // 2. 現在の年月が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_displays_previous_month_information()
    {
        // 1. 前月の勤怠データを作成
        $lastMonth = Carbon::now()->subMonth();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $lastMonth->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. テストユーザーでログインして前月データのページにアクセス
        $response = $this->actingAs($this->user)->post('/attendance/list', [
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);

        // 3. 1か月前の年月が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_button_displays_next_month_information()
    {
        // 1. 翌月の勤怠データを作成
        $nextMonth = Carbon::now()->addMonth();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $nextMonth->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. テストユーザーでログインして翌月データのページにアクセス
        $response = $this->actingAs($this->user)->post('/attendance/list', [
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
        ]);

        // 3. 1か月後の年月が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_attendance_detail_screen()
    {
        $today = Carbon::today();

        // 1. 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. テストユーザーでログインして勤怠一覧画面（一般ユーザー）にアクセス
        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);

        // 3. 詳細リンクが存在することを確認
        $detailUrl = route('attendance.detail', ['date' => $today->format('Y-m-d')]);
        $response->assertSee($detailUrl);

        // 4. 指定日の勤怠詳細画面（一般ユーザー）にアクセス
        $detailResponse = $this->actingAs($this->user)->get($detailUrl);

        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('attendance_detail');

        // 5. 指定日の勤怠詳細画面が表示されていることを確認
        $year = $today->year;

        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee("{$year}年");
        $detailResponse->assertSee($today->format('n月j日'));
        $detailResponse->assertSee('9:00');
        $detailResponse->assertSee('18:00');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 詳細ボタンのリンクが正しく存在する
     */
    public function test_detail_link_exists_in_attendance_list()
    {
        $today = Carbon::today();

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertStatus(200);

        // 詳細リンクのHTML要素が存在することを確認
        $response->assertSee('詳細', false); // HTMLエスケープなしで確認
        $response->assertSee("href=\"" . route('attendance.detail', ['date' => $today->format('Y-m-d')]) . "\"", false);
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 特定日付のパラメータを渡して勤怠詳細画面に遷移できる
     */
    public function test_attendance_detail_with_specific_date_parameter()
    {
        // 特定の日付でテスト
        $specificDate = '2023-06-15';

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $specificDate,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // ルートパラメータで日付を指定してアクセス
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$specificDate}");

        $response->assertStatus(200);
        $response->assertViewIs('attendance_detail');

        // 指定した日付のデータが表示されていることを確認
        $response->assertSee('2023');
        $response->assertSee('6月15日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 勤怠データがない日付の詳細をクリックした場合
     */
    public function test_attendance_detail_with_no_attendance_data()
    {
        $today = Carbon::today();

        // 勤怠データを作成せずに詳細画面にアクセス
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$today->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertViewIs('attendance_detail');

        // 勤怠データがない場合でも画面が表示されることを確認
        // （コントローラーで空のAttendanceインスタンスが作成される）
        $response->assertSee($today->format('n月j日'));
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 複数月のデータがある場合
     */
    public function test_multiple_months_data_display_correctly()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // 今月のデータ
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $currentMonth->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 前月のデータ
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $lastMonth->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 今月の表示
        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertDontSee('10:00');

        // 前月の表示
        $response = $this->actingAs($this->user)->post('/attendance/list', [
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertDontSee('09:00');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 勤怠データがない日でも詳細画面へのリンクが表示される
     */
    public function test_days_without_attendance_data_display_correctly()
    {
        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertViewIs('attendance_list');

        // 勤怠データがない場合でも詳細リンクは表示される
        $today = Carbon::today();
        $detailUrl = route('attendance.detail', ['date' => $today->format('Y-m-d')]);
        $response->assertSee($detailUrl);
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 未ログインユーザーのアクセステスト
     */
    public function test_unauthenticated_user_cannot_access_attendance_list()
    {
        $response = $this->get('/attendance/list');

        // ログインページにリダイレクトされることを確認
        $response->assertRedirect('/login');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 他のユーザーの勤怠情報が表示されない
     */
    public function test_other_users_attendance_data_not_displayed()
    {
        // 別のユーザーを作成
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 別のユーザーの勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => Carbon::today(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // 自分の勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        // 自分のデータは表示される
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 他のユーザーのデータは表示されない
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 休憩時間が複数ある場合
     */
    public function test_multiple_break_times_calculated_correctly()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 複数の休憩時間を作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => '15:00:00',
            'end_time' => '15:15:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        // 休憩時間の合計が表示される（1時間15分）
        $response->assertSee('1:15');
        // 労働時間が正しく計算される（9時間 - 1時間15分 = 7時間45分）
        $response->assertSee('7:45');
    }

    /**
     * テストケース9: 勤怠一覧情報取得機能（一般ユーザー）
     * 補助テスト: 退勤していない場合
     */
    public function test_display_when_user_has_not_clocked_out()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => null, // 退勤していない
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00'); // 出勤時間は表示される
        // 退勤時間、休憩時間、労働時間は空になる
    }
}
