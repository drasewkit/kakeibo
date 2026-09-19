<?php

namespace Tests\Feature\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/transactions/delete-transaction のFeatureテスト
 */
class DeleteTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/delete-transaction';

    public function test_収支を削除できる(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['transaction_id' => $transaction->id]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_添付画像もストレージから削除される(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$user->id, 'local');
        $transaction = Transaction::factory()->for($user)->create(['image_path' => $path]);

        $this->actingAs($user)->postJson(self::URI, ['transaction_id' => $transaction->id])
            ->assertNoContent();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_他人の収支は404になり削除されない(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::factory()->for($other)->create();

        $this->actingAs($user)->postJson(self::URI, ['transaction_id' => $transaction->id])
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_存在しない_i_dは404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['transaction_id' => 999999])->assertNotFound();
    }

    public function test_i_dが無いと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
