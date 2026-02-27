<?php
/*Procesos ('tipo',1,100,"","");
Procesos ('ciudad',1,100,"","");
Procesos ('zonas',1,100,"key_loca=2013","");
Procesos ('destacados',1,20,"","precioinmo, precioalq");
Procesos ('paginacion',1,20,"ascensor=1","precioinmo, precioalq");
PedirDatos($numagencia,$password,$idioma);*/

function Procesos ($tipo,$posinicial,$numelementos,$where,$orden)
{
    global $arrpeticiones;
    
    if (!isset($arrpeticiones) || is_null($arrpeticiones))
        $arrpeticiones = array();

    $arrpeticiones[count($arrpeticiones)]=$tipo;
    $arrpeticiones[count($arrpeticiones)]=$posinicial;
    $arrpeticiones[count($arrpeticiones)]=$numelementos;
    $arrpeticiones[count($arrpeticiones)]=$where;
    $arrpeticiones[count($arrpeticiones)]=$orden;
}


function Pedirdatos($numagencia,$password,$idioma,$json=0){

    global $arrpeticiones;
    global $addnumagencia;

    $texto="$numagencia$addnumagencia;$password;$idioma;";
    $texto=$texto ."lostipos";

    for  ($i=0;$i<count($arrpeticiones);$i++) {
        $texto=$texto .";" .$arrpeticiones[$i];
    }

    $texto=rawurlencode($texto);

    $parametros="param=$texto";

    $elDominio = $_SERVER['SERVER_NAME'] ?? 'dacisa.local';
    $parametros .= "&elDominio=$elDominio";


    $url="https://apiweb.inmovilla.com/apiweb/apiweb.php";

    if($json) {
        $contenido  = geturl($url,"{$parametros}&json=1");
    }else{
        @eval(geturl($url,$parametros));
    }

    //echo geturl($url,"param=$texto");

    //@eval (file_get_contents($url."?param=$texto"));

    //echo file_get_contents($url);

    $GLOBALS['arrpeticiones'] = array();

    if($json) {
        return $contenido;
    }
}

function geturl($url,$campospost)
{

    function getClientIP() {
        $proxy_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CF_CONNECTING_IP'
        );

        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

        foreach ($proxy_headers as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]); // Tomamos la primera IP de la lista
                if ($ip != $remote_addr) {
                    return $ip;
                }
            }
        }

        // Fall back to server IP when running from CLI (no REMOTE_ADDR).
        if (empty($remote_addr)) {
            return defined('EXTERNAL_IP') ? EXTERNAL_IP : gethostbyname(gethostname());
        }

        return $remote_addr;
    }

    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: ";

    $cookie = sys_get_temp_dir() . '/inmovilla_cookies.txt';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS,'');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if (strlen($campospost)>0) {
        //los datos tienen que ser reales, de no ser asi se desactivara el servicio
        $campospost=$campospost . "&ia=" . getClientIP();
        curl_setopt($ch, CURLOPT_POSTFIELDS, $campospost);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    $page = curl_exec($ch);
    curl_close($ch);

    if(defined('DEPURAR_API_INMOVILLA') && DEPURAR_API_INMOVILLA === true) {
        static $id_petition = null;

        if(empty($id_petition)) {
            $id_petition = rand(100000, 999999) . '_' . time();
        }

        file_put_contents(__DIR__ . '/apiinmovilla.log', date('Y-m-d H:i:s') . " - id_petition: {$id_petition} - parametros: {$campospost}" . PHP_EOL, FILE_APPEND);
        file_put_contents(__DIR__ . '/apiinmovilla.log', date('Y-m-d H:i:s') . " - id_petition: {$id_petition} - respuesta: {$page}" . PHP_EOL, FILE_APPEND);
    }

    return $page;
}
?>