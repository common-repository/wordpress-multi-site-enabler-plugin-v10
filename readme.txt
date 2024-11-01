=== Plugin Name ===
Contributors: jason.grim
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HKJ6XNRNRTXYA
Plugin URI: http://jgwebdevelopment.com/plugins/wordpress-multi-site-enabler-plugin
Tags: multisite, upgrade, plugin, multi-site, enabler, multi-user, multiuser, mu, ms
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.5

Easily upgrade your WordPress 3.0 blog into Multi-Site with the click of a single button.

== Description ==

This plugin takes care of most of the dirty work of installing Multi-Site on WordPress 3.0. However, there may be some server-side changes you may need to do before upgrading to Multi-Site. Please refer to [Plugin Documentation](http://jgwebdevelopment.com/plugins/wordpress-multi-site-enabler-plugin/wordpress-multi-site-information) or the [Wordpress Codex](http://codex.wordpress.org/Create_A_Network) before using this plugin. This plugin only takes care of steps 3 - 5, which will cover most people.

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new menu item under 'Settings' is added, it is titled 'Enable Multi-Site'. Follow the instructions on it's screen.

== Frequently Asked Questions ==

= Will this work on an IIS Server? =
Not yet. This will be supported in a later release.

= Do I have to edit any files? =
Only under special circumstances.

= What requirements does this plugin have? =
All the requirements of Multi-Site. See more details [here](http://jgwebdevelopment.com/plugins/wordpress-multi-site-enabler-plugin/wordpress-multi-site-information).

= If I use this plugin to upgrade to Multi-Site, can I disable it? =
Not until version 2.0. Until then you will have to do this downgrade manually.

== Changelog ==

= 1.0 =
* Plugin Released.

= 1.1 =
* Fixed bug where the "Network" tab was not appearing under the "Tools" menu.
* Fixed bug where /wp-content/blogs.dir was not being created correctly
* Made small changes to error checking.

= 1.2 =
* Fixed bug where some users recieved the message: "Fatal error: Cannot redeclare network_step1()" 

= 1.3 =
* Reverted bug fix where the "Network" tab was appearing under the "Tools" menu. This is not supposed to happen.
* Added links to documentation regarding Multi-Site and all of its requirements.

= 1.4 =
* Fixed issue where .htaccess was not being created properly.

= 1.5 =
* Fixed issue where .htaccess type was not being created properly.
* Reworked some security checks.
* Started development on the ability to reverse Multi-Site.
* Made it PHP 4 friendly.
* Gave the users the ability to override the subdomain vs subfolder warnings.