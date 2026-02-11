<?php
/**
 * File: /classes/controller/class-tfcm-plugin-links-controller.php
 *
 * Manages the addition of plugin action and meta links on the WordPress Plugins page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Plugin_Links_Controller {

	
	public static function register_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( TFCM_PLUGIN_FILE ), array( self::class, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( self::class, 'add_meta_links' ), 10, 2 );
		add_action( 'admin_head', array( self::class, 'add_star_styles' ) );
	}

	
	public static function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	
	public static function add_meta_links( $links, $file ) {
		if ( plugin_basename( TFCM_PLUGIN_FILE ) === $file ) {
			$links[] = "<a href='https://wordpress.org/support/plugin/traffic-monitor/' target='_blank'>Support</a>";
			$links[] = "<a href='https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post' target='_blank' title='Leave a review'><i class='tfcm-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
		}
		return $links;
	}

	
	public static function add_star_styles() {
		global $pagenow;
		if ( $pagenow === 'plugins.php' ) { ?>
				<style>
					.tfcm-stars {
						display: inline-block;
						color: #ffb900;
						position: relative;
						top: 3px
					}

					.tfcm-stars svg {
						fill: #ffb900
					}

					.tfcm-stars svg:hover {
						fill: #ffb900
					}

					.tfcm-stars svg:hover ~ svg {
						fill: none
					}
				</style>
			<?php
		}
	}
}
