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
        "psalamon/sulu-content-import-export-bundle": "dev-main"
    }
}
```

## Backend

1. Require the bundle through Composer.
2. If Symfony Flex does not register the bundle automatically, add `SuluContentImportExportBundle\\SuluContentImportExportBundle` to the host application's bundle config.
3. Import the admin routes:

```yaml
# config/routes/sulu_content_import_export.yaml
sulu_content_import_export:
    resource: '@SuluContentImportExportBundle/config/routes_admin.yaml'
```

The bundle exposes `config/routes_admin.yaml` from the package root, so this import works without copying route files into the host application.

4. Enable stateless CSRF checking for the configured token id:

```yaml
# config/packages/csrf.yaml
framework:
    csrf_protection:
        stateless_token_ids:
            - sulu_content_import_export
        check_header: true
```

5. Configure the bundle:

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

The language switcher uses the locales registered in the Sulu system, not a separate bundle-specific locale list.

## Admin Frontend

Add a webpack alias in the host application's `assets/admin/webpack.config.js`:

```js
const path = require('path');
const webpackConfig = require('../../vendor/sulu/sulu/webpack.config.js');

module.exports = (env, argv) => {
    env = env ? env : {};
    argv = argv ? argv : {};

    env.project_root_path = path.resolve(__dirname, '..', '..');
    env.node_modules_path = path.resolve(__dirname, 'node_modules');

    const config = webpackConfig(env, argv);
    config.entry = path.resolve(__dirname, 'index.js');
    config.resolve = config.resolve || {};
    config.resolve.alias = {
        ...(config.resolve.alias || {}),
        'sulu-content-import-export-bundle': path.resolve(
            __dirname,
            '../../vendor/psalamon/sulu-content-import-export-bundle/assets/admin'
        ),
    };

    return config;
};
```

Then import the bundle admin entrypoint from the host application's `assets/admin/app.js`:

```js
import 'sulu-content-import-export-bundle/app';
```

This keeps the host application import stable and avoids hardcoding a relative path into `vendor/`.

After that, rebuild the host application's Sulu admin assets with its normal build command.

The bundle ships source admin assets only. The host Sulu application remains responsible for compiling them as part of its own admin build.
