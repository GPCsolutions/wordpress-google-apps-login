<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://wp-glogin.com/
 * Description: Easy login for your Wordpress users by using their Google accounts (uses OAuth2 and requires a Google Apps domain).
 * Version: 1.1
 * Author: Dan Lester
 * Author URI: http://danlester.com/
 * License: GPL3
 */

class google_apps_login {
	
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
		require_once 'googleclient/Google_Client.php';
		require_once 'googleclient/contrib/Google_Oauth2Service.php';
		
		$client = new Google_Client();
		$client->setApplicationName("Wordpress Blog");
		
		$client->setClientId($options['ga_clientid']);
		$client->setClientSecret($options['ga_clientsecret']);
		$client->setRedirectUri(wp_login_url());
				
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
	
	public function ga_login_form() {
		$options = $this->get_option_galogin();
		$clients = $this->createGoogleClient($options);
		$client = $clients[0]; 
		
		// Generate a CSRF token
		$state = wp_create_nonce('google_apps_login');
		$client->setState(urlencode($state
				.'|'.$this->get_cookie_value()
				.'|'.(array_key_exists('redirect_to', $_REQUEST) ? $_REQUEST['redirect_to'] : '')
		));
		
		$authUrl = $client->createAuthUrl();
		if ($client->getClientId() == "") {
			$authUrl = "http://wp-glogin.com/installing-google-apps-login/#main-settings";
		}
?>
		<div class="galogin"> 
			<a href="<?php echo $authUrl; ?>">or <b>Login with Google</b></a>
		</div>
<?php 	
	}
	
	public function ga_authenticate($user) {
		if (isset($_REQUEST['error'])) {
			$user = new WP_Error('ga_login_error', $_REQUEST['error'] == 'access_denied' ? 'You did not grant access' : $_REQUEST['error']);
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
			if (count($statevars) != 3) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem computing state");
				return $this->displayAndReturnError($user);
			}
			$retnonce = $statevars[0];
			$retcookie = $statevars[1];
			$retredirectto = $statevars[2];
			
			if (!wp_verify_nonce($retnonce, 'google_apps_login')) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem setting nonce");
				return $this->displayAndReturnError($user);
			}

			if (!isset($_COOKIE['google_apps_login']) || $retcookie != $_COOKIE['google_apps_login']) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem setting cookie");
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
							$user = new WP_Error('ga_login_error', 'User '.$google_email.' not registered in Wordpress');
						}
						else {
							// Set redirect for next load - including if "" to force reset to no redirect
							setcookie('galogin_do_redirect_to', $retredirectto, time()+60, '/');
							// Reset client-side login cookie so it doesn't expire on us next login time
							setcookie('google_apps_login', '', time()-3600, '/');
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

		if (is_wp_error($user)) {
			$this->displayAndReturnError($user);
		}
		
		return $user;
	}
	
	protected function displayAndReturnError($user) {
		if (is_wp_error($user) && get_bloginfo('version') < 3.7) {
			// Only newer wordpress versions display errors from $user for us
			global $error;
			$error = htmlentities2($user->get_error_message());
		}
		return $user;
	}
	
	public function ga_init() {
		if (isset($_COOKIE['galogin_do_redirect_to'])) {
			$do_redirect = $_COOKIE['galogin_do_redirect_to'];
			setcookie('galogin_do_redirect_to', '', time()-3600, '/');
			
			if ($do_redirect != "") {
				wp_redirect($do_redirect);
				exit;
			}
		}

		if (!isset($_COOKIE['google_apps_login']) && $GLOBALS['pagenow'] == 'wp-login.php') {
			setcookie('google_apps_login', $this->get_cookie_value(), time()+1800, '/');
		}
	}
	
	public function ga_admin_init() {
		
		register_setting( 'galogin_options', 'galogin', Array($this, 'ga_options_validate') );
		
		add_settings_section('galogin_main_section', 'Main Settings', 
			array($this, 'ga_section_text'), 'galogin');
		
		add_settings_field('ga_clientid', 'Client ID', 
			array($this, 'ga_do_settings_clientid'), 'galogin', 'galogin_main_section');	
		add_settings_field('ga_clientsecret', 'Client Secret',
			array($this, 'ga_do_settings_clientsecret'), 'galogin', 'galogin_main_section');
	}
	
	public function ga_admin_menu() {
		add_options_page('Google Apps Login settings', 'Google Apps Login',
			 'manage_options', 'galogin_list_options',
			 array($this, 'ga_options_do_page'));
	}
	
	public function ga_options_do_page() {  ?>
		<div>
		<h2>Google Apps Login setup</h2>
		Set up your blog to enable Google logins.
		<form action="options.php" method="post">
		<?php settings_fields('galogin_options'); ?>
		<?php do_settings_sections('galogin'); ?>
		 
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>  <?php
	}
	
	public function ga_do_settings_clientid() {
		$options = $this->get_option_galogin();
		echo "<input id='plugin_text_string' name='galogin[ga_clientid]' size='80' type='text' value='{$options['ga_clientid']}' />";
		echo "<br /><span>Normally something like 1234567890123.apps.googleusercontent.com</span>";
	}

	public function ga_do_settings_clientsecret() {
		$options = $this->get_option_galogin();
		echo "<input id='plugin_text_string' name='galogin[ga_clientsecret]' size='40' type='text' value='{$options['ga_clientsecret']}' />";
		echo "<br /><span>Normally something like sHSfR4_jf_2jsy-kjPjgf2dT</span>";
	}
	
	public function ga_section_text() {
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
			<li>Web Origin: <?php echo (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/'; ?></li>
			<?php 
			if (is_ssl()) {
				?>
				<li>
					Web Origin (add a 2nd entry): http://<?php echo $_SERVER['HTTP_HOST'].'/'; ?>
				</li> 
				<?php
			}
			?>
			
			<li>Redirect URL: <?php echo wp_login_url(); ?></li>
			<?php 
			if (force_ssl_login() && strtolower(substr(wp_login_url(),0,7)) == 'http://') {
				?>
				<li>
					Redirect URL (add a 2nd entry): https://<?php echo substr(wp_login_url(),7); ?>
				</li> 
				<?php
			}
			?>
		</ul>
		</p>
		<p>Click Generate. You will see a Client ID and Client Secret which you must copy
		and paste into the boxes below on this screen - i.e. back in your Wordpress admin, right here.</p>
		
		<p><b>Optional:</b> In the Google Cloud Console, you can configure some things your users will see when they
		login. By default, Google will tell them they are authorizing 'Project Default Service Account', which is
		not very user friendly. You can change this to your company or blog name (and add your logo etc) by clicking
		<i>Consent screen</i> (which is another sub-menu of <i>APIs &amp; Auth</i>).
		</p> 
		
		<p><b>For support and premium features, please visit: 
			<a href="http://wp-glogin.com/?utm_source=Admin%20Panel&utm_medium=freemium&utm_campaign=Freemium" target="_blank">http://wp-glogin.com/</a></b>
		</p>
		
		<?php
	}
	
	public function ga_options_validate($input) {
		$newinput = Array();
		$newinput['ga_clientid'] = trim($input['ga_clientid']);
		$newinput['ga_clientsecret'] = trim($input['ga_clientsecret']);
		if(!preg_match('/^.{10}.*$/i', $newinput['ga_clientid'])) {
			add_settings_error(
			'ga_clientid',
			'tooshort_texterror',
			'The Client ID should be longer than that',
			'error'
			);
		}
		if(!preg_match('/^.{10}.*$/i', $newinput['ga_clientsecret'])) {
			add_settings_error(
			'ga_clientsecret',
			'tooshort_texterror',
			'The Client Secret should be longer than that',
			'error'
			);
		}
		return $newinput;
	}
	
	static $default_options = Array( 'ga_clientid' => '', 'ga_clientsecret' => '');
	private $ga_options = null;
	protected function get_option_galogin() {
		if ($this->ga_options != null) {
			return $this->ga_options;
		}
	
		$option = get_option('galogin');
	
		foreach (self::$default_options as $k => $v) {
			if (!isset($option[$k])) {
				$option[$k] = $v;
			}
		}
		$this->ga_options = $option;
		return $this->ga_options;
	}
	
	protected function add_actions() {
		add_action('login_enqueue_scripts', array($this, 'ga_login_styles'));
		add_action('login_form', array($this, 'ga_login_form'));
		add_action('authenticate', array($this, 'ga_authenticate'), 5, 3);
		add_action('init', array($this, 'ga_init'), 1);
		
		add_action('admin_init', array($this, 'ga_admin_init'));
		add_action('admin_menu', array($this, 'ga_admin_menu'));
	}
}

$ga_google_apps_login_plugin = new google_apps_login();

?>