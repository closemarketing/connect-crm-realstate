# Status Logic Inversion - Inmovilla & Inmovilla Procesos

## 📋 Descripción

Se ha corregido la lógica de status para Inmovilla e Inmovilla Procesos, donde el campo `nodisponible` tiene lógica **inversa** al campo `status` estándar.

## 🎯 Problema Resuelto

**ANTES** ❌:
```php
// Para todos los CRMs
$property_info['status'] = $property['nodisponible']; // Directamente

// Resultado incorrecto para Inmovilla:
// nodisponible = 1 → status = 1 ❌ (debería ser false)
// nodisponible = 0 → status = 0 ❌ (debería ser true)
```

**AHORA** ✅:
```php
// Para Inmovilla e Inmovilla Procesos
if ( in_array( $crm_type, array( 'inmovilla', 'inmovilla_procesos' ), true ) && 
     'nodisponible' === $fields['status'] ) {
    $status_value = ! (bool) $status_value; // Invertir lógica
}

// Resultado correcto:
// nodisponible = 1 → status = false ✅ (NO disponible)
// nodisponible = 0 → status = true  ✅ (disponible)
```

## 🔧 Implementación

### Archivo Modificado
`/includes/class-helper-api.php`

### Método Actualizado
`get_property_info()`

### Cambio Realizado

```php
// Get status if available.
if ( isset( $fields['status'] ) && isset( $property[ $fields['status'] ] ) ) {
    $status_value = $property[ $fields['status'] ];

    // For inmovilla and inmovilla_procesos, nodisponible has inverse logic.
    // nodisponible = 1 means NOT available, so status = false.
    // nodisponible = 0 means available, so status = true.
    if ( in_array( $crm_type, array( 'inmovilla', 'inmovilla_procesos' ), true ) && 
         'nodisponible' === $fields['status'] ) {
        $status_value = ! (bool) $status_value; // Invert the logic.
    }

    $property_info[ $prefix . 'status' ] = $status_value;
}
```

## 📊 Lógica por CRM

### Anaconda
**Campo**: `status`  
**Lógica**: Directa

| Valor API | Status Interpretado | Disponible |
|-----------|---------------------|------------|
| `1` / `true` | `true` | ✅ Sí |
| `0` / `false` | `false` | ❌ No |

### Inmovilla (APIWEB)
**Campo**: `nodisponible`  
**Lógica**: **Inversa** ⚠️

| Valor API (`nodisponible`) | Status Interpretado | Disponible |
|----------------------------|---------------------|------------|
| `1` / `true` | `false` | ❌ No (NO disponible) |
| `0` / `false` | `true` | ✅ Sí (disponible) |

### Inmovilla Procesos
**Campo**: `nodisponible`  
**Lógica**: **Inversa** ⚠️

| Valor API (`nodisponible`) | Status Interpretado | Disponible |
|----------------------------|---------------------|------------|
| `1` / `true` | `false` | ❌ No (NO disponible) |
| `0` / `false` | `true` | ✅ Sí (disponible) |

## 💡 Ejemplos

### Ejemplo 1: Propiedad Disponible en Inmovilla

**API devuelve**:
```json
{
  "cod_ofer": "12345",
  "referencia": "REF-001",
  "nodisponible": 0
}
```

**Procesamiento**:
```php
$property_info = API::get_property_info( $property, 'inmovilla' );

// Paso 1: Lee nodisponible = 0
// Paso 2: Detecta que es Inmovilla y el campo es 'nodisponible'
// Paso 3: Invierte lógica: ! (bool) 0 = ! false = true
// Resultado: $property_info['status'] = true ✅
```

**Resultado**: Propiedad **disponible** ✅

### Ejemplo 2: Propiedad NO Disponible en Inmovilla Procesos

**API devuelve**:
```json
{
  "cod_ofer": "67890",
  "ref": "REF-002",
  "nodisponible": 1
}
```

**Procesamiento**:
```php
$property_info = API::get_property_info( $property, 'inmovilla_procesos' );

// Paso 1: Lee nodisponible = 1
// Paso 2: Detecta que es Inmovilla Procesos y el campo es 'nodisponible'
// Paso 3: Invierte lógica: ! (bool) 1 = ! true = false
// Resultado: $property_info['status'] = false ✅
```

**Resultado**: Propiedad **NO disponible** ❌

### Ejemplo 3: Propiedad en Anaconda (Sin Inversión)

**API devuelve**:
```json
{
  "id": "999",
  "status": 1
}
```

**Procesamiento**:
```php
$property_info = API::get_property_info( $property, 'anaconda' );

// Paso 1: Lee status = 1
// Paso 2: NO es Inmovilla ni Inmovilla Procesos
// Paso 3: NO invierte lógica
// Resultado: $property_info['status'] = 1 ✅
```

**Resultado**: Propiedad **disponible** ✅

## 🔄 Consistencia con `is_property_available()`

La función `is_property_available()` ya manejaba correctamente la lógica inversa:

```php
public static function is_property_available( $property, $crm ) {
    if ( isset( $property['status'] ) ) {
        return (bool) $property['status'];
    }

    if ( 'inmovilla_procesos' === $crm ) {
        // Check nodisponible field (1 = not available, 0 = available).
        return ! isset( $property['nodisponible'] ) || 1 !== (int) $property['nodisponible'];
    } elseif ( 'inmovilla' === $crm ) {
        // Check estado field in Inmovilla APIWEB.
        return ! isset( $property['estado'] ) || 'V' !== $property['estado'];
    } elseif ( 'anaconda' === $crm ) {
        // Check operation_status field in Anaconda.
        return ! isset( $property['operation_status'] ) || 'Vendido' !== $property['operation_status'];
    }

    return true;
}
```

**Ahora ambas funciones son consistentes**:
- `get_property_info()` → Normaliza el status (invierte si es necesario)
- `is_property_available()` → Verifica si está disponible

## 🎯 Impacto en el Flujo

### 1. Al Obtener Información de Propiedad

```php
$property_info = API::get_property_info( $property, 'inmovilla' );

// Si nodisponible = 1
// $property_info['status'] = false (NO disponible) ✅

// Si nodisponible = 0
// $property_info['status'] = true (disponible) ✅
```

### 2. Al Filtrar Propiedades

```php
// En filter_properties_to_update()
$property_info = API::get_property_info( $property, 'inmovilla' );
$api_status = (bool) $property_info['status'];

if ( $api_status ) {
    // Solo importar si está disponible (status = true)
    $filtered[] = $property;
}
```

### 3. Al Calcular Estadísticas

```php
// En get_import_statistics()
foreach ( $api_properties as $prop_id => $prop_data ) {
    if ( SYNC::is_property_available( $prop_data, $crm_type ) ) {
        $available_properties[ $prop_id ] = $prop_data;
    }
}
```

## 📝 Casos de Prueba

### Test Case 1: Inmovilla con nodisponible = 0

```php
$property = array(
    'cod_ofer' => '123',
    'nodisponible' => 0,
);

$info = API::get_property_info( $property, 'inmovilla' );

// Assert
$this->assertTrue( (bool) $info['status'] ); // ✅ Disponible
```

### Test Case 2: Inmovilla con nodisponible = 1

```php
$property = array(
    'cod_ofer' => '456',
    'nodisponible' => 1,
);

$info = API::get_property_info( $property, 'inmovilla' );

// Assert
$this->assertFalse( (bool) $info['status'] ); // ❌ NO disponible
```

### Test Case 3: Inmovilla Procesos con nodisponible = 0

```php
$property = array(
    'cod_ofer' => '789',
    'nodisponible' => 0,
);

$info = API::get_property_info( $property, 'inmovilla_procesos' );

// Assert
$this->assertTrue( (bool) $info['status'] ); // ✅ Disponible
```

### Test Case 4: Anaconda (sin inversión)

```php
$property = array(
    'id' => '999',
    'status' => 1,
);

$info = API::get_property_info( $property, 'anaconda' );

// Assert
$this->assertEquals( 1, $info['status'] ); // ✅ Mantiene valor original
```

## 🐛 Debugging

Para depurar el procesamiento de status:

```php
// En get_property_info()

$status_value = $property[ $fields['status'] ];

error_log( sprintf(
    'CRM: %s | Field: %s | Value: %s | Before inversion: %s',
    $crm_type,
    $fields['status'],
    var_export( $property[ $fields['status'] ], true ),
    var_export( $status_value, true )
) );

if ( in_array( $crm_type, array( 'inmovilla', 'inmovilla_procesos' ), true ) && 
     'nodisponible' === $fields['status'] ) {
    $status_value = ! (bool) $status_value;
    
    error_log( sprintf(
        'After inversion: %s',
        var_export( $status_value, true )
    ) );
}
```

## ⚙️ Configuración del Mapping

El mapping de campos en `get_property_info()`:

```php
$match = array(
    'anaconda' => array(
        'status' => 'status',  // Lógica directa
    ),
    'inmovilla' => array(
        'status' => 'nodisponible',  // ⚠️ Lógica inversa
    ),
    'inmovilla_procesos' => array(
        'status' => 'nodisponible',  // ⚠️ Lógica inversa
    ),
);
```

## 📚 Referencias

- `/includes/class-helper-api.php` - Método `get_property_info()`
- `/includes/class-helper-sync.php` - Método `is_property_available()`
- `/docs/filter-unavailable-new-properties.md` - Filtrado de propiedades
- `/docs/import-statistics-filtering.md` - Estadísticas con filtrado

## ⚠️ Notas Importantes

### 1. Doble Negación

Cuidado con la doble negación en el código:

```php
// ❌ MAL: Doble negación innecesaria
if ( ! $nodisponible ) {
    // Disponible
}

// ✅ BIEN: Usar el status normalizado
$property_info = API::get_property_info( $property, $crm_type );
if ( $property_info['status'] ) {
    // Disponible
}
```

### 2. Tipo de Datos

El status siempre se debe convertir a booleano para comparaciones:

```php
$api_status = (bool) $property_info['status'];

if ( $api_status ) {
    // Disponible
}
```

### 3. Campos Alternativos

Inmovilla (APIWEB) también usa campo `estado`:
- `estado = 'V'` → Vendido (NO disponible)
- `estado != 'V'` → Disponible

Este caso se maneja en `is_property_available()` pero no en `get_property_info()` porque:
- `get_property_info()` normaliza a un status booleano estándar
- `is_property_available()` verifica múltiples campos para determinar disponibilidad

## ✅ Verificación

### Tests Pasando

```bash
composer test
# OK (32 tests, 102 assertions)
```

### Linting

```bash
composer lint
# FOUND 0 ERRORS
```

### Funcionamiento Esperado

Para Inmovilla e Inmovilla Procesos:

| `nodisponible` en API | `status` normalizado | `is_property_available()` | Importar |
|----------------------|---------------------|---------------------------|----------|
| `0` | `true` | `true` | ✅ Sí |
| `1` | `false` | `false` | ❌ No (si es nueva) |

Para Anaconda:

| `status` en API | `status` normalizado | `is_property_available()` | Importar |
|----------------|---------------------|---------------------------|----------|
| `1` / `true` | `1` / `true` | `true` | ✅ Sí |
| `0` / `false` | `0` / `false` | `false` | ❌ No (si es nueva) |

---

**Versión**: 1.0.0  
**Fecha**: 24 de enero de 2026  
**Autor**: David Perez
