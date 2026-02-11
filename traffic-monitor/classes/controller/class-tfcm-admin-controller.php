<?php
/**
 * File: /classes/controller/class-tfcm-admin-controller.php
 *
 * This file defines the TFCM_Admin_Controller class which handles all admin-related
 * functionality for the Traffic Monitor plugin, including menu registration, screen options,
 * and rendering the log page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Admin_Controller {
	
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_filter( 'default_hidden_columns', array( __CLASS__, 'set_default_hidden_columns' ), 10, 2 );
		add_action( 'set-screen-option', array( __CLASS__, 'save_screen_options' ), 10, 3 );
		add_filter( 'hidden_columns', array( __CLASS__, 'get_hidden_columns' ), 10, 2 );
	}

	
	public static function register_admin_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $tfcm_admin_page;
		$tfcm_admin_page = add_menu_page(
			'Traffic Monitor Settings',
			'Traffic Monitor',
			'manage_options',
			'traffic-monitor',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-list-view',
			81
		);

		add_action( "load-$tfcm_admin_page", array( __CLASS__, 'handle_screen_options' ) );
	}

	
	public static function render_admin_page() {
		
		if ( isset( $_GET['action'] ) && 'view_details' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && isset( $_GET['id'] ) ) {
			self::render_request_details();
			return;
		}

		$tfcm_table = new TFCM_Log_Table();
		$tfcm_table->prepare_items();

		TFCM_View::render_admin_page( $tfcm_table );
	}

	
	private static function render_request_details() {
		
		if ( ! isset( $_GET['tfcm_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['tfcm_details_nonce'] ) ), 'tfcm_details_nonce' ) ) {
			TFCM_View::display_notice( 'Invalid request. Please try again.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		$log_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		if ( 0 === $log_id ) {
			TFCM_View::display_notice( 'Invalid log entry.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		$log = TFCM_Database::get_single_request( $log_id );

		if ( ! $log ) {
			TFCM_View::display_notice( 'Log not found.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		TFCM_View::render_request_details( $log );
	}

	
	public static function handle_screen_options() {
		global $tfcm_admin_page, $tfcm_table;
		$screen = get_current_screen();

		if ( ! is_object( $screen ) || $screen->id !== $tfcm_admin_page ) {
			return;
		}

		$user_id          = get_current_user_id();
		$option_per_page  = 'tfcm_elements_per_page';
		$default_per_page = 10;

		if ( get_user_meta( $user_id, $option_per_page, true ) === '' ) {
			update_user_meta( $user_id, $option_per_page, $default_per_page );
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => 'Elements per page',
				'default' => $default_per_page,
				'option'  => $option_per_page,
			)
		);

		$tfcm_table = new TFCM_Log_Table();
	}

	
	public static function set_default_hidden_columns( $hidden, $screen ) {
		if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
			$hidden = array(
				'request_query',
				'method',
				'referrer_url',
				'referrer_query',
				'user_role',
				'ip_address',
				'fingerprint_hash',
				'operating_system',
				'browser',
				'bot_category',
				'user_agent',
				'click_key',
				'click_value',
				'status_code',
			);
		}
		return $hidden;
	}

	
	public static function save_screen_options( $status, $option, $value ) {
		if ( 'tfcm_elements_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	
	public static function get_hidden_columns( $hidden, $screen ) {
		if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
			$user         = get_current_user_id();
			$saved_hidden = get_user_meta( $user, 'manage' . $screen->id . 'columnshidden', true );
			$all_columns  = array_keys( ( new TFCM_Log_Table() )->get_columns() );

			if ( ! is_array( $saved_hidden ) ) {
				$saved_hidden = apply_filters( 'default_hidden_columns', array(), $screen );
			}

			return array_intersect( $all_columns, $saved_hidden );
		}
		return $hidden;
	}
}
