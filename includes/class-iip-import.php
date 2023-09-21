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
	 * The plugin file
	 *
	 * @var string
	 */
	private $file;
	private $products;
	private $ajax_msg;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		$this->ajax_msg = '';

		//add_action( iip_CRON, array( $this, 'import_products' ) );
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
				'url'        => admin_url( 'admin-ajax.php' ),
				'label_sync' => __( 'Syncing', 'import-holded-products-woocommerce' ),
				'nonce'      => wp_create_nonce( 'manual_import_nonce' ),
			)
		);
	}

	public function admin_print_footer_scripts() {
		$screen  = get_current_screen();
		$get_tab = isset( $_GET['tab'] ) ? (string) $_GET['tab'] : '';
		if ( 'toplevel_page_iip-options' === $screen->base && 'iip-import' === $get_tab ) {
			?>
		<style>
			.spinner{ float: none; }
		</style>
		<script type="text/javascript">
			var loop=0;
			jQuery(function($){
				$(document).find('#sync-inmovilla-engine').after('<div class="sync-wrapper"><h2><?php _e( 'Import Products from Holded', 'import-holded-products-woocommerce' ); ?></h2><p><?php _e( 'After you fillup the API settings, use the button below to import the products. The importing process may take a while and you need to keep this page open to complete it.', 'import-holded-products-woocommerce' ); ?><br/></p><button id="start-sync" class="button button-primary"><?php _e( 'Start Import', 'import-holded-products-woocommerce' ); ?></button></div>');
				$(document).find('#start-sync').on('click', function(){
					$(this).attr('disabled','disabled');
					$(this).after('<span class="spinner is-active"></span>');

					var syncAjaxCall = function(x){
						$.ajax({
							type: "POST",
							url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
							dataType: "json",
							data: {
								action: "import_products",
								syncLoop: x
							},
							success: function(results) {
								if(results.success){
									if(results.data.loop){
										syncAjaxCall(results.data.loop);
									}else{
										$(document).find('#start-sync').removeAttr('disabled');
										$(document).find('.sync-wrapper .spinner').remove();
									}
								} else {
									$(document).find('#start-sync').removeAttr('disabled');
									$(document).find('.sync-wrapper .spinner').remove();
								}
								if( results.data.msg != undefined ){
									if(!$(document).find('.sync-wrapper .progress').length)
										$(document).find('.sync-wrapper').append('<div class="progress"></div>');
									$(document).find('.sync-wrapper .progress').html('<p>'+results.data.msg+'</p>');
								}
							},
							error: function (xhr, text_status, error_thrown) {
								$(document).find('#start-sync').removeAttr('disabled');
								$(document).find('.sync-wrapper .spinner').remove();
								$(document).find('.sync-wrapper').append('<div class="progress">There was an Error! '+xhr.responseText+' '+text_status+': '+error_thrown+'</div>');
							}
								});
						}
						syncAjaxCall(window.loop);
					});
				});
			</script>
			<?php
		}
	}
	/**
	 * Import products from API
	 *
	 * @return void
	 */
	public function iip_import_method_products() {
		extract( $_REQUEST );
		global $wpdb;
		$not_sapi_cli = substr( php_sapi_name(), 0, 3 ) != 'cli' ? true : false;
		$doing_ajax   = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$apikey       = get_option( 'iip_api' );
		$prod_status  = get_option( 'iip_prodst' );
		$syncLoop     = isset( $syncLoop ) ? $syncLoop : 0;

		if ( ! isset( $this->products ) ) {
			$args_api = array(
				'paginacion',
				1,
				100,
				'ascensor=1',
				'precioinmo, precioalq'
			);

			$this->products = inmovilla_get_properties( $args_api );
		}

		if ( false === $this->products ) {
			if ( $doing_ajax ) {
				wp_send_json_error( array( 'msg' => 'Error' ) );
			} else {
				die();
			}
		} else {
			$products_array      = json_decode( $this->products );
			$products_count      = count( $products_array );
			$error_products_html = '';
			if ( $products_count ) {
				if ( ( $doing_ajax ) || $not_sapi_cli ) {
					$limit = 10;
					$count = $syncLoop + 1;
				}
				if ( $syncLoop > $products_count ) {
					if ( $doing_ajax ) {
						wp_send_json_error(
							array(
								'msg' => __( 'No products to import', 'import-holded-products-woocommerce' ),
							)
						);
					} else {
						die( __( 'No products to import', 'import-holded-products-woocommerce' ) );
					}
				} else {
					$item           = $products_array[ $syncLoop ];
					$is_new_product = false;
					$post_id = '';
					echo '<pre>';
					print_r($item);
					echo '</pre>';
					if ( $item->sku && 'simple' === $item->kind ) {
						$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value=%s LIMIT 1", $item->sku ) );

						if ( ! $post_id ) {
							$post_arg = array(
								'post_title'   => ( $item->name ) ? $item->name : '',
								'post_content' => ( $item->desc ) ? $item->desc : '',
								'post_status'  => $prod_status,
								'post_type'    => 'product',
							);
							$post_id  = wp_insert_post( $post_arg );
							if ( $post_id ) {
								$attach_id = update_post_meta( $post_id, '_sku', $item->sku );
							}
							$is_new_product = true;
						}
						if ( $post_id && $item->sku && 'simple' == $item->kind ) {
							wp_set_object_terms( $post_id, 'simple', 'product_type' );
							// Update meta for product.
							$this->update_product( $item, $post_id, 'simple' );
						} else {
							if ( $doing_ajax ) {
								wp_send_json_error(
									array(
										'msg' => __( 'There was an error while inserting new product!', 'import-holded-products-woocommerce' ) . ' ' . $item->name,
									)
								);
							} else {
								die( __( 'There was an error while inserting new product!', 'import-holded-products-woocommerce' ) );
							}
						}
					} elseif ( 'simple' !== $item->kind ) {
						// Product not synced without SKU
						$this->ajax_msg .= '<br/>' . __( 'Product type not supported. Product not imported: ', 'import-holded-products-woocommerce' ) . $item->name . '(' . $item->kind . ')</br>'; 
					} else {
						// Product not synced without SKU
						$this->ajax_msg .= '<br/>' . __( 'SKU is missing. Product not imported: ', 'import-holded-products-woocommerce' ) . $item->name . '</br>'; 
					}
				}

				if ( $doing_ajax || $not_sapi_cli ) {
					$products_synced = $syncLoop + 1;

					if ( $products_synced <= $products_count ) {
						$this->ajax_msg .= $products_synced . ' ' . __( 'products imported out of', 'import-holded-products-woocommerce' ) . ' ' . $products_count;
						if ( $products_synced == $products_count ) {
							$this->ajax_msg .= '<br/>' . __( 'All caught up!', 'import-holded-products-woocommerce' );
						}

						$args = array(
							'msg'           => $this->ajax_msg,
							'product_count' => $products_count,
						);
						if ( $doing_ajax ) {
							if ( $products_synced < $products_count ) {
								$args['loop'] = $syncLoop + 1;
							}
							wp_send_json_success( $args );
						} elseif ( $not_sapi_cli && $products_synced < $products_count ) {
							$url  = home_url() . '/?sync=true';
							$url .= '&syncLoop=' . ( $syncLoop + 1 );
							?>
							<script>
								window.location.href = '<?php echo $url; ?>';
							</script>
							<?php
							echo $args['msg'];
							die( 0 );
						}
					}
				}
			} else {
				if ( $doing_ajax ) {
					wp_send_json_error( array( 'msg' => __( 'No products to import', 'import-holded-products-woocommerce' ) ) );
				} else {
					die( __( 'No products to import', 'import-holded-products-woocommerce' ) );
				}
			}
		}
		if ( $doing_ajax ) {
			wp_die();
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

	/**
	 * Ajax function to load info
	 *
	 * @return void
	 */
	public function manual_import() {
		$loop = isset( $_POST['loop'] ) ? (int) $_POST['loop'] : 0;
		$post_type = 'property';
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
			$result_sync   = SYNC::sync_property( $item, $post_type );
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . $result_sync['message'];

			wp_send_json_success(
				array(
					'loop'    => $loop + 1,
					'message' => $progress_msg,
				)
			);
		} else {
			wp_send_json_error( array( 'error' => 'Error' ) );
		}
	}
}
