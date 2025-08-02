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

Route::post('/register', [UserController::class, 'storeUser']);
Route::post('/login', [UserController::class, 'loginUser']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.register');
    Route::get('/attendance/list', [AttendanceController::class, 'showAttendanceList'])
        ->name('attendance.list');
    Route::get('/attendance/detail/{date}',[AttendanceController::class,'showDetail'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.detail');
    Route::put('/attendance/detail/{date}', [AttendanceController::class, 'postRequest'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('attendance.correct');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'showRequestList'])->name('requests.list');
});
