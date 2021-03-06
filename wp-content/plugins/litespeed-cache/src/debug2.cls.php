<?php
/**
 * The plugin logging class.
 *
 * This generate the valid action.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Debug2 extends Instance
{
	protected static $_instance ;
	private static $log_path ;
	private static $_prefix ;

	const TYPE_CLEAR_LOG = 'clear_log' ;
	const TYPE_BETA_TEST = 'beta_test' ;

	const BETA_TEST_URL = 'beta_test_url' ;

	const BETA_TEST_URL_GITHUB = 'https://github.com/litespeedtech/lscache_wp/archive/master.zip';
	const BETA_TEST_URL_WP = 'https://downloads.wordpress.org/plugin/litespeed-cache.zip';

	/**
	 * Log class Confructor
	 *
	 * NOTE: in this process, until last step ( define const LSCWP_LOG = true ), any usage to WP filter will not be logged to prevent infinite loop with log_filters()
	 *
	 * @since 1.1.2
	 * @access public
	 */
	protected function __construct()
	{
		self::$log_path = LSCWP_CONTENT_DIR . '/debug.log' ;
		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) && strpos( $_SERVER[ 'HTTP_USER_AGENT' ], Crawler::FAST_USER_AGENT ) === 0 ) {
			self::$log_path = LSCWP_CONTENT_DIR . '/crawler.log' ;
		}

		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', get_current_blog_id() ) ;

		if ( Conf::val( Base::O_DEBUG_LEVEL ) ) {
			! defined( 'LSCWP_LOG_MORE' ) && define( 'LSCWP_LOG_MORE', true ) ;
		}

	}

	/**
	 * Beta test upgrade
	 *
	 * @since 2.9.5
	 * @access public
	 */
	public function beta_test( $zip = false )
	{
		if ( ! $zip ) {
			if ( empty( $_REQUEST[ self::BETA_TEST_URL ] ) ) {
				return ;
			}

			$zip = $_REQUEST[ self::BETA_TEST_URL ];
			if ( $zip !== Debug2::BETA_TEST_URL_WP ) {
				if ( $zip === 'latest' ) {
					$zip = Debug2::BETA_TEST_URL_WP;
				}
				else {
					if ( $zip === 'dev' || $zip === Debug2::BETA_TEST_URL_GITHUB ) {
						$commit = 'dev';
					}
					else {
						$commit = substr( $zip, strpos( $zip, '/commit/' ) + 8 );
					}

					// Generate zip url
					$zip = $this->_package_zip( $commit );
				}
			}
		}

		if ( ! $zip ) {
			Debug2::debug( '[Debug2] ???  No ZIP file' ) ;
			return ;
		}

		Debug2::debug( '[Debug2] ZIP file ' . $zip ) ;

		$update_plugins = get_site_transient( 'update_plugins' ) ;
		if ( ! is_object( $update_plugins ) ) {
			$update_plugins = new \stdClass() ;
		}

		$plugin_info = new \stdClass() ;
		$plugin_info->new_version = Core::VER;
		$plugin_info->slug = Core::PLUGIN_NAME;
		$plugin_info->plugin = Core::PLUGIN_FILE;
		$plugin_info->package = $zip ;
		$plugin_info->url = 'https://wordpress.org/plugins/litespeed-cache/' ;

		$update_plugins->response[ Core::PLUGIN_FILE ] = $plugin_info ;

		set_site_transient( 'update_plugins', $update_plugins ) ;

		// Run upgrade
		Activation::get_instance()->upgrade() ;
	}

	/**
	 * Git package refresh
	 *
	 * @since  2.9.5
	 * @access private
	 */
	private function _package_zip( $commit )
	{
		$data = array(
			'commit'	=> $commit,
		);
		$res = Cloud::get( Cloud::API_BETA_TEST, $data );

		if ( empty( $res[ 'zip' ] ) ) {
			return false ;
		}

		return $res[ 'zip' ] ;
	}

	/**
	 * Log Purge headers separately
	 *
	 * @since 2.7
	 * @access public
	 */
	public static function log_purge( $purge_header )
	{
		// Check if debug is ON
		if ( ! defined( 'LSCWP_LOG' ) && ! defined( 'LSCWP_LOG_BYPASS_NOTADMIN' ) ) {
			return ;
		}

		$purge_file = LSCWP_CONTENT_DIR . '/debug.purge.log' ;

		self::get_instance()->_init_request( $purge_file ) ;

		$msg = $purge_header . self::_backtrace_info( 6 ) ;

		File::append( $purge_file, self::format_message( $msg ) ) ;

	}

	/**
	 * Enable debug log
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function init()
	{
		$debug = Conf::val( Base::O_DEBUG ) ;
		if ( $debug == Base::VAL_ON2 ) {
			if ( ! Router::is_admin_ip() ) {
				define( 'LSCWP_LOG_BYPASS_NOTADMIN', true ) ;
				return ;
			}
		}

		/**
		 * Check if hit URI includes/excludes
		 * This is after LSCWP_LOG_BYPASS_NOTADMIN to make `log_purge()` still work
		 * @since  3.0
		 */
		$list = Conf::val( Base::O_DEBUG_INC ) ;
		if ( $list ) {
			$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $list ) ;
			if ( ! $result ) {
				return ;
			}
		}

		$list = Conf::val( Base::O_DEBUG_EXC ) ;
		if ( $list ) {
			$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $list ) ;
			if ( $result ) {
				return ;
			}
		}

		if ( ! defined( 'LSCWP_LOG' ) ) {// If not initialized, do it now
			self::get_instance()->_init_request() ;
			define( 'LSCWP_LOG', true ) ;

		}
	}

	/**
	 * Create the initial log messages with the request parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _init_request( $log_file = null )
	{
		if ( ! $log_file ) {
			$log_file = self::$log_path ;
		}

		// Check log file size
		$log_file_size = Conf::val( Base::O_DEBUG_FILESIZE ) ;
		if ( file_exists( $log_file ) && filesize( $log_file ) > $log_file_size * 1000000 ) {
			File::save( $log_file, '' ) ;
		}

		// For more than 2s's requests, add more break
		if ( file_exists( $log_file ) && time() - filemtime( $log_file ) > 2 ) {
			File::append( $log_file, "\n\n\n\n" ) ;
		}

		if ( PHP_SAPI == 'cli' ) {
			return ;
		}

		$servervars = array(
			'Query String' => '',
			'HTTP_ACCEPT' => '',
			'HTTP_USER_AGENT' => '',
			'HTTP_ACCEPT_ENCODING' => '',
			'HTTP_COOKIE' => '',
			'X-LSCACHE' => '',
			'LSCACHE_VARY_COOKIE' => '',
			'LSCACHE_VARY_VALUE' => '',
			'ESI_CONTENT_TYPE' => '',
		) ;
		$server = array_merge( $servervars, $_SERVER ) ;
		$params = array() ;

		if ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == 'on' ) {
			$server['SERVER_PROTOCOL'] .= ' (HTTPS) ' ;
		}

		$param = sprintf( '???? ------%s %s %s', $server['REQUEST_METHOD'], $server['SERVER_PROTOCOL'], strtok( $server['REQUEST_URI'], '?' ) ) ;

		$qs = ! empty( $server['QUERY_STRING'] ) ? $server['QUERY_STRING'] : '' ;
		if ( Conf::val( Base::O_DEBUG_COLLAPS_QS ) ) {
			if ( strlen( $qs ) > 53 ) {
				$qs = substr( $qs, 0, 53 ) . '...' ;
			}
			if ( $qs ) {
				$param .= ' ? ' . $qs ;
			}
			$params[] = $param ;
		}
		else {
			$params[] = $param ;
			$params[] = 'Query String: ' . $qs ;
		}

		if ( ! empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			$params[] = 'HTTP_REFERER: ' . $server[ 'HTTP_REFERER' ] ;
		}

		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'User Agent: ' . $server[ 'HTTP_USER_AGENT' ] ;
			$params[] = 'Accept: ' . $server['HTTP_ACCEPT'] ;
			$params[] = 'Accept Encoding: ' . $server['HTTP_ACCEPT_ENCODING'] ;
		}
		if ( Conf::val( Base::O_DEBUG_COOKIE ) ) {
			$params[] = 'Cookie: ' . $server['HTTP_COOKIE'] ;
		}
		if ( isset( $_COOKIE[ '_lscache_vary' ] ) ) {
			$params[] = 'Cookie _lscache_vary: ' . $_COOKIE[ '_lscache_vary' ] ;
		}
		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'X-LSCACHE: ' . ( ! empty( $server[ 'X-LSCACHE' ] ) ? 'true' : 'false' ) ;
		}
		if( $server['LSCACHE_VARY_COOKIE'] ) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $server['LSCACHE_VARY_COOKIE'] ;
		}
		if( $server['LSCACHE_VARY_VALUE'] ) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $server['LSCACHE_VARY_VALUE'] ;
		}
		if( $server['ESI_CONTENT_TYPE'] ) {
			$params[] = 'ESI_CONTENT_TYPE: ' . $server['ESI_CONTENT_TYPE'] ;
		}

		$request = array_map( __CLASS__ . '::format_message', $params ) ;

		File::append( $log_file, $request ) ;
	}

	/**
	 * Formats the log message with a consistent prefix.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $msg The log message to write.
	 * @return string The formatted log message.
	 */
	private static function format_message( $msg )
	{
		// If call here without calling get_enabled() first, improve compatibility
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			return $msg . "\n" ;
		}

		if ( ! isset( self::$_prefix ) ) {
			// address
			if ( PHP_SAPI == 'cli' ) {
				$addr = '=CLI=' ;
				if ( isset( $_SERVER[ 'USER' ] ) ) {
					$addr .= $_SERVER[ 'USER' ] ;
				}
				elseif ( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) {
					$addr .= $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ;
				}
			}
			else {
				$addr = $_SERVER[ 'REMOTE_ADDR' ] . ':' . $_SERVER[ 'REMOTE_PORT' ] ;
			}

			// Generate a unique string per request
			self::$_prefix = sprintf( " [%s %s %s] ", $addr, LSCWP_LOG_TAG, Str::rrand( 3 ) ) ;
		}
		list( $usec, $sec ) = explode(' ', microtime() ) ;
		return date( 'm/d/y H:i:s', $sec + LITESPEED_TIME_OFFSET ) . substr( $usec, 1, 4 ) . self::$_prefix . $msg . "\n" ;
	}

	/**
	 * Direct call to log a debug message.
	 *
	 * @since 1.1.3
	 * @since 1.6 Added array dump as 2nd param
	 * @access public
	 * @param string $msg The debug message.
	 * @param int|array $backtrace_limit Backtrace depth, Or the array to dump
	 */
	public static function debug( $msg, $backtrace_limit = false )
	{
		if ( ! defined( 'LSCWP_LOG' ) ) {
			return ;
		}

		if ( $backtrace_limit !== false ) {
			if ( ! is_numeric( $backtrace_limit ) ) {
				$msg .= ' --- ' . var_export( $backtrace_limit, true ) ;
				self::push( $msg ) ;
				return ;
			}

			self::push( $msg, $backtrace_limit + 1 ) ;
			return ;
		}

		self::push( $msg ) ;
	}

	/**
	 * Direct call to log an advanced debug message.
	 *
	 * @since 1.2.0
	 * @access public
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	public static function debug2( $msg, $backtrace_limit = false )
	{
		if ( ! defined( 'LSCWP_LOG_MORE' ) ) {
			return ;
		}
		self::debug( $msg, $backtrace_limit ) ;
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	private static function push( $msg, $backtrace_limit = false )
	{
		// backtrace handler
		if ( defined( 'LSCWP_LOG_MORE' ) && $backtrace_limit !== false ) {
			$msg .= self::_backtrace_info( $backtrace_limit ) ;
		}

		File::append( self::$log_path, self::format_message( $msg ) ) ;
	}

	/**
	 * Backtrace info
	 *
	 * @since 2.7
	 */
	private static function _backtrace_info( $backtrace_limit )
	{
		$msg = '' ;

		$trace = version_compare( PHP_VERSION, '5.4.0', '<' ) ? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) : debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $backtrace_limit + 3 ) ;
		for ( $i=2 ; $i <= $backtrace_limit + 2 ; $i++ ) {// 0st => _backtrace_info(), 1st => push()
			if ( empty( $trace[ $i ][ 'class' ] ) ) {
				if ( empty( $trace[ $i ][ 'file' ] ) ) {
					break ;
				}
				$log = "\n" . $trace[ $i ][ 'file' ] ;
			}
			else {
				if ( $trace[$i]['class'] == 'Debug2' ) {
					continue ;
				}

				$log = str_replace('Core', 'LSC', $trace[$i]['class']) . $trace[$i]['type'] . $trace[$i]['function'] . '()' ;
			}
			if ( ! empty( $trace[$i-1]['line'] ) ) {
				$log .= '@' . $trace[$i-1]['line'] ;
			}
			$msg .= " => $log" ;
		}

		return $msg ;
	}

	/**
	 * Clear log file
	 *
	 * @since 1.6.6
	 * @access private
	 */
	private function _clear_log()
	{
		File::save( self::$log_path, '' ) ;
		File::save( LSCWP_CONTENT_DIR . '/debug.purge.log', '' ) ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6.6
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_CLEAR_LOG :
				$instance->_clear_log() ;
				break ;

			case self::TYPE_BETA_TEST :
				$instance->beta_test() ;
				break ;

			default:
				break ;
		}

		Admin::redirect() ;
	}
}
