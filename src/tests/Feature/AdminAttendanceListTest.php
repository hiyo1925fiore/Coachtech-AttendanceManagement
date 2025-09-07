<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminAttendanceListTest extends TestCase
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
     * テストケース12: 勤怠一覧情報取得機能（管理者）
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_attendance_data_for_all_general_users_for_that_day_are_displayed_accurately()
    {
        // 別の一般ユーザーを作成
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 一般ユーザーの勤怠データと休憩時間を作成
        $today = Carbon::today();

        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 別の一般ユーザーの勤怠データと休憩時間を作成
        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => $today,
            'start_time' => '11:00:00',
            'end_time' => '19:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'start_time' => '12:15:00',
            'end_time' => '13:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 勤怠一覧画面（管理者）を開く
        $response = $this->actingAs($this->adminUser)->get('/admin/attendances');
        $response->assertStatus(200);

        $response->assertViewIs('admin.admin_attendance_list');
        $response->assertViewHas('attendanceData');

        // 勤怠データが表示されていることを確認
        $response->assertSee("{$today->format('Y年n月j日')}の勤怠");
        $response->assertSee("{$today->format('Y/m/d')}");
        $response->assertSee($this->user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00'); // 休憩時間
        $response->assertSee('8:00'); // 労働時間
        $response->assertSee($otherUser->name);
        $response->assertSee('11:00');
        $response->assertSee('19:00');
        $response->assertSee('0:45'); // 休憩時間
        $response->assertSee('7:15'); // 労働時間
    }

    /**
     * テストケース12: 勤怠一覧情報取得機能（管理者）
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_displayed_when_accessing_attendance_list_page()
    {
        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 勤怠一覧画面（管理者）を開く
        $response = $this->actingAs($this->adminUser)->get('/admin/attendances');
        $response->assertStatus(200);

        // 現在の日付が表示されていることを確認
        $today = Carbon::now();
        $response->assertSee("{$today->format('Y年n月j日')}の勤怠");
        $response->assertSee("{$today->format('Y/m/d')}");
    }

    /**
     * テストケース12: 勤怠一覧情報取得機能（管理者）
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_button_displays_previous_day_information()
    {
        // 前日の勤怠データを作成
        $previousDay = Carbon::now()->subDay();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $previousDay->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 勤怠一覧画面（管理者）を開く
        $response = $this->actingAs($this->adminUser)->get('/admin/attendances');
        $response->assertStatus(200);

        // 2. 「前日」ボタンを押す（POSTメソッド送信）
        $previousDayResponse = $this->actingAs($this->adminUser)->post('/admin/attendances', [
            'year' => $previousDay->year,
            'month' => $previousDay->month,
            'day' => $previousDay->day,
        ]);

        // 3. 前日の日付の勤怠情報が表示されることを確認
        $previousDayResponse->assertStatus(200);
        $previousDayResponse->assertSee("{$previousDay->format('Y年n月j日')}の勤怠");
        $previousDayResponse->assertSee("{$previousDay->format('Y/m/d')}");
        $previousDayResponse->assertSee($this->user->name);
        $previousDayResponse->assertSee('09:00');
        $previousDayResponse->assertSee('18:00');
    }

    /**
     * テストケース12: 勤怠一覧情報取得機能（管理者）
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_month_button_displays_next_month_information()
    {
        // 翌日の勤怠データを作成
        $nextDay = Carbon::now()->addDay();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $nextDay->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 1. 管理者ユーザーでログインする
        Auth::login($this->adminUser);

        // 2. 勤怠一覧画面（管理者）を開く
        $response = $this->actingAs($this->adminUser)->get('/admin/attendances');
        $response->assertStatus(200);

        // 2. 「翌日」ボタンを押す（POSTメソッド送信）
        $nextDayResponse = $this->actingAs($this->adminUser)->post('/admin/attendances', [
            'year' => $nextDay->year,
            'month' => $nextDay->month,
            'day' => $nextDay->day,
        ]);

        // 3. 翌日の日付の勤怠情報が表示されることを確認
        $nextDayResponse->assertStatus(200);
        $nextDayResponse->assertSee("{$nextDay->format('Y年n月j日')}の勤怠");
        $nextDayResponse->assertSee("{$nextDay->format('Y/m/d')}");
        $nextDayResponse->assertSee($this->user->name);
        $nextDayResponse->assertSee('09:00');
        $nextDayResponse->assertSee('18:00');
    }
}
