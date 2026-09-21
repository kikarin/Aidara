# Quick Start: Running Tests

## Prerequisites
- PHP 8.1+ installed
- MySQL running and accessible
- Composer dependencies installed

## 1. First Time Setup

No special setup needed! Tests use the existing `aidara` database.

## 2. Run All Tests

```bash
php artisan test
```

Expected output:
```
 ✓ Tests\Unit\Helpers\GlobalHelperTest (30 tests)
 ✓ Tests\Unit\Models\ActivityLogTest (11 tests)
 ✓ Tests\Unit\Repositories\ActivityLogRepositoryTest (12 tests)
 ✓ Tests\Unit\Traits\RepositoryTraitTest (13 tests)
 ✓ Tests\Feature\ActivityLogControllerTest (12 tests)
 ✓ Tests\Feature\DashboardTest (2 tests)

Tests:  81 passed (152 assertions)
```

## 3. Run Specific Tests

```bash
# Run all unit tests
php artisan test tests/Unit

# Run a specific file
php artisan test tests/Unit/Helpers/GlobalHelperTest.php

# Run a specific test method
php artisan test tests/Unit/Models/ActivityLogTest.php::test_it_can_create_an_activity_log
```

## 4. Common Commands

```bash
# Run with color output (default)
php artisan test

# Run and stop on first failure
php artisan test --bail

# Run tests and watch for changes
php artisan test --watch

# Run with code coverage
php artisan test --coverage

# Run parallel tests (faster on multi-core systems)
php artisan test --parallel

# Specify number of parallel processes
php artisan test --parallel --processes=4
```

## 5. Test Coverage

Generate a coverage report:
```bash
php artisan test --coverage

# With minimum threshold
php artisan test --coverage --min=80
```

## 6. Troubleshooting

### Issue: "Connection refused"
**Cause**: MySQL not running
**Solution**: Start MySQL service
```bash
# Linux/Mac
brew services start mysql
# or
systemctl start mysql
```

### Issue: Tests timeout
**Cause**: Long running queries
**Solution**: Check database for missing indexes or use `--bail` to stop early
```bash
php artisan test --bail
```

### Issue: Permission denied
**Cause**: Database user lacks privileges
**Solution**: Verify credentials in `phpunit.xml`

### Issue: "Trying to get property of non-object"
**Cause**: Factory missing columns
**Solution**: Check model fillable array includes all needed columns

## 7. Understanding Test Results

```
PASSED Tests\Unit\Models\ActivityLogTest::test_it_can_create_an_activity_log  0.04s
FAILED Tests\Unit\Models\ActivityLogTest::test_invalid_test  0.02s
SKIPPED Tests\Unit\Models\ActivityLogTest::test_todo  
```

- ✓ PASSED - Test executed successfully
- ✗ FAILED - Test assertion failed  
- ⊘ SKIPPED - Test intentionally skipped

## 8. Test Organization

```
tests/
├── Unit/
│   ├── Helpers/        # Helper function tests
│   ├── Models/         # Model tests
│   ├── Repositories/   # Repository pattern tests
│   └── Traits/         # Trait tests
├── Feature/
│   ├── ActivityLogControllerTest.php
│   └── DashboardTest.php
└── TestCase.php        # Base test class
```

## 9. Writing New Tests

Create a new test file:
```bash
php artisan make:test Unit/MyModelTest
php artisan make:test Feature/MyFeatureTest
```

Basic test template:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class MyTest extends TestCase
{
    public function test_example()
    {
        // Arrange
        $data = [];

        // Act
        $result = true;

        // Assert
        $this->assertTrue($result);
    }
}
```

## 10. Useful Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Collections
$this->assertCount(5, $collection);
$this->assertEmpty($collection);

// Strings
$this->assertStringContainsString('needle', 'haystack');

// Arrays
$this->assertArrayHasKey('key', $array);
$this->assertArrayNotHasKey('key', $array);

// Database
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);

// HTTP
$response->assertStatus(200);
$response->assertJsonStructure(['data' => ['id']]);
```

## 11. Continuous Integration

In CI/CD pipeline, run:
```bash
php artisan test --coverage --min=80
```

This ensures:
- All tests pass
- Code coverage meets 80% minimum
- Build fails if either condition not met

## Summary

| Task | Command |
|------|---------|
| Run all tests | `php artisan test` |
| Run unit tests only | `php artisan test tests/Unit` |
| Run feature tests only | `php artisan test tests/Feature` |
| Run specific test | `php artisan test tests/Unit/Helpers/GlobalHelperTest.php` |
| Watch mode | `php artisan test --watch` |
| Coverage report | `php artisan test --coverage` |
| Parallel tests | `php artisan test --parallel` |

For more details, see [TESTING_GUIDE.md](TESTING_GUIDE.md)
