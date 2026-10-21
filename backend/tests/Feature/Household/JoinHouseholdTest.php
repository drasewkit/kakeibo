<?php

namespace Tests\Feature\Household;

use App\Models\HouseholdInvitation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/households/join のFeatureテスト
 */
class JoinHouseholdTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/households/join';

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';

    public function test_招待を使って世帯に参加できる(): void
    {
        $inviter = User::factory()->create();
        $invitation = HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['token' => self::TOKEN]);

        $response->assertOk()
            ->assertJsonPath('id', $inviter->household_id)
            ->assertJsonCount(2, 'members');
        $this->assertSame($inviter->household_id, $user->refresh()->household_id);

        // 招待は使用済みになり、誰が参加したかを残す
        $invitation->refresh();
        $this->assertNotNull($invitation->used_at);
        $this->assertSame($user->id, $invitation->used_by);
    }

    public function test_参加後はパートナーが記帳した収支が見える(): void
    {
        $inviter = User::factory()->create();
        $transaction = Transaction::factory()->for($inviter)->for($inviter->household)->create();
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['token' => self::TOKEN])->assertOk();

        $this->actingAs($user->refresh())
            ->getJson("/api/transactions/get-detail?transactionId={$transaction->id}")
            ->assertOk();
    }

    public function test_同じ招待は2回使えない(): void
    {
        $inviter = User::factory()->create();
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);

        $this->actingAs(User::factory()->create())->postJson(self::URI, ['token' => self::TOKEN])->assertOk();

        $other = User::factory()->create();
        $this->actingAs($other)->postJson(self::URI, ['token' => self::TOKEN])->assertNotFound();
        $this->assertNotSame($inviter->household_id, $other->refresh()->household_id);
    }

    public function test_世帯が2人揃っていると422になる(): void
    {
        $inviter = User::factory()->create();
        User::factory()->for($inviter->household)->create();
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::URI, ['token' => self::TOKEN])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['token']]]);
        $this->assertNotSame($inviter->household_id, $user->refresh()->household_id);
    }

    public function test_期限切れの招待は404になる(): void
    {
        HouseholdInvitation::factory()->withToken(self::TOKEN)->expired()->create();

        $this->actingAs(User::factory()->create())->postJson(self::URI, ['token' => self::TOKEN])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_存在しないトークンは404になる(): void
    {
        $this->actingAs(User::factory()->create())->postJson(self::URI, ['token' => 'unknown'])->assertNotFound();
    }

    public function test_トークンが無いと422になる(): void
    {
        $this->actingAs(User::factory()->create())->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['token']]]);
    }

    public function test_未ログインでは401になる(): void
    {
        $this->postJson(self::URI, ['token' => self::TOKEN])->assertUnauthorized();
    }

    public function test_自分の世帯への招待は使えず使用済みにもならない(): void
    {
        // 送る前にリンクを試しても、パートナーに送るリンクが無効にならないこと
        $user = User::factory()->create();
        $invitation = HouseholdInvitation::factory()->for($user->household)->withToken(self::TOKEN)->create(['created_by' => $user->id]);

        $this->actingAs($user)->postJson(self::URI, ['token' => self::TOKEN])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['token']]]);

        $this->assertNull($invitation->refresh()->used_at);
    }

    public function test_参加すると元の世帯の未使用の招待は無効になる(): void
    {
        $inviter = User::factory()->create();
        HouseholdInvitation::factory()->for($inviter->household)->withToken(self::TOKEN)->create(['created_by' => $inviter->id]);
        $user = User::factory()->create();
        $oldInvitation = HouseholdInvitation::factory()->for($user->household)->create(['created_by' => $user->id]);

        $this->actingAs($user)->postJson(self::URI, ['token' => self::TOKEN])->assertOk();

        $this->assertDatabaseMissing('household_invitations', ['id' => $oldInvitation->id]);
    }
}
