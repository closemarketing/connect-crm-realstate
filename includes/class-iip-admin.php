<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Library for Page Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class Admin {
	/**
	 * Settings CRM
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Settings CRM
	 *
	 * @var array
	 */
	private $settings_fields;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_settings' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'wp_ajax_ccrmre_auto_map_fields', array( $this, 'ajax_auto_map_fields' ) );
		add_action( 'wp_ajax_ccrmre_load_log_content', array( $this, 'ajax_load_log_content' ) );
	}

	/**
	 * Show admin notices
	 *
	 * @return void
	 */
	public function show_admin_notices() {
		// Check if we just saved merge fields.
		if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) && 'iip-options' === $_GET['page'] ) {
			$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

			if ( 'iip-merge' === $active_tab ) {
				$merge_fields = get_option( 'conncrmreal_merge_fields' );
				$count        = is_array( $merge_fields ) ? count( $merge_fields ) : 0;

				echo '<div class="notice notice-success is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Merge fields saved successfully!', 'connect-crm-realstate' ) . '</strong> ';
				printf(
					/* translators: %d: number of mappings */
					esc_html( _n( '%d field mapping saved.', '%d field mappings saved.', $count, 'connect-crm-realstate' ) ),
					(int) $count
				);
				echo '</p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Create custom plugin settings menu
	 *
	 * @return void
	 */
	public function plugin_settings() {
		wp_register_style(
			'iip_admin-styles',
			plugin_dir_url( __FILE__ ) . 'assets/iip-styles-admin.css',
			array(),
			CCRMRE_VERSION
		);
		wp_enqueue_style( 'iip_admin-styles' );

		// Create new top-level menu.
		add_menu_page(
			__( 'Connect CRM Real State', 'connect-crm-realstate' ),
			__( 'Connect CRM Real State', 'connect-crm-realstate' ),
			'manage_options',
			'iip-options',
			array( $this, 'plugin_options_page' ),
			'dashicons-rest-api'
		);

		// Call register settings function.
		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );

		// Enqueue scripts for specific tabs.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our plugin page.
		if ( 'toplevel_page_iip-options' !== $hook ) {
			return;
		}

		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );
		$active_tab = ! ccrmre_is_license_active() ? 'iip-license' : $active_tab;

		// Enqueue import styles on manual import tab.
		if ( 'iip-import' === $active_tab ) {
			wp_enqueue_style(
				'ccrmre-admin-import',
				CCRMRE_PLUGIN_URL . 'assets/css/admin-import.css',
				array(),
				CCRMRE_VERSION
			);
		}

		// Enqueue settings scripts on settings tab.
		if ( 'iip-settings' === $active_tab && ccrmre_is_license_active() ) {
			wp_enqueue_script(
				'ccrmre-settings',
				plugin_dir_url( __FILE__ ) . 'assets/iip-settings.js',
				array( 'jquery' ),
				CCRMRE_VERSION,
				true
			);
		}

		// Enqueue select2 and merge fields scripts only on merge tab.
		if ( 'iip-merge' === $active_tab && ccrmre_is_license_active() ) {
			// Enqueue Select2 from local vendor.
			wp_enqueue_style(
				'ccrmre-select2',
				CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/css/select2.min.css',
				array(),
				'4.0.13'
			);
			wp_enqueue_script(
				'ccrmre-select2',
				CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/select2.min.js',
				array( 'jquery' ),
				CCRMRE_VERSION,
				true
			);

			// Enqueue Select2 Spanish translation.
			$locale = get_locale();
			if ( strpos( $locale, 'es_' ) === 0 ) {
				wp_enqueue_script(
					'ccrmre-select2-i18n',
					CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/i18n/es.js',
					array( 'ccrmre-select2' ),
					CCRMRE_VERSION,
					true
				);
			}

			// Enqueue merge fields styles.
			wp_enqueue_style(
				'ccrmre-merge-fields',
				plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.css',
				array(),
				CCRMRE_VERSION
			);

			// Enqueue merge fields script.
			wp_enqueue_script(
				'ccrmre-merge-fields',
				plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.js',
				array( 'jquery', 'ccrmre-select2' ),
				CCRMRE_VERSION,
				true
			);

			// Localize script for translations.
			wp_localize_script(
				'ccrmre-merge-fields',
				'ccrmreMergeFields',
				array(
					'searchPlaceholder' => __( 'Search or create WordPress field...', 'connect-crm-realstate' ),
					'newFieldLabel'     => __( '(New field)', 'connect-crm-realstate' ),
					'infoTitle'         => __( 'Creating New Fields:', 'connect-crm-realstate' ),
					'infoMessage'       => __( 'You can create new WordPress custom fields by typing a name that doesn\'t exist in the list. The field name will be automatically sanitized (lowercase, numbers, and underscores only).', 'connect-crm-realstate' ),
					'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
					'nonce'             => wp_create_nonce( 'ccrmre_auto_map_nonce' ),
					'autoMapping'       => __( 'Auto-mapping fields...', 'connect-crm-realstate' ),
					'autoMapSuccess'    => __( 'All fields have been auto-mapped successfully!', 'connect-crm-realstate' ),
					'autoMapError'      => __( 'Error auto-mapping fields. Please try again.', 'connect-crm-realstate' ),
					'confirmAutoMap'    => __( 'This will auto-generate WordPress field names for all CRM fields. Existing mappings will be preserved. Continue?', 'connect-crm-realstate' ),
				)
			);
		}
	}

	/**
	 * Adds plugin settings page
	 *
	 * @return void
	 */
	public function plugin_options_page() {
		$this->settings        = get_option( 'conncrmreal_settings' );
		$this->settings_fields = get_option( 'conncrmreal_merge_fields' );

		// Set active class for navigation tabs.
		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );
		$active_tab = ! ccrmre_is_license_active() ? 'iip-license' : $active_tab;

		echo '<div class="wrap bialty-containter">';
		echo '<h2><span class="dashicons dashicons-media-text" style="margin-top: 6px; font-size: 24px;"></span> ' . esc_html__( 'Connect CRM Real State', 'connect-crm-realstate' ) . '</h2>';
		echo '<h2 class="nav-tab-wrapper">';

		if ( ccrmre_is_license_active() ) {
			// Import Properties.
			echo '<a href="' . esc_url( '?page=iip-options&tab=iip-import' ) . '" class="nav-tab ';
			echo ( 'iip-import' === $active_tab ? 'nav-tab-active' : '' );
			echo '">' . esc_html__( 'Import Properties', 'connect-crm-realstate' ) . '</a>';

			// Settings Properties.
			echo '<a href="' . esc_url( '?page=iip-options&tab=iip-settings' ) . '" class="nav-tab ';
			echo ( 'iip-settings' === $active_tab ? 'nav-tab-active' : '' );
			echo '">' . esc_html__( 'Settings', 'connect-crm-realstate' ) . '</a>';

			// Merge variables.
			echo '<a href="' . esc_url( '?page=iip-options&tab=iip-merge' ) . '" class="nav-tab ';
			echo ( 'iip-merge' === $active_tab ? 'nav-tab-active' : '' );
			echo '">' . esc_html__( 'Merge variables', 'connect-crm-realstate' ) . '</a>';
		}

		// License.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-license' ) . '" class="nav-tab ';
		echo ( 'iip-license' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'License', 'connect-crm-realstate' ) . '</a>';

		echo '</h2>';

		if ( ccrmre_is_license_active() ) {
			if ( 'iip-import' === $active_tab ) {
				$this->plugin_import_page();
			}

			if ( 'iip-settings' === $active_tab ) {
				echo '<form method="post" action="options.php">';
				settings_fields( 'admin_conncrmreal_settings' );
				do_settings_sections( 'conncrmreal_settings' );
				submit_button( esc_html__( 'Save changes', 'connect-crm-realstate' ) );
				echo '</form>';
			}

			if ( 'iip-merge' === $active_tab ) {
				?>
				<h1><?php esc_html_e( 'Merge Variables with custom values', 'connect-crm-realstate' ); ?></h1>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Creating New Fields:', 'connect-crm-realstate' ); ?></strong>
						<?php esc_html_e( 'You can create new WordPress custom fields by typing a name that doesn\'t exist in the list. The field name will be automatically sanitized (lowercase, numbers, and underscores only).', 'connect-crm-realstate' ); ?>
					</p>
				</div>
				<form method="post" action="options.php" id="ccrmre-merge-form">
					<?php settings_fields( 'iip_plugin_merge_group' ); ?>
					<?php do_settings_sections( 'conncrmreal_merge_fields' ); ?>
					<?php submit_button(); ?>
				</form>
				<?php
			}
		}

		if ( 'iip-license' === $active_tab ) {
			do_action( 'ccrmre_license_settings_content' );
		}

		echo '</div>'; // Close wrap div.
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_plugin_settings() {
		$this->settings = get_option( 'conncrmreal_settings' );

		// Register our settings.
		register_setting(
			'admin_conncrmreal_settings',
			'conncrmreal_settings',
			array( $this, 'sanitize_fields_settings' )
		);

		add_settings_section(
			'admin_conncrmreal_settings',
			__( 'Settings for Integration with CRM Real State', 'connect-crm-realstate' ),
			array( $this, 'admin_section_settings_info' ),
			'conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_type',
			__( 'Type', 'connect-crm-realstate' ),
			array( $this, 'type_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_apipassword',
			__( 'API Password / Token', 'connect-crm-realstate' ),
			array( $this, 'apipassword_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		// Inmovilla specific fields.
		if ( isset( $this->settings['type'] ) && 'inmovilla' === $this->settings['type'] ) {
			add_settings_field(
				'conncrmreal_numagencia',
				__( 'Agency Number', 'connect-crm-realstate' ),
				array( $this, 'numagencia_callback' ),
				'conncrmreal_settings',
				'admin_conncrmreal_settings'
			);
		}

		$sync_minutes = CCRMRE_SYNC_PERIOD / 60;
		add_settings_field(
			'conncrmreal_cron',
			sprintf(
				/* translators: %s: minutes */
				__( 'Sync with Cron (every %s minutes)?', 'connect-crm-realstate' ),
				$sync_minutes
			),
			array( $this, 'cron_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_post_type',
			__( 'Post Type', 'connect-crm-realstate' ),
			array( $this, 'post_type_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		if ( isset( $this->settings['post_type'] ) && 'property' === $this->settings['post_type'] ) {
			add_settings_field(
				'conncrmreal_post_type_slug',
				__( 'Post Type SLUG', 'connect-crm-realstate' ),
				array( $this, 'post_type_slug_callback' ),
				'conncrmreal_settings',
				'admin_conncrmreal_settings'
			);
		}
		add_settings_field(
			'conncrmreal_postal_code',
			__( 'Include Properties by Postal Code', 'connect-crm-realstate' ),
			array( $this, 'postal_code_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_sold_action',
			__( 'Action for Sold/Unavailable Properties', 'connect-crm-realstate' ),
			array( $this, 'sold_action_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_download_images',
			__( 'Download Images Locally', 'connect-crm-realstate' ),
			array( $this, 'download_images_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_show_gallery',
			__( 'Auto Display Photo Gallery', 'connect-crm-realstate' ),
			array( $this, 'show_gallery_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_show_property_info',
			__( 'Auto Display Property Info Box', 'connect-crm-realstate' ),
			array( $this, 'show_property_info_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		// Register our settings.
		register_setting(
			'iip_plugin_merge_group',
			'conncrmreal_merge_fields',
			array( $this, 'sanitize_fields_settings_merge' )
		);

		add_settings_section(
			'iip_plugin_merge_group',
			__( 'Settings for Integration with CRM Real State', 'connect-crm-realstate' ),
			array( $this, 'admin_section_settings_info_merge' ),
			'conncrmreal_merge_fields'
		);

		add_settings_field(
			'conncrmreal_merge_fields',
			__( 'Merge Fields', 'connect-crm-realstate' ),
			array( $this, 'merge_fields_callback' ),
			'conncrmreal_merge_fields',
			'iip_plugin_merge_group'
		);
	}

	/**
	 * Sanitize fiels before saves in DB
	 *
	 * @param array $input Input fields.
	 * @return array
	 */
	public function sanitize_fields_settings( $input ) {
		$sanitary_values = array();

		$field_values = array(
			'type',
			'apipassword',
			'numagencia',
			'cron',
			'post_type',
			'post_type_slug',
			'postal_code',
			'sold_action',
			'download_images',
			'show_gallery',
			'show_property_info',
		);

		foreach ( $field_values as $field_value ) {
			if ( isset( $input[ $field_value ] ) ) {
				$sanitary_values[ $field_value ] = sanitize_text_field( $input[ $field_value ] );
			}
		}

		$sanitary_values['api_pagination'] = 'anaconda' === $input['type'] ? 200 : 100;

		// Invalidate API cache when credentials are updated.
		self::invalidate_api_cache();

		// Force immediate re-validation of credentials.
		$crm_type          = isset( $input['type'] ) ? $input['type'] : 'anaconda';
		$validation_result = $this->validate_api_credentials( $crm_type, true );

		// Add admin notice based on validation result.
		if ( $validation_result['valid'] ) {
			add_settings_error(
				'conncrmreal_settings',
				'credentials_validated',
				__( 'Settings saved successfully. API credentials are valid and working correctly.', 'connect-crm-realstate' ),
				'success'
			);
		} else {
			add_settings_error(
				'conncrmreal_settings',
				'credentials_invalid',
				sprintf(
					/* translators: %s: error message from API */
					__( 'Settings saved, but API validation failed: %s', 'connect-crm-realstate' ),
					$validation_result['message']
				),
				'warning'
			);
		}

		return $sanitary_values;
	}

	/**
	 * Show title callback
	 *
	 * @return void
	 */
	public function type_callback() {
		$type_option = isset( $this->settings['type'] ) ? $this->settings['type'] : 'show';
		?>
		<select name="conncrmreal_settings[type]" id="type">
			<option value="anaconda" <?php selected( $type_option, 'anaconda' ); ?>><?php esc_html_e( 'Anaconda', 'connect-crm-realstate' ); ?></option>
			<option value="inmovilla" <?php selected( $type_option, 'inmovilla' ); ?>><?php esc_html_e( 'Inmovilla APIWEB', 'connect-crm-realstate' ); ?></option>
			<option value="inmovilla_procesos" <?php selected( $type_option, 'inmovilla_procesos' ); ?>><?php esc_html_e( 'Inmovilla Procesos', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Password callback
	 *
	 * @return void
	 */
	public function apipassword_callback() {
		$type_option = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';
		$label       = in_array( $type_option, array( 'inmovilla', 'inmovilla_procesos' ), true ) ? __( 'API Password', 'connect-crm-realstate' ) : __( 'API Token', 'connect-crm-realstate' );

		printf(
			'<input class="regular-text" type="password" name="conncrmreal_settings[apipassword]" id="apipassword" value="%s"><br><small>%s</small>',
			isset( $this->settings['apipassword'] ) ? esc_attr( $this->settings['apipassword'] ) : '',
			esc_html( $label )
		);
	}

	/**
	 * Agency Number callback (Inmovilla only)
	 *
	 * @return void
	 */
	public function numagencia_callback() {
		printf(
			'<input class="regular-text" type="text" name="conncrmreal_settings[numagencia]" id="numagencia" value="%s"><br><small>%s</small>',
			isset( $this->settings['numagencia'] ) ? esc_attr( $this->settings['numagencia'] ) : '',
			esc_html__( 'Agency number from Inmovilla. Example: 2', 'connect-crm-realstate' )
		);
	}

	/**
	 * Cron callback
	 *
	 * @return void
	 */
	public function cron_callback() {
		$cron_option = isset( $this->settings['cron'] ) ? $this->settings['cron'] : 'no';
		?>
		<select name="conncrmreal_settings[cron]" id="cron">
			<option value="no" <?php selected( $cron_option, 'no' ); ?>><?php esc_html_e( 'No', 'connect-crm-realstate' ); ?></option>
			<option value="yes" <?php selected( $cron_option, 'yes' ); ?>><?php esc_html_e( 'Yes', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Show title callback
	 *
	 * @return void
	 */
	public function post_type_callback() {
		$post_type_option = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'show';

		$args       = array(
			'public' => true,
		);
		$post_types = get_post_types( $args );
		unset( $post_types['attachment'] );
		?>
		<select name="conncrmreal_settings[post_type]" id="post_type">
			<option value="property" <?php selected( $post_type_option, 'property' ); ?>><?php esc_html_e( 'Created by this plugin', 'connect-crm-realstate' ); ?></option>
			<?php
			foreach ( $post_types as $post_type ) {
				?>
				<option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $post_type_option, $post_type ); ?>><?php echo esc_html( $post_type ); ?></option>
				<?php
			}
			?>
		</select>
		<?php
	}

	/**
	 * Post Type callback
	 *
	 * @return void
	 */
	public function post_type_slug_callback() {
		printf(
			'<input class="regular-text" type="text" name="conncrmreal_settings[post_type_slug]" id="post_type_slug" value="%s">',
			isset( $this->settings['post_type_slug'] ) ? esc_attr( $this->settings['post_type_slug'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Slug for the post type. If you change this, you need to save the permalinks again.', 'connect-crm-realstate' )
		);
	}

	/**
	 * Postal code callback
	 *
	 * @return void
	 */
	public function postal_code_callback() {
		printf(
			'<input class="regular-text" type="text" name="conncrmreal_settings[postal_code]" id="postal_code" value="%s">',
			isset( $this->settings['postal_code'] ) ? esc_attr( $this->settings['postal_code'] ) : ''
		);
		printf(
			/* translators: %s: description */
			'<p class="description">%s</p>',
			esc_html__( 'Include all properties by Postal Code. If it is blank, will import all properties. Add Postal codes that you will like to import. For example: 18100. You can use placeholder like 18* to include all Granada. Add multiple zones by separated by comma.', 'connect-crm-realstate' )
		);
	}

	/**
	 * Sold action callback
	 *
	 * @return void
	 */
	public function sold_action_callback() {
		$sold_action = isset( $this->settings['sold_action'] ) ? $this->settings['sold_action'] : 'draft';
		?>
		<select name="conncrmreal_settings[sold_action]" id="sold_action">
			<option value="draft" <?php selected( $sold_action, 'draft' ); ?>><?php esc_html_e( 'Unpublish (Set to Draft)', 'connect-crm-realstate' ); ?></option>
			<option value="keep" <?php selected( $sold_action, 'keep' ); ?>><?php esc_html_e( 'Keep Published', 'connect-crm-realstate' ); ?></option>
			<option value="trash" <?php selected( $sold_action, 'trash' ); ?>><?php esc_html_e( 'Move to Trash', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
			/* translators: %s: description */
			'<p class="description">%s</p>',
			esc_html__( 'Choose what to do with properties that are sold or no longer available in the CRM.', 'connect-crm-realstate' )
		);
	}

	/**
	 * Download images callback
	 *
	 * @return void
	 */
	public function download_images_callback() {
		$download_images = isset( $this->settings['download_images'] ) ? $this->settings['download_images'] : 'no';
		?>
		<select name="conncrmreal_settings[download_images]" id="download_images">
			<option value="no" <?php selected( $download_images, 'no' ); ?>><?php esc_html_e( 'No - Use external image links', 'connect-crm-realstate' ); ?></option>
			<option value="featured" <?php selected( $download_images, 'featured' ); ?>><?php esc_html_e( 'Featured image only', 'connect-crm-realstate' ); ?></option>
			<option value="all" <?php selected( $download_images, 'all' ); ?>><?php esc_html_e( 'Yes - All images (featured + gallery)', 'connect-crm-realstate' ); ?></option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose whether to download property images to your server.', 'connect-crm-realstate' ); ?>
		</p>
		<ul class="description" style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Downloading images improves page speed, works with your CDN, and does not depend on the CRM being available.', 'connect-crm-realstate' ); ?></li>
			<li><?php esc_html_e( 'However, images will use disk space on your server and the import process will take longer.', 'connect-crm-realstate' ); ?></li>
			<li><?php esc_html_e( '"Featured image only" downloads just the main photo. "All images" downloads the full gallery as well.', 'connect-crm-realstate' ); ?></li>
		</ul>
		<?php
	}

	/**
	 * Show gallery callback
	 *
	 * @return void
	 */
	public function show_gallery_callback() {
		$show_gallery = isset( $this->settings['show_gallery'] ) ? $this->settings['show_gallery'] : 'no';
		?>
		<select name="conncrmreal_settings[show_gallery]" id="show_gallery">
			<option value="no" <?php selected( $show_gallery, 'no' ); ?>><?php esc_html_e( 'No - Use shortcode only', 'connect-crm-realstate' ); ?></option>
			<option value="yes" <?php selected( $show_gallery, 'yes' ); ?>><?php esc_html_e( 'Yes - Auto display after title', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
			/* translators: %s: shortcode */
			'<p class="description">%s <code>[property_gallery]</code></p>',
			esc_html__( 'Enable automatic display of photo gallery carousel after the property title, or use the shortcode manually:', 'connect-crm-realstate' )
		);
	}

	/**
	 * Show property info callback
	 *
	 * @return void
	 */
	public function show_property_info_callback() {
		$show_property_info = isset( $this->settings['show_property_info'] ) ? $this->settings['show_property_info'] : 'no';
		?>
		<select name="conncrmreal_settings[show_property_info]" id="show_property_info">
			<option value="no" <?php selected( $show_property_info, 'no' ); ?>><?php esc_html_e( 'No - Use shortcode only', 'connect-crm-realstate' ); ?></option>
			<option value="yes" <?php selected( $show_property_info, 'yes' ); ?>><?php esc_html_e( 'Yes - Auto display after content', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
			/* translators: %s: shortcode */
			'<p class="description">%s <code>[property_info]</code></p>',
			esc_html__( 'Enable automatic display of property information box with icons and price, or use the shortcode manually:', 'connect-crm-realstate' )
		);
	}

	/**
	 * Import Page
	 *
	 * @return void
	 */
	public function plugin_import_page() {
		$settings   = get_option( 'conncrmreal_settings' );
		$crm_type   = isset( $settings['type'] ) ? $settings['type'] : '';
		$pagination = API::get_pagination_size( $crm_type );

		// Check API credentials validation.
		$credentials_valid = $this->validate_api_credentials( $crm_type );
		$buttons_disabled  = ! $credentials_valid['valid'];
		?>
		<div class="connect-realstate-manual-action">
			<h2><?php esc_html_e( 'Import Properties', 'connect-crm-realstate' ); ?></h2>

			<?php if ( ! $credentials_valid['valid'] ) : ?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'API Connection Error:', 'connect-crm-realstate' ); ?></strong>
						<?php echo esc_html( $credentials_valid['message'] ); ?>
					</p>
				</div>
			<?php endif; ?>
			
			<!-- Import Statistics -->
			<div class="ccrmre-import-stats">
				<div class="ccrmre-stat-card">
					<div class="ccrmre-stat-icon ccrmre-icon-api">
						<span class="dashicons dashicons-cloud"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-available-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'Available in API', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel">
							<?php esc_html_e( 'Total:', 'connect-crm-realstate' ); ?> <span id="stat-api-count">--</span>
						</div>
					</div>
				</div>

				<div class="ccrmre-stat-card">
					<div class="ccrmre-stat-icon ccrmre-icon-wp">
						<span class="dashicons dashicons-wordpress-alt"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-wp-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'Properties in WordPress', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel"><?php esc_html_e( 'Published properties', 'connect-crm-realstate' ); ?></div>
					</div>
				</div>

				<div class="ccrmre-stat-card ccrmre-stat-import">
					<div class="ccrmre-stat-icon ccrmre-icon-import">
						<span class="dashicons dashicons-download"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-import-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'To Import/Update', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel">
							<span id="stat-new-count">--</span> <?php esc_html_e( 'new', 'connect-crm-realstate' ); ?> + 
							<span id="stat-outdated-count">--</span> <?php esc_html_e( 'outdated', 'connect-crm-realstate' ); ?>
						</div>
					</div>
				</div>

				<div class="ccrmre-stat-card ccrmre-stat-delete">
					<div class="ccrmre-stat-icon ccrmre-icon-delete">
						<span class="dashicons dashicons-trash"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-delete-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'To Remove', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel"><?php esc_html_e( 'Not in API', 'connect-crm-realstate' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Two Column Layout: Automatic Sync + Manual Import -->
			<div class="ccrmre-two-columns">
				<?php
				// Column 1: Show latest cron logs section.
				$cron_enabled = isset( $settings['cron'] ) && 'yes' === $settings['cron'];
				?>
				<div class="ccrmre-cron-logs">
					<h3>
						<span class="dashicons dashicons-clock" style="vertical-align: middle;"></span>
						<?php esc_html_e( 'Automatic Sync (Cron)', 'connect-crm-realstate' ); ?>
						<?php if ( $cron_enabled ) : ?>
							<span style="color: green; font-size: 0.8em; font-weight: normal;">● <?php esc_html_e( 'Enabled', 'connect-crm-realstate' ); ?></span>
						<?php else : ?>
							<span style="color: #999; font-size: 0.8em; font-weight: normal;">○ <?php esc_html_e( 'Disabled', 'connect-crm-realstate' ); ?></span>
						<?php endif; ?>
					</h3>
					<p style="color: #646970; font-size: 14px; margin-top: 10px;">
						<?php esc_html_e( 'Check the logs in the tab below to see automatic sync history.', 'connect-crm-realstate' ); ?>
					</p>
				</div>

				<!-- Column 2: Manual Import Section -->
				<div class="ccrmre-manual-import">
					<h3>
						<span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
						<?php esc_html_e( 'Manual Import', 'connect-crm-realstate' ); ?>
					</h3>

					<div class="import-button-wrapper">
						<select id="import-mode" class="import-mode-select" <?php echo $buttons_disabled ? 'disabled' : ''; ?>>
							<option value="updated"><?php esc_html_e( 'Properties to update', 'connect-crm-realstate' ); ?></option>
							<option value="all"><?php esc_html_e( 'All properties', 'connect-crm-realstate' ); ?></option>
						</select>
						<button type="button" id="manual_import" name="manual_import" class="button button-large button-primary" onclick="syncManualProperties(this, 0, <?php echo (int) $pagination; ?>);" <?php echo $buttons_disabled ? 'disabled' : ''; ?>>
							<?php esc_html_e( 'Start Import', 'connect-crm-realstate' ); ?>
						</button>
						<button type="button" id="refresh_stats" name="refresh_stats" class="button button-large" onclick="loadImportStats();" <?php echo $buttons_disabled ? 'disabled' : ''; ?>>
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Refresh Statistics', 'connect-crm-realstate' ); ?>
						</button>
						<span class="spinner"></span>
					</div>
				</div>
			</div>

			<!-- Unified Log Section with Tabs -->
			<div class="ccrmre-log-container">
				<div class="ccrmre-log-tabs">
					<button class="ccrmre-tab-button active" data-tab="automatic">
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Automatic Sync', 'connect-crm-realstate' ); ?>
					</button>
					<button class="ccrmre-tab-button" data-tab="manual">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Manual Import', 'connect-crm-realstate' ); ?>
					</button>
				</div>

				<div class="ccrmre-tab-content">
					<!-- Automatic Sync Tab (Accordion Logs) -->
					<div class="ccrmre-tab-pane active" id="tab-automatic">
						<div class="ccrmre-log-list">
							<?php
							$uploads_dir = wp_upload_dir();
							$folder      = $uploads_dir['basedir'] . '/ccrmre_logs/';
							$files       = file_exists( $folder ) ? list_files( $folder, 1 ) : array();

							// Sort by modification time (newest first).
							usort(
								$files,
								function ( $a, $b ) {
									return filemtime( $b ) - filemtime( $a );
								}
							);

							if ( empty( $files ) ) :
								?>
								<p style="color: #666; font-style: italic; padding: 20px; text-align: center;">
									<?php esc_html_e( 'No automatic sync logs yet.', 'connect-crm-realstate' ); ?>
								</p>
								<?php
							else :
								foreach ( $files as $file ) {
									if ( is_file( $file ) ) {
										$filename  = basename( $file );
										$file_open = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
										$line      = $file_open ? fgets( $file_open ) : '';
										if ( $file_open ) {
											fclose( $file_open ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
										}
										?>
										<div class="ccrmre-log-item" data-filename="<?php echo esc_attr( $filename ); ?>">
											<div class="ccrmre-log-header">
												<span class="ccrmre-log-toggle dashicons dashicons-arrow-right"></span>
												<span class="ccrmre-log-title"><?php echo ! empty( $line ) ? esc_html( $line ) : esc_html( $filename ); ?></span>
											</div>
											<div class="ccrmre-log-content" style="display: none;">
												<div class="ccrmre-log-loading">
													<span class="spinner is-active"></span>
													<?php esc_html_e( 'Loading...', 'connect-crm-realstate' ); ?>
												</div>
											</div>
										</div>
										<?php
									}
								}
							endif;
							?>
						</div>
					</div>

					<!-- Manual Import Tab -->
					<div class="ccrmre-tab-pane" id="tab-manual">
						<fieldset id="logwrapper" style="border: none; padding: 0; margin: 0;">
							<div id="loglist"></div>
						</fieldset>
					</div>
				</div>
			</div>

			<?php
			// Show API limitations info.
			$crm_type   = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';
			$api_config = API::get_api_config( $crm_type );

			if ( ! empty( $api_config ) ) {
				// Format values for display.
				$timeout_minutes = $api_config['timeout'] / 60;
				$timeout_display = $timeout_minutes > 1
					? $timeout_minutes . ' ' . __( 'minutes', 'connect-crm-realstate' )
					: $timeout_minutes . ' ' . __( 'minute', 'connect-crm-realstate' );

				$pagination_display = -1 === $api_config['pagination']
					? __( 'All at once', 'connect-crm-realstate' )
					: $api_config['pagination'];

				$retry_timeout_display    = $api_config['retry_timeout'] . ' ' . __( 'seconds', 'connect-crm-realstate' );
				$retry_rate_limit_minutes = $api_config['retry_rate_limit'] / 60;
				$retry_rate_limit_display = $retry_rate_limit_minutes . ' ' . __( 'minutes', 'connect-crm-realstate' );

				$info = array(
					'name'             => $api_config['name'],
					'timeout'          => $timeout_display,
					'pagination'       => $pagination_display,
					'retry_timeout'    => $retry_timeout_display,
					'retry_rate_limit' => $retry_rate_limit_display,
					'max_retries'      => $api_config['max_retries'],
				);
				?>
				<div class="api-limitations-info" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
					<h4 style="margin: 0 0 10px 0; color: #2271b1;">
						<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: API name */
								__( 'API Limitations - %s', 'connect-crm-realstate' ),
								$info['name']
							)
						);
						?>
					</h4>
					<ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
						<li>
							<strong><?php esc_html_e( 'Request Timeout:', 'connect-crm-realstate' ); ?></strong>
							<?php echo esc_html( $info['timeout'] ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Properties per Request:', 'connect-crm-realstate' ); ?></strong>
							<?php echo esc_html( $info['pagination'] ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Automatic Retries:', 'connect-crm-realstate' ); ?></strong>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: max retries */
									__( 'Up to %d attempts', 'connect-crm-realstate' ),
									$info['max_retries']
								)
							);
							?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Retry Wait Time:', 'connect-crm-realstate' ); ?></strong>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: timeout retry, 2: rate limit retry */
									__( '%1$s (timeout) / %2$s (rate limit)', 'connect-crm-realstate' ),
									$info['retry_timeout'],
									$info['retry_rate_limit']
								)
							);
							?>
						</li>
					</ul>
					<p style="margin: 10px 0 0 0; font-size: 0.9em; color: #646970;">
						<em><?php esc_html_e( 'The system will automatically retry failed requests with intelligent wait times based on the error type.', 'connect-crm-realstate' ); ?></em>
					</p>
				</div>
				<?php
			}
			?>
		</div>

		<script type="text/javascript">
		function loadImportStats() {
			const btn = document.getElementById('refresh_stats');
			const cards = document.querySelectorAll('.ccrmre-stat-card');
			
			// Don't load if button is disabled (credentials not valid).
			if (btn.disabled) {
				return;
			}
			
			btn.disabled = true;
			cards.forEach(card => card.classList.add('loading'));

			jQuery.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'get_import_stats',
					security: '<?php echo esc_js( wp_create_nonce( 'ccrmre_import_nonce' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						document.getElementById('stat-available-count').textContent = response.data.available_count.toLocaleString();
						document.getElementById('stat-api-count').textContent = response.data.api_count.toLocaleString();
						document.getElementById('stat-wp-count').textContent = response.data.wp_count.toLocaleString();
						document.getElementById('stat-import-count').textContent = response.data.import_count.toLocaleString();
						document.getElementById('stat-new-count').textContent = response.data.new_count.toLocaleString();
						document.getElementById('stat-outdated-count').textContent = response.data.outdated_count.toLocaleString();
						document.getElementById('stat-delete-count').textContent = response.data.delete_count.toLocaleString();
					} else {
						// Show error in UI instead of alert.
						console.error('Stats error:', response.data.message);
						// Optionally show a notice in the page.
						showStatsError(response.data.message || 'Unknown error');
					}
				},
				error: function(xhr, status, error) {
					// Log error but don't show alert.
					console.error('AJAX error loading statistics:', status, error);
					showStatsError('<?php echo esc_js( __( 'Error loading statistics', 'connect-crm-realstate' ) ); ?>');
				},
				complete: function() {
					btn.disabled = false;
					cards.forEach(card => card.classList.remove('loading'));
				}
			});
		}

		function showStatsError(message) {
			// Remove any existing error notice.
			const existingNotice = document.querySelector('.ccrmre-stats-error');
			if (existingNotice) {
				existingNotice.remove();
			}

			// Create error notice.
			const notice = document.createElement('div');
			notice.className = 'notice notice-error ccrmre-stats-error';
			notice.style.marginTop = '10px';
			notice.innerHTML = '<p><strong><?php echo esc_js( __( 'Statistics Error:', 'connect-crm-realstate' ) ); ?></strong> ' + message + '</p>';

			// Insert after the stats cards.
			const statsContainer = document.querySelector('.ccrmre-import-stats');
			if (statsContainer) {
				statsContainer.after(notice);
			}
		}

		// Load stats on page load only if credentials are valid
		jQuery(document).ready(function() {
			<?php if ( ! $buttons_disabled ) : ?>
			loadImportStats();
			<?php endif; ?>
		});
		</script>
		<?php
	}

	/**
	 * Info for neo automate section.
	 *
	 * @return void
	 */
	public function admin_section_settings_info() {
		esc_html_e( 'Put the connection API key settings in order to connect external data.', 'connect-crm-realstate' );
	}

	/**
	 * Info for merge fields section.
	 *
	 * @return void
	 */
	public function admin_section_settings_info_merge() {
		echo '<p>';
		esc_html_e( 'Map CRM fields to WordPress custom fields. Select an existing field or type a new field name to create it.', 'connect-crm-realstate' );
		echo '</p>';
		echo '<p>';
		esc_html_e( 'Fields marked with (Custom) are saved values that will be created automatically when properties are imported.', 'connect-crm-realstate' );
		echo '</p>';
	}

	/**
	 * Merge fields callback
	 *
	 * @return void
	 */
	public function merge_fields_callback() {
		$crm_type      = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';
		$post_type     = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		$custom_fields = $this->get_all_custom_fields( $post_type );

		// Validate API credentials before making request.
		$credentials_valid = $this->validate_api_credentials( $crm_type );
		if ( ! $credentials_valid['valid'] ) {
			echo '<div class="error notice"><p>' . esc_html( $credentials_valid['message'] ) . '</p></div>';
			return;
		}

		// Get Options API.
		$properties_fields = API::get_properties_fields( $crm_type );

		if ( 'error' === strtolower( $properties_fields['status'] ) ) {
			$message = ! empty( $properties_fields['message'] )
				? $properties_fields['message']
				: __( 'Unknown error', 'connect-crm-realstate' );
			echo '<div class="error notice"><p>' . esc_html( $message ) . '</p></div>';
			return;
		}

		// Auto-map button.
		echo '<button type="button" id="ccrmre-auto-map-btn" class="button button-secondary" style="margin-bottom: 15px;">';
		echo '<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span> ';
		esc_html_e( 'Auto-Map All Fields', 'connect-crm-realstate' );
		echo '</button>';

		echo '<div id="ccrmre-merge-container">';
		echo '<table class="form-table iip-table-merge-variables">';
		echo '<thead>';
		echo '<tr valign="top">';
		echo '<th scope="col"><strong>' . esc_html__( 'CRM Fields', 'connect-crm-realstate' ) . '</strong></th>';
		echo '<th scope="col"><strong>' . esc_html__( 'Sample Data', 'connect-crm-realstate' ) . '</strong></th>';
		echo '<th scope="col"><strong>' . esc_html__( 'WordPress Fields', 'connect-crm-realstate' ) . '</strong></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$value = '';
		foreach ( $properties_fields['data'] as $property_field ) {
			$value  = isset( $this->settings_fields[ $property_field['name'] ] ) ? $this->settings_fields[ $property_field['name'] ] : '';
			$sample = isset( $property_field['sample'] ) ? $property_field['sample'] : '';

			echo '<tr>';
			echo '<td class="ccrmre-label">' . esc_html( $property_field['label'] );
			echo '<br><small class="description">' . esc_attr( $property_field['name'] ) . '</small></td>';

			// Sample data column — read-only, informational.
			echo '<td class="ccrmre-sample">';
			if ( '' !== $sample ) {
				echo '<span class="ccrmre-sample-value" title="' . esc_attr( $sample ) . '">' . esc_html( $sample ) . '</span>';
			} else {
				echo '<span class="ccrmre-sample-empty">—</span>';
			}
			echo '</td>';

			echo '<td><select name="conncrmreal_merge_fields[' . esc_attr( $property_field['name'] ) . ']" class="ccrmre-select2-field" style="width: 100%;">';
			echo '<option value=""';
			selected( $value, '' );
			echo '>' . esc_html__( '-- Select WordPress Field --', 'connect-crm-realstate' ) . '</option>';

			// Add saved value first if it doesn't exist in custom_fields.
			if ( ! empty( $value ) && ! in_array( $value, $custom_fields, true ) ) {
				echo '<option value="' . esc_attr( $value ) . '" selected="selected">';
				echo esc_html( $value ) . ' ' . esc_html__( '(Custom)', 'connect-crm-realstate' );
				echo '</option>';
			}

			// Add all existing custom fields.
			foreach ( $custom_fields as $meta_key ) {
				echo '<option value="' . esc_attr( $meta_key ) . '"';
				selected( $value, $meta_key );
				echo '>' . esc_html( $meta_key ) . '</option>';
			}
			echo '</select></td>';

			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}


	/**
	 * Sanitize fields before saves in DB
	 *
	 * @param array $input Input fields.
	 * @return array
	 */
	public function sanitize_fields_settings_merge( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitary_values = array();

		foreach ( $input as $key => $value ) {
			// Skip empty values.
			if ( empty( $value ) || ! is_string( $value ) ) {
				continue;
			}

			// Sanitize the CRM field key.
			$sanitized_key = sanitize_text_field( $key );

			// Sanitize the WordPress field name.
			// Allow lowercase letters, numbers, and underscores only.
			$sanitized_value = strtolower( trim( $value ) );
			$sanitized_value = preg_replace( '/[^a-z0-9_]/', '_', $sanitized_value );
			$sanitized_value = preg_replace( '/_+/', '_', $sanitized_value ); // Remove duplicate underscores.
			$sanitized_value = trim( $sanitized_value, '_' ); // Remove leading/trailing underscores.

			// Only save if we have a valid value after sanitization.
			if ( ! empty( $sanitized_value ) && ! empty( $sanitized_key ) ) {
				$sanitary_values[ $sanitized_key ] = $sanitized_value;
			}
		}

		// Log for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'CCRMRE Merge Fields Saved: ' . print_r( $sanitary_values, true ) );
		}

		return $sanitary_values;
	}

	/**
	 * Validate API credentials are configured and working
	 *
	 * Uses a transient cache to avoid checking on every request.
	 * Cache duration: 1 day (DAY_IN_SECONDS)
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos).
	 * @param bool   $force_check Force validation even if cache exists.
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	private function validate_api_credentials( $crm_type, $force_check = false ) {
		// Check transient cache first (unless forced).
		if ( ! $force_check ) {
			$transient_key = 'ccrmre_api_valid_' . $crm_type;
			$cached_result = get_transient( $transient_key );

			if ( false !== $cached_result ) {
				return $cached_result;
			}
		}

		$settings = get_option( 'conncrmreal_settings', array() );
		$result   = array(
			'valid'   => false,
			'message' => '',
		);

		// Validate credentials are configured.
		if ( 'anaconda' === $crm_type ) {
			$apipassword = isset( $settings['apipassword'] ) ? trim( $settings['apipassword'] ) : '';

			if ( empty( $apipassword ) ) {
				$result['message'] = __( 'API password is not configured. Please configure your Anaconda API credentials in the Connection tab.', 'connect-crm-realstate' );
				$this->cache_api_validation( $crm_type, $result );
				return $result;
			}
		} elseif ( 'inmovilla' === $crm_type || 'inmovilla_procesos' === $crm_type ) {
			$apiuser     = isset( $settings['apiuser'] ) ? trim( $settings['apiuser'] ) : '';
			$apipassword = isset( $settings['apipassword'] ) ? trim( $settings['apipassword'] ) : '';

			if ( empty( $apiuser ) || empty( $apipassword ) ) {
				$result['message'] = __( 'API credentials are not configured. Please configure your Inmovilla API user and password in the Connection tab.', 'connect-crm-realstate' );
				$this->cache_api_validation( $crm_type, $result );
				return $result;
			}
		}

		// Test credentials with a simple API call.
		$test_result = $this->test_api_connection( $crm_type );

		if ( $test_result['valid'] ) {
			$result['valid']   = true;
			$result['message'] = '';
		} else {
			$result['message'] = $test_result['message'];
		}

		// Cache result for 1 day.
		$this->cache_api_validation( $crm_type, $result );

		return $result;
	}

	/**
	 * Test API connection with a lightweight request
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos).
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	private function test_api_connection( $crm_type ) {
		// Make a lightweight API call to test credentials.
		if ( 'anaconda' === $crm_type ) {
			$result = API::request_anaconda( 'properties?page=1&per_page=1' );
		} elseif ( 'inmovilla' === $crm_type ) {
			$result = API::request_inmovilla( 'lista', 1, 1 );
		} elseif ( 'inmovilla_procesos' === $crm_type ) {
			$result = API::request_inmovilla_procesos( 'Procesos', array( 'pag' => 1 ) );
		} else {
			return array(
				'valid'   => false,
				'message' => __( 'Unknown CRM type', 'connect-crm-realstate' ),
			);
		}

		if ( 'ok' === $result['status'] ) {
			return array(
				'valid'   => true,
				'message' => '',
			);
		}

		// Extract error message.
		$error_message = isset( $result['message'] ) ? $result['message'] : __( 'API connection test failed', 'connect-crm-realstate' );

		return array(
			'valid'   => false,
			'message' => $error_message,
		);
	}

	/**
	 * Cache API validation result
	 *
	 * @param string $crm_type CRM type.
	 * @param array  $result Validation result.
	 * @return void
	 */
	private function cache_api_validation( $crm_type, $result ) {
		$transient_key = 'ccrmre_api_valid_' . $crm_type;
		$cache_time    = $result['valid'] ? DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
		set_transient( $transient_key, $result, $cache_time );
	}

	/**
	 * Invalidate API credentials cache
	 *
	 * Should be called when credentials are updated.
	 *
	 * @param string $crm_type CRM type (optional, if empty clears all).
	 * @return void
	 */
	public static function invalidate_api_cache( $crm_type = '' ) {
		if ( ! empty( $crm_type ) ) {
			delete_transient( 'ccrmre_api_valid_' . $crm_type );
		} else {
			// Clear all CRM types.
			delete_transient( 'ccrmre_api_valid_anaconda' );
			delete_transient( 'ccrmre_api_valid_inmovilla' );
			delete_transient( 'ccrmre_api_valid_inmovilla_procesos' );
		}
	}

	/**
	 * Return all meta keys from WordPress database in post type
	 *
	 * @param string $post_type Post type.
	 * @return array Array of metakeys.
	 */
	private function get_all_custom_fields( $post_type ) {
		global $wpdb;
		$meta_keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT( {$wpdb->postmeta}.meta_key )
				FROM {$wpdb->posts}
				LEFT JOIN {$wpdb->postmeta}
					ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
					WHERE {$wpdb->posts}.post_type = %s ORDER BY {$wpdb->postmeta}.meta_key",
				$post_type
			)
		);

		return $meta_keys;
	}

	/**
	 * AJAX handler for auto-mapping fields
	 *
	 * @return void
	 */
	public function ajax_auto_map_fields() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_auto_map_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'connect-crm-realstate' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'connect-crm-realstate' ) ) );
		}

		$settings = get_option( 'conncrmreal_settings' );
		$crm_type = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		// Validate API credentials before making request.
		$credentials_valid = $this->validate_api_credentials( $crm_type );
		if ( ! $credentials_valid['valid'] ) {
			wp_send_json_error( array( 'message' => $credentials_valid['message'] ) );
		}

		// Get CRM fields.
		$properties_fields = API::get_properties_fields( $crm_type );

		if ( 'error' === $properties_fields['status'] ) {
			wp_send_json_error( array( 'message' => $properties_fields['data'] ) );
		}

		// Get current mappings.
		$current_mappings = get_option( 'conncrmreal_merge_fields', array() );
		$new_mappings     = array();
		$auto_mapped      = 0;

		// Generate WordPress field names for each CRM field.
		foreach ( $properties_fields['data'] as $property_field ) {
			$crm_field_name = $property_field['name'];

			// Skip if already mapped.
			if ( isset( $current_mappings[ $crm_field_name ] ) && ! empty( $current_mappings[ $crm_field_name ] ) ) {
				$new_mappings[ $crm_field_name ] = $current_mappings[ $crm_field_name ];
				continue;
			}

			// Generate WordPress field name from CRM field name.
			$wp_field_name = $this->generate_wp_field_name( $crm_field_name );

			$new_mappings[ $crm_field_name ] = $wp_field_name;
			++$auto_mapped;
		}

		// Save the mappings.
		update_option( 'conncrmreal_merge_fields', $new_mappings );

		wp_send_json_success(
			array(
				'message'     => sprintf(
					/* translators: %d: number of fields */
					_n( '%d field auto-mapped successfully!', '%d fields auto-mapped successfully!', $auto_mapped, 'connect-crm-realstate' ),
					$auto_mapped
				),
				'mappings'    => $new_mappings,
				'auto_mapped' => $auto_mapped,
			)
		);
	}

	/**
	 * Generate WordPress field name from CRM field name
	 *
	 * @param string $crm_field_name CRM field name.
	 * @return string WordPress field name.
	 */
	private function generate_wp_field_name( $crm_field_name ) {
		// Start with the CRM field name.
		$wp_field_name = $crm_field_name;

		// Convert to lowercase.
		$wp_field_name = strtolower( $wp_field_name );

		// Replace special characters with underscores.
		$wp_field_name = preg_replace( '/[^a-z0-9_]/', '_', $wp_field_name );

		// Remove duplicate underscores.
		$wp_field_name = preg_replace( '/_+/', '_', $wp_field_name );

		// Remove leading/trailing underscores.
		$wp_field_name = trim( $wp_field_name, '_' );

		// Add prefix to avoid conflicts.
		$wp_field_name = 'crm_' . $wp_field_name;

		return $wp_field_name;
	}

	/**
	 * AJAX handler to load log file content
	 *
	 * @return void
	 */
	public function ajax_load_log_content() {
		check_ajax_referer( 'ccrmre_manual_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'connect-crm-realstate' ) ) );
		}

		$filename = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : '';

		if ( empty( $filename ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid filename', 'connect-crm-realstate' ) ) );
		}

		$uploads_dir = wp_upload_dir();
		$file_path   = $uploads_dir['basedir'] . '/ccrmre_logs/' . $filename;

		// Security check: Ensure the file is within the logs directory.
		$real_path = realpath( $file_path );
		$logs_dir  = realpath( $uploads_dir['basedir'] . '/ccrmre_logs/' );

		if ( false === $real_path || false === $logs_dir || 0 !== strpos( $real_path, $logs_dir ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file path', 'connect-crm-realstate' ) ) );
		}

		if ( ! file_exists( $file_path ) ) {
			wp_send_json_error( array( 'message' => __( 'Log file not found', 'connect-crm-realstate' ) ) );
		}

		$content = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			wp_send_json_error( array( 'message' => __( 'Error reading log file', 'connect-crm-realstate' ) ) );
		}

		// Escape and format the content.
		$content = esc_html( $content );
		$content = nl2br( $content );

		wp_send_json_success( array( 'content' => $content ) );
	}
}

