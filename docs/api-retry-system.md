# Sistema de Reintentos Automáticos de API

## 📋 Descripción

El plugin implementa un sistema inteligente de reintentos automáticos para todas las APIs (Anaconda, Inmovilla, Inmovilla Procesos) que maneja errores de forma elegante y reintenta automáticamente según el tipo de error.

## ⚙️ Configuración

### Constantes de Reintentos

```php
const MAX_RETRIES = 3; // Máximo 3 reintentos por petición
```

### Configuración por Tipo de Error

| Tipo de Error | Tiempo de Espera | Descripción |
|---------------|------------------|-------------|
| `timeout` | **30 segundos** | Error de timeout de conexión |
| `rate_limit` | **5 minutos (300s)** | Límite de peticiones alcanzado (HTTP 429) |
| `server_error` | **2 minutos (120s)** | Error del servidor (HTTP 5xx) |
| `default` | **1 minuto (60s)** | Cualquier otro error |

## 🔄 Flujo de Funcionamiento

### 1. **Detección de Error**

```php
private static function detect_error_type( $response, $code = 0 )
```

Detecta automáticamente el tipo de error:
- **Timeout**: `http_request_timeout`, `http_request_failed`
- **Rate Limit**: Código HTTP 429
- **Server Error**: Códigos HTTP >= 500
- **Default**: Cualquier otro error

### 2. **Ejecución con Reintentos**

```php
private static function execute_with_retry( $request_callback, $api_name = 'API' )
```

Para cada petición:
1. ✅ **Intento 1**: Ejecuta la petición
2. ❌ Si falla → Detecta tipo de error
3. ⏱️ Espera el tiempo configurado
4. 🔄 **Intento 2**: Reintenta automáticamente
5. Repite hasta máximo 3 intentos
6. Si todos fallan → Devuelve error final

### 3. **Logging Automático**

Cada reintento se registra en el log de errores de WordPress:

```
Anaconda API - Intento 1/3 falló. Esperando 30 segundos antes de reintentar...: Connection timeout
Inmovilla API - Intento 2/3 falló. Esperando 120 segundos antes de reintentar...: Server error 503
```

## 📊 Ejemplos de Uso

### Escenario 1: Timeout de Conexión

```
[Intento 1] → Timeout
⏱️ Espera 30 segundos
[Intento 2] → Timeout
⏱️ Espera 30 segundos
[Intento 3] → Timeout
⏱️ Espera 30 segundos
[Intento 4] → ❌ Error Final: "Anaconda API: Maximum retry attempts reached"
```

**Total tiempo**: ~90 segundos (3 × 30s)

### Escenario 2: Rate Limit (429)

```
[Intento 1] → HTTP 429 (Rate Limit)
⏱️ Espera 5 minutos
[Intento 2] → ✅ Éxito
```

**Total tiempo**: ~5 minutos

### Escenario 3: Error de Servidor (500)

```
[Intento 1] → HTTP 500
⏱️ Espera 2 minutos
[Intento 2] → HTTP 503
⏱️ Espera 2 minutos
[Intento 3] → ✅ Éxito
```

**Total tiempo**: ~4 minutos (2 × 2min)

## 🎯 Beneficios

### ✅ **Resiliencia**
- Maneja automáticamente errores temporales
- No requiere intervención manual del usuario
- Continúa la importación sin interrupciones

### ⚡ **Optimización**
- Tiempos de espera específicos por tipo de error
- No sobrecarga las APIs con reintentos rápidos
- Respeta rate limits automáticamente

### 📝 **Transparencia**
- Logging detallado en error_log
- Mensajes claros al usuario
- Información de progreso en tiempo real

### 🛡️ **Seguridad**
- Límite máximo de reintentos
- Previene bucles infinitos
- Timeouts configurados por API

## 📈 APIs Afectadas

### Todas las APIs usan el sistema de reintentos:

1. **Anaconda API**
   - `request_anaconda()`
   - Timeout: 300s por petición
   - Reintentos: 3 × 30s = +90s máx

2. **Inmovilla API**
   - `request_inmovilla()`
   - Timeout: 60s por petición
   - Reintentos: 3 × variable

3. **Inmovilla Procesos API**
   - `request_inmovilla_procesos()`
   - Timeout: 300s por petición
   - Reintentos: 3 × variable

## 🔧 Configuración Avanzada

### Modificar Tiempos de Espera

Editar `includes/class-helper-api.php`:

```php
const RETRY_CONFIG = array(
    'timeout'     => array(
        'wait'    => 30,  // Cambiar a 60 para esperar 1 minuto
        'message' => 'Connection timeout, retrying in %d seconds...',
    ),
    'rate_limit'  => array(
        'wait'    => 300, // Cambiar a 600 para esperar 10 minutos
        'message' => 'Rate limit reached, waiting %d seconds before retry...',
    ),
    // ... etc
);
```

### Modificar Número de Reintentos

```php
const MAX_RETRIES = 5; // Cambiar de 3 a 5 reintentos
```

## ⚠️ Consideraciones

### Tiempos Máximos de Ejecución

Con 3 reintentos, los tiempos máximos por petición son:

| Escenario | Tiempo Base | Reintentos | Total Máximo |
|-----------|-------------|------------|--------------|
| Timeout | 300s | 3 × 30s | ~390s (~6.5 min) |
| Rate Limit | 300s | 3 × 300s | ~1200s (~20 min) |
| Server Error | 300s | 3 × 120s | ~660s (~11 min) |

### PHP Max Execution Time

Asegúrate de que `max_execution_time` en PHP sea suficiente:

```php
set_time_limit(0); // Sin límite (recomendado para importaciones)
// O
ini_set('max_execution_time', 3600); // 1 hora
```

## 📚 Mensajes Traducidos

Todos los mensajes están traducidos al español:

- ✅ "Intento %1$d/%2$d falló. Esperando %3$d segundos antes de reintentar..."
- ✅ "Se alcanzó el número máximo de reintentos. Último error: "
- ✅ "Error desconocido de API"
- ✅ "Solicitud exitosa"

## 🧪 Testing

Los tests unitarios verifican que el sistema funciona correctamente:

```bash
composer test
# OK (31 tests, 100 assertions)
```

## 📝 Notas de Versión

**Versión**: 1.0.0-beta.13
**Fecha**: 2026-01-24
**Cambios**:
- ✅ Implementado sistema de reintentos automáticos
- ✅ Detección inteligente de tipo de error
- ✅ Tiempos de espera configurables
- ✅ Logging detallado
- ✅ Traducciones completas

---

**Documentación actualizada**: 24 de enero de 2026
