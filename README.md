# Sulu Content Import/Export Bundle

Reusable JSON export/import admin tab for Sulu `page`, `snippet`, and optional `article` documents.

## Private Composer Usage

This repository is intended to be consumed from another Sulu application as a private Composer package through a VCS repository entry. The recommended host integration is:

- install the bundle through Composer
- import its admin routes and config
- add a webpack alias in the host admin build
- import `sulu-content-import-export-bundle/app` from the host `assets/admin/app.js`
- rebuild the host Sulu admin

## Status

The bundle currently provides:

- shared backend services for load/save/validate
- generic admin controller for page, snippet, and article
- Sulu admin tab registration
- locale switcher options taken from the Sulu localization system
- bundle frontend source for the export/import view
- unit tests for core helpers and requester

## Docs

- [Implementation plan](docs/implementation-plan.md)
- [Installation](docs/installation.md)
