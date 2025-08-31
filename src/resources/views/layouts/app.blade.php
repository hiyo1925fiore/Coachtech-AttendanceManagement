<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('head')
    <title>coachtech勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    @livewireStyles
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <img src="{{asset('image/CoachTech_White 1.png')}}" alt="COACHTECH" class="header__logo">
            <nav class="header__nav">
                <ul class="header__list">
                    <!-- 初期状態のヘッダー（JavaScriptで更新される） -->
                    <li class="header__list-item">
                        <a class="header__link" href="/attendance">勤怠</a>
                    </li>

                    <li class="header__list-item">
                        <a class="header__link" href="/attendance/list">勤怠一覧</a>
                    </li>

                    <li class="header__list-item">
                        <a class="header__link" href="/stamp_correction_request/list">申請</a>
                    </li>
                </ul>

                <!-- ログアウトフォーム -->
                <li class="header__list-item--logout">
                    <form action="/logout" class="header__form" method="post">
                        @csrf
                        <button class="header__form--logout" type="submit">ログアウト</button>
                    </form>
                </li>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @livewireScripts
    <!-- ヘッダーステータス管理スクリプト -->
    <script src="{{ asset('js/header-status.js') }}"></script>

    <!-- デバッグ用スクリプト -->
    <script>
        // デバッグ関数をグローバルに追加
        window.debugHeader = function() {
            if (window.headerStatusManager) {
                console.log('=== Header Status Debug Info ===');
                console.log(window.headerStatusManager.getDebugInfo());
                console.log('Current header HTML:', document.querySelector('.header__nav ul').innerHTML);
            } else {
                console.log('HeaderStatusManager not found');
            }
        };

        // 手動でステータスを設定する関数（テスト用）
        window.testHeaderStatus = function(status) {
            if (window.headerStatusManager) {
                console.log('Setting test status:', status);
                window.headerStatusManager.setStatus(status);
            }
        };

        // ページロード時に情報を出力
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Page Load Debug Info ===');
            console.log('Current URL:', window.location.href);
            console.log('Document ready state:', document.readyState);
            console.log('Livewire available:', typeof window.Livewire !== 'undefined');

            // 5秒後にデバッグ情報を表示
            setTimeout(function() {
                console.log('=== 5 Seconds After Load ===');
                window.debugHeader();
            }, 5000);
        });
    </script>
</body>
</html>