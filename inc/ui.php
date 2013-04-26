<?php

/*
 * Mappress
 * UI stuff
 */

function mappress_find_post_on_map_button($zoom = null, $text = false, $post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$text = $text ? $text : __('Locate on map', 'mappress');

	$geometry = mappress_element_geometry_data($post_id);

	if(!$geometry)
		return false;

	if(!$zoom) {
		$map_data = mappress_get_map_data();
		$zoom = $map_data['max_zoom'];
	}
	
	$zoom_attr = 'data-zoom="' . $zoom . '"';

	return apply_filters('mappress_find_post_on_map_button', '<a class="find-on-map center-map" ' . $geometry . ' ' . $zoom_attr . ' href="#">' . $text . '</a>');
}

function mappress_element_geometry_data($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$coords = mappress_get_marker_coordinates($post_id);
	$lon = $coords[0];
	$lat = $coords[1];

	if(!$coords[0] && !$coords[1])
		return false;

	return 'data-lat="' . $coords[1] . '"" data-lon="' . $coords[0] . '"';
}

function mappress_element_max_zoom($post_id = false) {
	global $mappress_map;

	$map_data = mappress_get_map_data();

	$zoom = $map_data['max_zoom'] ? $map_data['max_zoom'] : 18;

	return 'data-zoom="' . $zoom . '"';

}