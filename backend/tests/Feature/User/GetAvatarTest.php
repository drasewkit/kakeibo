<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GET /api/users/get-avatar のFeatureテスト
 */
class GetAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/users/get-avatar';

    public function test_自分のプロフィール画像を取得できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('me.jpg')->store('avatars/'.$user->id, 'local');
        $user->update(['avatar_path' => $path]);

        $response = $this->actingAs($user)->get(self::URI."?userId={$user->id}");

        $response->assertOk();
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_画像が未登録なら404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI."?userId={$user->id}")->assertNotFound();
    }

    public function test_他人のプロフィール画像は404になる(): void
    {
        // 世帯を導入するまでは、自分以外の画像は見られない
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = UploadedFile::fake()->image('other.jpg')->store('avatars/'.$other->id, 'local');
        $other->update(['avatar_path' => $path]);

        $this->actingAs($user)->getJson(self::URI."?userId={$other->id}")->assertNotFound();
    }

    public function test_存在しないIDは404になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI.'?userId=999999')->assertNotFound();
    }

    public function test_IDが無いと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI)
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['userId']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->getJson(self::URI.'?userId=1')->assertUnauthorized();
    }
}
