<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * POST /api/auth/login のFeatureテスト
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しい認証情報でログインできる(): void
    {
        $user = User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/auth/login', [
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

        $response = $this->postJson('/api/auth/login', [
            'email' => 'taro@example.com',
            'password' => 'password',
        ]);

        $response->assertJsonMissingPath('password');
    }

    public function test_パスワードが違うと422になる(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'taro@example.com',
            'password' => 'wrong-password',
        ]);

        // 認証失敗はemailに紐づくバリデーションエラーとして返す設計
        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['email']]]);

        $this->assertGuest();
    }

    public function test_存在しないメールアドレスでも422になる(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        // 「登録がない」と「パスワードが違う」を区別せず、同じ扱いにする
        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['email']]]);

        $this->assertGuest();
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['email', 'password']]]);
    }

    public function test_同じメールで短時間に失敗を繰り返すと429になる(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        // 1分5回までは通常通り失敗の422を返す
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'wrong'])
                ->assertStatus(422);
        }

        // 6回目は制限され、正しいパスワードでもログインできない
        $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'password'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS')
            ->assertHeader('Retry-After');

        $this->assertGuest();
    }

    public function test_別のメールには制限が及ばない(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);
        User::factory()->create(['email' => 'hanako@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'wrong']);
        }

        $this->postJson('/api/auth/login', ['email' => 'hanako@example.com', 'password' => 'password'])
            ->assertOk();
    }

    public function test_メールの大文字小文字を変えても同じ制限として数える(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'Taro@Example.com', 'password' => 'wrong']);
        }

        $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_emailに配列を送っても500にならず422になる(): void
    {
        $this->postJson('/api/auth/login', ['email' => ['a'], 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_本番以外ではCF_Connecting_IPを偽装しても制限を回避できない(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'wrong'], ['CF-Connecting-IP' => "10.0.0.$i"]);
        }

        $this->postJson('/api/auth/login', ['email' => 'taro@example.com', 'password' => 'wrong'], ['CF-Connecting-IP' => '10.0.0.99'])
            ->assertStatus(429);
    }

    public function test_本番ではCF_Connecting_IPを制限のキーに使う(): void
    {
        $this->app['env'] = 'production';

        // 本番はCloudflareが付けた値を信頼する（HTTP経由だと本番扱いでCSRF検証の免除が外れるため、リミッターを直接呼ぶ）
        $request = Request::create('/api/auth/login', 'POST', ['email' => 'Taro@Example.com'], server: ['HTTP_CF_CONNECTING_IP' => '203.0.113.1']);
        $limits = RateLimiter::limiter('login')($request);

        $this->assertSame('taro@example.com|203.0.113.1', $limits[0]->key);
        $this->assertSame('203.0.113.1', $limits[1]->key);
    }
}
