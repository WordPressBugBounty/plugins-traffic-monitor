<?php
/**
 * File: /classes/view/class-tfcm-help-tabs.php
 *
 * This file defines the TFCM_Help_Tabs class, which is responsible for adding help tabs
 * to the Traffic Monitor admin page in WordPress. These tabs provide users with guidance
 * on how to use the plugin, including instructions, bulk actions, search functionality,
 * column definitions, and troubleshooting information.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Help_Tabs {
	
	public static function register_hooks() {
		add_action( 'admin_head', array( __CLASS__, 'add_help_tab' ) );
	}

	
	public static function add_help_tab() {
		$screen = get_current_screen();

		
		if ( 'toplevel_page_traffic-monitor' !== $screen->id ) {
			return;
		}

		$instructions  = '<h3>Instructions and Use Cases</h3>';
		$instructions .= '<p>The Traffic Monitor plugin logs, manages, and analyzes page requests directly in your WordPress admin panel. It is useful for::</p>';
		$instructions .= '<ul>';
		$instructions .= '<li><strong>Debugging:</strong> Identify broken links, incorrect headers, or unexpected request behaviors.</li>';
		$instructions .= '<li><strong>Performance Monitoring:</strong> Track frequently accessed pages to optimize site speed and other improvements.</li>';
		$instructions .= '<li><strong>Security Analysis:</strong> Detect unusual traffic patterns, bot activity, and potential attacks (DDoS, brute force, etc.).</li>';
		$instructions .= '<li><strong>User Behavior Analysis:</strong> Analyze visitor sources, devices, operating systems, and browsers.</li>';
		$instructions .= '<li><strong>Click Fraud Detection:</strong> Identify multiple rapid clicks from the same IP and user agent combination..</li>';
		$instructions .= '</ul>';
		$instructions .= '<p>Click on the help tabs for detailed instructions on available features.</p>';

		$bulk_options  = '<h3>Bulk Actions</h3>';
		$bulk_options .= '<p><strong>Managing Selected Records:</strong> To apply actions to specific records, select them using the checkboxes, then choose an action from the dropdown.</p>';
		$bulk_options .= '<ul>';
		$bulk_options .= '<li><strong>Delete:</strong> Permanently removes selected log entries.</li>';
		$bulk_options .= '<li><strong>Export:</strong> Generates a downloadable CSV file of the selected logs.</li>';
		$bulk_options .= '</ul>';
		$bulk_options .= '<p><strong>Managing All Records:</strong> For bulk actions on all logs, use the buttons next to the bulk actions dropdown.</p>';
		$bulk_options .= '<ul>';
		$bulk_options .= '<li><strong>Delete All:</strong> Permanently removes all logs, including those not currently displayed.</li>';
		$bulk_options .= '<li><strong>Export All:</strong> Generates a CSV file with all logs, including those not currently displayed.</li>';
		$bulk_options .= '</ul>';

		$filter_search  = '<h3>Filtering Options</h3>';
		$filter_search .= '<p>You can narrow down the log table using filters above the table. These dropdown filters let you isolate specific user roles, bot names, bot categories, and ad platforms.</p>';
		$filter_search .= '<p><strong>Tip:</strong> You can apply one or more filters, and then use the search box to drill down further. This is helpful for identifying patterns, diagnosing issues, or spotting click fraud.</p>';

		$filter_search .= '<h3>Search Fields</h3>';
		$filter_search .= '<p>The search box above the log table scans the following fields:</p>';
		$filter_search .= '<ul>';
		$filter_search .= '<li><strong>Date:</strong> Request date/time (YYYY-MM-DD HH:MM:SS format).</li>';
		$filter_search .= '<li><strong>Page Requested:</strong> The requested page path (excluding query string).</li>';
		$filter_search .= '<li><strong>Request Query String:</strong> Query string values submitted with the request.</li>';
		$filter_search .= '<li><strong>Prior Page:</strong> The referring page that linked to your site.</li>';
		$filter_search .= '<li><strong>User Role:</strong> WordPress role of the visitor (administrator, subscriber, visitor, etc.).</li>';
		$filter_search .= '<li><strong>System:</strong> The user’s operating system name.</li>';
		$filter_search .= '<li><strong>Browser:</strong> The user’s browser name.</li>';
		$filter_search .= '<li><strong>IP Address:</strong> Visitor’s last known IP.</li>';
		$filter_search .= '<li><strong>Fingerprint:</strong> Unique string for a visitor based on IP and user agent.</li>';
		$filter_search .= '<li><strong>Bot Name:</strong> Detected bot identifier, if available.</li>';
		$filter_search .= '<li><strong>Bot Category:</strong> The category of bot behavior (example: Search Engine Crawler).</li>';
		$filter_search .= '<li><strong>User Agent:</strong> The description of the software used to make the request..</li>';
		$filter_search .= '<li><strong>Ad Platform:</strong> Advertising source of the request (example: Google Ads).</li>';
		$filter_search .= '<li><strong>Click Key:</strong> The query parameter used by the advertising platform to track the click.</li>';
		$filter_search .= '<li><strong>Click Value:</strong> The unique identifier assigned to the ad click.</li>';
		$filter_search .= '<li><strong>Source:</strong> The original source for the visitor.</li>';
		$filter_search .= '</ul>';

		$columns = '<h3>Column Descriptions</h3>';

		$columns .= '<p><strong>Primary Request Data</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Date (request_time):</strong> The timestamp of the request.</li>';
		$columns .= '<li><strong>Page Requested (request_url):</strong> The path (excluding query string) of the requested page.</li>';
		$columns .= '<li><strong>Page Query (request_query):</strong> The query string of the requested page URL.</li>';
		$columns .= '<li><strong>Cached (is_cached):</strong> Whether the page was served from cache instead of handled by WordPress.</li>';
		$columns .= '<li><strong>Method (method):</strong> The HTTP request method (GET, POST, etc.).</li>';
		$columns .= '<li><strong>Referring Page (referrer_url):</strong> The path (excluding query string) of the referring page.</li>';
		$columns .= '<li><strong>Referring Query (referrer_query):</strong> The query string of the referring page URL.</li>';
		$columns .= '</ul>';

		$columns .= '<p><strong>User Information</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>User Role (user_role):</strong> The WordPress role for the user (admin, editor, subscriber, etc.).</li>';
		$columns .= '<li><strong>IP Address (ip_address):</strong> The last known public IP address of the user.</li>';
		$columns .= '<li><strong>Fingerprint (fingerprint_hash):</strong> A string of characters representing a unique combination of IP address and user agent.</li>';
		$columns .= '<li><strong>Session Hash (session_id_hash):</strong> The id of the user’s browsing session hashed for security.</li>';
		$columns .= '</ul>';

		$columns .= '<p><strong>Device Information</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Device (device):</strong> The user’s device type (example: Mobile, Tablet, Desktop). Parsed from User Agent.</li>';
		$columns .= '<li><strong>System (operating_system):</strong> The user’s operating system name (example: Windows, macOS, Linux, Android, iOS). Parsed from User Agent.</li>';
		$columns .= '<li><strong>Browser (browser):</strong> The user’s browser name (example: Chrome, Firefox, Safari). Parsed from User Agent.</li>';
		$columns .= '<li><strong>Bot Name (bot_name):</strong> The bot’s name if user is a known bot (example: googlebot). Parsed from User Agent.</li>';
		$columns .= '<li><strong>Bot Category (bot_category):</strong> A category describing what the bot does.</li>';
		$columns .= '<li><strong>User Agent (user_agent):</strong> The description of the software used to make the request.</li>';
		$columns .= '</ul>';

		$columns .= '<p><strong>Advertising Data</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Ad Platform (ad_platform):</strong> The name of the advertising platform that request directly came from (example: Google Ads).</li>';
		$columns .= '<li><strong>Click Key (click_key):</strong> The query parameter used by the advertising platform to track the click (example: <code>gclid</code>).</li>';
		$columns .= '<li><strong>Click ID (click_value):</strong> The unique identifier assigned to the ad click by the platform, extracted from the query string.</li>';
		$columns .= '</ul>';

		$columns .= '<p><strong>Source & Response Data</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Source (source):</strong> The original source for the visitor browsing your website (example: ad, bot, another website).</li>';
		$columns .= '<li><strong>Status Code (status_code):</strong> The HTTP response code returned for the request (example: 200, 404, 500).</li>';
		$columns .= '</ul>';

		$troubleshooting  = '<h3>Troubleshooting</h3>';
		$troubleshooting .= '<p>If Traffic Monitor isn’t logging requests as expected, check the following:</p>';
		$troubleshooting .= '<p><strong>Page Request Not Logged:</strong> </p>';
		$troubleshooting .= '<ul>';
		$troubleshooting .= '<li>The person or bot making the request may have Javascript disabled and the page may be cached.  Traffic Monitor can log cached pages but only if Javascript can run on the page once it is recieved.  All requests for cached pages will have the AJAX request type.</li>';
		$troubleshooting .= '<li>Some CDNs, name servers, or web hosts may block bots. To log bot traffic, turn off bot blocking.</li>';
		$troubleshooting .= '</ul>';
		$troubleshooting .= '<p><strong>Missing or Incorrect IP Addresses:</strong></p>';
		$troubleshooting .= '<ul>';
		$troubleshooting .= '<li>Services like Cloudflare and Sucuri act as proxies and may alter headers, masking real IPs</li>';
		$troubleshooting .= '<li>If Cloudflare is used, turn off proxy mode (gray cloud) in DNS settings to reveal visitor IPs.</li>';
		$troubleshooting .= '<li>Some visitors may insert fake IP addresses so their activity can’t be tracked.</li>';
		$troubleshooting .= '</ul>';
		$troubleshooting .= '<p>For additional support, <a href="mailto:dmitri@viablepress.com">dmitri@viablepress.com</a>.</p>';

		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_instructions',
				'title'   => 'Instructions',
				'content' => $instructions,
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_bulk_options',
				'title'   => 'Bulk Actions',
				'content' => $bulk_options,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_filter_search',
				'title'   => 'Filter & Search',
				'content' => $filter_search,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_columns',
				'title'   => 'Column Definitions',
				'content' => $columns,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_bot_categories',
				'title'   => 'Bot Detection',
				'content' => self::get_bot_categories_help(),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_ad_platforms',
				'title'   => 'Ad Click Tracking',
				'content' => self::get_ad_platforms_help(),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_troubleshooting',
				'title'   => 'Troubleshooting',
				'content' => $troubleshooting,
			)
		);
	}

	
	public static function get_bot_categories_help() {
		if ( ! file_exists( TFCM_BOT_CATEGORIES_CSV_PATH ) ) {
			return '<p>No bot category information available.</p>';
		}

		$lines = file( TFCM_BOT_CATEGORIES_CSV_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( empty( $lines ) ) {
			return '<p>No bot category information found in CSV.</p>';
		}

		$rows = array();
		foreach ( $lines as $index => $line ) {
			if ( $index === 0 ) {
				continue; 
			}
			$data = str_getcsv( $line );
			if ( isset( $data[0], $data[3] ) ) {
				$category    = esc_html( $data[0] );
				$description = esc_html( $data[3] );
				$rows[]      = "<li><strong>{$category}:</strong> {$description}</li>";
			}
		}

		if ( empty( $rows ) ) {
			return '<p>No categories found in the bot-categories file.</p>';
		}

		$html  = '<h3>Bots Detected</h3>';
		$html .= '<p>The plugin comes preinstalled with detection logic for a wide variety of known bots based on user agent string matching. This list includes common crawlers, scrapers, AI trainers, and more.</p>';
		$html .= '<p>New bots may be added over time through plugin updates to improve detection.</p>';
		$html .= '<h3>Bot Categories</h3>';
		$html .= '<p>The following categories describe the general purpose of each bot that may visit your site:</p>';
		$html .= '<ul>' . implode( '', $rows ) . '</ul>';

		return $html;
	}

	
	public static function get_ad_platforms_help() {
		$ad_keys = TFCM_Request_Abstract::get_supported_ad_keys();

		$html  = '<h3>Monitoring Ad Clicks</h3>';
		$html .= '<p>The plugin automatically detects and logs clicks from supported advertising platforms by inspecting the query string. You can filter the log table by <strong>Ad Platform</strong> to verify the number of clicks you’ve received from each platform and compare that to your ad spend.</p>';

		$html .= '<h3>Spotting Click Fraud</h3>';
		$html .= '<p>To identify suspicious click patterns:</p>';
		$html .= '<ul>';
		$html .= '<li>Filter by a specific <strong>Ad Platform</strong>.</li>';
		$html .= '<li>Then sort by <strong>Fingerprint</strong> — a hash combining the visitor’s IP address and user agent string. A fingerprint provides a more accurate way to identify unique users or bots than using IP or user agent alone.</li>';
		$html .= '<li>Look for multiple clicks from the same fingerprint within a short period from different click IDs (<strong>Note</strong>: multiple clicks with the same click ID may be caused by a user refreshing the page or the click ID being retained in the URL and re-sent as the user navigates your site. Ad platforms like Google Ads charge only once per click ID.)</li>';
		$html .= '</ul>';

		$html .= '<h3>Supported Ad Platforms &amp; Click Keys</h3>';
		$html .= '<p>A <strong>Click Key</strong> is the query parameter used by an advertising platform, and a <strong>Click ID</strong> is the unique value assigned to that key to identify a click on a specific ad (example: <code>gclid=50meRand0m5tr1ng</code>).</p>';
		$html .= '<p>The plugin detects and logs the following ad platforms and click keys:</p>';
		$html .= '<ul>';
		foreach ( $ad_keys as $key => $platform ) {
			$html .= '<li>' . esc_html( $platform ) . ' &mdash; <code>' . esc_html( $key ) . '</code></li>';
		}
		$html .= '</ul>';

		return $html;
	}
}
