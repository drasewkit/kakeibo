<?php

namespace Tests\Feature\Household;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/households/get-detail のFeatureテスト
 */
class GetHouseholdDetailTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/households/get-detail';

    public function test_自分の世帯とメンバーを取得できる(): void
    {
        $user = User::factory()->create(['name' => '本人']);
        $partner = User::factory()->for($user->household)->create(['name' => 'パートナー']);

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonPath('id', $user->household_id)
            ->assertJsonPath('name', $user->household->name)
            ->assertJsonCount(2, 'members')
            ->assertJsonPath('members.0.id', $user->id)
            ->assertJsonPath('members.1.id', $partner->id)
            ->assertJsonStructure(['id', 'name', 'members' => [['id', 'name', 'hasAvatar', 'avatarVersion']]]);
    }

    public function test_メンバーのメールアドレスは含まれない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URI)->assertJsonMissingPath('members.0.email');
    }

    public function test_別の世帯のメンバーは含まれない(): void
    {
        $user = User::factory()->create();
        User::factory()->create();

        $this->actingAs($user)->getJson(self::URI)->assertJsonCount(1, 'members');
    }

    public function test_未ログインでは401になる(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }
}
