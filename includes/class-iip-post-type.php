<?php
/**
 * Library for Post type settings
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
class PostType {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'cpt_property' ) );
	}

	/**
	 * Register Post Type POST Property
	 *
	 * @return void
	 **/
	public function cpt_property() {
		$labels = array(
			'name'               => __( 'Property', 'connect-crm-realstate' ),
			'singular_name'      => __( 'Properties', 'connect-crm-realstate' ),
			'add_new'            => __( 'Add New Properties', 'connect-crm-realstate' ),
			'add_new_item'       => __( 'Add New Properties', 'connect-crm-realstate' ),
			'edit_item'          => __( 'Edit Properties', 'connect-crm-realstate' ),
			'new_item'           => __( 'New Properties', 'connect-crm-realstate' ),
			'view_item'          => __( 'View Properties', 'connect-crm-realstate' ),
			'search_items'       => __( 'Search Property', 'connect-crm-realstate' ),
			'not_found'          => __( 'Not found Property', 'connect-crm-realstate' ),
			'not_found_in_trash' => __( 'Not found Property in trash', 'connect-crm-realstate' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true, // Adds gutenberg support.
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => _x( 'properties', 'slug', 'connect-crm-realstate' ),
				'with_front' => false,
			),
			'has_archive'        => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-admin-users', // https://developer.wordpress.org/resource/dashicons/.
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);
		register_post_type( 'property', $args );
	}

}

