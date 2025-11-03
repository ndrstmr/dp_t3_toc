# Installation

## Requirements

- **TYPO3**: v13.4 or higher
- **PHP**: 8.3 or higher
- **Composer**: 2.x

### Optional Dependencies

- **b13/container**: For nested container support
- **Bootstrap 5**: For default styling
- **Kern UX**: For German government design standard

## Installation Steps

### 1. Install via Composer

```bash
composer require ndrstmr/dp-t3-toc
```

This will install the extension and all required dependencies.

### 2. Activate Extension

The extension is automatically activated via Composer. No manual activation needed.

### 3. Configure Site Set

#### Option A: Backend Configuration (Recommended)

1. Go to **Site Management** → Select your site → **Edit**
2. Navigate to **"Settings"** tab
3. In **"Dependencies"** section, add: `ndrstmr/toc`
4. Configure extension settings under **"Table of Contents"** section
5. Save

#### Option B: Manual YAML Configuration

Edit `config/sites/<yoursite>/settings.yaml`:

```yaml
dependencies:
  - ndrstmr/toc

settings:
  dp_t3_toc:
    # Template style (Bootstrap 5 or Kern UX)
    template: 'TableOfContents'  # or 'TableOfContentsKern'

    # Default filter mode for new elements
    defaultMode: 'sectionIndexOnly'

    # Default excluded column positions (comma-separated)
    defaultExcludeColPos: '5,88'

    # Default layout style
    defaultLayout: 'sidebar'

    # Default feature flags
    defaultScrollspy: true
    defaultSticky: true
    defaultMaxDepth: 0
```

See [Configuration/SiteSets.md](Configuration/SiteSets.md) for all available settings.

### 4. Include CSS (Optional)

#### For Bootstrap 5

If you're using the Bootstrap 5 template and don't have Bootstrap in your project:

```html
<!-- In your page template -->
<link rel="stylesheet" href="{f:uri.resource(path:'Css/toc.css', extensionName:'DpT3Toc')}" />
```

Or include Bootstrap 5 from CDN:

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

#### For Kern UX

```html
<link rel="stylesheet" href="path/to/kern-ux-plain/dist/kern.css" />
<link rel="stylesheet" href="{f:uri.resource(path:'Css/toc-kern.css', extensionName:'DpT3Toc')}" />
```

See [Configuration/KernUX.md](Configuration/KernUX.md) for detailed Kern UX integration.

## Verification

### 1. Check Extension is Loaded

```bash
# In TYPO3 root directory
./vendor/bin/typo3 extension:list | grep dp_t3_toc
```

Should output:
```
✓ dp_t3_toc (ndrstmr/dp-t3-toc)
```

### 2. Check Site Set is Active

Go to **Site Management** → Select site → **Settings**

You should see **"Table of Contents"** section with configuration options.

### 3. Create Test Content Element

1. Go to any page
2. Create new content element
3. Select **"Menu"** → **"Table of Contents"**
4. Configure and save
5. Preview page

## Troubleshooting

### Content Element Not Visible in Wizard

**Problem**: TOC element doesn't appear in "New Content Element" wizard

**Solution**:
1. Clear all caches (Install Tool → "Flush TYPO3 and PHP Cache")
2. Check if Site Set is activated in Site Configuration
3. Verify `page.tsconfig` is loaded (in Sets/Toc/)

### No Styling / CSS Missing

**Problem**: TOC renders but has no styling

**Solution**:
1. Check if CSS is included in your page template
2. For Bootstrap 5: Ensure Bootstrap CSS/JS is loaded
3. For Kern UX: Ensure Kern Design System CSS is loaded
4. Check browser console for 404 errors

### TypoScript Not Loaded

**Problem**: Content element renders as plain text

**Solution**:
1. Check if Site Set `ndrstmr/toc` is in dependencies
2. Clear all caches
3. Check TypoScript in Backend: **"Site Management" → "TypoScript"**
4. Verify `tt_content.menu_table_of_contents` is defined

### Site Settings Not Available

**Problem**: No "Table of Contents" settings in Site Configuration

**Solution**:
1. Ensure TYPO3 v13.4+ is installed
2. Check `Configuration/Sets/Toc/settings.definitions.yaml` exists
3. Clear caches and reload Backend
4. Re-add dependency: Remove and re-add `ndrstmr/toc` in Site Config

## Next Steps

- **[Configuration Guide](Configuration/SiteSets.md)** - Configure Site Settings
- **[Usage Examples](Configuration/SiteSets.md#examples)** - Common use cases
- **[Migration Guide](Migration/Version4.md)** - Upgrading from older versions

---

**Having issues?** Open an issue on [GitHub](https://github.com/ndrstmr/dp_t3_toc/issues)
