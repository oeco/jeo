<?php

/*
 * JEO GeoJSON API
 */

class JEO_API extends JEO_Markers {

	function __construct() {
		add_filter('init', array($this, 'endpoint'));
		add_filter('query_vars', array($this, 'query_var'));
		add_filter('request', array($this, 'request'));
		add_filter('jeo_markers_geojson', array($this, 'jsonp_callback'));
		add_filter('jeo_geojson_content_type', array($this, 'content_type'));
		add_action('jeo_markers_before_print', array($this, 'headers'));
		add_action('template_redirect', array($this, 'template_redirect'));
	}

	function query_var($vars) {
		$vars[] = 'geojson';
		$vars[] = 'download';
		return $vars;
	}

	function template_redirect() {
		if(get_query_var('geojson')) {
			$query = apply_filters('jeo_geojson_api_query', $this->query());
			$this->get_data($query);
			exit;
		}
	}

	function jsonp_callback($geojson) {
		if(get_query_var('geojson') && isset($_GET['callback'])) {
			$jsonp_callback = $_GET['callback'];
			$geojson = "$jsonp_callback($geojson)";
		}
		return $geojson;
	}

	function content_type($content_type) {
		if(get_query_var('geojson') && isset($_GET['callback'])) {
			$content_type = 'Content-type: application/javascript';
		}
		return $content_type;
	}

	function headers() {
		if(get_query_var('geojson') && isset($_GET['download'])) {
			$filename = apply_filters('jeo_geojson_filename', sanitize_title(get_bloginfo('name') . ' ' . wp_title(null, false)));
			header('Content-Disposition: attachment; filename="' . $filename . '.geojson"');
		}
		header('Access-Control-Allow-Origin: *');
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