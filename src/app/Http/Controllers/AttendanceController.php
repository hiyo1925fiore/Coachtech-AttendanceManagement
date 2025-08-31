<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest as AttendanceRequestModel;
use App\Models\BreakTimeRequest;
use App\Http\Requests\AttendanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 管理者が一般ユーザーページにアクセスした場合のチェック
     */
    private function checkUserAccess(): ?RedirectResponse
    {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.attendance.list');
        }
        return null;
    }

    /**
     * 出勤登録画面表示
     */
    public function index(){
        // 管理者アクセスチェック
        if ($redirect = $this->checkUserAccess()) {
            return $redirect;
        }

        return view('attendance_register');
    }

    /**
     * 勤怠一覧画面（一般ユーザー）表示
     */
    public function showAttendanceList(Request $request){
        // 管理者アクセスチェック
        if ($redirect = $this->checkUserAccess()) {
            return $redirect;
        }

        // 年月の取得（デフォルトは現在の年月）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        // 指定された年月の1日を作成
        $currentDate = Carbon::create($year, $month, 1);

        // その月の日数を取得
        $daysInMonth = $currentDate->daysInMonth;

        // ログインユーザーのIDを取得
        $userId = Auth::id();

        // 指定された年月の勤怠データを取得
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('breakTimes')
            ->get()
            ->keyBy(function($item){
                // Carbonインスタンスをymd形式の文字列に変換してキーにする
            return $item->date->format('Y-m-d');
            }); // 日付をキーとした配列に変換

        // 月の各日付に対応する勤怠データを作成
        $attendanceData = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentDate->copy()->day($day);
            $dateString = $date->format('Y-m-d');

            if (isset($attendances[$dateString])) {
                $attendance = $attendances[$dateString];
                $breakTime = $this->calculateBreakTime($attendance->breakTimes);

                $attendanceData[] = [
                    'date' => $date,
                    'attendance' => $attendance,
                    'break_time' => $breakTime,
                    'work_time' => $this->calculateWorkTime($attendance, $breakTime),
                ];
            } else {
                $attendanceData[] = [
                    'date' => $date,
                    'attendance' => null,
                    'break_time' => null,
                    'work_time' => null,
                ];
            }
        }

        // 前月・翌月のリンク用データ
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('attendance_list', compact(
            'attendanceData',
            'currentDate',
            'previousMonth',
            'nextMonth',
            'year',
            'month'
        ));
    }

    /**
     * 勤怠詳細画面（一般ユーザー）表示
     */
    public function showDetail($date){
        // 管理者アクセスチェック
        if ($redirect = $this->checkUserAccess()) {
            return $redirect;
        }

        // 日付の妥当性チェック
        try {
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            abort(404, '無効な日付です');
        }

        $user_id = Auth::id();

        // 指定日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user_id)
            ->whereDate('date', $targetDate)
            ->first();

        if (!$attendance) {
            // 勤怠データが存在しない場合は空のインスタンスを作成
            $attendance = new Attendance([
                'user_id' => $user_id,
                'date' => $date
            ]);
        }

        // 未承認の修正申請があるかチェック
        $hasUnapprovedRequest = AttendanceRequestModel::where('attendance_id', $attendance->id)
            ->where('is_approved', 0)
            ->exists();

        //未承認の申請内容を取得
        $unapprovedRequest = AttendanceRequestModel::where('attendance_id', $attendance->id)
            ->where('is_approved', 0)
            ->with('breakTimeRequests')
            ->first();

        return view('attendance_detail', compact('attendance', 'hasUnapprovedRequest', 'unapprovedRequest' , 'date'));
    }

    /**
     * 申請一覧画面（一般ユーザー）表示
     */
    public function showRequestList(){
        // 管理者アクセスチェック
        if ($redirect = $this->checkUserAccess()) {
            return $redirect;
        }

        return view('request_list', ['userType' => 'user']);
    }

    /**
     * 勤怠修正申請送信処理
     */
    public function postRequest(AttendanceRequest $request, $date)
    {
        // 日付の妥当性チェック
        try {
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            abort(404, '無効な日付です');
        }

        $user_id = Auth::id();

        // 勤怠データを取得（存在しない場合は新規作成）
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user_id,
                'date' => $date
            ],
            [
                'start_time' => null,
                'end_time' => null,
                'note' => null
            ]
        );

        // 勤怠修正申請を保存
        $attendanceRequest = AttendanceRequestModel::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user_id,
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time . ':00',
            'note' => $request->note,
            'is_approved' => '0', // 承認待ち
        ]);

        // 休憩時間の申請を保存
        $this->saveBreakTimeRequests($attendanceRequest, $request);

        return redirect()->route('attendance.detail', $date)
            ->with('success', '修正申請を送信しました。管理者の承認をお待ちください。');
    }

    /**
     * 休憩時間の修正申請の保存
     */
    private function saveBreakTimeRequests($attendanceRequest, $request)
    {
        // リクエストから休憩時間の申請を取得
        $breakStartTimes = $request->input('break_start_time', []);
        $breakEndTimes = $request->input('break_end_time', []);

        // 既存の休憩時間と新規追加分を両方処理
        foreach ($breakStartTimes as $index => $startTime) {
            $endTime = $breakEndTimes[$index] ?? null;

            // 両方の時間が入力されており、空でない場合のみ保存
            if (!empty($startTime) && !empty($endTime)) {
                BreakTimeRequest::create([
                    'attendance_request_id' => $attendanceRequest->id,
                    'start_time' => $startTime . ':00',
                    'end_time' => $endTime . ':00',
                ]);
            }
        }
    }

    /**
     * 勤怠一覧画面（管理者）表示
     */
    public function showAdminAttendance(Request $request){
        // 年月日の取得（デフォルトは現在の年月日）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $day = $request->get('day', Carbon::now()->day);

        // 指定された年月日を作成
        $currentDate = Carbon::create($year, $month, $day);

        // 指定された年月日の勤怠データを取得
        $attendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereDay('date', $day)
            ->whereNotNull('start_time')
            ->with('breakTimes', 'user')
            ->orderBy('user_id', 'asc')
            ->get();

        // 各日付に対応する勤怠データを作成
        $attendanceData = [];

        foreach ($attendances as $attendance) {
            $breakTime = $this->calculateBreakTime($attendance->breakTimes);

            $attendanceData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'attendance' => $attendance,
                'break_time' => $breakTime,
                'work_time' => $this->calculateWorkTime($attendance, $breakTime),
            ];
        }

        // 前日・翌日のリンク用データ
        $previousDay = $currentDate->copy()->subDay();
        $nextDay = $currentDate->copy()->addDay();

        // ユーザー別の勤怠データをセッションに保存
        $attendanceByUserAndDate = [];

        foreach ($attendances as $attendance) {
            $dateStr = $currentDate->format('Y-m-d');
            $attendanceByUserAndDate[$dateStr][$attendance->user_id] = [
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id
            ];
        }

        $request->session()->put('attendance_by_user_date', $attendanceByUserAndDate);

        return view('admin.admin_attendance_list', compact(
            'attendanceData',
            'currentDate',
            'previousDay',
            'nextDay',
            'year',
            'month',
            'day'
        ));
    }

    /**
     * 一般ユーザーのIDをセッションに保存
     */
    public function setAttendanceUser(Request $request){
        \Log::info('setAttendanceUser called via POST', [
            'user_id' => $request->user_id,
            'date' => $request->date,
            'redirect_url' => $request->redirect_url
        ]);

        $request->session()->put('selected_attendance_user_id', $request->user_id);
        $request->session()->put('selected_attendance_date', $request->date);

        \Log::info('Session data set via POST', [
            'selected_attendance_user_id' => $request->session()->get('selected_attendance_user_id'),
            'selected_attendance_date' => $request->session()->get('selected_attendance_date')
        ]);

        return redirect($request->redirect_url);
    }

    /**
     * 勤怠詳細画面（管理者）表示
     */
    public function showAdminDetail(Request $request, $date){
        // 日付の妥当性チェック
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            abort(404, '無効な日付形式です');
        }

        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];

        if (!checkdate($month, $day, $year)) {
            abort(404, '無効な日付です');
        }

        try {
            $targetDate = Carbon::create($year, $month, $day);
        } catch (\Exception $e) {
            abort(404, '日付の作成に失敗しました');
        }

        $userId = null;

        // セッションから各種データを取得
        $selectedUserId = $request->session()->get('selected_attendance_user_id');
        $selectedDate = $request->session()->get('selected_attendance_date');
        $staffUserId = $request->session()->get('current_staff_user_id');

        // 日付が一致する場合は選択されたユーザーIDを使用
        if ($selectedUserId && $selectedDate === $date) {
            $userId = $selectedUserId;
        }

        // スタッフ別勤怠一覧画面（管理者）からアクセスした場合の処理
        if (!$userId) {
            $userId = $staffUserId;
        }

        if (!$userId) {
            abort(404, 'ユーザー情報が取得できません');
        }

        // 指定日の勤怠データを取得（無ければ新規のインスタンスを作成）
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $targetDate)
            ->with('user', 'breakTimes')
            ->first();

        if (!$attendance) {
            $user = User::find($userId);
        if (!$user) {
            abort(404, 'ユーザーが見つかりません');
        }
            $attendance = new Attendance([
                'user_id' => $userId,
                'date' => $targetDate
            ]);
            $attendance->user = $user;
            $attendance->breakTimes = collect();
        }

        return view('admin.admin_attendance_detail', compact('attendance', 'userId', 'date'));
    }

    /**
     * 勤怠修正処理
     */
    public function updateDetail(AttendanceRequest $request, $date)
    {
        $attendance = Attendance::with('breakTimes')
            ->findOrFail($request->id);

        try {
            // データベースの更新をトランザクションで実行
            \DB::beginTransaction();

            // Attendancesテーブルを更新
            $attendance->update([
                'start_time' => $request->start_time . ':00',
                'end_time' => $request->end_time . ':00',
                'note' => $request->note,
            ]);

            // 既存の休憩時間を更新・削除
            if (isset($request->break_time_ids)) {
                foreach ($request->break_time_ids as $index => $breakTimeId) {
                    $breakTime = BreakTime::find($breakTimeId);

                    if ($breakTime) {
                        $breakStartTime = $request['break_start_time'][$index] ?? null;
                        $breakEndTime = $request['break_end_time'][$index] ?? null;

                        if ($breakStartTime && $breakEndTime) {
                            // 更新処理
                            $breakTime->update([
                                'start_time' => $breakStartTime,
                                'end_time' => $breakEndTime,
                            ]);
                        } else {
                            // 空の場合は削除
                            $breakTime->delete();
                        }
                    }
                }
            }

            // 新しい休憩時間を追加
            if (isset($request['break_start_time']) && isset($request['break_end_time'])) {
                $existingBreakCount = count($request->break_time_ids ?? []);

                for ($i = $existingBreakCount; $i < count($request['break_start_time']); $i++) {
                    $breakStartTime = $request['break_start_time'][$i] ?? null;
                    $breakEndTime = $request['break_end_time'][$i] ?? null;

                    if ($breakStartTime && $breakEndTime) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'start_time' => $breakStartTime,
                            'end_time' => $breakEndTime,
                        ]);
                    }
                }
            }

        \DB::commit();

        return redirect()->route('admin.attendance.detail.update', $date)
            ->with('success', '勤怠を修正しました。');

        } catch (\Exception $e) {
            \DB::rollback();
            return back()->withErrors(['error' => '更新中にエラーが発生しました。'])->withInput();
        }
    }

    /**
     * スタッフ別勤怠一覧画面（管理者）表示
     */
    public function showStaffAttendance(Request $request, $user){
        // 年月の取得（デフォルトは現在の年月）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        // 指定された年月の1日を作成
        $currentDate = Carbon::create($year, $month, 1);

        // その月の日数を取得
        $daysInMonth = $currentDate->daysInMonth;

        // 指定した一般ユーザーの情報を取得
        $userId = $user;
        $userName = User::where('id', $userId)
            ->first(['name']);

        // 指定された年月の勤怠データを取得
        $attendances = Attendance::where('user_id', $user)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('breakTimes')
            ->get()
            ->keyBy(function($item){
                // Carbonインスタンスをymd形式の文字列に変換してキーにする
            return $item->date->format('Y-m-d');
            }); // 日付をキーとした配列に変換

        // 月の各日付に対応する勤怠データを作成
        $attendanceData = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentDate->copy()->day($day);
            $dateString = $date->format('Y-m-d');

            if (isset($attendances[$dateString])) {
                $attendance = $attendances[$dateString];
                $breakTime = $this->calculateBreakTime($attendance->breakTimes);

                $attendanceData[] = [
                    'date' => $date,
                    'attendance' => $attendance,
                    'break_time' => $breakTime,
                    'work_time' => $this->calculateWorkTime($attendance, $breakTime),
                ];
            } else {
                $attendanceData[] = [
                    'date' => $date,
                    'attendance' => null,
                    'break_time' => null,
                    'work_time' => null,
                ];
            }
        }

        // 前月・翌月のリンク用データ
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        // 指定したユーザーのIDをセッションに保存
    $request->session()->put('current_staff_user_id', $userId);

        return view('admin.staff_attendance_list', compact(
            'attendanceData',
            'currentDate',
            'userId',
            'userName',
            'previousMonth',
            'nextMonth',
            'year',
            'month'
        ));
    }

    /**
     * 一般ユーザーの勤怠データをCSV出力
     */
    public function exportStaffAttendanceCsv(Request $request, $user)
    {
        // 年月の取得
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        // 指定された年月の1日を作成
        $currentDate = Carbon::create($year, $month, 1);

        // その月の日数を取得
        $daysInMonth = $currentDate->daysInMonth;

        // 指定された一般ユーザーの情報を取得
        $userName = User::where('id', $user)->first()->name;

        // 指定された年月の勤怠データを取得
        $attendances = Attendance::where('user_id', $user)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('breakTimes')
            ->get()
            ->keyBy(function($item){
                return $item->date->format('Y-m-d');
            });

        // CSVデータを準備
        $csvData = [];

        // CSVヘッダー
        $csvData[] = [
            '日付',
            '曜日',
            '出勤',
            '退勤',
            '休憩',
            '合計'
        ];

        // 各日のデータを作成
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentDate->copy()->day($day);
            $dateString = $date->format('Y-m-d');
            $dayOfWeek = $this->getDayOfWeekInJapanese($date->dayOfWeek);

            if (isset($attendances[$dateString])) {
                $attendance = $attendances[$dateString];
                $breakTimeMinutes = $this->calculateBreakTime($attendance->breakTimes);
                $workTimeMinutes = $this->calculateWorkTime($attendance, $breakTimeMinutes);

                $csvData[] = [
                    $date->format('m/d'),
                    $dayOfWeek,
                    $attendance->start_time ? $attendance->start_time->format('H:i') : '',
                    $attendance->end_time ? $attendance->end_time->format('H:i') : '',
                    $breakTimeMinutes ? $this->formatMinutesToTime($breakTimeMinutes) : '',
                    $workTimeMinutes ? $this->formatMinutesToTime($workTimeMinutes) : ''
                ];
            } else {
                $csvData[] = [
                    $date->format('m/d'),
                    $dayOfWeek,
                    '',
                    '',
                    '',
                    ''
                ];
            }
        }

        // CSVファイル名を作成
        $filename = sprintf('%s_%04d年%02d月_勤怠記録.csv', $userName, $year, $month);

        // レスポンスヘッダーを設定
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // CSVレスポンスを作成
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');

            // BOM（Byte Order Mark）を追加してExcelでの文字化けを防ぐ
            fputs($file, "\xEF\xBB\xBF");

            // CSVデータを出力
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 申請一覧画面（管理者）表示
     */
    public function showAdminRequestList(){
        return view('admin.admin_request_list', ['userType' => 'admin']);
    }

    /**
     * 修正申請承認画面（管理者）表示
     */
    public function showRequest($id){
        // 勤怠修正申請を取得
        $attendanceRequest = AttendanceRequestModel::where('id', $id)
            ->with('user', 'attendance', 'breakTimeRequests')
            ->first();

        // レコードが存在しない場合はエラー
        if (!$attendanceRequest) {
            abort(404, '指定された修正申請が見つかりません。');
        }

        return view('admin.approval', compact('attendanceRequest', 'id'));
    }

    /**
     * 曜日を日本語で取得
     */
    private function getDayOfWeekInJapanese($dayOfWeek)
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        return $days[$dayOfWeek];
    }

    /**
     * 分を時間:分の形式に変換
     */
    private function formatMinutesToTime($minutes)
    {
        if ($minutes === null || $minutes === 0) {
            return '';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($breakTimes)
    {
        if ($breakTimes->isEmpty()) {
            return 0;
        }

        $totalBreakMinutes = 0;
        foreach ($breakTimes as $breakTime) {
            if ($breakTime->start_time && $breakTime->end_time) {
                $start = Carbon::parse($breakTime->start_time);
                $end = Carbon::parse($breakTime->end_time);
                $totalBreakMinutes += $end->diffInMinutes($start);
            }
        }

        return $totalBreakMinutes;
    }

    /**
     * 労働時間を計算
     */
    private function calculateWorkTime($attendance, $breakTimeMinutes)
    {
        if (!$attendance->start_time || !$attendance->end_time) {
            return null;
        }

        $start = Carbon::parse($attendance->start_time);
        $end = Carbon::parse($attendance->end_time);
        $totalMinutes = $end->diffInMinutes($start);

        // 休憩時間を差し引く
        $workMinutes = $totalMinutes - ($breakTimeMinutes ?? 0);

        return max(0, $workMinutes); // 負の値にならないように
    }
}
