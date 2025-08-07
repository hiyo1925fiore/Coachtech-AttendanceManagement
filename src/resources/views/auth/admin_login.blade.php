@extends('layouts.auth_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="form__heading">
        <h1 class="form__heading-title">管理者ログイン</h1>
    </div>

    <form class="form" action="/admin/login" method="post" novalidate>
        @csrf
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
        <div class="form__button">
            <button class="form__button-submit" type="submit">管理者ログインする</button>
        </div>
    </form>
</div>
@endsection