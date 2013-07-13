<?php
/*
Plugin Name: WP-Progression-Player
Plugin URI: http://progressionstudios.com/
Description: Implemenation of ProgressionPlayer for WordPress to play video and audio files.
Version: 0.1
Author: Progression Studios
Author Email: contact@progressionstudios.com
License: 

  Copyright 2012 Progression Studios (contact@progressionstudios.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-pplayer.php' );

register_activation_hook( __FILE__, array( 'Progression_Player', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Progression_Player', 'deactivate' ) );

Progression_Player::get_instance();