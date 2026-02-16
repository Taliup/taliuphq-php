# Taliup PHP SDK Tests

This directory contains the test suite for the Taliup PHP SDK.

## Test Structure

- **Unit Tests** (`tests/Unit/`) - Test individual classes and methods in isolation using mocks
- **Integration Tests** (`tests/Integration/`) - Test actual API calls (requires test credentials)

## Running Tests

### Install Dependencies

```bash
composer install
```

### Run All Tests

```bash
./vendor/bin/phpunit
```

### Run Only Unit Tests

```bash
./vendor/bin/phpunit --testsuite Unit
```

### Run Only Integration Tests

```bash
./vendor/bin/phpunit --testsuite Integration
```

### Run with Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage
```

Then open `coverage/index.html` in your browser.

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/Unit/ClientTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit --filter testConstructorWithValidConfig
```

## Writing Tests

### Unit Tests

Unit tests should mock external dependencies (like HTTP clients) and test the logic in isolation:

```php
public function testExample(): void
{
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->once()
        ->with('/endpoint', ['data' => 'value'])
        ->andReturn(['result' => 'success']);
    
    // Test your code
}
```

### Integration Tests

Integration tests require valid Taliup test credentials. Set them as environment variables:

```bash
export TALIUP_MERCHANT_SITE_ID="hpm_test_xxxxx"
export TALIUP_MERCHANT_SECRET_KEY="hpsk_test_xxxxx"
export TALIUP_BASE_URL="https://sandbox.taliuphq.com/api/v1"
```

## Test Coverage Goals

- **Overall Coverage**: 80%+
- **Public API Methods**: 100%
- **Error Scenarios**: All documented error cases must be tested

## Continuous Integration

Tests are automatically run on every commit and pull request. Ensure all tests pass before merging.
