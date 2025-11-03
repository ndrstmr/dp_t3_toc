# Introduction

**dp_t3_toc** - Container-Aware Table of Contents for TYPO3 v13

## What is dp_t3_toc?

A ready-to-use Table of Contents Content Element for TYPO3 v13 with full support for nested containers (b13/container), Site Sets, and modern TYPO3 architecture.

## Key Features

### 🎯 Core Features
- **Container-Aware**: Full support for nested b13/container elements
- **Multiple Layouts**: Sidebar (sticky with scrollspy), Inline, Dropdown
- **Flexible Filtering**: Include/exclude column positions, multiple filter modes
- **Site Sets**: Modern TYPO3 v13 configuration via Site Settings
- **PSR-14 Events**: Full extensibility for third-party modifications

### 🎨 Design Systems
- **Bootstrap 5**: Modern, responsive design
- **Kern UX**: German government design standard (Kern Design System)
- **Customizable**: Bring your own CSS framework

### 🏗️ Architecture
- **Clean Architecture**: Repository pattern, Domain-Driven Design
- **SOLID Principles**: Single Responsibility, Dependency Injection
- **Type Safety**: PHP 8.3+ with strict types, PHPStan max level
- **Well Tested**: 150+ unit tests, high code coverage

### 🚀 Performance
- **N+1 Query Prevention**: Eager loading for container children
- **Optimized Recursion**: Efficient traversal with O(1) lookups
- **Caching Ready**: PSR-14 events for custom caching strategies

## Use Cases

✅ **Documentation Websites**: Auto-generate TOC for long articles
✅ **Landing Pages**: Quick navigation to page sections
✅ **Government Sites**: WCAG 2.1 AA compliant with Kern UX
✅ **Multi-Page TOC**: Aggregate TOC from multiple pages
✅ **Complex Layouts**: Handle nested containers and grids

## Quick Start

### 1. Install via Composer

```bash
composer require ndrstmr/dp-t3-toc
```

### 2. Activate Site Set

Edit your site configuration (`config/sites/yoursite/settings.yaml`):

```yaml
dependencies:
  - ndrstmr/toc
```

### 3. Add Content Element

1. Create a new content element
2. Choose **"Table of Contents"** from the menu
3. Configure in FlexForm (mode, layout, filters)
4. Save and preview

## Documentation Structure

- **[Installation](Installation.md)** - Setup and requirements
- **[Configuration](Configuration/)** - Site Sets, TypoScript, Kern UX
- **[Migration](Migration/)** - Upgrade guides
- **[Developer](Developer/)** - Architecture, Events, Testing

## Requirements

- TYPO3 v13.4+
- PHP 8.3+
- Composer 2.x

## License

GPL-2.0-or-later

## Support

- **Issues**: [GitHub Issues](https://github.com/ndrstmr/dp_t3_toc/issues)
- **Source**: [GitHub Repository](https://github.com/ndrstmr/dp_t3_toc)

---

**Next:** [Installation Guide](Installation.md) →
