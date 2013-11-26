<?php

/**
 * Plugin Name: Google Apps Login
 * Plugin URI: http://danlester.com/wpgoogleappslogin/
 * Description: Easy login for your Wordpress users by using their Google accounts (uses OAuth2 and requires a Google Apps domain).
 * Version: 1.0
 * Author: Dan Lester
 * Author URI: http://danlester.com/
 * License: GPL3
 */

class google_apps_login {
	
	function createGoogleClient() {
		require_once 'googleclient/Google_Client.php';
		require_once 'googleclient/contrib/Google_Oauth2Service.php';
		
		$options = get_option('galogin');
		
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
	
	function ga_login_styles() { ?>
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
	
	function ga_login_form() {
		self::_ga_unset_session();
		
		$clients = self::createGoogleClient();
		$client = $clients[0]; 
		
		// Generate a CSRF token
		$state = md5(rand());
		$_SESSION['galogin_state'] = $state;
		$client->setState($state);
		
		// Store following WP page if any
		if (array_key_exists('redirect_to', $_REQUEST)) {
			$_SESSION['galogin_redirect_to'] = $_REQUEST['redirect_to'];
		}
		
		$authUrl = $client->createAuthUrl();
?>
		<div class="galogin"> 
			<a href="<?php echo $authUrl; ?>">or <b>Login with Google</b></a>
		</div>
<?php 	
	}
	
	function ga_authenticate($user) {
		if (isset($_REQUEST['error'])) {
			$user = new WP_Error('ga_login_error', $_REQUEST['error'] == 'access_denied' ? 'You did not grant access' : $_REQUEST['error']);
			return self::displayAndReturnError($user);
		}
		
		$clients = self::createGoogleClient();
		$client = $clients[0]; 
		$oauthservice = $clients[1];
		
		if (isset($_GET['code'])) {
			if (session_id() && (!isset($_REQUEST['state']) || !isset($_SESSION['galogin_state']) 
					|| $_REQUEST['state'] != $_SESSION['galogin_state'])) {
				$user = new WP_Error('ga_login_error', "Session mismatch - try again, but there could be a problem setting cookies");
				return self::displayAndReturnError($user);
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
							if (session_id() && array_key_exists('galogin_redirect_to', $_SESSION)) {
								$_SESSION['galogin_do_redirect_to'] = $_SESSION['galogin_redirect_to'];
							}
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

		// Tidy things up for next time
		self::_ga_unset_session();

		if (is_wp_error($user)) {
			self::displayAndReturnError($user);
		}
		
		return $user;
	}
	
	function displayAndReturnError($user) {
		if (is_wp_error($user) && get_bloginfo('version') < 3.7) {
			// Only newer wordpress versions display errors from $user for us
			global $error;
			$error = htmlentities2($user->get_error_message());
		}
		return $user;
	}
	
	function ga_init() {
		if(!session_id()) {
			@session_start();
		}
		if (array_key_exists('galogin_do_redirect_to', $_SESSION)) {
			// Login page originally contained a redirect url, so go there now all auth is finished
			$url = $_SESSION['galogin_do_redirect_to'];
			unset($_SESSION['galogin_do_redirect_to']);
			wp_redirect($url);
			exit;
		}
	}
	
	function _ga_unset_session() {
		// Reset session state
		if (session_id()) {
			if (array_key_exists('galogin_redirect_to', $_SESSION)) {
				unset($_SESSION['galogin_redirect_to']);
			}
			if (array_key_exists('galogin_state', $_SESSION)) {
				unset($_SESSION['galogin_state']);
				unset($_SESSION['state']);
			}
		}
	}
	
	function ga_admin_init() {
		
		register_setting( 'galogin_options', 'galogin', Array('google_apps_login', 'ga_options_validate') );
		
		add_settings_section('galogin_main_section', 'Main Settings', 
			array('google_apps_login', 'ga_section_text'), 'galogin');
		
		add_settings_field('ga_clientid', 'Client ID', 
			array('google_apps_login', 'ga_do_settings_clientid'), 'galogin', 'galogin_main_section');	
		add_settings_field('ga_clientsecret', 'Client Secret',
			array('google_apps_login', 'ga_do_settings_clientsecret'), 'galogin', 'galogin_main_section');
	}
	
	function ga_admin_menu() {
		add_options_page('Google Apps Login settings', 'Google Apps Login',
			 'manage_options', 'galogin_list_options',
			 array('google_apps_login', 'ga_options_do_page'));
	}
	
	function ga_options_do_page() {  ?>
		<div>
		<h2>Google Apps Login setup</h2>
		Set up your blog to enable Google logins.
		<form action="options.php" method="post">
		<?php settings_fields('galogin_options'); ?>
		<?php do_settings_sections('galogin'); ?>
		 
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>  <?php
	}
	
	function ga_do_settings_clientid() {
		$options = get_option('galogin');
		echo "<input id='plugin_text_string' name='galogin[ga_clientid]' size='80' type='text' value='{$options['ga_clientid']}' />";
		echo "<br /><span>Normally something like 1234567890123.apps.googleusercontent.com</span>";
	}

	function ga_do_settings_clientsecret() {
		$options = get_option('galogin');
		echo "<input id='plugin_text_string' name='galogin[ga_clientsecret]' size='40' type='text' value='{$options['ga_clientsecret']}' />";
		echo "<br /><span>Normally something like sHSfR4_jf_2jsy-kjPjgf2dT</span>";
	}
	
	function ga_section_text() {
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
			<li>Web Origin: <?php echo site_url(); ?></li>
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
		
		<?php
	}
	
	function ga_options_validate($input) {
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
	
	function ga_on_uninstall() {
		if (!current_user_can('activate_plugins'))
		return;

		// Important: Check if the file is the one
		// that was registered during the uninstall hook.
		if (!defined( 'WP_UNINSTALL_PLUGIN' ) || __FILE__ != WP_UNINSTALL_PLUGIN)
			return;

		// Remove options for plugin
		delete_option('galogin');
	}
}

add_action('login_enqueue_scripts', array('google_apps_login', 'ga_login_styles'));
add_action('login_form', array('google_apps_login', 'ga_login_form'));
add_action('authenticate', array('google_apps_login', 'ga_authenticate'));
add_action('init', array('google_apps_login', 'ga_init'), 1);

add_action('admin_init', array('google_apps_login', 'ga_admin_init'));
add_action('admin_menu', array('google_apps_login', 'ga_admin_menu'));

register_uninstall_hook(__FILE__, array('google_apps_login', 'ga_on_uninstall'));

?>