<?php

namespace Tests\Feature\Household;

use App\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/households/create-invitation のFeatureテスト
 */
class CreateInvitationTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/households/create-invitation';

    public function test_招待リンクを作成できる(): void
    {
        config(['app.frontend_url' => 'https://kakeibo.example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI);

        $response->assertStatus(201)->assertJsonStructure(['url', 'expiresAt']);
        $this->assertMatchesRegularExpression('#^https://kakeibo\.example\.com/invite/[A-Za-z0-9]{40}$#', $response->json('url'));

        // トークンそのものではなく、ハッシュ値だけを保存する
        $token = basename($response->json('url'));
        $this->assertDatabaseHas('household_invitations', [
            'household_id' => $user->household_id,
            'created_by' => $user->id,
            'token_hash' => hash('sha256', $token),
            'used_at' => null,
        ]);
    }

    public function test_有効期限は7日後になる(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI);

        $this->assertSame(now()->addDays(7)->toJSON(), $response->json('expiresAt'));
    }

    public function test_作り直すと未使用の古い招待は無効になる(): void
    {
        $user = User::factory()->create();
        $old = HouseholdInvitation::factory()->for($user->household)->create(['created_by' => $user->id]);

        $this->actingAs($user)->postJson(self::URI)->assertStatus(201);

        $this->assertDatabaseMissing('household_invitations', ['id' => $old->id]);
        $this->assertSame(1, HouseholdInvitation::where('household_id', $user->household_id)->count());
    }

    public function test_世帯が2人揃っていると422になる(): void
    {
        $user = User::factory()->create();
        User::factory()->for($user->household)->create();

        $this->actingAs($user)->postJson(self::URI)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertDatabaseCount('household_invitations', 0);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI)->assertUnauthorized();
    }
}
