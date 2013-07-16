<?php
/**
 * Progression Player Class
 *
 * @package   Progression_Player
 * @author    ProgressionStudios <contact@progressionstudios.com>
 * @license   GPL-2.0+
 * @link      http://progressionstudios.com
 * @copyright 2013 ProgressionStudios
 */


/**
 * Plugin class.
 *
 * TODO: Rename this class to a proper name for your plugin.
 *
 * @package Progression_Player
 * @author  ProgressionStudios <contact@progressionstudios.com>
 */
class Progression_Player {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.0';

	/**
	 * Unique identifier of the plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'pplayer';

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
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register the settings page for the options of this plugin
		add_action('admin_init', array( $this, 'settings_api_init'));

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// change the class of the video shortcode
		add_filter( 'wp_video_shortcode_class', array( $this, 'shortcode_class' ) );

		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action( 'TODO', array( $this, 'action_method_name' ) );
		add_filter( 'TODO', array( $this, 'filter_method_name' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		
		
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/pplayer-admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/pplayer-admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// remove WordPress specific style. We will use our own.
		wp_deregister_style( 'mediaelement' ); 
		wp_deregister_style( 'wp-mediaelement' ); 

		wp_enqueue_style( $this->plugin_slug, plugins_url( 'assets/css/progression-player.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( $this->plugin_slug . '-icons', plugins_url( 'assets/font-awesome/css/font-awesome.min.css', __FILE__ ), array(), $this->version );

		// load skin CSS
		$skin = get_option( $this->plugin_slug . '_active_skin', 'default' );
		wp_enqueue_style( $this->plugin_slug . 'skin-' . $skin, plugins_url( 'assets/css/skin-'. $skin .'.css', __FILE__ ), array(), $this->version );

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'mediaelement' ); 

		// remove WordPress specific handling of mediaelement.js and define our own options.
		wp_deregister_script( 'wp-mediaelement' );	
		wp_enqueue_script( $this->plugin_slug . '-mediaelement', plugins_url( 'js/pplayer-mediaelement.js', __FILE__ ), array( 'mediaelement' ), $this->version );

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Options menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		$this->plugin_screen_hook_suffix = add_submenu_page(
			'options-general.php',
			__( 'Progression Player', $this->plugin_slug ),
			__( 'Progression Player', $this->plugin_slug ),
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
		include_once( 'views/admin.php' );
	}


	/**
	 * Initializing all settings to the admin panel
	 *
	 * @since    1.0.0
	 */
	public function settings_api_init() {

	 	add_settings_section( $this->plugin_slug . '_skin',
			'Player skin',
			array( $this, 'settings_section_skin_cb' ),
			'pplayer');
		 	
	 	add_settings_field( $this->plugin_slug . '_active_skin',
			'Selected player skin',
			array($this, 'settings_field_active_skin_cb'),
			'pplayer',
			$this->plugin_slug . '_skin');
	 	
	 	register_setting('pplayer', $this->plugin_slug . '_active_skin');
		
	}

	/**
	 * Settings section callback function
	 *
	 * @since    1.0.0
	 */
	
	function settings_section_skin_cb() {
		echo '<p>These settings let you choose how Progression Player will look like.</p>';
	}

	/**
	 * Callback function for our example setting
	 *
	 * @since    1.0.0
	 */
	
	function settings_field_active_skin_cb() { 

		// the list of available skins
		$skins = array( 
			'default',
			'default-dark',
			'minimal-dark',
			'minimal-light', 
			'fancy' );

		$html = '';
		$html .= '<select name="'. $this->plugin_slug . '_active_skin">';

		$option = '<option value="%s"%s>%s</option>';

			foreach ($skins as $skin)
				$html .= sprintf( $option, $skin, selected( get_option( $this->plugin_slug . '_active_skin', 'default' ), $skin, false ), $skin);

		$html .= '</select>';

		echo $html;

	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// TODO: Define your filter hook callback here
	}
	/**
	 * This is where we set the skin class of the video player
	 *
	 * @since    1.0.0
	 */
	public function shortcode_class( $class ) {

		$class .= ' progression-skin';
		$class .= ' progression-' . get_option( $this->plugin_slug . '_active_skin', 'default' );

		return $class;
	}

}