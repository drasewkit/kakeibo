<?php

namespace Tests\Feature\Household;

use App\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/households/get-invitation のFeatureテスト
 */
class GetInvitationTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/households/get-invitation';

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';

    public function test_ログインしていなくても招待内容を取得できる(): void
    {
        // 招待リンクから新規登録する人が、参加先を確認できるようにする
        $inviter = User::factory()->create(['name' => '招待した人']);
        $inviter->household->update(['name' => '高久家']);
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);

        $response = $this->getJson(self::URI.'?token='.self::TOKEN);

        $response->assertOk()
            ->assertJsonPath('householdId', $inviter->household_id)
            ->assertJsonPath('householdName', '高久家')
            ->assertJsonPath('invitedBy.name', '招待した人')
            ->assertJsonPath('isFull', false)
            ->assertJsonStructure(['expiresAt']);
    }

    public function test_世帯が2人揃っていればisFullがtrueになる(): void
    {
        $inviter = User::factory()->create();
        User::factory()->for($inviter->household)->create();
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);

        $this->getJson(self::URI.'?token='.self::TOKEN)->assertJsonPath('isFull', true);
    }

    public function test_存在しないトークンは404になる(): void
    {
        $this->getJson(self::URI.'?token=unknown')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_期限切れの招待は404になる(): void
    {
        HouseholdInvitation::factory()->withToken(self::TOKEN)->expired()->create();

        $this->getJson(self::URI.'?token='.self::TOKEN)->assertNotFound();
    }

    public function test_使用済みの招待は404になる(): void
    {
        HouseholdInvitation::factory()->withToken(self::TOKEN)->used()->create();

        $this->getJson(self::URI.'?token='.self::TOKEN)->assertNotFound();
    }

    public function test_トークンが無いと422になる(): void
    {
        $this->getJson(self::URI)
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['token']]]);
    }
}
