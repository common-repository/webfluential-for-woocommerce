<?php
/**
 * Plugin Name: Webfluential for WooCommerce
 * Plugin URI: https://github.com/
 * Description: Connect with influencers on the web using Webfluential
 * Author: Webfluential, shadim
 * Author URI: https://webfluential.com/
 * Version: 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Webfluential_WC' ) ) :

class Webfluential_WC {

	private $version = "1.0.0";

	/**
	 * Instance to call certain functions globally within the plugin
	 *
	 * @var Webfluential_WC
	 */
	protected static $_instance = null;
	
	/**
	 * Webfluential WooCommerce Search
	 *
	 * @var Webfluential_WC_Search
	 */
	public $webfluential_wc_search = null;

	/**
	 * Webfluential log tracking of API calls
	 *
	 * @var Webfluential__Logger
	 */
	protected $logger = null;

	/**
	* Construct the plugin.
	*/
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 * @see Webfluential_WC()
	 * @return Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		// Path related defines
		$this->define( 'WEBFLUENTIAL_PLUGIN_FILE', __FILE__ );
		$this->define( 'WEBFLUENTIAL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WEBFLUENTIAL_PLUGIN_DIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		$this->define( 'WEBFLUENTIAL_PLUGIN_DIR_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
		$this->define( 'WEBFLUENTIAL_VERSION', $this->version );
		$this->define( 'WEBFLUENTIAL_LOG_DIR', $upload_dir['basedir'] . '/wc-logs/' );

		$this->define( 'WEBFLUENTIAL_DOMAIN', 'https://webfluential.com' );
		$this->define( 'WEBFLUENTIAL_PLUGIN_VERSION', $this->version );

		$this->define( 'WEBFLUENTIAL_API_KEY', '5ab1790e02519b00175786f7' );
		$this->define( 'WEBFLUENTIAL_API_SECRET', 'ade7e1fc12ed473d80c39c79974024f6' );
		$this->define( 'WEBFLUENTIAL_API_CLIENT_TOKEN', 'd0bb1489e9863e89be5d6fb1ea21d4e44c5e4280233e18b98daf6ac5b9c211c1' );


	}
	
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		// Auto loader class
		include_once( 'includes/class-webfluential-autoloader.php' );
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
	}


	/**
	* Initialize the plugin.
	*/
	public function init() {

		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {			

			$this->set_webfluential_search();

		} else {
			// Throw an admin error informing the user this plugin needs WooCommerce to function
			add_action( 'admin_notices', array( $this, 'notice_wc_required' ) );
		}
	}
	
	public function set_webfluential_search() {
		$this->webfluential_wc_search = new Webfluential_WC_Search();
	}

	/**
	 * Localisation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'webfluential-wc', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'Webfluential_WC_Integration';
		return $integrations;
	}

	/**
	 * Admin error notifying user that WC is required
	 */
	public function notice_wc_required() {
	?>
		<div class="error">
			<p><?php _e( 'Webfluential plugin requires WooCommerce to be installed and activated!', 'webfluential-wc' ); ?></p>
		</div>
	<?php
	}


	public function get_shipping_wf_settings( ) {
		return get_option('woocommerce_webfluential_settings');
	}

	public function log_msg( $msg )	{
		
		try {
			$shipping_wf_settings = $this->get_shipping_wf_settings();
			$wf_debug = isset( $shipping_wf_settings['wf_debug'] ) ? $shipping_wf_settings['wf_debug'] : 'yes';
			
			if( ! $this->logger ) {
				$this->logger = new Webfluential_Logger( $wf_debug );
			}

			$this->logger->write( $msg );
			
		} catch (Exception $e) {
			// do nothing
		}
	}

	public function get_log_url( )	{

		try {
			$shipping_wf_settings = $this->get_shipping_wf_settings();
			$wf_debug = isset( $shipping_wf_settings['wf_debug'] ) ? $shipping_wf_settings['wf_debug'] : 'yes';
			
			if( ! $this->logger ) {
				$this->logger = new Webfluential_Logger( $wf_debug );
			}
			
			return $this->logger->get_log_url( );
			
		} catch (Exception $e) {
			throw $e;
		}
	}

}


endif;

function Webfluential_WC() {
	return Webfluential_WC::instance();
}

$Webfluential_WC = Webfluential_WC();
