<?php
/**
 * File: /classes/controller/class-tfcm-export-manager.php
 *
 * Handles the creation and deletion of export files (CSV) for Traffic Monitor.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


class TFCM_Export_Manager {
	
	private static $export_dir = TFCM_PLUGIN_DIR . 'exports/';

	
	public static function delete_old_exports() {
		$find_files = glob( self::$export_dir . '*traffic-log-*.csv' );
		$files      = $find_files ? $find_files : array();
		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}
	}

	
	public static function generate_csv( $rows, $file_name, $total_rows, $return_on_update = false ) {
		global $wp_filesystem;

		if ( empty( $rows ) ) {
			wp_send_json_error( array( 'message' => 'No matching records found.' ), 400 );
		}

		
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		
		if ( ! $wp_filesystem ) {
			wp_send_json_error( array( 'message' => 'File system access error.' ), 400 );
		}

		
		$csv_content = implode( ',', array_keys( $rows[0] ) ) . "\n"; 
		foreach ( $rows as $row ) {
			$csv_content .= implode( ',', array_map( array( __CLASS__, 'escape_csv_value' ), $row ) ) . "\n";
		}

		$file_path = self::$export_dir . $file_name;
		$file_url  = plugins_url( 'exports/' . $file_name, TFCM_PLUGIN_FILE );

		
		if ( ! $wp_filesystem->put_contents( $file_path, $csv_content, FS_CHMOD_FILE ) ) {
			wp_send_json_error( array( 'message' => 'Failed to create the export file.' ), 400 );
		}

		if ( $return_on_update ) {
			return true;
		} else {
			wp_send_json_success( array( 'message' => 'Total records exported: ' . $total_rows . ' <a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener noreferrer">Download CSV</a>' ), 200 );
		}
	}

	
	private static function escape_csv_value( $value ) {
		return '"' . str_replace( '"', '""', (string) $value ) . '"';
	}
}
