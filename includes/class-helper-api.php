<?php
/**
 * Library for API connection
 *
 * Documentation API.
 * Inmovilla: https://procesos.apinmo.com/api/v1/apidoc/
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Api Connection.
 *
 * @since 1.0.0
 */
class API {
	/**
	 * Request to API from Anaconda CRM
	 *
	 * @param string $method Method of API request.
	 * @param string $endpoint Endpoint of API request.
	 * @param array  $query Query of API request.
	 * @return array
	 */
	public static function request_anaconda( $method = 'GET', $endpoint, $query = array() ) {
		$settings    = get_option( 'conncrmreal_settings' );
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		if ( empty( $apipassword ) ) {
			return array(
				'status' => 'error',
				'data'   => __( 'API password is empty', 'connect-crm-realstate' ),
			);
		}
		// API connection.
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $apipassword,
			),
			'timeout' => 300,
		);
		if ( ! empty( $query ) ) {
			$args['body'] = $query;
		}

		$response    = wp_remote_request( 'https://api.anaconda.guru/api/v1/' . $endpoint, $args );
		$result_body = wp_remote_retrieve_body( $response );
		$code        = (int) substr( wp_remote_retrieve_response_code( $response ), 0, 1 );
		$data        = json_decode( $result_body, true );

		if ( is_wp_error( $response ) || empty( $response['body'] ) || 2 !== $code ) {
			return array(
				'status' => 'error',
				'data'   => isset( $data['message'] ) ? $data['message'] : '',
			);
		} else {
			return array(
				'status' => 'ok',
				'data'   => $data,
			);
		}
	}

	/**
	 * Gets url info from api inmovilla
	 *
	 * @param string $endpoint URL of API inmovilla.
	 * @param string $method Method of API inmovilla.
	 * @param array  $query Query of API inmovilla.
	 * @return array
	 */
	public static function request_inmovilla( $endpoint, $method = 'GET', $query = array() ) {
		$settings    = get_option( 'conncrmreal_settings' );
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		if ( empty( $apipassword ) ) {
			return array(
				'status' => 'error',
				'data'   => __( 'API password is empty', 'connect-crm-realstate' ),
			);
		}
		// API connection.
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-type' => 'application/json',
				'token'        => $apipassword,
			),
		);
		if ( ! empty( $query ) ) {
			$args['body'] = wp_json_encode( $query );
		}
		$url       = 'https://procesos.inmovilla.com/api/v1/' . $endpoint;
		$response  = wp_remote_request( $url, $args );
		$body      = wp_remote_retrieve_body( $response );
		$body_json = json_decode( $body, true );
		$code      = isset( $body_json['error'] ) ? (int) round( $body_json['error'] / 100, 0 ) : 0;

		if ( 4 === $code ) {
			$message = $body_json['codigo'] . ': ' . $body_json['mensaje'];

			return array(
				'status' => 'error',
				'data'   => $message,
			);
		} else {
			return array(
				'status' => 'ok',
				'data'   => 'POST' === $method && isset( $body_json['cod_cli'] ) ? $body_json['cod_cli'] : $body_json,
			);
		}
	}

	/**
	 * Request to properties API from CRM
	 *
	 * @param int    $page Page of properties.
	 * @param string $changed_from Date of changed properties.
	 * @return array
	 */
	public static function get_properties( $page = 0, $changed_from = '' ) {
		$settings     = get_option( 'conncrmreal_settings' );
		$settings_crm = isset( $settings['crm'] ) ? $settings['crm'] : 'anaconda';
		if ( 'anaconda' === $settings_crm && ! empty( $page ) ) {
			return self::request_anaconda( 'GET', 'properties/?page=' . $page );
		} elseif ( 'anaconda' === $settings_crm && ! empty( $changed_from ) ) {
			$query = array(
				'changed_from' => $changed_from,
			);
			return self::request_anaconda( 'POST', 'properties/search', $query );
		} elseif ( 'inmovilla' === $settings_crm ) {
			return self::request_inmovilla( 'GET', 'properties' );
		}
	}

	/**
	 * Request to properties API from CRM
	 *
	 * @return array
	 */
	public static function get_total_properties() {
		$settings     = get_option( 'conncrmreal_settings' );
		$settings_crm = isset( $settings['crm'] ) ? $settings['crm'] : 'anaconda';
		if ( 'anaconda' === $settings_crm ) {
			return self::request_anaconda( 'POST', 'properties/total_search_properties' );
		} elseif ( 'inmovilla' === $settings_crm ) {
		}
	}

	/**
	 * Request Fields from API CRM.
	 *
	 * @param string $crm CRM to get fields.
	 * @return array
	 */
	public static function get_properties_fields( $crm = 'anaconda' ) {
		if ( 'anaconda' === $crm ) {
			return self::get_fields_anaconda();
		} elseif ( 'inmovilla' === $crm ) {
			return self::get_fields_inmovilla();
		}
	}
	private static function get_fields_anaconda() {
		return array();
	}

	/**
	 * Geg fields from Inmovilla
	 *
	 * @return array
	 */
	private static function get_fields_inmovilla() {
		$inmovilla_fields = get_transient( 'ccrmre_query_inmovilla_fields' );
		$inmovilla_fields = false;
		if ( ! $inmovilla_fields ) {
			// Generate value for inmovilla_fields.
			$result_properties = self::request_inmovilla( 'propiedades/?listado' );

			if ( 'ok' !== $result_properties['status'] && ! isset( $result_properties['data'][0]['cod_ofer'] ) ) {
				return $result_properties;
			}

			$cod_ofer        = $result_properties['data'][0]['cod_ofer'];
			$result_property = self::request_inmovilla( 'propiedades/?cod_ofer=' . $cod_ofer );

			$inmovilla_fields = array(
				'status' => 'error',
				'data'   => __( 'Error getting fields', 'connect-crm-realstate' ),
			);

			if ( 'ok' === $result_property['status'] && isset( $result_property['data'] ) ) {
				$fields_slug = array_keys( $result_property['data'] );
				$fields_slug = array_filter( $fields_slug );

				$inmovilla_fields = array(
					'status' => 'ok',
					'data'   => array_map(
						function ( $slug ) {
							return array(
								'name'  => $slug,
								'label' => self::get_description_field_inmovilla( $slug ),
							);
						},
						$fields_slug
					),
				);
			}
			set_transient( 'ccrmre_query_inmovilla_fields', $inmovilla_fields, DAY_IN_SECONDS );
		}

		return $inmovilla_fields;
	}

	private static function get_description_field_inmovilla( $slug ) {
		$labels = array(
			'adaptadominus' => 'Adaptado PMR (Personas Movilidad Reducida)',
			'agua' => 'Agua',
			'airecentral' => 'Aire central',
			'aire_con' => 'Aire acondicionado',
			'alarma' => 'Alarma',
			'alarmaincendio' => 'Alarma de incendio',
			'alarmarobo' => 'Alarma de robo',
			'alta_exclusiva' => 'Fecha de inicio de exclusiva (Formato 2018-06-05 18:30:15)',
			'altillo' => 'Altillo',
			'alturatecho' => 'Altura del techo',
			'antiguedad' => 'Año de construcción',
			'apartseparado' => 'Apartamento separado',
			'arboles' => 'Árboles',
			'arma_empo' => 'Armario empotrado',
			'ascensor' => 'Ascensor',
			'aseos' => 'Aseos',
			'autobuses' => 'Autobuses',
			'baja_exclusiva' => 'Fecha de fin de exclusiva (Formato 2018-06-05 18:30:15)',
			'balcon' => 'Balcón',
			'banyos' => 'Baños',
			'bar' => 'Bar',
			'barbacoa' => 'Barbacoa',
			'bombafriocalor' => 'Bomba frío/calor',
			'buhardilla' => 'Buhardilla',
			'cajafuerte' => 'Caja fuerte',
			'calefaccion' => 'Calefacción',
			'calefacentral' => 'Calefacción central',
			'calle' => 'Dirección',
			'captadopor' => 'Código del agente captador',
			'centrico' => 'Céntrico',
			'centros_comerciales' => 'Centros comerciales',
			'centros_medicos' => 'Centros Médicos',
			'cerca_de_universidad' => 'Cerca de la Universidad',
			'cesioncom' => 'Comisión de cesión',
			'chimenea' => 'Chimenea',
			'cocina_inde' => 'Cocina independiente',
			'colegios' => 'Colegios',
			'comision' => 'Comisión',
			'comunidadincluida' => 'Si viene incluida la cuota de la comunidad',
			'conservacion' => 'Conservación / Estado de la propiedad',
			'contactadopor' => 'Medio por el que ha sido contactado/captado el inmueble',
			'costa' => 'Costa',
			'cp' => 'Código postal',
			'depoagua' => 'Depósito de agua',
			'descalcificador' => 'Descalcificador',
			'descripcionaleman' => 'Descripción en Alemán',
			'descripcioncatalan' => 'Descripción en Catalán',
			'descripciones' => 'Descripción en Castellano/Español',
			'descripcionfrances' => 'Descripción en Francés',
			'descripcioningles' => 'Descripción en Inglés',
			'descripcionruso' => 'Descripción en Ruso',
			'despensa' => 'Despensa',
			'destacado' => 'Propiedad destacada para la web',
			'diafano' => 'Diáfano',
			'distmar' => 'Distancia al mar (en metros)',
			'electro' => 'Cocina equipada con electrodomésticos',
			'emisionesletra' => 'Emisiones (Letra del certificado de emisiones)',
			'emisionesvalor' => 'Emisiones (valor en Kg CO2/m2)',
			'energialetra' => 'Energía (Letra del certificado energético)',
			'energiarecibido' => 'Estado del certificado energético',
			'energiavalor' => 'Energía (consumo en KW h/m2)',
			'eninternet' => 'Enviar a la web y/o portales inmobiliarios',
			'entidadbancaria' => 'Entidad bancaria',
			'escalera' => 'Dirección (Escalera)',
			'esquina' => 'Esquina',
			'estadoficha' => 'Estado de la propiedad',
			'exclu' => 'La propiedad está en exclusiva',
			'fecha' => 'Fecha de alta (Formato 2018-06-05 18:30:15)',
			'fechaact' => 'Fecha de última actualización (Formato 2018-06-05 18:30:15)',
			'fechamod' => 'Fecha de modificación (Formato 2018-06-05 18:30:15)',
			'galeria' => 'Galería',
			'garajedoble' => 'Garaje doble',
			'gasciudad' => 'Gas ciudad',
			'gastos_com' => 'Cuota de la comunidad',
			'gimnasio' => 'Gimnasio',
			'golf' => 'Golf',
			'habdobles' => 'Habitaciones dobles',
			'habitaciones' => 'Habitaciones simples',
			'habjuegos' => 'Habitación de juegos',
			'haycartel' => 'Tiene cartel de venta/alquiler colocado',
			'hidromasaje' => 'Hidromasaje',
			'hilomusical' => 'Hilo musical',
			'hospitales' => 'Hospitales',
			'jacuzzi' => 'Jacuzzi',
			'jardin' => 'Jardín',
			'keyacci' => 'Tipo de operación',
			'keyagente' => 'Código del agente gestor',
			'keycalefa' => 'Tipo de calefacción',
			'keycalle' => 'Tipo de vía',
			'keycarpin' => 'Tipo de carpintería',
			'keycarpinext' => 'Tipo de carpintería exterior',
			'keyelectricidad' => 'Tipo de instalación eléctrica',
			'keyfachada' => 'Tipo de fachada',
			'keyori' => 'Orientación de la propiedad',
			'keysuelo' => 'Tipo de suelo',
			'keytecho' => 'Tipo de techo',
			'keyvista' => 'Tipo de vista',
			'key_loca' => 'Código de la localidad/ciudad. (Véase: Enums - Ciudades)',
			'key_tipo' => 'Tipo de propiedad. (Véase: Enums - Tipo Propiedades)',
			'key_zona' => 'Código de la zona. (Véase: Enums - Zonas)',
			'latitud' => 'Coordenada (Latitud)',
			'lavanderia' => 'Lavandería',
			'linea_tlf' => 'Línea telefónica',
			'longitud' => 'Coordenada (Longitud)',
			'luminoso' => 'Luminoso',
			'luz' => 'Luz',
			'metro' => 'Metro',
			'mirador' => 'Mirador',
			'montacargas' => 'Montacargas',
			'montana' => 'Montaña',
			'muebles' => 'Muebles',
			'm_altillo' => 'Metros del altillo',
			'm_cocina' => 'Metros de la cocina',
			'm_comedor' => 'Metros del comedor',
			'm_cons' => 'Metros construidos',
			'm_fachada' => 'Metros de la fachada',
			'm_parcela' => 'Metros de la parcela',
			'm_sotano' => 'Metros del sótano',
			'm_terraza' => 'Metros de la terraza',
			'm_utiles' => 'Metros útiles',
			'nodisponible' => 'Si la propiedad no está disponible',
			'numero' => 'Dirección (Número del portal)',
			'numllave' => 'Número de llavero',
			'numplanta' => 'Dirección (Número total de plantas)',
			'numsucursal' => 'Id de la agencia sucursal',
			'ojobuey' => 'Ojos de buey',
			'opcioncompra' => 'La propiedad tiene opción a compra',
			'outlet' => 'Precio anterior del inmueble (por si se ha rebajado)',
			'parking' => 'Parking',
			'parques' => 'Parques',
			'patio' => 'Patio',
			'pergola' => 'Pérgola',
			'piscina_com' => 'Piscina comunitaria',
			'piscina_prop' => 'Piscina propia',
			'planta' => 'Dirección (Nº de planta)',
			'plaza_gara' => 'Plaza de garaje',
			'porceniva' => 'Porcentaje del IVA',
			'precioalq' => 'Precio de Alquiler',
			'precioinmo' => 'Precio de la propiedad para la inmobiliaria',
			'precioiva' => 'IVA del precio',
			'preciotraspaso' => 'Precio del traspaso de la propiedad',
			'preinstaacc' => 'Preinstalación del aire acondicionado',
			'preinsthmusi' => 'Preinstalación de hilo musical',
			'primera_linea' => 'Si está en primera línea',
			'prospecto' => 'Indica si la propiedad es un prospecto',
			'puerta' => 'Dirección (Puerta)',
			'puertasauto' => 'Puertas automáticas',
			'puerta_blin' => 'Puerta blindada',
			'rcatastral' => 'Dato catastral (Referencia catastral)',
			'rdirfinca' => 'Dato catastral (Dirección de la finca)',
			'ref' => 'Referencia de la propiedad (Debe ser única para cada propiedad)',
			'registrod' => 'Dato catastral (Registro)',
			'rfolio' => 'Dato catastral (Folio)',
			'riegoauto' => 'Riego automático',
			'rletra' => 'Dato catastral (Letra)',
			'rlibro' => 'Dato catastral (Libro)',
			'rnumero' => 'Dato catastral (Número)',
			'rnumeroinscr' => 'Dato catastral (Número inscripción)',
			'rtomo' => 'Dato catastral (Tomo)',
			'rural' => 'Rural',
			'salon' => 'Salón',
			'satelite' => 'Satélite',
			'sauna' => 'Sauna',
			'solarium' => 'Solarium',
			'sotano' => 'Sótano',
			'supermercados' => 'Supermercados',
			'tenis' => 'Pista de tenis propia',
			'teniscom' => 'Pista de tenis comunitaria',
			'terraza' => 'Terraza',
			'terrazaacris' => 'Terraza acristalada',
			'tfachada' => 'Descripción del fachada',
			'tgascom' => 'Periodicidad de la comunidad',
			'tinterior' => 'Descripción del interior',
			'tipomensual' => 'Periodicidad del alquiler',
			'tipovpo' => 'Tipo de régimen',
			'tituloaleman' => 'Título en Alemán',
			'titulocatalan' => 'Título en Catalán',
			'tituloes' => 'Título en Castellano/Español',
			'titulofrances' => 'Título en Francés',
			'tituloingles' => 'Título en Inglés',
			'tituloruso' => 'Título en Ruso',
			'todoext' => 'Todo exterior',
			'tranvia' => 'Tranvía',
			'trastero' => 'Trastero',
			'tren' => 'Tren',
			'trifasica' => 'Sistema eléctrico trifásico',
			'tv' => 'Televisión',
			'urbanizacion' => 'Urbanización',
			'urlprospecto' => 'URL del prospecto captado',
			'vallado' => 'Vallado',
			'vestuarios' => 'Vestuarios',
			'video_port' => 'Videoportero',
			'vigilancia_24' => 'Vigilancia 24H',
			'vistasalmar' => 'Tiene vistas al mar',
			'x_entorno' => 'Tipo de entornos',
			'zona' => 'Si no se envía key_zona, se puede enviar el nombre de la zona aquí.',
			'zonasinfantiles' => 'Zonas infantiles',
			'zona_de_paso' => 'Zona de Paso',
			'fotos' => 'Debe ser un objeto que contenga las url de las fotografías.',
		);
		return isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
	}
}
