<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\Auth;

class RequestListComponent extends Component
{
    public $activeTab = 'unapproved';
    public $user;

    public function mount()
    {
        $this->user = Auth::user();

        // 初期表示は「承認待ち」タブ
        $this->activeTab = 'unapproved';
    }

    public function selectTab($tab)
    {
        $this->activeTab = $tab;
    }

    // 動的にデータを取得するメソッド（renderメソッドから呼び出し）
    // 承認待ちの申請を取得
    public function getUnapprovedRequestsProperty()
    {
        return AttendanceRequest::where('user_id', $this->user->id)
            ->where('is_approved', 0)
            ->with(['user', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // 承認済みの申請を取得
    public function getApprovedRequestsProperty()
    {
        return AttendanceRequest::where('user_id', $this->user->id)
            ->where('is_approved', 1)
            ->with(['user', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.request-list-component');
    }
}
