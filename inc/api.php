<?php

/*
 * JEO GeoJSON API
 */

class JEO_API extends JEO_Markers {

	function __construct() {

		add_filter('jeo_settings_tabs', array($this, 'admin_settings_tab'));
		add_filter('jeo_settings_form_sections', array($this, 'admin_settings_form_section'), 10, 2);


		if($this->is_enabled()) {
			add_rewrite_endpoint('geojson', EP_ALL);
			add_filter('query_vars', array($this, 'query_var'));
			add_filter('jeo_markers_geojson', array($this, 'jsonp_callback'));
			add_filter('jeo_markers_data', array($this, 'filter_markers'), 10, 2);
			add_filter('jeo_geojson_content_type', array($this, 'content_type'));
			add_action('jeo_markers_before_print', array($this, 'headers'));
			add_action('pre_get_posts', array($this, 'pre_get_posts'));
			add_action('template_redirect', array($this, 'template_redirect'));
		}
	}

	/*
	 * Admin settings
	 */

	function is_enabled() {
		$options = jeo_get_options();
		return ($options && isset($options['api']) && $options['api']['enable']);
	}

	function admin_settings_tab($tabs = array()) {
		$tabs['api'] = __('GeoJSON API', 'jeo');
		return $tabs;
	}

	function admin_settings_form_section($sections = array(), $page_slug) {

		$section = array(
			'pageslug' => $page_slug,
			'tabslug' => 'api',
			'id' => 'api',
			'title' => __('GeoJSON API Settings', 'jeo'),
			'description' => '',
			'fields' => array(
				array(
					'id' => 'enable',
					'title' => __('Enable API', 'jeo'),
					'description' => __('Select if you\'d like to enable the GeoJSON API', 'jeo'),
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Enable', 'jeo')
				)
			)
		);

		if($this->is_enabled()) {
			$section['description'] = sprintf(__('Your API is <strong>enabled</strong>! <a href="%s" target="_blank">Click here to learn more</a>.', 'jeo'), 'http://dev.cardume.art.br/jeo/features/geojson-api/') . '<br/><br/>' . $section['description'];
		}

		$sections[] = $section;
		return $sections;
	}

	function get_options() {
		$options = jeo_get_options();
		if($options && isset($options['api'])) {
			return $options['api'];
		}
	}

	function query_var($vars) {
		$vars[] = 'geojson';
		$vars[] = 'download';
		return $vars;
	}

	function filter_markers($data, $query) {
		if(isset($query->query['geojson'])) {
			$features_with_geometry = array();
			foreach($data['features'] as $feature) {
				if(isset($feature['geometry']))
					$features_with_geometry[] = $feature;
			}
			$data['features'] = $features_with_geometry;
		}
		return $data;
	}

	function pre_get_posts($query) {
		if(isset($query->query['geojson'])) {
			$query->set('offset', null);
			$query->set('nopaging', null);
			$query->set('paged', (get_query_var('paged')) ? get_query_var('paged') : 1);
		}
	}

	function template_redirect() {
		global $wp_query;
		if(isset($wp_query->query['geojson'])) {
			$query = $this->query();
			$this->get_data(apply_filters('jeo_geojson_api_query', $query));
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
		if(isset($wp_query->query['geojson'])) {
			header('X-Total-Count: ' . $wp_query->found_posts);
			header('Access-Control-Allow-Origin: *');
			if(isset($_GET['download'])) {
				$filename = apply_filters('jeo_geojson_filename', sanitize_title(get_bloginfo('name') . ' ' . wp_title(null, false)));
				header('Content-Disposition: attachment; filename="' . $filename . '.geojson"');
			}
		}
	}

	function get_api_url($query_args = array()) {
		global $wp_query;
		$query_args = (empty($query_args)) ? $wp_query->query : $query_args;
		$query_args = $query_args + array('geojson' => 1);
		return add_query_arg($query_args, home_url('/'));
	}

	function get_download_url($query_args = array()) {
		return add_query_arg(array('download' => 1), $this->get_api_url($query_args));
	}
}

$GLOBALS['jeo_api'] = new JEO_API;

function jeo_get_api_url($query_args = array()) {
	return $GLOBALS['jeo_api']->get_api_url($query_args);
}

function jeo_get_api_download_url($query_args = array()) {
	return $GLOBALS['jeo_api']->get_download_url($query_args);
}
