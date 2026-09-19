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

    public function test_種類ごとにまとまり登録順で並ぶ(): void
    {
        $user = User::factory()->create();
        // 支出を収入の間に挟んで作り、並びがID順ではなく種類優先であることを確かめる
        $incomeFirst = Category::factory()->income()->create(['name' => '給与']);
        $expense = Category::factory()->expense()->create(['name' => '食費']);
        $incomeSecond = Category::factory()->income()->create(['name' => '副収入']);

        $response = $this->actingAs($user)->getJson('/api/categories/get-category-list');

        // typeはenum('income','expense')で、MySQLのORDER BYは文字列比較ではなく
        // enumの定義順（income=1, expense=2）で並べるため、incomeが先に来る。
        // 同じtype内はIDの昇順。
        // 注意: この並びはenumの定義順に依存しており、Phase 4でPostgreSQLへ移行すると
        // 文字列比較（expense → income）に変わる。そのときはこのテストが検知する
        $response->assertOk()
            ->assertJsonPath('0.id', $incomeFirst->id)
            ->assertJsonPath('1.id', $incomeSecond->id)
            ->assertJsonPath('2.id', $expense->id);
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
