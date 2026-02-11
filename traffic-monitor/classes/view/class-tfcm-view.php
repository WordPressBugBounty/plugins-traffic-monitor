<?php
/**
 * File: /classes/view/class-tfcm-view.php
 *
 * Handles the display of admin notices, log details, and the overall Traffic Monitor admin page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_View {
	
	public static function register_hooks() {
		add_action( 'in_admin_header', array( __CLASS__, 'add_custom_header' ) );
	}

	
	public static function add_custom_header() {
		
		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && 'toplevel_page_traffic-monitor' === $current_screen->id ) {

			echo '<div class="tfcm-header">
				<div class="tfcm-logo"> 
					<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">
						<img src="' . esc_url( plugins_url( 'assets/images/tfcm-logo-40x40.png', TFCM_PLUGIN_FILE ) ) . '" id="tfcm-logo-40x40">
					</a>
				</div>
				<h1 class="tfcm-logo-text">Traffic Monitor</h1>
			</div>';
		}
	}

	
	public static function display_notice( $message, $type = 'info' ) {
		$allowed_types = array( 'success', 'error', 'warning', 'info' );
		$type          = in_array( $type, $allowed_types, true ) ? $type : 'info';

		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	
	public static function display_back_button() {
		printf(
			'<p><a href="%s" class="button button-primary">Back to Log Table</a></p>',
			esc_url( admin_url( 'admin.php?page=traffic-monitor' ) )
		);
	}

	
	public static function render_request_details( $log ) {
		?>
		<div class="wrap">
			<h2>Request Details</h2>
			<table class="tfcm-request-detail-table">
				<?php foreach ( $log as $key => $value ) : ?>
					<tr>
						<th><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></th>
						<td>
						<?php
						if ( 'is_cached' === $key ) {
							if ( 1 === $value ) {
								echo 'Yes';
							} else {
								echo 'No';
							}
						} else {
							echo esc_html( $value );
						}
						?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php self::display_back_button(); ?>
		</div>
		<?php
	}

	
	public static function render_admin_page( $tfcm_table ) {
		?>
	<div class="wrap">
		<div id="tfcm-notices-container"></div>
		<!-- Table Form (POST) -->
		<form method="post">
			<?php
			$tfcm_table->display();
			?>
		</form>
	</div>
		<?php
	}
}
