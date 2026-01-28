<?php
/*
Procesos ('tipo',1,100,"","");
Procesos ('ciudad',1,100,"","");
Procesos ('zonas',1,100,"key_loca=2013","");
Procesos ('destacados',1,20,"","precioinmo, precioalq");
Procesos ('paginacion',1,20,"ascensor=1","precioinmo, precioalq");
PedirDatos($numagencia,$password,$idioma);*/

function Procesos( $tipo, $posinicial, $numelementos, $where, $orden ) {
	global $arrpeticiones;

	$arrpeticiones[ count( $arrpeticiones ) ] = $tipo;
	$arrpeticiones[ count( $arrpeticiones ) ] = $posinicial;
	$arrpeticiones[ count( $arrpeticiones ) ] = $numelementos;
	$arrpeticiones[ count( $arrpeticiones ) ] = $where;
	$arrpeticiones[ count( $arrpeticiones ) ] = $orden;
}


function Pedirdatos( $numagencia, $password, $idioma, $json = 0 ) {

	global $arrpeticiones;
	global $addnumagencia;

	$texto = "$numagencia$addnumagencia;$password;$idioma;";
	$texto = $texto . 'lostipos';

	for ( $i = 0;$i < count( $arrpeticiones );$i++ ) {
		$texto = $texto . ';' . $arrpeticiones[ $i ];
	}

	$texto = rawurlencode( $texto );

	$url = 'https://apiweb.inmovilla.com/apiweb/apiweb.php';

	if ( $json ) {
		$contenido = geturl( $url, "param=$texto&json=1" );
	} else {
		@eval( geturl( $url, "param=$texto" ) );
	}

	// echo geturl($url,"param=$texto");

	// @eval (file_get_contents($url."?param=$texto"));

	// echo file_get_contents($url);

	$GLOBALS['arrpeticiones'] = array();

	if ( $json ) {
		return $contenido;
	}
}

function geturl( $url, $campospost ) {
	$header[0]  = 'Accept: text/xml,application/xml,application/xhtml+xml,';
	$header[0] .= 'text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
	$header[]   = 'Cache-Control: max-age=0';
	$header[]   = 'Connection: keep-alive';
	$header[]   = 'Keep-Alive: 300';
	$header[]   = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
	$header[]   = 'Accept-Language: en-us,en;q=0.5';
	$header[]   = 'Pragma: ';

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, '' );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	if ( strlen( $campospost ) > 0 ) {
		// los datos tienen que ser reales, de no ser asi se desactivara el servicio
		$campospost = $campospost . '&ia=' . $_SERVER['REMOTE_ADDR'] . '&ib=' . $_SERVER['HTTP_X_FORWARDED_FOR'];
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $campospost );
	}
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3' );
	$page = curl_exec( $ch );
	curl_close( $ch );

	return $page;
}
