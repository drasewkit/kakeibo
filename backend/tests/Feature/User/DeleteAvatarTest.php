<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/users/delete-avatar のFeatureテスト
 */
class DeleteAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/users/delete-avatar';

    public function test_プロフィール画像を削除できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('me.jpg')->store('avatars/'.$user->id, 'local');
        $user->update(['avatar_path' => $path]);

        $this->actingAs($user)->postJson(self::URI)
            ->assertOk()
            ->assertJsonPath('hasAvatar', false)
            ->assertJsonPath('avatarVersion', null);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_未登録でも成功する(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI)
            ->assertOk()
            ->assertJsonPath('hasAvatar', false);
    }

    public function test_同じ世帯のメンバーのIDを送ってもその人の画像は消えない(): void
    {
        // 世帯のメンバーでも、他人のプロフィール画像は削除できない
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->for($user->household)->create();
        $path = UploadedFile::fake()->image('other.jpg')->store('avatars/'.$other->id, 'local');
        $other->update(['avatar_path' => $path]);

        $this->actingAs($user)->postJson(self::URI, ['userId' => $other->id])
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        Storage::disk('local')->assertExists($path);
        $this->assertSame($path, $other->refresh()->avatar_path);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI)->assertUnauthorized();
    }
}
