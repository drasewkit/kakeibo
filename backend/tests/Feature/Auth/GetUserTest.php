<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/get-user のFeatureテスト
 */
class GetUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン中のユーザーを取得できる(): void
    {
        $user = User::factory()->create([
            'name' => '高久 大祐',
            'email' => 'taro@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/get-user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', '高久 大祐')
            ->assertJsonPath('email', 'taro@example.com');
    }

    public function test_パスワードはレスポンスに含まれない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/get-user');

        $response->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token');
    }

    public function test_他人の情報は返らない(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'other@example.com']);

        $response = $this->actingAs($user)->getJson('/api/get-user');

        $response->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_未ログインでは401になる(): void
    {
        $response = $this->getJson('/api/get-user');

        $response->assertUnauthorized();
    }
}
