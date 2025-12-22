# Implementación API Inmovilla

## Descripción

Este documento describe la implementación de la API de Inmovilla en el plugin Connect CRM Real State, siguiendo la documentación oficial de Inmovilla.

## URL de la API

```
https://apiweb.inmovilla.com/apiweb/apiweb.php
```

## Configuración

Para usar la API de Inmovilla, necesitas configurar los siguientes parámetros en la configuración del plugin:

- **numagencia**: Número de agencia de Inmovilla
- **apipassword**: Contraseña de la API
- **addnumagencia**: Sufijo del usuario API (opcional, ej: `_000_ext`)

## Método Principal: `request_inmovilla()`

Este método implementa la funcionalidad de las funciones `Procesos()` y `PedirDatos()` de la API oficial de Inmovilla.

### Parámetros

```php
API::request_inmovilla(
    $tipo = 'paginacion',     // Tipo de datos a solicitar
    $pos_inicial = 1,         // Posición inicial
    $num_elementos = 50,      // Número de elementos
    $where = '',              // Cláusula WHERE para filtrar
    $orden = '',              // Cláusula ORDER para ordenar
    $idioma = 1               // ID del idioma (1 = Español)
);
```

### Tipos de Datos Disponibles

| Tipo | Descripción | Elementos Máximos |
|------|-------------|-------------------|
| `tipos` | Lista de tipos de propiedad | Sin límite |
| `provincias` | Lista de provincias | Sin límite |
| `provinciasofertas` | Provincias con propiedades | Sin límite |
| `ciudades` | Lista de ciudades con propiedades | Sin límite |
| `zonas` | Lista de zonas de una ciudad | Sin límite |
| `tipos_conservacion` | Estados de la propiedad | Sin límite |
| `paginacion` | Registros de propiedades | 50 registros |
| `destacados` | Propiedades destacadas | 30 registros |
| `ficha` | Detalle completo de una propiedad | 1 registro |
| `listar_propiedades_disponibles` | Códigos de propiedades disponibles | 5000 registros |
| `paginacion_promociones` | Listado de promociones de obra nueva | - |
| `ficha_promo` | Ficha de propiedad dentro de obra nueva | - |
| `alquilerdisponibilidad` | Periodos de ocupación | - |
| `alquilertemporada` | Temporadas de precios | - |

## Ejemplos de Uso

### 1. Obtener Propiedades Paginadas

```php
// Obtener 50 propiedades con ascensor, ordenadas por fecha descendente
$result = API::request_inmovilla(
    'paginacion',
    1,                    // Primera página
    50,                   // 50 elementos
    'ascensor=1',         // Con ascensor
    'fecha desc'          // Ordenadas por fecha
);

if ( 'ok' === $result['status'] ) {
    $propiedades = $result['data']['paginacion'];
    // $propiedades[0] contiene metadata (posicion, elementos, total)
    // $propiedades[1..n] contienen las propiedades
}
```

### 2. Obtener Tipos de Propiedad

```php
$result = API::get_inmovilla_tipos();

if ( 'ok' === $result['status'] ) {
    foreach ( $result['data'] as $tipo ) {
        echo $tipo['cod_tipo'] . ': ' . $tipo['tipo'];
    }
}
```

### 3. Obtener Provincias con Propiedades

```php
$result = API::get_inmovilla_provincias( true );

if ( 'ok' === $result['status'] ) {
    foreach ( $result['data'] as $provincia ) {
        echo $provincia['codprov'] . ': ' . $provincia['provincia'];
    }
}
```

### 4. Obtener Ciudades

```php
$result = API::get_inmovilla_ciudades();

if ( 'ok' === $result['status'] ) {
    foreach ( $result['data'] as $ciudad ) {
        echo $ciudad['cod_ciu'] . ': ' . $ciudad['city'];
    }
}
```

### 5. Obtener Zonas de una Ciudad

```php
// Obtener zonas de la ciudad con key_loca = 2013
$result = API::get_inmovilla_zonas( 2013 );

if ( 'ok' === $result['status'] ) {
    foreach ( $result['data'] as $zona ) {
        echo $zona['cod_zona'] . ': ' . $zona['zona'];
    }
}
```

### 6. Obtener Propiedades Destacadas

```php
$result = API::get_inmovilla_destacados( 20, 'precioinmo asc' );

if ( 'ok' === $result['status'] ) {
    foreach ( $result['data'] as $propiedad ) {
        echo $propiedad['ref'] . ': ' . $propiedad['tituloes'];
    }
}
```

### 7. Obtener Detalle Completo de una Propiedad

```php
$result = API::get_property( '12345', 'inmovilla' );

// El resultado incluye:
// - Datos básicos de la propiedad
// - descripciones: Array con títulos y descripciones en diferentes idiomas
// - fotos: Array con URLs de las fotos
// - videos: Array con códigos de YouTube
```

### 8. Filtrar Propiedades por Múltiples Criterios

```php
// Propiedades en venta, con piscina, en una zona específica
$where = "keyacci=1 AND piscina_prop=1 AND key_zona=456";
$result = API::request_inmovilla(
    'paginacion',
    1,
    50,
    $where,
    'precioinmo asc'
);
```

## Campos Principales de una Propiedad

### Identificación
- `cod_ofer`: Código interno de la propiedad
- `ref`: Referencia de la propiedad (única)
- `key_tipo`: Tipo de propiedad
- `keyacci`: Tipo de operación (1=Venta, 2=Alquiler, etc.)

### Ubicación
- `key_loca`: Código de la ciudad
- `key_zona`: Código de la zona
- `calle`: Dirección
- `numero`: Número del portal
- `planta`: Número de planta
- `puerta`: Puerta
- `cp`: Código postal
- `latitud`, `longitud`: Coordenadas GPS

### Características
- `habitaciones`: Habitaciones simples
- `habdobles`: Habitaciones dobles
- `banyos`: Baños
- `m_cons`: Metros construidos
- `m_utiles`: Metros útiles
- `m_parcela`: Metros de parcela

### Precios
- `precioinmo`: Precio de venta
- `precioalq`: Precio de alquiler
- `gastos_com`: Gastos de comunidad

### Estado
- `estadoficha`: Estado de la propiedad
- `conservacion`: Estado de conservación
- `destacado`: Si es destacada
- `eninternet`: Si se publica en web

### Fechas
- `fecha`: Fecha de alta
- `fechaact`: Fecha de última actualización
- `fechamod`: Fecha de modificación

### Multimedia
- `fotos`: Array con URLs de fotos
- `videos`: Array con códigos de YouTube
- `descripciones`: Array con títulos y descripciones por idioma

## Métodos Auxiliares

### `get_properties()`

Obtiene propiedades del CRM configurado (Anaconda o Inmovilla).

```php
// Para Inmovilla
$result = API::get_properties( 1 ); // Página 1

// Con filtro de fecha
$result = API::get_properties( 0, '2024-01-01 00:00:00' );
```

### `get_total_properties()`

Obtiene el total de propiedades disponibles.

```php
$result = API::get_total_properties();
if ( 'ok' === $result['status'] ) {
    $total = $result['data']['total'];
}
```

### `get_property()`

Obtiene el detalle completo de una propiedad.

```php
$property = API::get_property( '12345', 'inmovilla' );
```

## Seguridad y Limitaciones

### Control de IP

La API de Inmovilla implementa un control estricto de peticiones por IP:

- Existe un límite de peticiones por minuto
- Si se excede el límite, la IP se bloquea durante 10 minutos
- El plugin envía la IP real del usuario (`ia`) y la IP proxy si existe (`ib`)

### Recomendaciones

1. **No usar para scraping**: La API no está diseñada para cargar bases de datos completas
2. **Agrupar peticiones**: Usar múltiples llamadas a `Procesos()` antes de `PedirDatos()` cuando sea posible
3. **Usar caché**: Implementar caché para datos que no cambian frecuentemente (tipos, ciudades, etc.)
4. **Optimizar consultas**: Usar filtros `where` específicos para reducir el número de resultados

### Formato de Respuesta

Todas las peticiones devuelven un array con el siguiente formato:

```php
array(
    'status' => 'ok' | 'error',
    'data'   => array() | string  // Datos o mensaje de error
)
```

## Filtros Comunes

### Por Tipo de Operación

```php
$where = "keyacci=1";  // Venta
$where = "keyacci=2";  // Alquiler
$where = "keyacci=3";  // Alquiler vacacional
```

### Por Características

```php
$where = "ascensor=1";           // Con ascensor
$where = "piscina_prop=1";       // Con piscina propia
$where = "aire_con=1";           // Con aire acondicionado
$where = "habitaciones>=3";      // 3 o más habitaciones
$where = "precioinmo<=200000";   // Precio máximo 200.000€
```

### Combinando Filtros

```php
$where = "keyacci=1 AND habitaciones>=3 AND precioinmo<=300000";
```

### Por Ubicación

```php
$where = "key_loca=2013";        // Ciudad específica
$where = "key_zona=456";         // Zona específica
$where = "keyprov=28";           // Provincia (Madrid)
```

## Notas Importantes

1. **Índices de Array**: Los arrays devueltos por Inmovilla siempre tienen el índice 0 con metadata (posición, elementos, total). Los datos reales empiezan en el índice 1.

2. **Formato de Fechas**: Las fechas se devuelven en formato `YYYY-MM-DD HH:MM:SS`.

3. **Campos Booleanos**: Los campos booleanos se representan como 0 (false) o 1 (true).

4. **Campos de Entorno**: El campo `x_entorno` usa operaciones binarias (bitwise) para múltiples valores.

5. **Idiomas**: El parámetro `$idioma` afecta principalmente a los tipos de propiedad. Los idiomas disponibles son:
   - 1: Español
   - 2: Inglés
   - 3: Francés
   - 4: Alemán
   - 5: Ruso
   - 6: Catalán

## Soporte

Para más información sobre la API de Inmovilla, consulta la documentación oficial:
https://procesos.apinmo.com/apiweb/doc/index.html

