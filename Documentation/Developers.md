# Developer Guide: sitemetadata_extend

## Purpose

`EXT:sitemetadata_extend` is an **example extension** that demonstrates how to add custom
master-data fields to `EXT:sitemetadata` **without modifying the base extension**.

It uses two standard TYPO3/Symfony mechanisms:

| Mechanism | What it achieves |
|---|---|
| **Own Site Set** with `settings.definitions.yaml` | New fields appear in the Site Settings backend form |
| **Symfony Service Decoration** of `SiteMetadataDefinitions` | New fields appear automatically in the CKEditor dropdown and in frontend placeholder replacement |

Because both `SiteMetadataProcessor` (frontend) and `InjectSiteMetadataPlaceholdersListener`
(CKEditor) receive `SiteMetadataDefinitions` via constructor injection, decorating that single
service is enough to extend both pipelines simultaneously.

---

## Architecture

```
packages/sitemetadata_extend/
├── Classes/
│   └── Utility/
│       └── ExtendedSiteMetadataDefinitions.php   ← Decorator
├── Configuration/
│   ├── Services.yaml                              ← decorates: SiteMetadataDefinitions
│   └── Sets/SiteMetadataExtend/
│       ├── config.yaml                            ← Site Set metadata + dependency
│       └── settings.definitions.yaml             ← additional field definitions
├── Resources/Private/Language/
│   ├── locallang.xlf                              ← EN labels
│   └── de.locallang.xlf                          ← DE labels
├── composer.json
└── ext_emconf.php
```

---

## How the Decorator Works

`ExtendedSiteMetadataDefinitions` extends `SiteMetadataDefinitions` and overrides `getAll()`:

```php
public function getAll(string $filePath = ''): array
{
    $base  = parent::getAll($filePath);   // original fields from EXT:sitemetadata
    $extra = $this->loadOwnDefinitions(); // additional fields from this extension
    return array_merge($base, $extra);
}
```

`parent::getAll()` calls the original PHP implementation via class inheritance — no `.inner`
Symfony service injection is needed. This avoids a conflict with TYPO3's strict DI reference
check (`CheckExceptionOnInvalidReferenceBehaviorPass`).

The decorator is registered in `Configuration/Services.yaml`:

```yaml
porthd\sitemetadataextend\Utility\ExtendedSiteMetadataDefinitions:
  decorates: porthd\sitemetadata\Utility\SiteMetadataDefinitions
```

Symfony replaces the original `SiteMetadataDefinitions` service with this decorator in the DI
container. Both consumers of that service automatically receive the extended implementation.

---

## Data Flow

### Backend — CKEditor dropdown

```
AfterPrepareConfigurationForEditorEvent
  → InjectSiteMetadataPlaceholdersListener (EXT:sitemetadata)
  → SiteMetadataDefinitions::getAll()           ← resolves to decorator
      → parent::getAll()                         ← base fields
      → loadOwnDefinitions()                     ← additional fields
  → CKEditor config: siteMetadataPlaceholders = [...all fields...]
```

### Frontend — placeholder replacement

```
lib.parseFunc_RTE → nonTypoTagStdWrap.postUserFunc
  → SiteMetadataProcessor (EXT:sitemetadata)
  → SiteMetadataDefinitions::getAll()           ← resolves to decorator
      → parent::getAll()                         ← base fields
      → loadOwnDefinitions()                     ← additional fields
  → str_replace([[sitemetadata.xxx]], $value, $content)
```

---

## Adding Fields

All field definitions live in one place:

```
Configuration/Sets/SiteMetadataExtend/settings.definitions.yaml
```

Add a new entry following the `sitemetadata` key prefix convention:

```yaml
settings:
  sitemetadataMyNewField:
    type: string
    default: ''
    label: 'LLL:EXT:sitemetadata_extend/Resources/Private/Language/locallang.xlf:setting.myNewField'
```

Add the corresponding label to `locallang.xlf`:

```xml
<trans-unit id="setting.myNewField">
    <source>My New Field</source>
</trans-unit>
```

Then flush the cache — the field appears automatically in:
- the Site Settings backend form,
- the CKEditor placeholder dropdown (as `[[sitemetadata.myNewField]]`),
- the frontend placeholder replacement.

### Key Convention

Setting keys **must start with `sitemetadata`** (camelCase). The base extension's
`SiteMetadataDefinitions::keyToPlaceholder()` method handles the conversion:

```
sitemetadataLinkedIn  →  [[sitemetadata.linkedIn]]
sitemetadataVatId     →  [[sitemetadata.vatId]]
```

---

## Site Set Dependency

`Configuration/Sets/SiteMetadataExtend/config.yaml` declares `porthd/sitemetadata` as a
dependency:

```yaml
dependencies:
  - porthd/sitemetadata
```

This ensures TYPO3's Dependency Ordering Service loads the base set before this one.
The Site Set must also be explicitly activated per site in the site configuration
(see the Integration section in `EXT:sitemetadata`'s documentation).

---

## Extending This Pattern Further

This extension demonstrates the minimal pattern. Further possibilities within the same approach:

- **Additional YAML files**: `loadOwnDefinitions()` can be extended to merge definitions from
  multiple YAML files — useful for splitting field groups across feature sets.
- **Dynamic field sources**: Instead of reading a static YAML file, `loadOwnDefinitions()` can
  return fields from any source (database, remote config, etc.) as long as it returns
  `array<string, string>` with `sitemetadata`-prefixed keys.
- **Multiple decorators**: Additional extensions can each decorate the already-decorated service.
  Symfony chains decorators automatically.
