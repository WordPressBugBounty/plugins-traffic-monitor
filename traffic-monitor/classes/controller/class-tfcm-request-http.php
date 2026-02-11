<?php
/**
 * File: /classes/controller/class-tfcm-request-http.php
 *
 * Extends TFCM_Request_Abstract to handle HTTP (non-AJAX) request data.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Request_Http extends TFCM_Request_Abstract {
	
	public function __construct() {
		parent::__construct();

		$domain      = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$path        = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $path, PHP_URL_PATH ) ?: '';  
		$query       = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		$request_url = $domain ? $domain . $path : '';

		$this->request_url   = mb_substr( $request_url, 0, 190, 'UTF-8' ); 
		$this->request_query = mb_substr( $query, 0, 2048, 'UTF-8' );
		$this->is_cached     = 0;

		$method       = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$this->method = mb_substr( $method, 0, 10, 'UTF-8' );

		$referrer             = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$ref_parts            = wp_parse_url( $referrer ) ?: array();
		$referrer_host        = isset( $ref_parts['host'] ) ? $ref_parts['host'] : '';
		$referrer_path        = isset( $ref_parts['path'] ) ? $ref_parts['path'] : '';
		$referrer_query       = isset( $ref_parts['query'] ) ? $ref_parts['query'] : '';
		$referrer_url         = $referrer_host . $referrer_path;
		$this->referrer_url   = mb_substr( $referrer_url, 0, 190, 'UTF-8' );
		$this->referrer_query = mb_substr( $referrer_query, 0, 255, 'UTF-8' );

		
		$forwarded_for = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
		$xff_ips       = array_filter( array_map( 'trim', explode( ',', $forwarded_for ) ) );
		$remote_addr   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$best_ip       = filter_var( $remote_addr, FILTER_VALIDATE_IP ) ? $remote_addr : '';
		foreach ( array_reverse( $xff_ips ) as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				$best_ip = $ip;
				break;
			}
		}
		$this->ip_address = mb_substr( $best_ip, 0, 45, 'UTF-8' );

		$fingerprint_hash       = md5( $this->ip_address . $this->user_agent );
		$this->fingerprint_hash = mb_substr( $fingerprint_hash, 0, 32, 'UTF-8' );

		if ( $this->bot_category !== 'Advertising Bot' ) {
			$data                = self::get_ad_key_value( $this->request_query );
			$this->ad_platform   = mb_substr( $data['ad_platform'], 0, 32, 'UTF-8' );
			$this->click_key     = mb_substr( $data['click_key'], 0, 32, 'UTF-8' );
			$this->click_value   = mb_substr( $data['click_value'], 0, 32, 'UTF-8' );
			$this->campaign_id   = mb_substr( $data['campaign_id'], 0, 64, 'UTF-8' );
			$this->campaign_name = mb_substr( $data['campaign_name'], 0, 255, 'UTF-8' );
			$this->adgroup_id    = mb_substr( $data['adgroup_id'], 0, 64, 'UTF-8' );
			$this->adgroup_name  = mb_substr( $data['adgroup_name'], 0, 255, 'UTF-8' );
			$this->ad_id         = mb_substr( $data['ad_id'], 0, 64, 'UTF-8' );
			$this->keyword       = mb_substr( $data['keyword'], 0, 64, 'UTF-8' );
		}

		$source       = $this->get_source( $this->ad_platform, $this->referrer_url );
		$this->source = mb_substr( $source, 0, 64, 'UTF-8' );
	}
}
