<?php

/**
 * Plugin component common to all versions of Google Apps Login
 */

class core_google_apps_login {
	
	public function __construct() {
		$this->add_actions();
	}
	
	protected $newcookievalue = null;
	protected function get_cookie_value() {
		if (!$this->newcookievalue) {
			if (isset($_COOKIE['google_apps_login'])) {
				$this->newcookievalue = $_COOKIE['google_apps_login'];
			}
			else {
				$this->newcookievalue = md5(rand());
			}
		}
		return $this->newcookievalue;
	}
	
	protected function createGoogleClient($options) {
		require_once( plugin_dir_path(__FILE__).'/../googleclient/Google_Client.php' );
		require_once( plugin_dir_path(__FILE__).'/../googleclient/contrib/Google_Oauth2Service.php' );
		
		$client = new Google_Client();
		$client->setApplicationName("Wordpress Blog");
		
		$client->setClientId($options['ga_clientid']);
		$client->setClientSecret($options['ga_clientsecret']);
		$client->setRedirectUri($this->get_login_url());
				
		$client->setScopes(Array('openid', 'email', 'https://www.googleapis.com/auth/userinfo.profile'));
		$client->setApprovalPrompt('auto');
		
		$oauthservice = new Google_Oauth2Service($client);
		
		return Array($client, $oauthservice);
	}
	
	public function ga_login_styles() { ?>
	    <style type="text/css">
	        form#loginform div.galogin {
	        	float: right;
	        	margin-top: 28px;
	        	background: #DFDFDF;
	            text-align: center;
	            vertical-align: middle;
	            border-radius: 3px;
			    padding: 2px;
			    width: 58%;
			    height: 27px;
	        }
	        
	        form#loginform div.galogin a {
	        	color: #21759B;
	        	position: relative;
	        	top: 6px;
	        }

        	form#loginform div.galogin a:hover {
	        	color: #278AB7;
	        }
	        
	        .login .button-primary {
    			float: none;
    			margin-top: 10px;
			}
	    </style>
	<?php }
	
	// public in case widgets want to use it
	public function ga_start_auth_get_url() {
		$options = $this->get_option_galogin();
		$clients = $this->createGoogleClient($options);
		$client = $clients[0];
		
		// Generate a CSRF token
		$client->setState(urlencode(
				wp_create_nonce('google_apps_login-'.$this->get_cookie_value())
				.'|'.$this->get_redirect_url()
		));
		
		$authUrl = $client->createAuthUrl();
		if ($options['ga_clientid'] == '' || $options['ga_clientsecret'] == '') {
			$authUrl = "?error=ga_needs_configuring";
		}
		return $authUrl;
	}
	
	public function ga_login_form() {
		$authUrl = $this->ga_start_auth_get_url();
?>
		<div class="galogin"> 
			<a href="<?php echo $authUrl; ?>">or <b>Login with Google</b></a>
		</div>
<?php 	
	}
	
	protected function get_redirect_url() {
		$options = $this->get_option_galogin();
		
		if (array_key_exists('redirect_to', $_REQUEST) && $_REQUEST['redirect_to']) {
			return $_REQUEST['redirect_to'];
		} elseif (is_multisite() && !$options['ga_ms_usesubsitecallback']) {
			return admin_url(); // This is what WordPress would choose as default
								// but we have to specify explicitly since all callbacks go via root site
		}
		return '';
	}
	
	public function ga_authenticate($user, $username=null, $password=null) {
		if (isset($_REQUEST['error'])) {
			switch ($_REQUEST['error']) {
				case 'access_denied':
					$error_message = 'You did not grant access';
				break;
				case 'ga_needs_configuring':
					$error_message = 'The admin needs to configure Google Apps Login plugin - please follow '
										.'<a href="http://wp-glogin.com/installing-google-apps-login/#main-settings"'
										.' target="_blank">instructions here</a>';
				break;
				default:
					$error_message = htmlentities2($_REQUEST['error']);
				break;
			}
			$user = new WP_Error('ga_login_error', $error_message);
			return $this->displayAndReturnError($user);
		}
		
		$options = $this->get_option_galogin();
		$clients = $this->createGoogleClient($options);
		$client = $clients[0]; 
		$oauthservice = $clients[1];
		
		if (isset($_GET['code'])) {
			if (!isset($_REQUEST['state'])) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem setting state");
				return $this->displayAndReturnError($user);
			}
			
			$statevars = explode('|', urldecode($_REQUEST['state']));
			if (count($statevars) != 2) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem passing state");
				return $this->displayAndReturnError($user);
			}
			$retnonce = $statevars[0];
			$retredirectto = $statevars[1];
			
			if (!wp_verify_nonce($retnonce, 'google_apps_login-'.$this->get_cookie_value())) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem setting cookies");
				return $this->displayAndReturnError($user);
			}

			try {
				$client->authenticate($_GET['code']);
							
				/*  userinfo example:
				 "id": "115886881859296909934",
				 "email": "dan@danlester.com",
				 "verified_email": true,
				 "name": "Dan Lester",
				 "given_name": "Dan",
				 "family_name": "Lester",
				 "link": "https://plus.google.com/115886881859296909934",
				 "picture": "https://lh3.googleusercontent.com/-r4WThnaSX8o/AAAAAAAAAAI/AAAAAAAAABE/pEJQwH5wyqM/photo.jpg",
				 "gender": "male",
				 "locale": "en-GB",
				 "hd": "danlester.com"
				 */
				$userinfo = $oauthservice->userinfo->get();
				if ($userinfo && is_array($userinfo) && array_key_exists('email', $userinfo) 
						&& array_key_exists('verified_email', $userinfo)) {
					
					$google_email = $userinfo['email'];
					$google_verified_email = $userinfo['verified_email'];
					
					if (!$google_verified_email) {
						$user = new WP_Error('ga_login_error', 'Email needs to be verified on your Google Account');
					}
					else {
						$user = get_user_by('email', $google_email);
						
						if (!$user) {
							$user = $this->createUserOrError($userinfo, $options);
						}
						
						if ($user && !is_wp_error($user)) {
							// Set redirect for wp-login to receive via our own login_redirect callback
							$this->setFinalRedirect($retredirectto);
							// Would reset client-side login cookie but won't work on redirect
						}
					}
				}
				else {
					$user = new WP_Error('ga_login_error', "User authenticated OK, but error fetching user details from Google");
				}
			} catch (Google_Exception $e) {
				$user = new WP_Error('ga_login_error', $e->getMessage());
			}
		}
		else {
			$user = $this->checkRegularWPLogin($user, $username, $password, $options);
		}
		
		if (is_wp_error($user)) {
			$this->checkRegularWPError($user, $username, $password); // May exit			
			$this->displayAndReturnError($user);
		}
		
		return $user;
	}
	
	protected function createUserOrError($userinfo, $options) {
		return( new WP_Error('ga_login_error', 'User '.$userinfo['email'].' not registered in Wordpress') );
	}

	protected function checkRegularWPLogin($user, $username, $password, $options) {
		return $user;
	}
	
	protected function checkRegularWPError($user, $username, $password) {
	}
		
	protected function displayAndReturnError($user) {
		if (is_wp_error($user) && get_bloginfo('version') < 3.7) {
			// Only newer wordpress versions display errors from $user for us
			global $error;
			$error = htmlentities2($user->get_error_message());
		}
		return $user;
	}
	
	protected $_final_redirect = '';
	
	protected function setFinalRedirect($redirect_to) {
		$this->_final_redirect = $redirect_to;
	}

	protected function getFinalRedirect() {
		return $this->_final_redirect;
	}
	
	public function ga_login_redirect($redirect_to, $request_from, $user) {
		if ($user && !is_wp_error($user)) {
			$final_redirect = $this->getFinalRedirect();
			if ($final_redirect !== '') {
				return $final_redirect;
			}
		}
		return $redirect_to;
	}
	
	public function ga_init() {
		if (!isset($_COOKIE['google_apps_login']) && $GLOBALS['pagenow'] == 'wp-login.php') {
			setcookie('google_apps_login', $this->get_cookie_value(), time()+600, '/', COOKIE_DOMAIN);
		}
	}
	
	protected function get_login_url() {
		$options = $this->get_option_galogin();
		$login_url = wp_login_url();

		if (is_multisite() && !$options['ga_ms_usesubsitecallback']) {
			$login_url = network_site_url('wp-login.php');
		} 

		if ((force_ssl_login() || force_ssl_admin()) && strtolower(substr($login_url,0,7)) == 'http://') {
			$login_url = 'https://'.substr($login_url,7);
		}
		
		return $login_url;
	}
	
	// ADMIN AND OPTIONS
	// *****************

	protected function get_options_menuname() {
		return 'galogin_list_options';
	}
	
	protected function get_options_pagename() {
		return 'galogin_options';
	}
	
	protected function get_settings_url() {
		return is_multisite()
			? network_admin_url( 'settings.php?page='.$this->get_options_menuname() )
			: admin_url( 'options-general.php?page='.$this->get_options_menuname() );
	}
	
	public function ga_admin_auth_message() {
		?>
		<div class="error">
        	<p>You will need to complete Google Apps Login 
        		<a href="<?php echo $this->get_settings_url(); ?>">Settings</a> 
        		in order for the plugin to work
        	</p>
    	</div> <?php
	}
	
	public function ga_admin_init() {
		register_setting( $this->get_options_pagename(), $this->get_options_name(), Array($this, 'ga_options_validate') );
		
		$this->ga_admin_init_main();
		$this->ga_admin_init_domain();
		$this->ga_admin_init_multisite();
		
		// Admin notice that configuration is required
		$options = $this->get_option_galogin();
		
		if (current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) 
				&& ($options['ga_clientid'] == '' || $options['ga_clientsecret'] == '')) {

			if (!array_key_exists('page', $_REQUEST) || $_REQUEST['page'] != $this->get_options_menuname()) {
				add_action('admin_notices', Array($this, 'ga_admin_auth_message'));
				if (is_multisite()) {
					add_action('network_admin_notices', Array($this, 'ga_admin_auth_message'));
				}
			}
		}
	}
	
	protected function ga_admin_init_main() {
		add_settings_section('galogin_main_section', 'Main Settings',
		array($this, 'ga_mainsection_text'), $this->get_options_name());
		
		add_settings_field('ga_clientid', 'Client ID',
		array($this, 'ga_do_settings_clientid'), $this->get_options_name(), 'galogin_main_section');
		add_settings_field('ga_clientsecret', 'Client Secret',
		array($this, 'ga_do_settings_clientsecret'), $this->get_options_name(), 'galogin_main_section');
	}
	
	protected function ga_admin_init_domain() {
	}
	
	public function ga_admin_init_multisite() {
		if (is_multisite()) {
			add_settings_section('galogin_multisite_section', 'Multisite Options',
			array($this, 'ga_multisitesection_text'), $this->get_options_name());
			
			add_settings_field('ga_ms_usesubsitecallback', 'Use sub-site specific callback from Google',
			array($this, 'ga_do_settings_ms_usesubsitecallback'), $this->get_options_name(), 'galogin_multisite_section');
		}
	}
		
	public function ga_admin_menu() {
		if (is_multisite()) {
			add_submenu_page( 'settings.php', 'Google Apps Login settings', 'Google Apps Login',
				'manage_network_options', $this->get_options_menuname(),
				array($this, 'ga_options_do_page'));
		}
		else {
			add_options_page('Google Apps Login settings', 'Google Apps Login',
				 'manage_options', $this->get_options_menuname(),
				 array($this, 'ga_options_do_page'));
		}
	}
	
	public function ga_options_do_page() {
		$submit_page = is_multisite() ? 'edit.php?action='.$this->get_options_menuname() : 'options.php';
		
		if (is_multisite()) {
			$this->ga_options_do_network_errors();
		}
		?>
		  
		<div>
		<h2>Google Apps Login setup</h2>
		Set up your website to enable Google logins.
		<form action="<?php echo $submit_page; ?>" method="post">
		<?php settings_fields($this->get_options_pagename()); ?>
		<?php do_settings_sections($this->get_options_name()); ?>
		 
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>  <?php
	}
	
	protected function ga_options_do_network_errors() {
		if (isset($_REQUEST['updated']) && $_REQUEST['updated']) {
			?>
				<div id="setting-error-settings_updated" class="updated settings-error">
				<p>
				<strong>Settings saved.</strong>
				</p>
				</div>
			<?php
		}

		if (isset($_REQUEST['error_setting']) && is_array($_REQUEST['error_setting'])
			&& isset($_REQUEST['error_code']) && is_array($_REQUEST['error_code'])) {
			$error_code = $_REQUEST['error_code'];
			$error_setting = $_REQUEST['error_setting'];
			if (count($error_code) > 0 && count($error_code) == count($error_setting)) {
				for ($i=0; $i<count($error_code) ; ++$i) {
					?>
				<div id="setting-error-settings_<?php echo $i; ?>" class="error settings-error">
				<p>
				<strong><?php echo htmlentities2($this->get_error_string($error_setting[$i].'|'.$error_code[$i])); ?></strong>
				</p>
				</div>
					<?php
				}
			}
		}
	}
	
	public function ga_do_settings_clientid() {
		$options = $this->get_option_galogin();
		echo "<input id='input_ga_domainname' name='".$this->get_options_name()."[ga_clientid]' size='80' type='text' value='{$options['ga_clientid']}' />";
		echo "<br /><span>Normally something like 1234567890123.apps.googleusercontent.com</span>";
	}

	public function ga_do_settings_clientsecret() {
		$options = $this->get_option_galogin();
		echo "<input id='input_ga_clientsecret' name='".$this->get_options_name()."[ga_clientsecret]' size='40' type='text' value='{$options['ga_clientsecret']}' />";
		echo "<br /><span>Normally something like sHSfR4_jf_2jsy-kjPjgf2dT</span>";
	}
	
	public function ga_mainsection_text() {
		?>
		<p>The Google Apps domain admin needs to go to
			 <a href="https://cloud.google.com/console" target="_blank">https://cloud.google.com/console</a>. If you 
			 are not the domain admin, you may still have permissions to use the console, so just try it. If you are 
			 not using Google Apps, then just use your regular Gmail account to access the console.
		</p>
		<p>There, create a new project (any name is fine, and just leave Project ID as it is) - you may be required to 
		accept a verification phone call or SMS from Google.</p>
		
		<p>Then create a Web application within the project. To create the application, 
		you need to click into the new project, then click <i>APIs &amp; Auth</i> in the left-hand menu. 
		Click <i>Registered Apps</i> beneath that, then click the red <i>Register App</i> button.
		You can choose any name you wish, and make sure you select <i>Web Application</i> as the Platform type.
		</p>
		<p>
		Once you have created the application, you may need to open up the <i>OAuth 2.0 Client ID</i> section to be able to complete
		 the following steps.
		</p> 
		<p>You must input, into your new Google application, the following items:
		<ul style="margin-left: 10px;">
			<li>Web Origin: <?php echo (is_ssl() || force_ssl_login() || force_ssl_admin() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/'; ?></li>
			<li>Redirect URL: <?php echo $this->get_login_url(); ?></li>
		</ul>
		</p>
		<p>Click Generate. You will see a Client ID and Client Secret which you must copy
		and paste into the boxes below on this screen - i.e. back in your Wordpress admin, right here.</p>
		
		<p><b>Optional:</b> In the Google Cloud Console, you can configure some things your users will see when they
		login. By default, Google will tell them they are authorizing 'Project Default Service Account', which is
		not very user friendly. You can change this to your company or blog name (and add your logo etc) by clicking
		<i>Consent screen</i> (which is another sub-menu of <i>APIs &amp; Auth</i>).
		</p> 
		
		<?php
		
		$this->ga_section_text_end();
	}
	
	protected function ga_section_text_end() {
	}
	
	public function ga_multisitesection_text() {
		?>
			<p>These settings are for multisite admins only. By default, all logins need to be submitted via the root site
			(since that is the only Redirect URL you were asked to submit to Google Cloud Console above).
			If you have a reason to register Redirect URLs for each of your sub-sites too, tick the box below to
			have all logins submitted to the sub-site they were invoked on.
			 </p>
		<?php
	}
		
	public function ga_do_settings_ms_usesubsitecallback() {
		$options = $this->get_option_galogin();
		echo "<input id='input_ga_ms_usesubsitecallback' name='".$this->get_options_name()."[ga_ms_usesubsitecallback]' type='checkbox' ".($options['ga_ms_usesubsitecallback'] ? 'checked' : '')." />";
		echo "<div>Leave unchecked in most cases</div>";
	}
	
	public function ga_options_validate($input) {
		$newinput = Array();
		$newinput['ga_clientid'] = trim($input['ga_clientid']);
		$newinput['ga_clientsecret'] = trim($input['ga_clientsecret']);
		if(!preg_match('/^.{10}.*$/i', $newinput['ga_clientid'])) {
			add_settings_error(
			'ga_clientid',
			'tooshort_texterror',
			self::get_error_string('ga_clientid|tooshort_texterror'),
			'error'
			);
		}
		if(!preg_match('/^.{10}.*$/i', $newinput['ga_clientsecret'])) {
			add_settings_error(
			'ga_clientsecret',
			'tooshort_texterror',
			self::get_error_string('ga_clientsecret|tooshort_texterror'),
			'error'
			);
		}
		$newinput['ga_ms_usesubsitecallback'] = isset($input['ga_ms_usesubsitecallback']) ? $input['ga_ms_usesubsitecallback'] : false;
		$newinput['ga_version'] = $this->PLUGIN_VERSION;
		return $newinput;
	}
	
	protected function get_error_string($fielderror) {
		static $local_error_strings = Array(
				'ga_clientid|tooshort_texterror' => 'The Client ID should be longer than that',
				'ga_clientsecret|tooshort_texterror' => 'The Client Secret should be longer than that'
		);
		if (isset($local_error_strings[$fielderror])) {
			return $local_error_strings[$fielderror];
		}
		return 'Unspecified error';
	}
	
	protected function get_options_name() {
		return 'galogin';
	}

	protected function get_default_options() {
		return Array('ga_version' => $this->PLUGIN_VERSION, 'ga_clientid' => '', 'ga_clientsecret' => '', 'ga_ms_usesubsitecallback' => false);
	}
	
	protected $ga_options = null;
	protected function get_option_galogin() {
		if ($this->ga_options != null) {
			return $this->ga_options;
		}
	
		$option = get_site_option($this->get_options_name(), Array());
	
		$default_options = $this->get_default_options();
		foreach ($default_options as $k => $v) {
			if (!isset($option[$k])) {
				$option[$k] = $v;
			}
		}
		
		$this->ga_options = $option;
		return $this->ga_options;
	}
	
	public function ga_save_network_options() {
		check_admin_referer( $this->get_options_pagename().'-options' );
		
		if (isset($_POST[$this->get_options_name()]) && is_array($_POST[$this->get_options_name()])) {
			$inoptions = $_POST[$this->get_options_name()];
			$outoptions = $this->ga_options_validate($inoptions);
					
			$error_code = Array();
			$error_setting = Array();
			foreach (get_settings_errors() as $e) {
				if (is_array($e) && isset($e['code']) && isset($e['setting'])) {
					$error_code[] = $e['code'];
					$error_setting[] = $e['setting'];
				}
			}

			update_site_option($this->get_options_name(), $outoptions);
			
			// redirect to settings page in network
			wp_redirect(
				add_query_arg(
					array( 'page' => $this->get_options_menuname(),
							'updated' => true,
							'error_setting' => $error_setting,
							'error_code' => $error_code ),
						network_admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
	
	// PLUGINS PAGE
	
	public function ga_plugin_action_links( $links, $file ) {
		if ($file == $this->my_plugin_basename()) {
			$settings_link = '<a href="'.$this->get_settings_url().'">Settings</a>';
			array_unshift( $links, $settings_link );
		}
	
		return $links;
	}
	
	// HOOKS AND FILTERS
	// *****************
	
	protected function add_actions() {
		add_action('login_enqueue_scripts', array($this, 'ga_login_styles'));
		add_action('login_form', array($this, 'ga_login_form'));
		add_action('authenticate', array($this, 'ga_authenticate'), 5, 3);
		
		add_filter('login_redirect', array($this, 'ga_login_redirect'), 5, 3 );
		add_action('init', array($this, 'ga_init'), 1);
		
		add_action('admin_init', array($this, 'ga_admin_init'));
				
		add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', array($this, 'ga_admin_menu'));

		if (is_multisite()) {
			add_filter('network_admin_plugin_action_links', array($this, 'ga_plugin_action_links'), 10, 2 );
			add_action('network_admin_edit_'.$this->get_options_menuname(), array($this, 'ga_save_network_options'));
		}
		else {
			add_filter( 'plugin_action_links', array($this, 'ga_plugin_action_links'), 10, 2 );
		}
	}
	
}

?>