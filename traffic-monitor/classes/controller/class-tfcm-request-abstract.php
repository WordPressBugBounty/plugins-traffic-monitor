<?php
/**
 * File: /classes/controller/class-tfcm-request-abstract.php
 *
 * Abstract base class for capturing request metadata (headers, user agent, etc.).
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;


abstract class TFCM_Request_Abstract {
	public $request_time;
	public $request_url;
	public $request_query;
	public $is_cached;
	public $method;
	public $referrer_url;
	public $referrer_query;
	public $user_role;
	public $ip_address;
	public $session_id_hash;
	public $device;
	public $operating_system;
	public $browser;
	public $bot_name;
	public $bot_category;
	public $user_agent;
	public $fingerprint_hash;
	public $ad_platform;
	public $click_key;
	public $click_value;
	public $campaign_id;
	public $campaign_name;
	public $adgroup_id;
	public $adgroup_name;
	public $ad_id;
	public $keyword;
	public $source;
	public $status_code;

	
	public function __construct() {
		$this->request_time   = current_time( 'mysql' );
		$this->request_url    = ''; 
		$this->request_query  = ''; 
		$this->is_cached      = null; 
		$this->method         = ''; 
		$this->referrer_url   = ''; 
		$this->referrer_query = ''; 

		$user_role        = self::get_user_role();
		$this->user_role  = mb_substr( $user_role, 0, 50, 'UTF-8' );
		$this->ip_address = ''; 

		
		$session_id_hash       = session_id() ? md5( session_id() ) : '';
		$this->session_id_hash = mb_substr( $session_id_hash, 0, 32, 'UTF-8' );

		
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_write_close();
		}

		$data                   = self::get_user_agent_data();
		$this->device           = mb_substr( $data['device'], 0, 7, 'UTF-8' );
		$this->operating_system = mb_substr( $data['operating_system'], 0, 32, 'UTF-8' );
		$this->browser          = mb_substr( $data['browser'], 0, 32, 'UTF-8' );
		$this->bot_name         = mb_substr( $data['bot_name'], 0, 64, 'UTF-8' );
		$this->bot_category     = mb_substr( $data['bot_category'], 0, 32, 'UTF-8' );

		$user_agent       = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$this->user_agent = mb_substr( $user_agent, 0, 512, 'UTF-8' );

		$this->fingerprint_hash = ''; 
		$this->ad_platform      = ''; 
		$this->click_key        = ''; 
		$this->click_value      = ''; 
		$this->campaign_id      = ''; 
		$this->campaign_name    = ''; 
		$this->adgroup_id       = ''; 
		$this->adgroup_name     = ''; 
		$this->ad_id            = ''; 
		$this->keyword          = ''; 
		$this->source           = ''; 
		$this->status_code      = http_response_code();
	}

	
	private static function get_user_role() {
		$user_role = 'visitor';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( ! empty( $user->roles ) ) {
				$user_role = $user->roles[0];
			}
		}
		return $user_role;
	}

	
	private static function get_user_agent_data() {
		
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

		$device           = null;
		$operating_system = null;
		$browser          = null;
		$bot_name         = null;
		$bot_category     = null;

		$data = array(
			'device'           => $device,
			'operating_system' => $operating_system,
			'browser'          => $browser,
			'bot_name'         => $bot_name,
			'bot_category'     => $bot_category,
		);

		if ( ! $user_agent ) {
			return $data;
		}

		if ( stripos( $user_agent, 'Mobile' ) !== false ) {
			$device = 'Mobile';
		} elseif ( stripos( $user_agent, 'Tablet' ) !== false || stripos( $user_agent, 'iPad' ) !== false ) {
			$device = 'Tablet';
		} else {
			$device = 'Desktop';
		}

		
		if ( preg_match( '/\((.*?)\)/m', $user_agent, $parent_matches ) ) {
			preg_match_all(
				<<<'REGEX'
/(?P<operating_system>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|
(?:Open|Net|Free)BSD|Macintosh|Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|
(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?)(?:\ [^;]*)?(?:;|$)/imx
REGEX
				,
				$parent_matches[1],
				$result
			);
			if ( $result ) {
				$priority                   = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish' );
				$result['operating_system'] = array_unique( $result['operating_system'] );
				if ( count( $result['operating_system'] ) > 1 ) {
					$intersect        = array_intersect( $priority, $result['operating_system'] );
					$operating_system = ! empty( $intersect ) ? reset( $intersect ) : $result['operating_system'][0];
				} elseif ( isset( $result['operating_system'][0] ) ) {
					$operating_system = $result['operating_system'][0];
				}
			}
		}

		
		$operating_system_aliases = array(
			'linux-gnu' => 'Linux',
			'X11'       => 'Linux',
			'CrOS'      => 'Chrome OS',
			'Adr'       => 'Android',
		);
		$operating_system         = $operating_system_aliases[ $operating_system ] ?? $operating_system;

		if ( $operating_system === null && preg_match_all( '%(?P<operating_system>Android)[:/ ]%ix', $user_agent, $result ) ) {
			$operating_system = $result['operating_system'][0];
		}

		
		preg_match_all(
			<<<'REGEX'
%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|YaApp_Android|Whale|
NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)
%ix
REGEX,
			$user_agent,
			$result
		);

		if ( ! empty( $result['browser'] ) ) {
			$browser = reset( $result['browser'] );
		}

		$lower_browser = array_map( 'strtolower', $result['browser'] );

		$find = function ( $search, &$key = null, &$value = null ) use ( $lower_browser ) {
			$search = (array) $search;
			foreach ( $search as $val ) {
				$xkey = array_search( strtolower( $val ), $lower_browser, true );
				if ( $xkey !== false ) {
					$value = $val;
					$key   = $xkey;
					return true;
				}
			}
			return false;
		};

		$find_t = function ( array $search, &$key = null, &$value = null ) use ( $find ) {
			$value2 = null;
			if ( $find( array_keys( $search ), $key, $value2 ) ) {
				$value = $search[ $value2 ];
				return true;
			}
			return false;
		};

		$key = 0;
		$val = '';
		if ( $find_t(
			array(
				'OPR'                => 'Opera',
				'UCBrowser'          => 'UC Browser',
				'YaBrowser'          => 'Yandex',
				'YaApp_Android'      => 'Yandex',
				'Iceweasel'          => 'Firefox',
				'Icecat'             => 'Firefox',
				'CriOS'              => 'Chrome',
				'Edg'                => 'Edge',
				'EdgA'               => 'Edge',
				'XiaoMi/MiuiBrowser' => 'MiuiBrowser',
			),
			$key,
			$browser
		) ) {
			
		} elseif ( $find( 'Playstation Vita', $key, $operating_system ) ) {
			$operating_system = 'PlayStation Vita';
			$browser          = 'Browser';
		} elseif ( $find( array( 'Kindle Fire', 'Silk' ), $key, $val ) ) {
			$browser          = ( $val === 'Silk' ) ? 'Silk' : 'Kindle';
			$operating_system = 'Kindle Fire';
		} elseif ( $find( 'NintendoBrowser', $key ) || $operating_system === 'Nintendo 3DS' ) {
			$browser = 'NintendoBrowser';
		} elseif ( $find( array( 'Kindle' ), $key, $operating_system ) ) {
			$browser = $result['browser'][ $key ];
		} elseif ( $find( 'Opera', $key, $browser ) ) {
			
		} elseif ( $find( 'Puffin', $key, $browser ) ) {
			
			if ( strlen( $browser ) > 3 ) {
				$part = substr( $browser, -2 );
				if ( ctype_upper( $part ) ) {
					$flags = array(
						'IP' => 'iPhone',
						'IT' => 'iPad',
						'AP' => 'Android',
						'AT' => 'Android',
						'WP' => 'Windows Phone',
						'WT' => 'Windows',
					);
					if ( isset( $flags[ $part ] ) ) {
						$operating_system = $flags[ $part ];
					}
				}
			}
		} elseif ( $find(
			array(
				'IEMobile',
				'Edge',
				'Midori',
				'Whale',
				'Vivaldi',
				'OculusBrowser',
				'SamsungBrowser',
				'Valve Steam Tenfoot',
				'Chrome',
				'HeadlessChrome',
				'SailfishBrowser',
			),
			$key,
			$browser
		) ) {
			
		} elseif ( $browser === 'AppleWebKit' ) {
			if ( $operating_system === 'Android' ) {
				$browser = 'Android Browser';
			} elseif ( strpos( (string) $operating_system, 'BB' ) === 0 ) {
				$browser          = 'BlackBerry Browser';
				$operating_system = 'BlackBerry';
			} elseif ( $operating_system === 'BlackBerry' || $operating_system === 'PlayBook' ) {
				$browser = 'BlackBerry Browser';
			} elseif ( $find( 'Safari', $key, $browser ) || $find( 'TizenBrowser', $key, $browser ) ) {
				
			} elseif ( count( $result['browser'] ) ) {
				$key     = count( $result['browser'] ) - 1;
				$browser = $result['browser'][ $key ];
			}
		} elseif ( preg_grep( '/playstation \d/i', $result['browser'] ) ) {
			$p_key            = reset( preg_grep( '/playstation \d/i', $result['browser'] ) );
			$operating_system = 'PlayStation ' . preg_replace( '/\D/', '', $p_key );
			$browser          = 'NetFront';
		}

		
		$bot_list = get_transient( 'tfcm_all_bots' );
		if ( ! $bot_list ) {
			$bot_list = TFCM_Database::get_all_bots();
			set_transient( 'tfcm_all_bots', $bot_list, MONTH_IN_SECONDS );
		}

		
		foreach ( $bot_list as $bot_record ) {
			if ( stripos( $user_agent, $bot_record['name'] ) !== false ) {
				$bot_name     = $bot_record['name'];
				$bot_category = $bot_record['category'];
				break;
			}
		}

		
		

		
		return array(
			'device'           => (string) $device,
			'operating_system' => (string) $operating_system,
			'browser'          => (string) $browser,
			'bot_name'         => (string) $bot_name,
			'bot_category'     => (string) $bot_category,
		);
	}

	
	public static function get_ad_key_value( $query_string ) {
		$known_keys = self::get_supported_ad_keys();

		$ad_platform   = '';
		$click_key     = '';
		$click_value   = '';
		$campaign_id   = '';
		$campaign_name = '';
		$adgroup_id    = '';
		$adgroup_name  = '';
		$ad_id         = '';
		$keyword       = '';

		parse_str( $query_string, $params );

		foreach ( $known_keys as $key => $source ) {
			if ( isset( $params[ $key ] ) && $params[ $key ] !== '' ) {
				$ad_platform = $source;
				$click_key   = $key;
				$click_value = sanitize_text_field( $params[ $key ] );
				break;
			}
		}

		$campaign_id   = isset( $params['campaign_id'] ) ? sanitize_text_field( $params['campaign_id'] ) : '';
		$campaign_name = isset( $params['campaign_name'] ) ? sanitize_text_field( $params['campaign_name'] ) : '';
		$adgroup_id    = isset( $params['adgroup_id'] ) ? sanitize_text_field( $params['adgroup_id'] ) : '';
		$adgroup_name  = isset( $params['adgroup_name'] ) ? sanitize_text_field( $params['adgroup_name'] ) : '';
		$ad_id         = isset( $params['ad_id'] ) ? sanitize_text_field( $params['ad_id'] ) : '';
		$keyword       = isset( $params['keyword'] ) ? sanitize_text_field( $params['keyword'] ) : '';

		return array(
			'ad_platform'   => $ad_platform,
			'click_key'     => $click_key,
			'click_value'   => $click_value,
			'campaign_id'   => $campaign_id,
			'campaign_name' => $campaign_name,
			'adgroup_id'    => $adgroup_id,
			'adgroup_name'  => $adgroup_name,
			'ad_id'         => $ad_id,
			'keyword'       => $keyword,
		);
	}

	
	public static function get_supported_ad_keys() {
		return array(
			'gclid'     => 'Google Ads',
			'gbraid'    => 'Google Ads',
			'wbraid'    => 'Google Ads',
			'fbclid'    => 'Meta Ads',
			'msclkid'   => 'Microsoft Ads',
			'ttclid'    => 'TikTok Ads',
			'li_fat_id' => 'LinkedIn Ads',
			'twclid'    => 'Twitter Ads',
			'scid'      => 'Snapchat Ads',
			'epik'      => 'Pinterest Ads',
			'rdclid'    => 'Reddit Ads',
		);
	}

	
	public function get_source( $ad_platform = null, $referrer_url = null ) {
		$bot_name        = $this->bot_name;
		$referrer_domain = $this->get_domain_from_url( $referrer_url );
		$site_domain     = $this->get_domain_from_url( home_url() );
		$session_id_hash = $this->session_id_hash;

		
		
		
		
		
		

		if ( ! empty( $ad_platform ) ) {
			return $ad_platform;
		}

		if ( ! empty( $bot_name ) ) {
			return 'bot';
		}

		if ( ! empty( $referrer_domain ) && $referrer_domain !== $site_domain ) {

			
			if ( filter_var( $referrer_domain, FILTER_VALIDATE_IP ) ) {
				return null;
			}

			
			$parts = explode( '.', strtolower( trim( $referrer_domain ) ) );
			if ( count( $parts ) > 3 ) {
				$referrer_domain = implode( '.', array_slice( $parts, -3 ) );
			}
			return $referrer_domain;
		}

		
		if ( ! empty( $session_id_hash ) ) {
			$source = TFCM_Database::get_recent_session_source( $session_id_hash );
			if ( ! empty( $source ) ) {
				return $source;
			}
		}

		
		return null;
	}

	
	protected function get_domain_from_url( $url ) {
		if ( empty( $url ) ) {
			return null;
		}

		
		if ( strpos( $url, 'http' ) !== 0 ) {
			$url = 'https://' . ltrim( $url, '/' );
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return null;
		}

		return strtolower( preg_replace( '/^www\./', '', $host ) );
	}
}
