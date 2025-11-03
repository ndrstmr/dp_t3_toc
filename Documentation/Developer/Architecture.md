# Architecture

## Clean Architecture Principles

The extension follows Clean Architecture and SOLID principles for maintainability and testability.

## Directory Structure

```
dp_t3_toc/
├── Classes/
│   ├── Domain/
│   │   ├── Model/              # Value Objects & Domain Models
│   │   │   ├── TocItem.php           # readonly, immutable
│   │   │   └── TocConfiguration.php  # readonly, type-safe config
│   │   └── Repository/         # Repository Interfaces
│   │       └── ContentElementRepositoryInterface.php
│   ├── Service/                # Business Logic Services
│   │   ├── TocBuilderService.php          # Main TOC building logic
│   │   ├── TocItemMapper.php              # Data → Domain Model mapping
│   │   └── TcaContainerCheckService.php   # Container detection
│   ├── DataProcessing/         # TYPO3 DataProcessors
│   │   └── TocProcessor.php
│   ├── Event/                  # PSR-14 Events
│   │   ├── BeforeTocItemsBuiltEvent.php
│   │   ├── TocItemFilterEvent.php
│   │   └── AfterTocItemsBuiltEvent.php
│   ├── Utility/                # Helper Traits
│   │   └── TypeCastingTrait.php
│   └── Infrastructure/         # TYPO3-specific implementations
│       └── Repository/
│           └── Typo3ContentElementRepository.php
├── Configuration/              # TYPO3 Configuration
│   ├── Sets/Toc/               # Site Set
│   ├── Services.yaml           # Dependency Injection
│   └── TCA/                    # TCA Overrides
├── Tests/
│   ├── Unit/                   # Unit Tests (153 tests)
│   └── Functional/             # Functional Tests (future)
└── Resources/
    ├── Private/
    │   ├── Templates/          # Fluid Templates
    │   └── Language/           # Translations
    └── Public/
        ├── Css/                # Stylesheets
        └── Icons/              # SVG Icons
```

## Design Patterns

### Repository Pattern

**Interface:** `ContentElementRepositoryInterface`
```php
interface ContentElementRepositoryInterface
{
    public function findByPages(array $pageUids): array;
    public function findAllContainerChildrenForPages(array $pageUids): array;
}
```

**Implementation:** `Typo3ContentElementRepository`
- Solves N+1 query problem with eager loading
- Single query for all container children across multiple pages

### Value Objects

**TocItem** (readonly, immutable)
```php
readonly class TocItem
{
    public function __construct(
        public array $data,       # Raw DB row
        public string $title,     # Extracted header
        public string $anchor,    # #c123 or custom
        public int $level,        # Nesting level
        public array $path,       # Parent container path
    ) {}
}
```

**TocConfiguration** (readonly, type-safe)
```php
readonly class TocConfiguration
{
    public function __construct(
        public string $mode = 'visibleHeaders',
        public ?array $allowedColPos = null,
        public ?array $excludedColPos = null,
        public int $maxDepth = 0,
        public int $excludeUid = 0,
        public bool $useHeaderLink = false,
    ) {}

    public static function fromArray(array $config): self;
}
```

### Mapper Pattern

**TocItemMapper** (Single Responsibility)
```php
readonly class TocItemMapper
{
    public function mapFromRow(
        array $row,
        int $level,
        array $path,
        bool $useHeaderLink = false
    ): TocItem;
}
```

Responsibilities:
- Map DB row → TocItem domain model
- Generate anchors (#c{uid} or header_link)
- XSS prevention via anchor sanitization

### Event-Driven Architecture (PSR-14)

See [PSR14Events.md](PSR14Events.md) for detailed event documentation.

## SOLID Principles Applied

### Single Responsibility Principle (SRP)

- `TocBuilderService`: Traversal & filtering logic
- `TocItemMapper`: Data mapping & anchor generation
- `TcaContainerCheckService`: Container detection
- `Typo3ContentElementRepository`: Data access

### Open/Closed Principle (OCP)

- PSR-14 Events allow extension without modification
- Interface-based Repository pattern
- Strategy pattern for anchor generation (extensible via events)

### Liskov Substitution Principle (LSP)

- Repository interface can be swapped (e.g., for testing)
- Service injection via Dependency Injection

### Interface Segregation Principle (ISP)

- Focused interfaces (`ContentElementRepositoryInterface`)
- No "god interfaces"

### Dependency Inversion Principle (DIP)

- Services depend on abstractions (interfaces), not concrete classes
- Dependency Injection via `Services.yaml`

## Dependency Injection

`Configuration/Services.yaml`:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Ndrstmr\DpT3Toc\:
    resource: '../Classes/*'

  # Repository binding
  Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface:
    alias: Ndrstmr\DpT3Toc\Infrastructure\Repository\Typo3ContentElementRepository
    public: true
```

## Performance Optimizations

### N+1 Query Prevention

**Problem**: Loading container children recursively = 1 query per container

**Solution**: Eager loading
```php
// 1 query for all top-level elements
$contentElements = $this->repository->findByPages($pageUids);

// 1 query for ALL container children (across all pages!)
$allChildren = $this->repository->findAllContainerChildrenForPages($pageUids);

// Group by parent UID for O(1) lookup
foreach ($allChildren as $child) {
    $parentUid = $child['tx_container_parent'];
    $this->childrenByParent[$parentUid][] = $child;
}
```

**Result**: 2 queries total instead of N+1

### O(1) Container Child Lookup

```php
// During recursion: O(1) lookup instead of DB query
$children = $this->childrenByParent[$parentUid] ?? [];
```

## Type Safety

### Strict Types

Every file starts with:
```php
declare(strict_types=1);
```

### PHPStan Max Level

- Full type annotations
- No mixed types
- Generic array types: `array<int, TocItem>`

### TypeCastingTrait

Safe type casting for mixed DB data:
```php
use TypeCastingTrait;

$uid = $this->asInt($row['uid'] ?? 0);
$title = $this->asString($row['header'] ?? '');
$children = $this->asArray($row['children'] ?? []);
```

## Security

### XSS Prevention

Anchor sanitization in `TocItemMapper`:
```php
private function sanitizeAnchor(string $headerLink, int $uid): string
{
    // Only allow alphanumeric, underscore, hyphen
    if (1 === preg_match('/^[a-zA-Z0-9_-]+$/', $headerLink)) {
        return '#'.$headerLink;
    }

    // Invalid: fall back to safe #c{uid}
    return '#c'.$uid;
}
```

**Tested with**: XSS payloads, HTML injection, special characters

## Testing

- **153 Unit Tests** (268 assertions)
- **PHPStan max level**: 0 errors
- **PSR-12 compliant**: via PHP-CS-Fixer
- **Rector**: TYPO3 v13 rules applied

See [Testing.md](Testing.md) for details.

## Related Documentation

- [PSR-14 Events](PSR14Events.md) - Extensibility via events
- [Testing Guide](Testing.md) - Test structure and coverage
- [Release Process](ReleaseProcess.md) - Versioning and releases

---

**Version:** 4.2.0
**Last Updated:** 2025-11-03
