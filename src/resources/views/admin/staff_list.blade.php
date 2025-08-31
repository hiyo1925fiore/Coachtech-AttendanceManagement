@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="heading-title">
        <h1 class="heading-title__text">スタッフ一覧</h1>
    </div>

    <div class="list__container">
        <table class="list__table">
            @if(count($users) > 0)
                <tr class="list__table--header-row">
                    <th class="table-header--name">名前</th>
                    <th class="table-header">メールアドレス</th>
                    <th class="table-header--monthly-attendance">月次勤怠</th>
                </tr>

                @foreach($users as $user)
                    <tr class="list__table--row">
                        <!-- 一般ユーザー名 -->
                        <td class="table-content--name">
                            <p class="table-content__text">{{ $user->name }}</p>
                        </td>
                        <!-- メールアドレス -->
                        <td class="table-content--email">
                            <p class="table-content__email">{{ $user->email }}</p>
                        </td>
                        <!-- スタッフ別勤怠一覧画面（管理者）へのリンク -->
                        <td class="table-content--monthly-attendance">
                            <a href="{{ route('admin.staff.attendance', ['user' => $user->id]) }}" class="detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach

            @else
                <tr class="list__table-row--no-item">
                    <td colspan="3" class="table-content--no-item">
                        <p class="table-content-text">スタッフ情報がありません</p>
                    </td>
                </tr>
            @endif
        </table>
    </div>
</div>
@endsection