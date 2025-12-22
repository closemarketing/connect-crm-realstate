# API - WEB INMOVILLA

> ⚠️ **Aspectos previos**
> 
> La `API` no está pensada para ejecutarse en un cron y con ello cargar una base de datos interna de una web. No debe usarse para este fin. Para ello tenemos otras soluciones como facilitaros un XML (que se ubicará en una URL y que se actualizaría una vez al día por la noche).
> 
> Existe una limitación de peticiones por minutos. Si se reciben más peticiones por minuto de una determinada IP la API se bloqueará durante 10 minutos. Por seguridad, no podemos indicar el límite de peticiones. Pero un uso correcto del API no alcanzará nunca el límite.
> 
> Para saber los diferentes valores que pueden tener campos como `estadoficha`, `keyacci`, `conservacion`, `x_entorno` entre otros, puede consultar nuestra API REST y hacer una petición de tipo ENUM.
> 
> Documentación API REST: [https://procesos.apinmo.com/api/v1/apidoc/](https://procesos.apinmo.com/api/v1/apidoc/)

La API de Inmovilla, se basa en 2 funciones `procesos` para definir el tipo de datos que vamos consultar y `PedirDatos` que consulta los datos y define filtros y orden.

## Ejemplo de implementación

```php
<?php
include("apiinmovilla.php");

// Ejemplo:
// USUARIO_API: 2_000_ext
// PASSWORD: 11111

// 2
$numagencia = 'NUMERO_AGENCIA_DE_INMOVILLA';
// Es posible que el usuario_api en las cuentas
// de demo no tengan este formato
// de ser asi puede dejarse vacio.
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

//Mostramos los resultados
for ($i = 1; $i <= $paginacion[0]["elementos"]; $i++) {
    echo "REF: " . $paginacion[$i]["ref"] . "<br>";
}

echo "<br>";

//Mostramos la paginacion
for ($i = 1; $i <= $totalpag; $i++) {
    echo "<a href='?pag=$i'>Pagina $i </a> - ";
}
```

## Función PedirDatos

Hace la llamada e **inyecta** en la implementación todos los `arrays` que hayamos configurado con la función `Procesos`.

```php
PedirDatos(numagencia,password,idioma);
```

**Campo**

**Tipo de datos**

**Descripción**

`numagencia`

`int`

Numero de agencia

`password`

`varchar`

Contraseña api

`idioma`

`int`

Numero de idioma para cargar tipos de propiedad (**Ver tabla Idiomas**)

💡

**IMPORTANTE:** Si se hace la llamada a `PedirDatos` desde una función hay que poner la variable devuelta como `global`

---

Ejemplo:

```php
global $paginacion;
```

## Función Procesos

Configurar los `arrays` de datos que necesitamos

```php
Procesos (tipo,posinicial,numelementos,where,orden);
```

**Campo**

**Tipo de datos**

**Descripción**

`tipo`

`varchar`

Tipo de datos predefinido que solicitamos (**Ver tabla Tipos**)

`posinicial`

`int`

Posición por la que empieza en la consulta

`numelementos`

`int`

Número de elementos del `array` (Según el tipo está limitado a un número de registros) (**Ver tabla Tipos**)

`where`

`varchar`

`where` de la consulta `mysql` (**Ver Tabla Campos Búsquedas**)

`orden`

`varchar`

Orden `mysql`

**Ejemplo**

```php
<?php
include('apiinmovilla.php');

// Ejemplo:
// USUARIO_API: 2_000_ext
// PASSWORD: 11111

// 2
$numagencia = 'NUMERO_AGENCIA_DE_INMOVILLA';
//_000_ext
$addnumagencia = 'USUARIO_API_SIN_NUMERO_AGENCIA';
$password = 'PASSWORD';

Procesos ('paginacion',1,50,'ascensor=1','');
PedirDatos($numagencia, $password, 1);
```

Genera un array `$paginacion;` con 50 propiedades que tienen ascensor

La posición 0 siempre tiene la posición inicial, el número de elementos y el total de registros de la consulta.

```php
// Respuesta
// La variable $paginacion es inyecta por la funcion PedirDatos
$paginacion[0]=array(
	'posicion'=>$posinicial,
	'elementos'=>$numelementos,
	'total'=>TOTAL
);
$paginacion[1] ...........$paginacion[numelementos];
```

## Tabla Tipo

**Valor**

**Descripción**

**Num elementos Máximos**

`tipos`

Lista de tipos de propiedad

Sin limite

`provincias`

Lista de provincias

Sin limite

`ciudades`

Lista de ciudades

Sin limite

`zonas`

Lista de zonas de una ciudad, asociadas por campo `key_loca`

Sin limite

`tipos_conservacion`

Listas de estado de la propiedad

Sin limite

`paginacion`

Registros de propiedades

50 registros

`destacados`

Registros de propiedades destacadas

30 registros

`alquilerdisponibilidad`

Listar periodos de ocupación de la vivienda

`alquilertemporada`

Listar distintas temporadas de precios de la vivienda

`paginacion_promociones`

Listar promociones de obra nueva

`ficha_promo`

Registro de una ficha de propiedad dentro de una obra nueva

`ficha`

Registro de una ficha de propiedad

1 registro

`listar_propiedades_disponibles`

Listar `cod_ofer` disponibles

5000 registros

### Tipos de propiedad

**\[ $tipos \]**

**Campo**

**Tipo de datos**

**Descripción**

`cod_tipo`

`int`

Código del tipo de propiedad

`tipo`

`varchar`

Nombre del tipo de propiedad

### Provincias

**\[ $provincias \]** (Devuelve todas las provincias)

**\[ $provinciasofertas \]** (Devuelve las provincias que tengan propiedades)

**Campo**

**Tipo de datos**

**Descripción**

`codprov`

`int`

Código de la provincia

`provincia`

`varchar`

Nombre de la provincia

ℹ️ Para filtrar por provincia en paginación, a destacar que el campo es `keyprov`, en lugar de  
  
`codprov`

### Ciudades

**\[ $ciudades \]** (Devuelve las ciudades que tengan propiedades)

**Campo**

**Tipo de datos**

**Descripción**

`cod_ciu`

`int`

Código de la ciudad

`city`

`varchar`

Nombre de la ciudad

`provincia`

`varchar`

Provincia de la ciudad

`isla`

`varchar`

Isla (Canarias)

`codprov`

`int`

Código de la provincia **(Ver tabla provincias)**

### Zonas

**\[ $zonas \]**

**Campo**

**Tipo de datos**

**Descripción**

`cod_zona`

`int`

Código de la zona

`zone`

`varchar`

Nombre de la zona

Para sacar las zonas de una ciudad, `Procesos('zonas',1,100,'key_loca=37899','')`;

### Conservación

**\[ $tipos\_conservacion \]**

**Campo**

**Tipo de datos**

**Descripción**

`idconservacion`

`int`

Código del tipo de conservación

`conserv`

`varchar`

Nombre del tipo de propiedad (Buen estado, reformado, etc…)

`Procesos ('tipos_conservacion',1,50,"","");`

### Paginación

**\[ $paginacion \]**

**Campo**

**Tipo de datos**

**Descripción**

`cod_ofer`

`int`

Código interno de la propiedad

`ref`

`varchar`

Referencia de la propiedad

`keyacci`

`int`

Tipo de acción (1:Venta,2:Alquiler,3:Traspaso)

`precioinmo`

`int`

Precio de venta

`outlet`

`int`

Precio anterior

`precioalq`

`int`

Precio de alquiler

`tipomensual`

`varchar`

Tipo periodicidad alquiler `MES`, `QUI`, `SEM`, `DIA`, `FIN`

`numfotos`

`int`

Número de fotos

`nbtipo`

`varchar`

Nombre del tipo de propiedad

`ciudad`

`varchar`

Nombre de la ciudad

`zona`

`varchar`

Nombre de la zona

`numagencia`

`int`

Número de la agencia

`m_parcela`

`int`

Metros de parcela

`m_uties`

`int`

Metros útiles

`m_cons`

`int`

Metros construidos

`m_terraza`

`int`

Metros terraza

`banyos`

`int`

Número de baños

`aseos`

`int`

Número de aseos

`habdobles`

`int`

Habitaciones dobles

`habitaciones`

`int`

Habitaciones simples

`total_hab`

`int`

Total habitaciones

`distmar`

`int`

Distancia mar en metros

`ascensor`

`int (1 o 0)`

Valor 1: tiene ascensor, Valor 0: no tiene ascensor

`aire_con`

`int (1 o 0)`

Aire acondicionado

`parking`

`int`

Valor 0: No tiene, Valor 1: Opcional, Valor 2: Incluido

`piscina_com`

`int (1 o 0)`

Piscina comunitaria

`piscina_prop`

`int (1 o 0)`

Piscina propia

`diafano`

`int (1 o 0)`

Diáfano

`todoext`

`int (1 o 0)`

Todo exterior

`foto`

`varchar`

Ruta de la foto principal

`calefaccion`

`int (1 o 0)`

Calefacción

`aire_con`

`int (1 o 0)`

Aire acondicionado

`fechaact`

`datetime`

Fecha de ultima actualización

`Procesos ('paginacion',1,50,"","");`

💡Para evitar bloqueos y mejorar el rendimiento, se sugiere cargar los datos de manera eficiente en una primera instancia y, posteriormente, solicitar únicamente las actualizaciones necesarias cuando sea requerido. Esto reduce la carga de información innecesaria y mejora la eficiencia operativa, asegurando que se obtengan actualizaciones solo cuando sea necesario.

```php
//Pedir datos filtrando por fecha de actualizacion
$ultima_comprobacion = date('Y-m-d H:i:s', strtotime('-1 day'));
Procesos('paginacion', 1, 50, "ofertas.fechaact > '$ultima_comprobacion'", "");
```

💡 Por defecto no viene él título, ni las descripciones en la petición de paginacion. Pero puedes solicitar su activación

### Destacados

**\[$destacados\]**

**Campo**

**Tipo de datos**

**Descripción**

`cod_ofer`

`int`

Código interno de la propiedad

`ref`

`varchar`

Referencia de la propiedad

`keyacci`

`int`

Tipo de acción (1:Venta, 2:Alquiler, 3:Traspaso)

`precioinmo`

`int`

Precio de venta

`outlet`

`int`

Precio anterior

`precioalq`

`int`

Precio de alquiler

`tipomensual`

`varchar`

Tipo periodicidad alquiler MES, QUI, SEM, DIA,FIN

`numfotos`

`int`

Número de fotos

`nbtipo`

`varchar`

Nombre del tipo de propiedad

`ciudad`

`varchar`

Nombre de la ciudad

`zona`

`varchar`

Nombre de la zona

`numagencia`

`int`

Número de la agencia

`banyos`

`int`

Número de baños

`total_hab`

`int`

Total habitaciones

`foto`

`varchar`

Ruta de la foto principal

**Campos de búsqueda sobre paginación, destacados (inmuebles)**

Se puede filtrar en el `where` por todos los campos que hay arriba, estos son los campos de clave para filtrar por cada `tipo de propiedad`, `ciudad`, `zona` y `conservacion`.

**Campo**

**Tipo de datos**

**Descripción**

`key_tipo`

`int`

Código interno del tipo. (**Ver tabla Tipos**)

`key_loca`

`int`

Código interno de la ciudad. (**Ver tabla Ciudades**)

`key_zona`

`int`

Código interno de la zona. (**Ver tabla Zonas**)

`conservacion`

`int`

Código interno de conservación (**Ver tabla Conservación**)

### Disponibilidad Alquiler

**\[$alquilerdisponibilidad\]**

Con este tipo de datos obtenemos de una ficha los distintos periodos de ocupación de la vivienda, se obtendrán _n_ registros según los periodos ocupados que tenga asignados.

```php
// $datoofe contiene el campo cod_ofer de la vivienda
// obtenida en $paginación.
$where="fechafin>=$hoy and codigo=$datoofe";
Procesos("alquilerdisponibilidad",1,50,$where,"");
```

**Campo**

**Tipo de datos**

**Descripción**

`fechainicio`

Fecha `String`

Fecha inicio Ocupación

`fechafin`

Fecha `String`

Fecha fin Ocupación

### Temporadas Alquiler

**\[$alquilertemporada\]**

Con este tipo de datos obtenemos de una ficha las distintas temporadas de precios de la vivienda, se obtendrán n registros según las temporadas que tenga dadas de alta.

```php
$wheretemporada="keyclave=$datoofe";
Procesos("alquilertemporada",1,100,$wheretemporada,"");
```

**Campo**

**Tipo de datos**

**Descripción**

`diaini`

`Int`

Día inicio Temporada (1..31)

`mesini`

`Int`

Mes inicio temporada (1,,12)

`preciodia`

`Int`

Precio por día

`preciofinsemana`

`Int`

Precio por fin de semana

`preciosemana`

`Int`

Precio por semana

`preciomes`

`Int`

Precio por mes

`precioquincena`

`Int`

Precio por quincena

`diafin`

`Int`

Día fin de temporada (1..31)

`mesfin`

`Int`

Mes fin de temporada (1..12)

`titulo`

`String`

Título de temporada (Verano, Invierno, etc….)

**Filtro de alquiler de temporada en paginación**

Si le añadimos este `where` a la consulta de `paginación` obtendremos propiedades de `alquilervacacional`

`$where=$where ." and ((ofertas.precioalq>0 and tipomensual<>'MES' and tipomensual<>'mes') or keyacci in(9))";`

Luego al entrar en cada ficha con las consultas de arriba podréis mostrar una tabla de temporadas y algún calendario mostrando ocupación.

### Promociones paginación

**\[$paginacion\_promociones\]**

Con este tipo obtenemos el listado de promociones de `obra nueva`.

Este tipo sirve tanto para listar las promociones como para obtener la ficha de la promoción

```php
//para obtener el listado de obras nuevas
$where="";
Procesos("paginacion_promociones",1,100,$where,"");
```

**Campo**

**Tipo de datos**

**Descripción**

`codobra`

`int`

Código interno de la promoción

`refobra`

`varchar`

Referencia de la promoción

`precio_desde`

`int`

Precio mínimo del conjunto de propiedades de la promoción

`precio_hasta`

`int`

Precio máximo del conjunto de propiedades de la promoción

`numfotos`

`int`

Número de fotos

`ciudad`

`varchar`

Nombre de la ciudad

`zona`

`varchar`

Nombre de la zona

`numagencia`

`int`

Número de la agencia

`titulo`

`varchar`

El nombre de la promoción

`descrip`

`varchar`

La descripción de la propiedad

`nodispo`

`int`

Disponibilidad de la promoción

`foto`

`varchar`

Ruta de la foto principal

ℹ️ Hay muchos campos más, ver array `_var_dump($_``paginacion_promociones)`

### Promociones ficha

**\[$ficha\_promo\]**

Con este tipo obtenemos el listado de promociones de `obra nueva`.

Este tipo sirve tanto para listar las promociones como para obtener la ficha de la promoción

```php
//para obtener el listado de obras nuevas
$where="codobra=$codobra.$numagencia";
Procesos("ficha_promo",1,1,$where,"");
```

Se usa tanto el `codobra` como el `numagencia` separados por un punto

**Campo**

**Tipo de datos**

**Descripción**

`codobra`

`int`

Código interno de la promoción

`ref`

`varchar`

Referencia de la promoción

`precio_desde`

`int`

Precio mínimo del conjunto de propiedades de la promoción

`precio_hasta`

`int`

Precio máximo del conjunto de propiedades de la promoción

`numfotos`

`int`

Número de fotos

`ciudad`

`varchar`

Nombre de la ciudad

`zona`

`varchar`

Nombre de la zona

`numagencia`

`int`

Número de la agencia

`titulo`

`varchar`

El nombre de la promoción

`descrip`

`varchar`

La descripción de la propiedad

`nodispo`

`int`

Disponibilidad de la promoción

`foto`

`varchar`

Ruta de la foto principal

ℹ️ Hay muchos campos más como las calidades y características de la promoción, ver array `_var_dump($_``ficha_promo``_)_`

**Obtener las propiedades pertenecientes a la obra nueva**

Para filtrar las propiedades pertenecientes a una obra nueva hay que añadir al where de paginación de ofertas esto:

```php
$where .= "keypromo = $codobra";

//$codobralo obtendremos de paginacion_promociones
```

### Ficha

\[$ficha\]

**Campo**

**Tipo de datos**

**Descripción**

`cod_ofer`

`int`

Código interno de la propiedad

`ref`

`varchar`

Referencia de la propiedad

`keyacci`

`int`

Tipo de acción (1:Venta, 2:Alquiler, 3:Traspaso)

`precioinmo`

`int`

Precio de venta

`outlet`

`int`

Precio anterior

`precioalq`

`int`

Precio de alquiler

`tipomensual`

`varchar`

Tipo periodicidad alquiler MES, QUI, SEM, DIA

`numfotos`

`int`

Número de fotos

`nbtipo`

`varchar`

Nombre del tipo de propiedad

`ciudad`

`varchar`

Nombre de la ciudad

`zona`

`varchar`

Nombre de la zona

`numagencia`

`int`

Número de la agencia

`m_parcela`

`int`

Metros de parcela

`m_uties`

`int`

Metros útiles

`m_cons`

`int`

Metros construidos

`m_terraza`

`int`

Metros terraza

`banyos`

`int`

Número de baños

`aseos`

`int`

Número de aseos

`habdobles`

`int`

Habitaciones dobles

`habitaciones`

`int`

Habitaciones simples

`total_hab`

`int`

Total habitaciones

`distmar`

`int`

Distancia mar en metros

`ascensor`

`int (1 o 0)`

Valor 1: tiene ascensor, Valor 0: no tiene ascensor

`aire_con`

`int (1 o 0)`

Aire acondicionado

`Parking`

`int`

Valor 0: No tiene, Valor1: Opcional, Valor 2: Incluido

`piscina_com`

`int (1 o 0)`

Piscina comunitaria

`piscina_prop`

`int (1 o 0)`

Piscina propia

`diafano`

`int (1 o 0)`

Diáfano

`todoext`

`int (1 o 0)`

Todo exterior

`energialetra`

`varchar`

Calificación Energética (A,B,C,D,E,F,G,tramites)

`energiavalor`

`float`

Consumo Energía kWh/m2 Año

`emisionesletra`

`varchar`

Calificación Energética (A,B,C,D,E,F,G,tramites)

`emisionesvalor`

`float`

Consumo Kg CO2/m2 Año

`agencia`

`varchar`

Nombre de la agencia

`web`

`varchar`

Página web de la agencia

`emailagencia`

`varchar`

Email interno de la agencia

`telefono`

`varchar`

Teléfono de la agencia

`tourvirtual`

`int (1 o 0)`

Tour Virtual externo(\*)

`fotos360`

`int (1 o 0)`

Visor de las fotos panorámicas(\*\*)

`video`

`int (1 o 0)`

Si dispone de vídeos

`x_entorno`

`binario`

Campo binario que contiene calidades (Ver tabla Entorno)(\*\*\*)

ℹ️ Hay muchos campos más, ver array `_var_dump()_`

-   Para crear el link hacia el **tour virtual** debéis pasar los campos `_cod_ofer_` y el `_numagencia_` separados por un punto a la siguiente url: `_**http://ap.apinmo.com/fotosvr/tour.php?cod=cod_ofer.numagencia**_`

-   Para crear el link hacia el **visor de fotos panorámicas** debéis pasar los campos `_cod_ofer_` y el `_numagencia_` separados por un punto a la siguiente url: `_**http://ap.apinmo.com/fotosvr/?codigo=cod_ofer.numagencia**_`

-   El campo `x_entorno` es un campo binario, en el mismo campo se guardan varias calidades, para extraer el entorno que se quiera se debe pedir de la siguiente manera según el ID que tenga el entorno.

**Ejemplo:** _Queremos ver si la propiedad tiene activo el entorno Zonas Infantiles (Id. 12):_

```php
$bin_zona_infantil = pow(2,12); // 4096

// Comprobar si la ficha tiene zona infantil
if (($ficha[x_entorno]&$bin_zona_infantil)==$bin_zona_infantil){
	// ...
}

...

// CONSULTA MYSQL
// Agregar entorno en un filtro
// filtra propiedades que tengan zonas infantiles, el valor 4096 lo sacamos de elevar 2^12.
$where .= ' AND x_entorno&4096=4096';
```

**Al pedir el array ficha se generan estos arrays adicionales:**

**Descripciones**

**$descripciones \[cod\_ofer\]\[idioma\]**

**Campo**

**Tipo de datos**

**Descripción**

`titulo`

`varchar`

Título de la propiedad

`descrip`

`text`

Descripción de la propiedad

Ejemplo:

```php
$descripciones[35856][1]=array("titulo"=>"Titulo propiedad","descrip"=>"Descripcion...");
```

**Fotos**

**$fotos\[cod\_ofer\]**

Array con todas las url de las fotos de la ficha.

**Videos**

**$videos\[cod\_ofer\]**

Array con los códigos de **youtube** de los videos

### Listar propiedades disponibles

Dependiendo de la implementación en ocasiones necesitamos saber de antemano que cuales son las propiedades que están disponibles sin necesidad de tener que paginar entre todas las peticiones.

```php
Procesos('listar_propiedades_disponibles',1,5000,"");
$json = PedirDatos("NUMAGENCIA","PASSWORD",1, 1);
echo $json;
```

## Para retornar datos en formato JSON

Para retornar los datos en un `string` con formato de `json`, en vez de un `array`:

```php
Procesos('paginacion',1,3,"keyacci=1","fecha desc");
$json = PedirDatos("NUMAGENCIA","PASSWORD",1, 1);
echo $json; // Muestra el string retornado
```

En el ejemplo, se consulta por las propiedades que están a la venta `keyacci=1` y en orden descendente de fecha `fecha desc`. El parámetro `1` al final de la petición de `PedirDatos`, determina que los datos se servirán en `json`. Si no se especifica nada, devolverá un array global.

Si posteriormente queréis es adaptar el `string` en formato `json` a un `array`, podéis usar las funciones de php **json\_decode** y **json\_encode** (para volverla a pasar a `string`).

## Bloqueo de IP

Para garantizar una experiencia de usuario equitativa y prevenir el uso excesivo de recursos, nuestra API web implementa un control estricto sobre el número de peticiones que se pueden realizar. Es importante destacar que las direcciones IP de los usuarios que excedan el límite de peticiones establecido serán bloqueadas temporalmente para prevenir afectaciones al servicio.  
Recomendaciones para Optimizar el Uso de la API:  

-   **Evitar el Uso de la API para Scraping:** El diseño de nuestra API no está destinado para realizar scraping de nuestra web. Te recomendamos encarecidamente evitar esta práctica, ya que podría resultar en el bloqueo de tu dirección IP. Tienes otras alternativas como la carga de propiedades en tu web por XML.

-   **Optimizar el Número de Peticiones con PedirDatos**: Para minimizar el número de peticiones a nuestra API y evitar posibles bloqueos, recomendamos controlar y optimizar el uso de la función _PedirDatos_. Esto se puede lograr mediante:
    -   **Agrupación de Peticiones en una Sola Llamada a PedirDatos:** En lugar de realizar múltiples peticiones, organiza tus necesidades de datos para que puedas recopilar toda la información necesaria con una única llamada a _PedirDatos_. Esto reducirá significativamente el número de peticiones y optimizará el uso de la API.

```bash
Ejemplo
Procesos ('tipo',1,100,"","");
Procesos ('ciudad',1,100,"","");
Procesos ('destacados',1,20,"","precioinmo, precioalq");
Procesos ('paginacion',1,20,"ascensor=1","precioinmo, precioalq");
PedirDatos($numagencia,$password,$idioma);
```

En los casos que estemos bloqueando tu IP y consideres que tu implementación de nuestra API no debería superar nuestro limite de peticiones, recomendamos que te asegures no estar usando ningún tipo de proxy o servidor adicional para realizar las peticiones, ya que nuestra función `PedirDatos` **intentara obtener la IP del navegante o usuario de la web y es solo a este a quien se debe de bloquear.**

**Recomendaciones:**

-   Agrega logs dentro de la función `geturl` que se encuentra dentro de la función `PedirDatos` para confirmar que IPs se envían en parámetro `ia` en el payload de la petición

-   Si el problema continua deberás modificar nuestro código y ajustar los valores de los parámetros `ia` con la IP del usuario de la web y `ib` vacío;

![](https://procesos.apinmo.com/apiweb/doc/images/example_ip_params.png)

## Obtener log de la petición

Para obtener el log de la petición solo debemos agregar temporalmente la siguiente linea en la funcion `geturl` dentro del fichero `apiinmovilla.php` , para así guardarnos en el archivo `log_request.txt` las peticiones que se hacen a la API, con esto podemos confirmar cual es la petición real que hace tu implementación.

![](https://procesos.apinmo.com/apiweb/doc/images/log_request.png)