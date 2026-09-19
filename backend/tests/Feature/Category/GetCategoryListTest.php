<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/categories/get-category-list のFeatureテスト
 */
class GetCategoryListTest extends TestCase
{
    use RefreshDatabase;

    public function test_カテゴリ一覧を取得できる(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->create(['name' => '食費']);
        Category::factory()->income()->create(['name' => '給与']);

        $response = $this->actingAs($user)->getJson('/api/categories/get-category-list');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => '食費', 'type' => 'expense'])
            ->assertJsonFragment(['name' => '給与', 'type' => 'income']);
    }

    public function test_種類ごとにまとまり_i_d順で並ぶ(): void
    {
        $user = User::factory()->create();
        // 収入を先に作り、並び順がID順ではなく種類優先であることを確かめる
        $incomeFirst = Category::factory()->income()->create(['name' => '給与']);
        $expense = Category::factory()->expense()->create(['name' => '食費']);
        $incomeSecond = Category::factory()->income()->create(['name' => '副収入']);

        $response = $this->actingAs($user)->getJson('/api/categories/get-category-list');

        // typeの昇順（expense → income）、同じtype内はIDの昇順
        $response->assertOk()
            ->assertJsonPath('0.id', $expense->id)
            ->assertJsonPath('1.id', $incomeFirst->id)
            ->assertJsonPath('2.id', $incomeSecond->id);
    }

    public function test_カテゴリが未登録なら空配列を返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/categories/get-category-list');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_seedされたカテゴリをすべて返す(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        $response = $this->actingAs($user)->getJson('/api/categories/get-category-list');

        // 支出9件 + 収入3件
        $response->assertOk()->assertJsonCount(12);
    }

    public function test_未ログインでは401になる(): void
    {
        $response = $this->getJson('/api/categories/get-category-list');

        $response->assertUnauthorized();
    }
}
