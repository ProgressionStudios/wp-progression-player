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
 * The main class of the player plugin
 * *
 * @package Progression_Player
 * @author  ProgressionStudios <contact@progressionstudios.com>
 */
class Progression_Player {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Unique identifier of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_slug = 'progression';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Holds all the plugin options to avoid db queries
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $loaded_options = array();

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		$defaults = array(
			'startvolume' => 80,
			'autoplay' => 'false',
			'preload' => 'none',
			'loop' => 'false',
			'controls' => 'false',
			'size' => 'normal',
			'playlist' => 'true',
			'active_skin' => 'default',
			'custom_skin' => 'false',
			'colors' => array(
				'bg' => '',
				'border' => '',
				'text' => '',
				'handle' => '',
				'slider' => ''
			)
		);

		// load plugin options just once per instance
		if ( empty( $this->loaded_options )) {

			$db_options = get_option( $this->plugin_slug );
			
			if ( empty( $db_options ) ) {
				// return default options if db not available
				update_option( $this->plugin_slug, $defaults );
				$this->loaded_options = $defaults;
			} else {
				$this->loaded_options = wp_parse_args( $db_options, $defaults );
			}
			
		}

		// Load plugin text domain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register the settings page for the options of this plugin
		add_action( 'admin_init', array( $this, 'settings_api_init'));

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// change the class of the media shortcode
		add_filter( 'wp_video_shortcode_class', array( $this, 'shortcode_class' ) );
		add_filter( 'wp_audio_shortcode_class', array( $this, 'shortcode_class' ) );

		// Modify shortcode output
		add_filter( 'wp_audio_shortcode', array( $this, 'modify_audio_shortcode_output' ) );
		add_filter( 'wp_video_shortcode', array( $this, 'modify_video_shortcode_output' ) );

		// Add inline CSS for custom player skin
		add_action( 'wp_head', array( $this, 'custom_skin_css' ) );

		// create shortcode for playlist
		add_shortcode( 'playlist', array( $this, 'playlist_shortcode' ) );

		// make shortcodes work in widgets
		add_filter( 'widget_text', 'do_shortcode' );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		// usually we would add the options array here but we are doing that on init to create our options array with defaults
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		
		delete_option( 'progression' );
		 
	}

	/**
	 * Returns all options of the plugin or specific option if parameter $key given
	 *
	 * @since 1.0.0
	 * @param string $key
	 * @var array
	 */
	protected function options( $key = false ){
		
		if ( $key ) {
			return $this->loaded_options[ $key ];
		} else {
			return $this->loaded_options;
		}
		
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		
		if ( get_current_screen()->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/progression-admin.css', __FILE__ ), array( 'wp-color-picker'  ), $this->version );
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( get_current_screen()->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/progression-admin.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), $this->version );
		}

		// This function loads in the required media files for the media manager.
		wp_enqueue_media();

		// Register, localize and enqueue our custom JS.
		wp_enqueue_script( $this->plugin_slug . '-admin-playlist', plugins_url( 'js/progression-playlist.js', __FILE__ ), array( 'media-views', 'media-upload' ), $this->version );
		
		wp_localize_script( $this->plugin_slug . '-admin-playlist', $this->plugin_slug,
			array(
				'title'		=> __( 'Upload or Choose Audio Files to Create a Playlist' ),
				'button'	=> __( 'Insert Playlist' ),
				'menuitem'  => __( 'Create Playlist' )
			)
		);
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		// remove WordPress specific style. We will use our own.
		wp_dequeue_style( 'mediaelement' ); 
		wp_deregister_style( 'wp-mediaelement' ); 

		wp_enqueue_style( $this->plugin_slug, plugins_url( 'assets/css/progression-player.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( $this->plugin_slug . '-icons', plugins_url( 'assets/font-awesome/css/font-awesome.min.css', __FILE__ ), array(), $this->version );

		// load skin CSS
		$skin = $this->options( 'active_skin' );

		wp_enqueue_style( $this->plugin_slug . '-skin-' . $skin, plugins_url( 'assets/css/skin-'. $skin .'.css', __FILE__ ), array(), $this->version );

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		// remove WordPress specific handling of mediaelement.js. We will use our own.
		wp_deregister_script( 'wp-mediaelement' );	
		wp_enqueue_script( $this->plugin_slug . '-mediaelement', plugins_url( 'js/progression-mediaelement.js', __FILE__ ), array( 'jquery', 'mediaelement' ), $this->version );
		wp_enqueue_script( $this->plugin_slug . '-playlist', plugins_url( 'assets/build/mep-feature-playlist.js', __FILE__ ), array( 'jquery', 'mediaelement' ), $this->version );

		$options = $this->options();
		$options['startvolume'] = $options['startvolume'] / 100; // 80% => 0.8
		
		// hand over options to javascript object
		wp_localize_script( $this->plugin_slug . '-mediaelement', $this->plugin_slug, $options);
	}


	/**
	 * Register the administration menu for this plugin into the WordPress Options menu.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Initializing all settings to the admin panel
	 *
	 * @since 1.0.0
	 */
	public function settings_api_init() {

		register_setting( $this->plugin_slug, $this->plugin_slug );

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
	 	
 	 	add_settings_field( 
 	 		$this->plugin_slug . '_custom_skin',
 			__( 'Custom skin' ),
 			array( $this, 'settings_field_custom_skin_cb' ),
 			'progression',
 			$this->plugin_slug . '_skin' 
 		);

 	 	$color_zones = array(
 	 		'bg' 		=> __( 'Background color' ),
 	 		'border' 	=> __( 'Border color' ),
 	 		'text' 		=> __( 'Text and icon color' ),
 	 		'slider' 	=> __( 'Background color of the volume and timeline slider' ),
 	 		'handle' 	=> __( 'Color of the volume and timeline handle' )
 	 	);

 	 	foreach ( $color_zones as $key => $label ) {
 		 	add_settings_field( 
 		 		$this->plugin_slug . '_custom_skin_colors['. $key .']',
 				$label,
 				array( $this, 'settings_field_custom_skin_colors_cb' ),
 				'progression',
 				$this->plugin_slug . '_skin',
 				array( 
 					'name' => 'custom_skin_colors', 
 					'key' => $key
 				)
 			);
 	 	}

 	 	add_settings_field( 
 	 		$this->plugin_slug . 'size',
 			__( 'Size' ),
 			array( $this, 'settings_field_defaults_cb' ),
 			'progression',
 			$this->plugin_slug . '_skin',
 			array( 
 				'key' => 'size' 
 			) 
 		);

 	 	add_settings_section( 
 	 		$this->plugin_slug . '_defaults',
 			__( 'Player options' ),
 			array( $this, 'settings_section_defaults_cb' ),
 			'progression' 
 		);
 		 	
 	 	add_settings_field( 
 	 		$this->plugin_slug . '_startvolume',
 			__( 'Start volume' ),
 			array( $this, 'settings_field_defaults_cb' ),
 			'progression',
 			$this->plugin_slug . '_defaults',
			array( 
				'key' => 'startvolume' 
			)
 		);

	 	add_settings_field( 
	 		$this->plugin_slug . '_autoplay',
			__( 'Enable autoplay' ),
			array( $this, 'settings_field_defaults_cb' ),
			'progression',
			$this->plugin_slug . '_defaults',
			array( 
				'key' => 'autoplay' 
			)
		);

	 	add_settings_field( 
	 		$this->plugin_slug . '_controls',
			__( 'Always show controls' ),
			array( $this, 'settings_field_defaults_cb' ),
			'progression',
			$this->plugin_slug . '_defaults',
			array( 
				'key' => 'controls' 
			) 
		);

	 	add_settings_field( 
	 		$this->plugin_slug . '_loop',
			sprintf( '%s <br><small>(%s)</small>', __( 'Loop playback' ), __( 'applies to playlists only' )),
			array( $this, 'settings_field_defaults_cb' ),
			'progression',
			$this->plugin_slug . '_defaults',
			array( 
				'key' => 'loop' 
			) 
		);

	 	add_settings_field( 
	 		$this->plugin_slug . '_preload',
			__( 'Preload' ),
			array( $this, 'settings_field_defaults_cb' ),
			'progression',
			$this->plugin_slug . '_defaults',
			array( 
				'key' => 'preload' 
			) 
		);

	 	add_settings_section( 
	 		$this->plugin_slug . '_playlist',
			__( 'Playlist options' ),
			array( $this, 'settings_section_playlist_cb' ),
			'progression' 
		);

	 	add_settings_field( 
	 		$this->plugin_slug . '_playlist',
			__( 'Show playlist' ),
			array( $this, 'settings_field_defaults_cb' ),
			'progression',
			$this->plugin_slug . '_playlist',
			array( 
				'key' => 'playlist' 
			) 
		);
 	 	
 	 	register_setting( 'progression', $this->plugin_slug . '_startvolume' );
		
	}

	/**
	 * The intro text for the skin settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_section_skin_cb() {
		echo '<p>'. __( 'These settings let you choose how Progression Player will look like.'). '</p>';
	}

	/**
	 * The skin settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_field_active_skin_cb() { 

		// the list of available skins
		$skins = array( 
			'default' 		=> __( 'Default Skin' ),
			'default-dark' 	=> __( 'Dark Skin' ),
			'minimal-dark' 	=> __( 'Minimal Dark Skin' ),
			'minimal-light' => __( 'Minimal Light Skin' ),
			'fancy' 		=> __( 'Fancy Skin' )
		);

		$active_skin = $this->options( 'active_skin' );
		$option_name = $this->plugin_slug . '[active_skin]';
		$html_option = '<option value="%s"%s>%s</option>';

		$html = '';
		$html .= "<select name='$option_name'>";

			foreach ($skins as $skin => $skin_name)
				$html .= sprintf( $html_option, $skin, selected( $active_skin, $skin, false ), $skin_name);

		$html .= '</select>';

		echo $html;

	}

	/**
	 * The skin settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_field_custom_skin_cb() { ?>
		<input type="hidden" value="false" name="<?php echo $this->plugin_slug ?>[custom_skin]">
		<label><input name="<?php echo $this->plugin_slug ?>[custom_skin]" id="progression_custom_skin" type="checkbox" value="true" class="code" <?php echo checked( $this->options( 'custom_skin' ), 'true', false)?> /> <?php _e( 'Customize selected player skin' ); ?></label>
	<?php }

	/**
	 * The colorpicker for the background color of the skin
	 *
	 * @since 1.0.0
	 */
	
	function settings_field_custom_skin_colors_cb( $args ) {
		$options = $this->options();
		$key = $args['key'];
		$name = $this->plugin_slug . '[colors]['. $key .']';
		$value = $options[ 'colors' ][ $key ];
		$class = $this->plugin_slug . '-skincolor';

		echo "<input name='$name' value='$value' class='$class' />";
	}

	/**
	 * The intro text for the defaults settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_section_defaults_cb() {
		echo '<p>'. __( 'These settings set the behavior of Progression Player.'). '</p>';
	}

	/**
	 * The skin defaults section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_field_defaults_cb( $args ) { 

		$options = $this->options();
		$key = $args['key'];
		$name = $this->plugin_slug . '['. $key .']';		
		$value = $options[ $key ];

		if ( 'startvolume' === $key ) {
			echo "<input name='$name' type='number' value='$value' min='0' max='100' step='5' /> <span>%<span>";
		}

		if ( 'preload' === $key ) { ?>

			<select name="<?php echo $name ?>">
				<option value="none" <?php selected( $value, 'none' ) ?>><?php _e( 'None (recommended)'); ?> </option>
				<option value="metadata" <?php selected( $value, 'metadata' ) ?>><?php _e( 'Metadata'); ?> </option>
				<option value="auto" <?php selected( $value, 'auto' ) ?>><?php _e( 'Auto (browser setting)'); ?> </option>
			</select>

		<?php }

		if ( 
			'controls' === $key || 
			'autoplay' === $key || 
			'playlist' === $key || 
			'loop' === $key ) {
			$checked = checked( $value, 'true', false );
			echo "<input type='hidden' value='false' name='$name'>";
			echo "<input name='$name' type='checkbox' value='true' $checked />";
		}

		if ( 
			'size' === $key) { 
			?>
			<label><input name='<?php echo $name; ?>' type='radio' value='normal' <?php checked( $value, 'normal' ) ?> /> <span><?php _e( 'normal (default)' ); ?></span></label><br>
			<label><input name='<?php echo $name; ?>' type='radio' value='small' <?php checked( $value, 'small' ) ?> /> <span><?php _e( 'small' ); ?></span></label>
		<?php }

	}

	/**
	 * The intro text for the skin settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_section_playlist_cb() {
		echo '<p>'. __( 'These settings set the behavior of the playlist feature.'). '</p>';
	}

	/**
	 * This is where we set the skin class of the video player
	 *
	 * @since 1.0.0
	 */
	
	public function shortcode_class( $class ) {

		if ( strpos( $class, 'wp-audio-shortcode' )  !== false ) {
			$class .= ' progression-audio-player';
		}

		$class .= ' progression-skin';

		$active_skin = $this->options( 'active_skin' );

		$class .= " progression-$active_skin";

		if ( $this->options( 'custom_skin' ) === 'true') {
			$class .= " progression-custom";
		}

		return $class;
	}

	/**
	 * Wraps the default shortcode output in html necessary for responsive player
	 *
	 * @since 1.0.0
	 */
	
	public function modify_audio_shortcode_output( $html ) {
		$html = '<div class="responsive-wrapper responsive-audio">' . $html . '</div><!-- close .responsive-wrapper -->';

		if ( $this->options( 'size' ) === 'small') {
			$html = '<div class="progression-small">' . $html . '</div>';
		}

		return $html;
	}

	/**
	 * Wraps the default shortcode output in html necessary for responsive player
	 *
	 * @since 1.0.0
	 */
	
	public function modify_video_shortcode_output( $html ) {
		$html = str_replace( '<video', '<video style="width: 100%; height: 100%;" ', $html );
		$html = '<div class="responsive-wrapper">' . $html . '</div><!-- close .responsive-wrapper -->';

		if ( $this->options( 'size' ) === 'small' ) {
			$html = '<div class="progression-small">' . $html . '</div>';
		}
		
		return $html;
	}

	/**
	 * Insert custom skin rules generated from user settings as inline CSS
	 *
	 * @since 1.0.0
	 */
	
	public function custom_skin_css() {

		global $content_width;

		$options = $this->options();
		$colors = $options[ 'colors' ];

		if ( ! $options[ 'custom_skin' ] ) {
			return;
		}

		$html = '';

		if ( ! isset( $content_width ) )
			$content_width = 600;

		$html .= sprintf( '.responsive-wrapper  { max-width: %s !important }', $content_width . 'px' );

		if ( !empty( $colors ) ) {
			
			foreach ( $colors as $key => $color ) {
				
				if ( empty( $color ) ) continue;

				if ( 'bg' === $key ) {
					$html .= sprintf( 'body .mejs-container.progression-skin.progression-custom .mejs-controls { background: %s }', $color );
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-nexttrack:hover, body .progression-skin.progression-custom .mejs-controls .mejs-prevtrack:hover,  body .progression-skin.progression-custom .mejs-controls .mejs-show-playlist:hover, body .progression-skin.progression-custom  .mejs-controls .mejs-hide-playlist:hover,  body .progression-skin.progression-custom .mejs-controls .mejs-mute button:hover,  body .progression-skin.progression-custom .mejs-controls .mejs-fullscreen-button:hover,  body .progression-skin.progression-custom .mejs-controls .mejs-hide-playlist, body .progression-skin.progression-custom .mejs-controls .mejs-playpause-button:hover { background: %s }', $this->brightness( $color, 20 ) );
				}

				if ( 'border' === $key ) {
					$html .= sprintf( 'body .mejs-container.progression-skin.progression-custom, body .mejs-container.progression-skin.progression-custom .mejs-controls, body .progression-skin.progression-custom .mejs-controls .mejs-playpause-button, body .progression-skin.progression-custom .mejs-inner .mejs-controls .mejs-time, body .progression-skin.progression-custom .mejs-controls .mejs-fullscreen-button,  body .progression-skin.progression-custom .mejs-controls .mejs-show-playlist, body .progression-skin.progression-custom  .mejs-controls .mejs-hide-playlist, body .progression-skin.progression-custom .mejs-controls .mejs-prevtrack button,  body .progression-skin.progression-custom .mejs-controls .mejs-nexttrack button { border-color: %s }', $color );
				}

				if ( 'text' === $key ) {
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls button  { color: %s }', $color );
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls button:hover, body .progression-skin.progression-custom .mejs-inner .mejs-time .mejs-currenttime, body .progression-skin.progression-custom .mejs-inner .mejs-time .mejs-duration { color: %s }', $this->brightness( $color, 20 ) );
				}

				if ( 'handle' === $key ) {
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-handle, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-handle, body .progression-skin.progression-custom .mejs-controls .mejs-horizontal-volume-slider .mejs-horizontal-volume-handle  { background: %s; border-color: %s }', $color, $color );
				}

				if ( 'slider' === $key ) {
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-total, body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-loaded, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-total, body .progression-skin.progression-custom .mejs-controls .mejs-horizontal-volume-slider  .mejs-horizontal-volume-total { background: %s }', $color );
					$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-current, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-current, body .progression-skin.progression-custom .mejs-controls .mejs-horizontal-volume-slider .mejs-horizontal-volume-current { background: %s }', $this->brightness( $color, 30 ) );
				}

			}

		}

		if ( ! empty( $html )) {
			echo '<style type="text/css">'. $html .'</style>';
		}
	}

	/**
	 * Change the brightness of the passed in color
	 *
	 * $diff should be negative to go darker, positive to go lighter and
	 * is subtracted from the decimal (0-255) value of the color
	 * 
	 * Credits: http://lab.clearpixel.com.au/2008/06/darken-or-lighten-colours-dynamically-using-php/
	 *
	 * @param string $hex color to be modified
	 * @param string $diff amount to change the color
	 * @return string hex color
	 *
	 * @since 1.0.0
	 */
	
	private function brightness( $hex, $diff ){
		
		$rgb = str_split( trim( $hex, '# ' ), 2 );
		 
			foreach ( $rgb as &$hex ) {
				$dec = hexdec( $hex );
				if ( $diff >= 0 ) {
					$dec += $diff;
				}
				else {
					$dec -= abs( $diff );			
				}
				$dec = max( 0, min( 255, $dec ));
				$hex = str_pad( dechex( $dec ), 2, '0', STR_PAD_LEFT );
			}
		 
			return '#'.implode( $rgb );
	}

	/**
	 * Playlist Shortcode
	 *
	 * Works very similar to the [gallery] shortcode. 
	 * 
	 * @since 1.0.0
	 *
	 */
	
	public function playlist_shortcode( $attr ) {
		
		$post = get_post();

		static $instance = 0;
		$instance++;

		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) )
				$attr['orderby'] = 'post__in';
			$attr['include'] = $attr['ids'];
		}

		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		extract(shortcode_atts(array(
			'order'		=> 'ASC',
			'orderby'	=> 'menu_order ID',
			'id'		=> $post ? $post->ID : 0,
			'include'	=> '',
			'exclude'	=> ''
		), $attr, 'gallery'));

		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( !empty($include) ) {
			$_attachments = get_posts( array( 'include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby ) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby) );
		}

		if ( empty($attachments) )
			return '';

		$skin = $this->shortcode_class( 'progression-skin' );

		if ( $this->options( 'custom_skin' ) === 'true') {
			$skin .= " progression-custom";
		}
		
		$html = '<div class="progression-playlist-height responsive-wrapper responsive-audio" style="padding-bottom:174px;">';
		$html .= "<audio id='playlist-{$instance}' class='progression-playlist $skin progression-audio-player playlistid-{$id}'>";

		foreach ($attachments as $attachment) {
			$html .= "<source src='{$attachment->guid}' title='{$attachment->post_title}' type='{$attachment->post_mime_type}'/>";
		}
		
		$html .= '</audio>';
		$html .= '</div>';

		if ( $this->options( 'size' ) === 'small') {
			$html = '<div class="progression-small">' . $html . '</div>';
		}
		
		if ( $this->options( 'playlist' ) === 'false') {
			$html = '<div class="playlist-height-adjust">' . $html . '</div>';
		}
		
		return $html;
		 
	}

}