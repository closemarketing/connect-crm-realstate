# API Configuration Centralized

## 📋 Descripción

Toda la configuración técnica de las APIs (Anaconda, Inmovilla, Inmovilla Procesos) está centralizada en un único método estático en la clase `API`.

## 🎯 Objetivo

Evitar duplicación de código y asegurar consistencia en toda la aplicación al tener una única fuente de verdad para:
- Timeouts de peticiones
- Paginación
- Configuración de reintentos
- Nombres de APIs

## 📍 Ubicación

**Clase**: `Close\ConnectCRM\RealState\API`  
**Método**: `API::get_api_config( $crm_type = '' )`  
**Archivo**: `/includes/class-helper-api.php`

## 🔧 Uso

### Obtener Configuración de una API Específica

```php
// Obtener configuración de Anaconda
$config = API::get_api_config( 'anaconda' );

// Resultado:
array(
    'name'             => 'Anaconda',
    'timeout'          => 300,  // 5 minutos en segundos
    'pagination'       => 200,
    'retry_timeout'    => 30,   // 30 segundos
    'retry_rate_limit' => 300,  // 5 minutos
    'retry_server'     => 120,  // 2 minutos
    'max_retries'      => 3,
)
```

### Obtener Todas las Configuraciones

```php
// Sin parámetro devuelve array con todas las APIs
$all_configs = API::get_api_config();

// Resultado:
array(
    'anaconda'           => array( ... ),
    'inmovilla'          => array( ... ),
    'inmovilla_procesos' => array( ... ),
)
```

## 📊 Configuración por API

### Anaconda
```php
'anaconda' => array(
    'name'             => 'Anaconda',
    'timeout'          => 300,   // 5 minutos
    'pagination'       => 200,   // Propiedades por página
    'retry_timeout'    => 30,    // Reintento por timeout
    'retry_rate_limit' => 300,   // Reintento por rate limit
    'retry_server'     => 120,   // Reintento por error servidor
    'max_retries'      => 3,     // Máximo reintentos
)
```

### Inmovilla
```php
'inmovilla' => array(
    'name'             => 'Inmovilla',
    'timeout'          => 60,    // 1 minuto
    'pagination'       => 50,    // Propiedades por página
    'retry_timeout'    => 30,    // Reintento por timeout
    'retry_rate_limit' => 300,   // Reintento por rate limit
    'retry_server'     => 120,   // Reintento por error servidor
    'max_retries'      => 3,     // Máximo reintentos
)
```

### Inmovilla Procesos
```php
'inmovilla_procesos' => array(
    'name'             => 'Inmovilla Procesos',
    'timeout'          => 300,   // 5 minutos
    'pagination'       => -1,    // Todas a la vez
    'retry_timeout'    => 30,    // Reintento por timeout
    'retry_rate_limit' => 300,   // Reintento por rate limit
    'retry_server'     => 120,   // Reintento por error servidor
    'max_retries'      => 3,     // Máximo reintentos
)
```

## 🔗 Uso en el Código

### 1. Peticiones HTTP (class-helper-api.php)

**ANTES** (Hardcoded):
```php
$args = array(
    'method'  => 'GET',
    'headers' => array( ... ),
    'timeout' => 300,  // ❌ Hardcoded
);
```

**AHORA** (Centralizado):
```php
$api_config = self::get_api_config( 'anaconda' );
$args       = array(
    'method'  => 'GET',
    'headers' => array( ... ),
    'timeout' => $api_config['timeout'],  // ✅ Desde config
);
```

### 2. UI Admin (class-iip-admin.php)

**ANTES** (Duplicado):
```php
$api_info = array(
    'anaconda' => array(
        'name'        => 'Anaconda',
        'timeout'     => '5 minutes',
        'pagination'  => 200,
        'max_retries' => 3,  // ❌ Duplicado
    ),
    // ...
);
```

**AHORA** (Desde API):
```php
$api_config = API::get_api_config( $crm_type );

// Formatear para UI
$timeout_minutes = $api_config['timeout'] / 60;
$timeout_display = $timeout_minutes . ' ' . __( 'minutes', 'connect-crm-realstate' );
```

### 3. Sistema de Reintentos (class-helper-api.php)

Los valores de reintento se obtienen directamente desde las constantes:
```php
'retry_timeout'    => self::RETRY_CONFIG['timeout']['wait'],
'retry_rate_limit' => self::RETRY_CONFIG['rate_limit']['wait'],
'retry_server'     => self::RETRY_CONFIG['server_error']['wait'],
'max_retries'      => self::MAX_RETRIES,
```

## 🎯 Beneficios

### 1. **Única Fuente de Verdad**
- ✅ Un solo lugar para modificar configuraciones
- ✅ Evita inconsistencias
- ✅ Más fácil de mantener

### 2. **Reutilización**
- ✅ Usado en peticiones HTTP
- ✅ Usado en UI de admin
- ✅ Usado en documentación
- ✅ Usado en cualquier lugar que lo necesite

### 3. **Consistencia**
- ✅ Todos los componentes usan los mismos valores
- ✅ Cambios se propagan automáticamente
- ✅ No hay valores "olvidados"

### 4. **Documentación**
- ✅ La configuración sirve como documentación
- ✅ Comentarios claros en cada valor
- ✅ Fácil de entender

## 📝 Modificar Configuración

Para cambiar cualquier valor, editar **solo** el método `get_api_config()`:

```php
// includes/class-helper-api.php

public static function get_api_config( $crm_type = '' ) {
    $config = array(
        'anaconda' => array(
            'timeout' => 600,  // Cambiar de 300 a 600 (10 minutos)
            // ...
        ),
    );
    // ...
}
```

Este cambio se aplicará automáticamente en:
- ✅ Todas las peticiones HTTP a Anaconda
- ✅ UI de limitaciones en admin
- ✅ Cualquier código que use `get_api_config()`

## ⚠️ Notas Importantes

### Formato de Timeouts

Los timeouts están en **segundos** en la configuración:
- `60` = 1 minuto
- `300` = 5 minutos
- `600` = 10 minutos

Para mostrar en UI, convertir a minutos:
```php
$timeout_minutes = $api_config['timeout'] / 60;
```

### Paginación Especial

Inmovilla Procesos usa `-1` para indicar "todas a la vez":
```php
'pagination' => -1,  // No hay paginación, todas las propiedades
```

En UI se muestra como:
```php
$pagination_display = -1 === $api_config['pagination']
    ? __( 'All at once', 'connect-crm-realstate' )
    : $api_config['pagination'];
```

## 🔗 Archivos Relacionados

- `/includes/class-helper-api.php` - Definición de `get_api_config()`
- `/includes/class-iip-admin.php` - Uso en UI de admin
- `/docs/api-retry-system.md` - Sistema de reintentos
- `/docs/api-limitations-ui.md` - UI de limitaciones

## 🧪 Testing

Los tests verifican que:
- ✅ La configuración existe para cada API
- ✅ Los valores son correctos
- ✅ Se usan en las peticiones HTTP

```bash
composer test
# OK (31 tests, 100 assertions)
```

## 📚 Ejemplo Completo

```php
// Ejemplo: Hacer una petición con timeout correcto

$crm_type   = 'anaconda';
$api_config = API::get_api_config( $crm_type );

// Usar timeout desde config
$args = array(
    'method'  => 'GET',
    'headers' => array( 'Authorization' => 'Bearer ' . $token ),
    'timeout' => $api_config['timeout'],  // 300 segundos
);

$response = wp_remote_get( $url, $args );

// Mostrar info en UI
echo sprintf(
    'Timeout: %d minutos',
    $api_config['timeout'] / 60
);
// Output: "Timeout: 5 minutos"
```

---

**Versión**: 1.0.0  
**Fecha**: 24 de enero de 2026  
**Autor**: David Perez
