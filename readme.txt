=== Plugin Name ===
Contributors: danlester
Tags: login, google, authentication, oauth2, oauth, admin, google apps, sso, single-sign-on, auth, intranet
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 2.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Simple secure login and user management for Wordpress through your Google Apps domain 
(uses secure OAuth2, and MFA if enabled)

== Description ==

Google Apps Login allows existing Wordpress user accounts to login to the website using Google to securely authenticate 
their account. This means that if they are already logged into Gmail for example, they can simply click their way
through the Wordpress login screen - no username or password is explicitly required!

One-click login will work for the following domains and user accounts:

*  Google Apps for Business
*  Google Apps for Education
*  Google Apps for Non-profits
*  Personal gmail.com and googlemail.com emails

Plugin setup requires you to have admin access to any Google Apps domain, or a regular Gmail account, to register and
obtain two simple codes from Google.

**Full support and premium features are also available for purchase:**

**Eliminate the need for Google Apps domain admins to  separately manage WordPress user accounts, and get piece 
of mind that only authorized employees have access to the organizations's websites and intranet.**

**See [http://wp-glogin.com/](http://wp-glogin.com/)**

Google Apps Login uses the latest secure OAuth2 authentication recommended by Google. Other 3rd party authentication plugins 
may allow you to use your Google username and password to login, but they do not do this securely:

*  Other plugins: Users' passwords will be handled by your blog's server, potentially unencrypted. If these are compromised,
hackers would be able to gain access to your Google email accounts! This includes all 
[Google Apps](http://www.google.com/enterprise/apps/business/products.html) (Gmail, Drive, Calendar 
etc), and any other services which use your Google account to login.

*  This plugin: Users' passwords are only ever submitted to Google itself, then Google is asked to authenticate the user to
your WordPress site. This means Multi-factor Authentication can still be used (if set up on your Google account). 
Your website only requires permission to authenticate the user and obtain basic profile data - it can never have access to 
your emails and other data.

== Screenshots ==

1. User login screen can work as normal or via Google's authentication system
2. Admin obtains two simple codes from Google to set up - easy instructions to follow 

== Frequently Asked Questions ==

= How can I obtain support for this product? =

Full support is available if you purchase the appropriate license from the author via:
[http://wp-glogin.com/google-apps-login-premium/](http://wp-glogin.com/google-apps-login-premium/)

Please feel free to email [support@wp-glogin.com](mailto:support@wp-glogin.com) with any questions,
as we may be able to help, but you may be required to purchase a support license if the problem
is specific to your installation or requirements.

We may occasionally be able to respond to support queries posted on the 'Support' forum here on the wordpress.org
plugin page, but we recommend sending us an email instead if possible.

= Is login restricted to the Google Apps domain I use to set up the plugin? =

No, once you set up the plugin, any WordPress accounts whose email address corresponds to *any* Google account, 
whether on a different Google Apps domain or even a personal gmail.com account, will be able to use 'Login with 
Google' to easily connect to your WordPress site.

However, our [premium plugin](http://wp-glogin.com/google-apps-login-premium/) has features that greatly simplify 
your WordPress user management if your WordPress users are mostly on the same Google Apps domain(s).

= Does the plugin work with HTTP or HTTPS login pages? =

The plugin will work whether your site is configured for HTTP or HTTPS.

However, you may have configured your site to run so that the login pages 
can be accessed by *either* HTTP *or* HTTPS. In that case, you may run into problems. 
We recommend that you set [FORCE_SSL_ADMIN](http://codex.wordpress.org/Administration_Over_SSL) 
or at least FORCE_SSL_LOGIN to true. This will ensure that all users are consistently using HTTPS 
for login.

You may then need to ensure the Redirect URL and Web Origin in the Google Cloud Console are
set as HTTPS (this will make sense if you follow the installation instructions again).

If for some reason you cannot set FORCE_SSL_ADMIN, then instead you can add two URLs to the Google
Cloud Console for each entry, e.g. Redirect URL = http://wpexample.com/wp-login.php, and
then add another one for https://wpexample.com/wp-login.php. Same idea for Web Origin.

= Does the plugin work on Multisite? =

It is written, tested, and secure for multisite WordPress, both for subdirectories and subdomains, and *must* be activated
network-wide for security reasons.

There are many different possible configurations of multisite WordPress, however, so you must test carefully if you 
have any other plugins or special setup.

In a multisite setup, you will see an extra option in Settings -> Google Apps Login, named 'Use sub-site specific callback 
from Google'. Read details in the configuration instructions (linked from the Settings page). This setting will need to be 
ON if you are using any domain mapping plugin, and extra Redirect URIs will need to be registered in Google Cloud Console.

= Is it secure? =

Yes, and depending on your setup, it can be much more secure than just using
WordPress usernames and passwords.

However, the author does not accept liability or offer any guarantee,
and it is your responsibility to ensure that your site is secure in the way you require.

In particular, other plugins may conflict with each other, and different WordPress versions and configurations
may render your site insecure.

= What are the system requirements? =

*  PHP 5.2.x or higher with Curl and JSON extensions
*  Wordpress 3.3 or above

And you will need a Google account to set up the plugin.

== Installation ==

To set up the plugin, you will need access to a Google Apps domain as an administrator, or just a regular Gmail account.

Easiest way:

1. Go to your WordPress admin control panel's plugin page
1. Search for 'Google Apps Login'
1. Click Install
1. Click Activate on the plugin
1. Go to 'Google Apps Login' under Settings in your Wordpress admin area
1. Follow the instructions on that page to obtain two codes from Google, and also submit two URLs back to Google

If you cannot install from the WordPress plugins directory for any reason, and need to install from ZIP file:

1. Upload `googleappslogin` directory and contents to the `/wp-content/plugins/` directory, or upload the ZIP file directly in
the Plugins section of your Wordpress admin
1. Follow the instructions from step 4 above

== Changelog ==

= 2.0 =

Our platform provides centralized setup and management of Google-related features in your 
WordPress site and plugins.

Other developers can easily extend our Google authentication into their own plugins. 

= 1.4 =

Added clearer instructions, plus new options: automatically redirect users
to Login via Google; plus force users to fully approve access to their
Google account every time they login (allowing them to switch accounts if only
logged into the wrong one, as well as making the process clearer).

= 1.3 =
Much neater support for redirecting users to most appropriate page post-login,
especially on multisite installations; Better notices guiding admins through 
configuration

= 1.2 =
Upgrade to match WordPress 3.8; 
More extensible code

= 1.1 =
Increased security - uses an extra authenticity check; 
Better support for mal-configured Google credentials; 
No longer uses PHP-based sessions - will work on even more WordPress configurations

= 1.0 =
All existing versions are functionally identical - no need to upgrade.

