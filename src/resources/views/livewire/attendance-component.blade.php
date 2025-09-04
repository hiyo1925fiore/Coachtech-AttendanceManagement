<div class="attendance-register-content">
    {{-- resources/views/livewire/attendance-component.blade.php --}}
    <!-- ステータスをHTMLデータ属性として埋め込み -->
    <div id="attendance-status-data"
        data-current-status="{{ $currentStatus }}"
        style="display: none;"></div>

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

<script>
    // ページロード時に確実にステータスを設定
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Attendance Component] DOMContentLoaded fired');

        const statusElement = document.getElementById('attendance-status-data');
        if (statusElement && window.headerStatusManager) {
            const currentStatus = statusElement.getAttribute('data-current-status');
            console.log('[Attendance Component] Setting status from HTML data attribute:', currentStatus);
            window.headerStatusManager.updateStatus(currentStatus);
            window.headerStatusManager.markAsInitialized();
        } else {
            console.warn('[Attendance Component] Status element or headerStatusManager not found');
            console.log('Status element:', statusElement);
            console.log('HeaderStatusManager:', window.headerStatusManager);
        }
    });

    // Livewireイベントも継続して監視（リアルタイム更新用）
    document.addEventListener('livewire:load', function () {
        console.log('[Attendance Component] Livewire loaded');

        @this.on('statusUpdated', function(status) {
            console.log('[Attendance Component] Livewire statusUpdated event received:', status);
            if (window.headerStatusManager) {
                window.headerStatusManager.updateStatus(status);
            }
        });
    });

    // 追加の初期化処理（フォールバック）
    setTimeout(function() {
        const statusElement = document.getElementById('attendance-status-data');
        if (statusElement && window.headerStatusManager && !window.headerStatusManager.isInitialized) {
            const currentStatus = statusElement.getAttribute('data-current-status');
            console.log('[Attendance Component] Fallback initialization with status:', currentStatus);
            window.headerStatusManager.updateStatus(currentStatus);
            window.headerStatusManager.markAsInitialized();
        }
    }, 1000);
</script>
