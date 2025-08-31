<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\AttendanceRequest;
use App\Models\BreakTimeRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class ApprovalComponent extends Component
{
    public $attendanceRequestId;
    public $attendanceRequest;
    public $isApproved = false;

    public function mount($id)
    {
        $this->attendanceRequestId = $id;
        $this->loadAttendanceRequest();
    }

    public function loadAttendanceRequest()
    {
        $this->attendanceRequest = AttendanceRequest::where('id', $this->attendanceRequestId)
            ->with('user', 'attendance', 'breakTimeRequests')
            ->first();

        $this->isApproved = $this->attendanceRequest->is_approved == 1;
    }

    public function approveRequest()
    {
        try {
            DB::beginTransaction();

            // 1. attendancesテーブルを更新
            $attendance = Attendance::find($this->attendanceRequest->attendance_id);
            if ($attendance) {
                $attendance->update([
                    'start_time' => $this->attendanceRequest->start_time,
                    'end_time' => $this->attendanceRequest->end_time,
                    'note' => $this->attendanceRequest->note,
                ]);
            }

            // 2. break_timesテーブルを更新（既存レコードを全削除してから新規作成）
            // 既存の休憩時間レコードを全て削除
            BreakTime::where('attendance_id', $this->attendanceRequest->attendance_id)->delete();

            // break_time_requestsの内容で新しく作成
            foreach ($this->attendanceRequest->breakTimeRequests as $breakTimeRequest) {
                BreakTime::create([
                    'attendance_id' => $this->attendanceRequest->attendance_id,
                    'start_time' => $breakTimeRequest->start_time,
                    'end_time' => $breakTimeRequest->end_time,
                ]);
            }

            // 3. attendance_requestsのis_approvedを1に更新
            $this->attendanceRequest->update(['is_approved' => 1]);

            DB::commit();

            // 状態を更新
            $this->isApproved = true;

            // 成功メッセージを表示（必要に応じて）
            session()->flash('success', '承認が完了しました。');

        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', '承認処理中にエラーが発生しました。');
        }
    }

    public function render()
    {
        return view('livewire.approval-component');
    }
}
