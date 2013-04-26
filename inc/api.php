<?php

/*
 * MapPress GeoJSON API
 */

class MapPress_API {

	function __construct() {
		add_filter('init', array($this, 'endpoint'));
		add_filter('query_vars', array($this, 'query_var'));
		add_filter('request', array($this, 'request'));
		add_action('template_redirect', array($this, 'template_redirect'));
	}

	function query_var($vars) {
		$vars[] = 'geojson';
		return $vars;
	}

	function template_redirect() {
		if(get_query_var('geojson')) {
			global $mappress_markers;
			$marker_query = $mappress_markers->query();
			$query = apply_filters('mappress_geojson_api_query', $marker_query);
			$mappress_markers->get_data($marker_query);
			exit;
		}
	}

	function request($vars) {
		if(isset($vars['geojson'])) $vars['geojson'] = true;
		return $vars;
	}

	function endpoint() {
		add_rewrite_endpoint('geojson', EP_ALL);
	}
}

new MapPress_API;