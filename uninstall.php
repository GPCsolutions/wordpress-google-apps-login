<?php 
/* 
 * Remove plugin data 
 */

if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

if (!current_user_can('activate_plugins'))
	exit;

delete_option('galogin');

?>