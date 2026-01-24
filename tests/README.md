# Tests Directory

## Overview

This directory contains all testing files for the Connect CRM Real State plugin.

## Structure

```
tests/
├── README.md                       # This file
├── bootstrap.php                   # PHPUnit bootstrap
├── phpstan-bootstrap.php          # PHPStan bootstrap
├── Data/                          # Test fixtures
│   ├── inmovilla-procesos-properties.json
│   └── inmovilla-procesos-properties-single.json
└── Unit/                          # Unit tests
    └── HelperApiTest.php          # API Helper tests (20 tests)
```

## Quick Start

### 1. Install Dependencies
```bash
cd /path/to/plugin
composer install
```

### 2. Setup Test Environment
```bash
composer test-install
```

### 3. Run Tests
```bash
composer test
```

## Test Files

### Unit/HelperApiTest.php

**Purpose:** Tests for `API::get_property_info()` method

**Test Coverage:**
- 7 Anaconda CRM tests
- 3 Inmovilla CRM tests  
- 3 Inmovilla Procesos CRM tests
- 7 Edge case tests
- **Total: 20 tests with 68 assertions**

**Test Scenarios:**
- ✅ All fields present
- ✅ Missing fields
- ✅ Empty data
- ✅ Null values
- ✅ Empty strings
- ✅ Special characters
- ✅ Data type preservation
- ✅ Unknown CRM types
- ✅ Long strings
- ✅ Extra fields ignored

## Data Fixtures

### Data/inmovilla-procesos-properties.json

Sample property list from Inmovilla Procesos API for testing property fetching and parsing.

### Data/inmovilla-procesos-properties-single.json

Sample single property detail from Inmovilla Procesos API for testing property detail fetching.

## Running Tests

### All Tests
```bash
composer test
# or
vendor/bin/phpunit
```

### Specific Test File
```bash
vendor/bin/phpunit tests/Unit/HelperApiTest.php
```

### Specific Test Method
```bash
vendor/bin/phpunit --filter test_get_property_info_anaconda_all_fields
```

### With Coverage Report
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Debug Mode
```bash
composer test-debug
```

## Adding New Tests

### 1. Create Test File

Create a new file in `tests/Unit/`:

```php
<?php
namespace Close\ConnectCRM\RealState\Tests\Unit;

use WP_UnitTestCase;

class MyFeatureTest extends WP_UnitTestCase {
    
    public function test_my_feature() {
        // Arrange
        $input = 'test';
        
        // Act
        $result = my_function( $input );
        
        // Assert
        $this->assertEquals( 'expected', $result );
    }
}
```

### 2. Run Your New Tests

```bash
composer test
```

## Test Naming Conventions

- **File Names:** `ClassNameTest.php`
- **Class Names:** `ClassNameTest extends WP_UnitTestCase`
- **Method Names:** `test_method_name_scenario()`
- Use underscores, be descriptive

**Examples:**
- ✅ `test_get_property_info_anaconda_all_fields()`
- ✅ `test_sync_property_with_missing_fields()`
- ❌ `testProperty()`
- ❌ `test1()`

## Bootstrap Files

### bootstrap.php

Main PHPUnit bootstrap file:
- Sets up WordPress test environment
- Defines test constants
- Loads plugin files

### phpstan-bootstrap.php

PHPStan bootstrap file:
- Defines constants for static analysis
- Mocks WordPress functions
- Prevents PHPStan errors

## Common Issues

### Database Connection Error

**Error:** `Error establishing a database connection`

**Solution:**
```bash
# Run test installation
composer test-install

# Or manually
bash bin/install-wp-tests.sh wordpress_test root 'root' 127.0.0.1 latest
```

### Class Not Found

**Error:** `Class 'API' not found`

**Solution:**
Check namespace and use statements:
```php
use Close\ConnectCRM\RealState\API;
```

### WordPress Functions Undefined

**Error:** `Call to undefined function add_action()`

**Solution:**
Extend `WP_UnitTestCase`:
```php
class MyTest extends WP_UnitTestCase {
    // ...
}
```

## Documentation

For detailed documentation:
- [Unit Tests Guide](../docs/unit-tests.md)
- [Test Cases for get_property_info()](../docs/test-cases-get-property-info.md)

## Continuous Integration

Tests run automatically on GitHub Actions:
- On pull requests to `trunk`
- PHP versions: 8.3, 8.2, 8.1, 7.4
- With MySQL 8.0

## Best Practices

1. ✅ Write tests before fixing bugs (TDD)
2. ✅ Keep tests fast and isolated
3. ✅ One assertion per test when possible
4. ✅ Use descriptive test names
5. ✅ Test edge cases and error conditions
6. ✅ Mock external dependencies
7. ✅ Clean up after tests (setUp/tearDown)

## Current Coverage

| Component | Tests | Status |
|-----------|-------|--------|
| API::get_property_info() | 20 | ✅ Complete |
| Import functionality | 0 | ⏳ Pending |
| Sync functionality | 0 | ⏳ Pending |
| Admin pages | 0 | ⏳ Pending |
| Shortcodes | 0 | ⏳ Pending |

## Next Steps

Priority tests to add:

1. **Import Tests**
   - Test manual import process
   - Test import statistics calculation
   - Test property creation/update

2. **Sync Tests**
   - Test automatic sync
   - Test field mapping
   - Test merge functionality

3. **Admin Tests**
   - Test settings save/load
   - Test AJAX handlers
   - Test nonce verification

4. **Frontend Tests**
   - Test gallery shortcode
   - Test property info shortcode
   - Test featured image URL

## Contributing

When adding new features:

1. Write tests first (TDD)
2. Ensure all tests pass
3. Add documentation for new tests
4. Update this README if needed

## Resources

- [WordPress PHPUnit Docs](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Manual](https://phpunit.de/documentation.html)
- [Yoast PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills)
