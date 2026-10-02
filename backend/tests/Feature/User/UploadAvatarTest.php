<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/users/upload-avatar のFeatureテスト
 */
class UploadAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/users/upload-avatar';

    public function test_プロフィール画像を登録できる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->image('me.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('hasAvatar', true);
        $this->assertIsString($response->json('avatarVersion'));

        Storage::disk('local')->assertExists($user->refresh()->avatar_path);
    }

    public function test_ユーザーごとのディレクトリに保存される(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->image('me.jpg'),
        ])->assertOk();

        $this->assertStringStartsWith("avatars/{$user->id}/", $user->refresh()->avatar_path);
    }

    public function test_保存先のパスはレスポンスに含まれない(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->image('me.jpg'),
        ])->assertOk()->assertJsonMissingPath('avatarPath')->assertJsonMissingPath('avatar_path');
    }

    public function test_登録済みの場合は古い画像を削除して差し替える(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('avatars/'.$user->id, 'local');
        $user->update(['avatar_path' => $oldPath]);
        $oldVersion = $this->actingAs($user)->getJson('/api/auth/get-user')->json('avatarVersion');

        $response = $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk();

        Storage::disk('local')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, $user->refresh()->avatar_path);
        // 画像URLのキャッシュが効き続けないよう、バージョンも変わる
        $this->assertNotSame($oldVersion, $response->json('avatarVersion'));
    }

    public function test_同じ世帯のメンバーのIDを送っても自分の画像だけが変わる(): void
    {
        // 世帯のメンバーでも、他人のプロフィール画像は変更できない
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->for($user->household)->create();

        $this->actingAs($user)->postJson(self::URI, [
            'userId' => $other->id,
            'image' => UploadedFile::fake()->image('me.jpg'),
        ])->assertOk()->assertJsonPath('id', $user->id);

        $this->assertNotNull($user->refresh()->avatar_path);
        $this->assertNull($other->refresh()->avatar_path);
    }

    public function test_画像以外のファイルは422になる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_5MBを超える画像は422になる(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [
            'image' => UploadedFile::fake()->image('big.jpg')->size(5121),
        ])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_画像が無いと422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['image']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
