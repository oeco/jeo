<?php

/*
 * JEO
 * UI stuff
 */

function jeo_find_post_on_map_button($zoom = null, $text = false, $post_id = false) {

	if(!jeo_the_map())
		return false;

	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$text = $text ? $text : __('Locate on map', 'jeo');

	$geometry = jeo_get_element_geometry_data($post_id);

	if(!$geometry)
		return false;

	if(!$zoom) {
		$map_data = jeo_get_map_data();
		$zoom = $map_data['max_zoom'];
	}

	$zoom_attr = 'data-zoom="' . $zoom . '"';

	return apply_filters('jeo_find_post_on_map_button', '<a class="find-on-map center-map" ' . $geometry . ' ' . $zoom_attr . ' href="#"><span class="lsf">&#xE056;</span> ' . $text . '</a>');
}

function jeo_get_element_geometry_data($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$coords = jeo_get_marker_coordinates($post_id);
	$lon = $coords[0];
	$lat = $coords[1];

	if(!$coords[0] && !$coords[1])
		return false;

	return 'data-lat="' . $coords[1] . '" data-lon="' . $coords[0] . '"';
}

function jeo_element_max_zoom($post_id = false) {
	global $jeo_map;

	$map_data = jeo_get_map_data();

	$zoom = $map_data['max_zoom'] ? $map_data['max_zoom'] : 18;

	return 'data-zoom="' . $zoom . '"';

}
