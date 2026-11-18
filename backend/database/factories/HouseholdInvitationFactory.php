<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HouseholdInvitation>
 */
class HouseholdInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'created_by' => fn (array $attributes) => User::factory()->create(['household_id' => $attributes['household_id']]),
            'token_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * 指定したトークンで照合できる招待にする
     */
    public function withToken(string $token): static
    {
        return $this->state(fn () => ['token_hash' => hash('sha256', $token)]);
    }

    /**
     * 期限切れの招待にする
     */
    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    /**
     * 使用済みの招待にする
     */
    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()]);
    }
}
