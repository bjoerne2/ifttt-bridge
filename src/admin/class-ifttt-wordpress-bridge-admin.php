<?php
/**
 * IFTTT WordPress Bridge
 *
 * @package   Ifttt_Wordpress_Bridge_Admin
 * @author    Björn Weinbrenner <info@bjoerne.com>
 * @license   GPLv3
 * @link      http://bjoerne.com
 * @copyright 2014 bjoerne.com
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Ifttt_Wordpress_Bridge_Admin
 * @author  Björn Weinbrenner <info@bjoerne.com>
 */
class Ifttt_Wordpress_Bridge_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = Ifttt_Wordpress_Bridge::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_options_setting' ) );
		add_action( 'admin_post_sent_post_request', array( $this, 'send_test_request' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'IFTTT WordPress Bridge', $this->plugin_slug ),
			__( 'IFTTT WordPress Bridge', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$options = get_option( 'ifttt_wordpress_bridge_options' );
		$this->log_enabled = $options && array_key_exists( 'log_enabled', $options ) && $options['log_enabled'] == true;
		$this->log = get_option( 'ifttt_wordpress_bridge_log', array() );
		$this->send_test_request_url = get_site_url() . '/wp-content/plugins/ifttt-wordpress-bridge/send_test_request.php';
		include_once( 'views/admin.php' );
	}

	/**
	 * Registers the settings.
	 *
	 * @since    1.0.0
	 */
	public function register_options_setting() {
		register_setting( 'ifttt_wordpress_bridge_options_group', 'ifttt_wordpress_bridge_options', array( $this, 'validate_options' ) );
	}

	/**
	 * Validates the options. Clears the log if log has been disabled.
	 *
	 * @since    1.0.0
	 */
	public function validate_options( $options ) {
		if ( $options['log_enabled'] == false ) {
			delete_option( 'ifttt_wordpress_bridge_log' );
		}
		return $options;
	}

	/**
	 * Send a test request to this WordPress instance.
	 */
	public function send_test_request() {
		$url = get_site_url() . '/xmlrpc.php';
		$xml = file_get_contents( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'test_request_template.xml' );
		$options = array();
		$options['body'] = $xml;
		$response = wp_safe_remote_post( $url, $options );
		add_settings_error( null, null, _x( 'Test request sent', 'Success message', $this->plugin_slug ), 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
		wp_redirect( $goback );
		exit;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);
	}
}