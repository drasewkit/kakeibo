<?php

namespace Tests\Feature\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/transactions/delete-image のFeatureテスト
 */
class DeleteTransactionImageTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/delete-image';

    public function test_添付画像を削除できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$user->id, 'local');
        $transaction = Transaction::factory()->for($user)->create(['image_path' => $path]);

        $response = $this->actingAs($user)->postJson(self::URI, ['transactionId' => $transaction->id]);

        $response->assertOk()->assertJsonPath('hasImage', false);
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($transaction->refresh()->image_path);
    }

    public function test_収支自体は削除されない(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$user->id, 'local');
        $transaction = Transaction::factory()->for($user)->create(['image_path' => $path]);

        $this->actingAs($user)->postJson(self::URI, ['transactionId' => $transaction->id])->assertOk();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_画像が未添付でも成功する(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['image_path' => null]);

        $this->actingAs($user)->postJson(self::URI, ['transactionId' => $transaction->id])
            ->assertOk()
            ->assertJsonPath('hasImage', false);
    }

    public function test_他人の収支の画像は404になり削除されない(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$other->id, 'local');
        $transaction = Transaction::factory()->for($other)->create(['image_path' => $path]);

        $this->actingAs($user)->postJson(self::URI, ['transactionId' => $transaction->id])
            ->assertNotFound();

        Storage::disk('local')->assertExists($path);
        $this->assertNotNull($transaction->refresh()->image_path);
    }

    public function test_存在しないIDは404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['transactionId' => 999999])->assertNotFound();
    }

    public function test_IDが無いと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['transactionId']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
