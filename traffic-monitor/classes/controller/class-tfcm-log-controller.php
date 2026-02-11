<?php
/**
 * File: /classes/controller/class-tfcm-log-controller.php
 *
 * Processes incoming requests and logs them to the database.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Log_Controller {
	private $request;

	
	public function __construct( $request ) {
		$this->request = $request;
	}

	
	public function process_request() {
		global $tfcm_request_type;

		
		if ( 'HTTP' === $tfcm_request_type && ! $this->should_log_request() ) {
			return; 
		}

		
		if (
			preg_match( '/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico|map)$/i', $this->request->request_url ?: '' ) ||
			stripos( $this->request->request_url ?: '', '/wp-json/' ) !== false
		) {
			return; 
		}

		if ( 'AJAX' === $tfcm_request_type ) {
			
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( '' === $nonce ) {
				
				wp_send_json_error( array( 'message' => 'Missing nonce.' ), 400 );
				return;
			}
			$is_localhost = stripos( $this->request->request_url, 'localhost' ) !== false;
			if ( $is_localhost ) {
				
				
				wp_send_json_success( array( 'message' => 'Localhost AJAX request ignored.' ), 200 );
				return;
			}
		} elseif ( 'HTTP' === $tfcm_request_type ) {
			global $cache_check_nonce;
			$nonce = $cache_check_nonce;
		} else {
			return; 
		}

		$ip            = $this->request->ip_address;
		$transient_key = 'tfcm_nonce_' . $nonce . '_' . md5( $ip );

		
		if ( get_transient( $transient_key ) ) {
			

			if ( 'AJAX' === $tfcm_request_type ) {
				wp_send_json_success( array( 'message' => 'Request already logged.' ), 200 );
			}

			return;
		}

		
		set_transient( $transient_key, true, 60 );

		
		register_shutdown_function(
			function () {
				$this->request->status_code = http_response_code();
				TFCM_Database::insert_request( $this->request );
			}
		);

		if ( 'AJAX' === $tfcm_request_type ) {
			wp_send_json_success( array( 'message' => 'AJAX request logged successfully.' ), 200 );
			return;
		}
	}

	
	private function should_log_request() {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		$path   = wp_parse_url( $this->request->request_url, PHP_URL_PATH );
		$ext    = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( stripos( $accept, 'html' ) !== false ) {
			return true;
		}

		$page_exts = array( 'html', 'htm', 'php', 'asp', 'aspx', 'jsp' );
		if ( in_array( $ext, $page_exts, true ) ) {
			return true;
		}

		if ( empty( $ext ) ) {
			return true;
		}

		return false;
	}
}
