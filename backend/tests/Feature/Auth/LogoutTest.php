<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/logout のFeatureテスト
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログアウトできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertNoContent();

        // セッションを保持するwebガードを明示して確認する。
        // auth:sanctumミドルウェアが実行時に既定ガードをsanctumへ切り替え、
        // sanctumガードはリクエスト中に解決したユーザーをメモリ上に保持したままになるため、
        // ガードを指定しないassertGuest()はテスト上の都合で失敗する
        $this->assertGuest('web');
    }

    public function test_未ログインでは401になる(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
