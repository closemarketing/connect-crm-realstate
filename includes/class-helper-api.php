<?php
/**
 * Library for API connection
 *
 * Documentation API.
 * Inmovilla APIWEB: https://procesos.apinmo.com/apiweb/doc/index.html
 * Inmovilla API REST: https://procesos.apinmo.com/api/v1/apidoc/
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
	 * Get all property IDs from API
	 *
	 * Returns property identifiers with their last updated dates and status
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos).
	 * @param bool   $with_metadata Whether to include metadata (dates, status) (default: true).
	 * @return array Array with status and list of property IDs/references (with metadata if requested)
	 */
	public static function get_all_property_ids( $crm_type, $with_metadata = true ) {
		$transient_key = 'ccrmre_query_property_ids_' . $crm_type;
		$property_ids  = get_transient( $transient_key );

		if ( false === $property_ids || ! is_array( $property_ids ) ) {
			$property_ids = array();
			$result       = self::get_properties();

			if ( 'error' === $result['status'] ) {
				return $result;
			}

			$properties = isset( $result['data'] ) ? $result['data'] : array();

			// Extract IDs, dates and status for each property.
			foreach ( $properties as $property ) {
				$property_info = self::get_property_info( $property, $crm_type );
				$list_key      = $property_info['id'];

				if ( empty( $property_info['id'] ) ) {
					continue;
				}
				if ( $with_metadata ) {
					// Store as associative array with metadata.
					$property_ids[ $list_key ] = array(
						'last_updated' => $property_info['last_updated'] ?? null,
						'status'       => $property_info['status'] ?? null,
						'state_code'   => $property_info['state_code'] ?? null,
						'zip'          => $property_info['zip'] ?? null,
					);
				} else {
					// Store as simple array of IDs.
					$property_ids[] = $list_key;
				}
			}

			set_transient( $transient_key, $property_ids, MINUTE_IN_SECONDS * 30 );
		}

		return array(
			'status' => 'ok',
			'data'   => $property_ids,
			'count'  => count( $property_ids ),
		);
	}

	/**
	 * Request to API from Anaconda CRM
	 *
	 * @param string $endpoint Endpoint of API request.
	 * @param string $method Method of API request.
	 * @param array  $query Query of API request.
	 * @return array
	 */
	public static function request_anaconda( $endpoint, $method = 'GET', $query = array() ) {
		$settings    = get_option( 'ccrmre_settings' );
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		if ( empty( $apipassword ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'API password is empty', 'connect-crm-realstate' ),
				'data'    => array(),
			);
		}
		// API connection.
		$api_config = self::get_api_config( 'anaconda' );
		$args       = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $apipassword,
			),
			'timeout' => $api_config['timeout'],
		);
		if ( ! empty( $query ) ) {
			$args['body'] = $query;
		}

		return self::execute_with_retry(
			function () use ( $args, $endpoint ) {
				$response    = wp_remote_request( 'https://api.anaconda.guru/api/v1/' . $endpoint, $args );
				$result_body = wp_remote_retrieve_body( $response );
				$code        = wp_remote_retrieve_response_code( $response );
				$code_first  = (int) substr( $code, 0, 1 );
				$data        = json_decode( $result_body, true );

				if ( is_wp_error( $response ) || empty( $response['body'] ) || 2 !== $code_first ) {
					$error_type = self::detect_error_type( $response, $code );
					return array(
						'status'     => 'error',
						'message'    => isset( $data['message'] ) ? $data['message'] : __( 'Unknown API error', 'connect-crm-realstate' ),
						'data'       => array(),
						'error_type' => $error_type,
					);
				}

				return array(
					'status'  => 'ok',
					'message' => __( 'Request successful', 'connect-crm-realstate' ),
					'data'    => $data,
				);
			},
			'Anaconda API'
		);
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
	public static function request_inmovilla( $tipo = 'paginacion', $pos_inicial = 1, $num_elementos = 200, $where = '', $orden = '', $idioma = 1 ) {
		$settings    = get_option( 'ccrmre_settings' );
		$numagencia  = isset( $settings['numagencia'] ) ? $settings['numagencia'] : '';
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		// Validate required settings.
		if ( empty( $numagencia ) || empty( $apipassword ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'Inmovilla API credentials are not configured', 'connect-crm-realstate' ),
				'data'    => array(),
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

		// Add domain to the request, matching the official Inmovilla client order.
		$parsed_url = wp_parse_url( get_site_url() );
		$hostname   = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$body      .= '&elDominio=' . $hostname;

		// Prepare request arguments.
		$api_config = self::get_api_config( 'inmovilla' );
		$args       = array(
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
			'cookies'    => self::load_inmovilla_cookies(),
			'user-agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
			'sslverify'  => false,
			'timeout'    => $api_config['timeout'],
		);

		$url = 'https://apiweb.inmovilla.com/apiweb/apiweb.php';

		return self::execute_with_retry(
			function () use ( $url, $args ) {
				$response = wp_remote_post( $url, $args );

				self::save_inmovilla_cookies( $response );

				if ( is_wp_error( $response ) ) {
					return array(
						'status'     => 'error',
						'message'    => $response->get_error_message(),
						'data'       => array(),
						'error_type' => self::detect_error_type( $response, 0 ),
					);
				}

				$body = wp_remote_retrieve_body( $response );
				$code = wp_remote_retrieve_response_code( $response );

				if ( 200 !== $code || 'Se ha superado el numero de peticiones por minuto' === $body ) {
					return array(
						'status'     => 'error',
						'message'    => sprintf(
							/* translators: %d: HTTP response */
							__( 'Inmovilla API returned error: %d', 'connect-crm-realstate' ),
							$body
						),
						'data'       => array(),
						'error_type' => self::detect_error_type( $response, $code ),
					);
				}

				$data = json_decode( $body, true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					// Detect Inmovilla IP registration error (plain-text response, not JSON).
					if ( is_string( $body ) && false !== stripos( $body, 'NECESITAMOS RECIBIR LA IP' ) ) {
						$server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';
						if ( empty( $server_ip ) ) {
							$server_ip = gethostbyname( gethostname() );
						}
						return array(
							'status'     => 'error',
							'message'    => sprintf(
								/* translators: %s: Server IP address */
								__( 'Inmovilla API requires IP registration. Please provide your server IP (%s) to Inmovilla support so they can whitelist it.', 'connect-crm-realstate' ),
								$server_ip
							),
							'data'       => array(),
							'error_type' => 'ip_not_registered',
						);
					}
					$message  = __( 'Invalid JSON response from Inmovilla API', 'connect-crm-realstate' );
					$message .= is_string( $body ) ? ' - ' . $body : '';
					return array(
						'status'     => 'error',
						'message'    => $message,
						'data'       => array(),
						'error_type' => 'default',
					);
				}

				return array(
					'status' => 'ok',
					'data'   => $data,
				);
			},
			'Inmovilla API Web'
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
	 * Get path to Inmovilla API cookie jar file.
	 *
	 * @return string Absolute path to cookie file.
	 */
	private static function get_inmovilla_cookie_jar_path() {
		return get_temp_dir() . 'ccrmre-inmovilla-cookies.txt';
	}

	/**
	 * Load cookies from jar for Inmovilla API requests.
	 *
	 * @return array<string, string> Cookie name => value for wp_remote_post.
	 */
	private static function load_inmovilla_cookies() {
		$path = self::get_inmovilla_cookie_jar_path();
		if ( ! is_readable( $path ) ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local cookie jar file, not remote.
		$content = file_get_contents( $path );
		if ( false === $content ) {
			return array();
		}
		$cookies = array();
		$lines   = explode( "\n", $content );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, '=' ) ) {
				continue;
			}
			$pos = strpos( $line, '=' );
			if ( false === $pos ) {
				continue;
			}
			$name             = substr( $line, 0, $pos );
			$value            = substr( $line, $pos + 1 );
			$cookies[ $name ] = $value;
		}
		return $cookies;
	}

	/**
	 * Save cookies from Inmovilla API response into the jar.
	 *
	 * @param array|\WP_Error $response Response from wp_remote_post.
	 */
	private static function save_inmovilla_cookies( $response ) {
		if ( is_wp_error( $response ) ) {
			return;
		}
		$response_cookies = wp_remote_retrieve_cookies( $response );
		if ( empty( $response_cookies ) ) {
			return;
		}
		$cookies = self::load_inmovilla_cookies();
		foreach ( $response_cookies as $cookie ) {
			if ( $cookie instanceof \WP_Http_Cookie ) {
				$cookies[ $cookie->name ] = $cookie->value;
			}
		}
		$path  = self::get_inmovilla_cookie_jar_path();
		$lines = array();
		foreach ( $cookies as $name => $value ) {
			$safe_value = str_replace( array( "\r", "\n" ), '', $value );
			$lines[]    = $name . '=' . $safe_value;
		}
		// $lines is non-empty here: we returned early if $response_cookies was empty, so $cookies has entries.
		wp_mkdir_p( dirname( $path ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Cookie jar in temp dir for API session.
		file_put_contents( $path, implode( "\n", $lines ) . "\n", LOCK_EX );
	}

	/**
	 * Get pagination size based on CRM type
	 *
	 * @param string $crm CRM type (optional, if not provided will get from settings).
	 * @return int Pagination size.
	 */
	public static function get_pagination_size( $crm = '' ) {
		if ( empty( $crm ) ) {
			$settings = get_option( 'ccrmre_settings' );
			$crm      = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		}

		$pagination_sizes = array(
			'anaconda'           => 200,
			'inmovilla'          => 50,
			'inmovilla_procesos' => -1,
		);

		return isset( $pagination_sizes[ $crm ] ) ? $pagination_sizes[ $crm ] : 100;
	}

	/**
	 * Request to Inmovilla Procesos API (REST API v1)
	 *
	 * Documentation: https://procesos.inmovilla.com/api/v1
	 *
	 * @param string $endpoint Endpoint path (e.g., 'propiedades/?listado').
	 * @param string $method HTTP method (GET, POST, PUT, DELETE).
	 * @param array  $body Body data for POST/PUT requests.
	 * @return array
	 */
	public static function request_inmovilla_procesos( $endpoint, $method = 'GET', $body = array() ) {
		$settings    = get_option( 'ccrmre_settings' );
		$apipassword = isset( $settings['apipassword'] ) ? $settings['apipassword'] : '';

		// Validate required settings.
		if ( empty( $apipassword ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'Inmovilla Procesos API token is not configured', 'connect-crm-realstate' ),
				'data'    => array(),
			);
		}

		// Build request arguments.
		$api_config = self::get_api_config( 'inmovilla_procesos' );
		$args       = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Token'        => $apipassword,
			),
			'timeout' => $api_config['timeout'],
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		// Execute request with retry logic.
		return self::execute_with_retry(
			function () use ( $args, $endpoint ) {
				$api_url     = 'https://procesos.inmovilla.com/api/v1/' . $endpoint;
				$response    = wp_remote_request( $api_url, $args );
				$result_body = wp_remote_retrieve_body( $response );
				$code        = wp_remote_retrieve_response_code( $response );

				if ( is_wp_error( $response ) ) {
					$error_type = self::detect_error_type( $response, $code );
					return array(
						'status'     => 'error',
						'message'    => $response->get_error_message(),
						'data'       => array(),
						'error_type' => $error_type,
					);
				}

				// Check for HTTP errors.
				if ( $code < 200 || $code >= 300 ) {
					$error_type = self::detect_error_type( $response, $code );
					return array(
						'status'     => 'error',
						'message'    => sprintf(
							/* translators: %d: HTTP error code */
							__( 'Inmovilla Procesos API returned error code: %d', 'connect-crm-realstate' ),
							$code
						),
						'data'       => array(),
						'error_type' => $error_type,
					);
				}

				// Validate JSON response.
				$data = json_decode( $result_body, true );

				if ( null === $data ) {
					return array(
						'status'  => 'ok',
						'message' => __( 'This property is not available', 'connect-crm-realstate' ),
						'data'    => array(),
					);
				}

				return array(
					'status'  => 'ok',
					'message' => __( 'Properties fetched successfully', 'connect-crm-realstate' ),
					'data'    => $data,
				);
			},
			'Inmovilla Procesos API'
		);
	}

	/**
	 * Request to properties API from CRM
	 *
	 * @param int    $page Page of properties (for Anaconda) or page number for pagination (Inmovilla).
	 * @param string $changed_from Date of changed properties (format: Y-m-d H:i:s).
	 * @return array
	 */
	public static function get_properties( $page = 0, $changed_from = '' ) {
		$settings     = get_option( 'ccrmre_settings' );
		$settings_crm = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		if ( 'anaconda' === $settings_crm ) {
			if ( ! empty( $page ) ) {
				return self::request_anaconda( 'properties/?page=' . $page );
			} elseif ( ! empty( $changed_from ) ) {
				$query = array(
					'changed_from' => $changed_from,
				);
				return self::request_anaconda( 'properties/search', 'POST', $query );
			}
		} elseif ( 'inmovilla_procesos' === $settings_crm ) {
			// For Inmovilla Procesos, get all properties in one call (no server-side pagination).
			$result = self::request_inmovilla_procesos( 'propiedades/?listado' );

			if ( 'ok' === $result['status'] && is_array( $result['data'] ) ) {
				$all_properties = $result['data'];

				// Filter by date if changed_from is specified.
				if ( ! empty( $changed_from ) ) {
					$changed_timestamp = strtotime( $changed_from );
					$all_properties    = array_filter(
						$all_properties,
						function ( $prop ) use ( $changed_timestamp ) {
							$fechaact = isset( $prop['fechaact'] ) ? strtotime( $prop['fechaact'] ) : 0;
							return $fechaact >= $changed_timestamp;
						}
					);
					$all_properties    = array_values( $all_properties ); // Re-index array.
				}

				return array(
					'status' => 'ok',
					'data'   => $all_properties,
					'meta'   => array( 'total' => count( $all_properties ) ),
				);
			}

			return $result;
		} elseif ( 'inmovilla' === $settings_crm ) {
			return self::request_inmovilla_all_properties( $changed_from );
		}

		return array(
			'status'  => 'error',
			'message' => __( 'CRM type not configured', 'connect-crm-realstate' ),
			'data'    => array(),
		);
	}

	/**
	 * Fetch all properties from Inmovilla API iterating through all pages.
	 *
	 * Requests page 1 first, reads the total from the metadata element (index 0),
	 * then fetches remaining pages and merges all properties into a single array.
	 *
	 * @param string $changed_from ISO date string to filter by last-updated date (fechaact).
	 * @return array
	 */
	public static function request_inmovilla_all_properties( $changed_from = '' ) {
		$where      = '';
		$orden      = 'fecha desc';
		$pagination = self::get_pagination_size( 'inmovilla' );

		// Build date filter when provided.
		if ( ! empty( $changed_from ) ) {
			$changed_timestamp = strtotime( $changed_from );
			if ( $changed_timestamp ) {
				$where = "fechaact>='" . gmdate( 'Y-m-d', $changed_timestamp ) . "'";
			}
		}

		$all_properties = array();
		$pos_inicial    = 1;
		$total_records  = null;

		do {
			$result = self::request_inmovilla( 'paginacion', $pos_inicial, $pagination, $where, $orden );

			if ( 'ok' !== $result['status'] || ! isset( $result['data']['paginacion'] ) ) {
				// Return error on first page; stop silently on subsequent pages.
				if ( 1 === $pos_inicial ) {
					return $result;
				}
				break;
			}

			$raw = $result['data']['paginacion'];

			// Index 0 is metadata: posicion, elementos, total.
			if ( null === $total_records && isset( $raw[0]['total'] ) ) {
				$total_records = (int) $raw[0]['total'];
			}

			// Collect properties (index 1 onwards).
			$count = count( $raw );
			for ( $i = 1; $i < $count; $i++ ) {
				if ( isset( $raw[ $i ] ) ) {
					$all_properties[] = $raw[ $i ];
				}
			}

			$pos_inicial += $pagination;
		} while ( null !== $total_records && $pos_inicial <= $total_records );

		return array(
			'status' => 'ok',
			'data'   => $all_properties,
			'meta'   => array( 'total' => $total_records ?? count( $all_properties ) ),
		);
	}

	/**
	 * Request to get a single property from CRM
	 *
	 * @param array|string $item ID of property or incomplete property array.
	 * @param string       $crm CRM type (optional).
	 * @return array
	 */
	public static function get_property( $item, $crm = '' ) {
		if ( empty( $crm ) ) {
			$settings = get_option( 'ccrmre_settings' );
			$crm      = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		}
		$property_info = null;
		if ( is_array( $item ) ) {
			$property_info = self::get_property_info( $item, $crm );
			$property_id   = $property_info['id'];
		} else {
			$property_id = $item;
		}

		$result = array(
			'status'  => 'ok',
			'message' => __( 'Property fetched successfully', 'connect-crm-realstate' ),
			'data'    => array(),
		);

		if ( 'anaconda' === $crm ) {
			// Anaconda returns complete property data in listings.
			$result['data'] = $item;
		} elseif ( 'inmovilla_procesos' === $crm ) {
			// For Inmovilla Procesos, use GET /propiedades/?cod_ofer={cod_ofer}.

			if ( empty( $property_id ) ) {
				return array(
					'status'  => 'error',
					'message' => __( 'Property ID is required', 'connect-crm-realstate' ),
				);
			}

			$result = self::request_inmovilla_procesos( 'propiedades/?cod_ofer=' . $property_id );

			if ( 'ok' === $result['status'] && empty( $result['data'] ) ) {
				return array(
					'status'  => 'error',
					'message' => __( 'This property is not available in API', 'connect-crm-realstate' ),
				);
			}

			if ( 'ok' === $result['status'] ) {
				$result['data']['fotos'] = self::get_property_photos( $result['data'] );
			}
		} elseif ( 'inmovilla' === $crm ) {
			// For Inmovilla, use 'ficha' type to get complete property details.
			$where = "cod_ofer='{$property_id}'";

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

				$result['data'] = $property_data;
			}
		}

		return $result;
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
		} elseif ( 'inmovilla_procesos' === $crm ) {
			return self::get_fields_inmovilla_procesos();
		} elseif ( 'inmovilla' === $crm ) {
			return self::get_fields_inmovilla();
		}

		return array();
	}

	/**
	 * Format a raw API value for display as a sample in the admin.
	 *
	 * @param mixed $value Raw value from API.
	 * @return string
	 */
	private static function format_sample_value( $value ) {
		if ( is_null( $value ) || '' === $value ) {
			return '';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			$arr = (array) $value;
			if ( empty( $arr ) ) {
				return '';
			}
			$first = reset( $arr );
			if ( is_scalar( $first ) && '' !== (string) $first ) {
				return '[…] ' . wp_strip_all_tags( (string) $first );
			}
			return sprintf( '[%d items]', count( $arr ) );
		}

		$str = wp_strip_all_tags( (string) $value );
		return mb_strlen( $str ) > 60 ? mb_substr( $str, 0, 57 ) . '…' : $str;
	}

	/**
	 * Get fields from Anaconda
	 *
	 * @return array
	 */
	private static function get_fields_anaconda() {
		$anaconda_fields = get_transient( 'ccrmre_query_anaconda_fields_v2' );

		if ( ! $anaconda_fields ) {
			// Get a sample property to extract fields.
			$result = self::request_anaconda( 'properties/?page=1' );

			if ( 'ok' !== $result['status'] || empty( $result['data']['data'] ) ) {
				return array(
					'status'  => 'error',
					'message' => __( 'Error getting Anaconda fields. Please check your API connection.', 'connect-crm-realstate' ),
					'data'    => array(),
				);
			}

			// Get the first property to extract field structure and sample values.
			$sample_property = $result['data']['data'][0];
			$fields_slug     = array_filter( array_keys( $sample_property ) );
			$fields_data     = array();

			foreach ( $fields_slug as $slug ) {
				$fields_data[] = array(
					'name'   => $slug,
					'label'  => ucwords( str_replace( '_', ' ', $slug ) ),
					'sample' => isset( $sample_property[ $slug ] ) ? self::format_sample_value( $sample_property[ $slug ] ) : '',
				);
			}

			$anaconda_fields = array(
				'status' => 'ok',
				'data'   => $fields_data,
			);

			set_transient( 'ccrmre_query_anaconda_fields_v2', $anaconda_fields, DAY_IN_SECONDS );
		}

		return $anaconda_fields;
	}

	/**
	 * Get fields from Inmovilla Procesos
	 *
	 * @return array
	 */
	private static function get_fields_inmovilla_procesos() {
		$inmovilla_fields = get_transient( 'ccrmre_query_inmovilla_procesos_fields_v2' );

		if ( ! $inmovilla_fields ) {
			// Get a sample property to extract fields.
			$result = self::request_inmovilla_procesos( 'propiedades/?listado' );

			if ( 'ok' !== $result['status'] || empty( $result['data'] ) || ! is_array( $result['data'] ) ) {
				return array(
					'status'  => 'error',
					'message' => __( 'Error getting Inmovilla Procesos fields. Please check your API connection.', 'connect-crm-realstate' ),
					'data'    => array(),
				);
			}

			// Get the first property and fetch its complete details.
			$first_property = $result['data'][0];
			$property_info  = self::get_property_info( $first_property, 'inmovilla_procesos' );
			$property_id    = $property_info['id'];

			if ( ! empty( $property_id ) ) {
				$property_result = self::request_inmovilla_procesos( 'propiedades/?cod_ofer=' . $property_id );

				if ( 'ok' === $property_result['status'] && isset( $property_result['data'] ) ) {
					$sample_property = $property_result['data'];
					$fields_slug     = array_filter( array_keys( $sample_property ) );
					$fields_data     = array();

					// Local JSON file (not a remote URL).
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
					$fields_inmovilla_procesos = file_get_contents( __DIR__ . '/apidata/inmovillla-procesos.json' );
					$fields_inmovilla_procesos = json_decode( $fields_inmovilla_procesos, true );
					$labels                    = array_column( $fields_inmovilla_procesos, 'description', 'field' );

					foreach ( $fields_slug as $slug ) {
						$label = ! empty( $labels[ $slug ] ) ? $labels[ $slug ] : ucwords( str_replace( '_', ' ', $slug ) );

						$fields_data[] = array(
							'name'   => $slug,
							'label'  => $label,
							'sample' => isset( $sample_property[ $slug ] ) ? self::format_sample_value( $sample_property[ $slug ] ) : '',
						);
					}

					$inmovilla_fields = array(
						'status' => 'ok',
						'data'   => $fields_data,
					);

					set_transient( 'ccrmre_query_inmovilla_procesos_fields_v2', $inmovilla_fields, DAY_IN_SECONDS );
				} else {
					return array(
						'status'  => 'error',
						'message' => __( 'Error getting Inmovilla Procesos property details.', 'connect-crm-realstate' ),
						'data'    => array(),
					);
				}
			} else {
				return array(
					'status'  => 'error',
					'message' => __( 'No properties found in Inmovilla Procesos.', 'connect-crm-realstate' ),
					'data'    => array(),
				);
			}
		}

		return $inmovilla_fields;
	}

	/**
	 * Get fields from Inmovilla
	 *
	 * @return array
	 */
	private static function get_fields_inmovilla() {
		$inmovilla_fields = get_transient( 'ccrmre_query_inmovilla_fields_v2' );

		if ( ! $inmovilla_fields ) {
			// Get a sample property to extract fields.
			$result_properties = self::request_inmovilla( 'paginacion', 1, 1 );

			if ( 'ok' !== $result_properties['status'] || ! isset( $result_properties['data']['paginacion'][1] ) ) {
				$message  = __( 'Error getting Inmovilla fields. Please check your API connection.', 'connect-crm-realstate' );
				$message .= is_string( $result_properties['data'] ) ? ' - ' . $result_properties['data'] : '';

				return array(
					'status'  => 'error',
					'message' => $message,
					'data'    => array(),
				);
			}

			// Get the first property to extract field structure and sample values.
			$sample_property = $result_properties['data']['paginacion'][1];
			$fields_slug     = array_filter( array_keys( $sample_property ) );
			$fields_data     = array();

			foreach ( $fields_slug as $slug ) {
				$fields_data[] = array(
					'name'   => $slug,
					'label'  => self::get_description_field_inmovilla( $slug ),
					'sample' => isset( $sample_property[ $slug ] ) ? self::format_sample_value( $sample_property[ $slug ] ) : '',
				);
			}

			$inmovilla_fields = array(
				'status' => 'ok',
				'data'   => $fields_data,
			);

			set_transient( 'ccrmre_query_inmovilla_fields_v2', $inmovilla_fields, DAY_IN_SECONDS );
		}

		return $inmovilla_fields;
	}

	/**
	 * Get property ID/reference/status/date from property data
	 *
	 * @param array  $property Property data.
	 * @param string $crm_type CRM type.
	 * @param bool   $prefix Prefix for transient key.
	 *
	 * @return array Property info with id, reference, status, last_updated
	 */
	public static function get_property_info( $property, $crm_type, $prefix = false ) {
		if ( $prefix ) {
			$prefix = 'ccrmre_';
		} else {
			$prefix = '';
		}

		$match = array(
			'anaconda'           => array(
				'id'           => 'id',
				'reference'    => 'referencia',
				'status'       => 'status',
				'last_updated' => 'updated_at',
				'state_code'   => 'state_code',
			),
			'inmovilla'          => array(
				'id'           => 'cod_ofer',
				'reference'    => 'ref',
				'status'       => 'nodisponible',
				'last_updated' => 'fechaact',
				'state_code'   => 'keyprov',
			),
			'inmovilla_procesos' => array(
				'id'           => 'cod_ofer',
				'reference'    => 'ref',
				'status'       => 'nodisponible',
				'last_updated' => 'fechaact',
				'state_code'   => 'keyprov',
			),
		);

		$property_info = array(
			$prefix . 'id'           => null,
			$prefix . 'reference'    => null,
			$prefix . 'status'       => null,
			$prefix . 'last_updated' => null,
			$prefix . 'state_code'   => null,
		);
		if ( isset( $match[ $crm_type ] ) ) {
			$fields = $match[ $crm_type ];

			// Get ID if available.
			if ( isset( $fields['id'] ) && isset( $property[ $fields['id'] ] ) ) {
				$property_info[ $prefix . 'id' ] = $property[ $fields['id'] ];
			}

			// Get reference if available.
			if ( isset( $fields['reference'] ) && isset( $property[ $fields['reference'] ] ) ) {
				$property_info[ $prefix . 'reference' ] = $property[ $fields['reference'] ];
			}

			// Get status if available.
			if ( isset( $fields['status'] ) && isset( $property[ $fields['status'] ] ) ) {
				$status_value = $property[ $fields['status'] ];

				// For inmovilla and inmovilla_procesos, nodisponible has inverse logic.
				// nodisponible = 1 means NOT available, so status = false.
				// nodisponible = 0 means available, so status = true.
				if ( in_array( $crm_type, array( 'inmovilla', 'inmovilla_procesos' ), true ) && 'nodisponible' === $fields['status'] ) {
					$status_value = ! (bool) $status_value; // Invert the logic.
				}

				$property_info[ $prefix . 'status' ] = $status_value;
			}

			// Get last_updated if available.
			if ( isset( $fields['last_updated'] ) && isset( $property[ $fields['last_updated'] ] ) ) {
				$property_info[ $prefix . 'last_updated' ] = $property[ $fields['last_updated'] ];
			}

			// Get state_code if available.
			if ( isset( $fields['state_code'] ) && isset( $property[ $fields['state_code'] ] ) ) {
				$property_info[ $prefix . 'state_code' ] = $property[ $fields['state_code'] ];
			}
		}
		return $property_info;
	}

	/**
	 * Get enums from Inmovilla Procesos
	 *
	 * @param string $crm_type CRM type.
	 * @param string $key      Optional enum key to filter.
	 *
	 * @return array
	 */
	public static function get_enums( $crm_type, $key = '' ) {
		if ( 'inmovilla_procesos' === $crm_type ) {
			return self::get_enums_inmovilla_procesos( $key );
		}
		return array();
	}

	/**
	 * Get property photos from Inmovilla Procesos
	 *
	 * @param array $property Property.
	 *
	 * @return array
	 */
	public static function get_property_photos( $property ) {
		$numagencia = isset( $property['numagencia'] ) ? $property['numagencia'] : '';
		$cod_ofer   = isset( $property['cod_ofer'] ) ? $property['cod_ofer'] : '';
		$fotoletra  = isset( $property['fotoletra'] ) ? $property['fotoletra'] : '';
		$numfotos   = isset( $property['numfotos'] ) ? (int) $property['numfotos'] : 0;

		// Validate required fields.
		if ( empty( $numagencia ) || empty( $cod_ofer ) || empty( $fotoletra ) || $numfotos <= 0 ) {
			return array();
		}

		$photo_urls = array();

		for ( $i = 1; $i <= $numfotos; $i++ ) {
			$photo_urls[] = "https://fotos15.inmovilla.com/{$numagencia}/{$cod_ofer}/{$fotoletra}-{$i}.jpg";
		}

		return $photo_urls;
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
	 * Get enums from Inmovilla Procesos
	 *
	 * @param string $key Enum key.
	 * @return array
	 */
	public static function get_enums_inmovilla_procesos( $key = '' ) {
		if ( empty( $key ) ) {
			return '';
		}

		$inmovilla_enums = array(
			'cocina_inde',     // Cocina independiente.
			'conservacion',    // Conservación / Estado de la propiedad.
			'destacado',       // Propiedad destacada para la web.
			'electro',         // Cocina equipada con electrodomésticos.
			'eninternet',      // Enviar a la web y/o portales inmobiliarios.
			'estadoficha',     // Estado de la propiedad.
			'idioma',          // Listado de idiomas.
			'keyacci',         // Tipo de operación.
			'keyagua',         // Tipo de agua.
			'keygua',          // Tipo de agua.
			'keycalefa',       // Tipo de calefacción.
			'keycalle',        // Tipo de vía.
			'keycarpin',       // Tipo de carpintería.
			'keycarpinext',    // Tipo de carpintería exterior.
			'keyelectricidad', // Tipo de instalación eléctrica.
			'keyfachada',      // Tipo de fachada.
			'keyori',          // Orientación de la propiedad.
			'keysuelo',        // Tipo de suelo.
			'keytecho',        // Tipo de techo.
			'keyvista',        // Tipo de vista.
			'key_loca',        // Código de la localidad/ciudad. (Véase: Enums - Ciudades).
			'key_tipo',        // Tipo de propiedad. (Véase: Enums - Tipo Propiedades).
			'key_zona',        // Código de la zona. (Véase: Enums - Zonas).
			'tgascom',         // Periodicidad de la comunidad.
			'tipovpo',         // Tipo de régimen.
			'todoext',         // Todo exterior.
			'vercalle',        // Visibilidad de la ubicación de la propiedad.
			'x_entorno',       // Tipo de entornos.
		);

		if ( ! in_array( $key, $inmovilla_enums, true ) ) {
			return '';
		}

		$enums = get_transient( 'ccrmre_query_enums_inmovilla_procesos' );
		$enums = empty( $enums ) ? array() : $enums;

		if ( empty( $enums[ $key ] ) ) {
			if ( 'key_loca' === $key ) {
				$ciudades      = self::get_inmovilla_procesos_ciudades();
				$enums[ $key ] = $ciudades;
			} else {
				$result = self::request_inmovilla_procesos( 'enums/?tipos' );
				if ( 'ok' === $result['status'] && isset( $result['data'] ) ) {
					$enums = self::flat_values( $enums, $result['data'] );
				}
			}
			set_transient( 'ccrmre_query_enums_inmovilla_procesos', $enums, DAY_IN_SECONDS * 3 );
		}
		return $enums;
	}

	/**
	 * Get ciudades from Inmovilla
	 *
	 * @return array
	 */
	private static function get_inmovilla_procesos_ciudades() {
		$ciudades = get_transient( 'ccrmre_query_inmovilla_procesos_ciudades' );

		// If cached data has the old flat format (string values instead of arrays),
		// discard it and re-fetch so callers always get ['city', 'state'] arrays.
		if ( ! empty( $ciudades ) ) {
			$first = reset( $ciudades );
			if ( ! is_array( $first ) ) {
				$ciudades = false;
				delete_transient( 'ccrmre_query_inmovilla_procesos_ciudades' );
				delete_transient( 'ccrmre_query_enums_inmovilla_procesos' );
			}
		}

		if ( empty( $ciudades ) ) {
			$ciudades = array();
			$result   = self::request_inmovilla_procesos( 'enums/?ciudades=724' );
			if ( 'ok' === $result['status'] && isset( $result['data'] ) ) {
				foreach ( $result['data'] as $ciudad ) {
					if ( ! isset( $ciudad['ciudades'] ) || ! is_array( $ciudad['ciudades'] ) ) {
						continue;
					}
					foreach ( $ciudad['ciudades'] as $ciudad_item ) {
						$key_loca = isset( $ciudad_item['key_loca'] ) ? $ciudad_item['key_loca'] : null;
						if ( null === $key_loca ) {
							continue;
						}
						$ciudades[ $key_loca ] = array(
							'city'  => isset( $ciudad_item['ciudad'] ) ? $ciudad_item['ciudad'] : '',
							'state' => isset( $ciudad['provincia'] ) ? $ciudad['provincia'] : '',
						);
					}
				}
				set_transient( 'ccrmre_query_inmovilla_procesos_ciudades', $ciudades, MONTH_IN_SECONDS );
			}
		}
		return $ciudades;
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
		$result = get_transient( 'ccrmre_query_inmovilla_provincias' );
		if ( false === $result || empty( $result['data'] ) || 'ok' !== $result['status'] ) {
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
			set_transient( 'ccrmre_query_inmovilla_provincias', $result, DAY_IN_SECONDS );
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

	/**
	 * Flat values from Inmovilla
	 *
	 * @param array $actual Actual values.
	 * @param array $values Values.
	 * @return array
	 */
	public static function flat_values( $actual, $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}
		$actual = is_array( $actual ) ? $actual : array();
		foreach ( $values as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}
			foreach ( $value as $value_value ) {
				if ( isset( $value_value['valor'] ) && isset( $value_value['nombre'] ) ) {
					$actual[ $key ][ $value_value['valor'] ] = $value_value['nombre'];
				}
			}
		}
		return $actual;
	}

	/**
	 * Maximum number of retry attempts
	 *
	 * @var int
	 */
	const MAX_RETRIES = 3;

	/**
	 * Retry configuration by error type
	 *
	 * @var array
	 */
	const RETRY_CONFIG = array(
		'timeout'      => array(
			'wait'    => 30,  // 30 seconds.
			'message' => 'Connection timeout, retrying in %d seconds...',
		),
		'rate_limit'   => array(
			'wait'    => 300, // 5 minutes.
			'message' => 'Rate limit reached, waiting %d seconds before retry...',
		),
		'server_error' => array(
			'wait'    => 120, // 2 minutes.
			'message' => 'Server error, retrying in %d seconds...',
		),
		'default'      => array(
			'wait'    => 60,  // 1 minute.
			'message' => 'API error, retrying in %d seconds...',
		),
	);

	/**
	 * Flag to skip server-side retry sleep during manual imports.
	 *
	 * @var bool
	 */
	private static $skip_retry = false;

	/**
	 * Set skip retry flag.
	 *
	 * When true, execute_with_retry returns on first error without sleeping,
	 * so the AJAX client can handle the wait with user feedback.
	 *
	 * @param bool $skip Whether to skip server-side retries.
	 * @return void
	 */
	public static function set_skip_retry( $skip ) {
		self::$skip_retry = (bool) $skip;
	}

	/**
	 * Get API configuration information
	 *
	 * Returns technical specifications and limitations for each API type.
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos). If empty, returns all.
	 * @return array API configuration array
	 */
	public static function get_api_config( $crm_type = '' ) {
		$config = array(
			'anaconda'           => array(
				'name'             => 'Anaconda',
				'timeout'          => 300,  // 5 minutes in seconds.
				'pagination'       => 200,
				'retry_timeout'    => self::RETRY_CONFIG['timeout']['wait'],
				'retry_rate_limit' => self::RETRY_CONFIG['rate_limit']['wait'],
				'retry_server'     => self::RETRY_CONFIG['server_error']['wait'],
				'max_retries'      => self::MAX_RETRIES,
			),
			'inmovilla'          => array(
				'name'             => 'Inmovilla',
				'timeout'          => 60,   // 1 minute in seconds.
				'pagination'       => 50,
				'retry_timeout'    => self::RETRY_CONFIG['timeout']['wait'],
				'retry_rate_limit' => self::RETRY_CONFIG['rate_limit']['wait'],
				'retry_server'     => self::RETRY_CONFIG['server_error']['wait'],
				'max_retries'      => self::MAX_RETRIES,
			),
			'inmovilla_procesos' => array(
				'name'             => 'Inmovilla Procesos',
				'timeout'          => 300,  // 5 minutes in seconds.
				'pagination'       => -1,   // All at once.
				'retry_timeout'    => self::RETRY_CONFIG['timeout']['wait'],
				'retry_rate_limit' => self::RETRY_CONFIG['rate_limit']['wait'],
				'retry_server'     => self::RETRY_CONFIG['server_error']['wait'],
				'max_retries'      => self::MAX_RETRIES,
			),
		);

		if ( ! empty( $crm_type ) && isset( $config[ $crm_type ] ) ) {
			return $config[ $crm_type ];
		}

		return $config;
	}

	/**
	 * Detect error type from response
	 *
	 * @param mixed $response WP_Error or response array.
	 * @param int   $code HTTP response code.
	 * @return string Error type (timeout, rate_limit, server_error, default).
	 */
	private static function detect_error_type( $response, $code = 0 ) {
		if ( is_wp_error( $response ) ) {
			$error_code = $response->get_error_code();
			if ( in_array( $error_code, array( 'http_request_timeout', 'http_request_failed' ), true ) ) {
				return 'timeout';
			}
		}

		if ( 429 === $code || 408 === $code ) {
			return 'rate_limit';
		}

		if ( $code >= 500 ) {
			return 'server_error';
		}

		return 'default';
	}

	/**
	 * Execute request with retry logic
	 *
	 * @param callable $request_callback Function that makes the actual API request.
	 * @param string   $api_name Name of the API (for logging).
	 * @return array Response with status, message, and data.
	 */
	private static function execute_with_retry( $request_callback, $api_name = 'API' ) {
		$attempt = 0;

		while ( $attempt <= self::MAX_RETRIES ) {
			++$attempt;

			// Execute the request.
			$result = call_user_func( $request_callback );

			// If successful, return immediately.
			if ( 'ok' === $result['status'] ) {
				return $result;
			}

			// If last attempt, return the error.
			if ( $attempt > self::MAX_RETRIES ) {
				$result['message'] = sprintf(
					/* translators: %s: API name */
					__( '%s: Maximum retry attempts reached. Last error: ', 'connect-crm-realstate' ),
					$api_name
				) . $result['message'];
				return $result;
			}

			// Detect error type and get wait time.
			$error_type = isset( $result['error_type'] ) ? $result['error_type'] : 'default';

			// If IP is not registered, retrying will never help — return immediately.
			if ( 'ip_not_registered' === $error_type ) {
				return $result;
			}

			// If skip_retry is enabled (manual import), return immediately.
			if ( self::$skip_retry ) {
				$result['error_type'] = $error_type;
				return $result;
			}

			$retry_config = isset( self::RETRY_CONFIG[ $error_type ] ) ? self::RETRY_CONFIG[ $error_type ] : self::RETRY_CONFIG['default'];
			$wait_seconds = $retry_config['wait'];

			// Log retry attempt.
			$retry_message = sprintf(
				/* translators: 1: Attempt number, 2: Max retries, 3: Wait seconds */
				__( 'Attempt %1$d/%2$d failed. Waiting %3$d seconds before retry...', 'connect-crm-realstate' ),
				$attempt,
				self::MAX_RETRIES,
				$wait_seconds
			);

			// Wait before retry.
			sleep( $wait_seconds );
		}

		return $result;
	}
}
