@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="alert">
    @if(session('success'))
        <div class="alert--success">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="alert--error">
            {{ session('error') }}
        </div>
    @endif
</div>

<div class="detail-content">
    <div class="heading-title">
        <h1 class="heading-title__text">勤怠詳細</h1>
    </div>

    <div class="attendance-detail">
        <form class="update-form" action="{{ route('admin.attendance.detail.update', $date) }}"method="post">
            @method('PUT')
            @csrf
            <!-- 新規作成用にユーザーidをhidden属性で渡す -->
            <input type="hidden" name="user_id" value="{{ $userId }}">
            <!-- 変更を保存するため勤怠情報のidをhidden属性で渡す -->
            <input type="hidden" name="id" value="{{ $attendance->id ? $attendance->id : '' }}">
            <table class="attendance-detail__table">
                <tr class="table-row">
                    <th class="table-header">名前</th>
                    <td class="table-content--name">
                        <p class="table-data__text">{{ $attendance->user->name }}</p>
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">日付</th>
                    <td class="table-content--date">
                        <p class="table-data__text">{{ $attendance->date->format('Y年') }}</p>
                        <p class="table-data__text">{{ $attendance->date->format('n月j日') }}</p>
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">出勤・退勤</th>
                    <td class="table-content--attendance">
                        <div class="table-data__inner">
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="start_time"
                                id="start_time"
                                value="{{ old('start_time', $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '') }}">
                            <span class="wave-dash">～</span>
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="end_time"
                                id="end_time"
                                value="{{ old('end_time', $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}">
                        </div>

                        <p class="form__error">
                            @error('start_time')
                                {{ $message }}
                            @enderror
                        </p>
                        <p class="form__error">
                            @error('end_time')
                                {{ $message }}
                            @enderror
                        </p>
                    </td>
                </tr>

                @foreach($attendance->breakTimes as $index => $breakTime)
                <tr class="table-row">
                    <th class="table-header">{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td class="table-content--break-time">
                        <div class="table-data__inner">
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="break_start_time[{{ $index }}]"
                                value="{{ old('break_start_time.' . $index, \Carbon\Carbon::parse($breakTime->start_time)->format('H:i')) }}">
                            <span class="wave-dash">～</span>
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="break_end_time[{{ $index }}]"
                                value="{{ old('break_times.' . $index, \Carbon\Carbon::parse($breakTime->end_time)->format('H:i')) }}">
                            <!-- 既存の休憩時間のIDを保持 -->
                            <input type="hidden" name="break_time_ids[{{ $index }}]" value="{{ $breakTime->id }}">
                        </div>

                        <p class="form__error">
                            @if($errors->has("break_start_time.{$index}"))
                                {{ $errors->first("break_start_time.{$index}") }}
                            @endif
                        </p>
                        <p class="form__error">
                            @if($errors->has("break_end_time.{$index}"))
                                {{ $errors->first("break_end_time.{$index}") }}
                            @endif
                        </p>
                    </td>
                </tr>
                @endforeach

                <!-- 新規入力欄（空白）-->
                @php
                    $newIndex = count($attendance->breakTimes);
                    $newLabelNumber = $newIndex + 1;
                @endphp
                <tr class="table-row">
                    <th class="table-header">{{ $newIndex == 0 ? '休憩' : '休憩' . ($newLabelNumber) }}</th>
                    <td class="table-content--break-time">
                        <div class="table-data__inner">
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="break_start_time[{{ $newIndex }}]"
                                value="{{ old('break_start_time.' . $newIndex) }}">
                            <span class="wave-dash">～</span>
                            <input
                                class="table-data__input--time"
                                type="time"
                                name="break_end_time[{{ $newIndex }}]"
                                value="{{ old('break_end_time.' . $newIndex) }}">
                        </div>

                        <p class="form__error">
                            @if($errors->has("break_start_time.{$newIndex}"))
                                {{ $errors->first("break_start_time.{$newIndex}") }}
                            @endif
                        </p>
                        <p class="form__error">
                            @if($errors->has("break_end_time.{$newIndex}"))
                                {{ $errors->first("break_end_time.{$newIndex}") }}
                            @endif
                        </p>
                    </td>
                </tr>

                <tr class="table-row">
                    <th class="table-header">備考</th>
                    <td class="table-content--note">
                        <textarea class="table-data__textarea" name="note" id="note">{{ old('note', $attendance->note) }}</textarea>
                        <p class="form__error">
                            @error('note')
                                {{ $message }}
                            @enderror
                        </p>
                    </td>
                </tr>
            </table>

            <div class="correction-request-form__button">
                <button class="correction-request-form__button-submit" type="submit">修正</button>
            </div>
        </form>
    </div>
</div>
@endsection