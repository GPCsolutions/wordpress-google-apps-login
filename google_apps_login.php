<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Simple secure login for Wordpress through users' Google Apps accounts (uses secure OAuth2, and MFA if enabled)
 * Version: 1.3
 * Author: Dan Lester
 * Author URI: http://wp-glogin.com/
 * License: GPL3
 * Network: true
 */

require_once( plugin_dir_path(__FILE__).'/core/core_google_apps_login.php' );

class basic_google_apps_login extends core_google_apps_login {
	
	protected $PLUGIN_VERSION = '1.3';
	
	public function ga_section_text_end() {
	?>
		<p><b>For full support, and premium features that greatly simplify WordPress user management for admins, please visit:
		<a href="http://wp-glogin.com/?utm_source=Admin%20Panel&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
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

$ga_google_apps_login_plugin = new basic_google_apps_login();

?>