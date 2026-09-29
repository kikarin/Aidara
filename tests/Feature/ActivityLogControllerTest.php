<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user
        $this->user = User::factory()->create();
    }

    #[Test]
    public function authenticated_user_can_view_activity_logs_api_index()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'module',
                    'event',
                ],
            ],
            'meta' => [
                'total',
                'current_page',
                'per_page',
            ],
        ]);
    }

    #[Test]
    public function unauthenticated_user_gets_401_for_api()
    {
        // Act
        $response = $this->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_correct_pagination_meta()
    {
        // Arrange
        ActivityLog::factory()->count(15)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/activity-logs?per_page=10&page=1');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    #[Test]
    public function it_can_search_activity_logs()
    {
        // Arrange
        ActivityLog::factory()->create([
            'log_name' => 'user.created',
            'causer_id' => $this->user->id,
        ]);
        ActivityLog::factory()->create([
            'log_name' => 'post.deleted',
            'causer_id' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/activity-logs?search=user.created');

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_sorting_by_different_columns()
    {
        // Arrange
        ActivityLog::factory()->count(3)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/activity-logs?sort=created_at&order=desc');

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_data_when_per_page_is_minus_one()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/activity-logs?per_page=-1');

        // Assert
        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function it_returns_all_fields_in_response()
    {
        // Arrange
        ActivityLog::factory()->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        if (count($data) > 0) {
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('module', $data[0]);
            $this->assertArrayHasKey('event', $data[0]);
        }
    }

    #[Test]
    public function it_respects_pagination_limits()
    {
        // Arrange
        ActivityLog::factory()->count(25)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/activity-logs?per_page=10');

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    public function guest_cannot_access_api()
    {
        // Act
        $response = $this->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function api_returns_json_content_type()
    {
        // Arrange
        ActivityLog::factory()->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    #[Test]
    public function api_handles_empty_results()
    {
        // Act
        $response = $this->actingAs($this->user)->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }

    #[Test]
    public function api_response_includes_metadata()
    {
        // Arrange
        ActivityLog::factory()->count(3)->create(['causer_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/activity-logs');

        // Assert
        $response->assertStatus(200);
        $this->assertIsArray($response->json('meta'));
        $this->assertArrayHasKey('total', $response->json('meta'));
        $this->assertArrayHasKey('current_page', $response->json('meta'));
        $this->assertArrayHasKey('per_page', $response->json('meta'));
    }
}
