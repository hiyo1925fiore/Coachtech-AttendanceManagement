@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="content">
    @livewire('request-list-component', ['userType' => 'admin'])
</div>
@endsection