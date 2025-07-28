<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceComponent extends Component
{
    public $selectedDate;
    public $currentStatus = 'before_work'; // before_work, working, on_break, finished
    public $todayAttendance;
    public $activeBreak;

    public function mount()
    {
        $this->selectedDate = Carbon::today()->isoFormat('YYYY年M月D日(ddd)');
        $this->checkTodayStatus();

        // 初期ステータスをヘッダーに通知（少し遅延させてJavaScriptが準備完了してから送信）
        $this->dispatchBrowserEvent('initial-status', ['status' => $this->currentStatus]);
    }

    public function checkTodayStatus()
    {
        $today = Carbon::today();
        $userId = Auth::id();

        // 今日の勤怠レコードを取得
        $this->todayAttendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();

        // 現在進行中の休憩時間を取得
        $this->activeBreak = null;
        if ($this->todayAttendance) {
            $this->activeBreak = BreakTime::where('attendance_id', $this->todayAttendance->id)
                ->whereNull('end_time')
                ->first();
        }

        // ステータスの判定
        $this->determineCurrentStatus();

        // ステータスをヘッダーに通知（ブラウザイベントとLivewireイベント両方を送信）
        $this->emit('statusUpdated', $this->currentStatus);
        $this->dispatchBrowserEvent('status-updated', ['status' => $this->currentStatus]);
    }

    private function determineCurrentStatus()
    {
        if (!$this->todayAttendance) {
            $this->currentStatus = 'before_work';
        } elseif ($this->todayAttendance->end_time) {
            $this->currentStatus = 'finished';
        } elseif ($this->activeBreak) {
            $this->currentStatus = 'on_break';
        } else {
            $this->currentStatus = 'working';
        }
    }

    public function startWork()
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $userId = Auth::id();

        // 勤怠レコードを作成
        $this->todayAttendance = Attendance::create([
            'user_id' => $userId,
            'date' => $today,
            'start_time' => $now,
            'end_time' => null,
            'note' => null,
        ]);

        $this->currentStatus = 'working';
        $this->emit('statusChanged', '出勤しました');

        // ステータスをヘッダーに通知
        $this->emit('statusUpdated', $this->currentStatus);
    }

    public function endWork()
    {
        if ($this->todayAttendance) {
            // 進行中の休憩があれば終了
            if ($this->activeBreak) {
                $this->activeBreak->update([
                    'end_time' => Carbon::now()
                ]);
            }

            // 勤怠レコードを更新
            $this->todayAttendance->update([
                'end_time' => Carbon::now()
            ]);

            $this->currentStatus = 'finished';
            $this->emit('statusChanged', 'お疲れ様でした。');

            // ステータスをヘッダーに通知
        $this->emit('statusUpdated', $this->currentStatus);
        }
    }

    public function startBreak()
    {
        if ($this->todayAttendance) {
            $this->activeBreak = BreakTime::create([
                'attendance_id' => $this->todayAttendance->id,
                'start_time' => Carbon::now(),
                'end_time' => null,
            ]);

            $this->currentStatus = 'on_break';
            $this->emit('statusChanged', '休憩に入りました');
        }

        // ステータスをヘッダーに通知
        $this->emit('statusUpdated', $this->currentStatus);
    }

    public function endBreak()
    {
        if ($this->activeBreak) {
            $this->activeBreak->update([
                'end_time' => Carbon::now()
            ]);

            $this->activeBreak = null;
            $this->currentStatus = 'working';
            $this->emit('statusChanged', '休憩から戻りました');
        }

        // ステータスをヘッダーに通知
        $this->emit('statusUpdated', $this->currentStatus);
    }

    public function getCurrentTime()
    {
        return Carbon::now()->format('H:i');
    }

    public function getStatusText()
    {
        switch ($this->currentStatus) {
            case 'before_work':
                return '勤務外';
            case 'working':
                return '出勤中';
            case 'on_break':
                return '休憩中';
            case 'finished':
                return '退勤済';
            default:
                return '勤務外';
        }
    }

    public function render()
    {
        return view('livewire.attendance-component');
    }
}
