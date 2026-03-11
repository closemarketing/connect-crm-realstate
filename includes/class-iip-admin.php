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
		if ( ! isset( $_GET['settings-updated'] ) || ! isset( $_GET['page'] ) || 'ccrmre_options' !== $_GET['page'] ) {
			return;
		}

		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

		if ( 'iip-merge' === $active_tab ) {
			$merge_fields = get_option( 'ccrmre_merge_fields' );
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

		if ( 'iip-taxonomy' === $active_tab ) {
			$taxonomy_mappings = get_option( 'ccrmre_taxonomy_mappings', array() );
			$count             = is_array( $taxonomy_mappings ) ? count( $taxonomy_mappings ) : 0;

			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Taxonomy mappings saved successfully!', 'connect-crm-realstate' ) . '</strong> ';
			printf(
				/* translators: %d: number of mappings */
				esc_html( _n( '%d taxonomy mapping saved.', '%d taxonomy mappings saved.', $count, 'connect-crm-realstate' ) ),
				(int) $count
			);
			echo '</p>';
			echo '</div>';
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

		add_menu_page(
			__( 'Connect CRM RealState', 'connect-crm-realstate' ),
			__( 'Connect CRM RealState', 'connect-crm-realstate' ),
			'manage_options',
			'ccrmre_options',
			array( $this, 'plugin_options_page' ),
			'dashicons-rest-api'
		);

		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_ccrmre_options' !== $hook ) {
			return;
		}

		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

		// Enqueue import styles and stats script on manual import tab.
		if ( 'iip-import' === $active_tab ) {
			wp_enqueue_style(
				'ccrmre-admin-import',
				CCRMRE_PLUGIN_URL . 'assets/css/admin-import.css',
				array(),
				CCRMRE_VERSION
			);
			wp_enqueue_script(
				'ccrmre-admin-import-stats',
				CCRMRE_PLUGIN_URL . 'assets/js/admin-import-stats.js',
				array( 'jquery' ),
				CCRMRE_VERSION,
				true
			);
			wp_localize_script(
				'ccrmre-admin-import-stats',
				'ccrmreImportStats',
				array(
					'nonce'                  => wp_create_nonce( 'ccrmre_import_nonce' ),
					'errorLoadingStatistics' => __( 'Error loading statistics', 'connect-crm-realstate' ),
					'statisticsErrorLabel'   => __( 'Statistics Error:', 'connect-crm-realstate' ),
				)
			);

			// Import tabs script (no page reload).
			wp_enqueue_script(
				'ccrmre-import-tabs',
				CCRMRE_PLUGIN_URL . 'assets/js/import-tabs.js',
				array(),
				CCRMRE_VERSION,
				true
			);
		}

		// Enqueue settings scripts on settings tab.
		if ( 'iip-settings' === $active_tab ) {
			wp_enqueue_script(
				'ccrmre-settings',
				plugin_dir_url( __FILE__ ) . 'assets/iip-settings.js',
				array( 'jquery' ),
				CCRMRE_VERSION,
				true
			);
		}

		// Enqueue taxonomy mapping scripts on taxonomy tab.
		if ( 'iip-taxonomy' === $active_tab ) {
			wp_enqueue_style(
				'ccrmre-taxonomy-mapping',
				CCRMRE_PLUGIN_URL . 'includes/assets/iip-taxonomy-mapping.css',
				array(),
				CCRMRE_VERSION
			);
			wp_enqueue_script(
				'ccrmre-taxonomy-mapping',
				CCRMRE_PLUGIN_URL . 'includes/assets/iip-taxonomy-mapping.js',
				array(),
				CCRMRE_VERSION,
				true
			);
			wp_localize_script(
				'ccrmre-taxonomy-mapping',
				'ccrmreTaxonomyMapping',
				array(
					'selectCrmField'  => __( '-- Select CRM Field --', 'connect-crm-realstate' ),
					'selectTaxonomy'  => __( '-- Select Taxonomy --', 'connect-crm-realstate' ),
					'confirmRemove'   => __( 'Remove this mapping row?', 'connect-crm-realstate' ),
					'noFieldSelected' => __( 'Please select both a CRM field and a taxonomy for each row.', 'connect-crm-realstate' ),
				)
			);
		}

		// Enqueue select2 and merge fields scripts only on merge tab.
		if ( 'iip-merge' === $active_tab ) {
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

			$locale = get_locale();
			if ( 0 === strpos( $locale, 'es_' ) ) {
				wp_enqueue_script(
					'ccrmre-select2-i18n',
					CCRMRE_PLUGIN_URL . 'vendor/select2/select2/dist/js/i18n/es.js',
					array( 'ccrmre-select2' ),
					CCRMRE_VERSION,
					true
				);
			}

			wp_enqueue_style(
				'ccrmre-merge-fields',
				plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.css',
				array(),
				CCRMRE_VERSION
			);

			wp_enqueue_script(
				'ccrmre-merge-fields',
				plugin_dir_url( __FILE__ ) . 'assets/iip-merge-fields.js',
				array( 'jquery', 'ccrmre-select2' ),
				CCRMRE_VERSION,
				true
			);

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
					'confirmClearAll'   => __( 'Clear all WordPress field selections? You will need to save the form to apply changes.', 'connect-crm-realstate' ),
					'clearAllDone'      => __( 'All selections cleared.', 'connect-crm-realstate' ),
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
		$this->settings        = get_option( 'ccrmre_settings' );
		$this->settings_fields = get_option( 'ccrmre_merge_fields' );

		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

		echo '<div class="wrap bialty-containter">';
		echo '<h2><span class="dashicons dashicons-media-text" style="margin-top: 6px; font-size: 24px;"></span> ' . esc_html__( 'Connect CRM RealState', 'connect-crm-realstate' ) . '</h2>';
		echo '<h2 class="nav-tab-wrapper">';

		// Import Properties tab.
		echo '<a href="' . esc_url( '?page=ccrmre_options&tab=iip-import' ) . '" class="nav-tab ';
		echo ( 'iip-import' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Import Properties', 'connect-crm-realstate' ) . '</a>';

		// Settings tab.
		echo '<a href="' . esc_url( '?page=ccrmre_options&tab=iip-settings' ) . '" class="nav-tab ';
		echo ( 'iip-settings' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Settings', 'connect-crm-realstate' ) . '</a>';

		// Merge variables tab.
		echo '<a href="' . esc_url( '?page=ccrmre_options&tab=iip-merge' ) . '" class="nav-tab ';
		echo ( 'iip-merge' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Merge variables', 'connect-crm-realstate' ) . '</a>';

		// Taxonomy mapping tab.
		echo '<a href="' . esc_url( '?page=ccrmre_options&tab=iip-taxonomy' ) . '" class="nav-tab ';
		echo ( 'iip-taxonomy' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Taxonomy Mapping', 'connect-crm-realstate' ) . '</a>';

		/**
		 * Allow PRO or add-ons to inject extra admin tabs.
		 *
		 * @param string $active_tab Currently active tab slug.
		 */
		do_action( 'ccrmre_admin_tabs', $active_tab );

		echo '</h2>';

		if ( 'iip-import' === $active_tab ) {
			$this->plugin_import_page();
		}

		if ( 'iip-settings' === $active_tab ) {
			echo '<form method="post" action="options.php">';
			settings_fields( 'ccrmre_admin_settings' );
			do_settings_sections( 'ccrmre_settings' );
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
				<?php settings_fields( 'ccrmre_merge_group' ); ?>
				<?php do_settings_sections( 'ccrmre_merge_fields' ); ?>
				<?php submit_button(); ?>
			</form>
			<?php
		}

		if ( 'iip-taxonomy' === $active_tab ) {
			?>
			<h1><?php esc_html_e( 'Taxonomy Mapping', 'connect-crm-realstate' ); ?></h1>
			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'Map CRM fields to WordPress taxonomies. During synchronization, the value of each CRM field will be used to assign taxonomy terms to the imported properties.', 'connect-crm-realstate' ); ?>
				</p>
			</div>
			<form method="post" action="options.php" id="ccrmre-taxonomy-form">
				<?php settings_fields( 'ccrmre_taxonomy_group' ); ?>
				<?php $this->taxonomy_mapping_callback(); ?>
				<?php submit_button( esc_html__( 'Save Taxonomy Mappings', 'connect-crm-realstate' ) ); ?>
			</form>
			<?php
		}

		/**
		 * Allow PRO or add-ons to render custom tab content.
		 *
		 * @param string $active_tab Currently active tab slug.
		 */
		do_action( 'ccrmre_admin_tab_content', $active_tab );

		echo '</div>';
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_plugin_settings() {
		$this->settings = get_option( 'ccrmre_settings' );

		register_setting(
			'ccrmre_admin_settings',
			'ccrmre_settings',
			array( $this, 'sanitize_fields_settings' )
		);

		add_settings_section(
			'ccrmre_admin_settings',
			__( 'Settings for Integration with CRM Real State', 'connect-crm-realstate' ),
			array( $this, 'admin_section_settings_info' ),
			'ccrmre_settings'
		);

		add_settings_field(
			'ccrmre_type',
			__( 'Type', 'connect-crm-realstate' ),
			array( $this, 'type_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		add_settings_field(
			'ccrmre_apipassword',
			__( 'API Password / Token', 'connect-crm-realstate' ),
			array( $this, 'apipassword_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		// Inmovilla specific fields.
		if ( isset( $this->settings['type'] ) && 'inmovilla' === $this->settings['type'] ) {
			add_settings_field(
				'ccrmre_numagencia',
				__( 'Agency Number', 'connect-crm-realstate' ),
				array( $this, 'numagencia_callback' ),
				'ccrmre_settings',
				'ccrmre_admin_settings'
			);
		}

		add_settings_field(
			'ccrmre_post_type',
			__( 'Post Type', 'connect-crm-realstate' ),
			array( $this, 'post_type_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		if ( isset( $this->settings['post_type'] ) && 'property' === $this->settings['post_type'] ) {
			add_settings_field(
				'ccrmre_post_type_slug',
				__( 'Post Type SLUG', 'connect-crm-realstate' ),
				array( $this, 'post_type_slug_callback' ),
				'ccrmre_settings',
				'ccrmre_admin_settings'
			);
		}

		add_settings_field(
			'ccrmre_sold_action',
			__( 'Action for Sold/Unavailable Properties', 'connect-crm-realstate' ),
			array( $this, 'sold_action_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		/**
		 * Allow PRO or add-ons to register extra settings fields.
		 *
		 * @param array $settings Current plugin settings.
		 */
		do_action( 'ccrmre_register_settings', $this->settings );

		add_settings_field(
			'ccrmre_download_images',
			__( 'Download Images Locally', 'connect-crm-realstate' ),
			array( $this, 'download_images_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		add_settings_field(
			'ccrmre_show_gallery',
			__( 'Auto Display Photo Gallery', 'connect-crm-realstate' ),
			array( $this, 'show_gallery_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		add_settings_field(
			'ccrmre_show_property_info',
			__( 'Auto Display Property Info Box', 'connect-crm-realstate' ),
			array( $this, 'show_property_info_callback' ),
			'ccrmre_settings',
			'ccrmre_admin_settings'
		);

		// Merge fields settings.
		register_setting(
			'ccrmre_merge_group',
			'ccrmre_merge_fields',
			array( $this, 'sanitize_fields_settings_merge' )
		);

		add_settings_section(
			'ccrmre_merge_group',
			__( 'Settings for Integration with CRM Real State', 'connect-crm-realstate' ),
			array( $this, 'admin_section_settings_info_merge' ),
			'ccrmre_merge_fields'
		);

		add_settings_field(
			'ccrmre_merge_fields',
			__( 'Merge Fields', 'connect-crm-realstate' ),
			array( $this, 'merge_fields_callback' ),
			'ccrmre_merge_fields',
			'ccrmre_merge_group'
		);

		// Taxonomy mapping settings.
		register_setting(
			'ccrmre_taxonomy_group',
			'ccrmre_taxonomy_mappings',
			array( $this, 'sanitize_taxonomy_mappings' )
		);
	}

	/**
	 * Sanitize fields before saves in DB
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
			'post_type',
			'post_type_slug',
			'sold_action',
			'download_images',
			'show_gallery',
			'show_property_info',
		);

		/**
		 * Allow PRO or add-ons to add extra sanitize field keys.
		 *
		 * @param array $field_values Array of field keys to sanitize.
		 */
		$field_values = apply_filters( 'ccrmre_sanitize_settings_fields', $field_values );

		foreach ( $field_values as $field_value ) {
			if ( isset( $input[ $field_value ] ) ) {
				$sanitary_values[ $field_value ] = sanitize_text_field( $input[ $field_value ] );
			}
		}

		$sanitary_values['api_pagination'] = 'anaconda' === $input['type'] ? 200 : 100;

		// Invalidate API cache when credentials are updated.
		self::invalidate_api_cache();

		add_settings_error(
			'ccrmre_settings',
			'settings_saved',
			__( 'Settings saved successfully.', 'connect-crm-realstate' ),
			'success'
		);

		return $sanitary_values;
	}

	/**
	 * Show title callback
	 *
	 * @return void
	 */
	public function type_callback() {
		$type_option = isset( $this->settings['type'] ) ? $this->settings['type'] : 'show';
		if ( 'inmovilla' === $type_option ) {
			echo '<input type="hidden" name="ccrmre_settings[type]" value="inmovilla">';
		}
		?>
		<select name="ccrmre_settings[type]" id="type">
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
			'<input class="regular-text" type="password" name="ccrmre_settings[apipassword]" id="apipassword" value="%s"><br><small>%s</small>',
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
			'<input class="regular-text" type="text" name="ccrmre_settings[numagencia]" id="numagencia" value="%s"><br><small>%s</small>',
			isset( $this->settings['numagencia'] ) ? esc_attr( $this->settings['numagencia'] ) : '',
			esc_html__( 'API Username', 'connect-crm-realstate' )
		);
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
		<select name="ccrmre_settings[post_type]" id="post_type">
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
			'<input class="regular-text" type="text" name="ccrmre_settings[post_type_slug]" id="post_type_slug" value="%s">',
			isset( $this->settings['post_type_slug'] ) ? esc_attr( $this->settings['post_type_slug'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Slug for the post type. If you change this, you need to save the permalinks again.', 'connect-crm-realstate' )
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
		<select name="ccrmre_settings[sold_action]" id="sold_action">
			<option value="draft" <?php selected( $sold_action, 'draft' ); ?>><?php esc_html_e( 'Unpublish (Set to Draft)', 'connect-crm-realstate' ); ?></option>
			<option value="keep" <?php selected( $sold_action, 'keep' ); ?>><?php esc_html_e( 'Keep Published', 'connect-crm-realstate' ); ?></option>
			<option value="trash" <?php selected( $sold_action, 'trash' ); ?>><?php esc_html_e( 'Move to Trash', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
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
		<select name="ccrmre_settings[download_images]" id="download_images">
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
		<select name="ccrmre_settings[ccrmre_show_gallery]" id="show_gallery">
			<option value="no" <?php selected( $show_gallery, 'no' ); ?>><?php esc_html_e( 'No - Use shortcode only', 'connect-crm-realstate' ); ?></option>
			<option value="yes" <?php selected( $show_gallery, 'yes' ); ?>><?php esc_html_e( 'Yes - Auto display after title', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
			'<p class="description">%s <code>[ccrmre_property_gallery]</code></p>',
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
		<select name="ccrmre_settings[show_property_info]" id="show_property_info">
			<option value="no" <?php selected( $show_property_info, 'no' ); ?>><?php esc_html_e( 'No - Use shortcode only', 'connect-crm-realstate' ); ?></option>
			<option value="yes" <?php selected( $show_property_info, 'yes' ); ?>><?php esc_html_e( 'Yes - Auto display after content', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
		printf(
			'<p class="description">%s <code>[ccrmre_property_info]</code></p>',
			esc_html__( 'Enable automatic display of property information box with icons and price, or use the shortcode manually:', 'connect-crm-realstate' )
		);
	}

	/**
	 * Import Page - Manual import only (no cron).
	 *
	 * @return void
	 */
	public function plugin_import_page() {
		$settings   = get_option( 'ccrmre_settings' );
		$crm_type   = isset( $settings['type'] ) ? $settings['type'] : '';
		$pagination = API::get_pagination_size( $crm_type );

		?>
		<div class="connect-realstate-manual-action">
			<h2><?php esc_html_e( 'Import Properties', 'connect-crm-realstate' ); ?></h2>

			<!-- Import Statistics -->
			<div class="ccrmre-import-stats">
				<div class="ccrmre-stat-card">
					<div class="ccrmre-stat-icon ccrmre-icon-api">
						<span class="dashicons dashicons-cloud"></span>
					</div>
					<div class="ccrmre-stat-content">
						<div class="ccrmre-stat-value" id="stat-available-count">--</div>
						<div class="ccrmre-stat-label"><?php esc_html_e( 'Available in API', 'connect-crm-realstate' ); ?></div>
						<div class="ccrmre-stat-sublabel ccrmre-stat-filtered-province-wrap" id="stat-filtered-province-wrap" style="display: none;">
							<?php esc_html_e( 'Filtered by province:', 'connect-crm-realstate' ); ?> <span id="stat-filtered-province-count">0</span>
						</div>
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

		<!-- Import Tabs -->
		<div class="ccrmre-import-tabs-wrapper">
			<?php
			/**
			 * Allow PRO to add automatic sync tab button.
			 */
			do_action( 'ccrmre_import_tabs' );
			?>
			<button type="button" class="ccrmre-import-tab-btn" data-tab="manual">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Manual Import', 'connect-crm-realstate' ); ?>
			</button>
		</div>

		<?php
		/**
		 * Allow PRO to render automatic sync tab content.
		 *
		 * @param array $settings Current plugin settings.
		 */
		do_action( 'ccrmre_import_tab_content', $settings );
		?>

		<!-- Manual Import Section -->
		<div class="ccrmre-import-tab-content" data-tab="manual">
			<?php
			/** Allow PRO to hide the upsell notice when active. */
			if ( apply_filters( 'ccrmre_show_pro_upsell', true ) ) :
				?>
			<div class="notice notice-info inline" style="margin: 15px 0;">
				<p>
					<strong><?php esc_html_e( 'Need automatic sync?', 'connect-crm-realstate' ); ?></strong>
					<?php esc_html_e( 'Upgrade to Connect CRM RealState PRO for automatic background synchronization using cron.', 'connect-crm-realstate' ); ?>
					<a href="https://close.technology/wordpress-plugins/connect-crm-realstate/" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Learn more', 'connect-crm-realstate' ); ?> &rarr;
					</a>
				</p>
			</div>
				<?php
			endif;
			?>

			<div class="import-button-wrapper">
				<select id="import-mode" class="import-mode-select">
					<?php
					$import_mode_options = array(
						'updated' => __( 'Properties to update', 'connect-crm-realstate' ),
						'all'     => __( 'All properties', 'connect-crm-realstate' ),
					);
					$import_mode_options = apply_filters( 'ccrmre_import_mode_options', $import_mode_options );
					foreach ( $import_mode_options as $value => $label ) {
						echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
					}
					?>
				</select>
				<button type="button" id="manual_import" name="manual_import" class="button button-large button-primary" onclick="syncManualProperties(this, 0, <?php echo (int) $pagination; ?>);" >
					<?php esc_html_e( 'Start Import', 'connect-crm-realstate' ); ?>
				</button>
				<button type="button" id="refresh_stats" name="refresh_stats" class="button button-large" onclick="loadImportStats();">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh Statistics', 'connect-crm-realstate' ); ?>
				</button>
				<span class="spinner"></span>
			</div>

			<!-- Import Log -->
			<div class="ccrmre-log-container">
				<fieldset id="logwrapper" style="border: none; padding: 0; margin: 0;">
					<div id="loglist"></div>
				</fieldset>
			</div>

			<?php
			// Show API limitations info.
			$crm_type   = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';
			$api_config = API::get_api_config( $crm_type );

			if ( ! empty( $api_config ) ) {
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
		<?php
	}

	/**
	 * Info for settings section.
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

		$properties_fields = API::get_properties_fields( $crm_type );

		if ( 'error' === strtolower( $properties_fields['status'] ) ) {
			$message = ! empty( $properties_fields['message'] )
				? $properties_fields['message']
				: __( 'Unknown error', 'connect-crm-realstate' );
			echo '<div class="error notice"><p>' . esc_html( $message ) . '</p></div>';
			return;
		}

		echo '<button type="button" id="ccrmre-auto-map-btn" class="button button-secondary" style="margin-bottom: 15px;">';
		echo '<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span> ';
		esc_html_e( 'Auto-Map All Fields', 'connect-crm-realstate' );
		echo '</button>';
		echo ' ';
		echo '<button type="button" id="ccrmre-clear-all-selects-btn" class="button button-secondary" style="margin-bottom: 15px;">';
		echo '<span class="dashicons dashicons-dismiss" style="margin-top: 3px;"></span> ';
		esc_html_e( 'Clear all selects', 'connect-crm-realstate' );
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

			echo '<td class="ccrmre-sample">';
			if ( '' !== $sample ) {
				echo '<span class="ccrmre-sample-value" title="' . esc_attr( $sample ) . '">' . esc_html( $sample ) . '</span>';
			} else {
				echo '<span class="ccrmre-sample-empty">—</span>';
			}
			echo '</td>';

			echo '<td class="ccrmre-wp-field"><select name="ccrmre_merge_fields[' . esc_attr( $property_field['name'] ) . ']" class="ccrmre-select2-field" style="width: 100%;">';
			echo '<option value=""';
			selected( $value, '' );
			echo '>' . esc_html__( '-- Select WordPress Field --', 'connect-crm-realstate' ) . '</option>';

			if ( ! empty( $value ) && ! in_array( $value, $custom_fields, true ) ) {
				echo '<option value="' . esc_attr( $value ) . '" selected="selected">';
				echo esc_html( $value ) . ' ' . esc_html__( '(Custom)', 'connect-crm-realstate' );
				echo '</option>';
			}

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
			if ( empty( $value ) || ! is_string( $value ) ) {
				continue;
			}

			$sanitized_key = sanitize_text_field( $key );

			$sanitized_value = strtolower( trim( $value ) );
			$sanitized_value = preg_replace( '/[^a-z0-9_]/', '_', $sanitized_value );
			$sanitized_value = preg_replace( '/_+/', '_', $sanitized_value );

			if ( ! empty( $sanitized_value ) && ! empty( $sanitized_key ) ) {
				$sanitary_values[ $sanitized_key ] = $sanitized_value;
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'CCRMRE Merge Fields Saved: ' . print_r( $sanitary_values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		return $sanitary_values;
	}

	/**
	 * Invalidate API credentials cache
	 *
	 * @param string $crm_type CRM type (optional, if empty clears all).
	 * @return void
	 */
	public static function invalidate_api_cache( $crm_type = '' ) {
		if ( ! empty( $crm_type ) ) {
			delete_transient( 'ccrmre_api_valid_' . $crm_type );
		} else {
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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_auto_map_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'connect-crm-realstate' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'connect-crm-realstate' ) ) );
		}

		$settings = get_option( 'ccrmre_settings' );
		$crm_type = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		$properties_fields = API::get_properties_fields( $crm_type );

		if ( 'error' === $properties_fields['status'] ) {
			wp_send_json_error( array( 'message' => $properties_fields['data'] ) );
		}

		$current_mappings = get_option( 'ccrmre_merge_fields', array() );
		$new_mappings     = array();
		$auto_mapped      = 0;

		foreach ( $properties_fields['data'] as $property_field ) {
			$crm_field_name = $property_field['name'];

			if ( isset( $current_mappings[ $crm_field_name ] ) && ! empty( $current_mappings[ $crm_field_name ] ) ) {
				$new_mappings[ $crm_field_name ] = $current_mappings[ $crm_field_name ];
				continue;
			}

			$wp_field_name                   = $this->generate_wp_field_name( $crm_field_name );
			$new_mappings[ $crm_field_name ] = $wp_field_name;
			++$auto_mapped;
		}

		update_option( 'ccrmre_merge_fields', $new_mappings );

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
		$wp_field_name = strtolower( $crm_field_name );
		$wp_field_name = preg_replace( '/[^a-z0-9_]/', '_', $wp_field_name );
		$wp_field_name = preg_replace( '/_+/', '_', $wp_field_name );
		$wp_field_name = trim( $wp_field_name, '_' );
		$wp_field_name = 'crm_' . $wp_field_name;

		return $wp_field_name;
	}

	/**
	 * Renders the taxonomy mapping repeater UI.
	 *
	 * @return void
	 */
	public function taxonomy_mapping_callback() {
		$crm_type          = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';
		$properties_fields = API::get_properties_fields( $crm_type );
		$saved_mappings    = get_option( 'ccrmre_taxonomy_mappings', array() );

		if ( ! is_array( $saved_mappings ) ) {
			$saved_mappings = array();
		}

		// Get available taxonomies.
		$taxonomies = get_taxonomies(
			array( 'public' => true ),
			'objects'
		);

		$has_crm_fields = 'error' !== strtolower( $properties_fields['status'] ?? '' );
		$crm_fields     = $has_crm_fields && ! empty( $properties_fields['data'] ) ? $properties_fields['data'] : array();

		if ( ! $has_crm_fields ) {
			echo '<div class="error notice"><p>';
			esc_html_e( 'Could not load CRM fields. Please check your API connection in Settings.', 'connect-crm-realstate' );
			echo '</p></div>';
			return;
		}

		?>
		<div id="ccrmre-taxonomy-mapping-wrapper">
			<table class="ccrmre-taxonomy-mapping-table" id="ccrmre-taxonomy-mapping-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'CRM Field', 'connect-crm-realstate' ); ?></th>
						<th><?php esc_html_e( 'WordPress Taxonomy', 'connect-crm-realstate' ); ?></th>
						<th class="ccrmre-actions-col"><?php esc_html_e( 'Actions', 'connect-crm-realstate' ); ?></th>
					</tr>
				</thead>
				<tbody id="ccrmre-taxonomy-mapping-body">
					<?php
					if ( ! empty( $saved_mappings ) ) {
						foreach ( $saved_mappings as $index => $mapping ) {
							$this->render_taxonomy_row( $index, $crm_fields, $taxonomies, $mapping );
						}
					} else {
						$this->render_taxonomy_row( 0, $crm_fields, $taxonomies );
					}
					?>
				</tbody>
			</table>

			<p>
				<button type="button" id="ccrmre-add-taxonomy-row" class="button button-secondary">
					<span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
					<?php esc_html_e( 'Add Mapping', 'connect-crm-realstate' ); ?>
				</button>
			</p>
		</div>

		<script type="text/html" id="tmpl-ccrmre-taxonomy-row">
			<?php $this->render_taxonomy_row( '{{INDEX}}', $crm_fields, $taxonomies ); ?>
		</script>
		<?php
	}

	/**
	 * Renders a single taxonomy mapping row.
	 *
	 * @param int|string $index      Row index or placeholder.
	 * @param array      $crm_fields Available CRM fields.
	 * @param array      $taxonomies Available WordPress taxonomies.
	 * @param array      $mapping    Saved mapping data (optional).
	 * @return void
	 */
	private function render_taxonomy_row( $index, $crm_fields, $taxonomies, $mapping = array() ) {
		$selected_crm      = isset( $mapping['crm_field'] ) ? $mapping['crm_field'] : '';
		$selected_taxonomy = isset( $mapping['taxonomy'] ) ? $mapping['taxonomy'] : '';
		?>
		<tr class="ccrmre-taxonomy-row" data-index="<?php echo esc_attr( $index ); ?>">
			<td>
				<select name="ccrmre_taxonomy_mappings[<?php echo esc_attr( $index ); ?>][crm_field]" class="ccrmre-crm-field-select">
					<option value=""><?php esc_html_e( '-- Select CRM Field --', 'connect-crm-realstate' ); ?></option>
					<?php foreach ( $crm_fields as $field ) : ?>
						<option value="<?php echo esc_attr( $field['name'] ); ?>" <?php selected( $selected_crm, $field['name'] ); ?>>
							<?php echo esc_html( $field['label'] ); ?>
							<small>(<?php echo esc_html( $field['name'] ); ?>)</small>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="ccrmre_taxonomy_mappings[<?php echo esc_attr( $index ); ?>][taxonomy]" class="ccrmre-taxonomy-select">
					<option value=""><?php esc_html_e( '-- Select Taxonomy --', 'connect-crm-realstate' ); ?></option>
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $selected_taxonomy, $taxonomy->name ); ?>>
							<?php echo esc_html( $taxonomy->labels->name ); ?>
							<small>(<?php echo esc_html( $taxonomy->name ); ?>)</small>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td class="ccrmre-actions-col">
				<button type="button" class="button ccrmre-remove-taxonomy-row" title="<?php esc_attr_e( 'Remove', 'connect-crm-realstate' ); ?>">
					<span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize taxonomy mapping data before saving.
	 *
	 * @param array $input Raw input from the form.
	 * @return array Sanitized mappings.
	 */
	public function sanitize_taxonomy_mappings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		$index     = 0;

		foreach ( $input as $mapping ) {
			if ( ! is_array( $mapping ) ) {
				continue;
			}

			$crm_field = isset( $mapping['crm_field'] ) ? sanitize_text_field( $mapping['crm_field'] ) : '';
			$taxonomy  = isset( $mapping['taxonomy'] ) ? sanitize_text_field( $mapping['taxonomy'] ) : '';

			// Skip empty rows.
			if ( empty( $crm_field ) || empty( $taxonomy ) ) {
				continue;
			}

			// Validate that the taxonomy exists.
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$sanitized[ $index ] = array(
				'crm_field' => $crm_field,
				'taxonomy'  => $taxonomy,
			);
			++$index;
		}

		return $sanitized;
	}
}
