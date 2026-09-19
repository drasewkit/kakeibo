<?php

namespace Tests\Feature\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GET /api/transactions/get-transaction-image のFeatureテスト
 */
class GetTransactionImageTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/get-transaction-image';

    public function test_添付画像を取得できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$user->id, 'local');
        $transaction = Transaction::factory()->for($user)->create(['image_path' => $path]);

        $response = $this->actingAs($user)->get(self::URI."?transaction_id={$transaction->id}");

        $response->assertOk();
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_画像が未添付なら404になる(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['image_path' => null]);

        // 収支自体は存在するが、画像の有無を漏らさないため404で揃える
        $this->actingAs($user)->getJson(self::URI."?transaction_id={$transaction->id}")
            ->assertNotFound();
    }

    public function test_他人の収支の画像は404になる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = UploadedFile::fake()->image('receipt.jpg')->store('transaction-images/'.$other->id, 'local');
        $transaction = Transaction::factory()->for($other)->create(['image_path' => $path]);

        $this->actingAs($user)->getJson(self::URI."?transaction_id={$transaction->id}")
            ->assertNotFound();
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
