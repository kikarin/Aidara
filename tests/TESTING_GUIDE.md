# Unit Tests Guide

This document provides an overview of the unit test suite for the Aidara application.

## Test Structure

The tests are organized in the following directory structure:

```
tests/
├── Feature/                          # Feature/Integration Tests
│   ├── DashboardTest.php            # Dashboard feature tests
│   └── ActivityLogControllerTest.php # Activity Log controller tests
├── Unit/                             # Unit Tests
│   ├── Helpers/
│   │   └── GlobalHelperTest.php     # Helper functions tests
│   ├── Models/
│   │   └── ActivityLogTest.php      # ActivityLog model tests
│   ├── Repositories/
│   │   └── ActivityLogRepositoryTest.php # Repository pattern tests
│   ├── Traits/
│   │   └── RepositoryTraitTest.php  # Repository trait tests
│   └── TestCase.php                 # Base test class
```

## Test Categories

### Unit Tests

#### 1. **Helpers Tests** (`tests/Unit/Helpers/GlobalHelperTest.php`)
Tests for global helper functions:
- Date formatting (`set_date`, `DateForHuman`)
- Month conversion (`ConvertBulan`)
- Currency formatting (`ConvertRpTitik`, `saparator`, `removeSaparator`)
- String utilities (`str_slug`, `RandomString`, `InitialName`)
- Date calculations (`sisaHari`, `sisaJamMenit`, `addMonthswithdate`)
- List generators (`ListPerPage`, `ListBulanBelajar`, `listTrueFalse`)

#### 2. **Models Tests** (`tests/Unit/Models/ActivityLogTest.php`)
Tests for the ActivityLog model:
- Model creation and persistence
- Timestamp formatting (created_at, updated_at)
- Model relationships (belongs to causer)
- Properties storage and retrieval
- Soft deletes
- Scope methods (filter)

#### 3. **Repositories Tests** (`tests/Unit/Repositories/ActivityLogRepositoryTest.php`)
Tests for the ActivityLogRepository:
- Retrieving all records with/without pagination
- Finding records by ID or slug
- Creating new records
- Updating existing records
- Deleting single/multiple records
- Counting records
- Eager loading relationships

#### 4. **Traits Tests** (`tests/Unit/Traits/RepositoryTraitTest.php`)
Tests for the RepositoryTrait (CRUD operations):
- `getAll()` - retrieve all records
- `getCountAll()` - count all records
- `create()` - create new records with callbacks
- `update()` - update records with transaction handling
- `delete()` - delete single record
- `delete_selected()` - delete multiple records
- Applying column ordering
- Respecting query limits
- Column selection

### Feature Tests

#### 1. **Dashboard Tests** (`tests/Feature/DashboardTest.php`)
Tests for dashboard access:
- Unauthenticated users are redirected to login
- Authenticated users can access the dashboard

#### 2. **Activity Log Controller Tests** (`tests/Feature/ActivityLogControllerTest.php`)
Tests for Activity Log controller endpoints:
- Viewing activity logs list (API)
- Viewing activity log details
- Deleting activity logs
- Pagination and sorting
- Search functionality
- Permission-based access control
- Error handling for non-existent records

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Models/ActivityLogTest.php
php artisan test tests/Feature/ActivityLogControllerTest.php
```

### Run Tests with Verbose Output
```bash
php artisan test --verbose
```

### Run Tests and Generate Code Coverage
```bash
php artisan test --coverage
```

### Run Specific Test Method
```bash
php artisan test tests/Unit/Models/ActivityLogTest.php::test_it_can_create_an_activity_log
```

### Watch Mode (Auto-run tests on file changes)
```bash
php artisan test --watch
```

## Test Setup

All tests inherit from the base `TestCase` class which:
- Uses `RefreshDatabase` trait to reset database between tests
- Disables reCAPTCHA validation
- Disables CSRF token validation for easier testing

### Database Setup
- Tests use SQLite in-memory database by default (configured in `phpunit.xml`)
- Database is automatically refreshed before each test
- Factories are used to create test data

## Key Testing Patterns

### 1. Arrange-Act-Assert Pattern
```php
public function test_example()
{
    // Arrange
    $data = ActivityLog::factory()->create();

    // Act
    $result = $this->repository->getById($data->id);

    // Assert
    $this->assertEquals($data->id, $result->id);
}
```

### 2. Testing Exceptions
```php
public function test_throws_exception()
{
    $this->expectException(ModelNotFoundException::class);
    $this->repository->getById(999);
}
```

### 3. Testing Database State
```php
public function test_database_contains_record()
{
    $this->repository->create(['name' => 'Test']);
    $this->assertDatabaseHas('activity_log', ['name' => 'Test']);
}
```

### 4. Testing API Responses
```php
public function test_api_response()
{
    $response = $this->actingAs($user)
        ->getJson('/api/activity-logs');
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'meta']);
}
```

### 5. Testing Authentication
```php
public function test_authenticated_user()
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
}
```

## Factories

Factories are used to create test data. They're located in `database/factories/`:
- `ActivityLogFactory` - creates ActivityLog records
- `UserFactory` - creates User records
- `RoleFactory` - creates Role records

Example usage:
```php
// Create single record
$log = ActivityLog::factory()->create(['log_name' => 'test']);

// Create multiple records
$logs = ActivityLog::factory()->count(5)->create();

// Create with specific attributes
$user = User::factory()->create(['email' => 'test@example.com']);
```

## Best Practices

1. **Use descriptive test names**: Test methods should clearly describe what is being tested
2. **One assertion per test**: Keep tests focused on a single behavior
3. **Use factories**: Generate test data consistently using factories
4. **Test edge cases**: Include tests for boundary conditions and error scenarios
5. **Keep tests independent**: Each test should be able to run independently
6. **Use mocking sparingly**: Prefer real implementations when testing interactions
7. **Test behavior, not implementation**: Focus on what the code does, not how it does it

## Continuous Integration

Tests should pass in CI/CD pipeline:
```bash
php artisan test --coverage --min=80
```

## Common Issues

### 1. Database Connection Issues
Ensure `phpunit.xml` is configured to use SQLite in-memory database:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### 2. Seed Data Not Available
Use factories instead of seeding during tests. If seeders are needed:
```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(DatabaseSeeder::class);
}
```

### 3. Timing Issues with Timestamps
Use `Carbon::now()` instead of `date()` to ensure consistent timestamps in tests.

## Extending Tests

To add new tests:

1. Create test file in appropriate directory under `tests/`
2. Extend `TestCase` class
3. Use `RefreshDatabase` trait if testing database operations
4. Follow naming convention: `Test` suffix
5. Use descriptive test method names with `test_` prefix

Example:
```php
<?php
namespace Tests\Unit\Models;

use Tests\TestCase;

class MyNewModelTest extends TestCase
{
    public function test_it_can_create_record()
    {
        // Test implementation
    }
}
```

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Factories](https://laravel.com/docs/factories)
