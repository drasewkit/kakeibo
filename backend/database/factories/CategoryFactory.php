<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => TransactionType::Expense,
        ];
    }

    /**
     * 収入カテゴリにする
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TransactionType::Income]);
    }

    /**
     * 支出カテゴリにする
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TransactionType::Expense]);
    }
}
