<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/login のFeatureテスト
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しい認証情報でログインできる(): void
    {
        $user = User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'taro@example.com',
            // UserFactoryの既定パスワード
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', 'taro@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_パスワードはレスポンスに含まれない(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'taro@example.com',
            'password' => 'password',
        ]);

        $response->assertJsonMissingPath('password');
    }

    public function test_パスワードが違うと422になる(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'taro@example.com',
            'password' => 'wrong-password',
        ]);

        // 認証失敗はemailに紐づくバリデーションエラーとして返す設計
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_存在しないメールアドレスでも422になる(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        // 「登録がない」と「パスワードが違う」を区別せず、同じ扱いにする
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
