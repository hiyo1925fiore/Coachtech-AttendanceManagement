<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストケース1: 会員登録機能
     * 会員登録ページが正常に表示されることをテスト
     */
    public function test_register_page_can_be_displayed()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * テストケース1: 会員登録機能
     * 名前が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required_validation()
    {
        // 1. 会員登録ページを開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. 名前を入力せずに他の必要項目を入力する
        $userData = [
            'name' => '', // 名前を空にする
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 3. 登録ボタンを押す（POSTリクエストを送信）
        $response = $this->post('/register', $userData);

        // バリデーションエラーでリダイレクトされることを確認
        $response->assertStatus(302);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['name']);

        // 期待されるエラーメッセージを確認
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);

        // ユーザーがデータベースに保存されていないことを確認
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required_validation()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * メールアドレスが無効なメール形式だった場合、バリデーションメッセージが表示される
     */
    public function test_invalid_email_format_validation()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'invalid-email-format', // 無効なメール形式
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'メール形式で入力してください'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required_validation()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '', // パスワードを空にする
            'password_confirmation' => '',
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * パスワードが7文字以下の場合、バリデーションメッセージが表示される
     */
    public function test_password_minimum_length_validation()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '1234567', // 7文字のパスワード
            'password_confirmation' => '1234567',
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_validation()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password', // 異なるパスワード
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password_confirmation']);
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);
    }

    /**
     * テストケース1: 会員登録機能
     * 全ての項目が入力されている場合、会員情報が登録され、打刻画面に遷移される
     */
    public function test_user_can_register_successfully()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        // 登録後のリダイレクトを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');
        //$response->assertRedirect('/email/verify');

        // ユーザーがデータベースに保存されたことを確認
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com'
        ]);

        // パスワードがハッシュ化されて保存されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }
}
