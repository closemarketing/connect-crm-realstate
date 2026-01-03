# Select2 Local Setup

## Overview

The plugin uses Select2 4.0.13 installed locally via Composer instead of loading from external CDNs for better performance, security, and offline capability.

## Installation

Select2 is automatically installed when running:

```bash
composer install
```

Or to update:

```bash
composer update select2/select2
```

## File Locations

### CSS Files
```
vendor/select2/select2/dist/css/
├── select2.min.css          ← Used in plugin (minified)
└── select2.css              (development version, excluded from distribution)
```

### JavaScript Files
```
vendor/select2/select2/dist/js/
├── select2.min.js           ← Used in plugin (minified)
├── select2.full.min.js      (full version, excluded from distribution)
└── i18n/
    └── es.js                ← Spanish translation (loaded when locale is es_*)
```

## Implementation

### Enqueue in PHP

Located in `includes/class-iip-admin.php`:

```php
// Enqueue Select2 CSS
wp_enqueue_style(
    'ccrmre-select2',
    CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/css/select2.min.css',
    array(),
    '4.0.13'
);

// Enqueue Select2 JS
wp_enqueue_script(
    'ccrmre-select2',
    CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/select2.min.js',
    array( 'jquery' ),
    '4.0.13',
    true
);

// Enqueue Spanish translation if locale is Spanish
$locale = get_locale();
if ( strpos( $locale, 'es_' ) === 0 ) {
    wp_enqueue_script(
        'ccrmre-select2-i18n',
        CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/i18n/es.js',
        array( 'ccrmre-select2' ),
        '4.0.13',
        true
    );
}
```

### Initialize in JavaScript

Located in `includes/assets/iip-merge-fields.js`:

```javascript
// Select2 configuration
var select2Config = {
    placeholder: ccrmreMergeFields.searchPlaceholder,
    allowClear: true,
    width: '100%',
    tags: true,
    // ... other config
};

// Add language if available
if (typeof $.fn.select2.amd !== 'undefined' && $.fn.select2.amd.require('select2/i18n/es')) {
    select2Config.language = 'es';
}

// Initialize
$('.ccrmre-select2-field').select2(select2Config);
```

## Distribution Package

### Files Included

The `.distignore` file is configured to include only essential Select2 files:

**Included:**
- `vendor/select2/select2/dist/css/select2.min.css`
- `vendor/select2/select2/dist/js/select2.min.js`
- `vendor/select2/select2/dist/js/i18n/*.js` (all translations)

**Excluded:**
- Source files (`src/`)
- Tests (`tests/`)
- Documentation (`docs/`)
- Development files (unminified versions)
- GitHub files (`.github/`)
- Build files (`Gruntfile.js`, `package.json`, etc.)

### Package Size Optimization

By excluding unnecessary files, the distribution package includes only:
- ~50KB CSS (minified)
- ~70KB JS (minified)
- ~2KB per translation file

Total Select2 size in distribution: **~130KB** (vs ~2MB+ with all files)

## Supported Languages

Select2 includes translations for multiple languages in:
```
vendor/select2/select2/dist/js/i18n/
```

Currently loaded:
- **Spanish** (`es.js`) - Auto-loaded for Spanish locales (es_ES, es_MX, etc.)

To add support for other languages, modify the enqueue logic in `class-iip-admin.php`:

```php
// Example for Catalan
if ( strpos( $locale, 'ca_' ) === 0 ) {
    wp_enqueue_script(
        'ccrmre-select2-i18n',
        CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/i18n/ca.js',
        array( 'ccrmre-select2' ),
        '4.0.13',
        true
    );
}
```

## Benefits of Local Installation

### 1. **Performance**
- No external HTTP requests
- Faster load times
- Better for users with slow connections

### 2. **Security**
- No dependency on external CDNs
- No risk of CDN compromise
- Full control over library versions

### 3. **Reliability**
- Works offline (local development)
- No CDN downtime issues
- Consistent behavior across environments

### 4. **Privacy**
- No tracking from external CDNs
- No external dependencies
- GDPR compliant

### 5. **Version Control**
- Locked to specific version (4.0.13)
- Predictable behavior
- Easy to update via Composer

## Updating Select2

To update Select2 to a newer version:

1. Update composer requirement:
```bash
composer require select2/select2:^4.1
```

2. Update version numbers in enqueue calls:
```php
'4.0.13' → '4.1.0'
```

3. Test thoroughly before deploying

## Troubleshooting

### Select2 Not Loading

**Check:**
1. Composer dependencies installed: `composer install`
2. Vendor folder exists
3. File paths are correct
4. Browser console for errors

### Translations Not Working

**Check:**
1. Locale is correctly detected: `get_locale()`
2. Translation file exists for your locale
3. Translation script is enqueued after main Select2 script
4. JavaScript console for errors

### Styles Not Applied

**Check:**
1. CSS file is enqueued
2. No CSS conflicts with theme
3. Browser cache cleared
4. Network tab shows successful load

## Development vs Production

### Development
- Use unminified versions for debugging
- Enable WP_DEBUG for detailed errors
- Check browser console for warnings

### Production
- Use minified versions (already configured)
- Disable WP_DEBUG
- Enable caching for better performance

## Best Practices

1. **Always use Composer** to manage Select2 dependency
2. **Keep versions locked** in composer.json for stability
3. **Test after updates** to ensure compatibility
4. **Monitor package size** to keep plugin lightweight
5. **Document changes** when modifying Select2 configuration

## Resources

- [Select2 Official Documentation](https://select2.org/)
- [Select2 GitHub Repository](https://github.com/select2/select2)
- [Composer Documentation](https://getcomposer.org/)
