<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'reference_number' => 'DOC-' . fake()->unique()->numerify('####'),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(1000, 5000000),
            'mime_type' => 'application/pdf',
            'checksum' => fake()->sha256(),
            'pages' => fake()->numberBetween(1, 10),
            'status' => fake()->randomElement(['received', 'in_progress', 'completed']),
            'created_by' => User::factory(),
            'assigned_to_user_id' => fake()->boolean() ? User::factory() : null,
            'assigned_to_department_id' => fake()->boolean() ? Department::factory() : null,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'description' => fake()->paragraph(),
        ];
    }
}