=== Plugin Name ===
Contributors: danlester
Tags: login, google, authentication, oauth2, oauth, admin, googleapps
Requires at least: 3.3
Tested up to: 3.7.1
Stable tag: trunk
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

== Requirements ==

System requirements:

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

= 1.0 =
All existing versions are functionally identical - no need to upgrade.

