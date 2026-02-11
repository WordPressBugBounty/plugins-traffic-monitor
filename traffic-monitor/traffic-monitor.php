<?php
/**
 * Plugin Name: Traffic Monitor
 * Plugin URI: https://github.com/dmitrimartin817/traffic-monitor
 * Description: Lightweight traffic logger for WordPress analytics. View, filter, and export page request data; monitor caching; detect bots; and spot click fraud.
 * Version: 3.2.7
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Dmitri Martin
 * Author URI: https://www.linkedin.com/in/dmitriamartin/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Copyright:   2024 - Dmitri Martin
 * Text Domain: traffic-monitor
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || die;

global $wpdb;

$my_table_prefix = isset( $wpdb ) ? $wpdb->prefix : 'wp_'; 
define( 'TFCM_IP_TABLE', $my_table_prefix . 'tfcm_ip_addresses' );
define( 'TFCM_USER_AGENT_TABLE', $my_table_prefix . 'tfcm_user_agents' );
define( 'TFCM_FINGERPRINT_TABLE', $my_table_prefix . 'tfcm_fingerprints' );
define( 'TFCM_REQUESTED_PAGES_TABLE', $my_table_prefix . 'tfcm_requested_pages' );
define( 'TFCM_REFERRER_PAGES_TABLE', $my_table_prefix . 'tfcm_referrer_pages' );
define( 'TFCM_PAGE_REQUESTS_TABLE', $my_table_prefix . 'tfcm_page_requests' );
define( 'TFCM_BOTS_TABLE', $my_table_prefix . 'tfcm_bots' );


define( 'TRAFFIC_MONITOR_VERSION', '3.2.7' );
define( 'TFCM_PLUGIN_FILE', __FILE__ );
define( 'TFCM_PLUGIN_DIR', plugin_dir_path( TFCM_PLUGIN_FILE ) );
define( 'TFCM_BOTS_CSV_PATH', TFCM_PLUGIN_DIR . 'data/bots.csv' );
define( 'TFCM_BOT_CATEGORIES_CSV_PATH', TFCM_PLUGIN_DIR . 'data/bot-categories.csv' );


define( 'TFCM_REQUEST_LOG_TABLE', $my_table_prefix . 'tfcm_request_log' );


$GLOBALS['TFCM_BOT_BLOCKING']     = false;
$GLOBALS['TFCM_SKIP_BOT_LOGGING'] = false;


function tfcm_ensure_session_started() {
	
	if ( is_admin() ) {
		return;
	}
	if ( wp_doing_ajax() ) {
		return;
	}
	if ( wp_doing_cron() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( defined( 'WP_CLI' ) ) {
		return;
	}

	
	if ( ! headers_sent() && session_status() === PHP_SESSION_NONE ) {
		session_start();
	}
}
add_action( 'init', 'tfcm_ensure_session_started', 0 );


require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-lifecycle.php'; 
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-admin-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-assets.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-export-manager.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-log-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-plugin-links-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-abstract.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-ajax.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-http.php';


require_once TFCM_PLUGIN_DIR . 'classes/model/class-tfcm-database.php';


require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-help-tabs.php';
require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-log-table.php';
require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-view.php';


TFCM_Lifecycle::register_hooks();
TFCM_Assets::register_hooks();
TFCM_Plugin_Links_Controller::register_hooks();
TFCM_Request_Controller::register_hooks();
if ( is_admin() ) {
	TFCM_View::register_hooks();
	TFCM_Help_Tabs::register_hooks();
	TFCM_Admin_Controller::register_hooks();
}


function tfcm_handle_requests() {
	
	
	if ( isset( $_SERVER['REQUEST_METHOD'] ) &&
		! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
		! ( is_admin() ) &&
		! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) &&
		! ( defined( 'WP_CLI' ) ) &&
		! ( defined( 'DOING_CRON' ) && DOING_CRON ) &&
		! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) &&
		! ( isset( $_SERVER['HTTP_UPGRADE'] ) && 'websocket' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_UPGRADE'] ) ) ) ) ) {

		global $tfcm_request_type;
		$tfcm_request_type = 'HTTP';
		TFCM_Request_Controller::handle_request();
	}
}
add_action( 'init', 'tfcm_handle_requests' );
