# TODO

## Fix: Translation key "Export / Import" not translated

**Error:** `The translation key "Export / Import" has not been translated. The key itself will be returned instead.` (Translator.js:31:9)

**Root cause:** String is hardcoded, no Sulu translation infrastructure in bundle.

**Files to change:**
- `src/Admin/ContentImportExportAdmin.php:65` — `->setOption('tabTitle', 'Export / Import')`
- `assets/admin/views/ExportImportView.js:1049` — `<span style={styles.title}>Export / Import</span>`

**Fix:** Add `Resources/translations/` directory, create translation domain (e.g. `SuluContentImportExportBundle`), register in bundle, replace hardcoded strings with translation keys.

---

## Fix: composer.json package name

**Current:** `"name": "psalamon/sulu-content-import-export-bundle"`
**Should be:** `"name": "psalamontech/sulu-content-import-export-bundle"`

**Files to change:**
- `composer.json` — `name` field
- `docs/installation.md` — all `psalamon/` references in require examples
- `README.md` — all `psalamon/` references

Note: vendor folder in host projects will be `vendor/psalamontech/` after rename.

---

## Fix: Installation guide — add cache clear step

At the end of `docs/installation.md` add:

```bash
bin/adminconsole cache:clear
```

Required after asset build to pick up new routes and config.

---

## Feature: Disable plugin via .env variable

Add support for disabling the bundle entirely through an environment variable (e.g. `SULU_CONTENT_IMPORT_EXPORT_ENABLED=false`).

**Files to change:**
- `src/DependencyInjection/Configuration.php` — add `enabled` config node
- `src/DependencyInjection/SuluContentImportExportExtension.php` — read env var, skip service registration if disabled
- `src/Admin/ContentImportExportAdmin.php` — skip admin tab registration if disabled
- `config/services.yaml` — gate service definitions behind the flag
- `docs/installation.md` — document the env variable

**Approach:** Read `$_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED']` in the extension `load()` method; if `false`, return early without registering any services, routes, or admin tabs.
