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
	protected $plugin_slug = 'progression';

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

		// Add inline CSS for custom player skin
		add_action( 'wp_head', array( $this, 'custom_skin_css' ) );

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
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/progression-admin.css', __FILE__ ), array( 'wp-color-picker'  ), $this->version );
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
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/progression-admin.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), $this->version );
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

		wp_enqueue_style( $this->plugin_slug . '-skin-' . $skin, plugins_url( 'assets/css/skin-'. $skin .'.css', __FILE__ ), array(), $this->version );

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// remove WordPress specific handling of mediaelement.js and define our own options.
		wp_deregister_script( 'wp-mediaelement' );	
		wp_enqueue_script( $this->plugin_slug . '-mediaelement', plugins_url( 'js/progression-mediaelement.js', __FILE__ ), array( 'jquery', 'mediaelement' ), $this->version );

		// build options array for mediaelement
		$options = array(
			'startvolume' => get_option( $this->plugin_slug . '_startvolume', 80 ) / 100
		);
		wp_localize_script( $this->plugin_slug . '-mediaelement', $this->plugin_slug, $options);
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

	 	add_settings_section( 
	 		$this->plugin_slug . '_skin',
			__( 'Player skin' ),
			array( $this, 'settings_section_skin_cb' ),
			'progression' 
		);
		 	
	 	add_settings_field( 
	 		$this->plugin_slug . '_active_skin',
			__( 'Selected player skin' ),
			array( $this, 'settings_field_active_skin_cb' ),
			'progression',
			$this->plugin_slug . '_skin' 
		);
	 	
	 	register_setting( 'progression', $this->plugin_slug . '_active_skin' );


 	 	add_settings_field( 
 	 		$this->plugin_slug . '_custom_skin',
 			__( 'Custom skin' ),
 			array( $this, 'settings_field_custom_skin_cb' ),
 			'progression',
 			$this->plugin_slug . '_skin' 
 		);
 	 	
 	 	register_setting( 'progression', $this->plugin_slug . '_custom_skin' );


 	 	add_settings_field( 
 	 		$this->plugin_slug . '_custom_skin_bg',
 			__( 'Player background color' ),
 			array($this, 'settings_field_custom_skin_bg_cb'),
 			'progression',
 			$this->plugin_slug . '_skin' 
 		);
 	 	
 	 	register_setting( 'progression', $this->plugin_slug . '_custom_skin_bg' );


 	 	add_settings_section( 
 	 		$this->plugin_slug . '_defaults',
 			__( 'Player default options' ),
 			array( $this, 'settings_section_defaults_cb' ),
 			'progression' 
 		);
 		 	
 	 	add_settings_field( 
 	 		$this->plugin_slug . '_startvolume',
 			__( 'Start volume' ),
 			array($this, 'settings_field_defaults_volume_cb'),
 			'progression',
 			$this->plugin_slug . '_defaults' 
 		);
 	 	
 	 	register_setting( 'progression', $this->plugin_slug . '_startvolume' );
		
	}

	/**
	 * The intro text for the skin settings section of the admin panel.
	 *
	 * @since    1.0.0
	 */
	
	function settings_section_skin_cb() {
		echo '<p>'. __( 'These settings let you choose how Progression Player will look like.'). '</p>';
	}

	/**
	 * The skin settings section of the admin panel.
	 *
	 * @since    1.0.0
	 */
	
	function settings_field_active_skin_cb() { 

		// the list of available skins
		$skins = array( 
			'default' => __( 'Default Skin' ),
			'default-dark' => __( 'Dark Skin' ),
			'minimal-dark' => __( 'Minimal Dark Skin' ),
			'minimal-light' => __( 'Minimal Light Skin' ),
			'fancy' => __( 'Fancy Skin' )
		);

		$value = get_option( $this->plugin_slug . '_active_skin', 'default' );
		$option_name = $this->plugin_slug . '_active_skin';
		$html_option = '<option value="%s"%s>%s</option>';

		$html = '';
		$html .= "<select name='$option_name'>";

			foreach ($skins as $skin => $skin_name)
				$html .= sprintf( $html_option, $skin, selected( $value, $skin, false ), $skin_name);

		$html .= '</select>';

		echo $html;

	}

	/**
	 * The skin settings section of the admin panel.
	 *
	 * @since    1.0.0
	 */
	
	function settings_field_custom_skin_cb() { 

		echo '<label><input name="' . $this->plugin_slug . '_custom_skin" id="progression_custom_skin" type="checkbox" value="1" class="code" ' . checked( 1, get_option( $this->plugin_slug . '_custom_skin' ), 0 ) . ' /> Customize selected player skin</label>';

	}

	/**
	 * The colorpicker for the background color of the skin
	 *
	 * @since    1.0.0
	 */
	
	function settings_field_custom_skin_bg_cb() { 

		echo '<input name="' . $this->plugin_slug . '_custom_skin_bg" type="text" value="' . get_option( $this->plugin_slug . '_custom_skin_bg' ) . '" class="progression-skincolor" />';

	}

	/**
	 * The intro text for the defaults settings section of the admin panel.
	 *
	 * @since    1.0.0
	 */
	
	function settings_section_defaults_cb() {
		echo '<p>'. __( 'These settings let you set the default behavior of Progression Player.'). '</p>';
	}

	/**
	 * The skin defaults section of the admin panel.
	 *
	 * @since    1.0.0
	 */
	
	function settings_field_defaults_volume_cb() { 

		$value = get_option( $this->plugin_slug . '_startvolume', 80 );
		$option_name = $this->plugin_slug . '_startvolume';

		echo "<input name='$option_name' type='number' value='$value' min='0' max='100' step='5' /> <span>%<span>";

	}

	/**
	 * This is where we set the skin class of the video player
	 *
	 * @since    1.0.0
	 */
	public function shortcode_class( $class ) {

		$class .= ' progression-skin';

		$skin = get_option( $this->plugin_slug . '_active_skin', 'default' );

		$class .= " progression-$skin";

		if ( get_option( $this->plugin_slug . '_custom_skin' ) ) {
			$class .= " progression-custom";
		}

		return $class;
	}

	/**
	 * Custom skin rules generated from user settings
	 *
	 * @since    1.0.0
	 */
	public function custom_skin_css( $class ) {

		$c_bg = get_option( $this->plugin_slug . '_custom_skin_bg' );

		$html = '';
		$html .= '<style type="text/css">';
		
		if ( ! empty( $c_bg ) ) 
			$html .= "body .mejs-container.progression-skin.progression-custom .mejs-controls { background: $c_bg } ";
		    
		$html .= '</style>';

		echo $html;
	}

}