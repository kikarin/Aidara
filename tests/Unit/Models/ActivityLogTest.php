<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_activity_log()
    {
        // Act
        $log = ActivityLog::factory()->create([
            'log_name' => 'user.created',
            'description' => 'User created successfully',
        ]);

        // Assert
        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'log_name' => 'user.created',
            'description' => 'User created successfully',
        ]);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        // Act
        $log = new ActivityLog();

        // Assert
        $this->assertEquals('activity_log', $log->getTable());
    }

    #[Test]
    public function it_can_format_created_at_timestamp()
    {
        // Arrange
        $log = ActivityLog::factory()->create();

        // Act
        $createdAt = $log->created_at;

        // Assert
        $this->assertStringContainsString('-', $createdAt);
        $this->assertStringContainsString(':', $createdAt);
    }

    #[Test]
    public function it_can_format_updated_at_timestamp()
    {
        // Arrange
        $log = ActivityLog::factory()->create();

        // Act
        $log->update(['description' => 'Updated description']);
        $updatedAt = $log->updated_at;

        // Assert
        $this->assertStringContainsString('-', $updatedAt);
        $this->assertStringContainsString(':', $updatedAt);
    }

    #[Test]
    public function it_belongs_to_causer()
    {
        // Arrange
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create(['causer_id' => $user->id]);

        // Act
        $causer = $log->causer;

        // Assert
        $this->assertNotNull($causer);
        $this->assertEquals($user->id, $causer->id);
    }

    #[Test]
    public function it_can_store_and_retrieve_properties()
    {
        // Arrange
        $properties = ['field' => 'old_value', 'new_field' => 'new_value'];

        // Act
        $log = ActivityLog::factory()->create(['properties' => $properties]);

        // Assert
        // Properties may be returned as Collection, so convert both to array for comparison
        $retrievedProps = $log->properties instanceof \Illuminate\Support\Collection 
            ? $log->properties->toArray() 
            : $log->properties;
        $this->assertEquals($properties, $retrievedProps);
    }

    #[Test]
    public function it_can_filter_logs()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create();

        // Act
        $query = ActivityLog::query();
        $filtered = $query->filter([])->get();

        // Assert
        $this->assertCount(5, $filtered);
    }

    #[Test]
    public function it_can_store_subject_type_and_id()
    {
        // Act
        $log = ActivityLog::factory()->create([
            'subject_type' => 'App\\Models\\User',
            'subject_id' => 1,
        ]);

        // Assert
        $this->assertEquals('App\\Models\\User', $log->subject_type);
        $this->assertEquals(1, $log->subject_id);
    }

    #[Test]
    public function it_can_store_event()
    {
        // Act
        $log = ActivityLog::factory()->create(['event' => 'created']);

        // Assert
        $this->assertEquals('created', $log->event);
    }

    #[Test]
    public function it_can_be_deleted()
    {
        // Arrange
        $log = ActivityLog::factory()->create();
        $logId = $log->id;

        // Act
        $log->delete();

        // Assert
        $this->assertNull(ActivityLog::find($logId));
    }

    #[Test]
    public function it_uses_timestamps()
    {
        // Act
        $log = ActivityLog::factory()->create();

        // Assert
        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }
}
