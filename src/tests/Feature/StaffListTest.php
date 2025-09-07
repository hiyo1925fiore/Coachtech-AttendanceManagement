<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用一般ユーザーを作成
        $this->user = $this->createTestUser();
        // テスト用管理者を作成
        $this->adminUser = $this->createTestAdminUser();
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
     * テスト用管理者を作成
     */
    protected function createTestAdminUser()
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_user_can_view_name_and_email_address_of_all_general_users()
    {
        // 別の一般ユーザーを作成
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. スタッフ一覧ページを開く
        $response = $this->actingAs($this->adminUser)->get('/admin/users');
        $response->assertStatus(200);

        $response->assertViewIs('admin.staff_list');
        $response->assertViewHas('users');

        // 3. 全一般ユーザーの情報が画面に表示されていることを確認
        $response->assertSee('スタッフ一覧');
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
        $response->assertSee($otherUser->name);
        $response->assertSee($otherUser->email);
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_attendance_data_by_general_user_is_displayed_correctly()
    {
        // 勤怠データを作成
        $today = Carbon::now();

        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩時間を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff_attendance_list');
        $response->assertViewHas('attendanceData');

        // 現在の年月が表示されていることを確認
        $response->assertSee(Carbon::now()->format('Y/m'));

        // 3. 勤怠情報が正確に画面に表示されていることを確認
        $response->assertSee("{$this->user->name}さんの勤怠");
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00'); // 休憩時間
        $response->assertSee('8:00'); // 労働時間
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_displays_previous_month_information()
    {
        // 前月の勤怠データを作成
        $lastMonth = Carbon::now()->subMonth();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $lastMonth->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩時間を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す（POSTメソッド送信）
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->user->id}/attendances", [
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);

        // 1か月前の年月が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));

        // 勤怠情報が画面に表示されていることを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00'); // 休憩時間
        $response->assertSee('8:00'); // 労働時間
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_button_displays_next_month_information()
    {
        // 翌月の勤怠データを作成
        $nextMonth = Carbon::now()->addMonth();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $nextMonth->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩時間を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");
        $response->assertStatus(200);

        // 2. テストユーザーでログインして翌月データのページにアクセス
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->user->id}/attendances", [
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
        ]);

        // 3. 1か月後の年月が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));

        // 勤怠情報が画面に表示されていることを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00'); // 休憩時間
        $response->assertSee('8:00'); // 労働時間
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_attendance_detail_screen()
    {
        $today = Carbon::today();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $response = $this->get("/admin/users/{$this->user->id}/attendances");
        $response->assertStatus(200);

        // 詳細リンクが存在することを確認
        $detailUrl = route('admin.attendance.detail', ['date' => $today->format('Y-m-d')]);
        $response->assertSee($detailUrl);

        // 3. 「詳細」リンクをクリックする
        $detailResponse = $this->withSession([
            'current_staff_user_id' => $this->user->id,
        ])
            ->get($detailUrl);

        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('admin.admin_attendance_detail');

        // 指定した一般ユーザー・日付の勤怠詳細画面が表示されていることを確認
        $year = $today->year;

        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee("{$year}年");
        $detailResponse->assertSee($today->format('n月j日'));
        $detailResponse->assertSee('9:00');
        $detailResponse->assertSee('18:00');
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 補助テスト: 詳細ボタンのリンクが正しく存在する
     */
    public function test_detail_link_exists_in_admin_attendance_list()
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
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");
        $response->assertStatus(200);

        // 詳細リンクのHTML要素が存在することを確認
        $response->assertSee('詳細', false); // HTMLエスケープなしで確認
        $response->assertSee("href=\"" . route('admin.attendance.detail', ['date' => $today->format('Y-m-d')]) . "\"", false);
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
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

        // ルートパラメータで日付を指定して指定した一般ユーザーの勤怠詳細画面にアクセス
        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'current_staff_user_id' => $this->user->id,
            ])
            ->get("/admin/attendances/{$specificDate}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin_attendance_detail');

        // 指定した日付のデータが表示されていることを確認
        $response->assertSee('2023');
        $response->assertSee('6月15日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 補助テスト: 勤怠データがない日付の詳細をクリックした場合
     */
    public function test_attendance_detail_with_no_attendance_data()
    {
        $today = Carbon::today();

        // 勤怠データを作成せずに詳細画面にアクセス
        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'current_staff_user_id' => $this->user->id,
            ])
            ->get("/admin/attendances/{$today->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin_attendance_detail');

        // 勤怠データがない場合でも画面が表示されることを確認
        // （コントローラーで空のAttendanceインスタンスが作成される）
        $response->assertSee($today->format('n月j日'));
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
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
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertDontSee('10:00');

        // 前月の表示
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->user->id}/attendances", [
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertDontSee('09:00');
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 補助テスト: 勤怠データがない日でも詳細画面へのリンクが表示される
     */
    public function test_days_without_attendance_data_display_correctly()
    {
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff_attendance_list');

        // 勤怠データがない場合でも詳細リンクは表示される
        $today = Carbon::today();
        $detailUrl = route('admin.attendance.detail', ['date' => $today->format('Y-m-d')]);
        $response->assertSee($detailUrl);
    }

    /**
     * テストケース14: ユーザー情報取得機能（管理者）
     * 補助テスト: スタッフ一覧画面（管理者） 未ログインユーザーのアクセステスト
     */
    public function test_unauthenticated_user_cannot_access_staff_list()
    {
        $response = $this->get('/admin/users');

        // ログインページにリダイレクトされることを確認
        $response->assertRedirect('/admin/login');
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

        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->user->id}/attendances");

        $response->assertStatus(200);
        // 休憩時間の合計が表示される（1時間15分）
        $response->assertSee('1:15');
        // 労働時間が正しく計算される（9時間 - 1時間15分 = 7時間45分）
        $response->assertSee('7:45');
    }
}
