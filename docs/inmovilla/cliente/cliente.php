<?php

include("apiinmovilla.php");

//Configurar arrays
Procesos ('tipos',1,100,"","");
Procesos ('ciudades',1,100,"","");
Procesos ('zonas',1,100,"key_loca=32899","");
Procesos ('ficha',1,1,"ofertas.cod_ofer=350914","");
Procesos ('destacados',1,20,"","precioinmo, precioalq");
Procesos ('paginacion',1,100,"ascensor=1","precioinmo, precioalq");

//ejemplo $addnumagencia='_84';
$addnumagencia='';
//Pedir Datos
PedirDatos(NUM_AGENCIA,"CONTRASEÑA",1);

?>