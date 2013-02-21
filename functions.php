<?php

include(TEMPLATEPATH . '/inc/mappress-core.php');
include(TEMPLATEPATH . '/metaboxes/metaboxes.php');

/*
 * Featured map
 */

function mappress_featured_map() {
	$featured_map_id = get_option('mappress_featured_map');
	if(!$featured_map_id) {
		$latest_map = get_posts(array('post_type' => array('map', 'map-group'), 'posts_per_page' => 1));
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

function mappress_map($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	if(!$post_id)
		return;
	$post = get_post($post_id);
	setup_postdata($post);
	get_template_part('content', get_post_type($post_id));
	wp_reset_postdata();
}

?>