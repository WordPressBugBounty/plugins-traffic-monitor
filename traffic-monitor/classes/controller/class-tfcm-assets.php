<?php
/**
 * File: /classes/controller/class-tfcm-assets.php
 *
 * Handles script and style enqueueing for Traffic Monitor.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Assets {
	
	public static function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_client_scripts' ) );
	}

	
	public static function enqueue_admin_scripts( $hook ) {
		global $tfcm_admin_page;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $hook !== $tfcm_admin_page ) {
			return;
		}

		wp_enqueue_script(
			'tfcm-admin-script',
			plugins_url( 'assets/js/tfcm-admin-script.js', TFCM_PLUGIN_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-admin-script.js' ),
			true 
		);

		
		wp_localize_script(
			'tfcm-admin-script',
			'tfcmAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tfcm_ajax_nonce' ),
				
				'page'     => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '',
			)
		);

		wp_enqueue_style(
			'tfcm-admin-style',
			plugins_url( 'assets/css/tfcm-admin-style.css', TFCM_PLUGIN_FILE ),
			array(),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/css/tfcm-admin-style.css' )
		);
	}

	
	public static function enqueue_client_scripts() {
		global $tfcm_request_type;
		if ( 'HTTP' !== $tfcm_request_type ) {
			return;
		}

		wp_enqueue_script(
			'tfcm-client-script',
			plugins_url( 'assets/js/tfcm-client-script.js', TFCM_PLUGIN_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-client-script.js' ),
			false 
		);

		global $cache_check_nonce;

		wp_localize_script(
			'tfcm-client-script',
			'tfcmClientAjax',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'logging_nonce' => $cache_check_nonce,
			)
		);
		

		wp_enqueue_script( 'tfcm-client-script' );
	}
}
