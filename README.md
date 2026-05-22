# Sulu Content Import/Export Bundle

JSON export/import admin tab for Sulu `page`, `snippet`, and optional `article` documents.

Adds an **Export / Import** tab to each content document in the Sulu admin. The tab shows the document's JSON payload, lets you edit and validate it, and saves it back as a draft.

## Requirements

- PHP 8.2+
- Sulu 2.6+
- Symfony 6.4 or 7.0

## Installation

See [docs/installation.md](docs/installation.md) for the full step-by-step guide.

Quick summary:

```json
// composer.json in host project
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:your-org/sulu-content-import-export-bundle.git" }
    ],
    "require": {
        "psalamon/sulu-content-import-export-bundle": "^0.1.0"
    }
}
```

After `composer require`:

```bash
bin/console sulu-content-import-export:install
cd assets/admin && npm run build
```

The install command creates the routes file, patches `webpack.config.js`, and adds the JS import automatically.

CSRF protection is configured automatically — no manual `config/packages/csrf.yaml` needed.

## Configuration

All resources are enabled by default. Override only when needed:

```yaml
# config/packages/sulu_content_import_export.yaml
sulu_content_import_export:
    resources:
        page:
            enabled: true
        snippet:
            enabled: true
        article:
            enabled: true
            types: ['article', 'post']
```

Article support activates only when `sulu/article-bundle` is installed.

## API endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/admin/content-json/{resource}/{id}` | Load document JSON |
| `POST` | `/admin/content-json/{resource}/{id}/validate` | Validate structure |
| `POST` | `/admin/content-json/{resource}/{id}` | Save content as draft |
| `POST` | `/admin/content-json/{resource}/{id}/seo` | Save SEO fields as draft |

`{resource}` is one of `page`, `snippet`, `article`.

## Development

```bash
composer install
./vendor/bin/phpunit
```

Tests: 28 / Assertions: 131.

## Docs

- [Installation guide](docs/installation.md)
- [Implementation plan](docs/implementation-plan.md)
