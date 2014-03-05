=== Plugin Name ===
Contributors: cyberws
Donate link: http://www.cyberws.com/cleverwise-plugins/
Tags: redirect, redirects, redirection, url, urls, link, links
Requires at least: 3.0.1
Tested up to: 3.8.1
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to easily handle redirects to an unlimited number of offsite and onsite links. Also changing a destination link is fast and easy.

== Description ==

<p>Once this plugin has been installed you'll easily be able to manage all your site redirects. In fact the plugin has no limits to the number of redirects you may manage. It works from dozens to millions <i>(although you'll need more than budget hosting for millions).  All this is done through your familiar Wordpress control panel.</i></p>

<p>You add redirect links, assign them unique names (so visitors can't start guessing all your links), have the ability to enter notes (optional), and may even turn redirects on and off.  This allows you to disable links that you don't want redirected out but aren't quite ready to delete.</p>

<p>In addition you are able to easily search through your redirect records to quickly locate matches.  Plus you have total control over where visitors are sent if plugin can't find a redirect match.  All of this is easily changable at any time through Wordpress.</p>

<p>Language Support: Should work for all languages that use the A-Z alphabet.  Plugin only displays text (link names) entered by you.  The only limitation is possible removal of unknown characters outside standard A-Z.</p>

<p>Shameless Promotion: See other <a href="http://wordpress.org/plugins/search.php?q=cleverwise">Cleverwise Wordpress Directory Plugins</a></p>

== Installation ==

<ol>
<li>Upload the <strong>cleverwise-redirect-it</strong> directory to your plugins.</li>
<li>In Wordpress management panel activate "<strong>Cleverwise Redirect It</strong>" plugin.</li>
<li>A new menu option "<strong>Redirect It</strong>" will appear on your main menu (under Comments).</li>
<li>Once you have loaded the main panel for the plugin click on the "<strong>Help Guide</strong>" link which explains in detail how to use the plugin.</li>
</ol>

== Frequently Asked Questions ==

= I don't understand the redirectit.php file? =

Additional questions address this point but for optimization this plugin uses a redirect file and the file must be writeable by Wordpress (really your web server).  You have total control where to put this file.  By default (meaning if you don't change it) the file will be located in your root Wordpress directory.  However you can put it anywhere on your site that visitors can access with their web browsers.  So you can create an unique directory if you desire.  Yes the file name is customizable too.  For more advanced Wordpress users you can make just the redirect file writeable.

= Is this plugin database optimized? =

Yes! If you have a really busy site no problem.  When a visitor clicks on a link the redirect is handled by a file and does not even touch the database.

= Wait! This plugin is storing everything in a file? =

Well yes and no.  All data is stored in the database but redirection doesn't occur straight from the database.  Once you have the redirects the way you want the plugin will generate a file and that's what redirects visitors.  If you use a PHP script caching system like APC or Zend OPcache the speed will be lightning fast even with large files.  Caching is the name of the game with speed.  It usually refers to pulling data out of the database (or dynamic source) and storing it statically.  Hence this method.

= Won't the redirect file be huge? =

Very doubtful.  It is hard to say for sure the exact length due to various link sizes.  However lets assume for a moment a link size of 150 characters (which is pretty long - see next question), and a link title of 25 characters.  One million redirects would be around 167MB.  At this point you would need a dedicated server or VPS with a lot of memory like gigs, so 167MB is nothing.  For more realistic numbers 5,000 links is around 855KB, which for caching is small, and 100 links is around 17KB.  To give you a comparison Wordpress version 3.8.1 is around 17MB or 17,000KB.

= What does a 150 character link look like? =

`http://www.somesite.tld/parent_category/sub_category/sub_sub_category/sub_sub_sub_category/now_a_really_long_page_name_to_equal_one_hundred_and_fifty/`

== Screenshots ==

1. screenshot-1.jpg

== Changelog ==

= 1.3 =
UI changes<br>
Fixed: Typos

= 1.2 =
Permissions check bug fix<br>
Added additional notes to redirect file permissions (Settings screen)<br>
Added footer links

= 1.1 =
Altered framework code to fit Wordpress Plugin Directory terms

= 1.0 =
Initial release of plugin

== Upgrade Notice ==

= 1.2 =
Bug fix, additional permission notes, and footer links
