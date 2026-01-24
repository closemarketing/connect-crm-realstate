# API Limitations UI Display

## 📋 Descripción

Se ha agregado una sección informativa en la página de importación manual que muestra las limitaciones específicas de cada API (Anaconda, Inmovilla, Inmovilla Procesos).

## 📍 Ubicación

La información se muestra **después de los botones de importación** en la página de importación manual (`/wp-admin/admin.php?page=connect-crm-realstate`).

## 🎨 Diseño

### Elementos Visuales

- **Fondo**: Azul claro (#f0f6fc)
- **Borde Izquierdo**: Azul WordPress (#2271b1)
- **Icono**: Dashicon "info"
- **Estilo**: Card con bordes redondeados

### Información Mostrada

Para cada tipo de API se muestra:

1. ⏱️ **Request Timeout** - Tiempo máximo de espera por petición
2. 📊 **Properties per Request** - Cantidad de propiedades por petición
3. 🔄 **Automatic Retries** - Número máximo de reintentos
4. ⏲️ **Retry Wait Time** - Tiempos de espera entre reintentos

## 📊 Configuración por API

### Anaconda
```
- Timeout: 5 minutos
- Propiedades por petición: 200
- Reintentos: Hasta 3 intentos
- Tiempos de espera: 30s (timeout) / 5min (rate limit)
```

### Inmovilla
```
- Timeout: 1 minuto
- Propiedades por petición: 50
- Reintentos: Hasta 3 intentos
- Tiempos de espera: 30s (timeout) / 5min (rate limit)
```

### Inmovilla Procesos
```
- Timeout: 5 minutos
- Propiedades por petición: Todas a la vez
- Reintentos: Hasta 3 intentos
- Tiempos de espera: 30s (timeout) / 5min (rate limit)
```

## 💡 Mensaje Informativo

> "El sistema reintentará automáticamente las peticiones fallidas con tiempos de espera inteligentes según el tipo de error."

Este mensaje explica que:
- Los reintentos son **automáticos**
- Los tiempos de espera son **inteligentes**
- Se adaptan según el **tipo de error**

## 🔧 Implementación

### Código PHP

```php
// Ubicación: includes/class-iip-admin.php, línea ~708

$api_info = array(
    'anaconda' => array(
        'name'             => 'Anaconda',
        'timeout'          => '5 ' . __( 'minutes', 'connect-crm-realstate' ),
        'pagination'       => 200,
        'retry_timeout'    => '30 ' . __( 'seconds', 'connect-crm-realstate' ),
        'retry_rate_limit' => '5 ' . __( 'minutes', 'connect-crm-realstate' ),
        'max_retries'      => 3,
    ),
    // ... más configuraciones
);
```

### CSS Inline

```css
.api-limitations-info {
    margin-top: 15px;
    padding: 12px;
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    border-radius: 4px;
}
```

## 🌍 Traducciones

### Strings Traducidos

| English | Español |
|---------|---------|
| API Limitations - %s | Limitaciones de API - %s |
| Request Timeout: | Tiempo de espera de petición: |
| Properties per Request: | Propiedades por petición: |
| Automatic Retries: | Reintentos automáticos: |
| Up to %d attempts | Hasta %d intentos |
| Retry Wait Time: | Tiempo de espera entre reintentos: |
| %1$s (timeout) / %2$s (rate limit) | %1$s (timeout) / %2$s (límite de peticiones) |
| The system will automatically retry... | El sistema reintentará automáticamente... |
| minutes | minutos |
| minute | minuto |
| seconds | segundos |
| All at once | Todas a la vez |

## 📱 Responsive

La tarjeta de información se adapta automáticamente al ancho de la pantalla y se muestra correctamente en todos los dispositivos.

## 🎯 Beneficios

1. ✅ **Transparencia**: El usuario sabe exactamente qué esperar
2. ✅ **Educativo**: Explica las limitaciones técnicas
3. ✅ **Contexto**: Información relevante justo cuando se necesita
4. ✅ **Profesional**: Diseño consistente con WordPress
5. ✅ **Traducido**: Totalmente en español e inglés

## 🔗 Relacionado

- Sistema de reintentos automáticos: `/docs/api-retry-system.md`
- Clase API: `includes/class-helper-api.php`
- Admin UI: `includes/class-iip-admin.php`

---

**Versión**: 1.0.0
**Fecha**: 24 de enero de 2026
**Autor**: David Perez
