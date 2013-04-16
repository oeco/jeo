<?php

/*
 * Mappress
 * UI stuff
 */

function mappress_find_post_on_map_button($zoom = null, $text, $post_id) {
	global $post, $mappress_map;
	$post_id = $post_id ? $post_id : $post->ID;
	$text = $text ? $text : __('Locate on map', 'mappress');

	$coords = mappress_get_marker_coordinates($post_id);
	$lon = $coords[0];
	$lat = $coords[1];

	if(!$coords[0] && !$coords[1])
		return false;

	if(!$zoom) {
		$map_data = mappress_get_map_data();
		$zoom = $map_data['max_zoom'];
	}
	
	$zoom_attr = 'data-zoom="' . $zoom . '"';

	return apply_filters('mappress_find_post_on_map_button', '<a class="find-on-map center-map" data-lat="'. $lat . '" data-lon="' . $lon . '" ' . $zoom_attr . ' href="#">' . $text . '</a>');
}