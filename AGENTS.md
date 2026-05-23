# Agent Guide

Context for AI agents working on this repository.

## What this is

A Symfony bundle that adds a JSON export/import admin tab to Sulu CMS documents (pages, snippets, articles). The bundle is distributed as a private Composer VCS package consumed by host Sulu applications.

## Repository layout

```
src/
  Admin/                  # Backend helpers: JSON decode, document load/save, SEO, validation
  Controller/Admin/       # Single generic controller for all resource types
  DependencyInjection/    # Extension (loads config, prepends CSRF), Configuration tree
  Resource/               # ResourceRegistry + ResourceDefinition (config → runtime shape)
  Security/               # PermissionChecker per resource type, collected by registry
  SuluContentImportExportBundle.php  # getPath() override — must point to package root

config/
  routes_admin.yaml       # Attribute-based routing from Controller/Admin/
  services.yaml           # Autowire/autoconfigure, permission checker tags

assets/admin/
  app.js                  # Entrypoint: registers ExportImportView in Sulu admin
  views/ExportImportView.js  # Single React view, behaviour driven by route options
  services/exportImportRequester.js  # GET + CSRF-protected POST helpers

Resources/translations/
  admin.en.json           # Sulu admin translation keys for bundle UI
  admin.de.json           # German admin translations

tests/
  Admin/                  # Unit tests for helpers
  Controller/Admin/       # Integration test for ContentJsonController
  DependencyInjection/    # Extension prepend + load tests
  Security/               # PermissionCheckerRegistry test
  SuluContentImportExportBundleTest.php  # getPath() + routes_admin.yaml/translations existence
```

## Running tests

```bash
composer install
./vendor/bin/phpunit
```

Expected: 30 tests, 140 assertions, no PHPUnit deprecations.

No database or running Sulu instance needed. The controller test uses in-memory mocks.

## Key design decisions

- **One controller for all resources.** `ContentJsonController` dispatches via `ResourceRegistry`, which is built from bundle config in `SuluContentImportExportExtension::buildResourceConfig()`. Do not add per-resource controllers.
- **One React view for all resources.** `ExportImportView` reads `route.options` (e.g. `urlPrefix`, `hasSeo`, `saveLabel`) set by `ContentImportExportAdmin`. Do not fork the view per resource.
- **CSRF is auto-configured.** The extension implements `PrependExtensionInterface` and prepends `framework.csrf_protection.stateless_token_ids` with the bundle's token id. Host projects do not need a manual CSRF yaml.
- **Bundle can be disabled globally.** `SULU_CONTENT_IMPORT_EXPORT_ENABLED=false` disables the bundle at container build time: no CSRF prepend, no service registration, no admin tab. Keep any future feature behind this global switch as well.
- **Article is conditional.** `class_exists(ArticleAdmin::class)` gates article support at container build time. Any article-related code must stay behind this check.
- **`getPath()` override is intentional.** Symfony's default path resolution breaks for bundles installed via Composer VCS. The override in `SuluContentImportExportBundle::getPath()` returns `dirname(__DIR__)` (the package root). Do not remove it.
- **No host node_modules in vendor.** Bundle ships JS source only. Host compiles. The install flow injects an `applySuluContentImportExportConfig(config)` helper at top level in the host `webpack.config.js` and calls it from inside `module.exports`; that helper owns the bundle alias plus `resolve.modules` wiring so `sulu-admin-bundle/*` imports resolve to the host's `node_modules`.
- **Admin labels must use Sulu translations.** Do not hardcode visible admin UI strings like the Export/Import tab title; use translation keys backed by `Resources/translations/admin.*.json`.

## What to avoid

- Do not add per-resource duplication (controllers, views, services). The entire point of this bundle is the generic registry pattern.
- Do not remove the `jackalope/jackalope-doctrine-dbal` dev dependency. It is a Composer graph shim required to resolve `sulu/sulu`'s dependency tree during `composer install`.
- Do not change the bundle config shape without updating `Configuration.php`, `SuluContentImportExportExtension::buildResourceConfig()`, and docs.
- Do not hardcode locales. The locale list comes from Sulu's webspace configuration at runtime.
- Do not reintroduce deprecated PHPUnit mock APIs such as `MockBuilder::addMethods()`. Keep the suite clean on PHPUnit 11/12.

## Distribution

Private GitHub VCS repository. Host projects add a `repositories` entry pointing to this repo, then `composer require psalamontech/sulu-content-import-export-bundle:^0.1.0`.

Releases use semver tags (`0.1.0`, `0.1.1`, ...). Do not recommend `dev-main` as the primary install path.

After host-side admin asset rebuild, document and expect `bin/adminconsole cache:clear` so Sulu picks up new routes and config.
