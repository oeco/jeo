<?php

/*
 * JEO GeoJSON API
 */

class JEO_API extends JEO_Markers {

	function __construct() {
		add_rewrite_endpoint('geojson', EP_ALL);
		add_filter('query_vars', array($this, 'query_var'));
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
		global $wp_query;
		if(isset($wp_query->query['geojson'])) {
			define('DONOTCACHEPAGE', true);
			$query = apply_filters('jeo_geojson_api_query', $this->query());
			$this->get_data($query);
			exit;
		}
	}

	function jsonp_callback($geojson) {
		global $wp_query;
		if(isset($wp_query->query['geojson']) && isset($_GET['callback'])) {
			$jsonp_callback = preg_replace('/[^a-zA-Z0-9$_]/s', '', $_GET['callback']);
			$geojson = "$jsonp_callback($geojson)";
		}
		return $geojson;
	}

	function content_type($content_type) {
		global $wp_query;
		if(isset($wp_query->query['geojson']) && isset($_GET['callback'])) {
			$content_type = 'application/javascript';
		}
		return $content_type;
	}

	function headers() {
		global $wp_query;
		if(isset($wp_query->query['geojson']) && isset($_GET['download'])) {
			$filename = apply_filters('jeo_geojson_filename', sanitize_title(get_bloginfo('name') . ' ' . wp_title(null, false)));
			header('Content-Disposition: attachment; filename="' . $filename . '.geojson"');
		}
		header('Access-Control-Allow-Origin: *');
	}

	function get_api_url($query_args = array()) {
		global $wp_query;
		$query_args = (empty($query_args)) ? $wp_query->query : $query_args;
		$query_args = $query_args + array('geojson' => 1);
		return add_query_arg($query_args, home_url('/'));
	}
}

$GLOBALS['jeo_api'] = new JEO_API;

function jeo_get_api_url() {
	return $GLOBALS['jeo_api']->get_api_url();
}