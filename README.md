# dp_t3_toc — Container-Aware TOC DataProcessor for TYPO3 v13

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Container-aware Table of Contents (TOC) DataProcessor** for TYPO3 v13 with Clean Architecture, Repository Pattern, and full support for [b13/container](https://github.com/b13/container).

## ✨ Features

- ✅ **Zero Dependencies**: Vanilla CSS baseline, works out-of-the-box without Bootstrap or frameworks
- ✅ **Ready-to-Use Content Element**: Plug & Play TOC with visual backend configuration
- ✅ **3 Template Styles**: Base (vanilla), Bootstrap 5, Kern UX (German government standard)
- ✅ **3 Layout Modes**: Sidebar (sticky scrollspy), Inline (horizontal), Compact (accordion, mobile-friendly)
- ✅ **Container-Aware**: Full support for nested b13/container structures
- ✅ **Flexible Filtering**: Include/exclude colPos, multiple filter modes
- ✅ **SOLID-Compliant**: Clean Architecture with Repository Pattern
- ✅ **Dependency Injection**: Fully testable with interface-based dependencies
- ✅ **Smart Sorting**: Respects colPos and sorting, container children stay with parent
- ✅ **Multiple Modes**: sectionIndexOnly, visibleHeaders, all
- ✅ **WCAG 2.1 AA Compliant**: Accessible navigation with proper ARIA labels
- ✅ **Production-Ready**: PHPStan level max, Rector, PHP-CS-Fixer, Unit Tests

## 📦 Installation

```bash
composer require ndrstmr/dp-t3-toc
```

Run extension setup to execute database migrations and clear caches:

```bash
vendor/bin/typo3 extension:setup
```

> **Note:** In Composer mode (TYPO3 v11+), extensions are automatically activated after `composer require`. The `extension:setup` command ensures all database tables and configuration are properly initialized.

## 🚀 Quick Start

### Option 1: Content Element (Recommended - Zero Configuration!)

1. **Enable Site Set**
   ```yaml
   # config/sites/<yoursite>/settings.yaml
   dependencies:
     - ndrstmr/toc
   ```

2. **Configure Template Style** (Backend UI or YAML)

   **Via Backend:** Site Management → [Your Site] → Settings → Table of Contents

   **Via YAML:**
   ```yaml
   # config/sites/<yoursite>/settings.yaml
   settings:
     dp_t3_toc:
       template: 'TableOfContentsBase'  # Vanilla CSS (default, zero dependencies)
       # OR
       template: 'TableOfContentsBootstrap'  # Bootstrap 5 (requires Bootstrap CSS)
       # OR
       template: 'TableOfContentsKern'  # Kern UX (requires Kern UX CSS, government standard)
   ```

3. **Add CSS** (optional - only if using Bootstrap or Kern UX)
   ```html
   <!-- Vanilla (default): No additional CSS needed! Already included. -->

   <!-- If using Bootstrap 5 -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <!-- Note: Bootstrap JavaScript IS required for Accordion (compact layout) -->

   <!-- If using Kern UX -->
   <link rel="stylesheet" href="path/to/kern-ux-plain/dist/kern.css" />
   ```

3. **Create Content Element**
   - Add new content element → Menu → **Table of Contents**
   - Configure via FlexForm (all visual, no TypoScript needed!):
     - **Layout**: Sidebar (sticky) / Inline / Compact (accordion)
     - **Enable Scrollspy**: ✓ (sidebar only)
     - **Enable Sticky**: ✓ (sidebar only)

4. **Done!** The TOC works out-of-the-box with vanilla CSS - no external dependencies needed!

> **💡 Tip:** All FlexForm fields have smart defaults from Site Settings. Editors only need to change what's specific to their content element.

See [Documentation/Configuration/SiteSets.md](Documentation/Configuration/SiteSets.md) for detailed configuration options.

### Option 2: Custom TypoScript Configuration

For advanced custom implementations:

```typoscript
page = PAGE
page {
  dataProcessing {
    80 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
    80 {
      as = tocItems
      mode = sectionIndexOnly
      pidInList = this
      excludeColPos = 5,88  # Exclude sidebar columns
    }
  }
}
```

Then create your own Fluid template:

```html
<f:if condition="{tocItems}">
  <nav class="toc" aria-label="Table of Contents">
    <h2>Table of Contents</h2>
    <ul>
      <f:for each="{tocItems}" as="item">
        <li class="toc-item toc-level-{item.level}">
          <a href="{item.anchor}">{item.title}</a>
        </li>
      </f:for>
    </ul>
  </nav>
</f:if>
```

## 📖 Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `as` | string | `tocItems` | Variable name for Fluid template |
| `mode` | string | `visibleHeaders` | Filter mode (see below) |
| `pidInList` | mixed | current page | Page UID or `this` |
| `includeColPos` | string | `*` | Whitelist of colPos (comma-separated) |
| `excludeColPos` | string | ` ` | Blacklist of colPos (comma-separated) |
| `maxDepth` | int | `0` | Max nesting depth (0 = unlimited) |

### Filter Modes

| Mode | Description |
|------|-------------|
| `sectionIndexOnly` | Only elements with "Include in section index" enabled (`sectionIndex = 1`) |
| `visibleHeaders` | All visible headers (`header_layout != 100`) |
| `all` | All elements with headers |

### Layout Options

The Content Element supports 3 responsive layout modes:

| Layout | Description | Best For |
|--------|-------------|----------|
| **Sidebar** | Sticky navigation with scrollspy, vertical list | Documentation, long-form content |
| **Inline** | Horizontal pills navigation | Short TOC, landing pages |
| **Compact** | Accordion pattern with expand/collapse | Mobile-first, space-constrained |

**Features**:
- **Scrollspy**: Auto-highlights active section (custom IntersectionObserver implementation, no dependencies)
- **Sticky**: Keeps TOC visible while scrolling (sidebar only)
- **Responsive**: Adapts to mobile/tablet/desktop breakpoints
- **Accessible**: WCAG 2.1 AA compliant with ARIA labels

**Compact Layout Implementation:**

The compact layout uses accessible accordion patterns for mobile-friendly navigation:

- **Base (vanilla)**: Native HTML5 `<details>`/`<summary>` - no JavaScript required, browser-native functionality
- **Bootstrap**: Bootstrap 5 Accordion component with collapse.js - requires Bootstrap JavaScript
- **Kern UX**: Native HTML5 `<details>`/`<summary>` with [Kern UX Accordion styling](https://www.kern-ux.de/komponenten/accordion) - no JavaScript required

All implementations are fully accessible with:
- Keyboard navigation (Enter, Space to toggle)
- Screen reader support (native semantic HTML)
- ARIA attributes for state (`aria-expanded`, `aria-controls`)
- Reduced motion support (`@media (prefers-reduced-motion)`)

The accordion pattern is superior to traditional dropdowns for mobile because:
- ✅ Expands inline (no overlay, no viewport overflow)
- ✅ Large touch targets (entire header is clickable)
- ✅ Native browser support (no custom JavaScript for Base/Kern)
- ✅ Semantic HTML for better accessibility

### colPos Filtering Examples

```typoscript
# Example 1: Exclude sidebar (colPos 5, 88)
excludeColPos = 5,88

# Example 2: Include only main content columns
includeColPos = 0,1,2,3,4

# Example 3: Combine both (exclude takes precedence)
includeColPos = 0,1,2,3,4,5
excludeColPos = 5  # Result: 0,1,2,3,4

# Example 4: Allow all columns
includeColPos = *
excludeColPos =
```

## 🏗️ Architecture

This extension follows **Clean Architecture** and **SOLID principles**:

```
Classes/
├── DataProcessing/
│   └── TocProcessor.php                          # Thin orchestration layer
├── Service/
│   └── TocBuilderService.php                     # Business logic
├── Domain/
│   ├── Model/
│   │   └── TocItem.php                           # Immutable Value Object
│   └── Repository/
│       ├── ContentElementRepositoryInterface.php # Abstraction
│       └── ContentElementRepository.php          # Database access
```

### Key Design Decisions

- **Single Responsibility**: Each class has one clear responsibility
- **Dependency Inversion**: Service depends on interface, not concrete implementation
- **Testability**: Repository can be mocked for unit tests
- **Immutability**: TocItem is a readonly Value Object

## 🧪 Testing & QA

Run the complete QA suite:

```bash
composer check              # Run all checks (normalize, lint, cs, stan)
composer check:php:stan     # PHPStan level max
composer check:php:cs       # PHP-CS-Fixer (dry-run)
composer check:php:rector   # Rector checks (dry-run)
composer check:php:fractor  # Fractor checks for non-PHP files (dry-run)
composer check:php:lint     # PHP syntax check
composer test:unit          # PHPUnit unit tests (115 tests, 157 assertions)

# Auto-fix issues
composer fix                # Fix composer.json + code style
composer fix:php:cs         # Apply PHP-CS-Fixer
composer fix:php:rector     # Apply Rector refactorings
composer fix:php:fractor    # Apply Fractor refactorings to TypoScript/XML/YAML
```

**QA Tooling (TYPO3 Best Practices)**:
- **PHPStan level max** with `saschaegerer/phpstan-typo3` for TYPO3 type detection (0 errors)
- **PHP-CS-Fixer** for PSR-12 compliance
- **Rector** with `ssch/typo3-rector` for TYPO3 v13 refactorings
- **Fractor** with `a9f/typo3-fractor` for non-PHP files (TypoScript, XML, YAML, Fluid)
- **PHPUnit** with TYPO3 testing framework (115 tests, 100% class coverage)
- **Composer normalize** for consistent composer.json formatting
- **PHPLint** for syntax validation

All configurations follow the **tea extension** pattern in `Build/` subdirectories.

**Test Coverage**:
- ✅ `TocProcessor` - Configuration parsing, FlexForm overrides, PageInformation handling
- ✅ `TocBuilderService` - Business logic, colPos filtering, maxDepth, container recursion
- ✅ `ContentElementRepository` - Database queries, restrictions, ordering
- ✅ `TocItem` - Value object, effective sorting/colPos
- ✅ `TcaContainerCheckService` - TCA access wrapper
- ✅ `TypeCastingTrait` - Type-safe casting helpers

### Example Unit Test

```php
use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Service\TcaContainerCheckServiceInterface;
use PHPUnit\Framework\TestCase;

class TocBuilderServiceTest extends TestCase
{
    public function testBuildForPageWithExcludedColPos(): void
    {
        $mockRepo = $this->createMock(ContentElementRepositoryInterface::class);
        $mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1,
             'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Sidebar', 'colPos' => 5, 'sectionIndex' => 1,
             'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $mockRepo->method('findContainerChildren')->willReturn([]);

        $mockContainerCheck = $this->createMock(TcaContainerCheckServiceInterface::class);
        $mockContainerCheck->method('isContainer')->willReturn(false);

        $service = new TocBuilderService($mockRepo, $mockContainerCheck);
        $toc = $service->buildForPage(1, 'sectionIndexOnly', null, [5]);

        static::assertCount(1, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
    }
}
```

## 📚 Use Cases

### Use Case 1: Mobile Navigation TOC

```typoscript
page.10.dataProcessing {
  80 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
  80 {
    # Only show if menu_section element exists
    if {
      isTrue.stdWrap.cObject = CONTENT
      isTrue.stdWrap.cObject {
        table = tt_content
        select {
          where = CType = 'menu_section'
          pidInList.data = page:uid
          max = 1
        }
        renderObj = TEXT
        renderObj.value = 1
      }
    }
    as = mobileTocSections
    mode = sectionIndexOnly
    excludeColPos = 5,88
  }
}
```

### Use Case 2: Desktop Menu Section

```typoscript
tt_content.menu_section {
  dataProcessing {
    10 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
    10 {
      dataProcessing {
        20 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
        20 {
          as = content
          mode = sectionIndexOnly
          pidInList.field = uid
          excludeColPos = 5,88
        }
      }
    }
  }
}
```

### Use Case 3: Editors Without sectionIndex Permission

Problem: Editors can't set `sectionIndex`, but sidebar should still be excluded.

```typoscript
80 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
80 {
  mode = visibleHeaders  # All visible headers (not just sectionIndex=1)
  excludeColPos = 5,88   # Exclude sidebar
}
```

## 🐳 Container Support

The processor fully supports the [b13/container](https://github.com/b13/container) extension:

### How It Works

1. **Top-Level Loading**: Only elements without `tx_container_parent` are loaded initially
2. **Container Detection**: Checks `$GLOBALS['TCA']['tt_content']['containerConfiguration']`
3. **Recursive Traversal**: Container children are loaded recursively
4. **Smart Sorting**: Container children inherit parent's colPos/sorting for correct order

### Example Structure

```
Page (colPos 0)
├── Container (uid=10, colPos=0, sorting=256)
│   ├── Header 1 (uid=11, colPos=200, tx_container_parent=10)
│   └── Header 2 (uid=12, colPos=201, tx_container_parent=10)
├── Header 3 (uid=13, colPos=0, sorting=512)
└── Container (uid=14, colPos=1, sorting=768)
    └── Header 4 (uid=15, colPos=200, tx_container_parent=14)
```

**TOC Output** (sorted by colPos, then sorting):

```
1. Header 1 (from container, effective colPos 0, sorting 256)
2. Header 2 (from container, effective colPos 0, sorting 256)
3. Header 3 (colPos 0, sorting 512)
4. Header 4 (from container, effective colPos 1, sorting 768)
```

## 🔄 Migration from DatabaseQueryProcessor

### Before

```typoscript
80 = TYPO3\CMS\Frontend\DataProcessing\DatabaseQueryProcessor
80 {
  table = tt_content
  pidInList.data = page:uid
  where = sectionIndex = 1
  orderBy = sorting
  as = tocItems
}
```

### After

```typoscript
80 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
80 {
  as = tocItems
  mode = sectionIndexOnly
  pidInList = this
  excludeColPos = 5,88
}
```

**Benefits**:
- ✅ Container-aware
- ✅ Correct sorting with colPos
- ✅ Filtering without SQL WHERE
- ✅ Configurable modes

## 🐛 Troubleshooting

### TOC is empty

**Cause 1**: No elements with `sectionIndex = 1`
```typoscript
# Solution: Change mode
mode = visibleHeaders
```

**Cause 2**: All elements in excludeColPos
```typoscript
# Solution: Remove or adjust excludeColPos
excludeColPos =
```

**Cause 3**: Cache not flushed
```bash
vendor/bin/typo3 cache:flush
```

### Container children missing

**Cause**: Container children have colPos >= 200 and are filtered by `includeColPos`

```typoscript
# Wrong (container children missing)
includeColPos = 0,1,2,3,4

# Correct (use excludeColPos instead)
excludeColPos = 5,88
includeColPos =
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Add/update tests
5. Ensure all QA tools pass (`composer test`, `composer stan`, etc.)
6. Submit a pull request

## 📄 License

This extension is licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

## 🎨 Kern UX Support

This extension provides **native Kern UX integration** with official design tokens and component patterns.

**Kern UX** is the open UX standard for German government applications, initiated by Hamburg and Schleswig-Holstein.

### Quick Start with Kern UX

```typoscript
# Use Kern UX template
tt_content.menu_table_of_contents {
    templateName = TableOfContentsKern
}
```

See [Documentation/Configuration/KernUX.md](Documentation/Configuration/KernUX.md) for detailed integration guide.

**Resources**:
- [Kern UX GitLab](https://gitlab.opencode.de/kern-ux/kern-ux-plain)
- [Kern UX Pattern Library](https://gitlab.opencode.de/kern-ux/pattern-library)

## 📖 Documentation

### User Documentation
- [Introduction](Documentation/Introduction.md) - Project overview and features
- [Installation](Documentation/Installation.md) - Setup guide and troubleshooting
- [Configuration](Documentation/Configuration/)
  - [Site Sets](Documentation/Configuration/SiteSets.md) - Complete Site Settings reference
  - [TypoScript](Documentation/Configuration/TypoScript.md) - TypoScript API reference
  - [Kern UX Integration](Documentation/Configuration/KernUX.md) - Kern UX setup guide

### Migration Guides
- [Version 4 Migration](Documentation/Migration/Version4.md) - Upgrade guide from v3 to v4

### Developer Documentation
- [Architecture](Documentation/Developer/Architecture.md) - Clean Architecture and SOLID principles
- [Testing](Documentation/Developer/Testing.md) - Test suite and QA tools
- [PSR-14 Events](Documentation/Developer/PSR14Events.md) - Event system API reference
- [Release Process](Documentation/Developer/ReleaseProcess.md) - Release workflow

## 🔗 Links

- [TYPO3 Documentation - DataProcessing](https://docs.typo3.org/m/typo3/reference-typoscript/13.4/en-us/ContentObjects/Fluidtemplate/Index.html#confval-fluidtemplate-dataprocessing)
- [b13/container Extension](https://github.com/b13/container)
- [TYPO3 v13 Site Sets](https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/SiteHandling/SiteSets.html)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Kern UX - Open UX Standard](https://gitlab.opencode.de/kern-ux)

## 🙏 Credits

Developed by [Andreas Teumer](https://github.com/ndrstmr) for the TYPO3 community.

### Development Notes

Parts of this extension's development were assisted by AI agents ([Claude Code](https://claude.com/claude-code), [Google Gemini](https://gemini.google.com)), particularly for:
- PHPStan max level refactoring and type safety improvements
- Comprehensive unit test coverage implementation
- Code review and architectural optimization

All AI-generated code was carefully reviewed, tested, and validated to meet TYPO3 best practices and PSR-12 standards.

---

**Made with ❤️ for TYPO3**
