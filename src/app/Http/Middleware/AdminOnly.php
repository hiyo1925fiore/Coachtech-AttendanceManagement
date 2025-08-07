<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ユーザーがログインしていない場合
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // ログインしているユーザーを取得
        $user = auth()->user();

        // ユーザーが管理者でない場合
        if (!$user || !$user->isAdmin()) {
            // 管理者以外の場合は管理者ログインページにリダイレクト
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
