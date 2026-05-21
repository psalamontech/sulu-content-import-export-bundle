# Sulu Content Import/Export Bundle v1 Plan

## Summary

Create a reusable Sulu bundle that exposes the existing JSON export/import admin feature for exactly three resource types: page, snippet, and optional article. v1 keeps the existing payload model and admin UX, but removes app-specific hardcoding and replaces it with bundle configuration and resource-specific adapters.

## Key Changes

- Build the bundle around page, snippet, and article only.
- Share the backend logic through common services for JSON decoding, document loading/saving, SEO extraction, and structure validation.
- Replace the three duplicated controllers with a generic content JSON controller backed by resource registry and permission checker registries.
- Keep a single admin React view and vary behaviour through route options.
- Expose bundle configuration for enabled resources, article types, and CSRF token id.
- Keep SEO support only for page and article.

## Public Interfaces

- Bundle config:
  - `sulu_content_import_export.csrf_token_id`
  - `sulu_content_import_export.resources.page.enabled`
  - `sulu_content_import_export.resources.snippet.enabled`
  - `sulu_content_import_export.resources.article.enabled`
  - `sulu_content_import_export.resources.article.types`
- Admin routes:
  - `GET /admin/content-json/{resource}/{id}`
  - `POST /admin/content-json/{resource}/{id}/validate`
  - `POST /admin/content-json/{resource}/{id}`
  - `POST /admin/content-json/{resource}/{id}/seo`

## Test Plan

- Unit tests for JSON decode, structure validation, SEO helper, and document save/load helper.
- Permission checker registry coverage for page, snippet, and article paths.
- Controller behaviour tests for load, validate, save, save SEO, invalid CSRF, and permission failures.
- Frontend requester test coverage for GET, protected POST, and friendly CSRF error handling.

## Assumptions

- v1 is a bundle extraction of the existing feature, not a UX redesign.
- Article integration stays optional and should no-op when the Sulu Article bundle is not present.
- Host Sulu applications remain responsible for wiring the bundle admin entrypoint into their admin build, preferably through a webpack alias instead of a raw relative import from `vendor/`.
