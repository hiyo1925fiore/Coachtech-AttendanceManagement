function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");
    const timeString = `${hours}:${minutes}`;

    const timeElement = document.getElementById("current-time");
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// 1秒ごとに時刻を更新
setInterval(updateTime, 1000);
updateTime(); // 初期表示

// ステータス変更時の通知（必要に応じて）
document.addEventListener("livewire:load", function () {
    Livewire.on("statusChanged", (message) => {
        console.log(message);
        // 必要に応じて通知表示などを実装
    });
});
