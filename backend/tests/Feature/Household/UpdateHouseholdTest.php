<?php

namespace Tests\Feature\Household;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/households/update のFeatureテスト
 */
class UpdateHouseholdTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/households/update';

    public function test_世帯名を変更できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['name' => '高久家']);

        $response->assertOk()->assertJsonPath('name', '高久家');
        $this->assertDatabaseHas('households', ['id' => $user->household_id, 'name' => '高久家']);
    }

    public function test_別の世帯のIDを送っても自分の世帯だけが変わる(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['name' => '高久家', 'householdId' => $other->household_id])->assertOk();

        $this->assertDatabaseHas('households', ['id' => $user->household_id, 'name' => '高久家']);
        $this->assertDatabaseMissing('households', ['id' => $other->household_id, 'name' => '高久家']);
    }

    public function test_世帯名が空なら422になる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['name' => ''])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['fields' => ['name']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, ['name' => '高久家'])->assertUnauthorized();
    }
}
