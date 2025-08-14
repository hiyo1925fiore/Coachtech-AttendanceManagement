<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

        return view('request_list');
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
            ->with('success', '修正リクエストを送信しました。管理者の承認をお待ちください。');
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
     * 勤怠詳細画面（管理者）表示
     */
    public function showAdminDetail(Request $request){
        // リクエストから勤怠のIDを取得
        $id = $request->id;

        // IDと一致する勤怠データを取得
        $attendance = Attendance::where('id', $id)
            ->with('user', 'breakTimes')
            ->first();

        return view('admin.admin_attendance_detail', compact('attendance', 'id'));
    }

    /**
     * 勤怠修正処理
     */
    public function updateDetail(AttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')
            ->findOrFail($id);

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

        return redirect()->route('admin.attendance.detail.update', $id)
            ->with('success', '勤怠を修正しました。');

        } catch (\Exception $e) {
            \DB::rollback();
            return back()->withErrors(['error' => '更新中にエラーが発生しました。'])->withInput();
        }
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
