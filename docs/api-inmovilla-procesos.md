# API REST v1 — Inmovilla

## Índice

- [General](#general)
- [Enums](#enums)
  - [Calidades](#enums---calidades-get)
  - [Tipos](#enums---tipos-get)
  - [Países](#enums---paises-get)
  - [Ciudades](#enums---ciudades-get)
  - [Zonas](#enums---zonas-get)
- [Clientes](#clientes)
  - [Solicitar Cliente](#solicitar-cliente-get)
  - [Crear Cliente](#crear-cliente-post)
  - [Editar Cliente](#editar-cliente-put)
  - [Eliminar Cliente](#eliminar-cliente-delete)
  - [Buscar Clientes](#buscar-clientes-get)
  - [Campos de Clientes](#campos-de-clientes)
- [Propiedades y Prospectos](#propiedades-y-prospectos)
  - [Solicitar Propiedad](#solicitar-propiedad-get)
  - [Crear Propiedad](#crear-propiedad-post)
  - [Editar Propiedad](#editar-propiedad-post)
  - [Desactivar Propiedad](#desactivar-propiedad-post)
  - [Listar Propiedades](#listar-propiedades-get)
  - [Información Extra](#información-extra-get)
  - [Leads por Propiedad](#leads-por-propiedad-get)
  - [Campos de Propiedades](#campos-de-propiedades)
- [Propietarios](#propietarios)
  - [Solicitar Propietario](#solicitar-propietario-get)
  - [Crear Propietario](#crear-propietario-post)
  - [Editar Propietario](#editar-propietario-put)
  - [Eliminar Propietario](#eliminar-propietario-delete)
  - [Campos de Propietarios](#campos-de-propietarios)
- [Errores](#errores)
- [Límites de Peticiones](#límites-de-peticiones)

---

## General

**Base URL:** `https://procesos.inmovilla.com/api/v1`

Las peticiones que no se realicen vía HTTPS no serán procesadas.

### Headers requeridos

| Key | Value | Descripción |
|---|---|---|
| `Content-Type` | `application/json` | Las peticiones deben ser en formato JSON |
| `Token` | `{token_agencia}` | Genera el token en **Ajustes > Opciones > Token para API Rest** en el [CRM de Inmovilla](https://crm.inmovilla.com/panel) |

**Notas importantes:**
- La API no debe usarse para cargas masivas diarias de datos. Para eso existen procesos más óptimos — consultar en soporte@inmovilla.com.
- Los tokens caducan automáticamente tras 3 meses sin actividad.

---

## Enums

Las peticiones ENUM sirven para obtener los valores correctos de cada parámetro. No deben usarse para mapear campos en tiempo real, sino para listar y almacenar los valores previamente.

> **Límite:** 2 peticiones/minuto · 10 peticiones/10 minutos

---

### Enums — Calidades `GET`

```
GET /enums/?calidades
```

Devuelve los campos de calidades con valores booleanos (`true`/`false`).

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  { "campo": "adaptadominus", "valores": "true/false" },
  { "campo": "agua",          "valores": "true/false" },
  { "campo": "airecentral",   "valores": "true/false" },
  { "campo": "aire_con",      "valores": "true/false" },
  { "campo": "alarma",        "valores": "true/false" },
  ...
]
```

**Errores:**

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400007 | No existe el tipo — para `calidades` no es necesario asignarle ningún valor |
| 408 | 408 | Demasiadas peticiones — límite 2/minuto |

---

### Enums — Tipos `GET`

```
GET /enums/?tipos
GET /enums/?tipos={tipo}
```

Devuelve la relación campo-valor de todos los tipos de una propiedad (tipo de operación, carpintería, calefacción, etc.).

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "keyacci": [
    { "nombre": "Vender",   "valor": 1 },
    { "nombre": "Alquilar", "valor": 2 }
  ],
  "keycarpin": [
    { "nombre": "Aluminio", "valor": 1 },
    { "nombre": "Madera",   "valor": 2 },
    { "nombre": "PVC",      "valor": 3 },
    { "nombre": "Wengué",   "valor": 16 }
  ],
  ...
}
```

**Tipos disponibles:**

| Campo | Tipo | Descripción |
|---|---|---|
| `cocina_inde` | enum | Cocina independiente |
| `conservacion` | enum | Conservación / Estado de la propiedad |
| `destacado` | enum | Propiedad destacada para la web |
| `electro` | enum | Cocina equipada con electrodomésticos |
| `eninternet` | enum | Enviar a la web y/o portales inmobiliarios |
| `estadoficha` | enum | Estado de la propiedad |
| `idioma` | enum | Listado de idiomas |
| `keyacci` | enum | Tipo de operación |
| `keyagua` | enum | Tipo de agua |
| `keygua` | enum | Tipo de agua |
| `keycalefa` | enum | Tipo de calefacción |
| `keycalle` | enum | Tipo de vía |
| `keycarpin` | enum | Tipo de carpintería |
| `keycarpinext` | enum | Tipo de carpintería exterior |
| `keyelectricidad` | enum | Tipo de instalación eléctrica |
| `keyfachada` | enum | Tipo de fachada |
| `keyori` | enum | Orientación de la propiedad |
| `keysuelo` | enum | Tipo de suelo |
| `keytecho` | enum | Tipo de techo |
| `keyvista` | enum | Tipo de vista |
| `key_loca` | enum | Código de localidad/ciudad → ver [Enums - Ciudades](#enums---ciudades-get) |
| `key_tipo` | enum | Tipo de propiedad |
| `key_zona` | enum | Código de zona → ver [Enums - Zonas](#enums---zonas-get) |
| `tgascom` | enum | Periodicidad de la comunidad |
| `tipovpo` | enum | Tipo de régimen |
| `todoext` | enum | Todo exterior |
| `x_entorno` | enum | Tipo de entornos |

**Errores:**

| HTTP | Código | Descripción |
|---|---|---|
| 404 | 404001 | El tipo pasado por parámetro no existe |
| 400 | 400005 | `key_loca` incorrecto — debe ser numérico y separado por `,` |
| 400 | 400008 | Para `key_loca` usar: `/enums/?ciudades` |
| 400 | 400009 | Para `key_zona` usar: `/enums/?zonas={key_loca}` |
| 408 | 408 | Demasiadas peticiones — límite 2/minuto |

---

### Enums — Paises `GET`

```
GET /enums/?paises
```

Devuelve el listado de países con códigos ISO para usar en la petición de ciudades.

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  { "pais": "España",       "valor": "724", "iso2": "ES", "iso3": "ESP" },
  { "pais": "Portugal",     "valor": "620", "iso2": "PT", "iso3": "PRT" },
  { "pais": "Italia",       "valor": "380", "iso2": "IT", "iso3": "ITA" },
  { "pais": "Francia",      "valor": "250", "iso2": "FR", "iso3": "FRA" },
  { "pais": "Reino Unido",  "valor": "826", "iso2": "GB", "iso3": "GBR" },
  { "pais": "Andorra",      "valor": "020", "iso2": "AD", "iso3": "AND" },
  ...
]
```

**Errores:**

| HTTP | Código | Descripción |
|---|---|---|
| 408 | 400008 | Para `paises` no es necesario asignarle ningún valor |
| 408 | 408 | Demasiadas peticiones — límite 2/minuto |

---

### Enums — Ciudades `GET`

```
GET /enums/?ciudades
GET /enums/?ciudades={pais}
```

Devuelve todas las ciudades separadas por provincias, con sus códigos de país y provincia. Por defecto muestra las ciudades de España.

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  {
    "pais": 0,
    "provincia": "ALICANTE",
    "cod_prov": 4,
    "ciudades": [
      { "ciudad": "Adsubia", "key_loca": 31599 },
      { "ciudad": "Agost",   "key_loca": 31699 },
      ...
    ]
  },
  ...
]
```

**Errores:**

| HTTP | Código | Descripción |
|---|---|---|
| 408 | 400006 | El parámetro de ciudades (país) debe ser numérico |
| 408 | 408 | Demasiadas peticiones — límite 2/minuto |

---

### Enums — Zonas `GET`

```
GET /enums/?zonas={key_loca}
GET /enums/?zonas={key_loca,key_loca,key_loca}
```

Devuelve las zonas de una o varias ciudades. Pasar el `key_loca` obtenido en la petición de ciudades.

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  "31699": [
    { "zona": "Partida PozoBlanco", "key_zona": 2512711 },
    { "ciudad": "Urb. las lomas",   "key_loca": 883111 },
    ...
  ]
]
```

**Errores:**

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400005 | `key_loca` debe ser numérico y separado por `,` si son varios |
| 406 | 400006 | Para `ciudades` no es necesario asignarle ningún valor |
| 404 | 404002 | El `key_loca` solicitado no existe |
| 408 | 408 | Demasiadas peticiones — límite 2/minuto |

---

## Clientes

> **Límite:** 20 peticiones/minuto · 100 peticiones/10 minutos

---

### Solicitar Cliente `GET`

```
GET /clientes/?cod_cli={cod_cli}
```

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "cod_cli":    "13449756",
  "nombre":     "Pedro",
  "apellidos":  "Picapiedra",
  "nif":        "Z7347280G",
  "email":      "pedro@picapiedra.com",
  "calle":      "Av. Libertad",
  "numero":     "123",
  "planta":     "3",
  "puerta":     "der",
  "escalera":   "3",
  "cp":         "03201",
  "localidad":  "Elche",
  "provincia":  "Alicante",
  "pais":       "España",
  "nacionalidad": "Española",
  "telefono1":  666554433,
  "telefono2":  666221100,
  ...
}
```

---

### Crear Cliente `POST`

```
POST /clientes/
```

**Petición:**

```json
{
  "nombre":    "Pedro",
  "apellidos": "Picapiedra",
  "nif":       "12345678K",
  "email":     "pedro.picapiedra@inmovilla.com",
  "telefono1": 666554433,
  "telefono2": 666221100
}
```

**Respuesta:**

```json
HTTP/1.0 201 Created
{
  "cod_cli": 11223344,
  "codigo":  201,
  "mensaje": "Cliente creado y vinculado a la propiedad con cod_ofer 12345678"
}
```

---

### Editar Cliente `PUT`

```
PUT /clientes/
```

Solo enviar los campos a modificar. El `cod_cli` es obligatorio.

**Petición:**

```json
{
  "cod_cli": 11223344,
  "email":   "emailejemplo@inmovilla.com"
}
```

**Respuesta:**

```json
HTTP/1.0 202 Accepted
{
  "cod_cli": 11223344,
  "codigo":  202,
  "mensaje": "Cliente actualizado"
}
```

---

### Eliminar Cliente `DELETE`

```
DELETE /clientes/{cod_cli}
```

Si el cliente está vinculado a una propiedad o demanda, no se eliminará hasta desvincularlo.

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "codigo":  200,
  "mensaje": "Cliente eliminado"
}
```

---

### Buscar Clientes `GET`

```
GET /clientes/buscar/?telefono={telefono}&email={email}
```

Busca clientes por teléfono y/o email. El campo `telefono` busca en los tres campos de teléfono de cada cliente. Los parámetros se concatenan con `AND`.

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  {
    "cod_cli":   "13449756",
    "nombre":    "Pedro",
    "apellidos": "Picapiedra",
    "email":     "pedro@picapiedra.com",
    "...": "...",
    "agente": {
      "id":            "12326",
      "nombre":        "Antonio",
      "apellidos":     "Piedraita",
      "email":         "apiedraita@piedraita.com",
      "email_interno": "apiedraita.4856@inmovilla.com",
      "telefono1":     "65564646578",
      "telefono2":     "96556568964"
    }
  },
  ...
]
```

---

### Campos de Clientes

| Campo | Tipo | Descripción | Requerido en |
|---|---|---|---|
| `cod_cli` | numérico | Identificador único | GET · PUT · DELETE |
| `nombre` | texto | Nombre | POST |
| `apellidos` | texto | Apellidos | |
| `nif` | texto | NIF / DNI / CIF | |
| `email` | texto | Email | |
| `calle` | texto | Dirección | |
| `numero` | texto | Número de la dirección | |
| `planta` | numérico | Nº de planta | |
| `puerta` | texto | Puerta | |
| `escalera` | texto | Escalera | |
| `cp` | texto | Código Postal | |
| `localidad` | texto | Localidad / Ciudad | |
| `provincia` | texto | Provincia | |
| `pais` | texto | País | |
| `nacionalidad` | texto | Nacionalidad | |
| `prefijotel1` | numérico | Prefijo teléfono fijo | |
| `prefijotel2` | numérico | Prefijo teléfono móvil | |
| `prefijotel3` | numérico | Prefijo otro teléfono | |
| `prefijotel4` | numérico | Prefijo teléfono fijo cónyuge | |
| `prefijotel5` | numérico | Prefijo teléfono móvil cónyuge | |
| `telefono1` | numérico | Teléfono fijo | |
| `telefono2` | numérico | Teléfono móvil | |
| `telefono3` | numérico | Otro teléfono | |
| `telefono4` | numérico | Teléfono fijo cónyuge | |
| `telefono5` | numérico | Teléfono móvil cónyuge | |
| `fechanacimiento` | fecha | Fecha de nacimiento — formato `1984-09-05 23:25:00` | |
| `altacliente` | fecha | Fecha de alta | |
| `conyuge` | texto | Nombre del cónyuge | |
| `conemail` | texto | Email del cónyuge | |
| `connif` | texto | NIF del cónyuge | |
| `keymedio` | numérico | Medio de contacto | |
| `keycomercial` | numérico | ID del comercial gestor | |
| `captadopor` | numérico | ID del comercial captador | |
| `observacion` | texto | Observaciones | |
| `nonewsletters` | numérico | Newsletters: `0` Pendiente · `3` Validado Oficina · `1` Rechazado · `6` Fallo entrega | |
| `gesauto` | numérico | Envío por email: `0` Pendiente · `2` Validado Oficina · `4` Rechazado · `5` Validado Portal · `6` Fallo entrega | |
| `rgpdwhats` | numérico | Envío por WhatsApp: `0` Pendiente · `2` Validado Oficina · `4` Rechazado · `5` Validado Portal · `6` Fallo entrega | |
| `enviosauto` | booleano | Activar envíos automáticos por email (respeta `gesauto`) | |

**Errores de Clientes:**

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400001 | Petición mal formada — comprobar parseo JSON |
| 400 | 400002 | No se han enviado parámetros |
| 400 | 400003 | Error al crear, editar o eliminar el cliente |
| 404 | 404002 | No existe ningún cliente con los parámetros solicitados |
| 405 | 405001 | Método HTTP no permitido |
| 406 | 406001 | Campo `{x}` requerido |
| 406 | 406002 | Campo `{x}` no válido |
| 406 | 406004 | Cliente vinculado — no se puede eliminar |
| 406 | 406006 | El código `{x}` facilitado no existe |
| 408 | 408 | Demasiadas peticiones — límite 20/minuto |

---

## Propiedades y Prospectos

> **Límite:** 10 peticiones/minuto · 50 peticiones/10 minutos

---

### Solicitar Propiedad `GET`

```
GET /propiedades/?cod_ofer={cod_ofer}
GET /propiedades/?ref={ref}
```

| Parámetro | Descripción | Prioridad |
|---|---|---|
| `cod_ofer` | Código único de la propiedad | Alta |
| `ref` | Referencia pública de la propiedad | Baja |

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "cod_ofer":    87654321,
  "keyacci":     1,
  "banyos":      2,
  "keycli":      12345678,
  "fecha":       "2018-09-05 11:15:00",
  "keyori":      0,
  "ref":         "ABC-63653",
  "nodisponible": 0,
  "precio":      115000,
  "precioinmo":  120000,
  "key_loca":    37899,
  "key_zona":    1214099,
  "key_tipo":    3399,
  "calle":       "Avenida Libertad",
  "planta":      5,
  "numero":      123,
  ...
}
```

#### Fotografías de una Propiedad

Las fotos se construyen con la siguiente URL:

```
https://fotos15.inmovilla.com/{numagencia}/{cod_ofer}/{fotoletra}-{N}.jpg
```

| Parámetro | Descripción | Ejemplo |
|---|---|---|
| `numagencia` | ID de la agencia | `413` |
| `cod_ofer` | Código del inmueble | `9983361` |
| `fotoletra` | Identificador base de la foto | `8` |
| `N` | Número incremental desde `1` hasta `numfotos` | `1`, `2`, `3`... |

Ejemplo: `https://fotos15.inmovilla.com/413/9983361/8-1.jpg`

---

### Crear Propiedad `POST`

```
POST /propiedades/
```

**Petición:**

```json
{
  "ref":         "36532543",
  "keyacci":     1,
  "key_tipo":    3399,
  "key_loca":    "368799",
  "nodisponible": false,
  "precioinmo":  250000,
  "banyos":      3,
  "habitaciones": 2,
  "fotos": {
    "1": { "url": "https://crm.inmovilla.com/imagenes/foto001.jpg", "posicion": 1 },
    "2": { "url": "https://crm.inmovilla.com/imagenes/foto002.jpg", "posicion": 2 }
  }
}
```

El parámetro `fotos` es un objeto con las URLs de las fotografías. Si la URL no cambia en una edición, la foto no se sobreescribe.

Para crear un **prospecto**, enviar `"prospecto": true`.

**Respuesta:**

```json
HTTP/1.0 201 Created
{
  "codigo":  201,
  "mensaje": "Propiedad guardada"
}
```

---

### Editar Propiedad `POST`

```
POST /propiedades/
```

Mismo método que crear. El campo `ref` identifica la propiedad a actualizar. Enviar todos los campos con sus valores actualizados.

> Se puede convertir una propiedad en prospecto (y viceversa) enviando el campo `prospecto` con el valor deseado.

---

### Desactivar Propiedad `POST`

```
POST /propiedades/
```

Mismo método que crear/editar. Enviar `"nodisponible": true` junto con el `ref` exacto de la propiedad.

---

### Listar Propiedades `GET`

```
GET /propiedades/?listado
```

Devuelve propiedades y prospectos ordenados por fecha de actualización. Los prospectos con referencia vacía no aparecen.

**Respuesta:**

```json
HTTP/1.0 200 OK
[
  { "cod_ofer": 8284709, "ref": "PR00182", "nodisponible": false, "prospecto": true, "fechaact": "2018-09-20 10:12:25" },
  { "cod_ofer": 8284690, "ref": "PR00180", "nodisponible": false, "prospecto": true, "fechaact": "2018-09-19 17:10:07" },
  ...
]
```

---

### Información Extra `GET`

```
GET /propiedades/?extrainfo&cod_ofer={cod_ofer}
GET /propiedades/?extrainfo&ref={ref}
```

| Parámetro | Descripción | Prioridad |
|---|---|---|
| `cod_ofer` | Código único de la propiedad | Alta |
| `ref` | Referencia de la propiedad | Media |

Devuelve el estado de publicación en portales y los leads recibidos.

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "publishinfo": {
    "idealista": {
      "state":               "11",
      "message":             "Sent successfully.",
      "alerts_number":       "12345",
      "quality_percentage":  "62",
      "publication_url":     "https://www.idealista.com/inmueble/123456789"
    },
    "pisoscom": {
      "state":            "10",
      "message":          "Sent successfully.",
      "publication_url":  "https://www.pisos.com/detalle/123456789"
    },
    "fotocasa": {
      "state":   "12",
      "message": "Successfully deactivated."
    },
    ...
  },
  "leads": [
    {
      "date":              "2025-08-22 10:08:55",
      "language":          "es_ES",
      "source":            "idealista.com",
      "contact_firstname": "Name",
      "contact_lastname":  "Lastname",
      "contact_phone":     "+34 123456789",
      "contact_mobile":    "",
      "contact_email":     "example@email.com",
      "message":           "Mensaje de ejemplo."
    },
    ...
  ]
}
```

**Campos de publishinfo:**

| Campo | Tipo | Descripción |
|---|---|---|
| `publishinfo` | array | Portales donde está publicada la propiedad |
| `publishinfo/state` | int | Estado de publicación en el portal |
| `publishinfo/message` | texto | Último mensaje del proceso de publicación |
| `publishinfo/alerts_number` | int | Cruces realizados con el portal *(solo Idealista)* |
| `publishinfo/quality_percentage` | int | Calidad del anuncio *(solo Idealista)* |
| `publishinfo/publication_url` | texto | URL del anuncio en el portal *(no disponible en todos)* |

**Valores de `state`:**

| Valor | Descripción |
|---|---|
| `10` | Propiedad publicada correctamente |
| `11` | Propiedad publicada en el microsite |
| `12` | Propiedad no publicada |
| `7` | Publicada con alerta |
| `9` | No publicada por error |

---

### Leads por Propiedad `GET`

```
GET /propiedades/?leads&dateStart={dateStart}&dateEnd={dateEnd}&page={page}
```

Devuelve los leads de la agencia filtrados por fecha. Máximo 10 resultados por página.

| Parámetro | Descripción | Prioridad |
|---|---|---|
| `dateStart` | Fecha de inicio | Alta |
| `dateEnd` | Fecha de fin | Alta |
| `page` | Página de resultados | Alta |

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "leads": [
    {
      "date":              "2025-08-22 10:08:55",
      "language":          "es_ES",
      "source":            "idealista.com",
      "contact_firstname": "Name",
      "contact_lastname":  "Lastname",
      "contact_phone":     "+34 123456789",
      "contact_mobile":    "",
      "contact_email":     "example@email.com",
      "message":           "Mensaje de ejemplo."
    },
    ...
  ]
}
```

**Campos de leads:**

| Campo | Tipo | Descripción |
|---|---|---|
| `leads` | array | Leads recibidos |
| `leads/date` | string | Fecha de recepción del lead |
| `leads/language` | string | Idioma en formato ISO |
| `leads/source` | string | Medio por el que llegó el lead |
| `leads/contact_firstname` | string | Nombre del contacto |
| `leads/contact_lastname` | string | Apellido del contacto |
| `leads/contact_phone` | string | Teléfono fijo del contacto |
| `leads/contact_mobile` | string | Teléfono móvil del contacto |
| `leads/contact_email` | string | Email del contacto |
| `leads/message` | string | Mensaje o anotación del lead |

---

### Campos de Propiedades

Los campos marcados como **Requerido en POST** son obligatorios al crear una propiedad.

**Campos requeridos en POST:** `ref`, `keyacci`, `key_tipo`, `key_loca`

#### Identificación y estado

| Campo | Tipo | Descripción | Requerido en |
|---|---|---|---|
| `ref` | texto | Referencia única de la propiedad | POST |
| `cod_ofer` | numérico | Código único interno | — |
| `keyacci` | enum | Tipo de operación | POST |
| `key_tipo` | enum | Tipo de propiedad | POST |
| `key_loca` | enum | Código de localidad/ciudad | POST |
| `key_zona` | enum | Código de zona | — |
| `nodisponible` | booleano | Si la propiedad no está disponible | — |
| `prospecto` | booleano | Indica si es un prospecto | — |
| `estadoficha` | enum | Estado de la propiedad | — |
| `eninternet` | enum | Enviar a web y/o portales | — |
| `destacado` | enum | Propiedad destacada para la web | — |
| `exclu` | booleano | En exclusiva | — |
| `alta_exclusiva` | fecha | Inicio de exclusiva — formato `2018-06-05 18:30:15` | — |
| `baja_exclusiva` | fecha | Fin de exclusiva — formato `2018-06-05 18:30:15` | — |
| `fecha` | fecha | Fecha de alta | — |
| `fechaact` | fecha | Fecha de última actualización | — |
| `fechamod` | fecha | Fecha de modificación | — |
| `urlprospecto` | texto | URL del prospecto captado | — |
| `captadopor` | numérico | Código del agente captador | — |
| `keyagente` | numérico | Código del agente gestor | — |
| `numsucursal` | numérico | ID de la agencia sucursal | — |

#### Ubicación

| Campo | Tipo | Descripción |
|---|---|---|
| `calle` | texto | Dirección |
| `numero` | texto | Número del portal |
| `planta` | numérico | Nº de planta |
| `puerta` | texto | Puerta |
| `escalera` | texto | Escalera |
| `cp` | texto | Código postal |
| `zona` | texto | Nombre de la zona (si no se envía `key_zona`) |
| `latitud` | numérico | Coordenada latitud |
| `longitud` | numérico | Coordenada longitud |
| `numplanta` | numérico | Número total de plantas del edificio |
| `keycalle` | enum | Tipo de vía |

#### Precios

| Campo | Tipo | Descripción |
|---|---|---|
| `precioinmo` | numérico | Precio para la inmobiliaria |
| `precioalq` | numérico | Precio de alquiler |
| `precioiva` | numérico | IVA del precio |
| `porceniva` | numérico | Porcentaje del IVA |
| `preciotraspaso` | numérico | Precio del traspaso |
| `outlet` | numérico | Precio anterior (si ha sido rebajado) |
| `gastos_com` | numérico | Cuota de la comunidad |
| `comunidadincluida` | booleano | Si la cuota de comunidad está incluida |
| `tgascom` | enum | Periodicidad de la comunidad |
| `comision` | numérico | Comisión |
| `cesioncom` | numérico | Comisión de cesión |
| `tipomensual` | texto | Periodicidad del alquiler |
| `opcioncompra` | booleano | Opción a compra |

#### Características principales

| Campo | Tipo | Descripción |
|---|---|---|
| `habitaciones` | numérico | Habitaciones simples |
| `habdobles` | numérico | Habitaciones dobles |
| `banyos` | numérico | Baños |
| `aseos` | numérico | Aseos |
| `salon` | numérico | Salón |
| `parking` | numérico | Parking |
| `plaza_gara` | numérico | Plaza de garaje |
| `nplazasparking` | numérico | Cantidad de plazas de parking |
| `antiguedad` | numérico | Año de construcción |
| `conservacion` | enum | Estado de conservación |
| `tipovpo` | enum | Tipo de régimen |

#### Superficies (metros)

| Campo | Tipo | Descripción |
|---|---|---|
| `m_cons` | numérico | Metros construidos |
| `m_utiles` | numérico | Metros útiles |
| `m_parcela` | numérico | Metros de parcela |
| `m_terraza` | numérico | Metros de terraza |
| `m_cocina` | numérico | Metros de cocina |
| `m_comedor` | numérico | Metros de comedor |
| `m_fachada` | numérico | Metros de fachada |
| `m_sotano` | numérico | Metros de sótano |
| `m_altillo` | numérico | Metros del altillo |
| `altillo` | numérico | Altillo |
| `alturatecho` | numérico | Altura del techo |
| `distmar` | numérico | Distancia al mar (metros) |

#### Descripción y títulos

| Campo | Tipo | Descripción |
|---|---|---|
| `tituloes` | texto | Título en Castellano/Español |
| `tituloingles` | texto | Título en Inglés |
| `titulofrances` | texto | Título en Francés |
| `titulocatalan` | texto | Título en Catalán |
| `tituloaleman` | texto | Título en Alemán |
| `tituloruso` | texto | Título en Ruso |
| `descripciones` | texto | Descripción en Castellano/Español |
| `descripcioningles` | texto | Descripción en Inglés |
| `descripcionfrances` | texto | Descripción en Francés |
| `descripcioncatalan` | texto | Descripción en Catalán |
| `descripcionaleman` | texto | Descripción en Alemán |
| `descripcionruso` | texto | Descripción en Ruso |
| `tfachada` | texto | Descripción de la fachada |
| `tinterior` | texto | Descripción del interior |

#### Certificado energético

| Campo | Tipo | Descripción |
|---|---|---|
| `energialetra` | texto | Letra del certificado energético |
| `energiavalor` | numérico | Consumo en KWh/m² |
| `energiarecibido` | numérico | Estado: `0` Pendiente · `1` Aportado · `2` En Trámites · `3` Exento |
| `emisionesletra` | texto | Letra del certificado de emisiones |
| `emisionesvalor` | numérico | Emisiones en Kg CO₂/m² |

#### Datos catastrales

| Campo | Tipo | Descripción |
|---|---|---|
| `rcatastral` | texto | Referencia catastral |
| `rdirfinca` | texto | Dirección de la finca |
| `registrod` | texto | Registro |
| `rtomo` | numérico | Tomo |
| `rlibro` | numérico | Libro |
| `rfolio` | numérico | Folio |
| `rnumero` | numérico | Número |
| `rletra` | texto | Letra |
| `rnumeroinscr` | numérico | Número inscripción |

#### Materiales y construcción

| Campo | Tipo | Descripción |
|---|---|---|
| `keycarpin` | enum | Tipo de carpintería |
| `keycarpinext` | enum | Tipo de carpintería exterior |
| `keyfachada` | enum | Tipo de fachada |
| `keysuelo` | enum | Tipo de suelo |
| `keytecho` | enum | Tipo de techo |
| `keyori` | enum | Orientación |
| `keyvista` | enum | Tipo de vista |
| `keyelectricidad` | enum | Tipo de instalación eléctrica |
| `keycalefa` | enum | Tipo de calefacción |
| `keyagua` | enum | Tipo de agua |
| `keygua` | enum | Tipo de agua |
| `todoext` | enum | Todo exterior |
| `x_entorno` | enum | Tipo de entornos |
| `cocina_inde` | enum | Cocina independiente |
| `electro` | enum | Cocina con electrodomésticos |

#### Equipamiento y servicios (booleanos)

| Campo | Descripción | Campo | Descripción |
|---|---|---|---|
| `adaptadominus` | Adaptado PMR | `aire_con` | Aire acondicionado |
| `airecentral` | Aire central | `alarma` | Alarma |
| `alarmaincendio` | Alarma de incendio | `alarmarobo` | Alarma de robo |
| `apartseparado` | Apartamento separado | `arma_empo` | Armario empotrado |
| `ascensor` | Ascensor | `balcon` | Balcón |
| `bar` | Bar | `barbacoa` | Barbacoa |
| `bombafriocalor` | Bomba frío/calor | `buhardilla` | Buhardilla |
| `cajafuerte` | Caja fuerte | `calefaccion` | Calefacción |
| `calefacentral` | Calefacción central | `chimenea` | Chimenea |
| `depoagua` | Depósito de agua | `descalcificador` | Descalcificador |
| `despensa` | Despensa | `diafano` | Diáfano |
| `esquina` | Esquina | `galeria` | Galería |
| `garajedoble` | Garaje doble | `gasciudad` | Gas ciudad |
| `gimnasio` | Gimnasio | `golf` | Golf |
| `habjuegos` | Habitación de juegos | `haycartel` | Cartel colocado |
| `hidromasaje` | Hidromasaje | `hilomusical` | Hilo musical |
| `jacuzzi` | Jacuzzi | `jardin` | Jardín |
| `lavanderia` | Lavandería | `linea_tlf` | Línea telefónica |
| `luminoso` | Luminoso | `luz` | Luz |
| `metro` | Metro | `mirador` | Mirador |
| `montacargas` | Montacargas | `muebles` | Muebles |
| `ojobuey` | Ojos de buey | `patio` | Patio |
| `pergola` | Pérgola | `piscina_com` | Piscina comunitaria |
| `piscina_prop` | Piscina propia | `preinstaacc` | Preinstalación A/A |
| `preinsthmusi` | Preinstalación hilo musical | `primera_linea` | Primera línea |
| `puertasauto` | Puertas automáticas | `puerta_blin` | Puerta blindada |
| `riegoauto` | Riego automático | `rural` | Rural |
| `satelite` | Satélite | `sauna` | Sauna |
| `solarium` | Solarium | `sotano` | Sótano |
| `tenis` | Pista de tenis propia | `teniscom` | Pista de tenis comunitaria |
| `terraza` | Terraza | `terrazaacris` | Terraza acristalada |
| `tranvia` | Tranvía | `trastero` | Trastero |
| `tren` | Tren | `trifasica` | Eléctrico trifásico |
| `tv` | Televisión | `urbanizacion` | Urbanización |
| `vallado` | Vallado | `vestuarios` | Vestuarios |
| `video_port` | Videoportero | `vigilancia_24` | Vigilancia 24H |
| `vistasalmar` | Vistas al mar | `zona_de_paso` | Zona de paso |
| `zonasinfantiles` | Zonas infantiles | `agua` | Agua |
| `arboles` | Árboles | `autobuses` | Autobuses |
| `centrico` | Céntrico | `centros_comerciales` | Centros comerciales |
| `centros_medicos` | Centros médicos | `cerca_de_universidad` | Cerca universidad |
| `colegios` | Colegios | `costa` | Costa |
| `hospitales` | Hospitales | `montana` | Montaña |
| `parques` | Parques | `supermercados` | Supermercados |

#### Otros campos

| Campo | Tipo | Descripción |
|---|---|---|
| `fotos` | objeto | URLs de las fotografías |
| `numllave` | texto | Número de llavero |
| `contactadopor` | texto | Medio de contacto/captación |
| `entidadbancaria` | numérico | Entidad bancaria |
| `haycartel` | booleano | Cartel de venta/alquiler colocado |

**Errores de Propiedades:**

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400001 | Petición mal formada — comprobar parseo JSON |
| 400 | 400002 | No se han enviado parámetros |
| 400 | 400003 | Error al guardar la propiedad |
| 400 | 400004 | Error al insertar la propiedad |
| 405 | 405001 | Método HTTP no permitido |
| 406 | 406001 | Campo `{x}` requerido |
| 406 | 406002 | Campo `{x}` no válido |
| 406 | 406003 | El prospecto ya fue convertido a propiedad |

---

## Propietarios

> **Límite:** 20 peticiones/minuto · 100 peticiones/10 minutos

---

### Solicitar Propietario `GET`

```
GET /propietarios/?cod_cli={cod_cli}
GET /propietarios/?cod_ofer={cod_ofer}
GET /propietarios/?ref={ref}
```

| Parámetro | Descripción | Prioridad |
|---|---|---|
| `cod_cli` | Código único del propietario | Alta |
| `cod_ofer` | Código único de la propiedad | Media |
| `ref` | Referencia de la propiedad | Baja |

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "cod_cli":    "13449756",
  "nombre":     "Pedro",
  "apellidos":  "Picapiedra",
  "nif":        "Z7347280G",
  "email":      "pedro@picapiedra.com",
  "calle":      "Av. Libertad",
  "numero":     "123",
  "planta":     "3",
  "puerta":     "der",
  "escalera":   "3",
  "cp":         "03201",
  "localidad":  "Elche",
  "provincia":  "Alicante",
  "pais":       "España",
  "nacionalidad": "Española",
  "telefono1":  "666554433",
  "telefono2":  "666221100",
  "telefono3":  "",
  "fechanacimiento": null,
  "altacliente": "2018-03-01 12:53:25",
  "facebook":   null,
  "conyuge":    "Vilma Picapiedra",
  "conemail":   "vilma@picapiedra.com",
  "connif":     "Y4752447V",
  "propiedades": [
    {
      "cod_ofer":    "5288705",
      "ref":         "00633",
      "panel":       "https://www.haypisos.com/cliente/?cliente=01413_3617145288705",
      "estadistica": "https://www.haypisos.com/cliente/?estadistica=01413_3617145288705",
      "disponible":  true
    },
    ...
  ]
}
```

---

### Crear Propietario `POST`

```
POST /propietarios/
```

Requiere el `cod_ofer` de la propiedad relacionada.

**Petición:**

```json
{
  "nombre":    "Pedro",
  "apellidos": "Picapiedra",
  "nif":       "12345678K",
  "email":     "pedro.picapiedra@inmovilla.com",
  "telefono1": 666554433,
  "telefono2": 666221100,
  "cod_ofer":  12345678
}
```

**Respuesta:**

```json
HTTP/1.0 201 Created
{
  "cod_cli": 11223344,
  "codigo":  201,
  "mensaje": "Propietario creado y vinculado a la propiedad con cod_ofer 12345678"
}
```

---

### Editar Propietario `PUT`

```
PUT /propietarios/
```

Solo enviar los campos a modificar. El `cod_cli` es obligatorio.

**Petición:**

```json
{
  "cod_cli": 11223344,
  "email":   "pedro.picapiedra.gomez@inmovilla.com"
}
```

**Respuesta:**

```json
HTTP/1.0 202 Accepted
{
  "cod_cli": 11223344,
  "codigo":  202,
  "mensaje": "Propietario actualizado"
}
```

---

### Eliminar Propietario `DELETE`

```
DELETE /propietarios/{cod_cli}
```

Si el propietario está vinculado a una propiedad o demanda, no se eliminará hasta desvincularlo.

**Respuesta:**

```json
HTTP/1.0 200 OK
{
  "codigo":  200,
  "mensaje": "Propietario eliminado"
}
```

---

### Campos de Propietarios

| Campo | Tipo | Descripción | Requerido en |
|---|---|---|---|
| `cod_cli` | numérico | Identificador único | GET · PUT · DELETE |
| `cod_ofer` | numérico | Identificador de la propiedad vinculada | POST |
| `nombre` | texto | Nombre | POST |
| `apellidos` | texto | Apellidos | |
| `nif` | texto | NIF / DNI / CIF | |
| `email` | texto | Email | |
| `calle` | texto | Dirección | |
| `numero` | texto | Número de la dirección | |
| `planta` | numérico | Nº de planta | |
| `puerta` | texto | Puerta | |
| `escalera` | texto | Escalera | |
| `cp` | texto | Código Postal | |
| `localidad` | texto | Localidad / Ciudad | |
| `provincia` | texto | Provincia | |
| `pais` | texto | País | |
| `nacionalidad` | texto | Nacionalidad | |
| `prefijotel1` | numérico | Prefijo teléfono fijo | |
| `prefijotel2` | numérico | Prefijo teléfono móvil | |
| `prefijotel3` | numérico | Prefijo otro teléfono | |
| `telefono1` | numérico | Teléfono principal | |
| `telefono2` | numérico | Otro teléfono | |
| `telefono3` | numérico | Otro teléfono | |
| `fechanacimiento` | fecha | Fecha de nacimiento — formato `1984-09-05 23:25:00` | |
| `altacliente` | fecha | Fecha de alta | |
| `conyuge` | texto | Nombre del cónyuge | |
| `conemail` | texto | Email del cónyuge | |
| `connif` | texto | NIF del cónyuge | |
| `observacion` | texto | Observaciones | |
| `nonewsletters` | numérico | Newsletters: `0` Pendiente · `3` Validado Oficina · `1` Rechazado · `6` Fallo entrega | |
| `gesauto` | numérico | Envío por email: `0` Pendiente · `2` Validado Oficina · `4` Rechazado · `5` Validado Portal · `6` Fallo entrega | |
| `rgpdwhats` | numérico | Envío por WhatsApp: `0` Pendiente · `2` Validado Oficina · `4` Rechazado · `5` Validado Portal · `6` Fallo entrega | |

**Errores de Propietarios:**

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400001 | Petición mal formada — comprobar parseo JSON |
| 400 | 400002 | No se han enviado parámetros |
| 400 | 400003 | Error al crear, editar o eliminar el propietario |
| 404 | 404001 | No existe ninguna propiedad con el identificador solicitado |
| 405 | 405001 | Método HTTP no permitido |
| 406 | 406001 | Campo `{x}` requerido |
| 406 | 406002 | Campo `{x}` no válido |
| 406 | 406004 | Propietario vinculado — no se puede eliminar |
| 406 | 406006 | El código `{x}` facilitado no existe |
| 408 | 408 | Demasiadas peticiones — límite 20/minuto |

---

## Errores

Tabla de códigos de error comunes a todos los recursos:

| HTTP | Código | Descripción |
|---|---|---|
| 400 | 400001 | Petición mal formada — comprobar parseo JSON |
| 400 | 400002 | No se han enviado parámetros |
| 400 | 400003 | Error al crear, editar o eliminar |
| 404 | 404001 | Recurso no encontrado |
| 405 | 405001 | Método HTTP no permitido |
| 406 | 406001 | Campo requerido no enviado |
| 406 | 406002 | Campo no válido o mal escrito |
| 406 | 406004 | Registro vinculado — no se puede eliminar |
| 408 | 408 | Demasiadas peticiones |

---

## Límites de Peticiones

| Tipo | Intervalo | Peticiones máx. | Error |
|---|---|---|---|
| `enums` | Cada minuto | 2 | 408 |
| `enums` | Cada 10 minutos | 10 | 408 |
| `clientes` | Cada minuto | 20 | 408 |
| `clientes` | Cada 10 minutos | 100 | 408 |
| `propiedades` | Cada minuto | 10 | 408 |
| `propiedades` | Cada 10 minutos | 50 | 408 |
| `propietarios` | Cada minuto | 20 | 408 |
| `propietarios` | Cada 10 minutos | 100 | 408 |
