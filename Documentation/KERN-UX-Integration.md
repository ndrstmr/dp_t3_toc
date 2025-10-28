# Kern UX Integration

Die Extension bietet **native Kern UX-Unterstützung** mit offiziellen Design-Tokens und Komponenten-Patterns.

## 🎨 Was ist Kern UX?

[Kern UX](https://gitlab.opencode.de/kern-ux/kern-ux-plain) ist der **offene UX-Standard für die deutsche Verwaltung**. Es ist ein Designsystem, das von Hamburg und Schleswig-Holstein initiiert wurde und barrierefreie digitale Verwaltungslösungen ermöglicht.

## 📦 Kern UX Template & Styles

### Option 1: Kern UX Template verwenden

```typoscript
tt_content.menu_table_of_contents {
    templateName = TableOfContentsKern  # Statt TableOfContents
}
```

### Option 2: CSS einbinden

```html
<!-- Kern UX Core -->
<link rel="stylesheet" href="path/to/kern-ux-plain/dist/kern.css" />

<!-- TOC Kern UX Styles -->
<link rel="stylesheet" href="{f:uri.resource(path:'Css/toc-kern.css', extensionName:'DpT3Toc')}" />
```

## 🔧 Verwendete Kern UX Design Tokens

### Farben
```css
--kern-color-action-default              /* Link-Farbe */
--kern-color-action-visited              /* Besuchte Links */
--kern-color-action-state-indicator-*    /* Hover/Active States */
--kern-color-layout-text-default         /* Text-Farbe */
--kern-color-layout-background-default   /* Hintergrund */
--kern-color-layout-border               /* Rahmen */
```

### Abstände
```css
--kern-metric-space-none      /* 0px */
--kern-metric-space-2x-small  /* 2px */
--kern-metric-space-x-small   /* 4px */
--kern-metric-space-small     /* 8px */
--kern-metric-space-default   /* 16px */
--kern-metric-space-large     /* 24px */
--kern-metric-space-x-large   /* 32px */
```

### Typografie
```css
--kern-typography-font-family-default
--kern-typography-font-size-static-medium
--kern-typography-line-height-static-medium
--kern-typography-font-weight-semi-bold
```

### Rahmen
```css
--kern-metric-border-width-light    /* 1px */
--kern-metric-border-width-default  /* 2px */
--kern-metric-border-radius-small   /* 2px */
--kern-metric-border-radius-default /* 4px */
```

## 🧩 Verwendete Kern UX Komponenten

Das Template nutzt folgende offizielle Kern UX Klassen:

### Listen-Komponente
```html
<ul class="kern-list">
    <li class="kern-toc__item">...</li>
</ul>

<ul class="kern-list kern-list--horizontal">
    <!-- Horizontale Liste -->
</ul>
```

### Link-Komponente
```html
<a class="kern-link kern-toc__link" href="#anchor">
    Link-Text
</a>
```

### Button-Komponente (Dropdown)
```html
<button class="kern-btn kern-btn--secondary">
    Inhaltsverzeichnis
</button>
```

## 🌓 Dark Mode Support

Dark Mode wird **automatisch** durch Kern UX Design Tokens unterstützt:

### Via System-Präferenz
```css
@media (prefers-color-scheme: dark) {
    /* Kern UX schaltet automatisch um */
}
```

### Via HTML-Attribut
```html
<html data-kern-theme="dark">
    <!-- TOC nutzt automatisch Dark Mode Tokens -->
</html>
```

### Via CSS-Klasse
```html
<body class="kern-dark">
    <!-- TOC nutzt automatisch Dark Mode Tokens -->
</body>
```

## ♿ Barrierefreiheit (WCAG 2.1 AA)

Die Kern UX-Integration bietet erweiterte Accessibility-Features:

### Focus States
```css
.kern-toc__link:focus-visible {
    /* Kern UX 3-Ring Focus-Indikator */
    box-shadow:
        0 0 0 2px var(--kern-color-action-on-default),
        0 0 0 4px var(--kern-color-action-focus-border-inside),
        0 0 0 6px var(--kern-color-action-focus-border-outside);
}
```

### High Contrast Mode
```css
@media (prefers-contrast: high) {
    .kern-toc__link {
        text-decoration-thickness: 3px;
        border-width: 4px;
    }
}
```

### Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
    .kern-toc__link {
        transition: none;
    }
}
```

## 📱 Responsive Design

### Mobile (< 768px)
- Vertikale Stapelung
- Border oben statt links
- Vollbreite Links

### Desktop (≥ 768px)
- Adaptive Typografie (größere Schrift)
- Sidebar max-width: 300px
- Sticky positioning

## 🎯 Vorteile der Kern UX-Integration

✅ **Standards-konform**: Offizieller Verwaltungsstandard
✅ **Wartbar**: Zentrale Design Tokens statt hartcodierte Werte
✅ **Zugänglich**: WCAG 2.1 AA + BITV 2.0 konform
✅ **Konsistent**: Einheitliches Look & Feel mit anderen Verwaltungsanwendungen
✅ **Zukunftssicher**: Updates über Kern UX Package
✅ **Dark Mode**: Automatische Umschaltung ohne zusätzlichen Code

## 🔗 Ressourcen

- **Kern UX GitLab**: https://gitlab.opencode.de/kern-ux/kern-ux-plain
- **Kern UX Dokumentation**: https://gitlab.opencode.de/kern-ux/pattern-library
- **BITV 2.0**: https://www.bitvtest.de/bitv_test.html
- **WCAG 2.1**: https://www.w3.org/WAI/WCAG21/quickref/

## 💡 Beispiel-Integration

### TYPO3 Site Package

```typoscript
page {
    includeCSS {
        # Kern UX Core (aus npm package)
        kern = EXT:site_package/Resources/Public/Css/Vendor/kern.css

        # TOC Kern UX Styles
        tocKern = EXT:dp_t3_toc/Resources/Public/Css/toc-kern.css
    }
}

tt_content.menu_table_of_contents {
    templateName = TableOfContentsKern

    dataProcessing {
        10 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
        10 {
            as = tocItems
            mode = sectionIndexOnly
            excludeColPos = 5,88
        }
    }
}
```

### HTML Output

```html
<nav id="kern-toc-123" class="kern-toc kern-toc--sidebar sticky-top" aria-label="Inhaltsverzeichnis">
    <h2 class="kern-toc__header">Inhaltsverzeichnis</h2>
    <ul class="kern-toc__list kern-list">
        <li class="kern-toc__item kern-toc__item--level-2">
            <a class="kern-toc__link kern-link" href="#c1">Header 1</a>
        </li>
        <li class="kern-toc__item kern-toc__item--level-2">
            <a class="kern-toc__link kern-link" href="#c2">Header 2</a>
        </li>
    </ul>
</nav>
```

---

**Entwickelt für die TYPO3-Community mit Kern UX-Standards** ❤️
