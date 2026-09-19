<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/transactions/get-transaction-list のFeatureテスト
 */
class GetTransactionListTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/get-transaction-list';

    public function test_自分の収支一覧を取得できる(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->count(3)->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'user_id', 'category_id', 'category', 'type', 'amount', 'date', 'memo', 'has_image']],
                'summary' => ['income', 'expense', 'balance'],
                'available_years',
                'current_page',
                'last_page',
                'total',
            ]);
    }

    public function test_他人の収支は含まれない(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Transaction::factory()->for($user)->create();
        Transaction::factory()->for($other)->count(2)->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('total', 1);
    }

    public function test_日付の降順で並ぶ(): void
    {
        $user = User::factory()->create();
        $older = Transaction::factory()->for($user)->create(['date' => '2026-01-05']);
        $newer = Transaction::factory()->for($user)->create(['date' => '2026-03-10']);

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_年月で絞り込める(): void
    {
        $user = User::factory()->create();
        $target = Transaction::factory()->for($user)->create(['date' => '2026-03-15']);
        Transaction::factory()->for($user)->create(['date' => '2026-04-01']);
        Transaction::factory()->for($user)->create(['date' => '2025-03-15']);

        $response = $this->actingAs($user)->getJson(self::URI.'?year=2026&month=3');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_種別で絞り込める(): void
    {
        $user = User::factory()->create();
        $income = Transaction::factory()->for($user)->income()->create();
        Transaction::factory()->for($user)->expense()->create();

        $response = $this->actingAs($user)->getJson(self::URI.'?type=income');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $income->id);
    }

    public function test_カテゴリで絞り込める(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();
        $target = Transaction::factory()->for($user)->create(['category_id' => $category->id]);
        Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson(self::URI."?category_id={$category->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_集計は収入と支出と差引を返す(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->income()->create(['amount' => 300000, 'date' => '2026-03-01']);
        Transaction::factory()->for($user)->expense()->create(['amount' => 120000, 'date' => '2026-03-02']);

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonPath('summary.income', 300000)
            ->assertJsonPath('summary.expense', 120000)
            ->assertJsonPath('summary.balance', 180000);
    }

    public function test_集計は種別の絞り込みに影響されない(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->income()->create(['amount' => 300000, 'date' => '2026-03-01']);
        Transaction::factory()->for($user)->expense()->create(['amount' => 120000, 'date' => '2026-03-02']);

        // 一覧はincomeだけに絞られるが、集計は期間全体を対象にする仕様
        $response = $this->actingAs($user)->getJson(self::URI.'?type=income');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('summary.income', 300000)
            ->assertJsonPath('summary.expense', 120000);
    }

    public function test_登録がある年だけを降順で返す(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['date' => '2024-05-01']);
        Transaction::factory()->for($user)->create(['date' => '2026-02-01']);
        Transaction::factory()->for($user)->create(['date' => '2026-08-01']);

        $response = $this->actingAs($user)->getJson(self::URI);

        // 重複を除き降順。データの無い2025年は含まれない
        $response->assertOk()->assertJsonPath('available_years', [2026, 2024]);
    }

    public function test_収支が無い場合も集計は0を返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('summary.income', 0)
            ->assertJsonPath('summary.expense', 0)
            ->assertJsonPath('summary.balance', 0)
            ->assertJsonPath('available_years', []);
    }

    public function test_不正な絞り込み条件は422になる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(self::URI.'?month=13&type=unknown');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['month', 'type']);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }
}
