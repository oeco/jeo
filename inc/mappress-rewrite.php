<?php

/*
 * mappress rewrite rules
 */


// geojson api
function mappress_geojson_query_var($vars) {
	$vars[] = 'geojson';
	return $vars;
}
add_filter('query_vars', 'mappress_geojson_query_var');

function mappress_geojson_api() {
	if(get_query_var('geojson')) {
		$query = mappress_get_marker_query_args();
		$query = apply_filters('mappress_geojson_api_query', $query);
		mappress_get_markers_data($query);
		exit;
	}
}
add_action('template_redirect', 'mappress_geojson_api');

function mappress_geojson_request($vars) {
	if(isset($vars['geojson'])) $vars['geojson'] = true;
	return $vars;
}
add_filter('request', 'mappress_geojson_request');

function mappress_geojson_endpoint() {
	add_rewrite_endpoint('geojson', EP_ALL);
}
add_action('init', 'mappress_geojson_endpoint');