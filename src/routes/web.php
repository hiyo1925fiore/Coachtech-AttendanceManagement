<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 一般ユーザー新規登録画面
Route::post('/register', [UserController::class, 'storeUser']);
// 一般ユーザーログイン画面
Route::post('/login', [UserController::class, 'loginUser']);
// 管理者ログイン画面(GET) - 初回表示用
Route::get('/admin/login', [UserController::class, 'showAdminLogin'])
    ->name('admin.login');
// 管理者ログイン画面(POST) - ログイン
Route::post('/admin/login', [UserController::class, 'loginAdminUser']);

// 一般ユーザー用画面
Route::middleware('auth')->group(function () {
    // ログアウト
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    // 勤怠登録画面
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.register');
    // 勤怠一覧画面(GET) - 初回表示用
    Route::get('/attendance/list', [AttendanceController::class, 'showAttendanceList'])
        ->name('attendance.list');
    // 勤怠一覧画面(GET) - 日付変更用
    Route::post('/attendance/list', [AttendanceController::class, 'showAttendanceList'])
        ->name('attendance.list.post');
    // 勤怠詳細画面(GET) - 初回表示用
    Route::get('/attendance/detail/{date}',[AttendanceController::class,'showDetail'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.detail');
    // 勤怠詳細画面(PUT) - 修正申請送信用
    Route::put('/attendance/detail/{date}', [AttendanceController::class, 'postRequest'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.correct');
    // 申請一覧画面
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'showRequestList'])->name('requests.list');
});

// 管理者用画面
Route::middleware('adminOnly')->group(function () {
    // ログアウト
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'adminDestroy']);
    // 勤怠一覧画面（GET）- 初回表示用
    Route::get('/admin/attendances', [AttendanceController::class, 'showAdminAttendance'])
        ->name('admin.attendance.list');
    // 勤怠一覧画面（POST）- 日付変更用
    Route::post('/admin/attendances', [AttendanceController::class, 'showAdminAttendance'])
        ->name('admin.attendance.list.post');
});
