# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-10-26

### Added

#### Content Element
- **🎉 Ready-to-Use Content Element**: "Table of Contents" with visual FlexForm configuration
- **3 Layout Modes**:
  - **Sidebar**: Sticky navigation with Bootstrap scrollspy, vertical list (best for documentation)
  - **Inline**: Horizontal pills navigation (best for short TOC, landing pages)
  - **Dropdown**: Collapsible mobile-friendly menu (best for mobile-first)
- **FlexForm Configuration**: Visual backend settings for:
  - Filter Mode (Section Index Only / Visible Headers / All)
  - Layout Style (Sidebar / Inline / Dropdown)
  - Column Position Filtering (include/exclude)
  - Maximum Nesting Depth
  - Scrollspy Enable/Disable
  - Sticky Position Enable/Disable

#### Styling & Templates
- **Bootstrap 5 Support**: Complete styling with Bootstrap 5 nav components and scrollspy
  - CSS: `Resources/Public/Css/toc.css`
  - Template: `Resources/Private/Templates/TableOfContents.html`
- **Kern UX Native Support**: Official German government design system integration
  - CSS: `Resources/Public/Css/toc-kern.css` (uses Kern UX design tokens)
  - Template: `Resources/Private/Templates/TableOfContentsKern.html`
  - Auto dark mode via Kern UX tokens
  - WCAG 2.1 AA + BITV 2.0 compliant
- **Responsive Design**: Mobile/tablet/desktop breakpoints, dark mode, high contrast, reduced motion, print styles

#### TYPO3 v13 Site Sets (NEW!)
- **Site Set**: `ndrstmr/toc` for centralized configuration
- **Backend UI Settings**: Configure extension via Site Management → Settings
- **Smart Fallbacks**: FlexForm → Site Settings → Extension Defaults (3-level priority)
- **Settings Available**:
  - `template`: Choose between `TableOfContents` (Bootstrap 5) or `TableOfContentsKern` (Kern UX)
  - `defaultMode`: Default filter mode for new Content Elements
  - `defaultExcludeColPos`: Default excluded column positions
  - `defaultLayout`: Default layout style (sidebar/inline/dropdown)
  - `defaultScrollspy`: Enable scrollspy by default
  - `defaultSticky`: Enable sticky positioning by default
  - `defaultMaxDepth`: Default maximum nesting depth
- **Configuration Files**:
  - `Configuration/Sets/Toc/settings.yaml`: Site Set definition
  - `Configuration/Sets/Toc/settings.definitions.yaml`: Schema + Backend UI
  - `Configuration/Sets/Toc/config.yaml`: Default values

#### Configuration & Integration
- **PageTSconfig**: New Content Element Wizard integration
- **Static Template**: "Table of Contents" template for easy inclusion
- **Multilingual Labels**: Complete `locallang_db.xlf` for all FlexForm fields
- **TCA Configuration**: Full Content Element definition with proper field overrides

### Changed
- **TypoScript Setup**: Now uses Site Settings with intelligent fallbacks
  - `templateName`: Reads from `siteSettings:dp_t3_toc.template` (Bootstrap 5 or Kern UX)
  - `mode`: FlexForm → `siteSettings:dp_t3_toc.defaultMode` → hardcoded fallback
  - `excludeColPos`: FlexForm → `siteSettings:dp_t3_toc.defaultExcludeColPos` → `5,88`
  - Works with AND without Site Set (graceful degradation)
- **ext_localconf.php**: Registers PageTSconfig for Content Element wizard
- **ext_tables.php**: Adds static TypoScript template
- **ext_emconf.php**: Version 2.1.0, updated description with Kern UX + Site Sets

### Documentation
- **README**:
  - Updated Quick Start with Site Set configuration
  - Added Kern UX section with integration examples
  - CSS choice documentation (Bootstrap 5 vs Kern UX)
- **Documentation/Site-Set-Configuration.md** (NEW): Complete Site Set guide
  - All available settings explained
  - Backend UI vs YAML configuration
  - Fallback order documentation
  - Multi-site examples
- **Documentation/KERN-UX-Integration.md** (NEW): Kern UX native integration guide
  - Design tokens reference
  - Component mapping
  - Dark mode configuration
  - Accessibility features
  - Example integrations
- **Layout Options**: New section explaining the 3 layout modes and their use cases

### Upgrade Path

**From 2.0.0 to 2.1.0 (Non-Breaking)**:

1. **Without Site Set** (works immediately):
   ```bash
   composer update ndrstmr/dp-t3-toc
   vendor/bin/typo3 cache:flush
   ```
   Extension uses hardcoded defaults (Bootstrap 5 template, same behavior as 2.0.0).

2. **With Site Set** (opt-in for new features):
   ```yaml
   # config/sites/<site>/settings.yaml
   dependencies:
     - ndrstmr/toc

   settings:
     dp_t3_toc:
       template: 'TableOfContentsKern'  # Switch to Kern UX
       defaultMode: 'sectionIndexOnly'
       defaultExcludeColPos: '5,88'
   ```
   Enables centralized configuration and template selection.

**Backward Compatibility**:
- ✅ All 2.0.0 configurations continue to work
- ✅ Site Sets are optional (opt-in)
- ✅ No breaking changes to TypoScript API
- ✅ FlexForm values override Site Settings (existing content unchanged)

## [2.0.0] - 2025-10-25

### Added
- **Clean Architecture**: Implemented Repository Pattern with interface-based dependencies
- **Dependency Injection**: Full DI support with autowiring
- **Value Object**: Immutable `TocItem` DTO with `readonly` properties
- **Flexible Filtering**: New `excludeColPos` parameter for blacklisting columns
- **Smart Sorting**: Container children now inherit parent's colPos/sorting for correct order
- **Repository Interface**: `ContentElementRepositoryInterface` for testability
- **Service Layer**: `TocBuilderService` handles all business logic
- **Unit Tests**: Example tests included in documentation
- **PHPDoc**: Complete type hints (`@param array<int>`, etc.)
- **Top-Level Loading**: Only load elements without `tx_container_parent`, then recurse

### Changed
- **BREAKING**: Refactored `TocProcessor` to thin orchestration layer (delegates to service)
- **BREAKING**: TOC items now have structure: `['data' => [...], 'title' => ..., 'anchor' => ..., 'level' => ...]`
- **BREAKING**: Internal class structure changed (Repository Pattern, Service Layer)
- **Improved**: Container detection now more robust
- **Improved**: colPos filtering logic (exclude takes precedence over include)
- **Improved**: Empty string handling for configuration values

### Fixed
- **Container Children**: Now correctly sorted at parent's position
- **Duplicate Entries**: Container children are no longer loaded twice
- **colPos Filtering**: Container children with colPos >= 200 are now correctly filtered
- **Readonly Properties**: Fixed `end()` issue with readonly arrays (now uses `array_key_last()`)

### Removed
- Direct database access in `TocProcessor` (moved to Repository)
- `GeneralUtility::makeInstance(ConnectionPool)` in business logic

## [1.0.0] - 2025-10-XX

### Added
- Initial release
- Basic container-aware TOC DataProcessor
- Support for b13/container extension
- Three filter modes: `sectionIndexOnly`, `visibleHeaders`, `all`
- `includeColPos` parameter for whitelisting columns
- Recursive container traversal
- PHPStan level max
- Rector configuration
- PHP-CS-Fixer configuration
- GitHub Actions CI

---

## Migration Guide: 1.0 → 2.0

### TypoScript Configuration

No changes required! The TypoScript API remains backward compatible:

```typoscript
# This still works in v2.0
80 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
80 {
  as = tocItems
  mode = sectionIndexOnly
  includeColPos = 0,1,2,3,4
}
```

**New in v2.0**: `excludeColPos` parameter:

```typoscript
80 {
  excludeColPos = 5,88  # NEW: Blacklist instead of whitelist
  includeColPos =       # Can be combined or used separately
}
```

### Fluid Templates

**Minor Change**: TOC items now have a `data` wrapper:

#### Before (v1.0)

```html
<f:for each="{tocItems}" as="item">
  <a href="#{item.anchor}">{item.title}</a>
</f:for>
```

#### After (v2.0)

```html
<f:for each="{tocItems}" as="item">
  <a href="{item.anchor}">{item.data.header}</a>
  <!-- or use item.title (convenience property) -->
  <a href="{item.anchor}">{item.title}</a>
</f:for>
```

**Reason**: The `data` key contains the full `tt_content` row, allowing access to all fields.

### Custom Extensions

If you extended `TocProcessor` in custom code:

#### Before (v1.0)
```php
// Direct database access
$qb = GeneralUtility::makeInstance(ConnectionPool::class)
    ->getQueryBuilderForTable('tt_content');
```

#### After (v2.0)
```php
// Use Repository Pattern
class MyCustomTocBuilder
{
    public function __construct(
        private readonly ContentElementRepositoryInterface $repository
    ) {}

    public function buildCustomToc(): array
    {
        $elements = $this->repository->findByPage($pageUid);
        // ... your logic
    }
}
```

**Benefits**:
- ✅ Testable (mock the repository)
- ✅ No direct database coupling
- ✅ SOLID-compliant

---

## Upgrade Instructions

### Step 1: Update via Composer

```bash
composer update ndrstmr/dp-t3-toc
```

### Step 2: Flush Caches

```bash
vendor/bin/typo3 cache:flush
```

### Step 3: Test Your TOC

1. Check that TOC displays correctly
2. Verify container children are in correct order
3. Test colPos filtering

### Step 4 (Optional): Use New Features

```typoscript
# Use excludeColPos for cleaner config
80 {
  excludeColPos = 5,88  # Exclude sidebar
  # Remove includeColPos if not needed
}
```

---

## Backward Compatibility

### What's Compatible

✅ TypoScript configuration (same API)
✅ Filter modes (`sectionIndexOnly`, `visibleHeaders`, `all`)
✅ `includeColPos` parameter
✅ Container support (b13/container)
✅ Extension key (`dp_t3_toc`)
✅ Namespace (`Ndrstmr\DpT3Toc`)

### What Changed (Breaking)

❌ Internal class structure (TocProcessor, Service, Repository)
❌ TOC item structure (now with `data` key)
❌ Direct instantiation (use DI instead)

**Note**: These are internal changes. If you only use TypoScript + Fluid, no action required!

---

## Support

- **Issues**: [GitHub Issues](https://github.com/ndrstmr/dp_t3_toc/issues)
- **Discussions**: [TYPO3 Slack](https://typo3.org/community/meet/chat-slack)
- **Documentation**: See [README.md](README.md)
