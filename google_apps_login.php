<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Simple secure login for Wordpress through users' Google Apps accounts (uses secure OAuth2, and MFA if enabled)
 * Version: 2.3
 * Author: Dan Lester
 * Author URI: http://wp-glogin.com/
 * License: GPL3
 * Network: true
 * Text Domain: google-apps-login
 * Domain Path: /lang
 */

require_once( plugin_dir_path(__FILE__).'/core/core_google_apps_login.php' );

class basic_google_apps_login extends core_google_apps_login {
	
	protected $PLUGIN_VERSION = '2.3';
	
	// Singleton
	private static $instance = null;
	
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function ga_activation_hook($network_wide) {
		parent::ga_activation_hook($network_wide);
		
		// If installed previously, keep 'poweredby' to off (default) since they were used to that
		$old_options = get_site_option($this->get_options_name());
	
		if (!$old_options) {
			$new_options = $this->get_option_galogin();
			$new_option['ga_poweredby'] = true;
			update_site_option($this->get_options_name(), $new_option);
		}
	}
		
	protected function ga_section_text_end() {
	?>
		<p><b><?php _e( 'For full support, and premium features that greatly simplify WordPress user management for admins, please visit:' , 'google-apps-login'); ?>
		<a href="http://wp-glogin.com/google-apps-login-premium/?utm_source=Admin%20Promo&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
		</p>
	<?php
	}
	
	protected function ga_options_do_sidebar() {
		$drivelink = "http://wp-glogin.com/drive/?utm_source=Admin%20Sidebar&utm_medium=freemium&utm_campaign=Drive";
		$upgradelink = "http://wp-glogin.com/google-apps-login-premium/?utm_source=Admin%20Sidebar&utm_medium=freemium&utm_campaign=Freemium";
	?>
		<div id="gal-tableright" class="gal-tablecell">

			<div>
				<a href="<?php echo $upgradelink; ?>" target="_blank">
				<img src="<?php echo $this->my_plugin_url(); ?>img/basic_loginupgrade.png" />
				</a>
				<span>Buy our <a href="<?php echo $upgradelink; ?>" target="_blank">premium Login plugin</a> to revolutionize user management</span>
			</div>
			
			<div>
				<a href="<?php echo $drivelink; ?>" target="_blank">
				<img src="<?php echo $this->my_plugin_url(); ?>img/basic_driveplugin.png" />
				</a>
				<span>Try our <a href="<?php echo $drivelink; ?>" target="_blank">Google Drive Embedder</a> plugin</span>
			</div>
			
		</div>
	<?php
	}
	
	protected function ga_domainsection_text() {
		echo '<div id="domain-section" class="galtab">';
		
		echo '<p>'.__('The Domain Control section is only applicable to the premium version of this plugin.', 'google-apps-login').'</p>';
		echo '<p>';
		_e( 'In this basic version of the plugin, any <i>existing</i> WordPress account corresponding to a Google email address can authenticate via Google.', 'google-apps-login');
		echo '</p>';
		?>
		
		<h3>Premium Upgrade</h3>
		
		<p>In our premium plugin, you can specify your Google Apps domain name to obtain more powerful features.</p>

		<ul class="ul-disc">
			<li>Save time and increase security</li>
			<li>Completely forget about WordPress user management &ndash; it syncs users from Google Apps automatically</li>
			<li>Ensures that employees who leave or change roles no longer have unauthorized access to sensitive sites</li>
			<li>Increase engagement on corporate websites &ndash; WordPress user profiles are automatically set up with real names rather than quirky usernames</li>
		</ul>
		
		<p>Find out more about purchase options on our website:
		<a href="http://wp-glogin.com/google-apps-login-premium/?utm_source=Domain%20Control&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a>
		</p>
		
		<?php
		echo '</div>';
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
	
	protected function my_plugin_url() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			return plugins_url().'/'.basename(dirname(__FILE__)).'/';
		}
		// Normal case (non symlink)
		return plugin_dir_url( __FILE__ );
	}

}

// Global accessor function to singleton
function GoogleAppsLogin() {
	return basic_google_apps_login::get_instance();
}

// Initialise at least once
GoogleAppsLogin();

?>
