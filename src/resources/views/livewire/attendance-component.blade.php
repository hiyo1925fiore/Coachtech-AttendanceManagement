<div class="attendance-register-content">
    {{-- resources/views/livewire/attendance-component.blade.php --}}
    <div class="status-display">
        <span class="status-badge">{{ $this->getStatusText() }}</span>
    </div>

    <div class="date-time-display">
        <h2 class="date">{{ $selectedDate }}</h2>
        <div class="time" id="current-time">{{ $this->getCurrentTime() }}</div>
    </div>

    <div class="button-container">
        @if($currentStatus === 'before_work')
            <button wire:click="startWork" class="button__attendance">出勤</button>
        @elseif($currentStatus === 'working')
            <button wire:click="endWork" class="button__attendance">退勤</button>
            <button wire:click="startBreak" class="button__break-time">休憩入</button>
        @elseif($currentStatus === 'on_break')
            <button wire:click="endBreak" class="button__break-time">休憩戻</button>
        @elseif($currentStatus === 'finished')
            <div class="completion-message">お疲れ様でした。</div>
        @endif
    </div>
</div>
