=== Content Pay by Zong ===
Contributors: Zong
Tags: sms, premium, monetize, blog, payment, content
Requires at least: 2.5
Tested up to: 2.5.1
Stable tag: 0.1.8

Content Pay by Zong allows you to monetize your blog content using Premium text messaging.

== Description ==

Content Pay by Zong allows you to monetize your blog content using Premium text messaging.
Your reader sends a text message using his mobile phone and he receives a code that gives him access to your content 
for a determined duration. 

== Installation ==

1. 	Unzip the archive to a directory of your choice
2. 	Upload the contents of the directory to wp-content/plugins/
3. 	Activate the plugin
4. 	Create an account on [ZDN](http://developer.zong.com "Zong Developer Network") and activate it. (It's free!)
5. 	Create a service with the first market. (Type should be "One Off", "Session Based" isn't needed). 
   	The URL to use for the "One-Off Transaction handler" is shown to you at the bottom of the Settings page for the Content Pay by Zong plugin.
	It should be something like: _yourblogurl_/wp-content/plugins/content-pay-by-zong/content-pay-by-zong-handler.php?secretKey=MySecretKey
	Where "MySecretKey" is the key you entered in the setup page	
6. 	Activate your service
7. 	Add additional market(s) as required
8. 	In the "Services" page of the Zong Developer Network "Dashboard", check your service's ID and also copy your API Key (center-top, there's a link to display it)
9.  Set these up on the plugin settings page in WP
9. 	Configure the "Content Pay by Zong Options"
10.	Write a post and monitize it, there's a new option at the bottom of "Advanced Options" which let you monetize your post.

== Upgrading ==
Please see the upgrade.txt document


== Frequently Asked Questions ==

= Where can I have some support =

This plugin is released by the Zong Developer Network as an example of how you can use Zong to monetize content.
Its released as is without support and you are free to change the code to suit your needs.
We have opened a forum here: http://developer.zong.com/forum/viewforum.php?f=7

== Screenshots ==

1. The clickable image that appears for paid content
2. Payment instructions screen

== !! Pre requisites !! ==

- Since simpleXML is used in this plugin, it requires PHP 5.x or PHP 4.x with simpleXML support
- In order to use PHP's cURL functions you need to install the libcurl package with SSL support since the API calls are made over HTTPS

To avoid as many issues as possible we have added some configuration tests when you activate the plugin to ensure that your installation meets the system requirements.
