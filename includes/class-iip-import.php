<?php
/**
 * Library for importing products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

use Close\ConnectCRM\RealState\API;

/**
 * Library for WooCommerce Settings
 *
 * Settings in order to importing products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class Import {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_manual_import' ) );
		add_action( 'wp_ajax_manual_import', array( $this, 'manual_import' ) );
		add_action( 'wp_ajax_nopriv_manual_import', array( $this, 'manual_import' ) );
	}

	/**
	 * Manual import Requests
	 *
	 * @return void
	 */
	public function scripts_manual_import() {
		wp_enqueue_script(
			'connect-realstate-manual',
			CCRMRE_PLUGIN_URL . 'includes/assets/connect-realstate-manual.js',
			array(),
			CCRMRE_VERSION,
			true
		);

		wp_localize_script(
			'connect-realstate-manual',
			'ajaxAction',
			array(
				'url'                 => admin_url( 'admin-ajax.php' ),
				'label_sync'          => __( 'Sync', 'import-holded-products-woocommerce' ),
				'label_syncing'       => __( 'Syncing', 'import-holded-products-woocommerce' ),
				'label_sync_complete' => __( 'Finished', 'import-holded-products-woocommerce' ),
				'nonce'               => wp_create_nonce( 'manual_import_nonce' ),
			)
		);
	}

	/**
	 * Ajax function to load info
	 *
	 * @return void
	 */
	public function manual_import() {
		$loop         = isset( $_POST['loop'] ) ? (int) $_POST['loop'] : 0;
		$pagination   = isset( $_POST['pagination'] ) ? (int) $_POST['pagination'] : 200;
		$totalprop    = isset( $_POST['totalprop'] ) ? (int) $_POST['totalprop'] : 0;
		$progress_msg = '';

		if ( check_ajax_referer( 'manual_import_nonce', 'nonce' ) ) {
			$loop_page = $loop % $pagination;
			$page      = round( $loop / $pagination, 0 ) + 1;

			if ( 0 === $loop ) {
				update_option( 'connect_crm_realstate_sync', array() );
			}

			$property = get_transient( 'connreal_query_property_loop_' . $loop_page );
			if ( ! $property || 0 === $loop_page ) {
				$result_api   = API::get_properties( $page );
				$properties   = 'ok' === $result_api['status'] ? $result_api['data'] : array();
				$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-realstate' ) . '<br/>';
				$total_count  = ! empty( $total_count ) ? $total_count : 0;
				$total_count += count( $properties );
				$i            = 0;
				foreach ( $properties as $property_api ) {
					set_transient( 'connreal_query_property_loop_' . $i, $property_api, MINUTE_IN_SECONDS * 3 );
					$i++;
				}
				$property  = $result_api['data'][ $loop_page ];
				$totalprop = count( $properties );
			}

			if ( ! empty( $property ) ) {
				$result_sync   = SYNC::sync_property( $property );
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . $loop + 1;
				$progress_msg .= ' - ' . $result_sync['message'];

				if ( ! empty( $result_sync['property_id'] ) ) {
					$sync   = get_option( 'connect_crm_realstate_sync' );
					$sync   = ! empty( $sync ) ? $sync : array();
					$sync[] = $result_sync['property_id'];
					update_option( 'connect_crm_realstate_sync', $sync );
				}
				$finish = $totalprop < $pagination && $totalprop === $loop ? true : false;
				$finish = 0 === $totalprop ? true : $finish;
			} else {
				$finish = true;
			}

			if ( $finish ) {
				$count         = SYNC::trash_not_synced( $sync );
				$progress_msg .= esc_html__( 'Properties not synced and sent to trash: ', 'connect-crm-realstate' ) . $count;
			}

			delete_transient( 'connreal_query_property_loop_' . $loop_page );

			wp_send_json_success(
				array(
					'loop'       => $loop + 1,
					'message'    => $progress_msg,
					'pagination' => $pagination,
					'totalprop'  => $totalprop,
					'finish'     => $finish,
				)
			);
		} else {
			wp_send_json_error( array( 'error' => 'Error' ) );
		}
	}
}
