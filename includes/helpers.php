<?php
/**
 * Helpers functions
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Gets information from Inmovilla CRM
 *
 * @return array
 */
function inmovilla_get_properties( $query_array ) {
	$agency_number = get_option( 'iip_agency_number' );
	$agency_pass   = get_option( 'iip_agency_pass' );
	$language      = get_option( 'iip_language' );

	if ( '2' === $agency_number ) {
		$ia = '84.120.176.252';
		$ib = '42.5.120.1';
	} else {
		$ia = isset( $_SERVER['REMOTE_ADDR'] ) ? esc_url_raw( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ib = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
	}
	$query_text = $agency_number . ';' . $agency_pass . ';' . $language . ';lostipos';
	foreach ( $query_array as $query_item ) {
		$query_text = $query_text . ';' . $query_item;
	}
	$query_text   = rawurlencode( $query_text );
	$query_string = 'param=' . $query_text . '&json=1&ia=' . $ia . '&ib=' . $ib ;

	// API connection.
	$args = array(
		'headers' => array(
			'Accept'     => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
			'Connection' => 'keep-alive',
		),
		'sslverify' => false,
		'timeout'   => 50,
	);
	$response = wp_remote_get( 'https://apiweb.inmovilla.com/apiweb/apiweb.php?' . $query_string, $args );
	echo '<pre style="margin-left:200px;">$response:';
	print_r($response);
	echo '</pre>';
	if ( 200 === $response['response']['code'] ) {
		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	} else {
		return false;
	}
}