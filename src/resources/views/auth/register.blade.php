@extends('layouts.auth_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="form__heading">
        <h1 class="form__heading-title">会員登録</h1>
    </div>

    <form class="form" action="/register" method="post" novalidate>
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">名前</span>
            </div>
            <div class="form__group-content">
                <input type="text" class="form__input" name="name" value="{{ old('name') }}"/>
                <p class="form__error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </p>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">メールアドレス</span>
            </div>
            <div class="form__group-content">
                <input type="email" class="form__input" name="email" value="{{ old('email') }}"/>
                <p class="form__error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </p>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <input type="password" class="form__input" name="password"/>
                <p class="form__error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </p>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード確認</span>
            </div>
            <div class="form__group-content">
                <input type="password" class="form__input" name="password_confirmation"/>
                <p class="form__error">
                    @error('password_confirmation')
                    {{ $message }}
                    @enderror
                </p>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">登録する</button>
        </div>
    </form>
    <div class="link">
        <a class="link__text" href="/login">ログインはこちら</a>
    </div>
</div>
@endsection