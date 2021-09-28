<?php
/**
 * Library for importing products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

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
class IIP_Import {
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
	public function __construct( $file ) {
		$this->file = $file;
		$this->ajax_msg = '';
		add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ), 11, 1 );
		add_action( 'wp_ajax_import_products', array( $this, 'import_products' ) );

		add_action( iip_CRON, array( $this, 'import_products' ) );
	}
	/**
	 * Imports products from Holded
	 *
	 * @return void
	 */
	public function import_products() {
		$this->iip_import_method_products();

		/*
		// Sends an email to admin
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( get_option( 'admin_email' ), __( 'Products Synced in', 'import-holded-products-woocommerce' ) . ' ' . get_option( 'blogname' ), '', $headers );*/
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
	 * Gets information from Holded CRM
	 *
	 * @return array
	 */
	private function get_products() {
		$apikey = get_option( 'iip_api' );
		$args = array(
			'headers' => array(
				'key' => $apikey,
			)
		);
		$response = wp_remote_get( 'https://api.holded.com/api/invoicing/v1/products', $args );
		if ( 200 === $response['response']['code'] ) {
			$body = wp_remote_retrieve_body( $response );

			return $body;
		} else {
			return false;
		}

	}
	/**
	 * Update product meta with the object included
	 *
	 * @param object $item Item Object from holded.
	 * @param string $product_id Product ID.
	 * @param string $type Type of the product.
	 * @return void
	 */
	private function update_product( $item, $product_id, $type ) {
		$tax_included = get_option( 'iip_taxinc' );
		$import_stock   = get_option( 'iip_stock' );
		if ( 'simple' === $type ) {
			if ( 'yes' === $tax_included ) {
				update_post_meta( $product_id, '_regular_price', $item->total );
				update_post_meta( $product_id, '_price', $item->total );
			} else {
				update_post_meta( $product_id, '_regular_price', $item->price );
				update_post_meta( $product_id, '_price', $item->price );
			}
			update_post_meta( $product_id, '_weight', $item->weight );
			update_post_meta( $product_id, '_barcode', $item->barcode );
			update_post_meta( $product_id, '_product_attributes', array() );

			// Check if the product can be sold.
			if ( 'no' === $import_stock && $item->price > 0) {
				update_post_meta( $product_id, '_stock_status', 'instock' );
				update_post_meta( $product_id, '_visibility', 'visible' );
				wp_remove_object_terms( $product_id, 'exclude-from-catalog', 'product_visibility' );
				wp_remove_object_terms( $product_id, 'exclude-from-search', 'product_visibility' );
			} elseif ( 'yes' === $import_stock && $item->stock > 0 ) {
				update_post_meta( $product_id, '_manage_stock', 'yes' );
				update_post_meta( $product_id, '_backorders', 'no' );
				update_post_meta( $product_id, '_stock', $item->stock );
				update_post_meta( $product_id, '_stock_status', 'instock' );
				update_post_meta( $product_id, '_visibility', 'visible' );
				wp_remove_object_terms( $product_id, 'exclude-from-catalog', 'product_visibility' );
				wp_remove_object_terms( $product_id, 'exclude-from-search', 'product_visibility' );
			} elseif ( 'yes' === $import_stock && $item->stock == 0 ) {
				update_post_meta( $product_id, '_manage_stock', 'yes' );
				update_post_meta( $product_id, '_backorders', 'no' );
				update_post_meta( $product_id, '_stock', $item->stock );
				update_post_meta( $product_id, '_visibility', 'hidden' );
				update_post_meta( $product_id, '_stock_status', 'outofstock' );

				wp_set_object_terms(
					$product_id,
					array(
						'exclude-from-catalog',
						'exclude-from-search',
					),
					'product_visibility'
				);
			} else {
				update_post_meta( $product_id, '_stock_status', 'outofstock' );

				wp_set_object_terms(
					$product_id,
					array(
						'exclude-from-catalog',
						'exclude-from-search',
					),
					'product_visibility'
				);
			}
			/*
			update_post_meta( $product_id, '_product_attributes', array(
				'brand' => array('name' => __('Brand', 'import-holded-products-woocommerce'), 'value' => $item->marca, 'position' => 0),
				'model' => array('name' => __('Model', 'import-holded-products-woocommerce'), 'value' => $item->modelo, 'position' => 1),
			)
			);*/
		}
		// Default values.
		update_post_meta( $product_id, '_sale_price', '' );
		update_post_meta( $product_id, '_sale_price_dates_from', '' );
		update_post_meta( $product_id, '_sale_price_dates_to', '' );
		update_post_meta( $product_id, '_tax_status', 'taxable' );
		update_post_meta( $product_id, '_tax_class', '' );
		update_post_meta( $product_id, '_sold_individually', 'no' );
		update_post_meta( $product_id, '_virtual', 'no' );
		update_post_meta( $product_id, '_downloadable', 'no' );

		/*
		//Category
		$parent_id      = substr($item->familia, 0, 2);
		$parent_familia = $this->db->query("SELECT * FROM familias where id={$parent_id} LIMIT 1");

		if (is_object($parent_familia)&&$parent_familia->num_rows&&$is_new_product) {

			while ($parent_familia_item = $parent_familia->fetch_object()) {
				$parent_terms = get_terms(array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'meta_query' => array(
						array(
							'key'   => 'iip_meta_famid',
							'value' => $parent_familia_item->id,
						),
					),
				));
				// echo '<pre>';print_r($terms);print_r($familia_item);echo '</pre>';
				// die(0);
				if (empty($parent_terms)) {
					$term = wp_set_object_terms($post_id, $parent_familia_item->nombre, 'product_cat');
					if (is_array($term) && !empty($term)) {
						update_term_meta($term[0], 'iip_meta_famid', $parent_familia_item->id);
					}
				} else {
					foreach ($parent_terms as $term) {
						wp_update_term($term->term_id, 'product_cat', array(
							'name' => $parent_familia_item->nombre,
							'slug' => sanitize_title($parent_familia_item->nombre),
						));
						wp_set_object_terms($post_id, $term->term_id, 'product_cat');
						update_term_meta($term->term_id, 'iip_meta_famid', $parent_familia_item->id);
					}
				}
			}
		}
		$familia = $this->db->query("SELECT * FROM familias where id={$item->familia} LIMIT 1");
		if (is_object($familia)&&$familia->num_rows&&$is_new_product) {
			while ($familia_item = $familia->fetch_object()) {
				$terms = get_terms(array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'meta_query' => array(
						array(
							'key'   => 'iip_meta_famid',
							'value' => $familia_item->id,
						),
					),
				));
				// echo '<pre>';print_r($terms);print_r($familia_item);echo '</pre>';
				// die(0);
				if (empty($terms)) {
					$term = wp_set_object_terms($post_id, $familia_item->nombre, 'product_cat', true);
					if (is_array($term) && !empty($term)) {
						update_term_meta($term[0], 'iip_meta_famid', $familia_item->id);
					}
				} else {
					foreach ($terms as $term) {
						wp_update_term($term->term_id, 'product_cat', array(
							'name' => $familia_item->nombre,
							'slug' => sanitize_title($familia_item->nombre),
						));
						wp_set_object_terms($post_id, $term->term_id, 'product_cat', true);
						update_term_meta($term->term_id, 'iip_meta_famid', $familia_item->id);
					}
				}
			}
		}
		if ($is_new_product) {
			//Create images only for new products
			$imagen = $this->db->query("SELECT * FROM imagenes where id={$item->imagen} LIMIT 1");
			if ($imagen->num_rows) {
				while ($imagen_item = $imagen->fetch_object()) {
					if ($imagen_item->imagen) {
						$this->attach_image($post_id, $imagen_item->imagen);
					}
				}
			}
		}*/
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
			$this->products = $this->get_products();
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
}

new IIP_Import( __FILE__ );
