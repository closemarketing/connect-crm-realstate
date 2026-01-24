# CSS Assets Separation - Admin Import Styles

## 📋 Descripción

Se han movido todos los estilos CSS inline del archivo PHP a un archivo CSS separado siguiendo las mejores prácticas de WordPress.

## 🎯 Problema Resuelto

**ANTES** ❌:
- Estilos CSS dentro del HTML/PHP (~164 líneas)
- Difícil de mantener y modificar
- No aprovecha el sistema de encolado de WordPress
- No se puede cachear por el navegador

**AHORA** ✅:
- Estilos en archivo CSS separado
- Correctamente encolado con `wp_enqueue_style()`
- Cacheable por el navegador
- Más fácil de mantener y modificar

## 📁 Archivos

### Nuevo Archivo CSS
**Ubicación**: `/assets/css/admin-import.css`
**Tamaño**: ~5KB  
**Contenido**: Todos los estilos de la página de importación manual

### Archivo PHP Modificado
**Ubicación**: `/includes/class-iip-admin.php`
**Cambios**:
- ✅ Eliminado bloque `<style>` inline (~164 líneas)
- ✅ Agregado encolado en `enqueue_admin_scripts()`

## 🔧 Implementación

### 1. Creación del Archivo CSS

```css
/* /assets/css/admin-import.css */

/* Statistics Cards */
.ccrmre-import-stats { ... }
.ccrmre-stat-card { ... }

/* Import Controls */
.connect-realstate-manual-action .import-button-wrapper { ... }
#import-mode { ... }

/* Log Wrapper */
.connect-realstate-manual-action #logwrapper { ... }

/* API Limitations Info */
.api-limitations-info { ... }
```

### 2. Encolado del CSS

```php
// /includes/class-iip-admin.php

public function enqueue_admin_scripts( $hook ) {
    if ( 'toplevel_page_iip-options' !== $hook ) {
        return;
    }

    $active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

    // Enqueue import styles on manual import tab.
    if ( 'iip-manual-import' === $active_tab ) {
        wp_enqueue_style(
            'ccrmre-admin-import',
            CCRMRE_PLUGIN_URL . 'assets/css/admin-import.css',
            array(),
            CCRMRE_VERSION
        );
    }
    // ...
}
```

### 3. Eliminación del Inline CSS

**ANTES**:
```php
<style>
    .ccrmre-import-stats { display: grid; ... }
    .ccrmre-stat-card { background: white; ... }
    /* ... 160+ líneas más ... */
</style>
```

**AHORA**:
```php
<!-- Sin estilos inline, todo en archivo CSS -->
```

## 📊 Beneficios

### 1. **Rendimiento**
- ✅ El navegador puede cachear el CSS
- ✅ Menos bytes en el HTML inicial
- ✅ Carga paralela de recursos

### 2. **Mantenibilidad**
- ✅ CSS separado del PHP
- ✅ Más fácil de editar y modificar
- ✅ Sintaxis highlighting en editor

### 3. **Mejores Prácticas WordPress**
- ✅ Uso correcto de `wp_enqueue_style()`
- ✅ Versionado con `CCRMRE_VERSION`
- ✅ Carga condicional (solo en tab correcto)

### 4. **Organización**
- ✅ Archivos CSS en carpeta `assets/css/`
- ✅ Estructura clara del proyecto
- ✅ Fácil de encontrar y modificar

## 🗂️ Estructura de Estilos

El archivo CSS está organizado en secciones:

```css
/* Statistics Cards */
.ccrmre-import-stats         /* Grid container */
.ccrmre-stat-card           /* Individual stat card */
.ccrmre-stat-icon           /* Icon container */
.ccrmre-icon-api            /* API icon styles */
.ccrmre-icon-wp             /* WordPress icon styles */
.ccrmre-icon-import         /* Import icon styles */
.ccrmre-icon-delete         /* Delete icon styles */
.ccrmre-stat-content        /* Stat content */
.ccrmre-stat-value          /* Value number */
.ccrmre-stat-label          /* Label text */
.ccrmre-stat-sublabel       /* Sublabel text */

/* Import Controls */
.import-button-wrapper      /* Button container */
#import-mode                /* Mode selector */
#manual_import              /* Import button */
#refresh_stats              /* Refresh button */
.spinner                    /* Loading spinner */

/* Log Wrapper */
#logwrapper                 /* Log container */
#loglist                    /* Log list */
#loglist p                  /* Log messages */
#loglist p.odd              /* Odd rows */
#loglist p.even             /* Even rows */
#loglist p.error            /* Error messages */

/* API Limitations Info */
.api-limitations-info       /* Info box */
.api-limitations-info h4    /* Heading */
.api-limitations-info ul    /* List */
.api-limitations-info li    /* List items */
```

## 🎨 Clases CSS Principales

### Grid de Estadísticas
- `.ccrmre-import-stats` - Contenedor grid responsive
- `.ccrmre-stat-card` - Tarjeta individual con hover effect

### Iconos Coloreados
- `.ccrmre-icon-api` - Azul (#0073aa)
- `.ccrmre-icon-wp` - Gris (#2c3338)
- `.ccrmre-icon-import` - Verde (#00a32a)
- `.ccrmre-icon-delete` - Rojo (#d63638)

### Controles
- `#import-mode` - Select del modo de importación
- `#manual_import` - Botón de inicio
- `#refresh_stats` - Botón de refrescar

### Log
- `#logwrapper` - Contenedor del log
- `#loglist` - Lista scrollable
- `#loglist p.error` - Mensajes de error

## 🔄 Carga Condicional

El CSS solo se carga cuando:

1. ✅ El usuario está en la página del plugin (`toplevel_page_iip-options`)
2. ✅ La pestaña activa es `iip-manual-import`

```php
if ( 'toplevel_page_iip-options' !== $hook ) {
    return; // No cargar
}

$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

if ( 'iip-manual-import' === $active_tab ) {
    wp_enqueue_style( 'ccrmre-admin-import', ... ); // Cargar solo aquí
}
```

## 📝 Modificar Estilos

Para modificar los estilos:

1. **Editar**: `/assets/css/admin-import.css`
2. **Incrementar versión** (opcional): Si quieres forzar recarga, cambiar `CCRMRE_VERSION` en `connect-crm-realstate.php`
3. **Limpiar cache**: Los navegadores cachearán según la versión

### Ejemplo:

```css
/* Cambiar color del botón de importación */
.connect-realstate-manual-action #manual_import {
    background-color: #00a32a; /* Verde personalizado */
}

/* Aumentar tamaño de las tarjetas */
.ccrmre-stat-card {
    padding: 30px; /* Era 20px */
}
```

## 🧪 Testing

### Verificar Carga del CSS

1. Ir a la página de importación manual
2. Abrir DevTools > Network
3. Buscar `admin-import.css`
4. Verificar que se carga con código 200

### Verificar Estilos Aplicados

1. Inspeccionar elemento `.ccrmre-stat-card`
2. En "Styles" debe aparecer `admin-import.css:XX`
3. No debe haber estilos inline en el HTML

## 📏 Métricas

### Reducción de Código PHP
- **Antes**: 1300 líneas en `class-iip-admin.php`
- **Después**: 1136 líneas (164 líneas menos = -12.6%)

### Nuevo Archivo CSS
- **Tamaño**: ~5KB
- **Líneas**: ~223 líneas
- **Comentarios**: Bien documentado con secciones

## 🔗 Archivos Relacionados

- `/assets/css/admin-import.css` - Estilos de importación
- `/includes/class-iip-admin.php` - Clase admin (enqueue)
- `/includes/assets/iip-styles-admin.css` - Otros estilos admin
- `/includes/assets/iip-merge-fields.css` - Estilos merge fields

## 📚 Referencias WordPress

- [wp_enqueue_style()](https://developer.wordpress.org/reference/functions/wp_enqueue_style/)
- [admin_enqueue_scripts](https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/)
- [Stylesheets Best Practices](https://developer.wordpress.org/themes/basics/including-css-javascript/#stylesheets)

---

**Versión**: 1.0.0  
**Fecha**: 24 de enero de 2026  
**Autor**: David Perez
