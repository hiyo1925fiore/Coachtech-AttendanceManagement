/**
 * ヘッダーのステータス管理（デバッグ強化版）
 */
class HeaderStatusManager {
    constructor() {
        this.currentStatus = "before_work";
        this.isInitialized = false;
        this.debug = true; // デバッグモードを有効化
        this.retryCount = 0;
        this.maxRetries = 5;
        this.retryInterval = 1000; // 1秒間隔でリトライ

        this.log("HeaderStatusManager initialized");
        this.init();
    }

    /**
     * デバッグログ出力
     */
    log(message, ...args) {
        if (this.debug) {
            console.log(`[HeaderStatusManager] ${message}`, ...args);
        }
    }

    /**
     * 初期化
     */
    init() {
        this.log("Starting initialization");

        if (document.readyState === "loading") {
            this.log("Document still loading, waiting for DOMContentLoaded");
            document.addEventListener("DOMContentLoaded", () => {
                this.setupEventListeners();
            });
        } else {
            this.log(
                "Document already loaded, setting up listeners immediately"
            );
            this.setupEventListeners();
        }
    }

    /**
     * イベントリスナーの設定
     */
    setupEventListeners() {
        this.log("Setting up event listeners");

        // ブラウザイベントリスナーを最初に設定
        this.setupBrowserEventListeners();

        // Livewireの設定
        this.setupLivewireEventListeners();

        // 初期ステータスの取得を試行
        this.attemptInitialStatusRetrieval();
    }

    /**
     * Livewireイベントリスナーの設定
     */
    setupLivewireEventListeners() {
        if (typeof window.Livewire !== "undefined") {
            this.log("Livewire is available, setting up listeners");
            this.registerLivewireEvents();
        } else {
            this.log("Livewire not yet available, waiting for livewire:load");
            document.addEventListener("livewire:load", () => {
                this.log("livewire:load event fired");
                this.registerLivewireEvents();
            });

            // Livewireの読み込み待ちタイムアウト
            setTimeout(() => {
                if (typeof window.Livewire === "undefined") {
                    this.log(
                        "Warning: Livewire still not available after timeout"
                    );
                } else {
                    this.log("Livewire became available after timeout");
                    this.registerLivewireEvents();
                }
            }, 3000);
        }
    }

    /**
     * Livewireイベントの登録
     */
    registerLivewireEvents() {
        try {
            // ステータス更新イベント
            Livewire.on("statusUpdated", (status) => {
                this.log("Received Livewire statusUpdated:", status);
                this._updateStatus(status);
                this.markAsInitialized();
            });

            // ステータス変更イベント
            Livewire.on("statusChanged", (message) => {
                this.log("Received Livewire statusChanged:", message);
            });

            this.log("Livewire event listeners registered successfully");
        } catch (error) {
            this.log("Error registering Livewire events:", error);
        }
    }

    /**
     * ブラウザイベントリスナーの設定
     */
    setupBrowserEventListeners() {
        // 初期ステータス設定イベント
        window.addEventListener("set-global-status", (event) => {
            this.log("Received set-global-status event:", event.detail);
            if (event.detail && event.detail.status) {
                this._updateStatus(event.detail.status);
                this.markAsInitialized();
            }
        });

        // ステータス更新イベント
        window.addEventListener("status-updated", (event) => {
            this.log("Received status-updated event:", event.detail);
            if (event.detail && event.detail.status) {
                this._updateStatus(event.detail.status);
                this.markAsInitialized();
            }
        });

        this.log("Browser event listeners set up");
    }

    /**
     * 初期ステータス取得を試行
     */
    attemptInitialStatusRetrieval() {
        this.log("Attempting initial status retrieval");

        const attempt = () => {
            if (this.isInitialized) {
                this.log("Already initialized, skipping retrieval");
                return;
            }

            this.retryCount++;
            this.log(
                `Attempt ${this.retryCount}/${this.maxRetries} to get initial status`
            );

            // 方法1: グローバル変数から取得を試行
            if (window.initialAttendanceStatus) {
                this.log(
                    "Found global initialAttendanceStatus:",
                    window.initialAttendanceStatus
                );
                this._updateStatus(window.initialAttendanceStatus);
                this.markAsInitialized();
                return;
            }

            // 方法2: Livewireコンポーネントから直接取得を試行
            if (this.tryGetStatusFromLivewireComponent()) {
                return;
            }

            // リトライ判定
            if (this.retryCount < this.maxRetries) {
                this.log(`Retrying in ${this.retryInterval}ms...`);
                setTimeout(attempt, this.retryInterval);
            } else {
                this.log(
                    "Max retries reached, giving up on automatic initialization"
                );
                this.log("Current status remains:", this.currentStatus);
            }
        };

        // 最初の試行は少し遅延させる
        setTimeout(attempt, 500);
    }

    /**
     * Livewireコンポーネントからステータスを取得
     */
    tryGetStatusFromLivewireComponent() {
        try {
            const wireElements = document.querySelectorAll("[wire\\:id]");
            this.log(`Found ${wireElements.length} Livewire elements`);

            for (let element of wireElements) {
                const wireId = element.getAttribute("wire:id");
                if (wireId && window.Livewire && window.Livewire.find) {
                    const component = window.Livewire.find(wireId);
                    if (component && component.get) {
                        const status = component.get("currentStatus");
                        if (status) {
                            this.log(
                                "Retrieved status from Livewire component:",
                                status
                            );
                            this._updateStatus(status);
                            this.markAsInitialized();
                            return true;
                        }
                    }
                }
            }
        } catch (error) {
            this.log(
                "Error trying to get status from Livewire component:",
                error
            );
        }
        return false;
    }

    /**
     * 初期化完了マーク
     */
    markAsInitialized() {
        if (!this.isInitialized) {
            this.isInitialized = true;
            this.log(
                "Initialization completed with status:",
                this.currentStatus
            );
        }
    }

    /**
     * 外部からステータスを更新（public method）
     */
    updateStatus(status) {
        const oldStatus = this.currentStatus;
        this.currentStatus = status;
        this.log(`Status updated: ${oldStatus} -> ${status}`);
        this.updateHeader();
    }

    /**
     * ステータス更新（内部用）
     */
    _updateStatus(status) {
        const oldStatus = this.currentStatus;
        this.currentStatus = status;
        this.log(`Status updated: ${oldStatus} -> ${status}`);
        this.updateHeader();
    }

    /**
     * ヘッダー更新
     */
    updateHeader() {
        const headerNav = document.querySelector(".header__nav ul");

        if (!headerNav) {
            this.log("Warning: Header navigation element not found");
            return;
        }

        this.log("Updating header HTML for status:", this.currentStatus);

        // ステータスに応じてヘッダーのHTMLを更新
        if (this.currentStatus === "finished") {
            headerNav.innerHTML = this.getFinishedHeaderHTML();
            this.log("Applied finished header HTML");
        } else {
            headerNav.innerHTML = this.getDefaultHeaderHTML();
            this.log("Applied default header HTML");
        }

        // アクティブなリンクのハイライト処理
        this.highlightActiveLink();
    }

    /**
     * 退勤済み時のヘッダーHTML
     */
    getFinishedHeaderHTML() {
        return `
            <li class="header__list-item">
                <a class="header__link" href="/attendance/list">今日の出勤一覧</a>
            </li>
            <li class="header__list-item">
                <a class="header__link" href="/stamp_correction_request/list">申請一覧</a>
            </li>
        `;
    }

    /**
     * 通常時のヘッダーHTML
     */
    getDefaultHeaderHTML() {
        return `
            <li class="header__list-item">
                <a class="header__link" href="/attendance">勤怠</a>
            </li>
            <li class="header__list-item">
                <a class="header__link" href="/attendance/list">勤怠一覧</a>
            </li>
            <li class="header__list-item">
                <a class="header__link" href="/stamp_correction_request/list">申請</a>
            </li>
        `;
    }

    /**
     * アクティブリンクのハイライト
     */
    highlightActiveLink() {
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll(".header__link");

        links.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href") === currentPath) {
                link.classList.add("active");
            }
        });
    }

    /**
     * 手動でステータスを設定（デバッグ用）
     */
    setStatus(status) {
        this.log("Manual status set:", status);
        this.currentStatus = status;
        this.updateHeader();
    }

    /**
     * 現在のステータスを取得
     */
    getStatus() {
        return this.currentStatus;
    }

    /**
     * デバッグ情報を出力
     */
    getDebugInfo() {
        return {
            currentStatus: this.currentStatus,
            isInitialized: this.isInitialized,
            retryCount: this.retryCount,
            livewireAvailable: typeof window.Livewire !== "undefined",
            wireElements: document.querySelectorAll("[wire\\:id]").length,
        };
    }
}

// グローバルに利用可能にする
window.HeaderStatusManager = HeaderStatusManager;

// インスタンスを自動作成
window.headerStatusManager = new HeaderStatusManager();
