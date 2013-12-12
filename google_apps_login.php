<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Easy login for your Wordpress users by using their Google accounts (uses OAuth2 and requires a Google Apps domain).
 * Version: 1.2
 * Author: Dan Lester
 * Author URI: http://danlester.com/
 * License: GPL3
 */

require_once( plugin_dir_path(__FILE__).'/core/core_google_apps_login.php' );

class basic_google_apps_login extends core_google_apps_login {
	
	public function ga_section_text_end() {
	?>
		<p><b>For support and premium features, please visit:
		<a href="http://wp-glogin.com/?utm_source=Admin%20Panel&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
		</p>
	<?php
	}

}

$ga_google_apps_login_plugin = new basic_google_apps_login();

?>