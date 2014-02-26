<?php
/**
* Plugin Name: Cleverwise Redirect It
* Description: Manage link redirects easily through this powerful plugin.  By using this plugin you may easily control external (or even internal) links with ease.  After adding a destination link into the system you will be provided with a link at your domain name.  This provides several advantages.  First if the destination link ever changes no problem.  You simply change it in one location and all links to it are updated.  Second you are building your domain brand since the outbound links use your domain.  Third there is no way for a visitor to tell the link destination without clicking on it, which helps save affiliate commissions.  Fourth it works well for emails as you can shorten outbound links with variables in them.
* Version: 1.2
* Author: Jeremy O'Connell
* Author URI: http://www.cyberws.com/cleverwise-plugins/
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Load Cleverwise Framework Library
////////////////////////////////////////////////////////////////////////////
include_once('cwfa.php');
$cwfa_ri=new cwfa_ri;

////////////////////////////////////////////////////////////////////////////
//	Wordpress database option
////////////////////////////////////////////////////////////////////////////
Global $wpdb,$ri_wp_option_version_txt,$ri_wp_option,$ri_wp_option_version_num;

$ri_wp_option_version_num='1.2';
$ri_wp_option='redirect_it';
$ri_wp_option_version_txt=$ri_wp_option.'_version';
$ri_wp_option_updates_txt=$ri_wp_option.'_updates';

////////////////////////////////////////////////////////////////////////////
//	Get db prefix and set correct table names
////////////////////////////////////////////////////////////////////////////
Global $cw_redirect_it_tbl;

$wp_db_prefix=$wpdb->prefix;
$cw_redirect_it_tbl=$wp_db_prefix.'redirect_it';

////////////////////////////////////////////////////////////////////////////
//	If admin panel is showing and user can manage options load menu option
////////////////////////////////////////////////////////////////////////////
if (is_admin()) {
	//	Hook admin code
	include_once("ria.php");

	//	Activation code
	register_activation_hook( __FILE__, 'cw_redirect_it_activate');

	//	Check installed version and if mismatch upgrade
	Global $wpdb;
	$ri_wp_option_db_version=get_option($ri_wp_option_version_txt);
	if ($ri_wp_option_db_version < $ri_wp_option_version_num) {
		update_option($ri_wp_option_version_txt,$ri_wp_option_version_num);
	}
}