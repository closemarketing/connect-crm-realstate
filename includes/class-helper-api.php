<?php
/**
 * Library for API connection
 *
 * Documentation API.
 * Inmovilla: https://procesos.apinmo.com/apiweb/doc/index.html
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
	 * @param string $endpoint Endpoint of API request.
	 * @param string $method Method of API request.
	 * @param array  $query Query of API request.
	 * @return array
	 */
	public static function request_anaconda( $endpoint, $method = 'GET', $query = array() ) {
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
	 * Request to Inmovilla API
	 *
	 * Based on official Inmovilla API documentation:
	 * https://procesos.apinmo.com/apiweb/doc/index.html
	 *
	 * @param string $tipo Type of data (paginacion, tipos, ciudades, zonas, etc).
	 * @param int    $pos_inicial Start position.
	 * @param int    $num_elementos Number of elements to retrieve.
	 * @param string $where Where clause for filtering.
	 * @param string $orden Order clause.
	 * @param int    $idioma Language ID (1 = Spanish).
	 * @return array
	 */
	public static function request_inmovilla( $tipo = 'paginacion', $pos_inicial = 1, $num_elementos = 50, $where = '', $orden = '', $idioma = 1 ) {
		$settings    = get_option( 'conncrmreal_settings' );
		$numagencia  = isset( $settings['numagencia'] ) ? $settings['numagencia'] : '';
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		// Validate required settings.
		if ( empty( $numagencia ) || empty( $apipassword ) ) {
			return array(
				'status' => 'error',
				'data'   => __( 'Inmovilla API credentials are not configured', 'connect-crm-realstate' ),
			);
		}

		// Build the parameter string following Inmovilla format.
		// Format: numagencia;password;idioma;lostipos;tipo;posinicial;numelementos;where;orden.
		$texto  = $numagencia . ';';
		$texto .= $apipassword . ';';
		$texto .= $idioma . ';';
		$texto .= 'lostipos;';
		$texto .= $tipo . ';';
		$texto .= $pos_inicial . ';';
		$texto .= $num_elementos . ';';
		$texto .= $where . ';';
		$texto .= $orden;

		// Encode parameters.
		$texto = rawurlencode( $texto );

		// Build POST body with IP tracking for API security.
		$body  = 'param=' . $texto;
		$body .= '&json=1'; // Request JSON response.
		$body .= '&ia=' . self::get_client_ip();
		$body .= '&ib=' . self::get_forwarded_ip();

		// Prepare request arguments.
		$args = array(
			'method'     => 'POST',
			'headers'    => array(
				'Accept'          => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
				'Cache-Control'   => 'max-age=0',
				'Connection'      => 'keep-alive',
				'Keep-Alive'      => '300',
				'Accept-Charset'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
				'Accept-Language' => 'en-us,en;q=0.5',
				'Pragma'          => '',
			),
			'body'       => $body,
			'user-agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
			'sslverify'  => false,
			'timeout'    => 60,
		);

		// Make API request.
		$url      = 'https://apiweb.inmovilla.com/apiweb/apiweb.php';
		$response = wp_remote_post( $url, $args );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'data'   => $response->get_error_message(),
			);
		}

		// Get response body.
		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		// Check response code.
		if ( 200 !== $code ) {
			return array(
				'status' => 'error',
				'data'   => sprintf(
					/* translators: %d: HTTP response code */
					__( 'Inmovilla API returned error code: %d', 'connect-crm-realstate' ),
					$code
				),
			);
		}

		// Decode JSON response.
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$message  = __( 'Invalid JSON response from Inmovilla API', 'connect-crm-realstate' );
			$message .= is_string( $body ) ? ' - ' . $body : '';
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		}

		// Return successful response.
		return array(
			'status' => 'ok',
			'data'   => $data,
		);
	}

	/**
	 * Get client IP address
	 *
	 * Tries to get the real client IP from various proxy headers.
	 *
	 * @return string Client IP address.
	 */
	private static function get_client_ip() {
		$proxy_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'HTTP_CF_CONNECTING_IP',
		);

		foreach ( $proxy_headers as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
				$ip  = trim( $ips[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Get forwarded IP address
	 *
	 * Gets the X-Forwarded-For header value for Inmovilla API.
	 *
	 * @return string Forwarded IP address.
	 */
	private static function get_forwarded_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}
		return '';
	}

	/**
	 * Get pagination size based on CRM type
	 *
	 * @param string $crm CRM type (optional, if not provided will get from settings).
	 * @return int Pagination size.
	 */
	public static function get_pagination_size( $crm = '' ) {
		if ( empty( $crm ) ) {
			$settings = get_option( 'conncrmreal_settings' );
			$crm      = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		}

		$pagination_sizes = array(
			'anaconda'  => 200,
			'inmovilla' => 50,
		);

		return isset( $pagination_sizes[ $crm ] ) ? $pagination_sizes[ $crm ] : 100;
	}

	/**
	 * Request to properties API from CRM
	 *
	 * @param int    $page Page of properties (for Anaconda) or page number for pagination (Inmovilla).
	 * @param string $changed_from Date of changed properties (format: Y-m-d H:i:s).
	 * @return array
	 */
	public static function get_properties( $page = 0, $changed_from = '' ) {
		$settings     = get_option( 'conncrmreal_settings' );
		$settings_crm = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		$pagination   = self::get_pagination_size( $settings_crm );

		if ( 'anaconda' === $settings_crm ) {
			if ( ! empty( $page ) ) {
				return self::request_anaconda( 'properties/?page=' . $page );
			} elseif ( ! empty( $changed_from ) ) {
				$query = array(
					'changed_from' => $changed_from,
				);
				return self::request_anaconda( 'properties/search', 'POST', $query );
			}
		} elseif ( 'inmovilla' === $settings_crm ) {
			// For Inmovilla, use pagination type to get properties.
			$pos_inicial   = $page > 0 ? ( ( $page - 1 ) * $pagination ) + 1 : 1;
			$num_elementos = $pagination;
			$where         = '';
			$orden         = 'fecha desc'; // Order by date descending.

			// If we have a changed_from date, filter by date.
			if ( ! empty( $changed_from ) ) {
				// Convert date to format YYYY-MM-DD.
				$changed_timestamp = strtotime( $changed_from );
				if ( $changed_timestamp ) {
					$date_filter = gmdate( 'Y-m-d', $changed_timestamp );
					$where       = "fechaact>='{$date_filter}'";
				}
			}

			$result = self::request_inmovilla( 'paginacion', $pos_inicial, $num_elementos, $where, $orden );

			// Process the response to match expected format.
			if ( 'ok' === $result['status'] && isset( $result['data']['paginacion'] ) ) {
				$properties = array();
				$total      = count( $result['data']['paginacion'] );

				// Skip first element (metadata) and get properties.
				for ( $i = 1; $i < $total; $i++ ) {
					if ( isset( $result['data']['paginacion'][ $i ] ) ) {
						$properties[] = $result['data']['paginacion'][ $i ];
					}
				}

				return array(
					'status' => 'ok',
					'data'   => $properties,
					'meta'   => isset( $result['data']['paginacion'][0] ) ? $result['data']['paginacion'][0] : array(),
				);
			}

			return $result;
		}

		return array(
			'status' => 'error',
			'data'   => __( 'CRM type not configured', 'connect-crm-realstate' ),
		);
	}

	/**
	 * Request total count of properties from CRM
	 *
	 * @return array
	 */
	public static function get_total_properties() {
		$settings     = get_option( 'conncrmreal_settings' );
		$settings_crm = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		if ( 'anaconda' === $settings_crm ) {
			return self::request_anaconda( 'properties/total_search_properties', 'POST' );
		} elseif ( 'inmovilla' === $settings_crm ) {
			// For Inmovilla, use listar_propiedades_disponibles to get all property codes.
			$result = self::request_inmovilla( 'listar_propiedades_disponibles', 1, 5000 );

			if ( 'ok' === $result['status'] && isset( $result['data']['listar_propiedades_disponibles'] ) ) {
				$total = count( $result['data']['listar_propiedades_disponibles'] ) - 1; // Subtract metadata row.
				return array(
					'status' => 'ok',
					'data'   => array( 'total' => $total ),
				);
			}

			return $result;
		}

		return array(
			'status' => 'error',
			'data'   => __( 'CRM type not configured', 'connect-crm-realstate' ),
		);
	}

	/**
	 * Request to get a single property from CRM
	 *
	 * @param array|string $property ID of property or incomplete property array.
	 * @param string       $crm CRM type (optional).
	 * @return array
	 */
	public static function get_property( $property, $crm = '' ) {
		if ( empty( $crm ) ) {
			$settings = get_option( 'conncrmreal_settings' );
			$crm      = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		}

		if ( 'anaconda' === $crm ) {
			// Anaconda returns complete property data in listings.
			return $property;
		} elseif ( 'inmovilla' === $crm ) {
			// For Inmovilla, use 'ficha' type to get complete property details.
			$property_id = is_array( $property ) ? $property['cod_ofer'] : $property;
			$where       = "cod_ofer='{$property_id}'";

			$result = self::request_inmovilla( 'ficha', 1, 1, $where );

			if ( 'ok' === $result['status'] && isset( $result['data']['ficha'][1] ) ) {
				// Merge additional arrays if available.
				$property_data = $result['data']['ficha'][1];

				// Add descriptions if available.
				if ( isset( $result['data']['descripciones'][ $property_id ] ) ) {
					$property_data['descripciones'] = reset( $result['data']['descripciones'][ $property_id ] );
				}

				// Add photos if available.
				if ( isset( $result['data']['fotos'][ $property_id ] ) ) {
					$property_data['fotos'] = $result['data']['fotos'][ $property_id ];
				}

				// Add videos if available.
				if ( isset( $result['data']['videos'][ $property_id ] ) ) {
					$property_data['videos'] = $result['data']['videos'][ $property_id ];
				}

				return $property_data;
			}

			// Return original property if request fails.
			return $property;
		}

		return $property;
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

		return array();
	}

	/**
	 * Get fields from Anaconda
	 *
	 * @return array
	 */
	private static function get_fields_anaconda() {
		$anaconda_fields = get_transient( 'ccrmre_query_anaconda_fields' );

		if ( ! $anaconda_fields ) {
			// Get a sample property to extract fields.
			$result = self::request_anaconda( 'properties/?page=1' );

			if ( 'ok' !== $result['status'] || empty( $result['data']['data'] ) ) {
				return array(
					'status' => 'error',
					'data'   => __( 'Error getting Anaconda fields. Please check your API connection.', 'connect-crm-realstate' ),
				);
			}

			// Get the first property to extract field structure.
			$sample_property = $result['data']['data'][0];
			$fields_slug     = array_keys( $sample_property );
			$fields_slug     = array_filter( $fields_slug );

			$anaconda_fields = array(
				'status' => 'ok',
				'data'   => array_map(
					function ( $slug ) {
						return array(
							'name'  => $slug,
							'label' => ucwords( str_replace( '_', ' ', $slug ) ),
						);
					},
					$fields_slug
				),
			);

			set_transient( 'ccrmre_query_anaconda_fields', $anaconda_fields, DAY_IN_SECONDS );
		}

		return $anaconda_fields;
	}

	/**
	 * Get fields from Inmovilla
	 *
	 * @return array
	 */
	private static function get_fields_inmovilla() {
		$inmovilla_fields = get_transient( 'ccrmre_query_inmovilla_fields' );

		if ( ! $inmovilla_fields ) {
			// Get a sample property to extract fields.
			$result_properties = self::request_inmovilla( 'paginacion', 1, 1 );

			if ( 'ok' !== $result_properties['status'] || ! isset( $result_properties['data']['paginacion'][1] ) ) {
				$message  = __( 'Error getting Inmovilla fields. Please check your API connection.', 'connect-crm-realstate' );
				$message .= is_string( $result_properties['data'] ) ? ' - ' . $result_properties['data'] : '';

				return array(
					'status' => 'error',
					'data'   => $message,
				);
			}

			// Get the first property to extract field structure.
			$sample_property = $result_properties['data']['paginacion'][1];
			$fields_slug     = array_keys( $sample_property );
			$fields_slug     = array_filter( $fields_slug );

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

			set_transient( 'ccrmre_query_inmovilla_fields', $inmovilla_fields, DAY_IN_SECONDS );
		}

		return $inmovilla_fields;
	}

	/**
	 * Adds descriptions for fields in Inmovilla
	 *
	 * @param string $slug Slug of field.
	 * @return string
	 */
	private static function get_description_field_inmovilla( $slug ) {
		$labels = array(
			'adaptadominus'        => 'Adaptado PMR (Personas Movilidad Reducida)',
			'agua'                 => 'Agua',
			'airecentral'          => 'Aire central',
			'aire_con'             => 'Aire acondicionado',
			'alarma'               => 'Alarma',
			'alarmaincendio'       => 'Alarma de incendio',
			'alarmarobo'           => 'Alarma de robo',
			'alta_exclusiva'       => 'Fecha de inicio de exclusiva (Formato 2018-06-05 18 : 30: 15)',
			'altillo'              => 'Altillo',
			'alturatecho'          => 'Altura del techo',
			'antiguedad'           => 'Año de construcción',
			'apartseparado'        => 'Apartamento separado',
			'arboles'              => 'Árboles',
			'arma_empo'            => 'Armario empotrado',
			'ascensor'             => 'Ascensor',
			'aseos'                => 'Aseos',
			'autobuses'            => 'Autobuses',
			'baja_exclusiva'       => 'Fecha de fin de exclusiva (Formato 2018-06-05 18    : 30: 15)',
			'balcon'               => 'Balcón',
			'banyos'               => 'Baños',
			'bar'                  => 'Bar',
			'barbacoa'             => 'Barbacoa',
			'bombafriocalor'       => 'Bomba frío/calor',
			'buhardilla'           => 'Buhardilla',
			'cajafuerte'           => 'Caja fuerte',
			'calefaccion'          => 'Calefacción',
			'calefacentral'        => 'Calefacción central',
			'calle'                => 'Dirección',
			'captadopor'           => 'Código del agente captador',
			'centrico'             => 'Céntrico',
			'centros_comerciales'  => 'Centros comerciales',
			'centros_medicos'      => 'Centros Médicos',
			'cerca_de_universidad' => 'Cerca de la Universidad',
			'cesioncom'            => 'Comisión de cesión',
			'chimenea'             => 'Chimenea',
			'cocina_inde'          => 'Cocina independiente',
			'colegios'             => 'Colegios',
			'comision'             => 'Comisión',
			'comunidadincluida'    => 'Si viene incluida la cuota de la comunidad',
			'conservacion'         => 'Conservación / Estado de la propiedad',
			'contactadopor'        => 'Medio por el que ha sido contactado/captado el inmueble',
			'costa'                => 'Costa',
			'cp'                   => 'Código postal',
			'depoagua'             => 'Depósito de agua',
			'descalcificador'      => 'Descalcificador',
			'descripcionaleman'    => 'Descripción en Alemán',
			'descripcioncatalan'   => 'Descripción en Catalán',
			'descripciones'        => 'Descripción en Castellano/Español',
			'descripcionfrances'   => 'Descripción en Francés',
			'descripcioningles'    => 'Descripción en Inglés',
			'descripcionruso'      => 'Descripción en Ruso',
			'despensa'             => 'Despensa',
			'destacado'            => 'Propiedad destacada para la web',
			'diafano'              => 'Diáfano',
			'distmar'              => 'Distancia al mar (en metros)',
			'electro'              => 'Cocina equipada con electrodomésticos',
			'emisionesletra'       => 'Emisiones (Letra del certificado de emisiones)',
			'emisionesvalor'       => 'Emisiones (valor en Kg CO2/m2)',
			'energialetra'         => 'Energía (Letra del certificado energético)',
			'energiarecibido'      => 'Estado del certificado energético',
			'energiavalor'         => 'Energía (consumo en KW h/m2)',
			'eninternet'           => 'Enviar a la web y/o portales inmobiliarios',
			'entidadbancaria'      => 'Entidad bancaria',
			'escalera'             => 'Dirección (Escalera)',
			'esquina'              => 'Esquina',
			'estadoficha'          => 'Estado de la propiedad',
			'exclu'                => 'La propiedad está en exclusiva',
			'fecha'                => 'Fecha de alta (Formato 2018-06-05 18                : 30: 15)',
			'fechaact'             => 'Fecha de última actualización (Formato 2018-06-05 18: 30: 15)',
			'fechamod'             => 'Fecha de modificación (Formato 2018-06-05 18        : 30: 15)',
			'galeria'              => 'Galería',
			'garajedoble'          => 'Garaje doble',
			'gasciudad'            => 'Gas ciudad',
			'gastos_com'           => 'Cuota de la comunidad',
			'gimnasio'             => 'Gimnasio',
			'golf'                 => 'Golf',
			'habdobles'            => 'Habitaciones dobles',
			'habitaciones'         => 'Habitaciones simples',
			'habjuegos'            => 'Habitación de juegos',
			'haycartel'            => 'Tiene cartel de venta/alquiler colocado',
			'hidromasaje'          => 'Hidromasaje',
			'hilomusical'          => 'Hilo musical',
			'hospitales'           => 'Hospitales',
			'jacuzzi'              => 'Jacuzzi',
			'jardin'               => 'Jardín',
			'keyacci'              => 'Tipo de operación',
			'keyagente'            => 'Código del agente gestor',
			'keycalefa'            => 'Tipo de calefacción',
			'keycalle'             => 'Tipo de vía',
			'keycarpin'            => 'Tipo de carpintería',
			'keycarpinext'         => 'Tipo de carpintería exterior',
			'keyelectricidad'      => 'Tipo de instalación eléctrica',
			'keyfachada'           => 'Tipo de fachada',
			'keyori'               => 'Orientación de la propiedad',
			'keysuelo'             => 'Tipo de suelo',
			'keytecho'             => 'Tipo de techo',
			'keyvista'             => 'Tipo de vista',
			'key_loca'             => 'Código de la localidad/ciudad. (Véase               : Enums - Ciudades)',
			'key_tipo'             => 'Tipo de propiedad. (Véase                           : Enums - Tipo Propiedades)',
			'key_zona'             => 'Código de la zona. (Véase                           : Enums - Zonas)',
			'latitud'              => 'Coordenada (Latitud)',
			'lavanderia'           => 'Lavandería',
			'linea_tlf'            => 'Línea telefónica',
			'longitud'             => 'Coordenada (Longitud)',
			'luminoso'             => 'Luminoso',
			'luz'                  => 'Luz',
			'metro'                => 'Metro',
			'mirador'              => 'Mirador',
			'montacargas'          => 'Montacargas',
			'montana'              => 'Montaña',
			'muebles'              => 'Muebles',
			'm_altillo'            => 'Metros del altillo',
			'm_cocina'             => 'Metros de la cocina',
			'm_comedor'            => 'Metros del comedor',
			'm_cons'               => 'Metros construidos',
			'm_fachada'            => 'Metros de la fachada',
			'm_parcela'            => 'Metros de la parcela',
			'm_sotano'             => 'Metros del sótano',
			'm_terraza'            => 'Metros de la terraza',
			'm_utiles'             => 'Metros útiles',
			'nodisponible'         => 'Si la propiedad no está disponible',
			'numero'               => 'Dirección (Número del portal)',
			'numllave'             => 'Número de llavero',
			'numplanta'            => 'Dirección (Número total de plantas)',
			'numsucursal'          => 'Id de la agencia sucursal',
			'ojobuey'              => 'Ojos de buey',
			'opcioncompra'         => 'La propiedad tiene opción a compra',
			'outlet'               => 'Precio anterior del inmueble (por si se ha rebajado)',
			'parking'              => 'Parking',
			'parques'              => 'Parques',
			'patio'                => 'Patio',
			'pergola'              => 'Pérgola',
			'piscina_com'          => 'Piscina comunitaria',
			'piscina_prop'         => 'Piscina propia',
			'planta'               => 'Dirección (Nº de planta)',
			'plaza_gara'           => 'Plaza de garaje',
			'porceniva'            => 'Porcentaje del IVA',
			'precioalq'            => 'Precio de Alquiler',
			'precioinmo'           => 'Precio de la propiedad para la inmobiliaria',
			'precioiva'            => 'IVA del precio',
			'preciotraspaso'       => 'Precio del traspaso de la propiedad',
			'preinstaacc'          => 'Preinstalación del aire acondicionado',
			'preinsthmusi'         => 'Preinstalación de hilo musical',
			'primera_linea'        => 'Si está en primera línea',
			'prospecto'            => 'Indica si la propiedad es un prospecto',
			'puerta'               => 'Dirección (Puerta)',
			'puertasauto'          => 'Puertas automáticas',
			'puerta_blin'          => 'Puerta blindada',
			'rcatastral'           => 'Dato catastral (Referencia catastral)',
			'rdirfinca'            => 'Dato catastral (Dirección de la finca)',
			'ref'                  => 'Referencia de la propiedad (Debe ser única para cada propiedad)',
			'registrod'            => 'Dato catastral (Registro)',
			'rfolio'               => 'Dato catastral (Folio)',
			'riegoauto'            => 'Riego automático',
			'rletra'               => 'Dato catastral (Letra)',
			'rlibro'               => 'Dato catastral (Libro)',
			'rnumero'              => 'Dato catastral (Número)',
			'rnumeroinscr'         => 'Dato catastral (Número inscripción)',
			'rtomo'                => 'Dato catastral (Tomo)',
			'rural'                => 'Rural',
			'salon'                => 'Salón',
			'satelite'             => 'Satélite',
			'sauna'                => 'Sauna',
			'solarium'             => 'Solarium',
			'sotano'               => 'Sótano',
			'supermercados'        => 'Supermercados',
			'tenis'                => 'Pista de tenis propia',
			'teniscom'             => 'Pista de tenis comunitaria',
			'terraza'              => 'Terraza',
			'terrazaacris'         => 'Terraza acristalada',
			'tfachada'             => 'Descripción del fachada',
			'tgascom'              => 'Periodicidad de la comunidad',
			'tinterior'            => 'Descripción del interior',
			'tipomensual'          => 'Periodicidad del alquiler',
			'tipovpo'              => 'Tipo de régimen',
			'tituloaleman'         => 'Título en Alemán',
			'titulocatalan'        => 'Título en Catalán',
			'tituloes'             => 'Título en Castellano/Español',
			'titulofrances'        => 'Título en Francés',
			'tituloingles'         => 'Título en Inglés',
			'tituloruso'           => 'Título en Ruso',
			'todoext'              => 'Todo exterior',
			'tranvia'              => 'Tranvía',
			'trastero'             => 'Trastero',
			'tren'                 => 'Tren',
			'trifasica'            => 'Sistema eléctrico trifásico',
			'tv'                   => 'Televisión',
			'urbanizacion'         => 'Urbanización',
			'urlprospecto'         => 'URL del prospecto captado',
			'vallado'              => 'Vallado',
			'vestuarios'           => 'Vestuarios',
			'video_port'           => 'Videoportero',
			'vigilancia_24'        => 'Vigilancia 24H',
			'vistasalmar'          => 'Tiene vistas al mar',
			'x_entorno'            => 'Tipo de entornos',
			'zona'                 => 'Si no se envía key_zona, se puede enviar el nombre de la zona aquí.',
			'zonasinfantiles'      => 'Zonas infantiles',
			'zona_de_paso'         => 'Zona de Paso',
			'fotos'                => 'Debe ser un objeto que contenga las url de las fotografías.',
		);
		return isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
	}

	/**
	 * Get property types from Inmovilla
	 *
	 * @return array
	 */
	public static function get_inmovilla_tipos() {
		$result = self::request_inmovilla( 'tipos', 1, 100 );

		if ( 'ok' === $result['status'] && isset( $result['data']['tipos'] ) ) {
			// Remove metadata row.
			$tipos = $result['data']['tipos'];
			unset( $tipos[0] );
			return array(
				'status' => 'ok',
				'data'   => array_values( $tipos ),
			);
		}

		return $result;
	}

	/**
	 * Get provinces from Inmovilla
	 *
	 * @param bool $only_with_properties Only provinces with properties.
	 * @return array
	 */
	public static function get_inmovilla_provincias( $only_with_properties = true ) {
		$tipo   = $only_with_properties ? 'provinciasofertas' : 'provincias';
		$result = self::request_inmovilla( $tipo, 1, 100 );

		if ( 'ok' === $result['status'] && isset( $result['data'][ $tipo ] ) ) {
			// Remove metadata row.
			$provincias = $result['data'][ $tipo ];
			unset( $provincias[0] );
			return array(
				'status' => 'ok',
				'data'   => array_values( $provincias ),
			);
		}

		return $result;
	}

	/**
	 * Get cities from Inmovilla
	 *
	 * @return array
	 */
	public static function get_inmovilla_ciudades() {
		$result = self::request_inmovilla( 'ciudades', 1, 100 );

		if ( 'ok' === $result['status'] && isset( $result['data']['ciudades'] ) ) {
			// Remove metadata row.
			$ciudades = $result['data']['ciudades'];
			unset( $ciudades[0] );
			return array(
				'status' => 'ok',
				'data'   => array_values( $ciudades ),
			);
		}

		return $result;
	}

	/**
	 * Get zones from Inmovilla
	 *
	 * @param int $key_loca City key to filter zones.
	 * @return array
	 */
	public static function get_inmovilla_zonas( $key_loca = 0 ) {
		$where  = $key_loca > 0 ? "key_loca={$key_loca}" : '';
		$result = self::request_inmovilla( 'zonas', 1, 100, $where );

		if ( 'ok' === $result['status'] && isset( $result['data']['zonas'] ) ) {
			// Remove metadata row.
			$zonas = $result['data']['zonas'];
			unset( $zonas[0] );
			return array(
				'status' => 'ok',
				'data'   => array_values( $zonas ),
			);
		}

		return $result;
	}

	/**
	 * Get featured properties from Inmovilla
	 *
	 * @param int    $num_elementos Number of featured properties to retrieve (max 30).
	 * @param string $orden Order clause.
	 * @return array
	 */
	public static function get_inmovilla_destacados( $num_elementos = 20, $orden = 'precioinmo, precioalq' ) {
		$num_elementos = min( $num_elementos, 30 ); // Max 30 for destacados.
		$result        = self::request_inmovilla( 'destacados', 1, $num_elementos, '', $orden );

		if ( 'ok' === $result['status'] && isset( $result['data']['destacados'] ) ) {
			$destacados = array();
			$total      = count( $result['data']['destacados'] );

			// Skip first element (metadata) and get properties.
			for ( $i = 1; $i < $total; $i++ ) {
				if ( isset( $result['data']['destacados'][ $i ] ) ) {
					$destacados[] = $result['data']['destacados'][ $i ];
				}
			}

			return array(
				'status' => 'ok',
				'data'   => $destacados,
			);
		}

		return $result;
	}
}
