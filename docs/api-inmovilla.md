# 🌐 API - WEB INMOVILLA

## Índice

- Campos Enums
- Ejemplo de implementación
- Función PedirDatos
- Función Procesos
- Tabla Tipo
- Tipos de propiedad
- Provincias / Ciudades / Zonas / Conservación
- Paginación / Paginación Services
- Destacados
- Disponibilidad Alquiler / Temporadas Alquiler
- Filtro de alquiler de temporada en paginación
- Promociones paginación / Promociones ficha
- Obtener propiedades de obra nueva
- Ficha / Descripciones / Fotos / Antes y Después / Videos
- Referencias
- Listar propiedades disponibles
- Listar Agentes
- Retornar datos en formato JSON
- Bloqueo de IP
- Obtener log de petición y respuesta
- Generar lead desde web externa a Inmovilla

---

## ⚠️ Aspectos previos

La API no está pensada para ejecutarse en un cron y con ello cargar una base de datos interna de una web. No debe usarse para este fin. Para ello existen otras soluciones como facilitar un XML (ubicado en una URL y actualizado una vez al día por la noche).

Existe una limitación de peticiones por minuto. Si se reciben más peticiones por minuto desde una determinada IP, la API se bloqueará durante 10 minutos. Por seguridad, no se indica el límite de peticiones. Un uso correcto de la API no alcanzará nunca el límite.

URL: https://inmovilla.notion.site/API-WEB-INMOVILLA-783038f21ea2479db3d6d12e126ac165
https://procesos.apinmo.com/apiweb/doc

---

## Campos Enums

Para conocer los diferentes valores que pueden tener campos como `estadoficha`, `keyacci`, `conservacion`, `x_entorno`, entre otros, consultar la documentación:

**Documentación ENUMS:** [Documentación ENUMS]

---

## Ejemplo de implementación

La API de Inmovilla se basa en 2 funciones: `Procesos` para definir el tipo de datos a consultar y `PedirDatos` para consultar los datos y definir filtros y orden.

````php
<?php
include("apiinmovilla.php");

// Ejemplo:
// USUARIO_API: 2_000_ext
// PASSWORD: 11111

// 2
$numagencia = 'NUMERO_AGENCIA_DE_INMOVILLA';
// Es posible que el usuario_api en las cuentas
// de demo no tengan este formato
// de ser así puede dejarse vacío.
// _000_ext
$addnumagencia = 'USUARIO_API_SIN_NUMERO_AGENCIA';
$password = 'PASSWORD';
$idioma = 1;

$pag = $_GET["pag"];

if ($pag == "") {
    $pag = 1;
}

$tampag = 30;
$numregistro = (($pag - 1) * $tampag) + 1;

$ordensql = "precioalq asc";

Procesos(
    "paginacion",
    $numregistro,
    $tampag,
    $where,
    $ordensql
);
PedirDatos($numagencia, $password, $idioma);

$total = $paginacion[0]["total"];
$totalpag = (int)$total / $tampag;
$resto = $total % $tampag;

if ($resto > 0) $totalpag++;

// Mostramos los resultados
for ($i = 1; $i <= $paginacion[0]["elementos"]; $i++) {
    echo "REF: " . $paginacion[$i]["ref"] . "<br>";
}

echo "<br>";

// Mostramos la paginación
for ($i = 1; $i <= $totalpag; $i++) {
    echo "<a href='?pag=$i'>Pagina $i </a> - ";
}
````

---

## Función PedirDatos

Hace la llamada e inyecta en la implementación todos los arrays configurados con la función `Procesos`.

````php
PedirDatos(numagencia, password, idioma);
````

| Campo        | Tipo de datos | Descripción                                                              |
|--------------|---------------|--------------------------------------------------------------------------|
| `numagencia` | int           | Número de agencia                                                        |
| `password`   | varchar       | Contraseña API                                                           |
| `idioma`     | int           | Número de idioma para cargar tipos de propiedad (Ver tabla Idiomas)     |

> ⚠️ **IMPORTANTE:** Si se hace la llamada a `PedirDatos` desde una función hay que declarar la variable devuelta como global.
> ```php
> global $paginacion;
> ```

---

## Función Procesos

Configura los arrays de datos que se necesitan.

````php
Procesos(tipo, posinicial, numelementos, where, orden);
````

| Campo          | Tipo de datos | Descripción                                                                                  |
|----------------|---------------|----------------------------------------------------------------------------------------------|
| `tipo`         | varchar       | Tipo de datos predefinido que se solicita (Ver tabla Tipos)                                 |
| `posinicial`   | int           | Posición por la que empieza la consulta                                                     |
| `numelementos` | int           | Número de elementos del array (limitado según tipo) (Ver tabla Tipos)                      |
| `where`        | varchar       | WHERE de la consulta MySQL (Ver Tabla Campos Búsquedas)                                     |
| `orden`        | varchar       | Orden MySQL                                                                                  |

**Ejemplo:**

````php
<?php
include('apiinmovilla.php');

$numagencia    = 'NUMERO_AGENCIA_DE_INMOVILLA';
$addnumagencia = 'USUARIO_API_SIN_NUMERO_AGENCIA';
$password      = 'PASSWORD';

Procesos('paginacion', 1, 50, 'ascensor=1', '');
PedirDatos($numagencia, $password, 1);
````

Genera un array `$paginacion` con 50 propiedades que tienen ascensor. La posición `0` siempre contiene la posición inicial, el número de elementos y el total de registros de la consulta.

````php
// Respuesta
$paginacion[0] = array(
    'posicion'  => $posinicial,
    'elementos' => $numelementos,
    'total'     => TOTAL
);
$paginacion[1] ... $paginacion[numelementos];
````

---

## Tabla Tipo

| Valor                          | Descripción                                            | Núm. elementos máximos |
|--------------------------------|--------------------------------------------------------|------------------------|
| `tipos`                        | Lista de tipos de propiedad                            | Sin límite             |
| `provincias`                   | Lista de provincias                                    | Sin límite             |
| `ciudades`                     | Lista de ciudades                                      | Sin límite             |
| `zonas`                        | Lista de zonas de una ciudad (campo `key_loca`)        | Sin límite             |
| `tipos_conservacion`           | Lista de estados de la propiedad                       | Sin límite             |
| `paginacion`                   | Registros de propiedades                               | 50 registros           |
| `destacados`                   | Registros de propiedades destacadas                    | 30 registros           |
| `alquilerdisponibilidad`       | Listar periodos de ocupación de la vivienda            |                        |
| `alquilertemporada`            | Listar distintas temporadas de precios de la vivienda  |                        |
| `paginacion_promociones`       | Listar promociones de obra nueva                       |                        |
| `ficha_promo`                  | Registro de una ficha dentro de una obra nueva         |                        |
| `ficha`                        | Registro de una ficha de propiedad                     | 1 registro             |
| `listar_propiedades_disponibles` | Listar `cod_ofer` disponibles                        | 5000 registros         |
| `referencias`                  | Búsqueda de referencias                                | 100 registros          |

---

## Tipos de propiedad

**Array:** `$tipos`

| Campo      | Tipo de datos | Descripción                    |
|------------|---------------|--------------------------------|
| `cod_tipo` | int           | Código del tipo de propiedad   |
| `tipo`     | varchar       | Nombre del tipo de propiedad   |

---

## Provincias

- `$provincias` — Devuelve todas las provincias.
- `$provinciasofertas` — Devuelve las provincias que tengan propiedades.

| Campo      | Tipo de datos | Descripción               |
|------------|---------------|---------------------------|
| `codprov`  | int           | Código de la provincia    |
| `provincia`| varchar       | Nombre de la provincia    |

> ℹ️ Para filtrar por provincia en paginación, el campo es `keyprov` en lugar de `codprov`.

---

## Ciudades

**Array:** `$ciudades` — Devuelve las ciudades que tengan propiedades.

| Campo      | Tipo de datos | Descripción                            |
|------------|---------------|----------------------------------------|
| `cod_ciu`  | int           | Código de la ciudad                    |
| `city`     | varchar       | Nombre de la ciudad                    |
| `provincia`| varchar       | Provincia de la ciudad                 |
| `isla`     | varchar       | Isla (Canarias)                        |
| `codprov`  | int           | Código de la provincia (Ver Provincias)|

---

## Zonas

**Array:** `$zonas`

| Campo      | Tipo de datos | Descripción          |
|------------|---------------|----------------------|
| `cod_zona` | int           | Código de la zona    |
| `zone`     | varchar       | Nombre de la zona    |

Para obtener las zonas de una ciudad:

````php
Procesos('zonas', 1, 100, 'key_loca=37899', '');
````

---

## Conservación

**Array:** `$tipos_conservacion`

| Campo            | Tipo de datos | Descripción                                         |
|------------------|---------------|-----------------------------------------------------|
| `idconservacion` | int           | Código del tipo de conservación                     |
| `conserv`        | varchar       | Nombre del tipo (Buen estado, Reformado, etc.)      |

````php
Procesos('tipos_conservacion', 1, 50, "", "");
````

---

## Paginación

**Array:** `$paginacion`

| Campo                    | Tipo de datos  | Descripción                                                                 |
|--------------------------|----------------|-----------------------------------------------------------------------------|
| `cod_ofer`               | int            | Código interno de la propiedad                                              |
| `ref`                    | varchar        | Referencia de la propiedad                                                  |
| `keyacci`                | int            | Tipo de acción (1:Venta, 2:Alquiler, 3:Traspaso). Consultar enums          |
| `precioinmo`             | int            | Precio de venta                                                             |
| `outlet`                 | int            | Precio anterior                                                             |
| `precioalq`              | int            | Precio de alquiler                                                          |
| `tipomensual`            | varchar        | Periodicidad alquiler: `MES`, `QUI`, `SEM`, `DIA`, `FIN`                  |
| `numfotos`               | int            | Número de fotos                                                             |
| `nbtipo`                 | varchar        | Nombre del tipo de propiedad                                                |
| `ciudad`                 | varchar        | Nombre de la ciudad                                                         |
| `zona`                   | varchar        | Nombre de la zona                                                           |
| `numagencia`             | int            | Número de la agencia                                                        |
| `m_parcela`              | int            | Metros de parcela                                                           |
| `m_uties`                | int            | Metros útiles                                                               |
| `m_cons`                 | int            | Metros construidos                                                          |
| `m_terraza`              | int            | Metros terraza                                                              |
| `banyos`                 | int            | Número de baños                                                             |
| `aseos`                  | int            | Número de aseos                                                             |
| `habdobles`              | int            | Habitaciones dobles                                                         |
| `habitaciones`           | int            | Habitaciones simples                                                        |
| `habdobles+habitaciones` | int            | Total habitaciones (para la petición)                                       |
| `total_hab`              | int            | Total habitaciones (informativo)                                            |
| `distmar`                | int            | Distancia al mar en metros                                                  |
| `ascensor`               | int (1 o 0)    | 1: tiene ascensor, 0: no tiene ascensor                                     |
| `aire_con`               | int (1 o 0)    | Aire acondicionado                                                          |
| `parking`                | int            | 0: No tiene, 1: Opcional, 2: Incluido                                       |
| `piscina_com`            | int (1 o 0)    | Piscina comunitaria                                                         |
| `piscina_prop`           | int (1 o 0)    | Piscina propia                                                              |
| `diafano`                | int (1 o 0)    | Diáfano                                                                     |
| `todoext`                | int (1 o 0)    | Todo exterior                                                               |
| `foto`                   | varchar        | Ruta de la foto principal                                                   |
| `calefaccion`            | int (1 o 0)    | Calefacción                                                                 |
| `fechaact`               | datetime       | Fecha de última actualización                                               |
| `fecha`                  | datetime       | Fecha de alta                                                               |

````php
Procesos('paginacion', 1, 50, "", "");
````

Para filtrar por total de habitaciones se deben usar `habdobles` y `habitaciones` conjuntamente:

````php
Procesos('paginacion', 1, 50, "habitaciones+habdobles = 1", "");
````

> ⚠️ Para evitar bloqueos y mejorar el rendimiento, se recomienda cargar los datos eficientemente en primera instancia y solicitar únicamente las actualizaciones necesarias. Ejemplo filtrando por fecha de actualización:
> ```php
> $ultima_comprobacion = date('Y-m-d H:i:s', strtotime('-1 day'));
> Procesos('paginacion', 1, 50, "ofertas.fechaact > '$ultima_comprobacion'", "");
> ```

> ⚠️ Por defecto, el título y las descripciones no se incluyen en la petición de paginación, pero pueden solicitarse activando dicha opción.

---

## Paginación Services

Para aplicar búsquedas especiales en paginación, añadir `/services/?` en el `where`:

| Servicio   | Descripción                                                  | Ejemplo                    |
|------------|--------------------------------------------------------------|----------------------------|
| `cerca_de` | Filtra propiedades geolocalizadas cerca de una ubicación     | `madrid,puerta del sol`    |

**Uso:**

````php
Procesos('paginacion', 1, 50, "/services/?cerca_de=madrid,puerta del sol", "");

// Combinado con otros filtros:
Procesos('paginacion', 1, 50, "habitaciones+habdobles > 2/services/?cerca_de=madrid,puerta del sol", "");
````

---

## Destacados

**Array:** `$destacados`

| Campo                    | Tipo de datos | Descripción                                                            |
|--------------------------|---------------|------------------------------------------------------------------------|
| `cod_ofer`               | int           | Código interno de la propiedad                                         |
| `ref`                    | varchar       | Referencia de la propiedad                                             |
| `keyacci`                | int           | Tipo de acción (1:Venta, 2:Alquiler, 3:Traspaso). Consultar enums    |
| `precioinmo`             | int           | Precio de venta                                                        |
| `outlet`                 | int           | Precio anterior                                                        |
| `precioalq`              | int           | Precio de alquiler                                                     |
| `tipomensual`            | varchar       | Periodicidad alquiler: `MES`, `QUI`, `SEM`, `DIA`, `FIN`             |
| `numfotos`               | int           | Número de fotos                                                        |
| `nbtipo`                 | varchar       | Nombre del tipo de propiedad                                           |
| `ciudad`                 | varchar       | Nombre de la ciudad                                                    |
| `zona`                   | varchar       | Nombre de la zona                                                      |
| `numagencia`             | int           | Número de la agencia                                                   |
| `banyos`                 | int           | Número de baños                                                        |
| `habdobles+habitaciones` | int           | Total habitaciones (para la petición)                                  |
| `total_hab`              | int           | Total habitaciones (informativo)                                       |
| `foto`                   | varchar       | Ruta de la foto principal                                              |

### Campos de búsqueda sobre paginación y destacados

Se puede filtrar en el `where` por todos los campos anteriores. Los campos clave para filtrar por tipo, ciudad, zona y conservación son:

| Campo         | Tipo de datos | Descripción                               |
|---------------|---------------|-------------------------------------------|
| `key_tipo`    | int           | Código del tipo (Ver tabla Tipos)         |
| `key_loca`    | int           | Código de la ciudad (Ver Ciudades)        |
| `key_zona`    | int           | Código de la zona (Ver Zonas)             |
| `conservacion`| int           | Código de conservación (Ver Conservación) |

---

## Disponibilidad Alquiler

**Array:** `$alquilerdisponibilidad`

| Campo        | Tipo de datos | Descripción              |
|--------------|---------------|--------------------------|
| `fechainicio`| Fecha String  | Fecha inicio de ocupación|
| `fechafin`   | Fecha String  | Fecha fin de ocupación   |

Obtiene los distintos periodos de ocupación de una vivienda:

````php
// $datoofe contiene cod_ofer de la vivienda
// $numagencia contiene numagencia de la vivienda
$codigo = $datoofe . '.' . $numagencia;
$where  = "fechafin >= $hoy and codigo=$codigo";
Procesos("alquilerdisponibilidad", 1, 50, $where, "");
````

---

## Temporadas Alquiler

**Array:** `$alquilertemporada`

| Campo              | Tipo de datos | Descripción                          |
|--------------------|---------------|--------------------------------------|
| `diaini`           | int           | Día inicio temporada (1..31)         |
| `mesini`           | int           | Mes inicio temporada (1..12)         |
| `preciodia`        | int           | Precio por día                       |
| `preciofinsemana`  | int           | Precio por fin de semana             |
| `preciosemana`     | int           | Precio por semana                    |
| `preciomes`        | int           | Precio por mes                       |
| `precioquincena`   | int           | Precio por quincena                  |
| `diafin`           | int           | Día fin de temporada (1..31)         |
| `mesfin`           | int           | Mes fin de temporada (1..12)         |
| `titulo`           | String        | Título de temporada (Verano, Invierno, etc.) |

````php
$wheretemporada = "keyclave=$datoofe";
Procesos("alquilertemporada", 1, 100, $wheretemporada, "");
````

---

## Filtro de alquiler de temporada en paginación

Para obtener propiedades de alquiler vacacional en la paginación:

````php
$where = $where . " and ((ofertas.precioalq>0 and tipomensual<>'MES' and tipomensual<>'mes') or keyacci in(9))";
````

---

## Promociones paginación

**Array:** `$paginacion_promociones`

| Campo          | Tipo de datos | Descripción                                               |
|----------------|---------------|-----------------------------------------------------------|
| `codobra`      | int           | Código interno de la promoción                            |
| `refobra`      | varchar       | Referencia de la promoción                                |
| `precio_desde` | int           | Precio mínimo del conjunto de propiedades                 |
| `precio_hasta` | int           | Precio máximo del conjunto de propiedades                 |
| `numfotos`     | int           | Número de fotos                                           |
| `ciudad`       | varchar       | Nombre de la ciudad                                       |
| `zona`         | varchar       | Nombre de la zona                                         |
| `numagencia`   | int           | Número de la agencia                                      |
| `titulo`       | varchar       | Nombre de la promoción                                    |
| `descrip`      | varchar       | Descripción de la propiedad                               |
| `nodispo`      | int           | Disponibilidad de la promoción                            |
| `foto`         | varchar       | Ruta de la foto principal                                 |

````php
// Obtener el listado de obras nuevas
$where = "";
Procesos("paginacion_promociones", 1, 100, $where, "");
````

> ℹ️ Hay muchos campos más. Ver array con `var_dump($paginacion_promociones)`.

---

## Promociones ficha

**Array:** `$ficha_promo`

| Campo          | Tipo de datos | Descripción                                               |
|----------------|---------------|-----------------------------------------------------------|
| `codobra`      | int           | Código interno de la promoción                            |
| `ref`          | varchar       | Referencia de la promoción                                |
| `precio_desde` | int           | Precio mínimo del conjunto de propiedades                 |
| `precio_hasta` | int           | Precio máximo del conjunto de propiedades                 |
| `numfotos`     | int           | Número de fotos                                           |
| `ciudad`       | varchar       | Nombre de la ciudad                                       |
| `zona`         | varchar       | Nombre de la zona                                         |
| `numagencia`   | int           | Número de la agencia                                      |
| `titulo`       | varchar       | Nombre de la promoción                                    |
| `descrip`      | varchar       | Descripción de la propiedad                               |
| `nodispo`      | int           | Disponibilidad de la promoción                            |
| `foto`         | varchar       | Ruta de la foto principal                                 |

````php
// Se usa tanto codobra como numagencia separados por un punto
$where = "codobra=$codobra.$numagencia";
Procesos("ficha_promo", 1, 1, $where, "");
````

> ℹ️ Hay muchos campos más (calidades y características de la promoción). Ver `var_dump($ficha_promo)`.

---

## Obtener las propiedades pertenecientes a la obra nueva

Para filtrar las propiedades pertenecientes a una obra nueva, añadir al `where` de paginación:

````php
$where .= "keypromo = $codobra";
// $codobra se obtiene de $paginacion_promociones
````

---

## Ficha

**Array:** `$ficha`

| Campo                    | Tipo de datos  | Descripción                                                                 |
|--------------------------|----------------|-----------------------------------------------------------------------------|
| `cod_ofer`               | int            | Código interno de la propiedad                                              |
| `ref`                    | varchar        | Referencia de la propiedad                                                  |
| `keyacci`                | int            | Tipo de acción (1:Venta, 2:Alquiler, 3:Traspaso). Consultar enums         |
| `precioinmo`             | int            | Precio de venta                                                             |
| `outlet`                 | int            | Precio anterior                                                             |
| `precioalq`              | int            | Precio de alquiler                                                          |
| `tipomensual`            | varchar        | Periodicidad alquiler: `MES`, `QUI`, `SEM`, `DIA`                         |
| `numfotos`               | int            | Número de fotos                                                             |
| `nbtipo`                 | varchar        | Nombre del tipo de propiedad                                                |
| `ciudad`                 | varchar        | Nombre de la ciudad                                                         |
| `zona`                   | varchar        | Nombre de la zona                                                           |
| `numagencia`             | int            | Número de la agencia                                                        |
| `m_parcela`              | int            | Metros de parcela                                                           |
| `m_uties`                | int            | Metros útiles                                                               |
| `m_cons`                 | int            | Metros construidos                                                          |
| `m_terraza`              | int            | Metros terraza                                                              |
| `banyos`                 | int            | Número de baños                                                             |
| `aseos`                  | int            | Número de aseos                                                             |
| `habdobles`              | int            | Habitaciones dobles                                                         |
| `habitaciones`           | int            | Habitaciones simples                                                        |
| `habdobles+habitaciones` | int            | Total habitaciones (para la petición)                                       |
| `total_hab`              | int            | Total habitaciones (informativo)                                            |
| `distmar`                | int            | Distancia al mar en metros                                                  |
| `ascensor`               | int (1 o 0)    | 1: tiene ascensor, 0: no tiene ascensor                                     |
| `aire_con`               | int (1 o 0)    | Aire acondicionado                                                          |
| `Parking`                | int            | 0: No tiene, 1: Opcional, 2: Incluido                                       |
| `piscina_com`            | int (1 o 0)    | Piscina comunitaria                                                         |
| `piscina_prop`           | int (1 o 0)    | Piscina propia                                                              |
| `diafano`                | int (1 o 0)    | Diáfano                                                                     |
| `todoext`                | int (1 o 0)    | Todo exterior                                                               |
| `energialetra`           | varchar        | Calificación energética (A, B, C, D, E, F, G, tramites)                   |
| `energiavalor`           | float          | Consumo energía kWh/m² año                                                  |
| `emisionesletra`         | varchar        | Calificación emisiones (A, B, C, D, E, F, G, tramites)                    |
| `emisionesvalor`         | float          | Consumo kg CO₂/m² año                                                       |
| `agencia`                | varchar        | Nombre de la agencia                                                        |
| `web`                    | varchar        | Página web de la agencia                                                    |
| `emailagencia`           | varchar        | Email interno de la agencia                                                 |
| `telefono`               | varchar        | Teléfono de la agencia                                                      |
| `tourvirtual`            | int (1 o 0)    | Tour virtual externo (*)                                                    |
| `fotos360`               | int (1 o 0)    | Visor de fotos panorámicas (**)                                             |
| `video`                  | int (1 o 0)    | Dispone de vídeos                                                           |
| `x_entorno`              | binario        | Campo binario con calidades (Ver tabla Entorno) (***)                      |
| `antesydespues`          | int (0 o 1)    | Indica si la propiedad tiene fotos de antes y después                      |
| `fotoletra`              | int            | Identificador único para las fotos                                          |
| `fechacreacion`          | datetime       | Fecha de alta                                                               |
| `fechaactualizacion`     | datetime       | Fecha de actualización                                                      |

> ℹ️ Si `antesydespues` tiene valor `1`, habrá un array adicional con las numeraciones de las fotos antes/después. Se necesita también el campo `fotoletra` para generar las URLs de las fotos del después.

> ℹ️ Hay muchos campos más. Ver array con `var_dump()`.

**Tour virtual:**
````
http://ap.apinmo.com/fotosvr/tour.php?cod=cod_ofer.numagencia
````

**Visor de fotos panorámicas:**
````
http://ap.apinmo.com/fotosvr/?codigo=cod_ofer.numagencia
````

**Campo `x_entorno` (binario):** Para extraer un entorno concreto según su ID:

````php
// Ejemplo: comprobar si la propiedad tiene Zonas Infantiles (Id. 12)
$bin_zona_infantil = pow(2, 12); // 4096

if (($ficha['x_entorno'] & $bin_zona_infantil) == $bin_zona_infantil) {
    // La propiedad tiene zonas infantiles
}

// Filtrar en MySQL propiedades con zonas infantiles:
$where .= ' AND x_entorno&4096=4096';
````

---

## Descripciones

**Array:** `$descripciones[cod_ofer][idioma]`

| Campo    | Tipo de datos | Descripción                  |
|----------|---------------|------------------------------|
| `titulo` | varchar       | Título de la propiedad       |
| `descrip`| text          | Descripción de la propiedad  |

````php
$descripciones[35856][1] = array("titulo" => "Titulo propiedad", "descrip" => "Descripcion...");
````

---

## Fotos

**Array:** `$fotos[cod_ofer]`

Array con todas las URLs de las fotos de la ficha.

---

## Antes y Después

**Array:** `$antesydespues[cod_ofer]`

Array con todas las fotos que tienen antes y después.

| Campo        | Tipo de datos | Descripción              |
|--------------|---------------|--------------------------|
| `fotoantes`  | int           | Número de foto (antes)   |
| `fotodespues`| int           | Número de foto (después) |

**Ejemplo para generar las URLs:**

````
https://fotos15.apinmo.com/{numagencia}/{codofer}/ant-{fotoantes}
https://fotos15.apinmo.com/{numagencia}/{codofer}/{fotoletra}-{fotodespues}
````

---

## Videos

**Array:** `$videos[cod_ofer]`

Array con los códigos de YouTube de los vídeos.

---

## Referencias

Búsqueda de referencias exactas o similares.

**Array:** `$referencias`

| Campo        | Tipo de datos | Descripción                  |
|--------------|---------------|------------------------------|
| `ref`        | string        | Referencia de la propiedad   |
| `cod_ofer`   | int           | Código interno de la propiedad|
| `ciudad`     | string        | Ciudad                        |
| `zona`       | string        | Zona                          |
| `precioinmo` | float         | Precio de venta               |
| `precio_alq` | float         | Precio de alquiler            |
| `tipo_ofer`  | string        | Tipo de propiedad             |

````php
// En el campo where se indica la referencia a buscar
Procesos('referencias', 1, 100, "R1000", "");
$json = PedirDatos("NUMAGENCIA", "PASSWORD", 1, 1);
echo $json;
````

---

## Listar propiedades disponibles

Para obtener de antemano qué propiedades están disponibles sin paginar entre todas las peticiones:

````php
Procesos('listar_propiedades_disponibles', 1, 5000, "");
$json = PedirDatos("NUMAGENCIA", "PASSWORD", 1, 1);
echo $json;
````

---

## Listar Agentes

````php
// Obtener todos los agentes
$where = "numagencia =" . $numagencia;
Procesos('agentes', 1, 50, $where, "");
$json = PedirDatos("NUMAGENCIA", "PASSWORD", 1, 1);
echo $json;

// Obtener un agente en concreto
$where = "numagencia =" . $numagencia . " AND id = " . $idagente;
Procesos('agentes', 1, 1, $where, "");
$json = PedirDatos("NUMAGENCIA", "PASSWORD", 1, 1);
echo $json;
````

---

## Para retornar datos en formato JSON

Para retornar los datos como string JSON en lugar de array, añadir `1` como último parámetro de `PedirDatos`:

````php
Procesos('paginacion', 1, 3, "keyacci=1", "fecha desc");
$json = PedirDatos("NUMAGENCIA", "PASSWORD", 1, 1);
echo $json;
````

El parámetro `1` al final de `PedirDatos` indica que los datos se servirán en JSON. Sin él, devuelve un array global. Para convertir el string JSON a array en PHP se pueden usar `json_decode` y `json_encode`.

---

## Bloqueo de IP

Para garantizar una experiencia equitativa y prevenir el uso excesivo de recursos, la API implementa un control estricto sobre el número de peticiones: **el límite es de 70 peticiones por minuto**. Las IPs que lo superen serán bloqueadas temporalmente; si se superan los 10 bloqueos temporales, el bloqueo puede ser permanente.

**Recomendaciones para optimizar el uso de la API:**

**Evitar el scraping:** La API no está diseñada para scraping. Como alternativa, se recomienda la carga de propiedades por XML.

**Agrupar peticiones en una sola llamada a `PedirDatos`:** En lugar de múltiples peticiones, organizar todas las necesidades de datos en una única llamada:

````php
Procesos('tipo',    1, 100, "", "");
Procesos('ciudad',  1, 100, "", "");
Procesos('destacados', 1, 20, "", "precioinmo, precioalq");
Procesos('paginacion', 1, 20, "ascensor=1", "precioinmo, precioalq");
PedirDatos($numagencia, $password, $idioma);
````

Si se está siendo bloqueado y se considera que la implementación no supera el límite, verificar que no se esté usando ningún proxy o servidor adicional para las peticiones, ya que `PedirDatos` intenta obtener la IP del navegante y solo a este se debe bloquear. Si el problema continúa, modificar el código ajustando los parámetros `ia` (con la IP del usuario) e `ib` (vacío).

---

## Obtener log de petición y respuesta

Para obtener soporte ante incidencias, generar el log modificando la constante `DEPURAR_API_INMOVILLA` a `true` antes de ejecutar `PedirDatos`. El log se creará como `apiinmovilla.log` en la misma carpeta donde esté `apiinmovilla.php`.

````php
define('DEPURAR_API_INMOVILLA', true);
````

Si los ficheros ya incluían esta constante con valor `false`, simplemente cambiarla a `true`. En caso contrario, sustituir el fichero `apiinmovilla.php` por la versión actualizada:

````
http://ycasas.es/apiemail/servidor/adjuntos/api_cliente.rar
````

---

## Generar lead desde web externa a Inmovilla

Para generar leads de forma automática, enviar el formulario con la estructura estándar indicada en la documentación. El sistema detecta este formato de correo (con los datos del interesado: nombre, apellidos, email, teléfono, IP, referencia de consulta, etc.) y crea el lead correspondiente en Inmovilla.

**(Dato obligatorio) → Asunto:** `Solicitud de información: Ref. XXXXXXX`
Nombre : Sergio
Apellidos: Prueba
Email: prueba@prueba.com
Teléfono: 123987456 Dirección IP remitente: 62.97.106.101
Ref Consultada: 00249 Enlace a la referencia
Interesad@ en 00249