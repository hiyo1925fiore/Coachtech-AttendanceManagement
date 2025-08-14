@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="heading-title">
        <h1 class="heading-title__text">{{ $currentDate->format('Y年n月j日') }}の勤怠</h1>
    </div>

    <div class="pagination-by-day">
        <!-- 前日ボタン -->
        <form class="pagination-form" action="{{ route('admin.attendance.list.post') }}" method="post">
            @csrf
            <input type="hidden" name="year" value="{{ $previousDay->year }}">
            <input type="hidden" name="month" value="{{ $previousDay->month }}">
            <input type="hidden" name="day" value="{{ $previousDay->day }}">
            <button class="pagination-button" type="submit">
                <img class="arrow-icon--left" src="{{asset('image/arrow.png')}}" alt="←">
                前日
            </button>
        </form>

        <!-- 現在の年月日表示 -->
        <div class="current-date">
            <img class="calendar-icon" src="{{asset('image/calendar.png')}}" alt="カレンダー">
            <h2 class="current-date__text">{{ $currentDate->format('Y/m/d') }}</h2>
        </div>

        <!-- 翌日ボタン -->
        <form class="pagination-form" action="{{ route('admin.attendance.list.post') }}" method="post">
            @csrf
            <input type="hidden" name="year" value="{{ $nextDay->year }}">
            <input type="hidden" name="month" value="{{ $nextDay->month }}">
            <input type="hidden" name="day" value="{{ $nextDay->day }}">
            <button class="pagination-button" type="submit">
                翌日
                <img class="arrow-icon--right" src="{{asset('image/arrow.png')}}" alt=">">
            </button>
        </form>
    </div>

    <table class="attendance__table">
        <tr class="attendance__table-header--row">
            <th class="table-header--name">名前</th>
            <th class="table-header--items">出勤</th>
            <th class="table-header--items">退勤</th>
            <th class="table-header--items">休憩</th>
            <th class="table-header--items">合計</th>
            <th class="table-header--detail">詳細</th>
        </tr>

        @foreach($attendanceData as $data)
        <tr class="attendance__table--row">
            <!-- スタッフ名 -->
            <td class="table-content--name">
                {{ $data['attendance']->user->name }}
            </td>

            <!-- 出勤時間 -->
            <td class="table-content--items">
                @if($data['attendance'] && $data['attendance']->start_time)
                    {{ \Carbon\Carbon::parse($data['attendance']->start_time)->format('H:i') }}
                @endif
            </td>

            <!-- 退勤時間 -->
            <td class="table-content--items">
                @if($data['attendance'] && $data['attendance']->end_time)
                    {{ \Carbon\Carbon::parse($data['attendance']->end_time)->format('H:i') }}
                @endif
            </td>

            <!-- 休憩時間 -->
            <td class="table-content--items">
                @if($data['break_time'] !== null && $data['attendance']->start_time)
                    {{ floor($data['break_time'] / 60) }}:{{ sprintf('%02d', $data['break_time'] % 60) }}
                @endif
            </td>

            <!-- 労働時間 -->
            <td class="table-content--items">
                @if($data['work_time'] !== null)
                    {{ floor($data['work_time'] / 60) }}:{{ sprintf('%02d', $data['work_time'] % 60) }}
                @endif
            </td>

            <!-- 勤怠詳細画面（一般ユーザー）へのリンク -->
            <td class="table-content--detail">
                <a href="{{ route('admin.attendance.detail', ['id' => $data['attendance']->id]) }}" class="detail-link">
                    詳細
                </a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection