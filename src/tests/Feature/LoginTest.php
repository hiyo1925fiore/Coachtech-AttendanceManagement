<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用一般ユーザーを作成
     */
    protected function createTestUser()
    {
        return User::factory()->create([
            'name' => 'テストユーザー1',
            'email' => 'test1@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);
    }

    /**
     * テスト用管理者を作成
     */
    protected function createTestAdminUser()
    {
        return User::factory()->create([
            'name' => 'テストユーザー2',
            'email' => 'test2@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * ログインページが正常に表示されることをテスト
     */
    public function test_login_page_of_general_user_can_be_displayed()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required_for_login_as_general_user()
    {
        // 1. ログインページを開く
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 2. メールアドレスを入力せずに他の必要項目を入力する
        $loginData = [
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $loginData);

        // バリデーションエラーでリダイレクトされることを確認
        $response->assertStatus(302);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['email']);

        // 期待されるエラーメッセージを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);

        // ユーザーが認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * メールアドレスが無効なメール形式だった場合、バリデーションメッセージが表示される
     */
    public function test_login_of_general_user_fails_with_invalid_email_format()
    {
        $loginData = [
            'email' => 'invalid-email-format', // 無効なメール形式
            'password' => 'password123',
        ];

        $response = $this->post('/login', $loginData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'メール形式で入力してください'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required_for_login_as_general_user()
    {
        // 1. ログインページを開く
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 2. パスワードを入力せずに他の必要項目を入力する
        $loginData = [
            'email' => 'test1@example.com',
            'password' => '', // パスワードを空にする
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $loginData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_login_of_general_user_fails_with_invalid_credentials()
    {
        // テスト用一般ユーザーを作成
        $this->createTestUser();

        // 1. ログインページを開く
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        $loginData = [
            'email' => 'wrong@example.com', // 存在しないメールアドレス
            'password' => 'wrongpassword',   // 間違ったパスワード
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $loginData);

        $response->assertStatus(302);

        // 認証エラーメッセージを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        // ユーザーが認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * 存在するメールアドレスで間違ったパスワードを入力した場合、バリデーションメッセージが表示される
     */
    public function test_login_of_general_user_fails_with_wrong_password()
    {
        // テスト用一般ユーザーを作成
        $user = $this->createTestUser();

        $loginData = [
            'email' => $user->email,
            'password' => 'wrongpassword', // 間違ったパスワード
        ];

        $response = $this->post('/login', $loginData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * 管理者の情報が入力された場合、バリデーションメッセージが表示される
     */
    public function test_login_of_general_user_fails_with_admin_credentials()
    {
        // テスト用管理者を作成
        $adminUser = $this->createTestAdminUser();

        // 必要項目を入力する
        $loginData = [
            'email' => $adminUser->email,
            'password' => 'password123',
        ];

        $response = $this->post('/login', $loginData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_general_user_can_login_with_correct_credentials()
    {
        // テスト用一般ユーザーを作成
        $user = $this->createTestUser();

        // 1. ログインページを開く
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 2. 全ての必要項目を入力する
        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $loginData);

        // ログイン処理が実行される - リダイレクトを確認
        $response->assertStatus(302);

        // ログイン後のリダイレクト先を確認
        $response->assertRedirect('/attendance');

        // ユーザーが認証されていることを確認
        $this->assertAuthenticated();

        // 認証されたユーザーが正しいことを確認
        $this->assertEquals($user->id, auth()->id());
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * メール未認証ユーザーのログインテスト（もしメール認証が必要な場合）
     */
    public function test_unverified_general_user_cannot_login()
    {
        // メール未認証の一般ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null, // メール未認証
            'is_admin' => 0,
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->post('/login', $loginData);

        // メール認証が必要な場合の処理（アプリケーションの設定による）
        // 認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース2: ログイン認証機能（一般ユーザー）
     * 既にログイン済みの一般ユーザーがログインページにアクセスした場合、勤怠登録画面が表示される
     */
    public function test_authenticated_general_user_is_redirected_from_login_page()
    {
        // 一般ユーザーを作成してログイン
        $user = $this->createTestUser();
        $this->actingAs($user);

        // ログインページにアクセス
        $response = $this->get('/login');

        // 勤怠登録画面にリダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * ログインページが正常に表示されることをテスト
     */
    public function test_login_page_of_admin_user_can_be_displayed()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.admin_login');
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required_for_login_as_admin_user()
    {
        // 1. ログインページを開く
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 2. メールアドレスを入力せずに他の必要項目を入力する
        $loginData = [
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/admin/login', $loginData);

        // バリデーションエラーでリダイレクトされることを確認
        $response->assertStatus(302);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['email']);

        // 期待されるエラーメッセージを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);

        // ユーザーが認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * メールアドレスが無効なメール形式だった場合、バリデーションメッセージが表示される
     */
    public function test_login_of_admin_user_fails_with_invalid_email_format()
    {
        $loginData = [
            'email' => 'invalid-email-format', // 無効なメール形式
            'password' => 'password123',
        ];

        $response = $this->post('/admin/login', $loginData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'メール形式で入力してください'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required_for_login_as_admin_user()
    {
        // 1. ログインページを開く
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 2. パスワードを入力せずに他の必要項目を入力する
        $loginData = [
            'email' => 'test2@example.com',
            'password' => '', // パスワードを空にする
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/admin/login', $loginData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_login_of_admin_user_fails_with_invalid_credentials()
    {
        // テスト用管理者を作成
        $this->createTestAdminUser();

        // 1. ログインページを開く
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        $loginData = [
            'email' => 'wrong@example.com', // 存在しないメールアドレス
            'password' => 'wrongpassword',   // 間違ったパスワード
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/admin/login', $loginData);

        $response->assertStatus(302);

        // 認証エラーメッセージを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        // ユーザーが認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * 存在するメールアドレスで間違ったパスワードを入力した場合、バリデーションメッセージが表示される
     */
    public function test_login_of_admin_user_fails_with_wrong_password()
    {
        // テスト用管理者を作成
        $adminUser = $this->createTestAdminUser();

        $loginData = [
            'email' => $adminUser->email,
            'password' => 'wrongpassword', // 間違ったパスワード
        ];

        $response = $this->post('/admin/login', $loginData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * 一般ユーザーの情報が入力された場合、バリデーションメッセージが表示される
     */
    public function test_login_of_admin_user_fails_with_general_credentials()
    {
        // テスト用一般ユーザーを作成
        $user = $this->createTestUser();

        // 必要項目を入力する
        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->post('/admin/login', $loginData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_admin_user_can_login_with_correct_credentials()
    {
        // テスト用管理者を作成
        $adminUser = $this->createTestAdminUser();

        // 1. ログインページを開く
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 2. 全ての必要項目を入力する
        $loginData = [
            'email' => $adminUser->email,
            'password' => 'password123',
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/admin/login', $loginData);

        // ログイン処理が実行される - リダイレクトを確認
        $response->assertStatus(302);

        // ログイン後のリダイレクト先を確認
        $response->assertRedirect('/admin/attendances');

        // ユーザーが認証されていることを確認
        $this->assertAuthenticated();

        // 認証されたユーザーが正しいことを確認
        $this->assertEquals($adminUser->id, auth()->id());
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * メール未認証ユーザーのログインテスト（もしメール認証が必要な場合）
     */
    public function test_unverified_admin_user_cannot_login()
    {
        // メール未認証の管理者を作成
        $adminUser = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null, // メール未認証
            'is_admin' => 1,
        ]);

        $loginData = [
            'email' => $adminUser->email,
            'password' => 'password123',
        ];

        $response = $this->post('/admin/login', $loginData);

        // メール認証が必要な場合の処理（アプリケーションの設定による）
        // 認証されていないことを確認
        $this->assertGuest();
    }

    /**
     * テストケース3: ログイン認証機能（管理者）
     * 既にログイン済みの管理者がログインページにアクセスした場合、勤怠一覧画面（管理者）が表示される
     */
    public function test_authenticated_admin_user_is_redirected_from_login_page()
    {
        // 管理者を作成してログイン
        $adminUser = $this->createTestAdminUser();
        $this->actingAs($adminUser);

        // ログインページにアクセス
        $response = $this->get('/admin/login');

        // 勤怠一覧画面（管理者）にリダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/admin/attendances');
    }
}
