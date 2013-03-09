<?php

require_once(TEMPLATEPATH . '/inc/mappress-core.php');
require_once(TEMPLATEPATH . '/inc/mappress-markers.php');

// Metaboxes
require_once(TEMPLATEPATH . '/metaboxes/metaboxes.php');

// API
require_once(TEMPLATEPATH . '/inc/mappress-api.php');

// Plugins implementations and fixes
require_once(TEMPLATEPATH  . '/plugins/mappress-plugins.php');

/*
 * Featured map
 */

function mappress_featured_map($post_type = array('map', 'map-group')) {
	$featured_map_id = get_option('mappress_featured_map');
	if(!$featured_map_id) {
		$latest_map = get_posts(array('post_type' => $post_type, 'posts_per_page' => 1));
		if($latest_map) {
			$latest_map = array_shift($latest_map);
			$featured_map_id = $latest_map->ID;
		} else {
			return false;
		}
	}
	mappress_map($featured_map_id);
}

/*
 * Display maps
 */

function mappress_map($map_id = false) {
	global $map;
	$map_id = $map_id ? $map_id : $map->ID;
	if(!$map_id)
		return;
	$map = get_post($map_id);
	get_template_part('content', get_post_type($map_id));
}

?>