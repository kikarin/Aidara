<?php

namespace Tests\Unit\Traits;

use App\Models\ActivityLog;
use App\Traits\RepositoryTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositoryTraitTest extends TestCase
{
    use RefreshDatabase;

    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new class {
            use RepositoryTrait;

            public $model;
            public $with = [];
            public $withCount = [];
            public $orderDefault = 'created_at';
            public $orderDefaultSort = 'desc';
            public $orderByColumnsArray = [];
            public $selectColumn = [];
            public $whereHas = [];
            public $limit = null;

            public function __construct()
            {
                $this->model = new ActivityLog();
            }

            public function customGetAll($record, $data = [])
            {
                return $record;
            }

            public function customGetById($record)
            {
                return $record;
            }

            public function customDataCreateUpdate($data, $record = null)
            {
                return $data;
            }

            public function callbackAfterStoreOrUpdate($record, $data, $method = 'create', $record_sebelumnya = null)
            {
                return $record;
            }

            public function callbackAfterDelete($item, $request)
            {
            }

            public function callbackAfterDeleteSelected($records, $request)
            {
            }

            public function customDataDatatable($data)
            {
                return $data;
            }

            public function customRecordDatatable($record, $data = [])
            {
                return $record;
            }
        };
    }

    #[Test]
    public function it_can_get_all_records()
    {
        // Arrange
        ActivityLog::factory()->count(3)->create();

        // Act
        $records = $this->repository->getAll();

        // Assert
        $this->assertCount(3, $records);
    }

    #[Test]
    public function it_can_get_all_records_with_pagination()
    {
        // Arrange
        ActivityLog::factory()->count(15)->create();

        // Act
        $records = $this->repository->getAll(['per_page' => 10], true);

        // Assert
        $this->assertNotNull($records);
    }

    #[Test]
    public function it_can_get_count_of_all_records()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create();

        // Act
        $count = $this->repository->getCountAll();

        // Assert
        $this->assertEquals(5, $count);
    }

    #[Test]
    public function it_can_create_a_new_record()
    {
        // Arrange
        $data = [
            'log_name' => 'test.log',
            'description' => 'Test description',
        ];

        // Act
        $record = $this->repository->create($data);

        // Assert
        $this->assertNotNull($record->id);
        $this->assertEquals('test.log', $record->log_name);
    }

    #[Test]
    public function it_can_update_a_record()
    {
        // Arrange
        $record = ActivityLog::factory()->create(['log_name' => 'original.log']);
        $data = ['log_name' => 'updated.log'];

        // Act
        $updated = $this->repository->update($record->id, $data);

        // Assert
        $this->assertEquals('updated.log', $updated->log_name);
    }

    #[Test]
    public function it_can_delete_a_record()
    {
        // Arrange
        $record = ActivityLog::factory()->create();
        $recordId = $record->id;

        // Act
        $deleted = $this->repository->delete($recordId);

        // Assert
        $this->assertNull(ActivityLog::find($recordId));
        $this->assertEquals($recordId, $deleted->id);
    }

    #[Test]
    public function it_can_delete_multiple_selected_records()
    {
        // Arrange
        $records = ActivityLog::factory()->count(3)->create();
        $ids = $records->pluck('id')->toArray();

        // Act
        $this->repository->delete_selected($ids);

        // Assert
        $this->assertEquals(0, ActivityLog::whereIn('id', $ids)->count());
    }

    #[Test]
    public function it_ignores_empty_array_for_delete_selected()
    {
        // Arrange
        ActivityLog::factory()->count(3)->create();
        $initialCount = ActivityLog::count();

        // Act
        $this->repository->delete_selected([]);

        // Assert
        $this->assertEquals($initialCount, ActivityLog::count());
    }

    #[Test]
    public function it_returns_null_for_non_array_delete_selected()
    {
        // Arrange
        ActivityLog::factory()->count(3)->create();
        $initialCount = ActivityLog::count();

        // Act
        $this->repository->delete_selected('not-an-array');

        // Assert
        $this->assertEquals($initialCount, ActivityLog::count());
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
    public function it_applies_order_by_columns()
    {
        // Arrange
        ActivityLog::factory()->create(['created_at' => now()->subDay()]);
        ActivityLog::factory()->create(['created_at' => now()]);
        $this->repository->orderByColumnsArray = ['created_at' => 'asc'];

        // Act
        $records = $this->repository->getAll();

        // Assert
        $this->assertCount(2, $records);
    }

    #[Test]
    public function it_respects_limit()
    {
        // Arrange
        ActivityLog::factory()->count(5)->create();
        $this->repository->limit = 2;

        // Act
        $records = $this->repository->getAll();

        // Assert
        $this->assertCount(2, $records);
    }

    #[Test]
    public function it_can_select_specific_columns()
    {
        // Arrange
        ActivityLog::factory()->create();
        $this->repository->selectColumn = ['id', 'log_name'];

        // Act
        $records = $this->repository->getAll();

        // Assert
        $this->assertCount(1, $records);
    }
}
