<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Simple secure login for Wordpress through users' Google Apps accounts (uses secure OAuth2, and MFA if enabled)
 * Version: 2.2
 * Author: Dan Lester
 * Author URI: http://wp-glogin.com/
 * License: GPL3
 * Network: true
 * Text Domain: google-apps-login
 * Domain Path: /lang
 */

require_once( plugin_dir_path(__FILE__).'/core/core_google_apps_login.php' );

class basic_google_apps_login extends core_google_apps_login {
	
	protected $PLUGIN_VERSION = '2.2';
	
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
		<a href="http://wp-glogin.com/google-apps-login-premium/?utm_source=Admin%20Promo&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
		</p>
	<?php
	}
	
	protected function set_other_admin_notices() {
		global $pagenow;
		if (in_array($pagenow, array('users.php', 'user-new.php')) ) {
			$no_thanks = get_site_option($this->get_options_name().'_no_thanks', false);
			if (!$no_thanks) {
				if (isset($_REQUEST['google_apps_login_action']) && $_REQUEST['google_apps_login_action']=='no_thanks') {
					$this->ga_said_no_thanks(null);
				}
				
				add_action('admin_notices', Array($this, 'ga_user_screen_upgrade_message'));
				if (is_multisite()) {
					add_action('network_admin_notices', Array($this, 'ga_user_screen_upgrade_message'));
				}
			}
		}
	}
	
	public function ga_said_no_thanks( $data ) {
	   	update_site_option($this->get_options_name().'_no_thanks', true);
		wp_redirect( remove_query_arg( 'google_apps_login_action' ) );
		exit;
	}
	
	public function ga_user_screen_upgrade_message() {
		$purchase_url = 'http://wp-glogin.com/google-apps-login-premium/?utm_source=User%20Pages&utm_medium=freemium&utm_campaign=Freemium';
		$nothanks_url = add_query_arg( 'google_apps_login_action', 'no_thanks' );
		echo '<div class="updated"><p>';
		echo sprintf( __('Completely forget about WordPress user management - upgrade to <a href="%s">Google Apps Login premium</a> to automatically sync users from your Google Apps domain', 'google-apps-login'),
				$purchase_url );
		echo ' &nbsp; <a href="'.$purchase_url.'" class="button-secondary">' . __( 'Purchase', 'google-apps-login' ) . '</a>';
		echo '&nbsp;<a href="' . esc_url( $nothanks_url ) . '" class="button-secondary">' . __( 'No Thanks', 'google-apps-login' ) . '</a>';
		echo '</p></div>';
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
