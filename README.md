## Vanilla CMS

A minimal, framework-free, PHP library for defining content types, storing their data, routing requests to pages, and editing content through a small admin panel.

### What it does

- **Page types** — define pages as PHP classes with typed fields (text, bool, date, composite, repeater). A page type can be a single page (e.g. a home page) or an archetype with multiple instances (e.g. products), each identified by a slug.
- **Storage** — page data is persisted through a storage driver (JSON file storage included) and read back into page instances.
- **Routing** — a simple router dispatches request paths to handlers; default dispatchers are generated automatically from registered page types, plus a route for the admin panel.
- **Admin panel** — a lightweight built-in UI for listing, creating, and editing page instances, with basic auth and CSRF protection.

### Requirements

- PHP >= 8.1

### Installation

```bash
composer require xylo-is-coding/vanilla-cms
```
