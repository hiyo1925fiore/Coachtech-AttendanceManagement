@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_register.css') }}">
@endsection

@section('content')
<div class="content">
@livewire('attendance-component')
</div>
<!-- 時刻をリアルタイムで表示する -->
<script src="{{ asset('js/display_real_time.js') }}"></script>
@endsection