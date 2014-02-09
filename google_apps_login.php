<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Simple secure login for Wordpress through users' Google Apps accounts (uses secure OAuth2, and MFA if enabled)
 * Version: 2.1
 * Author: Dan Lester
 * Author URI: http://wp-glogin.com/
 * License: GPL3
 * Network: true
 * Text Domain: google-apps-login
 * Domain Path: /lang
 */

require_once( plugin_dir_path(__FILE__).'/core/core_google_apps_login.php' );

class basic_google_apps_login extends core_google_apps_login {
	
	protected $PLUGIN_VERSION = '2.1';
	
	// Singleton
	private static $instance = null;
	
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
		
	public function ga_section_text_end() {
	?>
		<p><b><?php _e( 'For full support, and premium features that greatly simplify WordPress user management for admins, please visit:' , 'google-apps-login'); ?>
		<a href="http://wp-glogin.com/?utm_source=Admin%20Promo&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
		</p>
	<?php
	}
	
	public function my_plugin_basename() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			$basename = basename(dirname(__FILE__)).'/'.basename(__FILE__);
		}
		return $basename;
	}

}

// Global accessor function to singleton
function GoogleAppsLogin() {
	return basic_google_apps_login::get_instance();
}

// Initialise at least once
GoogleAppsLogin();

?>
