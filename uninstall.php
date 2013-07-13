<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Progression_Player
 * @author    ProgressionStudios <contact@progressionstudios.com>
 * @license   GPL-2.0+
 * @link      http://progressionstudios.com
 * @copyright 2013 ProgressionStudios
 */

// If uninstall, not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}