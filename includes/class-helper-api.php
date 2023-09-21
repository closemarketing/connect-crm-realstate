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
	public static function request( $method, $endpoint, $query, $crm = 'anaconda' ) {
		if ( 'anaconda' === $crm ) {
			return self::request_anaconda( $method, $endpoint, $query );
		} else {
			return self::request_inmovilla( $method, $endpoint, $query );
		}
	}

	private static function request_anaconda( $method = 'GET', $endpoint, $query ) {
		// API connection.
		$args = array(
			'method' => $method,
			'headers' => array(
				'Authorization' => 'Bearer 3SEG3FBSPT4TFL9DSEF3',
			),
			'timeout' => 300,
		);
		if ( ! empty( $query ) ) {
			$args['body'] = $query;
		}
		$response    = wp_remote_request( 'https://api.anaconda.guru/api/v1/' . $endpoint, $args );
		$result_body = wp_remote_retrieve_body( $response );
		$body        = json_decode( $result_body, true );

		if ( ! is_wp_error( $response ) && ( 200 === $response['response']['code'] || 201 === $response['response']['code'] ) ) {
			return array(
				'status' => 'ok',
				'data'   => isset( $body ) ? $body : array(),
			);
		} else {
			return array(
				'status' => 'error',
				'data'   => isset( $body['error_message'] ) ? $body['error_message'] : '',
			);
		}
	}
	private static function request_inmovilla( $method = 'GET', $endpoint, $query ) {
	}

	public static function get_properties() {
		$crm = 'anaconda';
		return self::request( 'GET', 'properties', array(), $crm );
	}
}