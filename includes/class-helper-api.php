<?php
/**
 * Library for API connection
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
	private static function request_anaconda( $method = 'GET', $endpoint, $query = array() ) {
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
				'data'   => isset( $body['error_message'] ) ? $body['error_message'] : '',
			);
		} else {
			return array(
				'status' => 'ok',
				'data'   => $data,
			);
		}
	}

	/**
	 * Request to API from Anaconda CRM
	 *
	 * @param string $method Method of API request.
	 * @param string $endpoint Endpoint of API request.
	 * @param array  $query Query of API request.
	 * @return array
	 */
	private static function request_inmovilla( $method = 'GET', $endpoint, $query = array() ) {
	}

	/**
	 * Request to properties API from CRM
	 *
	 * @return array
	 */
	public static function get_properties( $page ) {
		$settings     = get_option( 'conncrmreal_settings' );
		$settings_crm = isset( $settings['crm'] ) ? $settings['crm'] : 'anaconda';
		if ( 'anaconda' === $settings_crm ) {
			return self::request_anaconda( 'GET', 'properties/my_office_properties?page=' . $page );
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
}
