<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <!-- 勤務状況に応じてヘッダーを更新する -->
        <script src="{{ asset('js/header-status.js') }}"></script>
    </main>
    @livewireScripts()
</body>
</html>