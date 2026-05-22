# Installation

## Private GitHub Repository

Add the repository and package requirement to the host application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:your-org/sulu-content-import-export-bundle.git"
        }
    ],
    "require": {
        "psalamon/sulu-content-import-export-bundle": "^0.1.0"
    }
}
```

If no tagged release exists yet, use `"dev-main"` as the version constraint.

## Install command

After `composer require`, run the install command from the host application root:

```bash
bin/console sulu-content-import-export:install
```

This creates `config/routes/sulu_content_import_export.yaml`, patches `assets/admin/webpack.config.js` with the required alias, and adds the JS import to `assets/admin/app.js` (or `index.js`). The command is idempotent — safe to run multiple times.

Then rebuild the admin assets:

```bash
cd assets/admin && npm run build
```

Do not use `bin/adminconsole sulu:admin:update-build` — it prompts to overwrite your customised `webpack.config.js` and `app.js`, and fails with `npm: not found` if you decline.

## Manual setup (alternative)

If Symfony Flex does not register the bundle automatically, add `SuluContentImportExportBundle\\SuluContentImportExportBundle` to the host application's bundle config.

Create `config/routes/sulu_content_import_export.yaml`:

```yaml
sulu_content_import_export:
    resource: '@SuluContentImportExportBundle/config/routes_admin.yaml'
```

Add a webpack alias and module resolution path in `assets/admin/webpack.config.js` before `return config;`:

```js
config.resolve = config.resolve || {};
config.resolve.alias = {
    ...(config.resolve.alias || {}),
    'sulu-content-import-export-bundle': path.resolve(
        __dirname,
        '../../vendor/psalamon/sulu-content-import-export-bundle/assets/admin'
    ),
};
config.resolve.modules = [
    path.resolve(__dirname, 'node_modules'),
    ...(config.resolve.modules || ['node_modules']),
];
```

The `resolve.modules` entry ensures that `sulu-admin-bundle` imports inside the bundle's vendor files resolve to the host's `node_modules` directory.

Add the import to `assets/admin/app.js`:

```js
import 'sulu-content-import-export-bundle/app';
```

The bundle automatically configures stateless CSRF protection via `prepend()`. No manual CSRF config is needed.

## Optional — override defaults

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

All resources are enabled by default. Omit this file entirely if the defaults are acceptable.

The language switcher uses the locales registered in the Sulu system, not a separate bundle-specific locale list.
