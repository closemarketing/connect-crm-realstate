<?php
/**
 * Library for Property Information Box
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Property Information Box Class
 *
 * Handles property information display with icons and shortcode
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */
class PropertyInfo {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * CRM field definitions: icon SVG path, translatable label, optional suffix, and whether it's a price field.
	 *
	 * @var array
	 */
	private $field_definitions = array();

	/**
	 * CRM fields that should appear in the price header instead of the grid.
	 *
	 * @var array
	 */
	private $price_fields = array(
		'precioinmo',
		'precioreal',
		'precioalq',
		'preciotraspaso',
		'precio',
		'price',
		'pvp',
		'precio_venta',
		'outlet',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'ccrmre_settings' );

		$this->field_definitions = array(
			// Prices.
			'precioinmo'      => array(
				'label'    => __( 'Price', 'connect-crm-realstate' ),
				'icon'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
				'is_price' => true,
			),
			'precioreal'      => array(
				'label'    => __( 'Real Price', 'connect-crm-realstate' ),
				'icon'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
				'is_price' => true,
			),
			'precioalq'       => array(
				'label'    => __( 'Rental Price', 'connect-crm-realstate' ),
				'icon'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
				'is_price' => true,
			),
			'preciotraspaso'  => array(
				'label'    => __( 'Transfer Price', 'connect-crm-realstate' ),
				'icon'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
				'is_price' => true,
			),
			'outlet'          => array(
				'label'    => __( 'Previous Price', 'connect-crm-realstate' ),
				'icon'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
				'is_price' => true,
			),
			// Rooms.
			'total_hab'       => array(
				'label' => __( 'Bedrooms', 'connect-crm-realstate' ),
				'icon'  => '<path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/>',
			),
			'habdobles'       => array(
				'label' => __( 'Double Bedrooms', 'connect-crm-realstate' ),
				'icon'  => '<path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/>',
			),
			'habitaciones'    => array(
				'label' => __( 'Single Bedrooms', 'connect-crm-realstate' ),
				'icon'  => '<path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/>',
			),
			// Bathrooms.
			'banyos'          => array(
				'label' => __( 'Bathrooms', 'connect-crm-realstate' ),
				'icon'  => '<path d="M20 2H4c-1.11 0-2 .89-2 2v16c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zM7 3c1.1 0 2 .89 2 2 0 1.1-.9 2-2 2s-2-.9-2-2c0-1.11.9-2 2-2zm13 15H4v-2h16v2zm0-5H4V6h16v7z"/>',
			),
			'aseos'           => array(
				'label' => __( 'Toilets', 'connect-crm-realstate' ),
				'icon'  => '<path d="M20 2H4c-1.11 0-2 .89-2 2v16c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zM7 3c1.1 0 2 .89 2 2 0 1.1-.9 2-2 2s-2-.9-2-2c0-1.11.9-2 2-2zm13 15H4v-2h16v2zm0-5H4V6h16v7z"/>',
			),
			// Areas.
			'm_cons'          => array(
				'label'  => __( 'Built Area', 'connect-crm-realstate' ),
				'icon'   => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H5V5h9v12zm5-12v12h-3V5h3z"/>',
				'suffix' => 'm²',
			),
			'm_uties'         => array(
				'label'  => __( 'Useful Area', 'connect-crm-realstate' ),
				'icon'   => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H5V5h9v12zm5-12v12h-3V5h3z"/>',
				'suffix' => 'm²',
			),
			'm_terraza'       => array(
				'label'  => __( 'Terrace', 'connect-crm-realstate' ),
				'icon'   => '<path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>',
				'suffix' => 'm²',
			),
			'm_parcela'       => array(
				'label'  => __( 'Plot', 'connect-crm-realstate' ),
				'icon'   => '<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>',
				'suffix' => 'm²',
			),
			// Property details.
			'nbtipo'          => array(
				'label' => __( 'Type', 'connect-crm-realstate' ),
				'icon'  => '<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>',
			),
			'key_tipo'        => array(
				'label' => __( 'Type', 'connect-crm-realstate' ),
				'icon'  => '<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>',
			),
			'nbconservacion'  => array(
				'label' => __( 'Condition', 'connect-crm-realstate' ),
				'icon'  => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>',
			),
			// Location.
			'ciudad'          => array(
				'label' => __( 'City', 'connect-crm-realstate' ),
				'icon'  => '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>',
			),
			'zona'            => array(
				'label' => __( 'Zone', 'connect-crm-realstate' ),
				'icon'  => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>',
			),
			'key_zona'        => array(
				'label' => __( 'Zone', 'connect-crm-realstate' ),
				'icon'  => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>',
			),
			// Reference.
			'ref'             => array(
				'label' => __( 'Reference', 'connect-crm-realstate' ),
				'icon'  => '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>',
			),
			'cod_ofer'        => array(
				'label' => __( 'Code', 'connect-crm-realstate' ),
				'icon'  => '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>',
			),
			// Features (boolean / flags shown as yes).
			'balcon'          => array(
				'label'    => __( 'Balcony', 'connect-crm-realstate' ),
				'icon'     => '<path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>',
				'is_bool'  => true,
			),
			'terraza'         => array(
				'label'   => __( 'Terrace', 'connect-crm-realstate' ),
				'icon'    => '<path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>',
				'is_bool' => true,
			),
			'ascensor'        => array(
				'label'   => __( 'Elevator', 'connect-crm-realstate' ),
				'icon'    => '<path d="M7 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H7v10zM9 10h6v8H9v-8zm4.5-6l-1-1h-3l-1 1H5v2h14V4h-4.5z"/>',
				'is_bool' => true,
			),
			'parking'         => array(
				'label'   => __( 'Parking', 'connect-crm-realstate' ),
				'icon'    => '<path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>',
				'is_bool' => true,
			),
			'plaza_gara'      => array(
				'label'   => __( 'Garage Space', 'connect-crm-realstate' ),
				'icon'    => '<path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>',
				'is_bool' => true,
			),
			'aire_con'        => array(
				'label'   => __( 'A/C', 'connect-crm-realstate' ),
				'icon'    => '<path d="M22 11h-4.17l3.24-3.24-1.41-1.42L15 11h-2V9l4.66-4.66-1.42-1.41L13 6.17V2h-2v4.17L7.76 2.93 6.34 4.34 11 9v2H9L4.34 6.34 2.93 7.76 6.17 11H2v2h4.17l-3.24 3.24 1.41 1.42L9 13h2v2l-4.66 4.66 1.42 1.41L11 17.83V22h2v-4.17l3.24 3.24 1.42-1.41L13 15v-2h2l4.66 4.66 1.41-1.42L17.83 13H22v-2z"/>',
				'is_bool' => true,
			),
			'calefaccion'     => array(
				'label'   => __( 'Heating', 'connect-crm-realstate' ),
				'icon'    => '<path d="M13.5 0.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>',
				'is_bool' => true,
			),
			'muebles'         => array(
				'label'   => __( 'Furnished', 'connect-crm-realstate' ),
				'icon'    => '<path d="M20 8h-2.81c-.45-.78-1.07-1.45-1.82-1.96L17 4.41 15.59 3l-2.17 2.17C12.96 5.06 12.49 5 12 5c-.49 0-.96.06-1.41.17L8.41 3 7 4.41l1.62 1.63C7.88 6.55 7.26 7.22 6.81 8H4v2h2.09c-.05.33-.09.66-.09 1v1H4v2h2v1c0 .34.04.67.09 1H4v2h2.81c1.04 1.79 2.97 3 5.19 3s4.15-1.21 5.19-3H20v-2h-2.09c.05-.33.09-.66.09-1v-1h2v-2h-2v-1c0-.34-.04-.67-.09-1H20V8zm-6 8h-4v-2h4v2zm0-4h-4v-2h4v2z"/>',
				'is_bool' => true,
			),
			'piscina_com'     => array(
				'label'   => __( 'Community Pool', 'connect-crm-realstate' ),
				'icon'    => '<path d="M22 21c-1.11 0-1.73-.37-2.18-.64-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.46.27-1.07.64-2.18.64s-1.73-.37-2.18-.64c-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.46.27-1.08.64-2.19.64-1.11 0-1.73-.37-2.18-.64-.37-.23-.6-.36-1.15-.36s-.78.13-1.15.36c-.46.27-1.08.64-2.19.64v-2c.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64 1.11 0 1.73.37 2.18.64.37.22.6.36 1.15.36s.78-.13 1.15-.36c.45-.27 1.07-.64 2.18-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36v2zm0-4.5c-1.11 0-1.73-.37-2.18-.64-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.45.27-1.07.64-2.18.64s-1.73-.37-2.18-.64c-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.45.27-1.07.64-2.18.64s-1.73-.37-2.19-.64c-.36-.23-.59-.36-1.14-.36s-.78.13-1.15.36c-.46.27-1.08.64-2.19.64v-2c.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64 1.11 0 1.73.37 2.18.64.37.22.6.36 1.15.36s.78-.13 1.15-.36c.45-.27 1.07-.64 2.18-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36v2zM8.67 12c.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64 1.11 0 1.73.37 2.18.64.37.22.6.36 1.15.36s.78-.13 1.15-.36c.12-.07.26-.15.41-.23L10.48 5.7C9.88 5.27 9.17 5 8.4 5H8v6.85c.21-.06.44-.1.67-.1.56-.01.78.12 0 .25z"/><circle cx="16.5" cy="5.5" r="2.5"/>',
				'is_bool' => true,
			),
			'piscina_prop'    => array(
				'label'   => __( 'Private Pool', 'connect-crm-realstate' ),
				'icon'    => '<path d="M22 21c-1.11 0-1.73-.37-2.18-.64-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.46.27-1.07.64-2.18.64s-1.73-.37-2.18-.64c-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.46.27-1.08.64-2.19.64-1.11 0-1.73-.37-2.18-.64-.37-.23-.6-.36-1.15-.36s-.78.13-1.15.36c-.46.27-1.08.64-2.19.64v-2c.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36.56 0 .78-.13 1.15-.36.46-.27 1.08-.64 2.19-.64 1.11 0 1.73.37 2.18.64.37.22.6.36 1.15.36s.78-.13 1.15-.36c.45-.27 1.07-.64 2.18-.64s1.73.37 2.18.64c.37.23.59.36 1.15.36v2z"/>',
				'is_bool' => true,
			),
			'vistasalmar'     => array(
				'label'   => __( 'Sea Views', 'connect-crm-realstate' ),
				'icon'    => '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>',
				'is_bool' => true,
			),
			'vistasdespejadas' => array(
				'label'   => __( 'Open Views', 'connect-crm-realstate' ),
				'icon'    => '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>',
				'is_bool' => true,
			),
			'exclu'           => array(
				'label'   => __( 'Exclusive', 'connect-crm-realstate' ),
				'icon'    => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>',
				'is_bool' => true,
			),
			'distmar'         => array(
				'label'  => __( 'Distance to Sea', 'connect-crm-realstate' ),
				'icon'   => '<path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>',
				'suffix' => 'm',
			),
			'tipomensual'     => array(
				'label' => __( 'Rental Period', 'connect-crm-realstate' ),
				'icon'  => '<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>',
			),
			'agencia'         => array(
				'label' => __( 'Agency', 'connect-crm-realstate' ),
				'icon'  => '<path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/>',
			),
		);

		// Register shortcode (prefixed for Plugin Directory guidelines).
		add_shortcode( 'ccrmre_property_info', array( $this, 'shortcode_property_info' ) );
		add_shortcode( 'property_info', array( $this, 'shortcode_property_info' ) ); // Backward compatibility.

		// Auto display property info if enabled.
		// Priority 30 ensures it appears after gallery (priority 20).
		if ( isset( $this->settings['show_property_info'] ) && 'yes' === $this->settings['show_property_info'] ) {
			add_filter( 'the_content', array( $this, 'auto_display_property_info' ), 30 );
		}

		// Enqueue property info assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_property_info_assets' ) );
	}

	/**
	 * Enqueue property info assets
	 *
	 * @return void
	 */
	public function enqueue_property_info_assets() {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) ) {
			return;
		}

		wp_enqueue_style(
			'ccrmre-property-info',
			plugin_dir_url( __FILE__ ) . 'assets/property-info.css',
			array(),
			CCRMRE_VERSION
		);
	}

	/**
	 * Auto display property info after content
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function auto_display_property_info( $content ) {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$info_html = $this->render_property_info();

		if ( empty( $info_html ) ) {
			return $content;
		}

		return $content . $info_html;
	}

	/**
	 * Shortcode for property info
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_property_info( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'property_info'
		);

		return $this->render_property_info( $atts['post_id'] );
	}

	/**
	 * Format a numeric value as price in euros.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function format_price( $value ) {
		if ( is_numeric( $value ) ) {
			return number_format( (float) $value, 0, ',', '.' ) . ' €';
		}
		return $value . ' €';
	}

	/**
	 * Render property info HTML
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function render_property_info( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		// Get merge fields configuration: [ 'crm_field' => 'wp_meta_key', ... ].
		$merge_fields = get_option( 'ccrmre_merge_fields', array() );

		if ( empty( $merge_fields ) ) {
			return '';
		}

		// Iterate merge fields in configured order and collect values.
		$price_items = array();
		$grid_items  = array();

		foreach ( $merge_fields as $crm_field => $wp_field ) {
			if ( empty( $wp_field ) ) {
				continue;
			}

			$value = get_post_meta( $post_id, $wp_field, true );

			if ( '' === $value || null === $value || false === $value ) {
				continue;
			}

			$def = isset( $this->field_definitions[ $crm_field ] ) ? $this->field_definitions[ $crm_field ] : null;

			if ( ! empty( $def['is_bool'] ) ) {
				// Only show boolean fields when their value is truthy (1, "1", "yes", "true").
				if ( ! $value || '0' === (string) $value ) {
					continue;
				}
			}

			if ( $def && ! empty( $def['is_price'] ) ) {
				$price_items[ $crm_field ] = array(
					'label' => $def['label'],
					'value' => $this->format_price( $value ),
					'icon'  => $def['icon'],
				);
			} else {
				$display_value = $value;
				$suffix        = '';

				if ( $def ) {
					if ( ! empty( $def['suffix'] ) ) {
						$suffix = ' ' . $def['suffix'];
					}
					if ( ! empty( $def['is_bool'] ) ) {
						$display_value = __( 'Yes', 'connect-crm-realstate' );
					}
					$label = $def['label'];
					$icon  = $def['icon'];
				} else {
					// Unknown field: use the CRM field name as label, generic icon.
					$label = ucfirst( str_replace( '_', ' ', $crm_field ) );
					$icon  = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>';
				}

				$grid_items[ $crm_field ] = array(
					'label' => $label,
					'value' => $display_value . $suffix,
					'icon'  => $icon,
				);
			}
		}

		if ( empty( $price_items ) && empty( $grid_items ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="ccrmre-property-info-box">
			<?php if ( ! empty( $price_items ) ) : ?>
				<div class="ccrmre-info-price-row">
					<?php foreach ( $price_items as $item ) : ?>
						<div class="ccrmre-info-price">
							<span class="ccrmre-price-label"><?php echo esc_html( $item['label'] ); ?></span>
							<span class="ccrmre-price-value"><?php echo esc_html( $item['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $grid_items ) ) : ?>
				<div class="ccrmre-info-grid">
					<?php foreach ( $grid_items as $crm_field => $item ) : ?>
						<div class="ccrmre-info-item ccrmre-field-<?php echo esc_attr( $crm_field ); ?>">
							<span class="ccrmre-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
									<?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</svg>
							</span>
							<div class="ccrmre-info-content">
								<span class="ccrmre-info-value"><?php echo esc_html( $item['value'] ); ?></span>
								<span class="ccrmre-info-label"><?php echo esc_html( $item['label'] ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

new PropertyInfo();
