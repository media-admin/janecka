<?php /*
**************************************************************************
Plugin Name: Hide YouTube Related Videos
Plugin URI: http://wordpress.org/extend/plugins/hide-youtube-related-videos/
Description: This is a simple plugin to keep the YouTube oEmbed from showing related videos.
Author: SparkWeb Interactive, Inc.
Version: 1.4.2
Author URI: http://www.soapboxdave.com/

**************************************************************************

Copyright (C) 2015 SparkWeb Interactive, Inc.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

**************************************************************************/

//The Filter That Does the Work
add_filter('oembed_result', 'hide_youtube_related_videos', 10, 3);
function hide_youtube_related_videos($data, $url, $args = array()) {

	//Autoplay
	$autoplay = strpos($url, "autoplay=1") !== false ? "&autoplay=1" : "";

	//Setup the string to inject into the url
	$str_to_add = apply_filters("hyrv_extra_querystring_parameters", "wmode=transparent&amp;") . 'rel=0';

	//Regular oembed
	if (strpos($data, "feature=oembed") !== false) {
		$data = str_replace('feature=oembed', $str_to_add . $autoplay . '&amp;feature=oembed', $data);

	//Playlist
	} elseif (strpos($data, "list=") !== false) {
		$data = str_replace('list=', $str_to_add . $autoplay . '&amp;list=', $data);
	}

	//All Set
	return $data;
}

//Disable the Jetpack
add_filter('jetpack_shortcodes_to_include', 'hyrv_remove_jetpack_shortcode_function');
function hyrv_remove_jetpack_shortcode_function( $shortcodes ) {
	$jetpack_shortcodes_dir = WP_CONTENT_DIR . '/plugins/jetpack/modules/shortcodes/';
	$shortcodes_to_unload = array('youtube.php');
	foreach ($shortcodes_to_unload as $shortcode) {
		if ($key = array_search($jetpack_shortcodes_dir . $shortcode, $shortcodes)) {
			unset($shortcodes[$key]);
		}
	}
	return $shortcodes;
}

//On Activation, all oembed caches are cleared
register_activation_hook(__FILE__, 'hyrv_clear_cache');
function hyrv_clear_cache() {
	global $wpdb;
	$post_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'");
	if ($post_ids) {
		$postmetaids = $wpdb->get_col("SELECT meta_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'");
		$in = implode(',', array_fill(1, count($postmetaids), '%d'));
		do_action('delete_postmeta', $postmetaids);
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_id IN ($in)", $postmetaids));
		do_action('deleted_postmeta', $postmetaids);
		foreach ($post_ids as $post_id) {
			wp_cache_delete($post_id, 'post_meta');
		}
		return true;
	}
}


//Display Clear Oembed Cache Link on Plugin Screen
add_filter('plugin_action_links', 'hyrv_plugin_action_links', 10, 2);
function hyrv_plugin_action_links($links, $file) {
	static $this_plugin;
	if (!$this_plugin) {
		$this_plugin = "hide-youtube-related-videos/hide-youtube-related-videos.php";
	}
	if ($file == $this_plugin) {
		$custom_link = '<a href="' . admin_url('plugins.php?hyrv_clear_cache=1') . '">Clear Oembed Cache</a>';
		array_unshift($links, $custom_link);
	}
	return $links;
}

//Clear Cache On Admin Page Load
add_action('admin_notices', 'hyrv_execute_cache_reload');
function hyrv_execute_cache_reload() {
	if (isset($_GET['hyrv_clear_cache'])) {
		hyrv_clear_cache();
		echo '<div class="updated"><p><strong>Success!</strong> Your oembed cache has been cleared.</p></div>';
	}
}
