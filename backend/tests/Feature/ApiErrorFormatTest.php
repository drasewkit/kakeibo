<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * APIのエラーレスポンスが独自エンベロープに統一されていることのFeatureテスト。
 *
 * この形はPhase 3でNestJSのExceptionFilterが再現すべき仕様であり、
 * フロントの lib/errors.ts が解釈する唯一の形でもある。
 */
class ApiErrorFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_バリデーション失敗は422でコードを返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/transactions/create', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonStructure(['error' => ['code', 'message', 'fields' => ['type', 'amount', 'date']]]);

        // fieldsのキーはリクエストのフィールド名（camelCase）
        $this->assertIsString($response->json('error.message'));
        $this->assertIsArray($response->json('error.fields.amount'));
    }

    public function test_未ログインは401でコードを返す(): void
    {
        $response = $this->getJson('/api/auth/get-user');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', ApiErrorCode::Unauthenticated->value)
            ->assertJsonStructure(['error' => ['code', 'message']])
            // fieldsはバリデーション失敗のときだけ含める
            ->assertJsonMissingPath('error.fields');
    }

    public function test_Acceptヘッダが無くても401になる(): void
    {
        // ブラウザで直接URLを開いた場合などAcceptがtext/htmlになるケース。
        // フレームワーク既定のroute('login')へのリダイレクトを無効化していないと、
        // ミドルウェア内でRouteNotFoundExceptionが出て500になる
        $response = $this->get('/api/auth/get-user');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_他人のレコードは404でコードを返す(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::factory()->for($other)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/transactions/get-detail?transactionId={$transaction->id}");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', ApiErrorCode::NotFound->value)
            ->assertJsonMissingPath('error.fields');
    }

    public function test_存在しないルートも404でコードを返す(): void
    {
        $response = $this->getJson('/api/no-such-endpoint');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', ApiErrorCode::NotFound->value);
    }

    public function test_Laravel既定のmessageとerrorsは返さない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/transactions/create', []);

        $response->assertJsonMissingPath('message')
            ->assertJsonMissingPath('errors');
    }
}
