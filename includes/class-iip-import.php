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
		$progress_msg = '';

		if ( check_ajax_referer( 'manual_import_nonce', 'nonce' ) ) {
			$properties = get_transient( 'connect_query_properties' );
			if ( ! $properties ) {
				$result_api = API::get_properties();
				$properties = 'ok' === $result_api['status'] ? $result_api['data'] : array();
				set_transient( 'connect_query_properties', $properties, MINUTE_IN_SECONDS * 3 );
			}
			if ( 0 === $loop ) {
				$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-woocommerce' ) . '<br/>';
			}
			$item          = $properties[ $loop ];
			$total_count   = count( $properties );
			$result_sync   = SYNC::sync_property( $item );
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . $loop + 1 . '/' . $total_count;
			$progress_msg .= ' - ' . $result_sync['message'];

			wp_send_json_success(
				array(
					'loop'    => $loop + 1,
					'message' => $progress_msg,
					'total'   => $total_count,
				)
			);
		} else {
			wp_send_json_error( array( 'error' => 'Error' ) );
		}
	}

	public function attach_image( $post_id, $img_string ) {
		if ( ! $img_string || ! $post_id ) {
			return null;
		}

		$post         = get_post( $post_id );
		$upload_dir   = wp_upload_dir();
		$upload_path  = $upload_dir['path'];
		$filename     = $post->post_name . '-' . time() . '.png';
		$image_upload = file_put_contents( $upload_path . $filename, $img_string );
		// HANDLE UPLOADED FILE
		if ( ! function_exists( 'wp_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		$file = array(
			'error'    => '',
			'tmp_name' => $upload_path . $filename,
			'name'     => $filename,
			'type'     => 'image/png',
			'size'     => filesize( $upload_path . $filename ),
		);
		if ( ! empty( $file ) ) {
			$file_return = wp_handle_sideload( $file, array( 'test_form' => false ) );
			$filename    = $file_return['file'];
		}
		if ( isset( $file_return['file'] ) && isset( $file_return['file'] ) ) {
			$attachment = array(
				'post_mime_type' => $file_return['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', ' ', basename( $file_return['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $file_return['url'],
			);
			$attach_id  = wp_insert_attachment( $attachment, $filename, $post_id );
			if ( $attach_id ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$post_thumbnail_id = get_post_thumbnail_id( $post_id );
				if ( $post_thumbnail_id ) {
					wp_delete_attachment( $post_thumbnail_id, true );
				}
				$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				set_post_thumbnail( $post_id, $attach_id );
			}
		}
	}
}
