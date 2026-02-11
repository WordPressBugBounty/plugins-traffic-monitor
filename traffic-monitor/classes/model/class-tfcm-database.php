<?php
/**
 * File: /classes/model/class-tfcm-database.php
 *
 * Manages database schema creation, updates, and CRUD operations for the Traffic Monitor log.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;








class TFCM_Database {

	
	
	

	
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		
		
		$sql = 'CREATE TABLE ' . TFCM_BOTS_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
            name VARCHAR(64) NOT NULL,
			category VARCHAR(32),
            block TINYINT(1) DEFAULT 0,
			log_request TINYINT(1) DEFAULT 1,
        	total_requests BIGINT UNSIGNED DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY name (name)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		
		$sql = 'CREATE TABLE ' . TFCM_IP_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			ip_address VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
			first_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			times_seen BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY ip_address (ip_address)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		$sql = 'CREATE TABLE ' . TFCM_USER_AGENT_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			user_agent VARCHAR(512) NOT NULL DEFAULT 'missing',
			device VARCHAR(7),
			operating_system VARCHAR(32),
			browser VARCHAR(32),
			bot_name VARCHAR(64),
			bot_category VARCHAR(32),
			first_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			times_seen BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_agent (user_agent)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		$sql = 'CREATE TABLE ' . TFCM_FINGERPRINT_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			ip_id INT UNSIGNED NOT NULL,
			user_agent_id INT UNSIGNED NOT NULL,
			fingerprint_hash VARCHAR(32) NOT NULL,
			first_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			times_seen BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (id),
			KEY ip_id (ip_id),
			KEY user_agent_id (user_agent_id),
			UNIQUE KEY fingerprint_hash (fingerprint_hash)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		$sql = 'CREATE TABLE ' . TFCM_REQUESTED_PAGES_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			request_url VARCHAR(190) NOT NULL DEFAULT '',
			first_requested TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_requested TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			times_requested BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY request_url (request_url)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		$sql = 'CREATE TABLE ' . TFCM_REFERRER_PAGES_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			referrer_url VARCHAR(190) NOT NULL DEFAULT '',
			first_requested TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_requested TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			times_requested BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY referrer_url (referrer_url)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();

		
		
		$sql = 'CREATE TABLE ' . TFCM_PAGE_REQUESTS_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
			page_id INT UNSIGNED NOT NULL,
			request_query VARCHAR(2048),
			is_cached TINYINT(1),
			method VARCHAR(10) DEFAULT 'GET',
			referrer_id INT UNSIGNED NOT NULL,
			referrer_query VARCHAR(255),
			user_role VARCHAR(50),
			fingerprint_id INT UNSIGNED NOT NULL,
			session_id_hash CHAR(32),
			ad_platform VARCHAR(32),
			click_key VARCHAR(32),
			click_value VARCHAR(128),
			source VARCHAR(64),
			status_code SMALLINT,
			PRIMARY KEY (id),
			KEY page_id (page_id),
			KEY referrer_id (referrer_id),
			KEY fingerprint_id (fingerprint_id),
			KEY session_id_hash (session_id_hash),
			KEY request_time (request_time)
		) $charset_collate;";
		
		dbDelta( $sql );
		$wpdb->show_errors();
	}

	
	public static function ensure_tables_exist() {
		global $wpdb;

		
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', TFCM_IP_TABLE )
		);

		if ( null === $table_exists ) {
			self::create_tables();
		}
	}

	
	public static function table_exists( $table_name ) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);
	}

	
	public static function maybe_upgrade_bot_name_column() {
		global $wpdb;

		
		$bots_name_info = $wpdb->get_row( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', TFCM_BOTS_TABLE, 'name' ), ARRAY_A );

		if ( isset( $bots_name_info['Type'] ) && strtolower( $bots_name_info['Type'] ) === 'varchar(32)' ) {
			
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i MODIFY name VARCHAR(64) NOT NULL', TFCM_BOTS_TABLE ) );
		}

		
		$user_agent_bot_name_info = $wpdb->get_row( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', TFCM_USER_AGENT_TABLE, 'bot_name' ), ARRAY_A );

		if ( isset( $user_agent_bot_name_info['Type'] ) && strtolower( $user_agent_bot_name_info['Type'] ) === 'varchar(32)' ) {
			
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i MODIFY bot_name VARCHAR(64) NOT NULL', TFCM_USER_AGENT_TABLE ) );
		}
	}

	
	
	

	
	public static function get_single_request( $log_id ) {
		global $wpdb;

		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT 
				pr.id,
				pr.request_time,
				rp.request_url,
				pr.request_query,
				pr.is_cached,
				pr.method,
				rf.referrer_url,
				pr.referrer_query,
				pr.user_role,
				ip.ip_address,
				fp.fingerprint_hash,
				pr.session_id_hash,
				ua.user_agent,
				ua.device,
				ua.operating_system,
				ua.browser,
				ua.bot_name,
				ua.bot_category,
				pr.ad_platform,
				pr.click_key,
				pr.click_value,
				pr.source,
				pr.status_code
			FROM %i pr
			INNER JOIN %i rp ON pr.page_id = rp.id
			LEFT JOIN %i rf ON pr.referrer_id = rf.id
			LEFT JOIN %i fp ON pr.fingerprint_id = fp.id
			LEFT JOIN %i ip ON fp.ip_id = ip.id
			LEFT JOIN %i ua ON fp.user_agent_id = ua.id
			WHERE pr.id = %d',
				TFCM_PAGE_REQUESTS_TABLE,
				TFCM_REQUESTED_PAGES_TABLE,
				TFCM_REFERRER_PAGES_TABLE,
				TFCM_FINGERPRINT_TABLE,
				TFCM_IP_TABLE,
				TFCM_USER_AGENT_TABLE,
				$log_id
			),
			ARRAY_A
		);

		return $log;
	}

	
	public static function get_selected_requests( $log_ids ) {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return array();
		}

		
		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge(
			array( TFCM_PAGE_REQUESTS_TABLE, TFCM_REQUESTED_PAGES_TABLE, TFCM_REFERRER_PAGES_TABLE, TFCM_FINGERPRINT_TABLE, TFCM_IP_TABLE, TFCM_USER_AGENT_TABLE ),
			$log_ids
		);

		
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pr.id,
				pr.request_time,
				rp.request_url,
				pr.request_query,
				pr.is_cached,
				pr.method,
				rf.referrer_url,
				pr.referrer_query,
				pr.user_role,
				ip.ip_address,
				fp.fingerprint_hash,
				pr.session_id_hash,
				ua.user_agent,
				ua.device,
				ua.operating_system,
				ua.browser,
				ua.bot_name,
				ua.bot_category,
				pr.ad_platform,
				pr.click_key,
				pr.click_value,
				pr.source,
				pr.status_code
			FROM %i pr
			INNER JOIN %i rp ON pr.page_id = rp.id
			LEFT JOIN %i rf ON pr.referrer_id = rf.id
			LEFT JOIN %i fp ON pr.fingerprint_id = fp.id
			LEFT JOIN %i ip ON fp.ip_id = ip.id
			LEFT JOIN %i ua ON fp.user_agent_id = ua.id
			WHERE pr.id IN ( $placeholders )",
				$prepare_values
			),
			ARRAY_A
		);
		
	}

	
	public static function get_all_requests() {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT pr.id,
				pr.request_time,
				rp.request_url,
				pr.request_query,
				pr.is_cached,
				pr.method,
				rf.referrer_url,
				pr.referrer_query,
				pr.user_role,
				ip.ip_address,
				fp.fingerprint_hash,
				pr.session_id_hash,
				ua.user_agent,
				ua.device,
				ua.operating_system,
				ua.browser,
				ua.bot_name,
				ua.bot_category,
				pr.ad_platform,
				pr.click_key,
				pr.click_value,
				pr.source,
				pr.status_code
			FROM %i pr
			INNER JOIN %i rp ON pr.page_id = rp.id
			LEFT JOIN %i rf ON pr.referrer_id = rf.id
			LEFT JOIN %i fp ON pr.fingerprint_id = fp.id
			LEFT JOIN %i ip ON fp.ip_id = ip.id
			LEFT JOIN %i ua ON fp.user_agent_id = ua.id',
				TFCM_PAGE_REQUESTS_TABLE,
				TFCM_REQUESTED_PAGES_TABLE,
				TFCM_REFERRER_PAGES_TABLE,
				TFCM_FINGERPRINT_TABLE,
				TFCM_IP_TABLE,
				TFCM_USER_AGENT_TABLE
			),
			ARRAY_A
		);
	}

	
	public static function count_all_requests() {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', TFCM_PAGE_REQUESTS_TABLE )
		);
	}

	
	public static function get_request_table_rows( $search_term, $orderby_sql, $per_page, $offset, $filters = array() ) {
		global $wpdb;

		$table_clauses = array(
			TFCM_PAGE_REQUESTS_TABLE,
			TFCM_REQUESTED_PAGES_TABLE,
			TFCM_REFERRER_PAGES_TABLE,
			TFCM_FINGERPRINT_TABLE,
			TFCM_IP_TABLE,
			TFCM_USER_AGENT_TABLE,
		);

		$where_clauses = array();
		$params        = array();

		
		if ( $search_term ) {
			$where_clauses[] = '(
				pr.request_time LIKE %s OR
				rp.request_url LIKE %s OR
				pr.request_query LIKE %s OR
				rf.referrer_url LIKE %s OR
				pr.user_role LIKE %s OR
				ua.operating_system LIKE %s OR
				ua.browser LIKE %s OR
				ip.ip_address LIKE %s OR
				fp.fingerprint_hash LIKE %s OR
				ua.bot_name LIKE %s OR
				ua.bot_category LIKE %s OR
				ua.user_agent LIKE %s OR
				pr.ad_platform LIKE %s OR
				pr.click_key LIKE %s OR
				pr.click_value LIKE %s OR
				pr.source LIKE %s
			)';
			$params          = array_merge( $params, array_fill( 0, 16, $search_term ) );
		}

		
		if ( isset( $filters['user_role'] ) ) {
			if ( '__empty__' === $filters['user_role'] ) {
				$where_clauses[] = '(pr.user_role IS NULL OR pr.user_role = "")';
			} elseif ( '' !== $filters['user_role'] ) {
				$where_clauses[] = 'pr.user_role = %s';
				$params[]        = $filters['user_role'];
			}
		}

		if ( isset( $filters['bot_name'] ) ) {
			if ( '__empty__' === $filters['bot_name'] ) {
				$where_clauses[] = '(ua.bot_name IS NULL OR ua.bot_name = "")';
			} elseif ( '' !== $filters['bot_name'] ) {
				$where_clauses[] = 'ua.bot_name = %s';
				$params[]        = $filters['bot_name'];
			}
		}

		if ( isset( $filters['bot_category'] ) ) {
			if ( '__empty__' === $filters['bot_category'] ) {
				$where_clauses[] = '(ua.bot_category IS NULL OR ua.bot_category = "")';
			} elseif ( '' !== $filters['bot_category'] ) {
				$where_clauses[] = 'ua.bot_category = %s';
				$params[]        = $filters['bot_category'];
			}
		}

		if ( isset( $filters['ad_platform'] ) ) {
			if ( '__empty__' === $filters['ad_platform'] ) {
				$where_clauses[] = '(pr.ad_platform IS NULL OR pr.ad_platform = "")';
			} elseif ( '' !== $filters['ad_platform'] ) {
				$where_clauses[] = 'pr.ad_platform = %s';
				$params[]        = $filters['ad_platform'];
			}
		}

		$where_sql = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$params[] = $per_page;
		$params[] = $offset;

		$sql = "
			SELECT pr.id,
				pr.request_time,
				rp.request_url,
				pr.request_query,
				pr.is_cached,
				pr.method,
				rf.referrer_url,
				pr.referrer_query,
				pr.user_role,
				ip.ip_address,
				fp.fingerprint_hash,
				pr.session_id_hash,
				ua.user_agent,
				ua.device,
				ua.operating_system,
				ua.browser,
				ua.bot_name,
				ua.bot_category,
				pr.ad_platform,
				pr.click_key,
				pr.click_value,
				pr.source,
				pr.status_code
			FROM %i pr
			INNER JOIN %i rp ON pr.page_id = rp.id
			LEFT JOIN %i rf ON pr.referrer_id = rf.id
			LEFT JOIN %i fp ON pr.fingerprint_id = fp.id
			LEFT JOIN %i ip ON fp.ip_id = ip.id
			LEFT JOIN %i ua ON fp.user_agent_id = ua.id
			$where_sql
			ORDER BY $orderby_sql
			LIMIT %d OFFSET %d
		";

		
		return $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $table_clauses, $params ) ), ARRAY_A );
	}

	
	public static function count_request_table_rows( $search_term, $filters = array() ) {
		global $wpdb;

		$table_clauses = array(
			TFCM_PAGE_REQUESTS_TABLE,
			TFCM_REQUESTED_PAGES_TABLE,
			TFCM_REFERRER_PAGES_TABLE,
			TFCM_FINGERPRINT_TABLE,
			TFCM_IP_TABLE,
			TFCM_USER_AGENT_TABLE,
		);

		$where_clauses = array();
		$params        = array();

		if ( $search_term ) {
			$where_clauses[] = '(
				pr.request_time LIKE %s OR
				rp.request_url LIKE %s OR
				pr.request_query LIKE %s OR
				rf.referrer_url LIKE %s OR
				pr.user_role LIKE %s OR
				ip.ip_address LIKE %s OR
				fp.fingerprint_hash LIKE %s OR
				ua.bot_name LIKE %s OR
				ua.bot_category LIKE %s OR
				pr.ad_platform LIKE %s
			)';
			$params          = array_merge( $params, array_fill( 0, 10, $search_term ) );
		}

		if ( isset( $filters['user_role'] ) ) {
			if ( '__empty__' === $filters['user_role'] ) {
				$where_clauses[] = '(pr.user_role IS NULL OR pr.user_role = "")';
			} elseif ( '' !== $filters['user_role'] ) {
				$where_clauses[] = 'pr.user_role = %s';
				$params[]        = $filters['user_role'];
			}
		}

		if ( isset( $filters['bot_name'] ) ) {
			if ( '__empty__' === $filters['bot_name'] ) {
				$where_clauses[] = '(ua.bot_name IS NULL OR ua.bot_name = "")';
			} elseif ( '' !== $filters['bot_name'] ) {
				$where_clauses[] = 'ua.bot_name = %s';
				$params[]        = $filters['bot_name'];
			}
		}

		if ( isset( $filters['bot_category'] ) ) {
			if ( '__empty__' === $filters['bot_category'] ) {
				$where_clauses[] = '(ua.bot_category IS NULL OR ua.bot_category = "")';
			} elseif ( '' !== $filters['bot_category'] ) {
				$where_clauses[] = 'ua.bot_category = %s';
				$params[]        = $filters['bot_category'];
			}
		}

		if ( isset( $filters['ad_platform'] ) ) {
			if ( '__empty__' === $filters['ad_platform'] ) {
				$where_clauses[] = '(pr.ad_platform IS NULL OR pr.ad_platform = "")';
			} elseif ( '' !== $filters['ad_platform'] ) {
				$where_clauses[] = 'pr.ad_platform = %s';
				$params[]        = $filters['ad_platform'];
			}
		}

		$where_sql = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$sql = "
			SELECT COUNT(*)
			FROM %i pr
			INNER JOIN %i rp ON pr.page_id = rp.id
			LEFT JOIN %i rf ON pr.referrer_id = rf.id
			LEFT JOIN %i fp ON pr.fingerprint_id = fp.id
			LEFT JOIN %i ip ON fp.ip_id = ip.id
			LEFT JOIN %i ua ON fp.user_agent_id = ua.id
			$where_sql
		";

		
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, array_merge( $table_clauses, $params ) ) );
	}

	
	public static function get_old_log_table_requests() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', TFCM_REQUEST_LOG_TABLE ), ARRAY_A );
	}

	
	public static function count_old_log_table_requests() {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', TFCM_REQUEST_LOG_TABLE ) );
	}

	
	public static function get_all_bots() {
		global $wpdb;

		$bots = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i',
				TFCM_BOTS_TABLE
			),
			ARRAY_A
		);

		
		usort(
			$bots,
			function ( $a, $b ) {
				return strlen( $b['name'] ) - strlen( $a['name'] );
			}
		);

		return $bots;
	}

	
	public static function get_recent_session_source( $session_id_hash ) {
		global $wpdb;
		
		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT source FROM %i WHERE session_id_hash = %s AND LENGTH(source) > 0 ORDER BY request_time DESC LIMIT 1',
				array( TFCM_PAGE_REQUESTS_TABLE, $session_id_hash )
			)
		);
	}

	
	
	

	
	public static function insert_request( $request ) {
		global $wpdb;

		
		if ( strpos( get_site_url(), 'localhost' ) !== false ) {
			self::ensure_tables_exist();
		}

		$wpdb->query( 'START TRANSACTION' ); 

		try {
			
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (ip_address, first_seen, last_seen, times_seen)
            VALUES (%s, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), times_seen = times_seen + 1',
					TFCM_IP_TABLE,
					$request->ip_address
				)
			);
			$ip_id = $wpdb->insert_id ?: $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE ip_address = %s', TFCM_IP_TABLE, $request->ip_address ) );

			
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (user_agent, device, operating_system, browser, bot_name, bot_category, first_seen, last_seen, times_seen)
            VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), times_seen = times_seen + 1',
					TFCM_USER_AGENT_TABLE,
					$request->user_agent,
					$request->device,
					$request->operating_system,
					$request->browser,
					$request->bot_name,
					$request->bot_category
				)
			);
			$user_agent_id = $wpdb->insert_id ?: $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE user_agent = %s', TFCM_USER_AGENT_TABLE, $request->user_agent ) );

			
			$fingerprint_hash = $request->fingerprint_hash;
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (ip_id, user_agent_id, fingerprint_hash, first_seen, last_seen, times_seen)
            VALUES (%d, %d, %s, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), times_seen = times_seen + 1',
					TFCM_FINGERPRINT_TABLE,
					$ip_id,
					$user_agent_id,
					$fingerprint_hash
				)
			);
			$fingerprint_id = $wpdb->insert_id ?: $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE fingerprint_hash = %s', TFCM_FINGERPRINT_TABLE, $fingerprint_hash ) );

			
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (request_url, first_requested, last_requested, times_requested)
            VALUES (%s, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_requested = NOW(), times_requested = times_requested + 1',
					TFCM_REQUESTED_PAGES_TABLE,
					$request->request_url
				)
			);
			$page_id = $wpdb->insert_id ?: $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE request_url = %s', TFCM_REQUESTED_PAGES_TABLE, $request->request_url ) );

			
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (referrer_url, first_requested, last_requested, times_requested)
            VALUES (%s, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_requested = NOW(), times_requested = times_requested + 1',
					TFCM_REFERRER_PAGES_TABLE,
					$request->referrer_url
				)
			);
			$referrer_id = $wpdb->insert_id ?: $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE referrer_url = %s', TFCM_REFERRER_PAGES_TABLE, $request->referrer_url ) );

			
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (
						request_time,
						page_id,
						request_query,
						is_cached,
						method,
						referrer_id,
						referrer_query,
						user_role,
						fingerprint_id,
						session_id_hash,
						ad_platform,
						click_key,
						click_value,
						source,
						status_code
					) VALUES (
						NOW(), %d, %s, %d, %s, %d, %s, %s, %d, %s, %s, %s, %s, %s, %d
					)',
					TFCM_PAGE_REQUESTS_TABLE,
					$page_id,
					$request->request_query,
					$request->is_cached ? 1 : 0,
					$request->method,
					$referrer_id,
					$request->referrer_query,
					$request->user_role,
					$fingerprint_id,
					$request->session_id_hash,
					$request->ad_platform,
					$request->click_key,
					$request->click_value,
					$request->source,
					$request->status_code
				)
			);

			$wpdb->query( 'COMMIT' ); 

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' ); 
			error_log( 'Traffic Monitor: Database Insert Error: ' . $e->getMessage() . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		}
	}

	
	public static function add_bot_entry( $name, $category, $block = 0, $log_request = 1 ) {
		global $wpdb;

		
		$existing_bot = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `name` FROM %i WHERE `name` = %s',
				TFCM_BOTS_TABLE,
				$name
			)
		);

		if ( ! $existing_bot ) {
			$wpdb->insert(
				TFCM_BOTS_TABLE,
				array(
					'name'        => $name,
					'category'    => $category,
					'block'       => (int) $block,
					'log_request' => (int) $log_request,
				),
				array( '%s', '%s', '%d', '%d' )
			);

			
			$bot_list = get_transient( 'tfcm_all_bots' );
			if ( ! is_array( $bot_list ) ) {
				
				$bot_list = self::get_all_bots();
			}

			
			$bot_list[] = array(
				'name'        => $name,
				'category'    => $category,
				'block'       => (int) $block,
				'log_request' => (int) $log_request,
			);

			
			usort(
				$bot_list,
				function ( $a, $b ) {
					return strlen( $b['name'] ) - strlen( $a['name'] );
				}
			);

			
			set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
		}
	}

	
	public static function increment_bot_total_requests( $name ) {
		global $wpdb;
		
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET total_requests = total_requests + 1 WHERE `name` = %s',
				TFCM_BOTS_TABLE,
				$name
			)
		);
	}

	
	public static function update_bot_block_status( $name, $block ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET block = %d WHERE `name` = %s',
				TFCM_BOTS_TABLE,
				$block,
				$name
			)
		);

		
		$bot_list = get_transient( 'tfcm_all_bots' );
		if ( ! is_array( $bot_list ) ) {
			
			$bot_list = self::get_all_bots();
		}

		foreach ( $bot_list as &$bot_record ) {
			if ( $bot_record['name'] === $name ) {
				$bot_record['block'] = (int) $block;
				break; 
			}
		}
		set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
	}

	
	public static function update_bot_log_request( $name, $log_request ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET log_request = %d WHERE `name` = %s',
				TFCM_BOTS_TABLE,
				$log_request,
				$name
			)
		);

		
		$bot_list = get_transient( 'tfcm_all_bots' );
		if ( ! is_array( $bot_list ) ) {
			
			$bot_list = self::get_all_bots();
		}

		foreach ( $bot_list as &$bot_record ) {
			if ( $bot_record['name'] === $name ) {
				$bot_record['log_request'] = (int) $log_request;
				break; 
			}
		}
		set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
	}

	
	public static function import_bots_from_csv() {
		global $wpdb, $wp_filesystem;

		
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		
		if ( ! file_exists( TFCM_BOTS_CSV_PATH ) ) {
			return;
		}

		
		$existing_bots = $wpdb->get_col( $wpdb->prepare( 'SELECT `name` FROM %i', TFCM_BOTS_TABLE ) );

		
		$existing_bots = array_map( 'strtolower', $existing_bots );

		$new_bots = array();
		$values   = array();

		
		$file_contents = $wp_filesystem->get_contents( TFCM_BOTS_CSV_PATH );
		if ( false === $file_contents ) {
			return;
		}

		$lines = explode( "\n", $file_contents );

		foreach ( $lines as $line ) {
			$data = str_getcsv( $line );
			if ( empty( $data ) || ! isset( $data[0] ) ) {
				continue;
			}

			
			if ( strpos( strtolower( $data[0] ), 'name' ) !== false ) {
				continue;
			}

			$name        = trim( strtolower( $data[0] ) );
			$category    = isset( $data[1] ) ? trim( $data[1] ) : '';
			$block       = isset( $data[2] ) ? (int) $data[2] : 0;
			$log_request = isset( $data[3] ) ? (int) $data[3] : 1;

			
			if ( in_array( $name, $existing_bots, true ) ) {
				continue;
			}

			$new_bots[] = '( %s, %s, %d, %d, %d )'; 
			$values     = array_merge( $values, array( $name, $category, $block, $log_request, 0 ) );

		}

		
		if ( ! empty( $new_bots ) ) {
			$placeholders   = implode( ', ', $new_bots );
			$prepare_values = array_merge( array( TFCM_BOTS_TABLE ), $values );
			
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i (`name`, category, `block`, log_request, total_requests) VALUES $placeholders",
					$prepare_values
				)
			);
			
		}
	}

	
	
	

	
	public static function delete_tables() {
		global $wpdb;

		$tables = array(
			TFCM_PAGE_REQUESTS_TABLE,
			TFCM_REQUESTED_PAGES_TABLE,
			TFCM_REFERRER_PAGES_TABLE,
			TFCM_FINGERPRINT_TABLE,
			TFCM_IP_TABLE,
			TFCM_USER_AGENT_TABLE,
			TFCM_BOTS_TABLE,
		);

		foreach ( $tables as $table ) {
			
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );

			if ( $wpdb->last_error ) {
				error_log( 'Traffic Monitor: Failed to delete table ' . $table . ': ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
			} else {
				error_log( 'Traffic Monitor: Successfully deleted table ' . $table . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
			}
		}
	}

	
	public static function delete_old_log_table() {
		global $wpdb;

		
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', TFCM_REQUEST_LOG_TABLE ) );

		if ( $wpdb->last_error ) {
			error_log( 'Traffic Monitor: Failed to delete old request log table: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		} else {
			
		}
	}

	
	public static function delete_selected_requests( $log_ids ) {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return false;
		}

		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_PAGE_REQUESTS_TABLE ), $log_ids );

		
		$deleted_rows = $wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE id IN ( $placeholders )", $prepare_values ) );

		if ( false === $deleted_rows ) {
			error_log( 'Traffic Monitor: Database Delete Error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
			return false;
		}

		
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE id NOT IN (SELECT DISTINCT page_id FROM %i)',
				TFCM_REQUESTED_PAGES_TABLE,
				TFCM_PAGE_REQUESTS_TABLE
			)
		);

		
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE id NOT IN (SELECT DISTINCT referrer_id FROM %i)',
				TFCM_REFERRER_PAGES_TABLE,
				TFCM_PAGE_REQUESTS_TABLE
			)
		);

		
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE id NOT IN (SELECT DISTINCT fingerprint_id FROM %i)',
				TFCM_FINGERPRINT_TABLE,
				TFCM_PAGE_REQUESTS_TABLE
			)
		);

		
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE id NOT IN (SELECT DISTINCT ip_id FROM %i)',
				TFCM_IP_TABLE,
				TFCM_FINGERPRINT_TABLE
			)
		);

		
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE id NOT IN (SELECT DISTINCT user_agent_id FROM %i)',
				TFCM_USER_AGENT_TABLE,
				TFCM_FINGERPRINT_TABLE
			)
		);

		return $deleted_rows;
	}

	
	public static function delete_all_requests() {
		global $wpdb;

		
		$deleted_rows = $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_PAGE_REQUESTS_TABLE ) );

		if ( false === $deleted_rows ) {
			error_log( 'Traffic Monitor: Database Delete Error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
			return false;
		}

		
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_REQUESTED_PAGES_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_REFERRER_PAGES_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_FINGERPRINT_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_IP_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_USER_AGENT_TABLE ) );

		return $deleted_rows;
	}
}
