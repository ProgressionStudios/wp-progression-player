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
 * The main class of the player
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
	 * Holds the plugin options
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $loaded_options = array();

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since 1.0.0
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

		// change the class of the media shortcode
		add_filter( 'wp_video_shortcode_class', array( $this, 'shortcode_class' ) );
		add_filter( 'wp_audio_shortcode_class', array( $this, 'shortcode_class' ) );

		// Add inline CSS for custom player skin
		add_action( 'wp_head', array( $this, 'custom_skin_css' ) );

		// create shortcode for playlist
		add_shortcode( 'playlist', array( $this, 'playlist_shortcode' ) );

		// hook into media library
		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );
		add_action( 'wp_enqueue_media', array( $this, 'wp_enqueue_media' ) );

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

		$defaults = array(
				
			// return default options if db not available
			'startvolume' => 80,
			'autoplay' => 'false',
			'preload' => 'none',
			'loop' => 'false',
			'controls' => 'false',
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
			$this->loaded_options = get_option( $this->plugin_slug, $defaults);
		}

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

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
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
			__( 'Loop playback' ),
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
	
	function settings_field_custom_skin_cb() { 
		echo '<label><input name="' . $this->plugin_slug . '[custom_skin]" id="progression_custom_skin" type="checkbox" value="true" class="code" ' . checked( 1, $this->options( 'custom_skin' ), 0 ) . ' /> Customize selected player skin</label>';
	}

	/**
	 * The colorpicker for the background color of the skin
	 *
	 * @since 1.0.0
	 */
	
	function settings_field_custom_skin_colors_cb( $args ) {
		$options = $this->options();
		$key = $args['key'];
		$value = $options[ 'colors' ][ $key ];
		$name = $this->plugin_slug . '[colors]['. $key .']';
		$class = $this->plugin_slug . '-skincolor';

		echo "<input name='$name' value='$value' class='$class' />";
	}

	/**
	 * The intro text for the defaults settings section of the admin panel.
	 *
	 * @since 1.0.0
	 */
	
	function settings_section_defaults_cb() {
		echo '<p>'. __( 'These settings let you set the behavior of Progression Player.'). '</p>';
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

		if ( 'controls' === $key || 'autoplay' === $key || 'loop' === $key ) {
			$checked = checked( $value, 'true', false );
			echo "<input name='$name' type='checkbox' value='true' $checked />";
		}

	}

	/**
	 * This is where we set the skin class of the video player
	 *
	 * @since 1.0.0
	 */
	public function shortcode_class( $class ) {

		$class .= ' progression-skin';

		$active_skin = $this->options( 'active_skin' );

		$class .= " progression-$active_skin";

		if ( $this->options( 'custom_skin' )) {
			$class .= " progression-custom";
		}

		return $class;
	}

	/**
	 * Insert custom skin rules generated from user settings as inline CSS
	 *
	 * @since 1.0.0
	 */
	public function custom_skin_css() {

		$options = $this->options();
		$colors = $options[ 'colors' ];

		if ( ! $options[ 'custom_skin' ] ) {
			return;
		}

		$html = '';
		$html .= '<style type="text/css">';

		if ( empty( $colors ) ) {
			return;
		}

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
				$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-handle, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-handle  { background: %s; border-color: %s }', $color, $color );
			}

			if ( 'slider' === $key ) {
				$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-total, body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-loaded, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-total { background: %s }', $color );
				$html .= sprintf( 'body .progression-skin.progression-custom .mejs-controls .mejs-time-rail .mejs-time-current, body .progression-skin.progression-custom .mejs-controls .mejs-volume-button .mejs-volume-slider .mejs-volume-current { background: %s }', $this->brightness( $color, 30 ) );
			}


		}
		    
		$html .= '</style>';

		echo $html;
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
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'include'    => '',
			'exclude'    => ''
		), $attr, 'gallery'));

		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( !empty($include) ) {
			$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'audio', 'order' => $order, 'orderby' => $orderby) );
		}

		if ( empty($attachments) )
			return '';
		 
	}

	/**
	* Enqueues all scripts, styles, settings, and templates necessary to use
	* all media JS APIs.
	*
	* @since 1.0.0
	*/

	public function wp_enqueue_media() {

		if ( ! ( 'post' == get_current_screen()->base && 'page' == get_current_screen()->id ) )
		    return;

		wp_enqueue_style( $this->plugin_slug .'-admin-media-styles', plugins_url( 'css/progression-admin-media.css', __FILE__ ), array(), $this->version );

	}

	/**
	 * Extends the media library to display additional options to video attachments
	 *	 *
	 * @since 1.0.0
	 */
	
	public function print_media_templates() {

		if ( ! ( 'post' == get_current_screen()->base && 'page' == get_current_screen()->id ) )
		    return;

		return;
		?>

		<script type="text/html" id="tmpl-progression-player-settings">

		  <# if ( 'video' === data.type || 'audio' === data.type ) { #>

		    <label class="setting">
		      <span><?php _e('Autoplay'); ?></span>
		      <input data-setting="autoplay" type="checkbox"> 
		      <b class="progression-label">Start video on pageload  </b>
		      </select>
		    </label>

		    <label class="setting">
		      <span><?php _e('Preload'); ?></span>
      	      <select data-setting="controls">
				<option value="none"><?php _e( 'None (recommended)'); ?> </option>
				<option value="metadata"><?php _e( 'Metadata'); ?> </option>        
				<option value="auto"><?php _e( 'Auto (browser setting)'); ?> </option>        
              </select>
		      </select>
		    </label>

		    <label class="setting">
		      <span><?php _e('Controls'); ?></span>
		      <input data-setting="controls" type="checkbox">  
		      <b class="progression-label"><?php _e( 'Always show them' ); ?></b>
		      </select>
		    </label>

		    <label class="setting">
		      <span><?php _e('Playlist'); ?></span>
		      <input data-setting="playlist" type="checkbox">     
		      <b class="progression-label"><?php _e( 'Collapsed by default' ); ?></b>
		      </select>
		    </label>

		  <# } #>
		  </script>

		  <script>

		    jQuery(document).ready(function(){

		      // add your shortcode attribute and its default value to the
		      // gallery settings list; $.extend should work as well...
		      _.extend(wp.media.view.settings.defaultProps, {
		        my_custom_attr: 'default_val'
		      });

		      // merge default gallery settings template with ours
		      wp.media.view.Settings.AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay.extend({
		        template: function(view){
		          return wp.media.template('attachment-display-settings')(view)
		               + wp.media.template('progression-player-settings')(view);
		        }
		      });

		    });

		  </script>
		<?php
	}

}