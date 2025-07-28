@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="heading-title">
        <div class="vertical-rule"></div>
        <h1 class="heading-title__text">勤怠詳細</h1>
    </div>

    {{-- デバッグ用 --}}
@if($errors->any())
    <div style="background: red; color: white; padding: 10px;">
        <h3>All Errors:</h3>
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach

        <h3>Error Bag:</h3>
        <pre>{{ print_r($errors->toArray(), true) }}</pre>
    </div>
@endif

    <div class="attendance-detail">
        <form class="correction-request-form" action="/attendance/detail/{{$attendance->id}}" method="post">
            @csrf
            @method('PUT')
            <table class="attendance-detail__table">
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">日付</th>
                    <td>
                        <p>{{ $attendance->date->format('Y年') }}</p>
                        <p>{{ $attendance->date->format('n月j日') }}</p>
                    </td>
                </tr>

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">出勤・退勤</th>
                    <td>
                        <input
                            type="time"
                            name="start_time"
                            id="start_time"
                            value="{{ old('start_time', $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '') }}">
                        ～
                        <input
                            type="time"
                            name="end_time"
                            id="end_time"
                            value="{{ old('end_time', $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}">

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
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td>
                        <input
                            type="time"
                            name="break_times[{{ $index }}][start_time]"
                            value="{{ old("break_times.{$index}.start_time", $breakTime->start_time ? \Carbon\Carbon::parse($breakTime->start_time)->format('H:i') : '') }}">
                        <span>～</span>
                        <input
                            type="time"
                            name="break_times[{{ $index }}][end_time]"
                            value="{{ old("break_times.{$index}.end_time", $breakTime->end_time ? \Carbon\Carbon::parse($breakTime->end_time)->format('H:i') : '') }}">

                        <p class="form__error">
                            @if($errors->has("break_times.{$index}.start_time"))
                                {{ $errors->first("break_times.{$index}.start_time") }}
                            @endif
                        </p>
                        <p class="form__error">
                            @if($errors->has("break_times.{$index}.end_time"))
                                {{ $errors->first("break_times.{$index}.end_time") }}
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
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">{{ $newIndex == 0 ? '休憩' : '休憩' . ($newLabelNumber) }}</th>
                    <td>
                        <input
                            type="time"
                            name="break_times[{{ $newIndex }}][start_time]"
                            value="{{ old('break_times.' . $newIndex . '.start_time') }}">
                        <span>～</span>
                        <input
                            type="time"
                            name="break_times[{{ $newIndex }}][end_time]"
                            value="{{ old('break_times.' . $newIndex . '.end_time') }}">

                        <p class="form__error">
                            @if($errors->has("break_times.{$newIndex}.start_time"))
                                {{ $errors->first("break_times.{$newIndex}.start_time") }}
                            @endif
                        </p>
                        <p class="form__error">
                            @if($errors->has("break_times.{$newIndex}.end_time"))
                                {{ $errors->first("break_times.{$newIndex}.end_time") }}
                            @endif
                        </p>
                    </td>
                </tr>

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__inner-title">備考</th>
                    <td>
                        <input class="attendance-detail__note" type="text" name="note" id="note" value="{{ old('note') }}">
                        <p class="form__error">
                            @error('note')
                                {{ $message }}
                            @enderror
                        </p>
                    </td>
                    </td>
                </tr>
            </table>

            @if($hasUnapprovedRequest)
                <p class="comment__waiting-for-approval">&#42;承認待ちのため修正はできません。</p>
            @else
                <div class="correction-request-form__button">
                    <button class="correction-request-form__button-submit" type="submit">修正</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection