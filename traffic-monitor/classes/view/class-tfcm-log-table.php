<?php
/**
 * File: /classes/view/class-tfcm-log-table.php
 *
 * Extends WP_List_Table to display Traffic Monitor log entries in the WordPress admin.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class TFCM_Log_Table extends WP_List_Table {
	
	public function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'request_time'     => 'Date',
			'request_url'      => 'Page Requested',
			'request_query'    => 'Request Query',
			'is_cached'        => 'Cached',
			'method'           => 'Method',
			'referrer_url'     => 'Referring Page',
			'referrer_query'   => 'Referring Query',
			'user_role'        => 'User Role',
			'ip_address'       => 'IP Address',
			'fingerprint_hash' => 'Fingerprint',
			'session_id_hash'  => 'Session Hash',
			'device'           => 'Device',
			'operating_system' => 'System',
			'browser'          => 'Browser',
			'bot_name'         => 'Bot Name',
			'bot_category'     => 'Bot Category',
			'user_agent'       => 'User Agent',
			'ad_platform'      => 'Ad Platform',
			'click_key'        => 'Click Key',
			'click_value'      => 'Click ID',
			'source'           => 'Source',
			'status_code'      => 'Response',
		);
		return $columns;
	}

	
	public function get_hidden_columns() {
		$user           = get_current_user_id();
		$screen         = get_current_screen();
		$hidden_columns = get_user_meta( $user, 'manage' . $screen->id . 'columnshidden', true );

		return is_array( $hidden_columns ) ? $hidden_columns : array();
	}

	
	public function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
			'export' => 'Export',
		);
		return $actions;
	}

	
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'top' === $which ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<div class="alignleft actions">
					<button type="button" id="tfcm-delete-all" class="button button-secondary">Delete All</button>
					<button type="button" id="tfcm-export-all" class="button button-secondary">Export All</button>
					<input type="hidden" name="page" value="traffic-monitor" />

					<div class="tfcm-filters" style="display: inline-block; margin-left: 10px;">
						<?php self::render_log_table_filters(); ?>
						<?php submit_button( 'Filter', '', '', false ); ?>
					</div>
				</div>
				<div class="alignright">
					<?php $this->search_box( 'Search', 'search_id' ); ?>
				</div>
			<?php elseif ( 'bottom' === $which ) : ?>
				<?php $this->pagination( $which ); ?>
			<?php endif; ?>
			<br class="clear" />
		</div>
		<?php
	}

	
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="element[]" value="%s" aria-label="Select row for %s" />',
			esc_attr( $item['id'] ),
			esc_attr( $item['id'] )
		);
	}

	
	public function column_default( $item, $column_name ) {
		if ( 'request_time' === $column_name ) {
			$view_url = add_query_arg(
				array(
					'page'               => 'traffic-monitor',
					'action'             => 'view_details',
					'id'                 => $item['id'],
					'tfcm_details_nonce' => wp_create_nonce( 'tfcm_details_nonce' ),
				),
				admin_url( 'admin.php' )
			);

			return sprintf(
				'%s <br><a href="%s">View Details</a>',
				esc_html( $item[ $column_name ] ),
				esc_url( $view_url )
			);
		}
		if ( 'is_cached' === $column_name ) {
			return ( $item[ $column_name ] ) ? 'Yes' : 'No';
		}
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	
	public function no_items() {
		echo 'No requests found.';
	}

	
	public function prepare_items() {
		global $wpdb;

		$columns  = $this->get_columns();
		$sortable = array();
		foreach ( array_keys( $columns ) as $column ) {
			if ( 'request_time' === $column ) {
				$sortable[ $column ] = array( $column, true );
			} elseif ( 'id' !== $column ) {
				$sortable[ $column ] = array( $column, false );
			}
		}

		$hidden                = get_hidden_columns( get_current_screen() );
		$primary               = 'request_time';
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		$orderby              = self::get_request_value( 'orderby' ) ?: $primary;
		$allowed_sort_columns = array_keys( $sortable );
		$orderby              = in_array( $orderby, $allowed_sort_columns, true ) ? esc_sql( $orderby ) : $primary;

		$allowed_sort_orders = array( 'ASC', 'DESC' );
		$order               = strtoupper( self::get_request_value( 'order' ) ) ?: 'DESC';
		$order               = in_array( strtoupper( $order ), $allowed_sort_orders, true ) ? strtoupper( $order ) : 'DESC';

		$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

		$search      = self::get_request_value( 's' );
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		$per_page     = absint( $this->get_items_per_page( 'tfcm_elements_per_page', 9 ) );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$filters = array(
			'user_role'    => self::get_request_value( 'tfcm_user_role' ),
			'bot_name'     => self::get_request_value( 'tfcm_bot_name' ),
			'bot_category' => self::get_request_value( 'tfcm_bot_category' ),
			'ad_platform'  => self::get_request_value( 'tfcm_ad_platform' ),
		);

		$this->items = TFCM_Database::get_request_table_rows( $search_term, $orderby_sql, $per_page, $offset, $filters );

		$total_items = TFCM_Database::count_request_table_rows( $search_term, $filters );
		

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	
	public static function get_request_value( $key ) {
		if ( isset( $_POST[ $key ] ) ) { 
			return sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); 
		} elseif ( isset( $_GET[ $key ] ) ) { 
			return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); 
		}
		return '';
	}

	
	public static function render_log_table_filters() {
		$screen = get_current_screen();
		if ( $screen->id !== 'toplevel_page_traffic-monitor' ) {
			return;
		}

		$current_role     = self::get_request_value( 'tfcm_user_role' );
		$current_bot      = self::get_request_value( 'tfcm_bot_name' );
		$current_cat      = self::get_request_value( 'tfcm_bot_category' );
		$current_platform = self::get_request_value( 'tfcm_ad_platform' );

		
		$roles = wp_roles()->get_names();
		$roles = array_merge( array( 'visitor' => 'Visitor' ), $roles );
		ksort( $roles );

		echo '<select name="tfcm_user_role">
		<option value="">All User Roles</option>
		<option value="__empty__" ' . selected( $current_role, '__empty__', false ) . '>No User Role</option>';

		foreach ( $roles as $role_key => $role_name ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $role_key ), selected( $current_role, $role_key, false ), esc_html( $role_name ) );
		}
		echo '</select>';

		
		
		$bot_list = get_transient( 'tfcm_all_bots' );
		if ( false === $bot_list ) {
			$bot_list = TFCM_Database::get_all_bots();
			set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
		}

		$bot_names = array_map( fn( $b ) => $b['name'], $bot_list );
		$bot_names = array_unique( $bot_names );
		sort( $bot_names );

		echo '<select name="tfcm_bot_name">
		<option value="">All Bot Names</option>
		<option value="__empty__" ' . selected( $current_bot, '__empty__', false ) . '>No Bot Found</option>';

		foreach ( $bot_names as $bot_name ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $bot_name ), selected( $current_bot, $bot_name, false ), esc_html( $bot_name ) );
		}
		echo '</select>';

		
		$categories = array();
		if ( file_exists( TFCM_BOT_CATEGORIES_CSV_PATH ) ) {
			$lines = file( TFCM_BOT_CATEGORIES_CSV_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			foreach ( $lines as $i => $line ) {
				if ( $i === 0 ) {
					continue; 
				}
				$data = str_getcsv( $line );
				if ( isset( $data[0] ) ) {
					$categories[] = $data[0];
				}
			}
		}
		$categories = array_unique( $categories );
		sort( $categories );

		echo '<select name="tfcm_bot_category">
		<option value="">All Bot Categories</option>
		<option value="__empty__" ' . selected( $current_cat, '__empty__', false ) . '>No Bot Category</option>';

		foreach ( $categories as $cat ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $cat ), selected( $current_cat, $cat, false ), esc_html( $cat ) );
		}
		echo '</select>';

		
		$platforms = array_values( array_unique( TFCM_Request_Abstract::get_supported_ad_keys() ) );
		sort( $platforms );

		echo '<select name="tfcm_ad_platform">
		<option value="">All Ad Platforms</option>
		<option value="__empty__" ' . selected( $current_platform, '__empty__', false ) . '>No Ad Platform</option>';

		foreach ( $platforms as $platform ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $platform ), selected( $current_platform, $platform, false ), esc_html( $platform ) );
		}
		echo '</select>';
	}

	
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args['total_items'] ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$current     = $this->get_pagenum();

		if ( $total_pages <= 1 ) {
			return;
		}

		$pagination_base = remove_query_arg( array( 'paged', '_wp_http_referer', '_wpnonce' ) );

		$add_args = array();
		
		foreach ( $_REQUEST as $key => $value ) {
			if ( in_array( $key, array( 's', 'tfcm_user_role', 'tfcm_bot_name', 'tfcm_bot_category', 'tfcm_ad_platform', 'orderby', 'order' ), true ) ) {
				$add_args[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		$page_links = array();

		$total_pages_before = '<span class="paging-input"><span class="tablenav-paging-text">';
		$total_pages_after  = '</span></span>';

		$page_links[] =
		'<span class="screen-reader-text">Current Page</span>' .
		sprintf(
			'<span id="table-paging" class="paging-input"><span class="tablenav-paging-text">%1$d of <span class="total-pages">%2$d</span></span></span>',
			$current,
			$total_pages
		);

		$page_links = array_merge(
			array( '<span class="pagination-links">' ),
			$this->get_navigation_arrow_links( $current, $total_pages, $pagination_base, $add_args ),
			array(
				$total_pages_before . implode( '', $page_links ) . $total_pages_after,
			),
			$this->get_navigation_arrow_links( $current, $total_pages, $pagination_base, $add_args, false ),
			array( '</span>' )
		);

		echo '<div class="tablenav-pages">';
		printf( '<span class="displaying-num">%s items</span>', esc_html( number_format_i18n( $total_items ) ) );
		foreach ( $page_links as $link ) {
			echo wp_kses_post( $link ) . ' ';
		}
		echo '</div>';
	}

	
	protected function get_navigation_arrow_links( $current, $total_pages, $base_url, $add_args, $prev = true ) {
		$output = array();

		if ( $prev ) {
			
			if ( $current > 1 ) {
				$output[] = sprintf(
					'<a class="first-page button" href="%s"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>',
					esc_url( add_query_arg( array_merge( $add_args, array( 'paged' => 1 ) ), $base_url ) )
				);
				$output[] = sprintf(
					'<a class="prev-page button" href="%s"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>',
					esc_url( add_query_arg( array_merge( $add_args, array( 'paged' => $current - 1 ) ), $base_url ) )
				);
			} else {
				$output[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
				$output[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
			}
		} elseif ( $current < $total_pages ) {
				$output[] = sprintf(
					'<a class="next-page button" href="%s"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>',
					esc_url( add_query_arg( array_merge( $add_args, array( 'paged' => $current + 1 ) ), $base_url ) )
				);
				$output[] = sprintf(
					'<a class="last-page button" href="%s"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>',
					esc_url( add_query_arg( array_merge( $add_args, array( 'paged' => $total_pages ) ), $base_url ) )
				);
		} else {
			$output[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
			$output[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
		}

		return $output;
	}
}