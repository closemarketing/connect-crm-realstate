# Unit Tests Documentation

## Overview

This plugin includes comprehensive unit tests using PHPUnit to ensure code quality and prevent regressions. The test suite covers core functionality including API helpers, property management, and CRM integrations.

## Setup

### 1. Install Dependencies

First, install the required testing dependencies via Composer:

```bash
composer install
```

This will install:
- `yoast/phpunit-polyfills` - Compatibility layer for PHPUnit
- `wp-phpunit/wp-phpunit` - WordPress testing framework

### 2. Install WordPress Test Suite

Run the test installation script:

```bash
composer test-install
```

Or manually:

```bash
bash bin/install-wp-tests.sh wordpress_test root 'root' 127.0.0.1 latest
```

**Parameters:**
- `wordpress_test` - Database name for tests
- `root` - Database user
- `root` - Database password
- `127.0.0.1` - Database host
- `latest` - WordPress version

### 3. Configure Database

The test suite requires a MySQL database. Default configuration:
- **Database:** `wordpress_test`
- **User:** `root`
- **Password:** `root`
- **Host:** `127.0.0.1`

You can customize these values when running `test-install`.

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Run with Debug Mode

For debugging with Xdebug:

```bash
composer test-debug
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/HelperApiTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter test_get_property_info_anaconda_all_fields
```

## Test Structure

```
tests/
├── bootstrap.php                    # PHPUnit bootstrap file
├── phpstan-bootstrap.php           # PHPStan bootstrap
├── Data/                           # Test data fixtures
│   ├── inmovilla-procesos-properties.json
│   └── inmovilla-procesos-properties-single.json
└── Unit/                           # Unit tests
    └── HelperApiTest.php           # API Helper tests
```

## Test Coverage

### HelperApiTest.php

Tests for `API::get_property_info()` method covering:

#### Anaconda CRM Tests
- ✅ All fields present
- ✅ Missing fields
- ✅ Empty property
- ✅ Numeric ID
- ✅ Only ID present
- ✅ Only reference present
- ✅ Only date present

#### Inmovilla CRM Tests
- ✅ All fields present
- ✅ Missing reference
- ✅ Missing date

#### Inmovilla Procesos CRM Tests
- ✅ All fields present
- ✅ Missing date
- ✅ Empty property

#### Edge Cases
- ✅ Unknown CRM type
- ✅ Null values
- ✅ Empty strings
- ✅ Special characters
- ✅ Long strings
- ✅ Data type preservation
- ✅ Extra fields ignored

## Writing New Tests

### Basic Test Structure

```php
<?php
namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\API;
use WP_UnitTestCase;

class MyNewTest extends WP_UnitTestCase {
    
    /**
     * Test description
     */
    public function test_my_feature() {
        // Arrange
        $input = array( 'key' => 'value' );
        
        // Act
        $result = API::some_method( $input );
        
        // Assert
        $this->assertEquals( 'expected', $result );
    }
}
```

### Test Naming Conventions

- Test files: `ClassNameTest.php`
- Test methods: `test_method_name_scenario()`
- Use descriptive names that explain what is being tested

### Common Assertions

```php
// Equality
$this->assertEquals( $expected, $actual );
$this->assertSame( $expected, $actual ); // Strict comparison

// Types
$this->assertIsArray( $value );
$this->assertIsString( $value );
$this->assertIsInt( $value );
$this->assertNull( $value );

// Arrays
$this->assertArrayHasKey( 'key', $array );
$this->assertArrayNotHasKey( 'key', $array );
$this->assertCount( 5, $array );

// Boolean
$this->assertTrue( $value );
$this->assertFalse( $value );
```

## Using Test Data

Test data fixtures are stored in `tests/Data/` directory:

```php
public function test_with_fixture_data() {
    $json_data = file_get_contents( 
        UNIT_TESTS_DATA_PLUGIN_DIR . 'inmovilla-procesos-properties.json' 
    );
    $properties = json_decode( $json_data, true );
    
    // Use $properties in test
}
```

## Configuration Files

### phpunit.xml.dist

Main PHPUnit configuration:
- Bootstrap file
- Test suites
- Coverage settings

### tests/bootstrap.php

Test environment setup:
- Defines test constants
- Loads WordPress test framework
- Loads plugin files

## Continuous Integration

Tests are automatically run on GitHub Actions for:
- Pull requests to `trunk` branch
- Multiple PHP versions: 8.3, 8.2, 8.1, 7.4

See `.github/workflows/phpunit.yml` for CI configuration.

## Troubleshooting

### Database Connection Issues

**Error:** `Error establishing a database connection`

**Solution:**
```bash
# Verify MySQL is running
mysql --version

# Test database connection
mysql -u root -p -h 127.0.0.1

# Recreate test database
composer test-install
```

### Class Not Found Errors

**Error:** `Class 'API' not found`

**Solution:**
Ensure the namespace and use statements are correct:
```php
use Close\ConnectCRM\RealState\API;
```

### WordPress Functions Not Available

**Error:** `Call to undefined function add_action()`

**Solution:**
Make sure tests extend `WP_UnitTestCase`:
```php
class MyTest extends WP_UnitTestCase {
    // ...
}
```

### Test Database Persistence

Tests may affect the database. Use transactions or setUp/tearDown:

```php
public function setUp(): void {
    parent::setUp();
    // Setup test data
}

public function tearDown(): void {
    // Clean up test data
    parent::tearDown();
}
```

## Best Practices

1. **Isolation**: Each test should be independent
2. **Arrange-Act-Assert**: Structure tests clearly
3. **One Assertion Per Test**: Focus on single functionality
4. **Descriptive Names**: Use clear, descriptive test method names
5. **Test Edge Cases**: Cover normal, boundary, and error cases
6. **Mock External Services**: Don't make real API calls in tests
7. **Fast Tests**: Keep tests fast by avoiding unnecessary setup

## Additional Resources

- [WordPress PHPUnit Documentation](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Yoast PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills)

## Example Test Run Output

```bash
$ composer test

PHPUnit 9.6.15 by Sebastian Bergmann and contributors.

...................                                               20 / 20 (100%)

Time: 00:02.456, Memory: 48.00 MB

OK (20 tests, 68 assertions)
```

## Next Steps

To add more test coverage:

1. Create new test files in `tests/Unit/`
2. Test import functionality
3. Test synchronization logic
4. Test admin pages
5. Test shortcodes and frontend display
6. Test AJAX handlers
7. Test cron jobs

## Maintenance

- Run tests before committing changes
- Update tests when modifying functionality
- Add tests for new features
- Keep test data fixtures up to date
- Review test coverage regularly
