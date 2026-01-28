# Property Photo Gallery Feature

## Overview

The Property Photo Gallery feature provides a responsive carousel to display property photos from the CRM. It can be displayed automatically after the property title or manually using a shortcode.

## Features

- ✅ **Responsive carousel** with smooth transitions
- ✅ **Thumbnail navigation** with active state
- ✅ **Previous/Next buttons** with hover effects
- ✅ **Touch/swipe support** for mobile devices
- ✅ **Keyboard navigation** (arrow keys)
- ✅ **Photo counter** (1/22)
- ✅ **Lazy loading** for better performance
- ✅ **Automatic or manual display**

---

## Configuration

### Settings Location

Navigate to: **WordPress Admin > Connect CRM Real State > Settings**

### Auto Display Option

**Field:** "Auto Display Photo Gallery"

**Options:**
- **No - Use shortcode only**: Gallery will only display where you manually add the shortcode
- **Yes - Auto display after title**: Gallery automatically appears after the property title

**Default:** No (shortcode only)

---

## Usage

### Method 1: Automatic Display (Recommended)

1. Go to **Connect CRM Real State > Settings**
2. Find "Auto Display Photo Gallery" field
3. Select **"Yes - Auto display after title"**
4. Save settings

The gallery will now automatically appear on all property single pages, right after the title and before the content.

### Method 2: Manual Shortcode

Use the shortcode anywhere in your content, widgets, or templates:

```
[property_gallery]
```

#### Shortcode Parameters

**post_id** (optional): Display gallery for a specific property ID

```
[property_gallery post_id="123"]
```

If no `post_id` is provided, it will use the current property.

---

## Technical Details

### Files Structure

```
includes/
├── class-gallery.php         # Main gallery class
└── assets/
    ├── gallery.css          # Carousel styles
    └── gallery.js           # Carousel functionality
```

### CSS Classes

- `.ccrmre-property-gallery` - Main container
- `.ccrmre-gallery-main` - Slider container
- `.ccrmre-gallery-slide` - Individual slide
- `.ccrmre-gallery-slide.active` - Active slide
- `.ccrmre-gallery-prev` - Previous button
- `.ccrmre-gallery-next` - Next button
- `.ccrmre-gallery-counter` - Photo counter
- `.ccrmre-gallery-thumbnails` - Thumbnails container
- `.ccrmre-gallery-thumb` - Individual thumbnail
- `.ccrmre-gallery-thumb.active` - Active thumbnail

### Customization

#### Custom CSS

Add custom styles in your theme's CSS file:

```css
/* Change aspect ratio */
.ccrmre-gallery-main {
    aspect-ratio: 4 / 3;
}

/* Change button colors */
.ccrmre-gallery-prev,
.ccrmre-gallery-next {
    background: rgba(255, 0, 0, 0.7);
}

/* Change thumbnail grid */
.ccrmre-gallery-thumbnails {
    grid-template-columns: repeat(6, 1fr);
}
```

#### Enable Auto-Advance

Edit `includes/assets/gallery.js` and uncomment lines 156-167 to enable automatic slideshow (advances every 5 seconds).

---

## Responsive Breakpoints

### Desktop (>768px)
- Aspect ratio: 16:9
- Thumbnails: Auto-fill with min 100px
- Button size: 50x50px

### Tablet (768px)
- Aspect ratio: 4:3
- Thumbnails: Auto-fill with min 80px
- Button size: 40x40px

### Mobile (<480px)
- Aspect ratio: 1:1
- Thumbnails: Auto-fill with min 60px
- Compact layout

---

## User Interactions

### Desktop
- **Click** Previous/Next buttons to navigate
- **Click** thumbnails to jump to specific photo
- **Arrow keys** (← →) for keyboard navigation

### Mobile/Touch
- **Swipe** left/right to navigate
- **Tap** thumbnails to jump to specific photo
- **Tap** Previous/Next buttons

---

## Performance

### Optimizations

1. **Lazy Loading**: Images load only when needed
2. **CSS Transitions**: Smooth animations with GPU acceleration
3. **Thumbnail Scrolling**: Only visible thumbnails are rendered
4. **Conditional Loading**: Assets only load on property pages

### Load Order

1. Gallery CSS enqueued in `<head>`
2. Gallery JS enqueued before `</body>`
3. Images load with `loading="lazy"` attribute

---

## Troubleshooting

### Gallery Not Showing

**Check:**
1. Property has photos imported from CRM
2. Meta key `ccrmre_gallery_urls` exists for the property
3. Auto display is enabled in settings (or shortcode is added)
4. License is active

### Gallery Shows But No Photos

**Check:**
1. Import completed successfully
2. Photos are available in property meta (`ccrmre_gallery_urls`)
3. Image URLs are valid and accessible
4. Browser console for JavaScript errors

### Thumbnails Not Clickable

**Check:**
1. JavaScript loaded correctly (check browser console)
2. No theme/plugin conflicts
3. Try clearing cache

---

## Developer Reference

### Hooks & Filters

#### Filter: Auto Display Priority

```php
// Change when gallery is inserted in content
add_filter('the_content', 'custom_gallery_position', 15);
```

#### Shortcode Override

```php
// Remove default shortcode
remove_shortcode('property_gallery');

// Add custom implementation
add_shortcode('property_gallery', 'custom_property_gallery');
function custom_property_gallery($atts) {
    // Your custom code
}
```

### PHP Methods

**Render Gallery:**
```php
$gallery = new Close\ConnectCRM\RealState\Gallery();
echo $gallery->shortcode_gallery(['post_id' => 123]);
```

---

## Changelog

### Version 1.0.0-beta.12
- Initial gallery feature release
- Responsive carousel with thumbnails
- Auto display option
- Shortcode support
- Touch/keyboard navigation

---

## Support

For issues or feature requests related to the gallery:

1. Check property has imported photos
2. Verify settings configuration
3. Test with default WordPress theme
4. Contact support at: david@closemarketing.es
