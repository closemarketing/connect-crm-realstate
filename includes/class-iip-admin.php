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

		echo '<div class="wrap bialty-containter">';
		echo '<h2><span class="dashicons dashicons-media-text" style="margin-top: 6px; font-size: 24px;"></span> ' . esc_html__( 'Connect CRM Real State Settings', 'connect-crm-realstate' ) . '</h2>';
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

			// Log.
			echo '<a href="' . esc_url( '?page=iip-options&tab=iip-log' ) . '" class="nav-tab ';
			echo ( 'iip-log' === $active_tab ? 'nav-tab-active' : '' );
			echo '">' . esc_html__( 'Log', 'connect-crm-realstate' ) . '</a>';
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

			if ( 'iip-log' === $active_tab ) {
				$this->plugin_log_page();
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
			'show_gallery',
			'show_property_info',
		);

		foreach ( $field_values as $field_value ) {
			if ( isset( $input[ $field_value ] ) ) {
				$sanitary_values[ $field_value ] = sanitize_text_field( $input[ $field_value ] );
			}
		}

		$sanitary_values['api_pagination'] = 'anaconda' === $input['type'] ? 200 : 100;

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
		?>
		<div class="connect-realstate-manual-action">
			<h2><?php esc_html_e( 'Import Properties', 'connect-crm-realstate' ); ?></h2>
			<p><?php esc_html_e( 'After you fillup the settings, use the button below to import the properties. The importing process may take a while and you need to keep this page open to complete it.', 'connect-crm-realstate' ); ?><br/></p>
			
			<!-- Import Statistics -->
			<div class="ccrmre-import-stats">
				<div class="ccrmre-stat-card">
					<div class="ccrmre-stat-icon ccrmre-icon-api">
						<span class="dashicons dashicons-cloud"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-api-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'Properties in API', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $crm_type ) ) ); ?></div>
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
						<div class="ccrmre-stat-label"><?php esc_html_e( 'To Import', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel"><?php esc_html_e( 'New properties', 'connect-crm-realstate' ); ?></div>
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

			<div class="import-button-wrapper">
				<button type="button" id="manual_import" name="manual_import" class="button button-large button-primary" onclick="syncManualProperties(this, 0, <?php echo (int) $pagination; ?>);" >
					<?php esc_html_e( 'Start Import', 'connect-crm-realstate' ); ?>
				</button>
				<button type="button" id="refresh_stats" name="refresh_stats" class="button button-large" onclick="loadImportStats();">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh Statistics', 'connect-crm-realstate' ); ?>
				</button>
				<span class="spinner"></span>
			</div>
			<fieldset id="logwrapper"><legend><?php esc_html_e( 'Log', 'connect-crm-realstate' ); ?></legend><div id="loglist"></div></fieldset>
		</div>
		<style>
			.ccrmre-import-stats {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 20px;
				margin: 20px 0 30px;
			}
			.ccrmre-stat-card {
				background: white;
				border: 1px solid #c3c4c7;
				border-radius: 8px;
				padding: 20px;
				display: flex;
				align-items: center;
				gap: 15px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.08);
				transition: transform 0.2s, box-shadow 0.2s;
			}
			.ccrmre-stat-card:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 8px rgba(0,0,0,0.12);
			}
			.ccrmre-stat-icon {
				width: 50px;
				height: 50px;
				border-radius: 8px;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
			}
			.ccrmre-icon-api {
				background: #e8f4fd;
				color: #0073aa;
			}
			.ccrmre-icon-wp {
				background: #f0f0f1;
				color: #2c3338;
			}
			.ccrmre-icon-import {
				background: #ecf7ed;
				color: #00a32a;
			}
			.ccrmre-icon-delete {
				background: #fcf0f1;
				color: #d63638;
			}
			.ccrmre-stat-icon .dashicons {
				font-size: 28px;
				width: 28px;
				height: 28px;
			}
			.ccrmre-stat-content {
				flex: 1;
			}
			.ccrmre-stat-value {
				font-size: 32px;
				font-weight: 700;
				line-height: 1.2;
				color: #1d2327;
			}
			.ccrmre-stat-label {
				font-size: 13px;
				font-weight: 600;
				color: #50575e;
				margin-top: 5px;
			}
			.ccrmre-stat-sublabel {
				font-size: 12px;
				color: #787c82;
				margin-top: 2px;
			}
			.ccrmre-stat-card.loading .ccrmre-stat-value {
				opacity: 0.5;
			}

			.connect-realstate-manual-action .import-button-wrapper {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 15px;
			}
			.connect-realstate-manual-action .spinner {
				float: none;
				margin: 0;
				display: none;
			}
			.connect-realstate-manual-action .spinner.is-active {
				display: block;
				visibility: visible;
			}
			.connect-realstate-manual-action #manual_import:disabled,
			.connect-realstate-manual-action #refresh_stats:disabled {
				opacity: 0.6;
				cursor: not-allowed;
			}
			.connect-realstate-manual-action #refresh_stats .dashicons {
				margin-right: 5px;
			}
			.connect-realstate-manual-action #logwrapper {
				margin-top: 20px;
				padding: 15px;
				background: #f0f0f1;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
			}
			.connect-realstate-manual-action #loglist {
				max-height: 400px;
				overflow-y: auto;
				background: white;
				padding: 10px;
				border-radius: 3px;
			}
			.connect-realstate-manual-action #loglist p {
				margin: 5px 0;
				padding: 5px 10px;
				border-left: 3px solid #0073aa;
			}
			.connect-realstate-manual-action #loglist p.odd {
				background: #f9f9f9;
			}
			.connect-realstate-manual-action #loglist p.even {
				background: white;
			}
			.connect-realstate-manual-action #loglist p.error {
				border-left-color: #d63638;
				background: #fcf0f1;
			}
		</style>
		<script>
		function loadImportStats() {
			const btn = document.getElementById('refresh_stats');
			const cards = document.querySelectorAll('.ccrmre-stat-card');
			
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
						document.getElementById('stat-api-count').textContent = response.data.api_count.toLocaleString();
						document.getElementById('stat-wp-count').textContent = response.data.wp_count.toLocaleString();
						document.getElementById('stat-import-count').textContent = response.data.import_count.toLocaleString();
						document.getElementById('stat-delete-count').textContent = response.data.delete_count.toLocaleString();
					} else {
						alert('Error: ' + (response.data.message || 'Unknown error'));
					}
				},
				error: function() {
					alert('<?php echo esc_js( __( 'Error loading statistics', 'connect-crm-realstate' ) ); ?>');
				},
				complete: function() {
					btn.disabled = false;
					cards.forEach(card => card.classList.remove('loading'));
				}
			});
		}

		// Load stats on page load
		jQuery(document).ready(function() {
			loadImportStats();
		});
		</script>
		<?php
	}

	/**
	 * Log Page
	 *
	 * @return void
	 */
	public function plugin_log_page() {
		?>
		<div class="connect-realstate-log">
			<h2><?php esc_html_e( 'Latest cron logs', 'connect-crm-realstate' ); ?></h2>
			<p><?php esc_html_e( 'After you fillup the API settings, use the button below to import the products. The importing process may take a while and you need to keep this page open to complete it.', 'connect-crm-realstate' ); ?>
			</p>

			<fieldset id="logwrapper">
				<legend><?php esc_html_e( 'Log', 'connect-crm-realstate' ); ?></legend>
				<div id="loglist">
					<?php
					$uploads_dir = wp_upload_dir();
					$folder      = $uploads_dir['basedir'] . '/ccrmre_logs/';
					$files       = list_files( $folder, 2 );
					$index       = 0;
					foreach ( $files as $file ) {
						if ( is_file( $file ) ) {
							$filename = basename( $file );
							$class    = ( 0 === $index % 2 ) ? 'even' : 'odd';
							echo '<p class="' . esc_html( $class ) . '">';
							$file_open = fopen( $file, 'r' );
							if ( $file_open ) {
								$line = fgets( $file_open );
								fclose( $file_open );
							} else {
								$line = '';
							}
							echo '<a href="' . esc_url( $uploads_dir['baseurl'] . '/ccrmre_logs/' . $filename ) . '" target="_blank">';
							echo ! empty( esc_html( $line ) ) ? esc_html( $line ) : esc_html( $filename );
							echo '</a>';
							echo '</p>';
							++$index;
						}
					}
					?>
				</div>
			</fieldset>
		</div>
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
		// Get Options .
		$properties_fields = API::get_properties_fields( $crm_type );

		if ( 'error' === $properties_fields['status'] ) {
			echo '<div class="error notice"><p>' . esc_html( $properties_fields['data'] ) . '</p></div>';
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
		echo '<th scope="col"><strong>' . esc_html__( 'WordPress Fields', 'connect-crm-realstate' ) . '</strong></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$value = '';
		foreach ( $properties_fields['data'] as $property_field ) {
			$value = isset( $this->settings_fields[ $property_field['name'] ] ) ? $this->settings_fields[ $property_field['name'] ] : '';
			echo '<tr>';
			echo '<td class="ccrmre-label">' . esc_html( $property_field['label'] );
			echo '<br><small class="description">' . esc_attr( $property_field['name'] ) . '</small></td>';
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
}

