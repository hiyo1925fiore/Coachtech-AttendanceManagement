<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * 一般ユーザー新規登録処理
     */
    public function storeUser(RegisterRequest $request){
        $user=User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'is_admin'=> 0,
        ]);

        // メール認証通知を送信
        //event(new Registered($user));

        Auth::login($user);

        //return redirect()->route('verification.notice');
        return redirect()->route('attendance.register');
    }

    /**
     * 一般ユーザーログイン処理
     */
    public function loginUser(LoginRequest $request){
        $credentials = $request->only('email', 'password');
        $credentials['is_admin'] = 0;
        if(Auth::attempt($credentials)){
            // メール認証が済んでいない場合
            //if (!Auth::user()->hasVerifiedEmail()) {
                //return redirect()->route('verification.notice');
            //}

            return redirect('/attendance');
        }

        // カスタムエラーメッセージ
        throw ValidationException::withMessages([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 管理者ログイン画面表示
     */
    public function showAdminLogin(){
        return view('auth.admin_login');
    }

    /**
     * 管理者ログイン処理
     */
    public function loginAdminUser(LoginRequest $request){
        $credentials = $request->only('email', 'password');
        $credentials['is_admin'] = 1;
        if(Auth::attempt($credentials)){
            // メール認証が済んでいない場合
            //if (!Auth::user()->hasVerifiedEmail()) {
                //return redirect()->route('verification.notice');
            //}

            return redirect('/admin/attendances');
        }

        // カスタムエラーメッセージ
        throw ValidationException::withMessages([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * スタッフ一覧画面表示
     */
    public function showStaffList(){

        return view('admin.staff_list');
    }
}