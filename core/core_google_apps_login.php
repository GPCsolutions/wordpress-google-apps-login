<?php

/**
 * Plugin component common to all versions of Google Apps Login
 */

class core_google_apps_login {
	
	protected function __construct() {
		$this->add_actions();
		register_activation_hook($this->my_plugin_basename(), array( $this, 'ga_activation_hook' ) );
	}
	
	// May be overridden in basic or premium
	public function ga_activation_hook($network_wide) {
	}
	
	public function ga_plugins_loaded() {
		load_plugin_textdomain( 'google-apps-login', false, dirname($this->my_plugin_basename()).'/lang/' );
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
	
	protected function createGoogleClient($options, $includeoauth=false) {
		// Another plugin might have already included these files
		// Unfortunately we just have to hope they have a similar enough version
		
		set_include_path(get_include_path() . PATH_SEPARATOR . plugin_dir_path(__FILE__));
		
		// Google PHP Client obtained from https://github.com/google/google-api-php-client
		// Using modified Google Client to avoid name clashes - rename process:
		// find . -type f -exec sed -i '' -e 's/Google_/GoogleGAL_/g' {} +
		
		if (!class_exists('GoogleGAL_Client')) {
			require_once( 'Google/Client.php' );
		}
		
		$client = new GoogleGAL_Client();
		$client->setApplicationName("Wordpress Site");
		
		$client->setClientId($options['ga_clientid']);
		$client->setClientSecret($options['ga_clientsecret']);
		$client->setRedirectUri($this->get_login_url());
				
		$scopes = array_unique(apply_filters('gal_gather_scopes', $this->get_default_scopes()));
		$client->setScopes($scopes);
		$client->setApprovalPrompt($options['ga_force_permissions'] ? 'force' : 'auto');
		
		$oauthservice = null;
		if ($includeoauth) {
			if (!class_exists('GoogleGAL_Service_Oauth2')) {
				require_once( 'Google/Service/Oauth2.php' );
			}
			$oauthservice = new GoogleGAL_Service_Oauth2($client);
		}
				
		return Array($client, $oauthservice);
	}
	
	protected function get_default_scopes() {
		return Array('openid', 'email', 'https://www.googleapis.com/auth/userinfo.profile');
	}
	
	public function ga_login_styles() {
		$options = $this->get_option_galogin();
		wp_enqueue_script('jquery');
		 ?>
	    <style type="text/css">
	    	form#loginform p.galogin {
				background: none repeat scroll 0 0 #2EA2CC;
			    border-color: #0074A2;
			    box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
			    color: #FFFFFF;
			    text-decoration: none;
	            text-align: center;
	            vertical-align: middle;
	            border-radius: 3px;
			    padding: 4px;
			    height: 27px;
			    font-size: 14px;
			    margin-bottom: <?php echo $options['ga_poweredby'] ? '6' : '16' ?>px;
	        }
	        
	        form#loginform p.galogin a {
	        	color: #FFFFFF;
	        	line-height: 27px;
	        	font-weight: bold;
	        }

        	form#loginform p.galogin a:hover {
	        	color: #CCCCCC;
	        }
	        
	        h3.galogin-or {
	        	text-align: center;
	        	margin-top: 16px;
	        	margin-bottom: 16px;
	        }
	        
	        p.galogin-powered {
			    font-size: 0.7em;
			    font-style: italic;
			    text-align: right;
	        }
	        
	        p.galogin-logout {
	          	background-color: #FFFFFF;
    			border: 4px solid #CCCCCC;
    			box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
    			padding: 12px;
    			margin: 12px 0px;
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
		$options = $this->get_option_galogin();
		
		$authUrl = $this->ga_start_auth_get_url();
		
		$do_autologin = false;
		
		if (isset($_GET['gaautologin'])) { // This GET param can always override the option set in admin panel
			$do_autologin = $_GET['gaautologin'] == 'true';
		}
		elseif ($options['ga_auto_login']) {
			// Respect the option unless GET params mean we should remain on login page (e.g. ?loggedout=true)
			if (count($_GET) == (isset($_GET['redirect_to']) ? 1 : 0) 
									+ (isset($_GET['reauth']) ? 1 : 0) 
									+ (isset($_GET['action']) && $_GET['action']=='login' ? 1 : 0)) {
				$do_autologin = true;
			}
		}
		
		if ($do_autologin && $options['ga_clientid'] != '' && $options['ga_clientsecret'] != '') {
			if (!headers_sent()) {
				wp_redirect($authUrl);
				exit;
			}
			else { ?>
				<p><b><?php printf( __( 'Redirecting to <a href="%s">Login via Google</a>...' , 'google-apps-login'), $authUrl ); ?></b></p>
				<script type="text/javascript">
				window.location = "<?php echo $authUrl; ?>";
				</script>
			<?php 
			}
		}
		
?>
		<p class="galogin"> 
			<a href="<?php echo $authUrl; ?>"><?php echo esc_html($this->get_login_button_text()); ?></a>
		</p>
		
		<?php if ($options['ga_poweredby']) { ?>
		<p class='galogin-powered'><?php esc_html_e( 'Powered by ' , 'google-apps-login'); ?><a href='http://wp-glogin.com/?utm_source=Login%20Form&utm_medium=freemium&utm_campaign=LoginForm' target="_blank">wp-glogin.com</a></p>
		<?php } ?>
		
		<script>
		jQuery(document).ready(function(){
			<?php ob_start(); /* Buffer javascript contents so we can run it through a filter */ ?>
			
	        var loginform = jQuery('#loginform,#front-login-form');
	        var googlelink = jQuery('p.galogin');
	        var poweredby = jQuery('p.galogin-powered');

	        <?php if ($this->should_hidewplogin($options)) { ?>
				loginform.empty();
			<?php 
			} else {
			?>
	        	loginform.prepend("<h3 class='galogin-or'><?php esc_html_e( 'or' , 'google-apps-login'); ?></h3>");
	        <?php } ?>
	        
	        if (poweredby) {
	        	loginform.prepend(poweredby);
	        }
	        loginform.prepend(googlelink);

	        <?php 
	        	$fntxt = ob_get_clean(); 
	        	echo apply_filters('gal_login_form_readyjs', $fntxt);
			?>
		});
		</script>
<?php 	
	}
	
	protected function get_login_button_text() {
		return __( 'Login with Google' , 'google-apps-login');
	}
	
	protected function should_hidewplogin($options) {
		return false;
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
					$error_message = __( 'You did not grant access' , 'google-apps-login');
				break;
				case 'ga_needs_configuring':
					$error_message = __( 'The admin needs to configure Google Apps Login plugin - please follow '
										.'<a href="http://wp-glogin.com/installing-google-apps-login/#main-settings"'
										.' target="_blank">instructions here</a>' , 'google-apps-login');
				break;
				default:
					$error_message = htmlentities2($_REQUEST['error']);
				break;
			}
			$user = new WP_Error('ga_login_error', $error_message);
			return $this->displayAndReturnError($user);
		}
		
		$options = $this->get_option_galogin();
		
		if (isset($_GET['code'])) {
			if (!isset($_REQUEST['state'])) {
				$user = new WP_Error('ga_login_error', __( "Session mismatch - try again, but there could be a problem setting state" , 'google-apps-login') );
				return $this->displayAndReturnError($user);
			}
			
			$statevars = explode('|', urldecode($_REQUEST['state']));
			if (count($statevars) != 2) {
				$user = new WP_Error('ga_login_error', __( "Session mismatch - try again, but there could be a problem passing state" , 'google-apps-login') );
				return $this->displayAndReturnError($user);
			}
			$retnonce = $statevars[0];
			$retredirectto = $statevars[1];
			
			if (!wp_verify_nonce($retnonce, 'google_apps_login-'.$this->get_cookie_value())) {
				$user = new WP_Error('ga_login_error', __( "Session mismatch - try again, but there could be a problem setting cookies" , 'google-apps-login') );
				return $this->displayAndReturnError($user);
			}

			try {
				$clients = $this->createGoogleClient($options, true);
				$client = $clients[0];
				$oauthservice = $clients[1];
				
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
				
				if ($userinfo && is_object($userinfo) && property_exists($userinfo, 'email') 
						&& property_exists($userinfo, 'verifiedEmail')) {
					
					$google_email = $userinfo->email;
					$google_verified_email = $userinfo->verifiedEmail;
					
					if (!$google_verified_email) {
						$user = new WP_Error('ga_login_error', __( 'Email needs to be verified on your Google Account' , 'google-apps-login') );
					}
					else {
						$user = get_user_by('email', $google_email);
						
						$userdidnotexist = false;
						if (!$user) {
							$userdidnotexist = true;
							$user = $this->createUserOrError($userinfo, $options);
						}
						
						if ($user && !is_wp_error($user)) {
							// In some versions, check group membership
							$this->check_groups($client, $userinfo, $user, $userdidnotexist);

							// Set redirect for wp-login to receive via our own login_redirect callback
							$this->setFinalRedirect($retredirectto);
							
							// Call hook in case another plugin wants to use the user's data
							do_action('gal_user_loggedin', $user, $userinfo, $userdidnotexist, $client, $oauthservice);
						}
					}
				}
				else {
					$user = new WP_Error('ga_login_error', __( "User authenticated OK, but error fetching user details from Google" , 'google-apps-login') );
				}
			} catch (GoogleGAL_Exception $e) {
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
		return( new WP_Error('ga_login_error', sprintf( __( 'User %s not registered in Wordpress' , 'google-apps-login'),
												 $userinfo->email) ) );
	}

	protected function checkRegularWPLogin($user, $username, $password, $options) {
		return $user;
	}
	
	// Has content in Premium
	protected function checkRegularWPError($user, $username, $password) {
	}
	
	// Has content in Enterprise
	protected function check_groups($client, $userinfo, $user, $userdidnotexist) {
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
			setcookie('google_apps_login', $this->get_cookie_value(), time()+1800, '/', defined(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '' );
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
		echo '<div class="error"><p>';
        echo sprintf( __('You will need to complete Google Apps Login <a href="%s">Settings</a> in order for the plugin to work', 'google-apps-login'), 
        			esc_url($this->get_settings_url()) ); 
        echo '</p></div>';
	}
	
	public function ga_admin_init() {
		register_setting( $this->get_options_pagename(), $this->get_options_name(), Array($this, 'ga_options_validate') );
				
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
		else {
			$this->set_other_admin_notices();
		}
		
		add_action('show_user_profile', Array($this, 'ga_personal_options'));
	}
	
	public function ga_personal_options($wp_user) {
		if (is_object($wp_user)) {
		// Display avatar in profile
		$purchase_url = 'http://wp-glogin.com/avatars/?utm_source=Profile%20Page&utm_medium=freemium&utm_campaign=Avatars';
		$source_text = 'Install <a href="'.$purchase_url.'">Google Profile Avatars</a> to use your Google account\'s profile photo here automatically.';
		?>
		<table class="form-table">
			<tbody><tr>
				<th>Profile Photo</label></th>
				<td><?php echo get_avatar($wp_user->ID, '48'); ?></td>
				<td><?php echo apply_filters('gal_avatar_source_desc', $source_text, $wp_user); ?></td>
			</tr>
			</tbody>
		</table>
		<?php
		}
	}
	
	// Has content in Basic
	protected function set_other_admin_notices() {
	}
	
	public function ga_admin_menu() {
		if (is_multisite()) {
			add_submenu_page( 'settings.php', __( 'Google Apps Login settings' , 'google-apps-login'), __( 'Google Apps Login' , 'google-apps-login'),
				'manage_network_options', $this->get_options_menuname(),
				array($this, 'ga_options_do_page'));
		}
		else {
			add_options_page( __( 'Google Apps Login settings' , 'google-apps-login'), __( 'Google Apps Login' , 'google-apps-login'),
				 'manage_options', $this->get_options_menuname(),
				 array($this, 'ga_options_do_page'));
		}
	}
	
	public function ga_options_do_page() {

		wp_enqueue_script( 'gal_admin_js', $this->my_plugin_url().'js/gal-admin.js', array('jquery') );
		wp_enqueue_style( 'gal_admin_css', $this->my_plugin_url().'css/gal-admin.css' );

		$submit_page = is_multisite() ? 'edit.php?action='.$this->get_options_menuname() : 'options.php';
		
		if (is_multisite()) {
			$this->ga_options_do_network_errors();
		}
		?>
		  
		<div>
		
		<h2><?php _e('Google Apps Login setup', 'google-apps-login'); ?></h2>
		
		<div id="gal-tablewrapper">
		
		<div id="gal-tableleft" class="gal-tablecell">
		
		<p><?php _e( 'To set up your website to enable Google logins, you will need to follow instructions specific to your website.', 'google-apps-login'); ?></p>
		
		<p><a href="<?php echo $this->calculate_instructions_url(); ?>#config" id="gal-personalinstrlink" class="button-secondary" target="gainstr"><?php 
		_e( 'Click here to open your personalized instructions in a new window' , 'google-apps-login'); ?></a></p>
		
	
		<?php 
		$this->ga_section_text_end();
		?>
		
		<h2 id="gal-tabs" class="nav-tab-wrapper">
			<a href="#main" id="main-tab" class="nav-tab nav-tab-active">Main Setup</a>
			<a href="#domain" id="domain-tab" class="nav-tab">Domain Control</a>
			<a href="#advanced" id="advanced-tab" class="nav-tab">Advanced Options</a>
			<?php $this->draw_more_tabs(); ?>
		</h2>
				
		
		<form action="<?php echo $submit_page; ?>" method="post" id="gal_form">
		
		<?php 
		settings_fields($this->get_options_pagename());
		$this->ga_mainsection_text();
		$this->ga_domainsection_text();
		$this->ga_advancedsection_text();
		$this->ga_moresection_text();
		?>
		
		<p class="submit">
			<input type="submit" value="<?php esc_attr_e( 'Save Changes' , 'google-apps-login'); ?>" class="button button-primary" id="submit" name="submit">
		</p>
		</form>
		</div>

		<?php $this->ga_options_do_sidebar(); ?>

		</div>
		
		</div>  <?php
	}
	
	// Extended in premium
	protected function draw_more_tabs() {
	}
	
	// Extended in premium
	protected function ga_moresection_text() {
	}
	
	// Has content in Basic
	protected function ga_options_do_sidebar() {
	}
	
	protected function ga_options_do_network_errors() {
		if (isset($_REQUEST['updated']) && $_REQUEST['updated']) {
			?>
				<div id="setting-error-settings_updated" class="updated settings-error">
				<p>
				<strong><?php _e( 'Settings saved.', 'google-apps-login'); ?></strong>
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
	
	protected function ga_mainsection_text() {
		echo '<div id="main-section" class="galtab active">';
		echo '<p>';
		echo sprintf( __( "The <a href='%s'>instructions</a> above will guide you to Google's Cloud Console where you will enter two URLs, and also obtain two codes (Client ID and Client Secret) which you will need to enter in the boxes below.",
			 'google-apps-login'), $this->calculate_instructions_url()."#config" );
		echo '</p>';
		
		$options = $this->get_option_galogin();
		echo '<label for="input_ga_clientid" class="textinput big">'.__('Client ID', 'google-apps-login').'</label>';
		echo "<input id='input_ga_clientid' class='textinput' name='".$this->get_options_name()."[ga_clientid]' size='68' type='text' value='".esc_attr($options['ga_clientid'])."' />";
		echo '<br class="clear"/><p class="desc big">';
		printf( __('Normally something like %s', 'google-apps-login'), '1234567890123-w1dwn5pfgjeo96c73821dfbof6n4kdhw.apps.googleusercontent.com' );
		echo '</p>';
		
		echo '<label for="input_ga_clientsecret" class="textinput big">'.__('Client Secret', 'google-apps-login').'</label>';
		echo "<input id='input_ga_clientsecret' class='textinput' name='".$this->get_options_name()."[ga_clientsecret]' size='40' type='text' value='".esc_attr($options['ga_clientsecret'])."' />";
		echo '<br class="clear" /><p class="desc big">';
		printf( __('Normally something like %s', 'google-apps-login'), 'sHSfR4_jf_2jsy-kjPjgf2dT' );
		echo '</p>';
		
		echo '</div>';
	}
	
	// Has content in Basic
	protected function ga_section_text_end() {
	}
	
	// Has content in Premium
	protected function ga_domainsection_text() {
	}
		
	protected function ga_advancedsection_text() {
		echo '<div id="advanced-section" class="galtab">';
		echo '<p>';
		printf( __('Once you have the plugin working, you can try these settings to customize the login flow for your users.', 'google-apps-login')
				.' '.__('See <a href="%s" target="gainstr">instructions here</a>.', 'google-apps-login'),
				$this->calculate_instructions_url('a').'#advanced' );
		echo '</p>';

		$options = $this->get_option_galogin();
		
		echo "<input id='input_ga_force_permissions' name='".$this->get_options_name()."[ga_force_permissions]' type='checkbox' ".($options['ga_force_permissions'] ? 'checked' : '')." class='checkbox' />";
		echo '<label for="input_ga_force_permissions" class="checkbox plain">';
		_e( 'Force user to confirm Google permissions every time' , 'google-apps-login' );
		echo '</label>';
		
		echo '<br class="clear" />';
		
		echo "<input id='input_ga_auto_login' name='".$this->get_options_name()."[ga_auto_login]' type='checkbox' ".($options['ga_auto_login'] ? 'checked' : '')." class='checkbox' />";
		
		echo '<label for="input_ga_auto_login" class="checkbox plain">';
		_e( 'Automatically redirect to Google from login page' , 'google-apps-login' );
		echo '</label>';
		
		echo '<br class="clear" />';
		
		echo "<input id='input_ga_poweredby' name='".$this->get_options_name()."[ga_poweredby]' type='checkbox' ".($options['ga_poweredby'] ? 'checked' : '')." class='checkbox' />";
		
		echo '<label for="input_ga_poweredby" class="checkbox plain">';
		_e( 'Display \'Powered By wp-glogin.com\' on Login form' , 'google-apps-login' );
		echo '</label>';
		
		$this->ga_advancedsection_extra();
		
		echo '<br class="clear" />';
		
		if (is_multisite()) {
			echo '<h3>'.__( 'Multisite Options' , 'google-apps-login').'</h3><p>';
			printf( __('This setting is for multisite admins only. See <a href="%s" target="gainstr">instructions here</a>.', 'google-apps-login')
			, $this->calculate_instructions_url('m').'#multisite' );
			echo '</p>';
			echo "<input id='input_ga_ms_usesubsitecallback' name='".$this->get_options_name()."[ga_ms_usesubsitecallback]' type='checkbox' ".($options['ga_ms_usesubsitecallback'] ? 'checked' : '')." class='checkbox'/>";
				
			echo '<label for="input_ga_ms_usesubsitecallback" class="checkbox plain">'.__( 'Use sub-site specific callback from Google' , 'google-apps-login').'</label>';
			echo '<br class="clear" />';
			
			echo '<p class="desc">';
			_e( 'Leave unchecked if in doubt' , 'google-apps-login');
			echo '</p>';
		}
		
		echo '</div>';
	}
	
	// Overridden in Commercial
	protected function ga_advancedsection_extra() {
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
		$newinput['ga_ms_usesubsitecallback'] = isset($input['ga_ms_usesubsitecallback']) ? (boolean)$input['ga_ms_usesubsitecallback'] : false;
		$newinput['ga_force_permissions'] = isset($input['ga_force_permissions']) ? (boolean)$input['ga_force_permissions'] : false;
		$newinput['ga_auto_login'] = isset($input['ga_auto_login']) ? (boolean)$input['ga_auto_login'] : false;
		$newinput['ga_poweredby'] = isset($input['ga_poweredby']) ? (boolean)$input['ga_poweredby'] : false;
		$newinput['ga_version'] = $this->PLUGIN_VERSION;
		return $newinput;
	}
	
	protected function get_error_string($fielderror) {
		$local_error_strings = Array(
				'ga_clientid|tooshort_texterror' => __('The Client ID should be longer than that', 'google-apps-login') ,
				'ga_clientsecret|tooshort_texterror' => __('The Client Secret should be longer than that', 'google-apps-login') 
		);
		if (isset($local_error_strings[$fielderror])) {
			return $local_error_strings[$fielderror];
		}
		return __( 'Unspecified error' , 'google-apps-login');
	}
	
	protected function get_options_name() {
		return 'galogin';
	}

	protected function get_default_options() {
		return Array('ga_version' => $this->PLUGIN_VERSION, 
						'ga_clientid' => '', 
						'ga_clientsecret' => '', 
						'ga_ms_usesubsitecallback' => false,
						'ga_force_permissions' => false,
						'ga_auto_login' => false,
						'ga_poweredby' => false);
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
	
	protected function calculate_instructions_url($refresh='n') {
		return add_query_arg(
					array( 'garedirect' => urlencode( $this->get_login_url() ),
							'gaorigin' => urlencode( (is_ssl() || force_ssl_login() || force_ssl_admin() 
											? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/' ),
							'ganotms' => is_multisite() ? 'false' : 'true',
							'gar' => urlencode( $refresh ),
							'utm_source' => 'Admin%20Instructions',
							'utm_medium' => 'freemium',
							'utm_campaign' => 'Freemium' ),
						$this->get_wpglogincom_baseurl()
				);
	}
	
	protected function get_wpglogincom_baseurl() {
		return 'http://wp-glogin.com/installing-google-apps-login/basic-setup/';
	}
	
	// Google Apps Login platform
	
	public function gal_get_clientid() {
		$options = $this->get_option_galogin();
		return $options['ga_clientid'];
	}
	
	// PLUGINS PAGE
	
	public function ga_plugin_action_links( $links, $file ) {
		if ($file == $this->my_plugin_basename()) {
			$settings_link = '<a href="'.$this->get_settings_url().'">'.__( 'Settings' , 'google-apps-login').'</a>';
			array_unshift( $links, $settings_link );
		}
	
		return $links;
	}
	
	// HOOKS AND FILTERS
	// *****************
	
	protected function add_actions() {		
		add_action('plugins_loaded', array($this, 'ga_plugins_loaded'));
		
		add_action('login_enqueue_scripts', array($this, 'ga_login_styles'));
		add_action('login_form', array($this, 'ga_login_form'));
		add_action('woocommerce_login_form', array($this, 'ga_login_form'));
		add_filter('authenticate', array($this, 'ga_authenticate'), 5, 3);
		
		add_filter('login_redirect', array($this, 'ga_login_redirect'), 5, 3 );
		add_action('init', array($this, 'ga_init'), 1);
		
		add_action('admin_init', array($this, 'ga_admin_init'), 5, 0);
				
		add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', array($this, 'ga_admin_menu'));
		
		add_filter('gal_get_clientid', Array($this, 'gal_get_clientid') );

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
