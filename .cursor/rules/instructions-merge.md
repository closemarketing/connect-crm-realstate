# Funcionalidad Merge variables

Esta funcionalidad trata de guardar el emparejamiento de variables de la api y los campos personalizados de WordPress.

Irá en la página wp-admin/admin.php?page=iip-options&tab=iip-merge.

Antes de cargar la página, deberá coger todos los campos personalizados de WordPress en una variable. También en otra los campos de la api seleccionada.

Deberá haber una fila que se pueda repetir con dos columnas:
- Variable de la API
- Campo personalizado de WordPress

Al final guardará un array con pares de nombres de API => campo personalizado.

Ambos campos deberían dejarte que se busque con un tipo de campo select2. 

Usa select2 con composer.