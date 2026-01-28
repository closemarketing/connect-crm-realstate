# Assets Structure

## Overview

All JavaScript and CSS files are properly enqueued following WordPress best practices. No inline scripts or styles are injected directly into PHP files.

## Directory Structure

```
includes/assets/
├── iip-styles-admin.css      # Global admin styles
├── iip-settings.js            # Settings page functionality
├── iip-merge-fields.css       # Merge fields page styles
└── iip-merge-fields.js        # Merge fields page functionality
```

## File Descriptions

### Global Admin Styles

**File:** `iip-styles-admin.css`

**Purpose:** General admin interface styles

**Loaded:** On all plugin admin pages

**Enqueued in:** `class-iip-admin.php` → `plugin_settings()`

```php
wp_register_style(
    'iip_admin-styles',
    plugin_dir_url( __FILE__ ) . 'assets/iip-styles-admin.css',
    array(),
    CCRMRE_VERSION
);
wp_enqueue_style( 'iip_admin-styles' );
```

---

### Settings Page Script

**File:** `iip-settings.js`

**Purpose:** Dynamic field visibility on settings page (Inmovilla-specific fields)

**Loaded:** Only on Settings tab (`iip-settings`)

**Functionality:**
- Shows/hides Agency Number field based on CRM type selection
- Toggles on page load and on type change

**Enqueued in:** `class-iip-admin.php` → `enqueue_admin_scripts()`

```php
if ( 'iip-settings' === $active_tab && cccrmre_is_license_active() ) {
    wp_enqueue_script(
        'ccrmre-settings',
        plugin_dir_url( __FILE__ ) . 'assets/iip-settings.js',
        array( 'jquery' ),
        CCRMRE_VERSION,
        true
    );
}
```

---

### Merge Fields Styles

**File:** `iip-merge-fields.css`

**Purpose:** Styles for merge variables interface

**Loaded:** Only on Merge tab (`iip-merge`)

**Features:**
- Table layout for field mappings
- Select2 integration styles
- Auto-map button styling
- Loading animations

**Enqueued in:** `class-iip-admin.php` → `enqueue_admin_scripts()`

```php
if ( 'iip-merge' === $active_tab && cccrmre_is_license_active() ) {
    wp_enqueue_style(
        'ccrmre-merge-fields',
        plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.css',
        array(),
        CCRMRE_VERSION
    );
}
```

---

### Merge Fields Script

**File:** `iip-merge-fields.js`

**Purpose:** Interactive functionality for merge variables

**Loaded:** Only on Merge tab (`iip-merge`)

**Functionality:**
- Select2 initialization with custom config
- Auto-map AJAX handler
- Field creation and mapping
- Success/error notifications

**Dependencies:**
- jQuery
- Select2 (`ccrmre-select2`)

**Localized Data:**
```javascript
ccrmreMergeFields = {
    searchPlaceholder: 'Search or create WordPress field...',
    newFieldLabel: '(New field)',
    ajaxUrl: 'admin-ajax.php',
    nonce: 'generated_nonce',
    // ... other translations
}
```

**Enqueued in:** `class-iip-admin.php` → `enqueue_admin_scripts()`

```php
if ( 'iip-merge' === $active_tab && cccrmre_is_license_active() ) {
    wp_enqueue_script(
        'ccrmre-merge-fields',
        plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.js',
        array( 'jquery', 'ccrmre-select2' ),
        CCRMRE_VERSION,
        true
    );

    wp_localize_script(
        'ccrmre-merge-fields',
        'ccrmreMergeFields',
        array(
            'searchPlaceholder' => __( 'Search or create WordPress field...', 'connect-crm-realstate' ),
            // ... other translations
        )
    );
}
```

---

## External Dependencies

### Select2

**Source:** Composer package (`select2/select2`)

**Location:** `vendor/select2/select2/dist/`

**Files Used:**
- `css/select2.min.css`
- `js/select2.min.js`
- `js/i18n/es.js` (Spanish translation)

**Enqueued:** Only on Merge tab

```php
wp_enqueue_style(
    'ccrmre-select2',
    CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/css/select2.min.css',
    array(),
    '4.0.13'
);

wp_enqueue_script(
    'ccrmre-select2',
    CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/select2.min.js',
    array( 'jquery' ),
    '4.0.13',
    true
);
```

See: [Select2 Local Setup](select2-local-setup.md)

---

## Loading Strategy

### Conditional Loading by Tab

Assets are loaded conditionally based on the active admin tab:

| Tab | CSS | JavaScript |
|-----|-----|------------|
| All tabs | `iip-styles-admin.css` | - |
| Settings | - | `iip-settings.js` |
| Merge | `iip-merge-fields.css`<br>`select2.min.css` | `iip-merge-fields.js`<br>`select2.min.js`<br>`select2/i18n/es.js` |

### Performance Benefits

1. **Reduced Page Weight**: Only necessary assets load per page
2. **Faster Load Times**: Fewer HTTP requests
3. **Better UX**: No unnecessary resource loading
4. **Maintainability**: Clear separation of concerns

---

## Enqueue Method

All assets are enqueued through the `enqueue_admin_scripts()` method:

```php
/**
 * Enqueue admin scripts and styles
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
public function enqueue_admin_scripts( $hook ) {
    // Only load on our plugin page
    if ( 'toplevel_page_iip-options' !== $hook ) {
        return;
    }

    $active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

    // Tab-specific asset loading
    if ( 'iip-settings' === $active_tab && cccrmre_is_license_active() ) {
        // Load settings scripts
    }

    if ( 'iip-merge' === $active_tab && cccrmre_is_license_active() ) {
        // Load merge fields scripts and styles
    }
}
```

**Hooked in:** `admin_enqueue_scripts` action

```php
add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
```

---

## Best Practices Followed

### ✅ Do's

1. **Separate files for CSS/JS** - No inline code
2. **Conditional loading** - Only load when needed
3. **Dependency management** - Declare script dependencies
4. **Version control** - Use plugin version for cache busting
5. **Minification** - Use minified versions in production
6. **Localization** - Use `wp_localize_script()` for translations
7. **Standards compliance** - Follow WordPress Coding Standards

### ❌ Don'ts

1. ~~**Inline scripts/styles**~~ - Use separate files
2. ~~**Global loading**~~ - Load conditionally per tab
3. ~~**Missing dependencies**~~ - Always declare dependencies
4. ~~**Hardcoded URLs**~~ - Use `plugin_dir_url()` or constants
5. ~~**Direct `<script>` tags**~~ - Use `wp_enqueue_script()`
6. ~~**Direct `<style>` tags**~~ - Use `wp_enqueue_style()`
7. ~~**External CDNs**~~ - Use local assets via Composer

---

## File Naming Conventions

All asset files follow consistent naming:

- **Prefix:** `iip-` (plugin identifier)
- **Purpose:** Descriptive name (e.g., `settings`, `merge-fields`)
- **Extension:** `.js` or `.css`

**Examples:**
- ✅ `iip-settings.js`
- ✅ `iip-merge-fields.css`
- ❌ `settings-script.js`
- ❌ `merge.css`

---

## Versioning

All assets use the plugin version constant for cache busting:

```php
CCRMRE_VERSION  // Defined in main plugin file
```

**Benefits:**
- Automatic cache invalidation on plugin update
- No manual version management needed
- Consistent versioning across all assets

---

## Debugging

### Development Mode

To debug asset loading:

1. Enable `WP_DEBUG` in `wp-config.php`
2. Check browser console for JavaScript errors
3. Use browser Network tab to verify file loading
4. Check for 404 errors on asset files

### Common Issues

**Scripts not loading:**
- Check file path is correct
- Verify hook is firing (`toplevel_page_iip-options`)
- Ensure tab detection is working
- Check license is active

**Select2 not working:**
- Verify Select2 is loaded before custom script
- Check dependencies are correct
- Ensure jQuery is available
- Check browser console for errors

**Styles not applied:**
- Clear browser cache
- Check CSS file is loading (Network tab)
- Verify no CSS conflicts
- Check specificity of selectors

---

## Adding New Assets

To add a new asset file:

1. **Create the file** in `includes/assets/`
2. **Name it properly** following conventions
3. **Enqueue in PHP** using `enqueue_admin_scripts()`
4. **Declare dependencies** if any
5. **Add to documentation** in this file
6. **Test thoroughly** in different scenarios

**Example:**

```php
// In enqueue_admin_scripts()
if ( 'new-tab' === $active_tab ) {
    wp_enqueue_script(
        'ccrmre-new-feature',
        plugin_dir_url( __FILE__ ) . 'assets/iip-new-feature.js',
        array( 'jquery' ),
        CCRMRE_VERSION,
        true
    );
}
```

---

## Distribution

All assets in `includes/assets/` are included in distribution packages.

Excluded from distribution (via `.distignore`):
- Source files (if using build process)
- Development versions
- Test files

---

## Resources

- [WordPress Enqueue Scripts Guide](https://developer.wordpress.org/reference/functions/wp_enqueue_script/)
- [WordPress Enqueue Styles Guide](https://developer.wordpress.org/reference/functions/wp_enqueue_style/)
- [WordPress Localization Guide](https://developer.wordpress.org/reference/functions/wp_localize_script/)
- [Select2 Documentation](https://select2.org/)
