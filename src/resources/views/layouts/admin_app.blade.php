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
                <li class="header__list-item">
                        <a class="header__link" href="admin/attendances">勤怠一覧</a>
                    </li>

                    <li class="header__list-item">
                        <a class="header__link" href="/admin/users">スタッフ一覧</a>
                    </li>

                    <li class="header__list-item">
                        <a class="header__link" href="/admin/requests">申請一覧</a>
                    </li>

                    <li class="header__list-item">
                        <form action="/admin/logout" class="header__form" method="post">
                            @csrf
                            <button class="header__form--logout" type="submit">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>