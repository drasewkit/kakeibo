<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/transactions/update-transaction のFeatureテスト
 */
class UpdateTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/update-transaction';

    /**
     * @return array<string, mixed>
     */
    private function validPayload(int $transactionId, array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => $transactionId,
            'type' => 'expense',
            'category_id' => Category::factory()->expense()->create()->id,
            'amount' => 5000,
            'date' => '2026-09-19',
            'memo' => '更新後',
        ], $overrides);
    }

    public function test_収支を更新できる(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['amount' => 1000, 'memo' => '更新前']);

        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload($transaction->id));

        $response->assertOk()
            ->assertJsonPath('id', $transaction->id)
            ->assertJsonPath('amount', 5000)
            ->assertJsonPath('memo', '更新後');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 5000,
            'memo' => '更新後',
        ]);
    }

    public function test_transaction_idはレスポンスの項目として保存されない(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        // コントローラーが更新内容からtransaction_idを除いていることを確かめる
        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload($transaction->id));

        $response->assertOk()->assertJsonMissingPath('transaction_id');
    }

    public function test_他人の収支は404になる(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::factory()->for($other)->create(['amount' => 1000]);

        $this->actingAs($user)->postJson(self::URI, $this->validPayload($transaction->id))
            ->assertNotFound();

        // 他人のレコードが書き換わっていないこと
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'amount' => 1000]);
    }

    public function test_存在しない_i_dは404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, $this->validPayload(999999))->assertNotFound();
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_id', 'type', 'amount', 'date']);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
