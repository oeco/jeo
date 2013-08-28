<?php

/*
 * JEO GeoJSON API
 */

class JEO_API {

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
			global $jeo_markers;
			$marker_query = $jeo_markers->query();
			$query = apply_filters('jeo_geojson_api_query', $marker_query);
			$jeo_markers->get_data($marker_query);
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

new JEO_API;