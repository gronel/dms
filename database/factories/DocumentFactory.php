<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'owner_id'    => User::factory(),
            'folder_id'   => null,
            'status'      => 'draft',
            'is_locked'   => false,
            'locked_by'   => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attrs) => [
            'is_locked' => true,
            'locked_by' => $attrs['owner_id'],
        ]);
    }
}
