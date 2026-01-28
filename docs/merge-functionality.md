# Merge Variables Functionality

## Overview

The Merge Variables functionality allows you to map CRM API fields to WordPress custom fields. This feature enables automatic synchronization of property data from your CRM (Inmovilla or Anaconda) to specific WordPress custom fields.

## Location

Access the Merge Variables page at:
```
wp-admin/admin.php?page=iip-options&tab=iip-merge
```

Or navigate through:
**WordPress Admin** → **Connect CRM Real State** → **Merge variables** tab

## How It Works

### 1. Field Detection

When you access the Merge Variables page, the plugin automatically:

- **Retrieves CRM Fields**: Fetches all available fields from your selected CRM (Anaconda or Inmovilla)
- **Retrieves WordPress Fields**: Gets all custom fields from your configured post type
- **Caches Results**: Stores field data for 24 hours to improve performance

### 2. Field Mapping Interface

The interface displays a table with two columns:

#### CRM Fields Column
- Shows the human-readable label of each CRM field
- Displays the field slug in parentheses (e.g., `cod_ofer`)
- Lists all available fields from your CRM API

#### WordPress Fields Column
- Searchable dropdown (Select2) with all custom fields
- Allows you to select which WordPress field should receive the CRM data
- Can be left empty if you don't want to map a particular field

### 3. Using Select2

The WordPress Fields dropdown uses Select2, providing:
- **Search functionality**: Type to quickly find a field
- **Create new fields**: Type a new field name to create it on the fly
- **Clear selection**: Click the × to remove a mapping
- **Responsive design**: Works well on all screen sizes

#### Creating New WordPress Fields

You can create new custom fields directly from the merge interface:

1. **Type a field name** that doesn't exist in the dropdown
2. **The name is auto-sanitized**: 
   - Converted to lowercase
   - Special characters replaced with underscores
   - Multiple underscores collapsed to one
   - Leading/trailing underscores removed
3. **Visual indicator**: New fields show with a "+" icon and "(New field)" label
4. **Automatic creation**: The field will be created when you save and import properties

**Example:**
- You type: `My Property Price`
- System creates: `my_property_price`
- This field will be used as a custom field key in WordPress

### 4. Auto-Map All Fields

For quick setup, use the **Auto-Map All Fields** button:

1. **Click the button** above the field list
2. **Confirm the action** in the dialog
3. **System automatically generates** WordPress field names for all CRM fields
4. **Preserves existing mappings** - only maps unmapped fields
5. **Auto-saves** the mappings after completion

**Auto-generated field names:**
- Based on CRM field names
- Prefixed with `crm_` to avoid conflicts
- Sanitized (lowercase, underscores only)
- Example: `precioinmo` → `crm_precioinmo`

### 5. Saving Mappings

Click **Save Changes** to store your field mappings. The plugin saves:
- An array of `CRM_field_name => wordpress_field_name` pairs
- Only non-empty mappings (empty selections are not saved)
- Shows success message with count of saved mappings

## Configuration by CRM Type

### Anaconda

For Anaconda CRM, the plugin:
1. Fetches a sample property from the API
2. Extracts all field names from the property data
3. Creates human-readable labels from field slugs

**Example Anaconda fields:**
- `property_id` → Property ID
- `property_name` → Property Name
- `property_price` → Property Price

### Inmovilla

For Inmovilla CRM, the plugin:
1. Fetches a sample property using the `paginacion` endpoint
2. Extracts all field names
3. Maps fields to descriptive Spanish labels

**Example Inmovilla fields:**
- `cod_ofer` → Referencia de la propiedad
- `precioinmo` → Precio de la propiedad
- `habitaciones` → Habitaciones simples
- `banyos` → Baños

## Data Storage

The field mappings are stored in the WordPress options table as:
- **Option name**: `conncrmreal_merge_fields`
- **Format**: Serialized array
- **Structure**: `array( 'crm_field' => 'wp_field', ... )`

## Performance Optimization

### Transient Caching

The plugin uses WordPress transients to cache:
- **Anaconda fields**: `ccrmre_query_anaconda_fields` (24 hours)
- **Inmovilla fields**: `ccrmre_query_inmovilla_fields` (24 hours)

To clear the cache, you can:
1. Wait 24 hours for automatic expiration
2. Manually delete the transients using a plugin
3. Use WP-CLI: `wp transient delete ccrmre_query_inmovilla_fields`

## Usage During Import

When properties are imported from the CRM:

1. The plugin loads the saved field mappings
2. For each property, it reads the CRM field values
3. Maps them to the corresponding WordPress custom fields
4. Saves the data to the post meta table

## Troubleshooting

### No CRM Fields Showing

**Possible causes:**
- API credentials not configured
- No connection to CRM
- No properties in CRM

**Solution:**
1. Go to Settings tab
2. Verify API credentials
3. Test connection by importing a property

### WordPress Fields Not Appearing

**Possible causes:**
- No custom fields in the selected post type
- Post type not configured

**Solution:**
1. Ensure you have posts with custom fields
2. Check the post type setting in Settings tab

### Cannot Search Fields

**Possible causes:**
- Select2 JavaScript not loading
- JavaScript conflicts

**Solution:**
1. Check browser console for errors
2. Disable other plugins temporarily
3. Try a different browser

## Technical Details

### Files Modified

- `includes/class-iip-admin.php`: Admin interface and field mapping
- `includes/class-helper-api.php`: API field retrieval

### Functions

#### `merge_fields_callback()`
Renders the merge variables interface with Select2 dropdowns.

#### `get_all_custom_fields( $post_type )`
Retrieves all custom field keys for a given post type from the database.

#### `sanitize_fields_settings_merge( $input )`
Sanitizes and filters field mappings before saving.

#### `API::get_properties_fields( $crm )`
Gets available fields from the selected CRM.

#### `API::get_fields_anaconda()`
Retrieves field structure from Anaconda API.

#### `API::get_fields_inmovilla()`
Retrieves field structure from Inmovilla API with Spanish labels.

## Best Practices

1. **Test Mappings**: After setting up mappings, import a single property to verify
2. **Document Mappings**: Keep a record of which CRM fields map to which WordPress fields
3. **Regular Review**: Periodically review mappings when CRM or WordPress fields change
4. **Backup First**: Always backup your database before bulk imports

## Technical Implementation

### Select2 Library

The plugin uses Select2 4.0.13 for enhanced dropdown functionality:

- **Installation**: Via Composer (`composer require select2/select2`)
- **Location**: `vendor/select2/select2/dist/`
- **Files Used**:
  - CSS: `select2.min.css`
  - JS: `select2.min.js`
  - i18n: `i18n/es.js` (Spanish translation)

**Benefits:**
- Local assets (no external CDN)
- Better performance and security
- Offline capability
- Version control via Composer

For detailed information, see: [Select2 Local Setup Documentation](select2-local-setup.md)

## Support

For issues or questions:
- Check the plugin logs at **Connect CRM Real State** → **Log** tab
- Contact Close·marketing support
- Review API documentation for your CRM
- See [Select2 Documentation](https://select2.org/) for Select2-specific issues
