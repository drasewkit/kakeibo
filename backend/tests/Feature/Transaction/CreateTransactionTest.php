<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/transactions/create-transaction のFeatureテスト
 */
class CreateTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/create-transaction';

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'expense',
            'category_id' => Category::factory()->expense()->create()->id,
            'amount' => 1280,
            'date' => '2026-09-19',
            'memo' => 'スーパー',
        ], $overrides);
    }

    public function test_収支を登録できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('amount', 1280)
            ->assertJsonPath('type', 'expense')
            ->assertJsonPath('memo', 'スーパー');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 1280,
            'memo' => 'スーパー',
        ]);
    }

    public function test_ログインユーザー自身の収支として登録される(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // user_idを指定しても無視され、ログインユーザーのものになる
        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload([
            'user_id' => $other->id,
        ]));

        $response->assertStatus(201)->assertJsonPath('user_id', $user->id);
    }

    public function test_カテゴリなしでも登録できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload([
            'category_id' => null,
        ]));

        $response->assertStatus(201)->assertJsonPath('category_id', null);
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'amount', 'date']);
    }

    public function test_存在しないカテゴリは422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, $this->validPayload(['category_id' => 999999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_金額が0以下だと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, $this->validPayload(['amount' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_金額に小数は許可しない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, $this->validPayload(['amount' => 1280.5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_カテゴリの種類が収支の種類と違うと422になる(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->income()->create();

        // 支出の収支に収入カテゴリを指定する
        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload([
            'type' => 'expense',
            'category_id' => $incomeCategory->id,
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_カテゴリの種類が一致していれば登録できる(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->income()->create();

        $response = $this->actingAs($user)->postJson(self::URI, $this->validPayload([
            'type' => 'income',
            'category_id' => $incomeCategory->id,
        ]));

        $response->assertStatus(201)->assertJsonPath('type', 'income');
    }

    public function test_種別が不正だと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, $this->validPayload(['type' => 'unknown']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
