# PSR-14 Events

**Since:** v4.2.0

The extension dispatches PSR-14 events to allow third-party extensions to hook into the TOC building process.

## Available Events

### 1. BeforeTocItemsBuiltEvent

**When:** Before TOC building starts

**Use Cases:**
- Modify configuration dynamically
- Change target pages
- Add custom logic based on context

**Example:**

```php
namespace Vendor\Extension\EventListener;

use Ndrstmr\DpT3Toc\Event\BeforeTocItemsBuiltEvent;
use Ndrstmr\DpT3Toc\Domain\Model\TocConfiguration;

final class ModifyTocConfiguration
{
    public function __invoke(BeforeTocItemsBuiltEvent $event): void
    {
        $config = $event->getConfig();

        // Change mode based on page type
        if ($this->isLandingPage()) {
            $newConfig = new TocConfiguration(
                mode: 'all',  // Override
                allowedColPos: $config->allowedColPos,
                excludedColPos: $config->excludedColPos,
                maxDepth: $config->maxDepth,
                excludeUid: $config->excludeUid,
                useHeaderLink: $config->useHeaderLink
            );

            $event->setConfig($newConfig);
        }
    }
}
```

**Registration (Services.yaml):**

```yaml
services:
  Vendor\Extension\EventListener\ModifyTocConfiguration:
    tags:
      - name: event.listener
        identifier: 'modify-toc-config'
        event: Ndrstmr\DpT3Toc\Event\BeforeTocItemsBuiltEvent
```

### 2. TocItemFilterEvent

**When:** For each TOC item candidate

**Use Cases:**
- Filter items based on custom criteria
- Modify item properties (title, anchor, etc.)
- Skip items conditionally

**Example: Skip Items**

```php
namespace Vendor\Extension\EventListener;

use Ndrstmr\DpT3Toc\Event\TocItemFilterEvent;

final class FilterTocItems
{
    public function __invoke(TocItemFilterEvent $event): void
    {
        $item = $event->getItem();
        $rawRow = $event->getRawRow();

        // Skip items with specific CType
        if ($rawRow['CType'] === 'my_custom_type') {
            $event->skip();
            return;
        }

        // Skip items without specific field
        if (empty($rawRow['custom_field'])) {
            $event->skip();
        }
    }
}
```

**Example: Modify Items**

```php
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

final class ModifyTocItems
{
    public function __invoke(TocItemFilterEvent $event): void
    {
        $item = $event->getItem();
        $rawRow = $event->getRawRow();

        // Add prefix to titles based on custom field
        if (!empty($rawRow['custom_prefix'])) {
            $modifiedItem = new TocItem(
                data: $item->data,
                title: $rawRow['custom_prefix'] . ': ' . $item->title,
                anchor: $item->anchor,
                level: $item->level,
                path: $item->path
            );

            $event->setItem($modifiedItem);
        }
    }
}
```

**Registration:**

```yaml
services:
  Vendor\Extension\EventListener\FilterTocItems:
    tags:
      - name: event.listener
        identifier: 'filter-toc-items'
        event: Ndrstmr\DpT3Toc\Event\TocItemFilterEvent
```

### 3. AfterTocItemsBuiltEvent

**When:** After all TOC items are built

**Use Cases:**
- Modify final TOC items array
- Add/remove items globally
- Sort/reorder items
- Add metadata

**Example:**

```php
namespace Vendor\Extension\EventListener;

use Ndrstmr\DpT3Toc\Event\AfterTocItemsBuiltEvent;

final class EnhanceTocItems
{
    public function __invoke(AfterTocItemsBuiltEvent $event): void
    {
        $items = $event->getItems();

        // Sort by custom criteria
        usort($items, function($a, $b) {
            return $a->data['custom_sort'] <=> $b->data['custom_sort'];
        });

        // Filter out duplicates
        $items = array_unique($items, SORT_REGULAR);

        $event->setItems($items);
    }
}
```

## Event API Reference

### BeforeTocItemsBuiltEvent

```php
// Getters
public function getPageUids(): array;        // Target page UIDs
public function getConfig(): TocConfiguration; // Current config

// Setters
public function setPageUids(array $pageUids): void;
public function setConfig(TocConfiguration $config): void;
```

### TocItemFilterEvent

```php
// Getters
public function getItem(): TocItem;          // Current TOC item
public function getRawRow(): array;          // Raw DB row
public function isSkipped(): bool;           // Skip status

// Setters
public function setItem(TocItem $item): void;
public function skip(): void;                // Mark item as skipped
```

### AfterTocItemsBuiltEvent

```php
// Getters
public function getPageUids(): array;        // Processed page UIDs
public function getConfig(): TocConfiguration; // Used configuration
public function getItems(): array;           // Built TOC items

// Setters
public function setItems(array $items): void;
```

## Use Case Examples

### Custom Caching Strategy

```php
final class CacheTocItems
{
    public function __invoke(AfterTocItemsBuiltEvent $event): void
    {
        $cacheKey = md5(serialize($event->getPageUids()));
        $this->cache->set($cacheKey, $event->getItems());
    }
}
```

### Access Control

```php
final class FilterByAccess
{
    public function __invoke(TocItemFilterEvent $event): void
    {
        $rawRow = $event->getRawRow();

        // Skip protected content for non-logged-in users
        if ($rawRow['fe_group'] && !$this->userIsLoggedIn()) {
            $event->skip();
        }
    }
}
```

### Custom Anchor Generation

```php
final class CustomAnchorGeneration
{
    public function __invoke(TocItemFilterEvent $event): void
    {
        $item = $event->getItem();

        // Use slugified title as anchor
        $customAnchor = '#' . $this->slugify($item->title);

        $modifiedItem = new TocItem(
            data: $item->data,
            title: $item->title,
            anchor: $customAnchor,  // Override anchor
            level: $item->level,
            path: $item->path
        );

        $event->setItem($modifiedItem);
    }
}
```

## Best Practices

1. **Keep listeners focused**: One listener = one responsibility
2. **Avoid heavy computation**: Events are called for every item
3. **Use skip() early**: Don't process items you'll skip anyway
4. **Preserve immutability**: Create new TocItem instead of modifying
5. **Document your listeners**: Explain why filtering/modification happens

## Related

- [Architecture](Architecture.md) - Event-driven design
- [TypoScript](../Configuration/TypoScript.md) - TocProcessor configuration

---

**API Stability**: Stable since v4.2.0
