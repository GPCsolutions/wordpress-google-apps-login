=== Plugin Name ===
Contributors: danlester
Tags: login, google, authentication, oauth2, oauth, admin, googleapps
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 1.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easy login for your Wordpress users by using their Google accounts (uses secure OAuth2, including Multi-factor auth if enabled).

== Description ==

Google Apps Login allows existing Wordpress user accounts to login to the blog by using Google to securely authenticate their
account. This means that if they are already logged into Gmail for example, they can simply click their way
through the Wordpress login screen - no username or password is explicitly required!

Login will work for any Google Apps domains plus personal gmail.com emails.

Plugin setup requires you to have admin access to any Google Apps domain, or a regular Gmail account, to register and
obtain two simple codes from Google.

**Support and premium features are also available for purchase: eliminate the need for Google Apps domain admins to separately 
manage WordPress user accounts, and get piece of mind that only authorized employees have access to the company's websites and intranet.**

**See [http://wp-glogin.com/](http://wp-glogin.com/)**

Google Apps Login uses the latest secure OAuth2 authentication recommended by Google. Other 3rd party authentication plugins 
may allow you to use your Google username and password to login, but they do not do this securely:

*  Other plugins: Users' passwords will be handled by your blog's server, potentially unencrypted. If these are compromised,
hackers would be able to gain access to your Google email accounts! This includes Gmail, Drive, and any other services which
use your Google account to login.

*  This plugin: Users' passwords are only ever submitted to Google itself, then Google is asked to authenticate the user to
your blog. This means Multi-factor Authentication can still be used (if set up on your Google account). Your blog only ever
has permission to authenticate the user and obtain basic profile data - it can never have access to your emails and other data.

== Screenshots ==

1. User login screen can work as normal or via Google's authentication system
2. Admin obtains two simple codes from Google to set up - easy instructions to follow 

== Frequently Asked Questions ==

= Does the plugin work with HTTP or HTTPS login pages? =

The plugin will work whether your site is configured for HTTP or HTTPS.

However, you may have configured your site to run so that the login pages 
can be accessed by *either* HTTP *or* HTTPS. In that case, you may run into problems. 
We recommend that you set [FORCE_SSL_ADMIN](http://codex.wordpress.org/Administration_Over_SSL) 
or at least FORCE_SSL_LOGIN to true. This will ensure that all users are consistently using HTTPS 
for login.

You may then need to ensure the Redirect URL and Web Origin in the Google Cloud Console are
set as HTTPS (this will make sense if you follow the installation instructions again).

If for some reason you cannot set FORCE_SSL_ADMIN, then you can add two URLs to the Google
Cloud Console for each entry, e.g. Redirect URL = http://wpexample.com/wp-login.php, and
then add another one for https://wpexample.com/wp-login.php. Same idea for Web Origin.

= Does the plugin work on Multisite? =

It is written, tested, and secure for multisite in subdirectories (not subdomains), and *must* be activated
network-wide for security reasons.

If you do require it used for subdomains, please contact the plugin author who may
be able to help for your specific installation.

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

== Upgrade Notice ==

= 1.2 =
Upgrade to match WordPress 3.8
More extensible code

= 1.1 =
Upgrade recommended
Increased security - uses an extra authenticity check
Better support for mal-configured Google credentials
No longer uses PHP-based sessions - will work on even more WordPress configurations

= 1.0 =
All existing versions are functionally identical - no need to upgrade.

