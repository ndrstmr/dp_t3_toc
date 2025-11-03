# TypoScript Reference

## Overview

The extension uses TYPO3 v13 Site Sets for configuration. All TypoScript is automatically loaded when the Site Set is activated.

## File Location

```
Configuration/
└── Sets/
    └── Toc/
        ├── config.yaml              # Site Set configuration
        ├── settings.definitions.yaml # Settings schema
        ├── setup.typoscript         # Content element TypoScript
        └── page.tsconfig            # Backend configuration
```

## Content Element Configuration

The TOC content element is configured via `setup.typoscript`:

```typoscript
tt_content.menu_table_of_contents =< lib.contentElement
tt_content.menu_table_of_contents {
    templateRootPaths {
        10 = EXT:dp_t3_toc/Resources/Private/Templates/
    }

    # Template Selection: Bootstrap 5 or Kern UX
    templateName = TEXT
    templateName {
        data = siteSettings:dp_t3_toc.template
        ifEmpty = TableOfContents
    }

    dataProcessing {
        # 1. Process FlexForm (Content Element Settings)
        10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
        10 {
            fieldName = pi_flexform
            as = tocSettings
        }

        # 2. Build TOC Items
        20 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
        20 {
            # Target page(s)
            pidInList.cObject = TEXT
            pidInList.cObject {
                field = pages
                ifEmpty.data = page:uid
            }

            # Filter mode (with fallback chain)
            mode.field = tocSettings.settings.mode
            mode.ifEmpty.data = siteSettings:dp_t3_toc.defaultMode

            # Include specific columns
            includeColPos.field = tocSettings.settings.includeColPos

            # Exclude specific columns (with fallback)
            excludeColPos.field = tocSettings.settings.excludeColPos
            excludeColPos.ifEmpty.data = siteSettings:dp_t3_toc.defaultExcludeColPos

            # Maximum nesting depth (with fallback)
            maxDepth.field = tocSettings.settings.maxDepth
            maxDepth.ifEmpty.data = siteSettings:dp_t3_toc.defaultMaxDepth

            # Output variable name
            as = tocItems
        }
    }
}
```

## Configuration Priority

The extension follows a 3-level configuration cascade:

### 1. FlexForm (Highest Priority)

Set per content element in the Backend:
- Mode (sectionIndexOnly, visibleHeaders, all)
- Include/Exclude colPos
- Max depth
- Layout, Scrollspy, Sticky

### 2. Site Settings (Medium Priority)

Set in Site Configuration (`settings.yaml`):
```yaml
settings:
  dp_t3_toc:
    defaultMode: 'sectionIndexOnly'
    defaultExcludeColPos: '5,88'
    defaultMaxDepth: 0
```

### 3. Extension Defaults (Lowest Priority)

Defined in `Configuration/Sets/Toc/settings.definitions.yaml`:
```yaml
settings:
  dp_t3_toc:
    defaultMode:
      value: 'sectionIndexOnly'
    # ... other defaults
```

## TocProcessor Options

### pidInList

**Description**: Target page(s) to generate TOC from

**Type**: String (comma-separated page UIDs)

**Default**: Current page (`page:uid`)

**Example**:
```typoscript
20.pidInList = 1,2,3
# Or from field:
20.pidInList.field = pages
```

### mode

**Description**: Filter mode for content elements

**Type**: String

**Values**:
- `sectionIndexOnly`: Only elements with "Show in section index" checked
- `visibleHeaders`: All visible headers (excludes `header_layout=100`)
- `all`: All elements with headers

**Default**: `sectionIndexOnly`

**Example**:
```typoscript
20.mode = visibleHeaders
# Or from Site Settings:
20.mode.data = siteSettings:dp_t3_toc.defaultMode
```

### includeColPos

**Description**: Whitelist of column positions to include

**Type**: String (comma-separated integers)

**Default**: All columns (no filter)

**Example**:
```typoscript
20.includeColPos = 0,1,2
```

### excludeColPos

**Description**: Blacklist of column positions to exclude

**Type**: String (comma-separated integers)

**Default**: Empty (no exclusions)

**Example**:
```typoscript
20.excludeColPos = 5,88,99
```

**Note**: `excludeColPos` takes precedence over `includeColPos`

### maxDepth

**Description**: Maximum nesting depth for containers

**Type**: Integer

**Values**:
- `0`: Unlimited depth (default)
- `> 0`: Stop recursion at specified level

**Default**: `0`

**Example**:
```typoscript
20.maxDepth = 3
```

### as

**Description**: Variable name for TOC items in Fluid template

**Type**: String

**Default**: `tocItems`

**Example**:
```typoscript
20.as = tocItems
```

## Page-Level DataProcessor (Advanced)

For custom implementations, you can use the TocProcessor at page level:

```typoscript
page = PAGE
page {
    dataProcessing {
        10 = TYPO3\CMS\Frontend\DataProcessing\PageContentFetchingProcessor
        10 {
            as = pageContent
        }

        20 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
        20 {
            as = tocItems
            mode = visibleHeaders
            pidInList = 1,2,3
            excludeColPos = 5,88
            maxDepth = 3
        }
    }
}
```

Then in your page template:

```html
<f:for each="{tocItems}" as="item">
    <a href="{item.anchor}">{item.title}</a>
</f:for>
```

## Customization

### Custom Template Paths

```typoscript
tt_content.menu_table_of_contents {
    templateRootPaths {
        10 = EXT:dp_t3_toc/Resources/Private/Templates/
        20 = EXT:my_sitepackage/Resources/Private/Templates/TocOverrides/
    }
}
```

### Custom Template Selection

```typoscript
tt_content.menu_table_of_contents {
    templateName = TEXT
    templateName {
        # Use custom logic
        value = MyCustomTemplate
    }
}
```

## Backend Configuration (page.tsconfig)

Backend configuration is handled in `page.tsconfig`:

```tsconfig
# Add to New Content Element Wizard
mod.wizards.newContentElement.wizardItems {
    menu {
        elements {
            menu_table_of_contents {
                iconIdentifier = content-table-of-contents
                title = LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents
                description = LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents.description
                tt_content_defValues {
                    CType = menu_table_of_contents
                }
            }
        }
        show := addToList(menu_table_of_contents)
    }
}

# Default FlexForm values for new elements
TCAdefaults {
    tt_content {
        pi_flexform.data.sDEF.lDEF {
            settings.layout.vDEF = sidebar
            settings.scrollspy.vDEF = 1
            settings.sticky.vDEF = 1
        }
    }
}
```

## Related Documentation

- [Site Sets Configuration](SiteSets.md) - Full Site Settings reference
- [Developer/PSR14Events.md](../Developer/PSR14Events.md) - Event-based customization

---

**Next:** [Advanced Configuration with PSR-14 Events](../Developer/PSR14Events.md) →
