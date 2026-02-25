# AGENTS.md

## Cursor Cloud specific instructions

This is a **WordPress plugin** (Connect CRM RealState) that imports real estate properties from CRM systems (Inmovilla, Anaconda) into WordPress custom post types.

### Running the plugin locally

Use [WordPress Playground CLI](https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli) to run WordPress with the plugin auto-mounted:

```bash
npx @wp-playground/cli@latest server --auto-mount --php=8.1 --login --port=9400
```

This starts WordPress at `http://127.0.0.1:9400` with the plugin already active. No Docker, MySQL, or Apache needed for manual testing — Playground uses SQLite internally.

### Development commands

All commands are defined in `composer.json` scripts section:

| Command | Purpose |
|---|---|
| `composer lint` | Run PHP_CodeSniffer (WordPress standards) |
| `composer format` | Auto-fix coding standard issues |
| `composer phpstan` | Run PHPStan static analysis (level 1) |
| `composer test` | Run PHPUnit tests (requires MySQL + WP test suite) |
| `composer test-install` | Install WordPress test suite + test database |

### PHPUnit test environment

PHPUnit integration tests require MariaDB/MySQL running with a `wordpress_test` database. Setup steps:

1. Start MariaDB: `sudo mysqld_safe &`
2. Install WP test suite: `composer test-install`
3. Run tests: `composer test`

**Known issue:** PHPUnit tests pass (all dots shown) but the process may hang during WordPress test teardown in this containerized environment. Use `timeout 120 vendor/bin/phpunit` to work around this.

### Project structure

- `includes/` — Plugin PHP classes (API helpers, sync logic, admin UI, post type registration)
- `tests/Unit/` — PHPUnit integration tests
- `tests/Data/` — Mock API response JSON files for tests
- `assets/` — CSS/JS assets (no build step needed)
- `bin/install-wp-tests.sh` — WordPress test suite installer
