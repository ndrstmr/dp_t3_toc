# Site Set Configuration

Die Extension nutzt **TYPO3 v13 Site Sets** für zentrale Konfiguration über das Backend.

## 🎯 Was sind Site Sets?

Site Sets sind die moderne Art in TYPO3 v13, Extension-Konfigurationen zu verwalten. Statt TypoScript-Konstanten nutzt man **Settings**, die zentral im Backend verwaltet werden.

## 📦 Site Set einbinden

### 1. Site Set aktivieren

**Site Management → [Deine Site] → Edit → Settings**

```yaml
# config/sites/<yoursite>/settings.yaml
dependencies:
  - ndrstmr/toc  # TOC Site Set laden
```

### 2. Konfiguration anpassen (optional)

**Site Management → [Deine Site] → Edit → Settings**

```yaml
# config/sites/<yoursite>/settings.yaml
settings:
  dp_t3_toc:
    # Template-Auswahl
    template: 'TableOfContentsKern'  # oder 'TableOfContents' (Bootstrap 5)

    # Defaults für neue Content-Elemente
    defaultMode: 'sectionIndexOnly'
    defaultExcludeColPos: '5,88'
    defaultLayout: 'sidebar'
    defaultScrollspy: true
    defaultSticky: true
    defaultMaxDepth: 0
```

## ⚙️ Verfügbare Settings

### `template`
**Template-Stil auswählen**

| Wert | Beschreibung |
|------|--------------|
| `TableOfContents` | Bootstrap 5 (default) - Moderne Websites |
| `TableOfContentsKern` | Kern UX - Deutscher Verwaltungsstandard |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    template: 'TableOfContentsKern'
```

### `defaultMode`
**Standard-Filtermodus für neue Content-Elemente**

| Wert | Beschreibung |
|------|--------------|
| `sectionIndexOnly` | Nur Elemente mit "Im Inhaltsverzeichnis anzeigen" |
| `visibleHeaders` | Alle sichtbaren Header |
| `all` | Alle Elemente mit Headern |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultMode: 'visibleHeaders'
```

### `defaultExcludeColPos`
**Standard-Spalten zum Ausschließen**

Komma-separierte Liste von `colPos`-Werten (z.B. Sidebar-Spalten).

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultExcludeColPos: '5,88,99'
```

### `defaultLayout`
**Standard-Layout-Stil**

| Wert | Beschreibung |
|------|--------------|
| `sidebar` | Sticky Navigation mit Scrollspy |
| `inline` | Horizontale Pills-Navigation |
| `dropdown` | Mobile-freundliches Dropdown-Menü |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultLayout: 'inline'
```

### `defaultScrollspy`
**Scrollspy standardmäßig aktivieren**

| Wert | Beschreibung |
|------|--------------|
| `true` | Scrollspy aktiviert (Standard) |
| `false` | Scrollspy deaktiviert |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultScrollspy: false
```

### `defaultSticky`
**Sticky Position standardmäßig aktivieren**

| Wert | Beschreibung |
|------|--------------|
| `true` | Sticky aktiviert (Standard) |
| `false` | Sticky deaktiviert |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultSticky: false
```

### `defaultMaxDepth`
**Standard-Verschachtelungstiefe**

| Wert | Beschreibung |
|------|--------------|
| `0` | Unbegrenzt (Standard) |
| `> 0` | Maximale Tiefe für Container |

**Beispiel:**
```yaml
settings:
  dp_t3_toc:
    defaultMaxDepth: 3
```

## 🎨 Beispiel-Konfigurationen

### Beispiel 1: Bootstrap 5 für moderne Website

```yaml
# config/sites/mysite/settings.yaml
dependencies:
  - ndrstmr/toc

settings:
  dp_t3_toc:
    template: 'TableOfContents'
    defaultMode: 'sectionIndexOnly'
    defaultExcludeColPos: '5,88'
    defaultLayout: 'sidebar'
    defaultScrollspy: true
    defaultSticky: true
```

**CSS einbinden:**
```html
<link rel="stylesheet" href="{f:uri.resource(path:'Css/toc.css', extensionName:'DpT3Toc')}" />
```

### Beispiel 2: Kern UX für Verwaltungsanwendung

```yaml
# config/sites/verwaltungsportal/settings.yaml
dependencies:
  - ndrstmr/toc

settings:
  dp_t3_toc:
    template: 'TableOfContentsKern'
    defaultMode: 'visibleHeaders'
    defaultExcludeColPos: '5,88'
    defaultLayout: 'sidebar'
    defaultScrollspy: true
    defaultSticky: true
```

**CSS einbinden:**
```html
<link rel="stylesheet" href="path/to/kern-ux-plain/dist/kern.css" />
<link rel="stylesheet" href="{f:uri.resource(path:'Css/toc-kern.css', extensionName:'DpT3Toc')}" />
```

### Beispiel 3: Inline TOC für Landing Pages

```yaml
# config/sites/landingpage/settings.yaml
dependencies:
  - ndrstmr/toc

settings:
  dp_t3_toc:
    template: 'TableOfContents'
    defaultMode: 'all'
    defaultExcludeColPos: ''
    defaultLayout: 'inline'
    defaultScrollspy: false
    defaultSticky: false
```

## 🔄 Fallback-Reihenfolge

Die Konfiguration erfolgt in 3 Ebenen:

1. **FlexForm** (Content-Element Backend) - höchste Priorität
2. **Site Settings** (`settings.yaml`)
3. **Extension Defaults** (`Configuration/Sets/Toc/config.yaml`) - niedrigste Priorität

**Beispiel:**
```
FlexForm: mode = "all"
Site Settings: defaultMode = "visibleHeaders"
Extension Default: defaultMode = "sectionIndexOnly"

→ Ergebnis: mode = "all" (FlexForm gewinnt)
```

**Wenn FlexForm leer:**
```
FlexForm: mode = "" (leer)
Site Settings: defaultMode = "visibleHeaders"
Extension Default: defaultMode = "sectionIndexOnly"

→ Ergebnis: mode = "visibleHeaders" (Site Settings gewinnt)
```

## 🛠️ Backend UI

Sobald das Site Set eingebunden ist, erscheinen die Settings im Backend:

**Site Management → [Site] → Settings → Table of Contents**

- ✅ Dropdown für Template-Auswahl
- ✅ Dropdown für Default Filter Mode
- ✅ Text-Feld für Excluded Columns
- ✅ Dropdown für Default Layout
- ✅ Checkboxen für Scrollspy/Sticky
- ✅ Zahlen-Feld für Max Depth

Alle Änderungen werden direkt in `config/sites/<site>/settings.yaml` gespeichert.

## 📝 Vorteile von Site Sets

✅ **Backend UI**: Konfiguration direkt im Backend, kein TypoScript nötig
✅ **Typ-Sicherheit**: YAML-Schema mit Validierung
✅ **Multi-Site**: Unterschiedliche Konfiguration pro Site
✅ **Versionierung**: settings.yaml ist Git-freundlich
✅ **Dokumentation**: `settings.definitions.yaml` dient als Doku
✅ **Flexibel**: FlexForm kann Site Settings überschreiben

## 🔗 TYPO3 Dokumentation

- [Site Sets](https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/SiteHandling/SiteSets.html)
- [Site Settings](https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/SiteHandling/Settings.html)

---

**Entwickelt für TYPO3 v13** 🚀
