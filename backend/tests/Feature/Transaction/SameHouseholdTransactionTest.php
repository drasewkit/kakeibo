<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 同じ世帯のメンバーが記帳した収支を扱えることのFeatureテスト
 *
 * 収支は世帯が所有するため、相手が記帳した収支も一覧・詳細・更新・削除・画像の操作ができる。
 * 別の世帯の収支が404になることは、各エンドポイントのテストで確認している。
 */
class SameHouseholdTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        // ログインユーザーと、同じ世帯のメンバー（相手）
        $this->user = User::factory()->create();
        $this->partner = User::factory()->for($this->user->household)->create();
    }

    public function test_一覧に相手が記帳した収支も含まれる(): void
    {
        Transaction::factory()->for($this->user)->create();
        Transaction::factory()->for($this->partner)->count(2)->create();
        // 別の世帯の収支は含まれない
        Transaction::factory()->for(User::factory()->create())->create();

        $response = $this->actingAs($this->user)->getJson('/api/transactions/get-list');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('total', 3);
    }

    public function test_集計と登録済みの年に相手の収支も含まれる(): void
    {
        Transaction::factory()->for($this->user)->income()->create(['amount' => 300000, 'date' => '2026-03-01']);
        Transaction::factory()->for($this->partner)->expense()->create(['amount' => 120000, 'date' => '2026-03-02']);
        Transaction::factory()->for($this->partner)->expense()->create(['amount' => 5000, 'date' => '2025-12-31']);

        $response = $this->actingAs($this->user)->getJson('/api/transactions/get-list?year=2026&month=3');

        $response->assertOk()
            ->assertJsonPath('summary.income', 300000)
            ->assertJsonPath('summary.expense', 120000)
            ->assertJsonPath('summary.balance', 180000)
            ->assertJsonPath('availableYears', [2026, 2025]);
    }

    public function test_相手が記帳した収支の詳細を取得できる(): void
    {
        $transaction = Transaction::factory()->for($this->partner)->create();

        $this->actingAs($this->user)
            ->getJson("/api/transactions/get-detail?transactionId={$transaction->id}")
            ->assertOk()
            ->assertJsonPath('id', $transaction->id)
            ->assertJsonPath('userId', $this->partner->id);
    }

    public function test_相手が記帳した収支を更新しても記帳者は変わらない(): void
    {
        $transaction = Transaction::factory()->for($this->partner)->create(['amount' => 1000]);

        $this->actingAs($this->user)->postJson('/api/transactions/update', [
            'transactionId' => $transaction->id,
            'type' => 'expense',
            'categoryId' => Category::factory()->expense()->create()->id,
            'amount' => 5000,
            'date' => '2026-09-19',
            'memo' => '相手の記帳を修正',
        ])->assertOk()->assertJsonPath('amount', 5000);

        $transaction->refresh();
        $this->assertSame(5000, $transaction->amount);
        $this->assertSame($this->partner->id, $transaction->user_id);
    }

    public function test_相手が記帳した収支を削除できる(): void
    {
        $transaction = Transaction::factory()->for($this->partner)->create();

        $this->actingAs($this->user)
            ->postJson('/api/transactions/delete', ['transactionId' => $transaction->id])
            ->assertNoContent();

        $this->assertModelMissing($transaction);
    }

    public function test_相手が記帳した収支の画像を取得できる(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$this->partner->household_id, 'local');
        $transaction = Transaction::factory()->for($this->partner)->create(['image_path' => $path]);

        $response = $this->actingAs($this->user)
            ->get("/api/transactions/get-image?transactionId={$transaction->id}");

        $response->assertOk();
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_相手が記帳した収支に画像を添付できる(): void
    {
        Storage::fake('local');
        $transaction = Transaction::factory()->for($this->partner)->create();

        $this->actingAs($this->user)->postJson('/api/transactions/upload-image', [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertOk()->assertJsonPath('hasImage', true);

        // 保存先は記帳者ではなく世帯のディレクトリ
        $this->assertStringStartsWith(
            "transaction-images/{$this->user->household_id}/",
            $transaction->refresh()->image_path,
        );
    }

    public function test_相手が記帳した収支の画像を削除できる(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$this->partner->household_id, 'local');
        $transaction = Transaction::factory()->for($this->partner)->create(['image_path' => $path]);

        $this->actingAs($this->user)
            ->postJson('/api/transactions/delete-image', ['transactionId' => $transaction->id])
            ->assertOk()
            ->assertJsonPath('hasImage', false);

        Storage::disk('local')->assertMissing($path);
    }
}
