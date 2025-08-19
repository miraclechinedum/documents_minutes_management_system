<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Minute>
 */
class MinuteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasOverlay = fake()->boolean(30); // 30% chance of having overlay coordinates
        
        return [
            'document_id' => Document::factory(),
            'body' => fake()->paragraph(),
            'visibility' => fake()->randomElement(['public', 'department', 'internal']),
            'page_number' => $hasOverlay ? fake()->numberBetween(1, 5) : null,
            'pos_x' => $hasOverlay ? fake()->randomFloat(4, 0, 1) : null,
            'pos_y' => $hasOverlay ? fake()->randomFloat(4, 0, 1) : null,
            'box_style' => $hasOverlay ? [
                'color' => fake()->hexColor(),
                'size' => fake()->randomElement(['small', 'medium', 'large'])
            ] : null,
            'created_by' => User::factory(),
        ];
    }
}