<?php

/*
 * MapPress Markers
 */

class MapPress_Markers {

	function __construct() {
		$this->setup_scripts();
		$this->setup_ajax();
		$this->setup_cache_flush();
	}

	// geocode service choice
	function geocode_service() {
		// osm or gmaps (gmaps requires api key)
		return apply_filters('mappress_geocode_service', 'osm');
	}

	// gmaps api
	function gmaps_api_key() {
		return apply_filters('mappress_gmaps_api_key', false);
	}

	function setup_scripts() {
		add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'geocode_scripts'));
		add_action('admin_footer', array($this, 'geocode_scripts'));
		add_action('wp_footer', array($this, 'enqueue_scripts'));
	}

	function enqueue_scripts() {
		if(wp_script_is('mappress.markers', 'registered'))
			wp_enqueue_script('mappress.markers');
	}

	function register_scripts() {
		wp_register_script('mappress.markers', get_template_directory_uri() . '/inc/js/markers.js', array('mappress', 'underscore'), '0.2.3');
		wp_localize_script('mappress.markers', 'mappress_markers', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'query' => $this->query(),
			'markerextent' => mappress_use_marker_extent(),
			'markerextent_defaultzoom' => mappress_marker_extent_default_zoom()
		));
	}

	function geocode_scripts() {
		/* geocode scripts */
		$geocode_service = $this->geocode_service();
		$gmaps_key = $this->gmaps_api_key();

		if($geocode_service == 'gmaps' && $gmaps_key) {
			wp_register_script('google-maps-api', 'http://maps.googleapis.com/maps/api/js?key=' . $gmaps_key . '&sensor=true');
			wp_register_script('mappress.geocode.box', get_template_directory_uri() . '/metaboxes/geocode/geocode-gmaps.js', array('jquery', 'google-maps-api'), '0.0.1');
		} else {
			wp_register_script('mappress.geocode.box', get_template_directory_uri() . '/metaboxes/geocode/geocode-osm.js', array('jquery', 'mapbox-js'), '0.0.3.3');
		}
		wp_localize_script('mappress.geocode.box', 'geocode_labels', array(
			'not_found' => __('We couldn\'t find what you are looking for, please try again.', 'mappress'),
			'results_found' => __('results found', 'mappress')
		));
	}

	function query() {
		global $wp_query;
		$marker_query = $wp_query;

		$query = $marker_query->query_vars;

		if(is_singular(array('map', 'map-group'))) {
			$marker_query = new WP_Query();
			$marker_query->parse_query();
			$query = $marker_query->query_vars;
		}


		$markers_limit = $this->get_limit();
		$query['posts_per_page'] = $markers_limit;
		if($markers_limit != -1) {
			$amount = $marker_query->found_posts;
			if($markers_limit > $amount) {
				$markers_limit = $amount;
			} else {
				$page = (get_query_var('paged')) ? get_query_var('paged') : 1;
				$offset = get_query_var('posts_per_page') * ($page - 1);
				if($offset <= ($amount - $markers_limit)) {
					if($offset !== 0) $offset = $offset - 1;
					$query['offset'] = $offset;
				} else {
					$query['offset'] = $amount - $markers_limit;
				}
			}
		}

		// add search
		if(isset($_GET['s']))
			$query['s'] = $_GET['s'];

		$query['paged'] = (get_query_var('paged')) ? get_query_var('paged') : 1;

		return apply_filters('mappress_marker_query', $query);
	}

	function setup_ajax() {
		add_action('wp_ajax_nopriv_markers_geojson', array($this, 'get_data'));
		add_action('wp_ajax_markers_geojson', array($this, 'get_data'));
	}

	function get_data($query = false) {
		$query = $query ? $query : $_REQUEST['query'];

		if(!isset($query['singular_map']) || $query['singular_map'] !== true) {
			$query['posts_per_page'] = $this->get_limit();
			$query['nopaging'] = false;
			$query['paged'] = 0;
		}

		$cache_key = 'mp_';

		if(function_exists('qtrans_getLanguage'))
			$cache_key .= qtrans_getLanguage() . '_';

		$query_id = md5(serialize($query));
		$cache_key .= $query_id;

		$cache_key = apply_filters('mappress_markers_cache_key', $cache_key, $query);

		$data = false;
		$data = wp_cache_get($cache_key, 'mappress_markers_query');

		if($data === false) {
			$data = array();

			$posts = apply_filters('mappress_the_markers', get_posts($query), $query);

			$data['query_id'] = $cache_key;

			if($posts) {
				global $post;
				$data['type'] = 'FeatureCollection';
				$data['features'] = array();
				$i = 0;
				foreach($posts as $post) {

					setup_postdata($post);

					$data['features'][$i]['type'] = 'Feature';

					// marker geometry
					$data['features'][$i]['geometry'] = $this->get_geometry();

					// marker properties
					$data['features'][$i]['properties'] = $this->get_properties();

					$i++;

					wp_reset_postdata();
				}
			}
			$data = apply_filters('mappress_markers_data', $data);
			$data = json_encode($data);
			wp_cache_set($cache_key, $data, 'mappress_markers_query');
		}

		header('Content Type: application/json');

		/* Browser caching */
		$expires = 60 * 10; // 10 minutes of browser cache
		header('Pragma: public');
		header('Cache-Control: maxage=' . $expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
		/* --------------- */

		echo $data;
		exit;
	}

	function setup_cache_flush() {
		add_action('save_post', array($this, 'cache_flush'));
		add_action('delete_post', array($this, 'cache_flush'));
	}

	function cache_flush() {
		wp_cache_flush();
	}

	function use_extent() {
		$extent = true;
		if(is_front_page() || is_singular(array('map', 'map-group')))
			$extent = false;

		return apply_filters('mappress_use_marker_extent', $extent);
	}

	function extent_default_zoom() {
		return apply_filters('mappress_marker_extent_default_zoom', false);
	}

	function get_limit() {
		return apply_filters('mappress_markers_limit', 200);
	}

	function get_bubble() {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		ob_start();
		get_template_part('content', 'marker-bubble');
		$bubble = ob_get_contents();
		ob_end_clean();
		return apply_filters('mappress_marker_bubble', $bubble, $post);
	}

	function get_icon() {
		global $post;
		$marker = array(
			'url' => get_template_directory_uri() . '/img/marker.png',
			'width' => 26,
			'height' => 30
		);
		return apply_filters('mappress_marker_icon', $marker, $post);
	}

	function get_class() {
		global $post;
		$class = get_post_class();
		return apply_filters('mappress_marker_class', $class, $post);
	}

	function get_properties() {
		global $post;
		$properties = array();
		$properties['id'] = 'post-' . $post->ID;
		$properties['postID'] = $post->ID;
		$properties['title'] = get_the_title();
		$properties['date'] = get_the_date(_x('m/d/Y', 'reduced date format', 'mappress'));
		$properties['url'] = get_permalink();
		$properties['bubble'] = $this->get_bubble();
		$properties['marker'] = $this->get_icon();
		$properties['class'] = implode(' ', $this->get_class());
		return apply_filters('mappress_marker_data', $properties, $post);
	}

	function get_geometry() {
		global $post;
		$geometry = array();
		$geometry['type'] = 'Point';
		$geometry['coordinates'] = $this->get_coordinates();
		return apply_filters('mappress_marker_geometry', $geometry, $post);
	}

	function get_coordinates($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$lat = get_post_meta($post_id, 'geocode_latitude', true);
		$lon = get_post_meta($post_id, 'geocode_longitude', true);

		if($lat && $lon)
			$coordinates = array($lon, $lat);
		else
			$coordinates = array(0, 0);

		return apply_filters('mappress_marker_coordinates', $coordinates, $post);
	}

	function get_conf_coordinates($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$coordinates = $this->get_coordinates($post_id);
		return array('lat' => $coordinates[1], 'lon' => $coordinates[0]);
	}

	function has_location($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		$coordinates = $this->get_coordinates($post_id);
		if($coordinates[0] !== 0)
			return true;
		return false;
	}

}

$mappress_markers = new MapPress_Markers();

function mappress_use_marker_extent() {
	global $mappress_markers;
	return $mappress_markers->use_extent();
}

function mappress_marker_extent_default_zoom() {
	global $mappress_markers;
	return $mappress_markers->extent_default_zoom();
}

function mappress_get_markers_limit() {
	global $mappress_markers;
	return $mappress_markers->get_limit();
}

function mappress_get_marker_bubble($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->get_bubble();
}

function mappress_get_marker_icon() {
	global $mappress_markers;
	return $mappress_markers->get_icon();
}

function mappress_get_marker_class() {
	global $mappress_markers;
	return $mappress_markers->get_class();
}

function mappress_get_marker_properties() {
	global $mappress_markers;
	return $mappress_markers->get_properties();
}

function mappress_get_marker_geometry() {
	global $mappress_markers;
	return $mappress_markers->get_geometry();
}

function mappress_get_marker_coordinates($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->get_coordinates($post_id);
}

function mappress_get_marker_conf_coordinates($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->get_conf_coordinates($post_id);
}

function mappress_has_marker_location($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->has_location($post_id);
}