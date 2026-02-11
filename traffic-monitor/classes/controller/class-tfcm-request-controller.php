<?php
/**
 * File: /classes/controller/class-tfcm-request-controller.php
 *
 * Determines the type of incoming request (AJAX, HTTP, etc.) and routes it for logging,
 * as well as handling bulk actions via AJAX.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Request_Controller {
	
	public static function register_hooks() {
		
		add_action( 'wp_ajax_nopriv_tfcm_get_ip', array( __CLASS__, 'get_client_ip' ) );
		
		add_action( 'wp_ajax_tfcm_get_ip', array( __CLASS__, 'get_client_ip' ) );
		
		add_action( 'wp_ajax_nopriv_tfcm_log_ajax_request', array( __CLASS__, 'handle_ajax_request' ) );
		
		add_action( 'wp_ajax_tfcm_log_ajax_request', array( __CLASS__, 'handle_ajax_request' ) );
		
		add_action( 'wp_ajax_tfcm_handle_bulk_action', array( __CLASS__, 'handle_bulk_action' ) );
	}

	
	public static function get_client_ip() {
		$origin      = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$origin_host = wp_parse_url( $origin, PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( $origin_host !== $site_host ) {
			wp_send_json_error( array( 'message' => 'Invalid origin.' ), 403 );
			return;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';

		wp_send_json_success( array( 'ip' => $ip ) );
	}

	
	public static function handle_ajax_request() {
		global $tfcm_request_type;
		$tfcm_request_type = 'AJAX';
		self::handle_request();
	}

	
	public static function handle_request() {
		
		global $tfcm_request_type;

		
		if ( 'AJAX' === $tfcm_request_type ) {
			$request = new TFCM_Request_Ajax();
		} elseif ( 'HTTP' === $tfcm_request_type ) {
			$request = new TFCM_Request_Http();
		} else {
			exit;
		}

		
		$ip = $request->ip_address;
		if ( $ip && get_transient( 'tfcm_quarantine_' . md5( $ip ) ) ) {
			
			
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				fastcgi_finish_request();
			} else {
				header( 'Connection: close' );
				header( 'Content-Length: 0' );
				flush();
			}
			exit;
		}

		$bot_found  = false;
		$user_agent = strtolower( $request->user_agent );

		
		$bot_list = get_transient( 'tfcm_all_bots' );
		if ( false === $bot_list ) {
			$bot_list = TFCM_Database::get_all_bots();
			set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
		}

		foreach ( $bot_list as $bot_record ) {
			if ( strpos( $user_agent, strtolower( $bot_record['name'] ) ) !== false ) {
				
				$bot_found = true;
				self::handle_matched_bot( $bot_record, $request );
				break; 
			}
		}

		if ( false === $bot_found ) {
			self::process_request_logging( $request );
		}
	}

	
	private static function handle_matched_bot( $bot_record, $request ) {
		global $tfcm_request_type;

		$block_bots   = $GLOBALS['TFCM_BOT_BLOCKING'];
		$skip_logging = $GLOBALS['TFCM_SKIP_BOT_LOGGING'];
		$should_log   = ! $skip_logging || ( $skip_logging && 1 === (int) $bot_record['log_request'] );
		$should_block = 1 === (int) $bot_record['block'] && 'HTTP' === $tfcm_request_type && $block_bots;

		
		TFCM_Database::increment_bot_total_requests( $bot_record['name'] );

		
		if ( $should_block ) {
			http_response_code( 403 );
			
		}

		
		if ( $should_log ) {
			
			self::process_request_logging( $request );
		}

		
		if ( $should_block ) {
			
			if ( $should_log ) {
				
				nocache_headers();
				header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );
				header( 'Pragma: no-cache', true );
				header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT', true );
				header( 'HTTP/1.1 403 Forbidden' );
				exit;
			} else {
				
				$ip = $request->ip_address;
				
				if ( $ip ) {
					set_transient( 'tfcm_quarantine_' . md5( $ip ), true, 10 );
				}

				
				
				if ( function_exists( 'fastcgi_finish_request' ) ) {
					fastcgi_finish_request();
				} else {
					header( 'Connection: close' );
					header( 'Content-Length: 0' );
					flush();
				}
				exit;
			}
		}
	}

	
	private static function process_request_logging( $request ) {
		

		
		global $cache_check_nonce;
		$cache_check_nonce = wp_create_nonce( uniqid( 'tfcm_cache_logging_nonce_', true ) );

		
		$log_controller = new TFCM_Log_Controller( $request );
		$log_controller->process_request();
	}

	
	public static function handle_bulk_action() {

		
		if ( ! check_ajax_referer( 'tfcm_ajax_nonce', 'nonce', false ) ) {
			
			wp_send_json_error( array( 'message' => 'Invalid request. Please try again.' ), 400 );
			return;
		}

		
		if ( ! current_user_can( 'manage_options' ) ) {
			
			wp_send_json_error( array( 'message' => 'Unauthorized access.' ), 403 );
			return;
		}

		
		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$log_ids     = isset( $_POST['element'] ) ? wp_parse_id_list( wp_unslash( $_POST['element'] ) ) : array();

		if ( empty( $bulk_action ) ) {
			wp_send_json_error( array( 'message' => 'Please select a bulk action before clicking Apply.' ), 400 );
		}

		if ( ( 'delete' === $bulk_action || 'export' === $bulk_action ) && empty( $log_ids ) ) {
			wp_send_json_error( array( 'message' => 'Please select the records you want to ' . $bulk_action . '.' ), 400 );
		}

		if ( 'delete' === $bulk_action ) {
			$result = TFCM_Database::delete_selected_requests( $log_ids );

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'Total records deleted: ' . count( $log_ids ) ), 200 );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete records.' ), 400 );
			}
		} elseif ( 'delete_all' === $bulk_action ) {
			$result = TFCM_Database::delete_all_requests();

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'All records deleted successfully. Refresh table to verify.' ), 200 );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete all records.' ), 400 );
			}
		}

		if ( 'export' === $bulk_action || 'export_all' === $bulk_action ) {
			TFCM_Export_Manager::delete_old_exports();

			
			$nonce     = wp_create_nonce( 'tfcm_csv_export' );
			$timestamp = time();
			$domain    = str_replace( '.', '-', wp_parse_url( home_url(), PHP_URL_HOST ) );
			$file_name = $domain . "-traffic-log-{$nonce}-{$timestamp}.csv";
		}

		if ( 'export' === $bulk_action ) {
			$rows = TFCM_Database::get_selected_requests( $log_ids );

			$total_rows = count( $log_ids );
			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		} elseif ( 'export_all' === $bulk_action ) {
			$total_rows = TFCM_Database::count_all_requests();
			$rows       = TFCM_Database::get_all_requests();

			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		}
	}
}
