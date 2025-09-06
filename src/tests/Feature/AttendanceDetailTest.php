<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use App\Models\BreakTimeRequest;
use Carbon\Carbon;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用一般ユーザーを作成
        $this->user = $this->createTestUser();
        // テスト用管理者を作成
        $this->adminUser = $this->createTestAdminUser();

        // テスト日付を設定
        $this->testDate = '2023-06-01';
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
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_name_displays_logged_in_user_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 名前欄を確認する
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->user->name === $this->user->name;
        });
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_date_displays_selected_date_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 日付欄を確認する
        $response->assertStatus(200);
        $response->assertSee('2023年');
        $response->assertSee('6月1日');
        $response->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->date->format('Y-m-d') === $this->testDate;
        });
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 「出勤・退勤」にて記録されている時間がログインユーザーの打刻と一致している
     */
    public function test_clock_in_out_times_match_user_timestamps_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 出勤・退勤欄を確認する
        $response->assertStatus(200);

        // 未承認申請がない場合は入力フィールドが表示される
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 「休憩」にて記録されている時間がログインユーザーの打刻と一致している
     */
    public function test_break_times_match_user_timestamps_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩時間データを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 休憩欄を確認する
        $response->assertStatus(200);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 未承認の修正申請がある場合、修正申請の内容が表示される
     */
    public function test_unapproved_correction_request_displays_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 未承認の修正申請を作成
        $unapprovedRequest = AttendanceRequest::create([
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'start_time' => '09:30:00',
            'end_time' => '18:30:00',
            'note' => '電車遅延のため',
            'is_approved' => 0,
        ]);

        // 休憩時間の修正申請も作成
        BreakTimeRequest::create([
            'attendance_request_id' => $unapprovedRequest->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 修正申請の内容が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('電車遅延のため');
        $response->assertSee('承認待ちのため修正はできません。');

        // 入力フィールドは表示されない
        $response->assertDontSee('name="start_time"', false);
        $response->assertDontSee('name="end_time"', false);
        $response->assertDontSee('name="note"', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 未承認の修正申請がない場合、通常の入力フィールドが表示される
     */
    public function test_input_fields_display_when_no_unapproved_request()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => '通常勤務',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 入力フィールドが表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('name="note"', false);
        $response->assertSee('修正', false);

        // 承認待ちメッセージは表示されない
        $response->assertDontSee('承認待ちのため修正はできません。');
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 勤怠データが存在しない場合、空のインスタンスが作成される
     */
    public function test_empty_instance_created_when_attendance_data_not_exists()
    {
        // 1. ユーザーにログインする
        Auth::login($this->user);

        // 2. 存在しない日付で勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. ページが正常に表示されることを確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertViewHas('attendance');

        // 空の入力フィールドが表示される
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 複数の休憩時間がある場合の表示テスト
     */
    public function test_multiple_break_times_display_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 複数の休憩時間データを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '15:00:00',
            'end_time' => '15:15:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 複数の休憩時間が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('休憩');
        $response->assertSee('休憩2');
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:15"', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 新規休憩時間入力欄の表示テスト
     */
    public function test_new_break_time_input_fields_display_correctly()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
        Auth::login($this->user);

        // 勤怠データを作成（休憩時間なし）
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 3. 新規休憩時間入力欄が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('break_start_time[0]', false);
        $response->assertSee('break_end_time[0]', false);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 無効な日付フォーマットでアクセスした場合のテスト
     */
    public function test_invalid_date_format_returns_404_error()
    {
        // 1. ユーザーにログインする
        Auth::login($this->user);

        // 2. 無効な日付でアクセス
        $response = $this->get('/attendance/detail/invalid-date');

        // 3. 404エラーが返されることを確認
        $response->assertStatus(404);
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 管理者がアクセスした場合は勤怠一覧画面（管理者）にリダイレクトする
     */
    public function test_admin_user_redirected_to_admin_attendance_list()
    {
        Auth::login($this->adminUser);

        // 勤怠詳細ページにアクセスを試行
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // 管理者画面にリダイレクトされることを確認
        $response->assertRedirect(route('admin.attendance.list'));
    }

    /**
     * テストケース10: 勤怠詳細情報取得機能（一般ユーザー）
     * 補助テスト: 未ログインユーザーのアクセステスト
     */
    public function test_unauthenticated_user_cannot_access_attendance_list()
    {
        $response = $this->get("/attendance/detail/{$this->testDate}");

        // ログインページにリダイレクトされることを確認
        $response->assertRedirect('/login');
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_attendance_detail_displays_selected_user_correctly()
    {
        // 一般ユーザーの勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩時間データを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 1. 管理者ユーザーにログインする
        Auth::login($this->adminUser);

        // 2. 勤怠詳細ページを開く（勤怠一覧画面（管理者）から遷移した場合）
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
            ])
            ->get("/admin/attendances/{$this->testDate}");

        $response->assertStatus(200);

        // 名前欄を確認する
        $response->assertSee($this->user->name);
        $response->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->user->name === $this->user->name;
        });

        // 日付欄を確認する
        $response->assertSee('2023年');
        $response->assertSee('6月1日');
        $response->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->date->format('Y-m-d') === $this->testDate;
        });

        // 出勤・退勤欄を確認する
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);

        // 休憩欄を確認する
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 指定した一般ユーザー・日付で勤怠データが存在しない場合、空のインスタンスが作成される
     */
    public function test_empty_instance_created_when_attendance_data_not_exists_by_admin_screen()
    {
        // 1. 管理者ユーザーにログインする
        Auth::login($this->adminUser);

        // 2. 指定した一般ユーザーかつ、勤怠データが存在しない日付の勤怠詳細ページを開く
        // テスト用に、勤怠一覧画面（管理者）から遷移した場合を想定（本番では想定されない）
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/{$this->testDate}");

        // 3. ページが正常に表示されることを確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertViewHas('attendance');

        // 空の入力フィールドが表示される
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 複数の休憩時間がある場合の表示テスト
     */
    public function test_multiple_break_times_display_correctly_by_admin_screen()
    {
        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 複数の休憩時間データを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '15:00:00',
            'end_time' => '15:15:00',
        ]);

        // 1. 管理者ユーザーにログインする
        Auth::login($this->adminUser);

        // 2. 勤怠詳細ページを開く
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/{$this->testDate}");

        // 3. 複数の休憩時間が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('休憩');
        $response->assertSee('休憩2');
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:15"', false);
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 新規休憩時間入力欄の表示テスト
     */
    public function test_new_break_time_input_fields_display_correctly_by_admin_screen()
    {
        // 勤怠データを作成（休憩時間なし）
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 1. 管理者ユーザーにログインする
        Auth::login($this->adminUser);

        // 2. 勤怠詳細ページを開く
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/{$this->testDate}");

        // 3. 新規休憩時間入力欄が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('break_start_time[0]', false);
        $response->assertSee('break_end_time[0]', false);
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 無効な日付フォーマットでアクセスした場合のテスト
     */
    public function test_invalid_date_format_returns_404_error_by_admin_screen()
    {
        // 1. 管理者ユーザーにログインする
        Auth::login($this->adminUser);

        // 2. 勤怠詳細ページを開く
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/invalid-date");

        // 3. 404エラーが返されることを確認
        $response->assertStatus(404);
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 一般ユーザーがアクセスした場合は管理者ログインページにリダイレクトする
     */
    public function test_general_user_redirected_to_attendance_register_screen()
    {
        // 一般ユーザーでログイン
        Auth::login($this->user);

        // 勤怠詳細ページにアクセスを試行
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/{$this->testDate}");

        // 一般ユーザー画面にリダイレクトされることを確認
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * テストケース13: 勤怠詳細情報取得・修正機能（管理者）
     * 補助テスト: 未ログインユーザーのアクセステスト
     */
    public function test_unauthenticated_user_cannot_access_admin_attendance_list()
    {
        $response = $this->withSession([
            'selected_attendance_user_id' => $this->user->id,
            'selected_attendance_date' => $this->testDate
        ])
            ->get("/admin/attendances/{$this->testDate}");

        // 管理者ログインページにリダイレクトされることを確認
        $response->assertRedirect('/admin/login');
    }
}
