# Property Information Box Feature

## Overview

The Property Information Box feature displays key property details in a beautiful, visually appealing card with icons and a prominent price display. It can be shown automatically after the content or manually using a shortcode.

## Features

- ✅ **Large price display** - Prominent pricing at the top
- ✅ **Icon-based layout** - Visual icons for each property feature
- ✅ **Gradient background** - Modern purple gradient design
- ✅ **Responsive grid** - Adapts to all screen sizes
- ✅ **Automatic field mapping** - Detects common property fields
- ✅ **Multiple theme options** - Different color schemes available
- ✅ **Auto or manual display** - Settings-based or shortcode

---

## Information Displayed

### Primary Fields

1. **💰 Price** - Large display at the top
2. **🛏️ Bedrooms** - Number of bedrooms
3. **🚿 Bathrooms** - Number of bathrooms
4. **📐 Area** - Property size in m²
5. **🏠 Type** - Property type (apartment, house, etc.)
6. **📍 Location** - City/municipality
7. **📄 Reference** - Property reference code

---

## Configuration

### Settings Location

Navigate to: **WordPress Admin > Connect CRM Real State > Settings**

### Auto Display Option

**Field:** "Auto Display Property Info Box"

**Options:**
- **No - Use shortcode only**: Only displays where shortcode is added
- **Yes - Auto display after content**: Automatically appears after property content

**Default:** No (shortcode only)

---

## Usage

### Method 1: Automatic Display

1. Go to **Connect CRM Real State > Settings**
2. Find "Auto Display Property Info Box" field
3. Select **"Yes - Auto display after content"**
4. Save settings

The info box will now automatically appear on all property single pages at the end of the content.

### Method 2: Manual Shortcode

Use the shortcode anywhere in your content, widgets, or templates:

```
[property_info]
```

#### Shortcode Parameters

**post_id** (optional): Display info for a specific property ID

```
[property_info post_id="123"]
```

If no `post_id` is provided, it will use the current property.

---

## Visual Design

### Layout Structure

```
┌─────────────────────────────────────┐
│         Property Info Box           │
│  ┌─────────────────────────────┐   │
│  │         Price                │   │
│  │       125.000 €              │   │
│  └─────────────────────────────┘   │
│                                     │
│  ┌────┐  ┌────┐  ┌────┐           │
│  │🛏️3 │  │🚿2 │  │📐85m²│          │
│  └────┘  └────┘  └────┘           │
│                                     │
│  ┌────┐  ┌────┐  ┌────┐           │
│  │🏠  │  │📍  │  │📄  │            │
│  └────┘  └────┘  └────┘           │
└─────────────────────────────────────┘
```

### Color Schemes

#### Default (Purple Gradient)
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

#### Available Themes
- `ccrmre-theme-blue` - Blue/Purple gradient (default)
- `ccrmre-theme-green` - Green gradient
- `ccrmre-theme-orange` - Orange gradient
- `ccrmre-theme-dark` - Dark gray gradient

---

## Technical Details

### Files Structure

```
includes/
├── class-property-info.php          # Main property info class
└── assets/
    └── property-info.css            # Styles and themes
```

### Field Mapping

The system automatically detects property fields using multiple name variations:

#### Price Fields
- `precio`, `price`, `pvp`, `precio_venta`

#### Bedroom Fields
- `dormitorios`, `bedrooms`, `habitaciones`, `dorm`

#### Bathroom Fields
- `banos`, `bathrooms`, `aseos`, `wc`

#### Area Fields
- `superficie`, `area`, `m2`, `metros`, `superficie_construida`

#### Type Fields
- `tipo`, `type`, `tipologia`, `tipo_inmueble`

#### Location Fields
- `ciudad`, `location`, `localidad`, `poblacion`, `municipio`

#### Reference Fields
- `referencia`, `reference`, `ref`, `codigo`

---

## Responsive Breakpoints

### Desktop (>768px)
- Price: 48px font
- Grid: Auto-fit columns (min 200px)
- Icons: 48x48px
- Values: 24px font

### Tablet (≤768px)
- Price: 36px font
- Grid: 2 columns
- Icons: 40x40px
- Values: 20px font

### Mobile (≤480px)
- Price: 32px font
- Grid: 1 column (stacked)
- Icons: 40x40px
- Compact padding

---

## Customization

### Change Theme

Add a class to the info box using a filter:

```php
add_filter('ccrmre_property_info_class', function($classes) {
    $classes[] = 'ccrmre-theme-green';
    return $classes;
});
```

### Custom CSS

Override styles in your theme:

```css
/* Change gradient colors */
.ccrmre-property-info-box {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
}

/* Change price size */
.ccrmre-price-value {
    font-size: 60px;
}

/* Customize grid */
.ccrmre-info-grid {
    grid-template-columns: repeat(3, 1fr);
}
```

### Add Custom Fields

Extend the field mapping in your theme's `functions.php`:

```php
add_filter('ccrmre_property_info_fields', function($fields, $post_id) {
    // Add pool info
    $pool = get_post_meta($post_id, 'property_pool', true);
    if ($pool) {
        $fields['pool'] = $pool;
    }
    return $fields;
}, 10, 2);
```

---

## Integration Examples

### In Page Builder

Most page builders support shortcodes:

**Elementor:**
1. Add "Shortcode" widget
2. Enter `[property_info]`

**Gutenberg:**
1. Add "Shortcode" block
2. Enter `[property_info]`

### In PHP Template

```php
<?php
if (function_exists('do_shortcode')) {
    echo do_shortcode('[property_info]');
}
?>
```

### In Widget

Most widget areas support shortcodes directly, or use a "Text" widget and enable shortcode processing.

---

## Troubleshooting

### Info Box Not Showing

**Check:**
1. Property has imported data from CRM
2. Merge fields are configured correctly
3. Auto display is enabled (or shortcode is added)
4. License is active

### Missing Information

**Check:**
1. Field mapping in merge fields settings
2. Data exists in property meta
3. Field names match mapping arrays
4. Import completed successfully

### Styling Issues

**Check:**
1. CSS file loaded (check browser inspector)
2. No theme conflicts (try default theme)
3. Clear cache (browser and WordPress)
4. Check for CSS override conflicts

---

## Performance

### Optimizations

1. **Conditional Loading**: CSS only loads on property pages
2. **No JavaScript**: Pure CSS implementation
3. **Lightweight SVG**: Icons use inline SVG (no image requests)
4. **GPU Acceleration**: Transform effects use hardware acceleration

### Load Impact

- CSS file size: ~6KB
- No additional HTTP requests (inline SVG icons)
- Minimal render impact

---

## Accessibility

### Features

- ✅ **Semantic HTML**: Proper structure
- ✅ **Color Contrast**: WCAG AA compliant
- ✅ **Screen Reader**: Proper labels
- ✅ **Print Friendly**: Optimized print styles

---

## Print Styles

When printing, the info box automatically:
- Changes to white background with black text
- Removes gradients and shadows
- Adds borders for clarity
- Maintains readability

---

## Developer Reference

### PHP Class Methods

**Get Info Box HTML:**
```php
$property_info = new Close\ConnectCRM\RealState\PropertyInfo();
echo $property_info->shortcode_property_info(['post_id' => 123]);
```

### Hooks & Filters

**Filter info display:**
```php
add_filter('ccrmre_show_property_info', '__return_false', 10, 2);
```

**Modify mapped fields:**
```php
add_filter('ccrmre_property_info_fields', 'custom_property_fields', 10, 2);
```

---

## Examples

### Real Estate Agency

Display property info prominently after description:
- Enable auto-display
- Use default purple theme
- Shows all imported fields automatically

### Property Portal

Use shortcode in custom layout:
```
[property_gallery]

<div class="property-content">
    <?php the_content(); ?>
</div>

[property_info]

<div class="property-features">
    <!-- Additional custom content -->
</div>
```

---

## Changelog

### Version 1.0.0-beta.12
- Initial property info box feature
- Multiple field mapping support
- 4 color theme options
- Responsive design
- Auto display option
- Shortcode support

---

## Support

For issues or feature requests:

1. Verify merge fields configuration
2. Check data imported correctly
3. Test with default theme
4. Contact: david@closemarketing.es
