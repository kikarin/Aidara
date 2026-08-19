<?php

namespace Tests\Unit\Repositories;

use App\Models\ActivityLog;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ActivityLogRepository(new ActivityLog());
    }

    #[Test]
    public function it_can_get_all_activity_logs()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create();

        // Act
        $result = $this->repository->getAll();

        // Assert
        $this->assertCount(5, $result);
    }

    #[Test]
    public function it_can_get_all_with_pagination()
    {
        // Arrange
        ActivityLog::factory()->count(15)->create();

        // Act
        $result = $this->repository->getAll(['per_page' => 10], true);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->hasPages() || $result->count() > 0);
    }

    #[Test]
    public function it_can_get_activity_log_by_id()
    {
        // Arrange
        $log = ActivityLog::factory()->create();

        // Act
        $result = $this->repository->getById($log->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($log->id, $result->id);
    }

    #[Test]
    public function it_throws_exception_when_activity_log_not_found()
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->getById(999);
    }

    #[Test]
    public function it_can_find_activity_log_by_id()
    {
        // Arrange
        $log = ActivityLog::factory()->create();

        // Act
        $result = $this->repository->getFind($log->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($log->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_find_not_found()
    {
        // Act
        $result = $this->repository->getFind(999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_count_all_activity_logs()
    {
        // Arrange
        ActivityLog::factory()->count(10)->create();

        // Act
        $count = $this->repository->getCountAll();

        // Assert
        $this->assertEquals(10, $count);
    }

    #[Test]
    public function it_can_delete_activity_log_by_id()
    {
        // Arrange
        $log = ActivityLog::factory()->create();
        $logId = $log->id;

        // Act
        $result = $this->repository->delete($logId);

        // Assert
        $this->assertEquals($logId, $result->id);
        $this->assertNull(ActivityLog::find($logId));
    }

    #[Test]
    public function it_can_delete_selected_activity_logs()
    {
        // Arrange
        $logs = ActivityLog::factory()->count(3)->create();
        $ids = $logs->pluck('id')->toArray();

        // Act
        $this->repository->delete_selected($ids);

        // Assert
        $this->assertEquals(0, ActivityLog::whereIn('id', $ids)->count());
    }

    #[Test]
    public function it_can_get_instance_model()
    {
        // Act
        $model = $this->repository->getInstanceModel();

        // Assert
        $this->assertInstanceOf(ActivityLog::class, $model);
    }

    #[Test]
    public function it_can_get_detail_with_user_track()
    {
        // Arrange
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create(['causer_id' => $user->id]);

        // Act
        $result = $this->repository->getDetailWithUserTrack($log->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($log->id, $result->id);
        $this->assertNotNull($result->causer);
    }

    #[Test]
    public function it_can_delete_selected_by_ids()
    {
        // Arrange
        $logs = ActivityLog::factory()->count(3)->create();
        $ids = $logs->pluck('id')->toArray();
        $initialCount = ActivityLog::count();

        // Act
        $this->repository->deleteSelected($ids);

        // Assert
        $this->assertEquals($initialCount - 3, ActivityLog::count());
    }
}
