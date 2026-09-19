<?php

namespace Tests\Feature\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/transactions/get-transaction-detail のFeatureテスト
 */
class GetTransactionDetailTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/get-transaction-detail';

    public function test_自分の収支を1件取得できる(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'amount' => 1280,
            'memo' => 'スーパー',
        ]);

        $response = $this->actingAs($user)->getJson(self::URI."?transaction_id={$transaction->id}");

        $response->assertOk()
            ->assertJsonPath('id', $transaction->id)
            ->assertJsonPath('amount', 1280)
            ->assertJsonPath('memo', 'スーパー')
            ->assertJsonStructure(['id', 'category', 'type', 'amount', 'date', 'memo', 'has_image']);
    }

    public function test_画像のパスはレスポンスに含まれない(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'image_path' => 'transaction-images/1/secret.jpg',
        ]);

        $response = $this->actingAs($user)->getJson(self::URI."?transaction_id={$transaction->id}");

        $response->assertOk()
            ->assertJsonMissingPath('image_path')
            ->assertJsonPath('has_image', true);
    }

    public function test_他人の収支は404になる(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::factory()->for($other)->create();

        $response = $this->actingAs($user)->getJson(self::URI."?transaction_id={$transaction->id}");

        // 所有権の有無を漏らさないため、存在しない場合と区別せず404にする
        $response->assertNotFound();
    }

    public function test_存在しない_i_dは404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI.'?transaction_id=999999')->assertNotFound();
    }

    public function test_i_dが無いと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->getJson(self::URI.'?transaction_id=1')->assertUnauthorized();
    }
}
