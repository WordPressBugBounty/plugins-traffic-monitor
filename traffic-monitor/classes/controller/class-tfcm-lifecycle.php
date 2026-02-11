<?php
/**
 * File: /classes/controller/class-tfcm-lifecycle.php
 *
 * Handles plugin lifecycle events such as activation, deactivation, uninstallation,
 * and database updates for Traffic Monitor.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Lifecycle {
	
	public static function register_hooks() {
		register_activation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'run_on_plugin_update' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_update_notice' ) );
		add_action( 'wp_ajax_tfcm_dismiss_export_notice', array( __CLASS__, 'handle_dismiss_export_notice' ) );
	}

	
	public static function activate() {
		
		TFCM_Database::create_tables();
		
		

		TFCM_Database::import_bots_from_csv();
		
		set_transient( 'tfcm_all_bots', TFCM_Database::get_all_bots(), MONTH_IN_SECONDS );

		update_option( 'tfcm_plugin_version', TRAFFIC_MONITOR_VERSION );
	}

	
	public static function deactivate() {
		
	}

	
	public static function uninstall() {
		$users = get_users( array( 'fields' => 'ID' ) );
		foreach ( $users as $user_id ) {
			delete_user_meta( $user_id, 'tfcm_elements_per_page' );
			delete_user_meta( $user_id, 'managetoplevel_page_traffic-monitorcolumnshidden' );
		}

		
		TFCM_Export_Manager::delete_old_exports();

		
		TFCM_Database::delete_tables();

		
		delete_transient( 'tfcm_all_bots' );

		
		delete_option( 'tfcm_plugin_version' );
		delete_option( 'tfcm_old_log_export_notice' );
	}

	
	public static function run_on_plugin_update() {
		$current_version = get_option( 'tfcm_plugin_version', '1.0.0' );

		
		
		
		
		
		
		
		
		

		
		if ( version_compare( $current_version, '3.0.0', '<' ) ) {
			TFCM_Database::maybe_upgrade_bot_name_column();

			

			$nonce     = wp_create_nonce( 'tfcm_csv_export' );
			$timestamp = time();
			$domain    = str_replace( '.', '-', wp_parse_url( home_url(), PHP_URL_HOST ) );
			$file_name = $domain . "-traffic-log-{$nonce}-{$timestamp}.csv";

			
			$rows       = TFCM_Database::get_old_log_table_requests();
			$total_rows = TFCM_Database::count_old_log_table_requests();

			if ( ! empty( $rows ) ) {
				$export_success = TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows, true );

				if ( $export_success ) {
					
					$file_url = plugins_url( 'exports/' . $file_name, TFCM_PLUGIN_FILE );
					
					update_option( 'tfcm_old_log_export_notice', array( 'file_url' => $file_url ) );

					
					TFCM_Database::delete_old_log_table();
				}
			}
		}

		
		if ( version_compare( $current_version, TRAFFIC_MONITOR_VERSION, '<' ) ) {
			
			TFCM_Database::create_tables();
			
			TFCM_Database::import_bots_from_csv();
			
			set_transient( 'tfcm_all_bots', TFCM_Database::get_all_bots(), MONTH_IN_SECONDS );

			update_option( 'tfcm_plugin_version', TRAFFIC_MONITOR_VERSION );
		}
	}

	
	public static function display_update_notice() {
		$notice = get_option( 'tfcm_old_log_export_notice', false );

		if ( ! $notice ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( ! $current_screen || 'toplevel_page_traffic-monitor' !== $current_screen->id ) {
			return;
		}

		

		printf(
			'<div class="notice notice-warning is-dismissible" id="tfcm-old-log-export-notice">
			<p><strong>Traffic Monitor Update Notice:</strong> 
			Due to a major database update, your request log has been reset.</p> 
			<p>A backup of your previous log is available for download: 
			<a href="%1$s" target="_blank" rel="noopener noreferrer">Download your exported log</a>
			before dismissing this message.</p></div>',
			esc_url( $notice['file_url'] )
		);
	}

	
	public static function handle_dismiss_export_notice() {
		check_ajax_referer( 'tfcm_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			return;
		}

		delete_option( 'tfcm_old_log_export_notice' );
		wp_send_json_success();
	}
}
