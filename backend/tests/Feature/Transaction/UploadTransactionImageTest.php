<?php

namespace Tests\Feature\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/transactions/upload-image のFeatureテスト
 */
class UploadTransactionImageTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/transactions/upload-image';

    public function test_画像を添付できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertOk()->assertJsonPath('hasImage', true);

        $transaction->refresh();
        Storage::disk('local')->assertExists($transaction->image_path);
    }

    public function test_ユーザーごとのディレクトリに保存される(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertOk();

        // 他人のuser_idを推測してもアクセスできないようにする意図
        $this->assertStringStartsWith("transaction-images/{$user->id}/", $transaction->refresh()->image_path);
    }

    public function test_保存先のパスはレスポンスに含まれない(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertOk()->assertJsonMissingPath('imagePath');
    }

    public function test_添付済みの場合は古い画像を削除して差し替える(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('transaction-images/'.$user->id, 'local');
        $transaction = Transaction::factory()->for($user)->create(['image_path' => $oldPath]);

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk();

        Storage::disk('local')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, $transaction->refresh()->image_path);
    }

    public function test_他人の収支には添付できない(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::factory()->for($other)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertNotFound();

        $this->assertNull($transaction->refresh()->image_path);
    }

    public function test_画像以外のファイルは422になる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_5_m_bを超える画像は422になる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'transactionId' => $transaction->id,
            'image' => UploadedFile::fake()->image('big.jpg')->size(5121),
        ])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_画像が無いと422になる(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->postJson(self::URI, ['transactionId' => $transaction->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
