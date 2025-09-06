<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Livewire\Livewire;

class AttendanceRegisterTest extends TestCase
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
     * テストケース4: 日時取得機能
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_and_time_displayed_in_UI_format()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. AttendanceComponentをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 3. 日付がUI形式で設定されていることを確認
        $selectedDate = $component->get('selectedDate');

        // 現在の日付要素を取得
        $now = Carbon::now();
        $currentYear = (string)$now->year;
        $currentMonth = (string)$now->month;
        $currentDay = (string)$now->day;
        $currentDayOfWeek = $now->locale('ja')->isoFormat('ddd');

        // 日付形式の確認：年月日と曜日が含まれている
        $this->assertStringContainsString($currentYear, $selectedDate);
        $this->assertStringContainsString($currentMonth, $selectedDate);
        $this->assertStringContainsString($currentDay, $selectedDate);
        $this->assertStringContainsString($currentDayOfWeek, $selectedDate);
        $this->assertStringContainsString('年', $selectedDate);
        $this->assertStringContainsString('月', $selectedDate);
        $this->assertStringContainsString('日', $selectedDate);

        // 4. 時刻がUI形式で取得されることを確認
        $currentTime = $component->instance()->getCurrentTime();

        // 時刻形式の確認：HH:MM形式
        $this->assertStringContainsString(':', $currentTime);
        $this->assertEquals(5, strlen($currentTime)); // HH:MM = 5文字

        // 時刻が現在時刻に近いことを確認（3分以内）
        $timeParts = explode(':', $currentTime);
        $this->assertCount(2, $timeParts);
        $this->assertTrue(is_numeric($timeParts[0]) && is_numeric($timeParts[1]));
        $this->assertTrue((int)$timeParts[0] >= 0 && (int)$timeParts[0] <= 23);
        $this->assertTrue((int)$timeParts[1] >= 0 && (int)$timeParts[1] <= 59);
    }

    /**
     * テストケース5: ステータス確認機能
     * 勤務外の場合、勤務ステータスが正しく表示される
     */
    public function test_status_display_when_before_work()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 勤怠データが存在しない状態でコンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 3. currentStatusが'before_work'になることを確認
        $this->assertEquals('before_work', $component->get('currentStatus'));

        // 4. ステータステキストが正しく表示されることを確認（バックエンドロジック）
        $statusText = $component->instance()->getStatusText();
        $this->assertEquals('勤務外', $statusText);

        // 5. 画面に実際に表示されているステータスを確認（HTML出力）
        $component->assertSee('勤務外');
    }

    /**
     * テストケース5: ステータス確認機能
     * 出勤中の場合、勤務ステータスが正しく表示される
     */
    public function test_status_display_when_working()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 今日の出勤データを作成（終了時間なし = 出勤中）
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. currentStatusが'working'になることを確認
        $this->assertEquals('working', $component->get('currentStatus'));

        // 5. ステータステキストが正しく表示されることを確認（バックエンドロジック）
        $statusText = $component->instance()->getStatusText();
        $this->assertEquals('出勤中', $statusText);

        // 6. 画面に実際に表示されているステータスを確認（HTML出力）
        $component->assertSee('出勤中');
    }

    /**
     * テストケース5: ステータス確認機能
     * 休憩中の場合、勤務ステータスが正しく表示される
     */
    public function test_status_display_when_on_break()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 今日の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        // 3. 休憩データを作成（終了時間なし = 休憩中）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => null, // 休憩終了時間なし
        ]);

        // 4. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 5. currentStatusが'on_break'になることを確認
        $this->assertEquals('on_break', $component->get('currentStatus'));

        // 6. ステータステキストが正しく表示されることを確認（バックエンドロジック）
        $statusText = $component->instance()->getStatusText();
        $this->assertEquals('休憩中', $statusText);

        // 7. 画面に実際に表示されているステータスを確認（HTML出力）
        $component->assertSee('休憩中');
    }

    /**
     * テストケース5: ステータス確認機能
     * 退勤済の場合、勤務ステータスが正しく表示される
     */
    public function test_status_display_when_finished()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 今日の出勤データを作成（終了時間あり = 退勤済）
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00', // 終了時間あり
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. currentStatusが'finished'になることを確認
        $this->assertEquals('finished', $component->get('currentStatus'));

        // 5. ステータステキストが正しく表示されることを確認（バックエンドロジック）
        $statusText = $component->instance()->getStatusText();
        $this->assertEquals('退勤済', $statusText);

        // 6. 画面に実際に表示されているステータスを確認（HTML出力）
        $component->assertSee('退勤済');
    }

    /**
     * テストケース6: 出勤機能
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_properly()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 勤怠データが存在しない状態でコンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 3. 画面に出勤ボタンが表示されることを確認
        $component->assertSee('button__attendance');
        $component->assertSee('出勤');
        $this->assertEquals('before_work', $component->get('currentStatus'));

        // 4. 出勤ボタンをクリック
        $component->call('startWork');

        // 5. ステータスが「出勤中」に変更されることを確認
        $this->assertEquals('working', $component->get('currentStatus'));
        $component->assertSee('出勤中');

        // 6. 退勤ボタンと休憩入ボタンが表示されることを確認
        $component->assertSee('退勤');
        $component->assertSee('休憩入');
    }

    /**
     * テストケース6: 出勤機能
     * 出勤は一日一回のみできる
     */
    public function test_can_start_work_only_once_per_day()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 退勤済みの出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 画面に出勤ボタンが表示されないことを確認
        $component->assertDontSee('button__attendance');
        $component->assertDontSee('出勤');
    }

    /**
     * テスト6: 出勤機能
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_start_time_displayed_in_attendance_list()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 現在時刻を固定
        $fixedTime = Carbon::create(2023, 6, 1, 8, 0, 0);
        Carbon::setTestNow($fixedTime);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 出勤処理を実行
        $component->call('startWork');

        // 5. データベースに正しい開始時刻で勤怠レコードが作成されることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => $fixedTime->format('Y-m-d'),
            'start_time' => $fixedTime->format('H:i:s'),
        ]);

        // 6. 出勤日の年月の勤怠一覧画面（一般ユーザー）を開く
        $response = $this->post('/attendance/list', [
            'year' => $fixedTime->year,
            'month' => $fixedTime->month,
        ]);
        $response->assertStatus(200);

        // 7. ビューに正しいデータが渡されることを確認
        $response->assertViewHas('attendanceData');

        // 8. ビューデータの取得
        $viewData = $response->viewData('attendanceData');

        // 9. 該当日のデータが存在することを確認（数値インデックス配列）
        $targetDay = $fixedTime->day; // 1日
        $arrayIndex = $targetDay - 1;

        $this->assertArrayHasKey($arrayIndex, $viewData,
            "Array index {$arrayIndex} not found. Array has " . count($viewData) . " elements.");

        // 10. 勤怠データの存在確認
        $dailyData = $viewData[$arrayIndex];
        $this->assertIsArray($dailyData, 'Daily attendance data should be an array');
        $this->assertArrayHasKey('attendance', $dailyData, 'Attendance key not found in daily data');
        $this->assertNotNull($dailyData['attendance'], 'Attendance data should not be null for day ' . $targetDay);

        // 11. 出勤時刻の正確性を検証
        $attendanceRecord = $dailyData['attendance'];
        $this->assertInstanceOf(
            \App\Models\Attendance::class,
            $attendanceRecord,
            'Attendance should be an Attendance model instance'
        );

        $actualStartTime = Carbon::parse($attendanceRecord->start_time);
        $this->assertEquals(
            $fixedTime->format('H:i'),
            $actualStartTime->format('H:i'),
            'Start time should match the fixed time'
        );

        // 12. ビューでの表示確認
        $response->assertSee($fixedTime->format('H:i'), false);
        $response->assertSee($fixedTime->format('Y/m'), false);

        // テスト時刻をリセット
        Carbon::setTestNow();
    }

    /**
     * テストケース7: 休憩機能
     * 休憩ボタンが正しく機能する
     */
    public function test_break_button_works_properly()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 出勤中の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 画面に休憩入ボタンが表示されることを確認
        $component->assertSee('button__break-time');
        $component->assertSee('休憩入');
        $this->assertEquals('working', $component->get('currentStatus'));

        // 5. 休憩入ボタンをクリック
        $component->call('startBreak');

        // 6. ステータスが「休憩中」に変更されることを確認
        $this->assertEquals('on_break', $component->get('currentStatus'));
        $component->assertSee('休憩中');
    }

    /**
     * テストケース7: 休憩機能
     * 休憩は一日に何回でもできる
     */
    public function test_can_take_breaks_as_many_times_per_day()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 出勤中の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 休憩入ボタンをクリック
        $component->call('startBreak');

        // 5. 休憩戻ボタンをクリック
        $component->call('endBreak');

        // 6. 画面に休憩入ボタンが表示されることを確認
        $component->assertSee('button__break-time');
        $component->assertSee('休憩入');
    }

    /**
     * テストケース7: 休憩機能
     * 休憩戻ボタンが正しく機能する
     */
    public function test_end_break_button_works_properly()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 出勤中の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 休憩入ボタンをクリック
        $component->call('startBreak');

        // 5. 休憩戻ボタンが表示されることを確認
        $component->assertSee('button__break-time');
        $component->assertSee('休憩戻');

        // 6. 休憩戻ボタンをクリック
        $component->call('endBreak');

        // 7. ステータスが「出勤中」に変更されることを確認
        $this->assertEquals('working', $component->get('currentStatus'));
        $component->assertSee('出勤中');
    }

    /**
     * テストケース7: 休憩機能
     * 休憩戻は一日に何回でもできる
     */
    public function test_breaks_can_be_ended_as_many_times_per_day()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 出勤中の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 休憩入ボタンをクリック
        $component->call('startBreak');

        // 5. 休憩戻ボタンをクリック
        $component->call('endBreak');

        // 4. 再度休憩入ボタンをクリック
        $component->call('startBreak');

        // 5. 画面に休憩戻ボタンが表示されることを確認
        $component->assertSee('button__break-time');
        $component->assertSee('休憩戻');
    }

    /**
     * テスト7: 休憩機能
     * 休憩時間が勤怠一覧画面で確認できる
     */
    public function test_break_time_displayed_in_attendance_list()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2.事前に出勤処理を行う
        $startTime = Carbon::create(2023, 6, 1, 9, 0, 0);
        Carbon::setTestNow($startTime);

        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);
        $component->call('startWork');

        // 3. ステータスが「出勤中」であることを確認
        $this->assertEquals('working', $component->get('currentStatus'));
        $component->assertSee('休憩入');
        $component->assertSee('退勤');

        // 勤怠レコードを取得
        $attendance = Attendance::where('user_id', $this->user->id)
            ->where('date', $startTime->format('Y-m-d'))
            ->first();

        $this->assertNotNull($attendance);

        // 4. 休憩入処理を実行
        $breakStart = Carbon::create(2023, 6, 1, 12, 0, 0);
        Carbon::setTestNow($breakStart);

        $component->call('startBreak');

        // 5. データベースに休憩レコードが作成されることを確認
        $this->assertDatabaseCount('break_times', 1);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => $breakStart->format('H:i:s'),
            'end_time' => null,
        ]);

        // 6. 休憩戻処理を実行
        $breakEnd = Carbon::create(2023, 6, 1, 13, 0, 0);
        Carbon::setTestNow($breakEnd);

        $component->call('endBreak');

        // 7. データベースに休憩終了時刻が記録されることを確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => $breakStart->format('H:i:s'),
            'end_time' => $breakEnd->format('H:i:s'),
        ]);

        // 8. 勤怠一覧画面（一般ユーザー）を開く
        $response = $this->post('/attendance/list', [
            'year' => $startTime->year,
            'month' => $startTime->month,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceData');

        // 8. ビューデータの取得と検証
        $viewData = $response->viewData('attendanceData');

        // 9. 該当日のデータが存在することを確認（数値インデックス配列）
        $targetDay = $startTime->day; // 1日
        $arrayIndex = $targetDay - 1;

        $this->assertArrayHasKey($arrayIndex, $viewData,
            "Array index {$arrayIndex} not found. Array has " . count($viewData) . " elements.");

        // 10. 勤怠データの存在確認
        $dailyData = $viewData[$arrayIndex];
        $this->assertNotNull($dailyData['attendance'], 'Attendance data should not be null for day ' . $targetDay);
        $this->assertNotNull($dailyData['break_time'], 'Break time data should not be null for day ' . $targetDay);

        // 11. 休憩時間が正しく記録されていることを確認（12:00-13:00 = 60分）
        $expectedBreakMinutes = 60;
        $this->assertEquals($expectedBreakMinutes, $dailyData['break_time'],
            'Break time should match the fixed time');

        // 12. 勤怠一覧画面での表示を確認
        // 分表記を「時間：分」表記に変換
        $hours = floor(60 / 60); // 1時間
        $minutes = 60 % 60; // 0分
        $expectedFormat = sprintf('%d:%02d', $hours, $minutes); // "1:00"

        $response->assertSee($expectedFormat, false);
        $response->assertSee($startTime->format('m/d'), false); // 日付表示

        // テスト時刻をリセット
        Carbon::setTestNow();
    }

    /**
     * テスト7: 休憩機能
     * 補助テスト: 一日に複数回休憩をした場合
     */
    public function test_multiple_break_sessions()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 事前に出勤データを作成する
        $workDate = Carbon::create(2023, 6, 15, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $workDate->format('Y-m-d'),
            'start_time' => $workDate->format('H:i:s'),
            'end_time' => $workDate->copy()->addHours(8)->format('H:i:s'),
        ]);

        // 3. 複数の休憩時間データを作成（60分 + 30分 = 90分）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2023, 6, 15, 12, 0, 0)->format('H:i:s'),
            'end_time' => Carbon::create(2023, 6, 15, 13, 0, 0)->format('H:i:s'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2023, 6, 15, 15, 0, 0)->format('H:i:s'),
            'end_time' => Carbon::create(2023, 6, 15, 15, 30, 0)->format('H:i:s'),
        ]);

        // コントローラーのクエリと同じ条件でデータを取得しているかテスト
        $testQuery = \App\Models\Attendance::where('user_id', $this->user->id)
            ->whereYear('date', 2023)
            ->whereMonth('date', 6)
            ->with('breakTimes')
            ->get()
            ->keyBy(function($item) {
                return $item->date->format('Y-m-d');
            });

        // 4. 勤怠一覧画面（一般ユーザー）を開く
        $response = $this->post('/attendance/list', [
            'year' => 2023,
            'month' => 6,
        ]);

        // 5. ビューデータの取得と検証
        $viewData = $response->viewData('attendanceData');
        $arrayIndex = 14; // 6月15日

        $this->assertArrayHasKey($arrayIndex, $viewData,
            "Array index {$arrayIndex} not found. Array has " . count($viewData) . " elements.");

        // 10. 勤怠データの存在確認
        $dailyData = $viewData[$arrayIndex];
        $this->assertArrayHasKey('attendance', $dailyData, 'Attendance key not found.');
        $this->assertNotNull($dailyData['attendance'], 'Attendance data should not be null for day ' . 15);

        // break_timeキーの存在確認
        $this->assertArrayHasKey('break_time', $dailyData, 'Break time key not found.');

        // 6. 休憩時間が正しく記録されていることを確認
        $this->assertEquals(90, $dailyData['break_time']);

        // 7. 勤怠一覧画面での表示を確認
        $response->assertSee('1:30', false);
}

    /**
     * テスト7: 休憩機能
     * 補助テスト: 様々な休憩時間パターンのテスト
     */
    public function test_various_break_time_patterns()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 複数の日付に対して休憩時間を設定する(0分、45分、120分)
        $testCases = [
            ['minutes' => 0, 'expected' => '0:00', 'day' => 5],
            ['minutes' => 45, 'expected' => '0:45', 'day' => 10],
            ['minutes' => 120, 'expected' => '2:00', 'day' => 20],
        ];

        // 3. 各日付に対して勤怠データと休憩データを作成する
        foreach ($testCases as $testCase) {
            $day = $testCase['day'];
            $minutes = $testCase['minutes'];

            $workDate = Carbon::create(2023, 6, $day, 9, 0, 0);

            $attendance = Attendance::create([
                'user_id' => $this->user->id,
                'date' => $workDate->format('Y-m-d'),
                'start_time' => $workDate->format('H:i:s'),
                'end_time' => $workDate->copy()->addHours(8)->format('H:i:s'),
            ]);

            if ($testCase['minutes'] > 0) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $workDate->copy()->addHours(3)->format('H:i:s'),
                    'end_time' => $workDate->copy()->addHours(3)->addMinutes($minutes)->format('H:i:s'),
                ]);
            }
        }

        // 4. 勤怠一覧画面（一般ユーザー）を開く
        $response = $this->post('/attendance/list', [
            'year' => $workDate->year,
            'month' => $workDate->month,
        ]);

        // 5. ビューデータを取得
        $viewData = $response->viewData('attendanceData');

        // 6. 休憩時間が正しく記録されていること及び勤怠一覧画面での表示を確認
        foreach ($testCases as $testCase) {
            $day = $testCase['day'];
            $minutes = $testCase['minutes'];
            $expected = $testCase['expected'];
            $arrayIndex = $day - 1;

            $dailyData = $viewData[$arrayIndex];

            $this->assertEquals($minutes, $dailyData['break_time']);
            $response->assertSee($expected, false);

            // 日付表示の確認（ゼロパディング対応）
            $dayFormatted = sprintf('%02d', $day); // 05, 10, 20
            $response->assertSee("06/{$dayFormatted}", false);
        }
    }

    /**
     * テストケース8: 退勤機能
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_works_properly()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        // 2. 出勤中の出勤データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => null, // 終了時間なし
        ]);

        // 3. コンポーネントをテスト
        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 4. 画面に退勤ボタンが表示されることを確認
        $component->assertSee('button__attendance');
        $component->assertSee('退勤');
        $this->assertEquals('working', $component->get('currentStatus'));

        // 5. 退勤ボタンをクリック
        $component->call('endWork');

        // 6. ステータスが「退勤済」に変更されることを確認
        $this->assertEquals('finished', $component->get('currentStatus'));
        $component->assertSee('退勤済');
    }

    /**
     * テスト8: 退勤機能
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_end_time_displayed_in_attendance_list()
    {
        // 1. テストユーザーでログイン
        $this->actingAs($this->user);

        $component = Livewire::test(\App\Http\Livewire\AttendanceComponent::class);

        // 2. ステータスが「勤務外」であることを確認
        $this->assertEquals('before_work', $component->get('currentStatus'));
        $component->assertSee('出勤');
        $component->assertDontSee('退勤');

        // 3. 出勤処理を実行
        $fixedStartTime = Carbon::create(2023, 6, 1, 8, 0, 0);
        Carbon::setTestNow($fixedStartTime);

        $component->call('startWork');

        // 4. データベースに出勤レコードが作成されることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => $fixedStartTime->format('Y-m-d'),
            'start_time' => $fixedStartTime->format('H:i:s'),
            'end_time' => null,
        ]);

        // 5. 退勤処理を実行
        $fixedEndTime = Carbon::create(2023, 6, 1, 18, 0, 0);
        Carbon::setTestNow($fixedEndTime);

        $component->call('endWork');

        // 6. データベースに退勤時刻が記録されることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => $fixedStartTime->format('Y-m-d'),
            'start_time' => $fixedStartTime->format('H:i:s'),
            'end_time' => $fixedEndTime->format('H:i:s'),
        ]);

        // 7. 勤怠一覧画面（一般ユーザー）を開く
        $response = $this->post('/attendance/list', [
            'year' => $fixedStartTime->year,
            'month' => $fixedStartTime->month,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceData');

        // 8. ビューデータの取得と検証
        $viewData = $response->viewData('attendanceData');

        // 9. 該当日のデータが存在することを確認（数値インデックス配列）
        $targetDay = $fixedStartTime->day; // 1日
        $arrayIndex = $targetDay - 1;

        $this->assertArrayHasKey($arrayIndex, $viewData,
            "Array index {$arrayIndex} not found. Array has " . count($viewData) . " elements.");

        // 10. 勤怠データの存在確認
        $dailyData = $viewData[$arrayIndex];
        $this->assertNotNull($dailyData['attendance'], 'Attendance data should not be null for day ' . $targetDay);

        $attendanceRecord = $dailyData['attendance'];

        // 11. 出勤・退勤時刻が正しく記録されていることを確認
        $this->assertEquals(
            $fixedStartTime->format('H:i'),
            Carbon::parse($attendanceRecord->start_time)->format('H:i'),
            'Start time should match the fixed time'
        );

        $this->assertEquals(
            $fixedEndTime->format('H:i'),
            Carbon::parse($attendanceRecord->end_time)->format('H:i'),
            'End time should match the fixed time'
        );

        // 12. 勤怠一覧画面での表示を確認
        $response->assertSee($fixedStartTime->format('H:i'), false); // 出勤時刻表示
        $response->assertSee($fixedEndTime->format('H:i'), false);   // 退勤時刻表示
        $response->assertSee($fixedStartTime->format('m/d'), false); // 日付表示

        // テスト時刻をリセット
        Carbon::setTestNow();
    }

    /**
     * 補助テスト: 未ログインユーザーのアクセステスト
     */
    public function test_unauthenticated_user_cannot_access_attendance_register_screen()
    {
        $response = $this->get('/attendance');

        // ログインページにリダイレクトされることを確認
        $response->assertRedirect('/login');
    }
}
