<?php
/**
 * File: /classes/controller/class-tfcm-request-ajax.php
 *
 * Extends TFCM_Request_Abstract to handle AJAX-specific request data.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Request_Ajax extends TFCM_Request_Abstract {
	
	public function __construct() {
		parent::__construct();

		
		$raw_request_url   = isset( $_POST['request_url'] ) ? sanitize_text_field( wp_unslash( $_POST['request_url'] ) ) : '';
		$parsed_url        = wp_parse_url( $raw_request_url ) ?: array();
		$domain            = isset( $parsed_url['host'] ) ? sanitize_text_field( $parsed_url['host'] ) : '';
		$path              = isset( $parsed_url['path'] ) ? sanitize_text_field( $parsed_url['path'] ) : '';
		$request_url       = $domain ? $domain . $path : '';
		$this->request_url = mb_substr( $request_url, 0, 190, 'UTF-8' );

		
		$query               = isset( $_POST['request_query'] ) ? sanitize_text_field( wp_unslash( $_POST['request_query'] ) ) : '';
		$this->request_query = mb_substr( $query, 0, 2048, 'UTF-8' );

		$this->is_cached = 1;
		$this->method    = ''; 

		
		
		$referrer_url       = isset( $_POST['referrer_url'] ) ? sanitize_text_field( wp_unslash( $_POST['referrer_url'] ) ) : '';
		$this->referrer_url = mb_substr( $referrer_url, 0, 190, 'UTF-8' );

		
		$referrer_query       = isset( $_POST['referrer_query'] ) ? sanitize_text_field( wp_unslash( $_POST['referrer_query'] ) ) : '';
		$this->referrer_query = mb_substr( $referrer_query, 0, 255, 'UTF-8' );

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : 'server-detected'; 
		if ( $ip_address === 'server-detected' ) {
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}
		$this->ip_address = mb_substr( $ip_address, 0, 45, 'UTF-8' );

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
