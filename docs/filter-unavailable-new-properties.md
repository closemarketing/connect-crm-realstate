# Filter Unavailable New Properties

## 📋 Descripción

La función `filter_properties_to_update()` ahora **NO importa propiedades nuevas si su estado es false** (no disponibles). Solo se importan propiedades nuevas que estén disponibles.

## 🎯 Problema Resuelto

**ANTES** ❌:
- Todas las propiedades nuevas se importaban, sin verificar su disponibilidad
- Se creaban posts en WordPress para propiedades no disponibles
- Ocupaba espacio y generaba confusión

**AHORA** ✅:
- Solo se importan propiedades nuevas si `status = true` (disponibles)
- Propiedades nuevas con `status = false` se ignoran
- WordPress solo contiene propiedades que realmente están disponibles

## 🔧 Implementación

### Archivo Modificado
`/includes/class-helper-sync.php`

### Método Actualizado
`filter_properties_to_update()`

### Cambio Realizado

**ANTES**:
```php
// Check if property is new.
if ( ! in_array( $property_id, $wp_ids, true ) ) {
    $filtered[] = $property;  // ❌ Siempre agregaba nuevas
    continue;
}
```

**AHORA**:
```php
// Check if property is new.
if ( ! in_array( $property_id, $wp_ids, true ) ) {
    // Only import new properties if they are available (status = true).
    $api_status = (bool) $property_info['status'];
    if ( $api_status ) {
        $filtered[] = $property;  // ✅ Solo si está disponible
    }
    continue;
}
```

## 📊 Flujo de Decisión

```
Propiedad Nueva desde API
         ↓
¿Existe en WordPress?
         ↓
        NO (es nueva)
         ↓
¿Status = true (disponible)?
    ↙         ↘
  SÍ          NO
   ↓           ↓
Importar    Ignorar
```

## 💡 Ejemplos

### Ejemplo 1: Propiedades Nuevas Mixtas

**API devuelve**:
```php
array(
    array('id' => '1', 'status' => '1'),  // Disponible
    array('id' => '2', 'status' => '0'),  // No disponible
    array('id' => '3', 'status' => true), // Disponible
    array('id' => '4', 'status' => false),// No disponible
)
```

**Resultado**:
- ✅ Propiedad 1: Se importa (nueva y disponible)
- ❌ Propiedad 2: Se ignora (nueva pero no disponible)
- ✅ Propiedad 3: Se importa (nueva y disponible)
- ❌ Propiedad 4: Se ignora (nueva pero no disponible)

### Ejemplo 2: Propiedad Existente que se Vuelve Disponible

**WordPress tiene**:
```php
array('id' => '100', 'status' => false)  // Ya existe, no disponible
```

**API devuelve**:
```php
array('id' => '100', 'status' => true)   // Ahora disponible
```

**Resultado**:
- ✅ Se actualiza (ya existe, cambió el status)

## 🔗 Relación con `is_property_available()`

La función `is_property_available()` se usa durante la **importación manual** para verificar disponibilidad:

```php
// Durante importación manual
$is_available = SYNC::is_property_available( $property, $crm );

if ( ! $is_available ) {
    // Manejar según configuración (skip, unpublish, trash, keep)
    $result = SYNC::handle_unavailable_property( ... );
}
```

Ahora, el filtro `filter_properties_to_update()` usa la **misma lógica** pero aplicada a propiedades **nuevas**:

```php
// Durante filtrado
$api_status = (bool) $property_info['status'];
if ( $api_status ) {
    // Solo importar si está disponible
}
```

## 🎯 Beneficios

### 1. **Consistencia**
- ✅ Mismo criterio para propiedades nuevas y existentes
- ✅ No se crean posts innecesarios

### 2. **Rendimiento**
- ✅ Menos propiedades a procesar
- ✅ Menos consultas a la base de datos
- ✅ Menos espacio ocupado

### 3. **Calidad de Datos**
- ✅ WordPress solo contiene propiedades disponibles
- ✅ Menos confusión para el usuario
- ✅ Listings más limpios

### 4. **Estadísticas Precisas**
- ✅ "To Import/Update" muestra solo disponibles
- ✅ Números reflejan lo que realmente se importará

## 🧪 Testing

### Test Agregado
`test_filter_skips_new_unavailable_properties()`

```php
// API tiene 3 propiedades nuevas:
// - 2 no disponibles (status = '0' y false)
// - 1 disponible (status = '1')

$api_properties = array(
    array('id' => 'NEW_UNAVAIL_1', 'status' => '0'),    // No disponible
    array('id' => 'NEW_AVAIL_1',   'status' => '1'),    // Disponible
    array('id' => 'NEW_UNAVAIL_2', 'status' => false),  // No disponible
);

$filtered = SYNC::filter_properties_to_update( $api_properties, 'anaconda' );

// Resultado: Solo 1 propiedad (la disponible)
$this->assertCount( 1, $filtered );
$this->assertEquals( 'NEW_AVAIL_1', $filtered[0]['id'] );
```

### Tests Actualizados

Se actualizaron todos los tests existentes para usar `status = '1'` (disponible) en lugar de `'0'`:

- `test_filter_all_new_properties()`
- `test_filter_new_properties()`
- `test_filter_single_property_with_updated_date()`
- `test_filter_single_property_with_changed_status()`
- Y otros 8 tests más

### Verificación

```bash
composer test
# OK (32 tests, 103 assertions)
```

## 📝 Casos de Uso

### Caso 1: Primera Importación

**Escenario**: Primera sincronización con 100 propiedades en la API

**API**:
- 70 propiedades disponibles
- 30 propiedades vendidas/no disponibles

**Resultado**:
- ✅ Se importan 70 propiedades
- ❌ Se ignoran 30 propiedades

### Caso 2: Importación Incremental

**WordPress tiene**: 50 propiedades
**API devuelve**: 60 propiedades (10 nuevas)

**De las 10 nuevas**:
- 7 disponibles → ✅ Se importan
- 3 no disponibles → ❌ Se ignoran

**Total en WP después**: 57 propiedades (50 + 7)

### Caso 3: Propiedad se Vuelve Disponible

**Estado inicial**:
- WordPress: NO tiene la propiedad
- API (semana 1): Propiedad X con `status = false`

**Primera sincronización**:
- ❌ Propiedad X no se importa (nueva y no disponible)

**API (semana 2)**: Propiedad X con `status = true`

**Segunda sincronización**:
- ✅ Propiedad X se importa (ahora está disponible)

## ⚙️ Configuración

No requiere configuración adicional. El comportamiento es automático basado en:

1. **Campo status**: Valor booleano o string convertible
2. **Lógica**: `(bool) $property_info['status']`
3. **Aplicación**: Solo para propiedades **nuevas**

## 🔍 Verificación en el Código

### 1. En `filter_properties_to_update()`

```php
// Línea ~494
if ( ! in_array( $property_id, $wp_ids, true ) ) {
    // Only import new properties if they are available (status = true).
    $api_status = (bool) $property_info['status'];
    if ( $api_status ) {
        $filtered[] = $property;
    }
    continue;
}
```

### 2. En `is_property_available()`

```php
// Línea ~288
public static function is_property_available( $property, $crm ) {
    if ( isset( $property['status'] ) ) {
        return (bool) $property['status'];
    }
    // ... verificaciones específicas por CRM
}
```

### 3. En `get_import_statistics()`

```php
// Línea ~299
$available_properties = array();
foreach ( $api_properties as $prop_id => $prop_data ) {
    if ( SYNC::is_property_available( $prop_data, $crm_type ) ) {
        $available_properties[ $prop_id ] = $prop_data;
    }
}
```

## 📊 Impacto en Estadísticas

### Dashboard de Importación

**Antes del cambio**:
```
API Count: 100
To Import/Update: 80
  - New: 50
  - Outdated: 30
```

**Después del cambio**:
```
API Count: 100 (sin cambio)
To Import/Update: 60  ← Menos
  - New: 30  ← Solo disponibles
  - Outdated: 30 (sin cambio)
```

**Diferencia**: 20 propiedades menos (las nuevas no disponibles)

## 🐛 Debugging

Para depurar el filtrado:

```php
// En filter_properties_to_update()

foreach ( $properties as $property ) {
    $property_info = API::get_property_info( $property, $crm_type );
    $property_id   = ! empty( $property_info['id'] ) ? $property_info['id'] : $property_info['reference'];

    if ( ! in_array( $property_id, $wp_ids, true ) ) {
        $api_status = (bool) $property_info['status'];
        
        error_log( sprintf(
            'New property %s - Status: %s - Will import: %s',
            $property_id,
            var_export( $property_info['status'], true ),
            $api_status ? 'YES' : 'NO'
        ) );
        
        if ( $api_status ) {
            $filtered[] = $property;
        }
        continue;
    }
}
```

## 📚 Referencias

- `/includes/class-helper-sync.php` - Método `filter_properties_to_update()`
- `/includes/class-helper-sync.php` - Método `is_property_available()`
- `/includes/class-iip-import.php` - Método `get_import_statistics()`
- `/tests/Unit/ImportFilterPropertiesTest.php` - Tests actualizados
- `/docs/import-statistics-filtering.md` - Filtrado en estadísticas

## ⚠️ Notas Importantes

### 1. Solo Aplica a Propiedades Nuevas

Este filtro **SOLO** afecta propiedades que:
- No existen en WordPress
- Son nuevas desde la API

**NO afecta** propiedades que:
- Ya existen en WordPress
- Se están actualizando

### 2. Propiedades Existentes

Si una propiedad ya existe en WordPress con `status = true` y la API la devuelve con `status = false`:
- ✅ SÍ se procesará (para actualizar/despublicar según configuración)
- ✅ Esto se maneja en `handle_unavailable_property()`

### 3. Conversión de Status

El status se convierte a booleano:
```php
(bool) '1'    // true  ✅ Disponible
(bool) '0'    // false ❌ No disponible
(bool) 1      // true  ✅ Disponible
(bool) 0      // false ❌ No disponible
(bool) true   // true  ✅ Disponible
(bool) false  // false ❌ No disponible
```

---

**Versión**: 1.0.0  
**Fecha**: 24 de enero de 2026  
**Autor**: David Perez
