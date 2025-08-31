<div class="content">
    {{-- resources/views/livewire/approval-component.blade.php --}}
    <div class="alert">
    @if(session('success'))
        <div class="alert--success">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="alert--error">
            {{ session('error') }}
        </div>
    @endif
    </div>

    <div class="detail-content">
        <div class="heading-title">
            <h1 class="heading-title__text">勤怠詳細</h1>
        </div>

        <div class="attendance-detail">
            <table class="attendance-detail__table">
                <tr class="table-row">
                    <th class="table-header">名前</th>
                    <td class="table-content--name">
                        <p class="table-data__text">{{ $attendanceRequest->user->name }}</p>
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">日付</th>
                    <td class="table-content--date">
                        <p class="table-data__text">{{ $attendanceRequest->attendance->date->format('Y年') }}</p>
                        <p class="table-data__text">{{ $attendanceRequest->attendance->date->format('n月j日') }}</p>
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">出勤・退勤</th>
                    <td class="table-content--attendance">
                        <div class="table-data__inner">
                            <p class="table-data__text--start-time">{{ $attendanceRequest->start_time ? \Carbon\Carbon::parse($attendanceRequest->start_time)->format('H:i') : '' }}</p>
                            <span class="wave-dash">～</span>
                            <p class="table-data__text--end-time">{{ $attendanceRequest->end_time ? \Carbon\Carbon::parse($attendanceRequest->end_time)->format('H:i') : '' }}</p>
                        </div>
                    </td>
                </tr>

                @foreach($attendanceRequest->breakTimeRequests as $index => $breakTimeRequest)
                <tr class="table-row">
                    <th class="table-header">{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td class="table-content--break-time">
                        <div class="table-data__inner">
                            <p class="table-data__text--start-time">{{ \Carbon\Carbon::parse($breakTimeRequest->start_time)->format('H:i') }}</p>
                            <span class="wave-dash">～</span>
                            <p class="table-data__text--end-time">{{ \Carbon\Carbon::parse($breakTimeRequest->end_time)->format('H:i') }}</p>
                        </div>
                    </td>
                </tr>
                @endforeach

                <!-- 新規入力欄（空白）-->
                @php
                    $newIndex = count($attendanceRequest->breakTimeRequests);
                    $newLabelNumber = $newIndex + 1;
                @endphp
                <tr class="table-row">
                    <th class="table-header">{{ $newIndex == 0 ? '休憩' : '休憩' . ($newLabelNumber) }}</th>
                    <td class="table-content--break-time">
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">備考</th>
                    <td class="table-content--note">
                        <p class="table-data__text--note">{{ $attendanceRequest->note }}</p>
                    </td>
                </tr>
            </table>

            <div class="correction-request-form__button">
                @if($isApproved)
                    <button class="correction-request-form__button-approved" disabled>承認済み</button>
                @else
                    <button class="correction-request-form__button-submit" type="button" wire:click="approveRequest">承認</button>
                @endif
            </div>
        </div>
    </div>
</div>
