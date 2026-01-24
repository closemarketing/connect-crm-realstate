# Test Cases: get_property_info() Function

## Function Overview

**Location:** `includes/class-helper-api.php`  
**Method:** `API::get_property_info( $property, $crm_type )`  
**Purpose:** Extract property information (ID, reference, last_updated) from CRM property data

## Input/Output Specification

### Input Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | array | Property data from CRM API |
| `$crm_type` | string | CRM type: 'anaconda', 'inmovilla', 'inmovilla_procesos' |

### Output

Returns an array with three keys:

```php
array(
    'id'           => string|int|null,  // Property ID
    'reference'    => string|null,      // Property reference
    'last_updated' => string|null       // Last update timestamp
)
```

## CRM Field Mappings

### Anaconda

| Output Key | Input Field | Description |
|------------|-------------|-------------|
| `id` | `id` | Numeric property ID |
| `reference` | `referencia` | Property reference code |
| `last_updated` | `updated_at` | Last update timestamp |

### Inmovilla (APIWEB)

| Output Key | Input Field | Description |
|------------|-------------|-------------|
| `id` | `cod_ofer` | Property code |
| `reference` | `referencia` | Property reference |
| `last_updated` | `fechaact` | Last update date |

### Inmovilla Procesos

| Output Key | Input Field | Description |
|------------|-------------|-------------|
| `id` | `cod_ofer` | Property code |
| `reference` | - | Not available |
| `last_updated` | `fechaact` | Last update date |

## Test Cases

### 1. Anaconda CRM Tests

#### Test 1.1: All Fields Present ✅
```php
Input:
    property = [
        'id' => '12345',
        'referencia' => 'REF-ABC-001',
        'updated_at' => '2024-01-20 10:30:00'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '12345',
        'reference' => 'REF-ABC-001',
        'last_updated' => '2024-01-20 10:30:00'
    ]
```

#### Test 1.2: Missing Fields ✅
```php
Input:
    property = [
        'id' => '12345'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '12345',
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 1.3: Empty Property ✅
```php
Input:
    property = []
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => null,
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 1.4: Numeric ID ✅
```php
Input:
    property = [
        'id' => 123456,  // Integer, not string
        'referencia' => 'REF-123'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => 123456,  // Preserves integer type
        'reference' => 'REF-123',
        'last_updated' => null
    ]
```

#### Test 1.5: Only ID Present ✅
```php
Input:
    property = [
        'id' => '12345'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '12345',
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 1.6: Only Reference Present ✅
```php
Input:
    property = [
        'referencia' => 'REF-001'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => null,
        'reference' => 'REF-001',
        'last_updated' => null
    ]
```

#### Test 1.7: Only Date Present ✅
```php
Input:
    property = [
        'updated_at' => '2024-01-20'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => null,
        'reference' => null,
        'last_updated' => '2024-01-20'
    ]
```

---

### 2. Inmovilla CRM Tests

#### Test 2.1: All Fields Present ✅
```php
Input:
    property = [
        'cod_ofer' => 'INM-2024-001',
        'referencia' => 'REF-INM-001',
        'fechaact' => '2024-01-20'
    ]
    crm_type = 'inmovilla'

Expected Output:
    [
        'id' => 'INM-2024-001',
        'reference' => 'REF-INM-001',
        'last_updated' => '2024-01-20'
    ]
```

#### Test 2.2: Missing Reference ✅
```php
Input:
    property = [
        'cod_ofer' => 'INM-2024-001',
        'fechaact' => '2024-01-20'
    ]
    crm_type = 'inmovilla'

Expected Output:
    [
        'id' => 'INM-2024-001',
        'reference' => null,
        'last_updated' => '2024-01-20'
    ]
```

#### Test 2.3: Missing Date ✅
```php
Input:
    property = [
        'cod_ofer' => 'INM-2024-001',
        'referencia' => 'REF-INM-001'
    ]
    crm_type = 'inmovilla'

Expected Output:
    [
        'id' => 'INM-2024-001',
        'reference' => 'REF-INM-001',
        'last_updated' => null
    ]
```

---

### 3. Inmovilla Procesos CRM Tests

#### Test 3.1: All Fields Present ✅
```php
Input:
    property = [
        'cod_ofer' => 'PROC-2024-001',
        'fechaact' => '2024-01-20 15:45:00'
    ]
    crm_type = 'inmovilla_procesos'

Expected Output:
    [
        'id' => 'PROC-2024-001',
        'reference' => null,  // Not available in this CRM
        'last_updated' => '2024-01-20 15:45:00'
    ]
```

#### Test 3.2: Missing Date ✅
```php
Input:
    property = [
        'cod_ofer' => 'PROC-2024-001'
    ]
    crm_type = 'inmovilla_procesos'

Expected Output:
    [
        'id' => 'PROC-2024-001',
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 3.3: Empty Property ✅
```php
Input:
    property = []
    crm_type = 'inmovilla_procesos'

Expected Output:
    [
        'id' => null,
        'reference' => null,
        'last_updated' => null
    ]
```

---

### 4. Edge Cases

#### Test 4.1: Unknown CRM Type ✅
```php
Input:
    property = [
        'id' => '12345',
        'referencia' => 'REF-001'
    ]
    crm_type = 'unknown_crm'

Expected Output:
    [
        'id' => null,
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 4.2: Null Values ✅
```php
Input:
    property = [
        'id' => null,
        'referencia' => null,
        'updated_at' => null
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => null,
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 4.3: Empty Strings ✅
```php
Input:
    property = [
        'id' => '',
        'referencia' => '',
        'updated_at' => ''
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '',
        'reference' => '',
        'last_updated' => ''
    ]

Note: Empty strings are preserved, not converted to null
```

#### Test 4.4: Special Characters ✅
```php
Input:
    property = [
        'id' => 'ID-ÑÁÉ-001',
        'referencia' => 'REF-€$@-002',
        'updated_at' => '2024-01-20 10:30:00'
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => 'ID-ÑÁÉ-001',
        'reference' => 'REF-€$@-002',
        'last_updated' => '2024-01-20 10:30:00'
    ]

Note: Special characters are preserved as-is
```

#### Test 4.5: Data Type Preservation ✅
```php
Input (Integer):
    property = [
        'id' => 999  // Integer
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => 999,  // Still integer
        'reference' => null,
        'last_updated' => null
    ]

Input (String):
    property = [
        'id' => '999'  // String
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '999',  // Still string
        'reference' => null,
        'last_updated' => null
    ]
```

#### Test 4.6: Extra Fields Ignored ✅
```php
Input:
    property = [
        'id' => '12345',
        'referencia' => 'REF-001',
        'updated_at' => '2024-01-20',
        'titulo' => 'Property Title',
        'descripcion' => 'Description',
        'precio' => 100000
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => '12345',
        'reference' => 'REF-001',
        'last_updated' => '2024-01-20'
    ]

Note: Only mapped fields are included, extra fields are ignored
```

#### Test 4.7: Very Long Strings ✅
```php
Input:
    property = [
        'id' => str_repeat('A', 1000),
        'referencia' => str_repeat('B', 1000),
        'updated_at' => str_repeat('C', 100)
    ]
    crm_type = 'anaconda'

Expected Output:
    [
        'id' => [1000 character string],
        'reference' => [1000 character string],
        'last_updated' => [100 character string]
    ]

Note: Function handles long strings without truncation
```

---

## Summary Statistics

| Category | Test Count | Status |
|----------|------------|--------|
| Anaconda Tests | 7 | ✅ All Pass |
| Inmovilla Tests | 3 | ✅ All Pass |
| Inmovilla Procesos Tests | 3 | ✅ All Pass |
| Edge Cases | 7 | ✅ All Pass |
| **Total** | **20** | **✅ 100%** |

## Code Coverage

- ✅ All CRM types covered
- ✅ All field mappings tested
- ✅ Missing fields handled
- ✅ Empty data handled
- ✅ Null values handled
- ✅ Data type preservation verified
- ✅ Edge cases covered
- ✅ Unknown CRM type handled

## Usage Examples

### Example 1: Using with Anaconda Property
```php
$property = array(
    'id' => 12345,
    'referencia' => 'ANA-2024-001',
    'updated_at' => '2024-01-20 10:30:00',
    'titulo' => 'Luxury Apartment'
);

$info = API::get_property_info( $property, 'anaconda' );

// Use ID for API calls
if ( ! empty( $info['id'] ) ) {
    $details = API::get_property( $info['id'], 'anaconda' );
}

// Check last update
if ( $info['last_updated'] ) {
    echo 'Last updated: ' . $info['last_updated'];
}
```

### Example 2: Fallback to Reference
```php
$info = API::get_property_info( $property, 'anaconda' );

// Use ID if available, otherwise use reference
$identifier = ! empty( $info['id'] ) ? $info['id'] : $info['reference'];

if ( $identifier ) {
    // Process property
}
```

### Example 3: Checking for Updates
```php
$info = API::get_property_info( $property, 'inmovilla' );

$local_update_time = get_post_meta( $post_id, 'last_sync', true );

if ( $info['last_updated'] && $info['last_updated'] > $local_update_time ) {
    // Property needs update
    sync_property( $post_id, $property );
}
```

## Related Functions

- `API::get_all_property_ids()` - Uses `get_property_info()` to extract IDs
- `API::get_property()` - Uses `get_property_info()` to get property identifiers
- `API::get_fields_inmovilla_procesos()` - Uses `get_property_info()` for field mapping

## Notes

1. The function is **static** and can be called without instantiating the class
2. It always returns an array with three keys, even if all values are null
3. It preserves the original data types (string, integer, null)
4. It does not validate or sanitize the input data
5. Extra fields in the property array are safely ignored
6. The function is **CRM-agnostic** and handles unknown CRM types gracefully
