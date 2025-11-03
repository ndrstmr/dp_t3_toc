# Testing

## Test Suite Overview

- **153 Unit Tests** (268 assertions)
- **PHPStan**: Max level, 0 errors
- **Code Coverage**: High (core services 100%)

## Running Tests

```bash
# All unit tests
composer test:unit

# Specific test file
composer test:unit -- --filter TocBuilderServiceTest

# With coverage
composer test:unit -- --coverage-html coverage/
```

## Test Structure

```
Tests/
└── Unit/
    ├── DataProcessing/
    │   └── TocProcessorTest.php
    ├── Domain/
    │   └── Model/
    │       ├── TocItemTest.php
    │       └── TocConfigurationTest.php
    └── Service/
        ├── TocBuilderServiceTest.php
        ├── TocItemMapperTest.php
        └── TcaContainerCheckServiceTest.php
```

## Test Categories

### TocBuilderServiceTest (40+ tests)

- Filter modes (sectionIndexOnly, visibleHeaders, all)
- Column position filtering (include/exclude)
- Container nesting and recursion
- Max depth limits
- XSS prevention
- Header-link anchor generation
- PSR-14 event dispatching

### TocItemMapperTest (12 tests)

- Basic TocItem creation
- Anchor generation (default #c{uid})
- Header-link validation and XSS prevention
- Path handling for nested containers
- Type safety and edge cases

### TocProcessorTest (15+ tests)

- DataProcessor integration
- FlexForm processing
- Site Settings fallbacks
- Multi-page TOC
- Error handling

## Test Doubles

### Mocking Strategy

```php
// Repository Mock
$mockRepo = $this->createMock(ContentElementRepositoryInterface::class);
$mockRepo->method('findByPages')->willReturn([/* test data */]);

// Real services (no mocks needed)
$tocItemMapper = new TocItemMapper(); // No dependencies
```

### Test Data Fixtures

```php
private function createTestElement(int $uid, string $header): array
{
    return [
        'uid' => $uid,
        'header' => $header,
        'colPos' => 0,
        'sectionIndex' => 1,
        'sorting' => 256,
        'CType' => 'text',
        'header_layout' => 0,
    ];
}
```

## Quality Checks

```bash
# All QA checks
composer check

# Individual checks
composer check:php:stan    # PHPStan max level
composer check:php:cs      # PSR-12 code style
composer check:php:rector  # TYPO3 v13 rules
composer check:php:lint    # Syntax check
```

## PHPStan Configuration

`Build/phpstan/phpstan.neon`:

```neon
parameters:
    level: max
    paths:
        - Classes
        - Tests
    strictRules:
        allRules: true
```

## Continuous Integration

GitHub Actions runs full test suite on:
- Push to `main`
- Pull requests to `main`

See `.github/workflows/ci.yml`

## Writing Tests (TDD)

1. **Write test FIRST** (RED)
2. **Implement feature** (GREEN)
3. **Refactor** (maintain GREEN)

Example:

```php
public function testNewFeature(): void
{
    // Arrange
    $config = new TocConfiguration(mode: 'all');

    // Act
    $result = $this->service->buildForPageWithConfig(1, $config);

    // Assert
    static::assertCount(5, $result);
}
```

---

**Coverage Goal**: 90%+ for core services
**PHPStan Level**: max (always)
