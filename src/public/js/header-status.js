/**
 * ヘッダーのステータス管理
 */
class HeaderStatusManager {
    constructor() {
        this.currentStatus = "before_work"; // 初期値（後でLivewireから上書きされる）
        this.isInitialized = false;
        this.init();
    }

    /**
     * 初期化
     */
    init() {
        // DOMが完全に読み込まれた後に処理を開始
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () => {
                this.setupEventListeners();
            });
        } else {
            this.setupEventListeners();
        }
    }

    /**
     * イベントリスナーの設定
     */
    setupEventListeners() {
        // Livewireが読み込まれた後にイベントリスナーを設定
        document.addEventListener("livewire:load", () => {
            this.setupLivewireListeners();
        });

        // ブラウザイベントリスナーを設定
        this.setupBrowserEventListeners();
    }

    /**
     * Livewireイベントリスナーの設定
     */
    setupLivewireListeners() {
        // ステータス更新イベントを受信
        Livewire.on("statusUpdated", (status) => {
            console.log("Livewire statusUpdated:", status);
            this.updateStatus(status);
        });

        // ステータス変更イベントを受信（メッセージ表示用）
        Livewire.on("statusChanged", (message) => {
            console.log("Status changed:", message);
        });
    }

    /**
     * ブラウザイベントリスナーの設定
     */
    setupBrowserEventListeners() {
        // 初期ステータスイベント
        window.addEventListener("initial-status", (event) => {
            console.log("Initial status received:", event.detail.status);
            this.updateStatus(event.detail.status);
            this.isInitialized = true;
        });

        // ステータス更新イベント（ブラウザイベント版）
        window.addEventListener("status-updated", (event) => {
            console.log("Browser status-updated:", event.detail.status);
            this.updateStatus(event.detail.status);
        });
    }

    /**
     * ステータスを更新
     */
    updateStatus(status) {
        console.log("Updating status from", this.currentStatus, "to", status);
        this.currentStatus = status;
        this.updateHeader();
    }

    /**
     * ヘッダーの更新
     */
    updateHeader() {
        const headerNav = document.querySelector(".header__nav ul");

        if (!headerNav) {
            console.warn("Header navigation element not found");
            return;
        }

        console.log("Updating header for status:", this.currentStatus);

        // ステータスに応じてヘッダーのHTMLを更新
        if (this.currentStatus === "finished") {
            headerNav.innerHTML = this.getFinishedHeaderHTML();
        } else {
            headerNav.innerHTML = this.getDefaultHeaderHTML();
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
     * アクティブなリンクのハイライト
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
     * ステータスを手動で設定（デバッグ用）
     */
    setStatus(status) {
        this.currentStatus = status;
        this.updateHeader();
    }

    /**
     * 現在のステータスを取得
     */
    getStatus() {
        return this.currentStatus;
    }
}

// グローバルに利用可能にする
window.HeaderStatusManager = HeaderStatusManager;

// インスタンスを自動作成
window.headerStatusManager = new HeaderStatusManager();
