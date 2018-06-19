<?php
/*
Plugin Name: WP Text Expander
Plugin URI: https://www.smartfoxes.ca/wordpress-plugins/wp-text-expander/
Description: Allows you to use [mytext YOURTERM] shortcodes to replace snippents of text across the website with pre-defined values controlled from the central spot. 
Text Domain: wp-text-expander
Version: 1.0.0
Author: Smart Foxes
Author URI: https://www.smartfoxes.ca/
License: GPL v3

WP Text Expander
Copyright (C) 2018, Smart Foxes Inc., https://www.smartfoxes.ca/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once('lib/WPTextExpander/Mytext.php');

add_action('plugins_loaded', array("WPTextExpander_Mytext", "init"));
register_activation_hook( __FILE__, array("WPTextExpander_Mytext", "install") );

if( is_admin() ) {
    include_once('lib/WPTextExpander/Settings.php');
    include_once('lib/WPTextExpander/ListTable.php');
    $wpQuickieSettingsPage = new WPTextExpander_Settings();
        		
    add_filter( 'plugin_action_links', array(WPTextExpander_Settings, "action_link"), 10, 5 );
}