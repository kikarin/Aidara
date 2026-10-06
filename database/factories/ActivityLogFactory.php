<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'log_name'     => fake()->word(),
            'description'  => fake()->sentence(),
            'subject_type' => null,
            'subject_id'   => null,
            'causer_type'  => 'App\\Models\\User',
            'causer_id'    => null,
            'properties'   => [],
            'event'        => fake()->randomElement(['created', 'updated', 'deleted']),
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    /**
     * Indicate that the log is for a created event.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'created',
        ]);
    }

    /**
     * Indicate that the log is for an updated event.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'updated',
        ]);
    }

    /**
     * Indicate that the log is for a deleted event.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'deleted',
        ]);
    }

    /**
     * Set a subject for the activity log.
     */
    public function forSubject($model): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => get_class($model),
            'subject_id'   => $model->id,
        ]);
    }

    /**
     * Set a causer for the activity log.
     */
    public function causedBy($user): static
    {
        return $this->state(fn (array $attributes) => [
            'causer_type' => get_class($user),
            'causer_id'   => $user->id,
        ]);
    }

    /**
     * Set custom properties for the activity log.
     */
    public function withProperties($properties): static
    {
        return $this->state(fn (array $attributes) => [
            'properties' => $properties,
        ]);
    }
}
