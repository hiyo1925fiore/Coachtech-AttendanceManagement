@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="heading-title">
        <h1 class="heading-title__text">勤怠一覧</h1>
    </div>

    <div class="pagination-by-month">
        <!-- 前月ボタン -->
        <form class="pagination-form" action="{{ route('attendance.list.post') }}" method="post">
            @csrf
            <input type="hidden" name="year" value="{{ $previousMonth->year }}">
            <input type="hidden" name="month" value="{{ $previousMonth->month }}">
            <button class="pagination-button" type="submit">
                <img class="arrow-icon--left" src="{{asset('image/arrow.png')}}" alt="←">
                前月
            </button>
        </form>

        <!-- 現在の月表示 -->
        <div class="current-date">
            <img class="calendar-icon" src="{{asset('image/calendar.png')}}" alt="カレンダー">
            <h2 class="current-date__text">{{ $currentDate->format('Y/m') }}</h2>
        </div>

        <!-- 翌月ボタン -->
        <form class="pagination-form" action="{{ route('attendance.list.post') }}" method="post">
            @csrf
            <input type="hidden" name="year" value="{{ $nextMonth->year }}">
            <input type="hidden" name="month" value="{{ $nextMonth->month }}">
            <button class="pagination-button" type="submit">
                翌月
                <img class="arrow-icon--right" src="{{asset('image/arrow.png')}}" alt=">">
            </button>
        </form>
    </div>

    <table class="attendance__table">
        <tr class="attendance__table-header--row">
            <th class="table-header--date">日付</th>
            <th class="table-header--items">出勤</th>
            <th class="table-header--items">退勤</th>
            <th class="table-header--items">休憩</th>
            <th class="table-header--items">合計</th>
            <th class="table-header--detail">詳細</th>
        </tr>

        @foreach($attendanceData as $data)
        <tr class="attendance__table--row">
            <!-- 日付 -->
            <td class="table-content--date">
                {{ $data['date']->format('m/d') }}
                {{ ['(日)', '(月)', '(火)', '(水)', '(木)', '(金)', '(土)'][$data['date']->dayOfWeek] }}
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
                @if($data['attendance'])
                    <a href="{{ route('attendance.detail', ['date' => $data['date']->format('Y-m-d')]) }}" class="detail-link">
                        詳細
                    </a>
                @else
                    <a href="{{ route('attendance.detail', ['date' => $data['date']->format('Y-m-d')]) }}" class="detail-link">
                        詳細
                    </a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection