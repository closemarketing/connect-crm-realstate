# AGENTS.md

This is a **WordPress plugin** (Connect CRM RealState) that imports real estate properties from CRM systems (Inmovilla APIWEB, Inmovilla Procesos REST, Anaconda) into WordPress custom post types.

- **Plugin slug:** `connect-crm-realstate`
- **Namespace:** `Close\ConnectCRM\RealState\`
- **Prefix:** `ccrmre_` for all hooks, options, transients, constants, and meta keys
- **Requires:** WordPress 5.8+, PHP 7.4+
- **License:** GPL-2.0-or-later

## Running the plugin locally

Use [WordPress Playground CLI](https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli) to run WordPress with the plugin auto-mounted:

```bash
npx @wp-playground/cli@latest server --auto-mount --php=8.1 --login --port=9400
```

This starts WordPress at `http://127.0.0.1:9400` with the plugin already active. No Docker, MySQL, or Apache needed — Playground uses SQLite internally.

## Development commands

All commands are defined in `composer.json` scripts section:

| Command | Purpose |
|---|---|
| `composer lint` | Run PHP_CodeSniffer (WordPress standards) |
| `composer format` | Auto-fix coding standard issues |
| `composer phpstan` | Run PHPStan static analysis (level 1) |
| `composer test` | Run PHPUnit tests (requires MySQL + WP test suite) |
| `composer test-install` | Install WordPress test suite + test database |

Run before every commit:

```bash
composer lint      # must pass with zero errors
composer phpstan   # must pass at level 1
```

## PHPUnit test environment

PHPUnit integration tests require MariaDB/MySQL running with a `wordpress_test` database:

1. Start MariaDB: `sudo mysqld_safe &`
2. Install WP test suite: `composer test-install`
3. Run tests: `composer test`

**Known issue:** Tests pass but the process may hang during WordPress test teardown. Use `timeout 120 vendor/bin/phpunit` to work around this.

## Project structure

```
connect-crm-realstate/
├── connect-crm-realstate.php     # Plugin entry point — defines constants, migration, bootstrapping
├── includes/
│   ├── class-iip-admin.php       # Admin settings UI (tabs, AJAX handlers)
│   ├── class-helper-api.php      # API class: fetches/parses CRM data from all three CRMs
│   ├── class-helper-sync.php     # Sync class: upserts WP posts from API data
│   ├── class-iip-import.php      # Import class: manual import UI and AJAX
│   ├── class-iip-post-type.php   # Registers ccrmre_property CPT + meta boxes
│   ├── class-property-info.php   # Shortcode/block: property info display
│   ├── class-gallery.php         # Shortcode: property photo gallery
│   ├── class-featured-image-url.php  # Fallback: render external image URL
│   └── apidata/
│       └── inmovillla-procesos.json  # Enum/field label metadata for Inmovilla Procesos API
├── assets/                       # CSS/JS (no build step — plain vanilla JS/CSS files)
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
| `CCRMRE_PLUGIN` | `__FILE__` | Absolute path to main plugin file |
| `CCRMRE_PLUGIN_URL` | `plugin_dir_url()` | Plugin URL |
| `CCRMRE_PLUGIN_PATH` | `plugin_dir_path()` | Plugin filesystem path |
| `CCRMRE_POST_TYPE` | `ccrmre_property` | Default CPT slug |
| `CCRMRE_PRO_PLUGIN_URL_WEB` | URL | Link to PRO add-on product page |
| `ccrmre_settings` | WP option | CRM connection + display settings |
| `ccrmre_merge_fields` | WP option | Field mapping (CRM field → WP meta key) |
| `ccrmre_taxonomy_mappings` | WP option | CRM field values → WP taxonomy terms |
| `ccrmre_options_migrated` | WP option | One-time migration flag from legacy `conncrmreal_*` prefix |

## Supported CRM types

| `crm_type` value | CRM | API style | Notes |
|---|---|---|---|
| `anaconda` | Anaconda | REST JSON (`api.anaconda.guru/api/v1/`) | Bearer token auth; pagination 200/page |
| `inmovilla` | Inmovilla APIWEB | Encoded POST params, JSON response (`apiweb.inmovilla.com`) | Requires server IP whitelisted; uses cookie jar; pagination 50/page |
| `inmovilla_procesos` | Inmovilla API REST | REST JSON (`procesos.inmovilla.com/api/v1/`) | `Token` header auth; returns all properties in one call |

The `crm_type` is stored in `ccrmre_settings['type']` and drives branching throughout `API` and `SYNC` classes.

## Architecture overview

```
Plugin bootstrap (connect-crm-realstate.php)
  └─ plugins_loaded (priority 1): ccrmre_migrate_options_to_prefix()
  └─ plugins_loaded (priority 5): load autoloader, require includes, instantiate:
       new Admin()   → settings UI
       new Import()  → manual import AJAX
       new PostType() → CPT registration

Admin UI (class-iip-admin.php)
  └─ Settings tabs: connection, merge fields, taxonomy mappings, import, stats

Import flow (triggered via AJAX wp_ajax_ccrmre_manual_import):
  1. Import::manual_import()          — paginates over all properties per AJAX call
  2. API::get_all_property_ids()      — fetches listing, cached 30 min transient
  3. API::get_property()              — fetches full property data per item
  4. SYNC::is_property_available()    — checks availability (nodisponible / estado / status)
     ├─ if unavailable → SYNC::handle_unavailable_property() → draft/trash/keep per settings
  5. SYNC::sync_property()            — upserts WP post + meta fields
     ├─ apply_filters('ccrmre_should_import_property', true, $item)  ← PRO hook to skip
     ├─ API::get_property_info()      — extracts id, reference, status, last_updated
     ├─ maps CRM fields via ccrmre_merge_fields settings (or raw prefix if not configured)
     ├─ SYNC::assign_taxonomy_terms() — maps CRM values to WP taxonomy terms (auto-creates)
     ├─ SYNC::format_item_meta()      — resolves enum codes to human labels (key_loca, key_tipo…)
     ├─ SYNC::process_description()   — converts ~ separators + **bold** to Gutenberg blocks
     ├─ downloads featured image locally (if settings['download_images'] = 'featured'|'all')
     └─ stores gallery URLs in meta ccrmre_gallery_urls (+ attachment IDs if download = 'all')

Frontend:
  - PropertyInfo  — [ccrmre_property_info] shortcode
  - Gallery       — [ccrmre_property_gallery] shortcode (or auto-inject via the_content)
  - Featured_Image_URL — fallback filter for external image URLs (legacy, no local download)
```

## Fixed meta keys written per property post

| Meta key | Content |
|---|---|
| `ccrmre_property_id` | CRM internal ID (used to match posts on re-sync) |
| `ccrmre_reference` | Human-readable reference (ref field) |
| `ccrmre_status` | Availability boolean from CRM |
| `ccrmre_last_updated` | Last modification timestamp from CRM |
| `ccrmre_gallery_urls` | Array of external photo URLs |
| `ccrmre_gallery_attachment_ids` | Array of local WP attachment IDs (when images downloaded) |
| `ccrmre_featured_image_url` | URL of the first photo (used to skip re-download if unchanged) |
| `property_synced` | Boolean flag set after every successful sync |

## API class details (`class-helper-api.php`)

- All methods are `static`.
- `API::get_all_property_ids($crm_type, $with_metadata)` — returns listing with `last_updated`, `status`, `state_code` per property; cached 30 min via `ccrmre_query_property_ids_*` transient.
- `API::get_property($item, $crm)` — fetches full detail; Anaconda returns listing item as-is; Inmovilla calls `ficha` endpoint and merges `descripciones`, `fotos`, `videos`; Inmovilla Procesos calls `propiedades/?cod_ofer=`.
- `API::get_property_info($property, $crm_type, $prefix)` — normalizes CRM field names to `id`, `reference`, `status`, `last_updated`, `state_code`. For Inmovilla, `nodisponible=1` means NOT available (logic inverted).
- `API::get_enums($crm_type, $key)` — resolves numeric codes (key_tipo, key_loca, key_zona) to labels; cached 3 days. For `key_loca`, returns `['city', 'state']` arrays.
- `API::execute_with_retry($callback, $api_name)` — wraps all requests with up to 3 retries; waits 30 s on timeout, 300 s on rate limit, 120 s on server error. Skips sleep when `set_skip_retry(true)` (manual import context).
- **Inmovilla IP registration:** Inmovilla APIWEB requires the server IP to be whitelisted. If it isn't, the API returns a plain-text `NECESITAMOS RECIBIR LA IP` response. The plugin detects this and surfaces a human-readable error with the server IP. Server public IP is cached 24 h in `ccrmre_server_public_ip` option.
- **Inmovilla cookies:** Session cookies from Inmovilla APIWEB are persisted in `{temp_dir}/ccrmre-inmovilla-cookies.txt` and sent on subsequent requests.

## SYNC class details (`class-helper-sync.php`)

- All methods are `static`.
- `SYNC::sync_property($item, $settings, $settings_fields)` — main upsert; calls `wp_insert_post` for new, `wp_update_post` for existing (matched via `ccrmre_property_id` meta).
- `SYNC::handle_unavailable_property()` — applies `settings['sold_action']`: `draft` → set to draft, `trash` → wp_trash_post, `keep` → leave published.
- `SYNC::remove_properties_not_in_api($crm_type)` — trashes WP posts whose `ccrmre_property_id` no longer appears in the CRM listing.
- `SYNC::filter_properties_to_update()` — skips properties where `ccrmre_last_updated` matches the API date (avoids unnecessary re-imports).
- `SYNC::assign_taxonomy_terms()` — creates terms automatically if they don't exist; supports comma-separated multi-value fields.
- `SYNC::process_description()` — splits on `~`, converts `**text**` → `<strong>`, wraps each line in Gutenberg `<!-- wp:paragraph -->` blocks.
- `SYNC::download_and_set_featured_image()` — skips re-download if URL unchanged and attachment still valid; deletes old attachment on URL change.
- `SYNC::download_gallery_images()` — per-index URL comparison; deletes leftover attachments when gallery shrinks.

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

## Adding a new feature — checklist

1. Add PHP class in `includes/class-*.php` following existing class structure.
2. Require and instantiate it in the `plugins_loaded` callback in `connect-crm-realstate.php`.
3. Prefix all hooks, options, and constants with `ccrmre_`.
4. Enqueue scripts/styles via `wp_enqueue_scripts` or `admin_enqueue_scripts` — never inline.
5. Add PHPUnit test in `tests/Unit/`.
6. Run `composer lint && composer phpstan`.
7. Add a changelog entry under `### Unreleased` in `readme.txt` (format: `- Fixed/Added/Changed ... .`).
8. Documentation goes in `docs/` and must be listed in `.distignore` so it is excluded from WordPress.org releases.

## Adding a new CRM type

1. Add a branch in `API::request_*`, `API::get_properties()`, `API::get_property()`, and `API::get_property_info()`.
2. Add the new key to the `$match` array in `API::get_property_info()` with `id`, `reference`, `status`, `last_updated`, `state_code` field names.
3. Add pagination size in `API::get_pagination_size()` and timeout/config in `API::get_api_config()`.
4. Add a branch in `SYNC::get_property_content()` for title/description/city extraction.
5. Add a branch in `SYNC::is_property_available()` and `SYNC::get_unavailable_reason()`.

## WordPress.org compliance rules

- Every feature must work completely without a license key.
- No trial periods, usage quotas, or artificial limits gated behind payment.
- Upselling to the PRO version must be informational and non-intrusive.
- Use feature detection (check if PRO addon is active via `apply_filters`) not feature restriction.
- Before submission: verify no locked code paths exist in the free plugin.
- PRO features: background cron sync, postal code / province filtering, WP-CLI support.
