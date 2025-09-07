<div class="request-list__content">
    {{-- resources/views/livewire/request-list-component.blade.php --}}
    <div class="heading-title">
        <h1 class="heading-title__text">申請一覧</h1>
    </div>

    <div class="list__tab-buttons">
        <button
            wire:click="selectTab('unapproved')"
            class="tab-button{{ $activeTab === 'unapproved' ? '--active' : '' }}">
            承認待ち
        </button>
        <button
            wire:click="selectTab('approved')"
            class="tab-button{{ $activeTab === 'approved' ? '--active' : '' }}">
            承認済み
        </button>
    </div>
    <div class="list__container">
        <table class="list__table">
            @if($activeTab === 'unapproved')
                @if($this->unapprovedRequests->count() >0)
                    <tr class="list__table--header-row">
                        <th class="table-header">状態</th>
                        <th class="table-header">名前</th>
                        <th class="table-header">対象日時</th>
                        <th class="table-header">申請理由</th>
                        <th class="table-header">申請日時</th>
                        <th class="table-header--detail">詳細</th>
                    </tr>
                @endif

                @foreach($this->unapprovedRequests as $data)
                    <tr class="list__table--row">
                        <!-- 承認状態 -->
                        <td class="table-content--status">
                            <p class="table-content--text">承認待ち</p>
                        </td>
                        <!-- ユーザー名 -->
                        <td class="table-content--name">
                            <p class="table-content__text">{{ $data->user->name }}</p>
                        </td>
                        <!-- 対象日時 -->
                        <td class="table-content--date">
                            <p class="table-content__text">{{ $data->attendance->date->format('Y/m/d ') }}</p>
                        </td>
                        <!-- 申請理由 -->
                        <td class="table-content--note">
                            <p class="table-content__text">{{ $data->note }}</p>
                        </td>
                        <!-- 申請日時 -->
                        <td class="table-content--created-at">
                            <p class="table-content__text">{{ $data->created_at->format('Y/m/d ') }}</p>
                        </td>
                        <!-- 勤怠詳細画面へのリンク -->
                        <td class="table-content--detail">
                            @if($userType === 'user')
                                <!-- 一般ユーザー用 -->
                                <a href="{{ route('attendance.detail', ['date' => $data->attendance->date->format('Y-m-d')]) }}" class="detail-link">
                                    詳細
                                </a>
                            @else
                                <!-- 管理者用 -->
                                <a href="{{ route('admin.request.detail', ['id' => $data->id]) }}" class="detail-link">
                                    詳細
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if($this->unapprovedRequests->isEmpty())
                    <tr class="list__table-row--no-item">
                        <td colspan="6" class="table-content--no-item">
                            <p class="table-content-text">承認待ちの申請はありません</p>
                        </td>
                    </tr>
                @endif

            @elseif($activeTab === 'approved')
                @if($this->approvedRequests->count() >0)
                    <tr class="list__table--header-row">
                        <th class="table-header--status">状態</th>
                        <th class="table-header">名前</th>
                        <th class="table-header">対象日時</th>
                        <th class="table-header">申請理由</th>
                        <th class="table-header">申請日時</th>
                        <th class="table-header">詳細</th>
                    </tr>
                @endif

                @foreach($this->approvedRequests as $data)
                    <tr class="list__table--row">
                        <!-- 承認状態 -->
                        <td class="table-content--status">
                            <p class="table-content--text">承認済み</p>
                        </td>
                        <!-- ユーザー名 -->
                        <td class="table-content--name">
                            <p class="table-content__text">{{ $data->user->name }}</p>
                        </td>
                        <!-- 対象日時 -->
                        <td class="table-content--date">
                            <p class="table-content__text">{{ $data->attendance->date->format('Y/n/j ') }}</p>
                        </td>
                        <!-- 申請理由 -->
                        <td class="table-content--note">
                            <p class="table-content__text">{{ $data->note }}</p>
                        </td>
                        <!-- 申請日時 -->
                        <td class="table-content--created-at">
                            <p class="table-content__text">{{ $data->created_at->format('Y/n/j ') }}</p>
                        </td>
                        <!-- 勤怠詳細画面へのリンク -->
                        <td class="table-content--detail">
                            @if($userType === 'user')
                                <!-- 一般ユーザー用 -->
                                <a href="{{ route('attendance.detail', ['date' => $data->attendance->date->format('Y-m-d')]) }}" class="detail-link">
                                    詳細
                                </a>
                            @else
                                <!-- 管理者用 -->
                                <a href="{{ route('admin.request.detail', ['id' => $data->id]) }}" class="detail-link">
                                    詳細
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if($this->approvedRequests->isEmpty())
                    <tr class="list__table-row--no-item">
                        <td colspan="6" class="table-content--no-item">
                            <p class="table-content-text">承認済みの申請はありません</p>
                        </td>
                    </tr>
                @endif
            @endif
        </table>
    </div>
</div>
