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
    ->name('admin.login')
    ->middleware('redirect.if.admin');
// 管理者ログイン画面(POST) - ログイン
Route::post('/admin/login', [UserController::class, 'loginAdminUser'])
    ->middleware('redirect.if.admin');

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
    // 勤怠一覧画面(POST) - 日付変更用
    Route::post('/attendance/list', [AttendanceController::class, 'showAttendanceList'])
        ->name('attendance.list.post');
    // 勤怠詳細画面(GET) - 初回表示用
    Route::get('/attendance/detail/{date}',[AttendanceController::class,'showDetail'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.detail');
    // 勤怠詳細画面(POST) - 修正申請送信用
    Route::post('/attendance/detail/{date}', [AttendanceController::class, 'postRequest'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.correct');
    // 申請一覧画面
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'showRequestList'])->name('requests.list');
});

// 管理者用画面
Route::middleware('adminOnly')->group(function () {
    // ログアウト
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'adminDestroy']);
    // 勤怠詳細画面(GET) - 初回表示用
    Route::get('/admin/attendances/{date}',[AttendanceController::class,'showAdminDetail'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('admin.attendance.detail');
    // 一般ユーザーのIDをセッションに保存
    Route::post('/admin/set-attendance-user', [AttendanceController::class, 'setAttendanceUser'])
        ->name('admin.set.attendance.user');
    // 勤怠詳細画面(PUT) - 勤怠修正用
    Route::put('/admin/attendances/{date}',[AttendanceController::class,'updateDetail'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('admin.attendance.detail.update');
    // スタッフ別勤怠一覧画面(GET) - 初回表示用
    Route::get('/admin/users/{user}/attendances', [AttendanceController::class, 'showStaffAttendance'])
        ->name('admin.staff.attendance');
    // スタッフ別勤怠一覧画面(GET) - CSV出力用
    Route::get('/admin/users/{user}/attendances/csv', [AttendanceController::class, 'exportStaffAttendanceCsv'])
        ->name('admin.staff.attendance.csv');
    // スタッフ別勤怠一覧画面(POST) - 日付変更用
    Route::post('/admin/users/{user}/attendances', [AttendanceController::class, 'showStaffAttendance'])
        ->name('admin.staff.attendance.post');
    // 修正申請承認画面(GET) - 初回表示用
    Route::get('/admin/requests/{id}', [AttendanceController::class, 'showRequest'])
        ->name('admin.request.detail');
    // 修正申請承認画面(PUT) - 勤怠承認用
    Route::put('/admin/requests/{id}', [AttendanceController::class, 'approveRequest'])
        ->name('admin.request.update');
    // 勤怠一覧画面（GET）- 初回表示用
    Route::get('/admin/attendances', [AttendanceController::class, 'showAdminAttendance'])
        ->name('admin.attendance.list');
    // 勤怠一覧画面（POST）- 日付変更用
    Route::post('/admin/attendances', [AttendanceController::class, 'showAdminAttendance'])
        ->name('admin.attendance.list.post');
    // スタッフ一覧画面
    Route::get('/admin/users', [UserController::class, 'showStaffList'])
        ->name('admin.staff.list');
    // 申請一覧画面
    Route::get('/admin/requests', [AttendanceController::class, 'showAdminRequestList'])->name('admin.requests.list');
});
