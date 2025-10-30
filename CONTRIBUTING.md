# Contributing to dp_t3_toc

Thank you for considering contributing to dp_t3_toc! 🎉 We appreciate your time and effort.

## Development Setup

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Git

### Setup

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/dp_t3_toc.git
   cd dp_t3_toc
   ```
3. **Install dependencies**:
   ```bash
   composer install
   ```
4. **Run QA suite** to ensure everything works:
   ```bash
   composer check
   composer test:unit
   ```

## Workflow

1. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feature/my-awesome-feature
   # OR
   git checkout -b fix/my-bugfix
   ```

2. **Make your changes** following our code standards (see below)

3. **Commit** using [Conventional Commits](https://www.conventionalcommits.org/):
   ```bash
   git commit -m "feat(toc): add new feature"
   ```

4. **Push** to your fork:
   ```bash
   git push origin feature/my-awesome-feature
   ```

5. **Open a Pull Request** on GitHub against `main`

## Code Standards

We follow strict quality standards to maintain a professional codebase:

### Must-Have
- ✅ **PSR-12** code style (enforced by PHP-CS-Fixer)
- ✅ **PHPStan level max** (zero errors)
- ✅ **Unit tests** for new features/bugfixes
- ✅ **Conventional Commits** format
- ✅ **TYPO3 v13 Best Practices**

### QA Tools

Run these commands before submitting a PR:

```bash
# Run all checks at once
composer check

# Individual checks
composer check:php:stan     # PHPStan static analysis
composer check:php:cs       # Code style check
composer check:php:lint     # PHP syntax check
composer check:php:rector   # Rector rules check
composer test:unit          # PHPUnit tests

# Auto-fix issues
composer fix                # Fix composer.json + code style
composer fix:php:cs         # Auto-fix code style
```

**All checks must pass before your PR can be merged!**

## Commit Message Format

We use [Conventional Commits](https://www.conventionalcommits.org/) for automated changelog generation and semantic versioning.

### Format
```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

### Types
- `feat`: New feature (triggers MINOR version bump)
- `fix`: Bug fix (triggers PATCH version bump)
- `docs`: Documentation changes
- `style`: Code style changes (formatting, no logic change)
- `refactor`: Code refactoring (no behavior change)
- `test`: Adding or updating tests
- `chore`: Build process, tooling, dependencies

### Scopes
- `toc`: TOC building logic
- `processor`: DataProcessor
- `repository`: Repository layer
- `service`: Service layer
- `tests`: Test files
- `ci`: CI/CD configuration
- `docs`: Documentation

### Examples

**Good commits:**
```bash
feat(toc): add maxDepth parameter for limiting nesting levels
fix(repository): correct sorting in findByPage query
docs: update README with maxDepth configuration examples
test(toc): add unit tests for colPos filtering
refactor(service): extract TCA access to separate wrapper class
```

**Breaking changes:**
```bash
feat(processor)!: change constructor signature to accept new service

BREAKING CHANGE:
TocProcessor now requires TcaContainerCheckServiceInterface as second
constructor parameter. Update your Services.yaml configuration.
```

## Pull Request Process

### Before Submitting

1. ✅ **All QA checks pass** (`composer check` + `composer test:unit`)
2. ✅ **Branch is up-to-date** with `main`
3. ✅ **Tests added/updated** for your changes
4. ✅ **Documentation updated** (README.md, inline docs)
5. ✅ **CHANGELOG.md updated** (if user-facing change)

### PR Title

Use Conventional Commits format:
- `feat(toc): add support for custom anchor patterns`
- `fix(di): add missing interface bindings in Services.yaml`

### PR Description

Use the provided template. Include:
- **What** changed
- **Why** it changed (problem/use case)
- **How** to test it
- **Screenshots** (if UI change)
- **Related issues** (closes #123)

### Review Process

1. 🤖 **Automated CI checks** run (PHPStan, CS-Fixer, Tests)
2. 👤 **Code review** by maintainer (usually within 1 week)
3. 💬 **Discussion & changes** (if needed)
4. ✅ **Approval & merge** (squash merge into `main`)

## Testing Guidelines

### Unit Tests

- **All new code must have unit tests** (aim for 100% coverage)
- Tests go in `Tests/Unit/` mirroring the `Classes/` structure
- Use PHPUnit 10+ features (attributes, typed properties)
- Mock external dependencies (repositories, services)

### Test Structure

```php
<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Service\MyService;
use PHPUnit\Framework\TestCase;

final class MyServiceTest extends TestCase
{
    private MyService $service;

    protected function setUp(): void
    {
        $this->service = new MyService();
    }

    public function testSomething(): void
    {
        $result = $this->service->doSomething();

        static::assertSame('expected', $result);
    }
}
```

### Running Tests

```bash
composer test:unit                          # All tests
vendor/bin/phpunit --filter=MyServiceTest   # Specific test
```

## Documentation

### Code Documentation

- **PHPDoc** for all public methods
- **Type hints** for all parameters and return types
- **Inline comments** for complex logic only

Example:
```php
/**
 * Build TOC for a specific page.
 *
 * @param int             $pageUid        The page UID to build TOC for
 * @param string          $mode           Filter mode (sectionIndexOnly, visibleHeaders, all)
 * @param array<int>|null $allowedColPos  Whitelist of allowed column positions
 * @param array<int>|null $excludedColPos Blacklist of excluded column positions
 * @param int             $maxDepth       Maximum nesting depth (0 = unlimited)
 * @param int             $excludeUid     UID to exclude (usually the TOC element itself)
 *
 * @return array<int, TocItem> List of TOC items
 */
public function buildForPage(
    int $pageUid,
    string $mode = 'visibleHeaders',
    ?array $allowedColPos = null,
    ?array $excludedColPos = null,
    int $maxDepth = 0,
    int $excludeUid = 0,
): array {
    // ...
}
```

### User Documentation

Update `README.md` when adding:
- New configuration options
- New features
- Breaking changes
- Migration guides

## Reporting Bugs

Found a bug? Please [open an issue](https://github.com/ndrstmr/dp_t3_toc/issues/new/choose) with:

- **TYPO3 version** (e.g., 13.4.3)
- **PHP version** (e.g., 8.3.10)
- **Extension version** (e.g., 3.0.0)
- **Steps to reproduce**
- **Expected vs actual behavior**
- **Error logs** (if applicable)

## Feature Requests

Have an idea? [Open a feature request](https://github.com/ndrstmr/dp_t3_toc/issues/new/choose) with:

- **Problem description** (what use case are you solving?)
- **Proposed solution** (how should it work?)
- **Alternatives considered** (what other solutions did you think about?)
- **Additional context** (screenshots, examples, etc.)

## Getting Help

- 📖 **Documentation**: [README.md](README.md)
- 💬 **GitHub Discussions**: [Ask questions](https://github.com/ndrstmr/dp_t3_toc/discussions)
- 🐛 **Bug reports**: [GitHub Issues](https://github.com/ndrstmr/dp_t3_toc/issues)
- 💡 **Feature requests**: [GitHub Issues](https://github.com/ndrstmr/dp_t3_toc/issues)

## Code of Conduct

This project follows the [TYPO3 Code of Conduct](https://typo3.org/community/code-of-conduct).

**TL;DR:** Be respectful, inclusive, and constructive. We're all here to learn and build great software together!

## License

By contributing, you agree that your contributions will be licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) license.

---

**Thank you for contributing!** ❤️
