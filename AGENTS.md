# AGENTS.md

This is a **WordPress plugin** (Connect CRM RealState) that imports real estate properties from CRM systems (Inmovilla, Anaconda) into WordPress custom post types.

## Running the plugin locally

Use [WordPress Playground CLI](https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli) to run WordPress with the plugin auto-mounted:

```bash
npx @wp-playground/cli@latest server --auto-mount --php=8.1 --login --port=9400
```

This starts WordPress at `http://127.0.0.1:9400` with the plugin already active. No Docker, MySQL, or Apache needed for manual testing — Playground uses SQLite internally.

## Development commands

All commands are defined in `composer.json` scripts section:

| Command | Purpose |
|---|---|
| `composer lint` | Run PHP_CodeSniffer (WordPress standards) |
| `composer format` | Auto-fix coding standard issues |
| `composer phpstan` | Run PHPStan static analysis (level 1) |
| `composer test` | Run PHPUnit tests (requires MySQL + WP test suite) |
| `composer test-install` | Install WordPress test suite + test database |

## PHPUnit test environment

PHPUnit integration tests require MariaDB/MySQL running with a `wordpress_test` database. Setup steps:

1. Start MariaDB: `sudo mysqld_safe &`
2. Install WP test suite: `composer test-install`
3. Run tests: `composer test`

**Known issue:** PHPUnit tests pass (all dots shown) but the process may hang during WordPress test teardown in this containerized environment. Use `timeout 120 vendor/bin/phpunit` to work around this.

## Project structure

```
connect-crm-realstate/
├── connect-crm-realstate.php     # Plugin entry point, constants, bootstrapping
├── includes/
│   ├── class-iip-admin.php       # Admin settings UI (tabs, AJAX handlers)
│   ├── class-helper-api.php      # API class: fetches/parses CRM data
│   ├── class-helper-sync.php     # Sync class: upserts WP posts from API data
│   ├── class-iip-import.php      # Import class: manual import UI and AJAX
│   ├── class-iip-post-type.php   # Registers ccrmre_property CPT + meta boxes
│   ├── class-property-info.php   # Shortcode/block: property info display
│   ├── class-gallery.php         # Shortcode: property photo gallery
│   ├── class-featured-image-url.php  # Fallback: render external image URL
│   └── apidata/
│       └── inmovillla-procesos.json  # Enum/field metadata from Inmovilla API
├── assets/                       # CSS/JS (no build step — plain files)
├── tests/
│   ├── Unit/                     # PHPUnit integration tests
│   │   ├── HelperApiTest.php
│   │   ├── ImportFilterPropertiesTest.php
│   │   ├── ImportStatsTest.php
│   │   ├── InmovillaEnumsTest.php
│   │   └── TaxonomyMappingTest.php
│   ├── Data/                     # Mock API response JSON fixtures
│   ├── bootstrap.php             # PHPUnit bootstrap
│   └── phpstan-bootstrap.php     # PHPStan bootstrap
├── docs/
│   ├── api-inmovilla.md          # Inmovilla APIWEB docs reference
│   └── api-inmovilla-procesos.md # Inmovilla API REST (procesos) docs reference
├── bin/
│   └── install-wp-tests.sh       # WordPress test suite installer
├── composer.json
├── phpstan.neon.dist
├── phpunit.xml.dist
└── .phpcs.xml.dist
```

## Key constants and options

| Constant / Option | Value / Key | Purpose |
|---|---|---|
| `CCRMRE_VERSION` | `1.2.5-beta.2` | Plugin version |
| `CCRMRE_POST_TYPE` | `ccrmre_property` | Default CPT slug |
| `ccrmre_settings` | WP option | CRM connection + display settings |
| `ccrmre_merge_fields` | WP option | Field mapping (CRM → WP meta) |
| `ccrmre_taxonomy_mappings` | WP option | CRM values → WP taxonomy terms |

Options were migrated from legacy prefix `conncrmreal_*` — see `ccrmre_migrate_options_to_prefix()` in the main plugin file.

## Architecture overview

```
Admin UI (class-iip-admin.php)
  └─ settings tabs: connection, merge fields, taxonomy mappings

Import flow:
  1. Import::manual_import() — triggered via AJAX from admin page
  2. API::get_all_property_ids() — fetches list from CRM (cached 30 min transient)
  3. API::get_property_detail() — fetches full property data
  4. SYNC::sync_property() — upserts WP post + meta fields
     ├─ applies filter: ccrmre_should_import_property (skip/include logic)
     ├─ maps CRM fields via merge fields settings
     ├─ maps CRM values via taxonomy mappings
     ├─ downloads and attaches featured image locally
     └─ stores gallery image URLs in meta

Frontend:
  - PropertyInfo — [ccrmre_property_info] shortcode
  - Gallery — [ccrmre_property_gallery] shortcode (or auto-inject via the_content)
  - Featured_Image_URL — fallback filter for external image URLs (legacy)
```

## Supported CRM types

| `crm_type` value | CRM | API style |
|---|---|---|
| `anaconda` | Anaconda | REST-like |
| `inmovilla` | Inmovilla APIWEB | XML-based web API |
| `inmovilla_procesos` | Inmovilla API REST | REST (procesos endpoint) |

The `crm_type` is stored in `ccrmre_settings['type']` and drives branching throughout `API` and `SYNC` classes.

## Coding standards

- **PHP 7.4+** minimum. Typed properties and arrow functions are allowed.
- **WordPress Coding Standards** enforced via `.phpcs.xml.dist`.
- **Namespace:** `Close\ConnectCRM\RealState\`
- **Prefix:** `ccrmre_` for options, transients, hooks, and constants.
- **Text domain:** `connect-crm-realstate`
- Tabs for indentation. Align `=` vertically within groups.
- Yoda conditions always (`null === $value`).
- No `mb_*` functions.
- Vanilla JS only — no jQuery.
- All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- Use early returns; avoid `else` after a `return`.
- Comments: one short line per logic block, capital letter, ends with period.

Run before committing:

```bash
composer lint      # must pass with zero errors
composer phpstan   # must pass at level 1
```

## Adding a new feature — checklist

1. Add PHP class in `includes/class-*.php` following existing class structure.
2. Require and instantiate it in the `plugins_loaded` callback in `connect-crm-realstate.php`.
3. Prefix all hooks and options with `ccrmre_`.
4. Enqueue scripts/styles via `wp_enqueue_scripts` or `admin_enqueue_scripts` — never inline.
5. Add PHPUnit test in `tests/Unit/`.
6. Run `composer lint && composer phpstan`.
7. Add a changelog entry under `### Unreleased` in `readme.txt` (format: `- Fixed/Added/Changed ... .`).
8. Documentation goes in `docs/` and must be listed in `.distignore` so it is excluded from WordPress.org releases.

## WordPress.org compliance rules

- Every feature must work completely without a license key.
- No trial periods, usage quotas, or artificial limits gated behind payment.
- Upselling to the Pro version must be informational and non-intrusive.
- Use feature detection (check if Pro addon is active) not feature restriction.
- Before submission: verify no locked code paths exist in the free plugin.
