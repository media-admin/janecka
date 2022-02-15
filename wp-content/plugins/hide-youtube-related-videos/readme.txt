=== Hide YouTube Related Videos ===
Contributors: sparkweb
Tags: youtube, video, oembed, related
Requires at least: 2.9
Tested up to: 4.7
Stable tag: 1.4.2
This is a simple plugin to keep the YouTube oEmbed from showing related videos.

== Description ==

WordPress' built-in oEmbed feature is fantastic. I've heard several complaints, though, about the related videos that show up after the video is done playing so I put together this very simple plugin that keeps the YouTube oEmbed code from showing related videos.

This plugin also adds wmode=transparent so that the flash object doesn't overlap a modal window.

On activation, the plugin clears the oEmbed cache so that the videos can be successfully re-cached with the new setting. If you are upgrading the plugin, you may need to manually deactivate, then reactivate it to clear the cache.

== Installation ==

Copy the folder to your WordPress
'*/wp-content/plugins/*' folder.

1. Activate the plugin in your WordPress admin
1. That's it. There's nothing else to do. Really!

If you want to pass in some other parameters to the embedded url, you can do so with the `hyrv_extra_querystring_parameters` filter. Just make sure you end with an ampersand.

Add Something:

`add_filter("hyrv_extra_querystring_parameters", "my_hyrv_extra_querystring_parameters");
function my_hyrv_extra_querystring_parameters($str) {
	return "wmode=transparent&amp;MY_VAR_NAME=MY_VALUE&amp;";
}`


Remove the wmode=transparent:

`add_filter("hyrv_extra_querystring_parameters", "my_hyrv_extra_querystring_parameters");
function my_hyrv_extra_querystring_parameters($str) {
	return "";
}`

Be sure to leave an &amp; at the end of your string. After installing your filter, be sure to deactivate and reactivate the plugin.


== Changelog ==

= 1.4.2 (Jan 15, 2015) =
* Allow `&autoplay=1` on the original url to be passed through to the oembed

= 1.4.1 (Sep 28, 2015) =
* Ease matching restrictions to allow more querystring elements to be passed through on original url

= 1.4 (Sep 24, 2015) =
* Added support for playlists, added clear cache button to plugin page

= 1.3 (Feb 10, 2014) =
* Disabled Jetpack YouTube shortcode embed as it kills this feature

= 1.2 (Feb 21, 2013) =
* Added wmode=transparent to the url structure

= 1.1 (Feb 11, 2013) =
* Updated to match YouTube's new URL structure

= 1.0 (Dec 23, 2011) =
* Initial Release


== Upgrade Notice ==

= 1.4.2 =
Allow `&autoplay=1` on the original url to be passed through to the oembed
