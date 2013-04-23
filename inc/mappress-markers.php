<?php

/*
 * MapPress markers functions and utilities
 */

// Marker query

function mappress_marker_query() {

	global $wp_query;
	$marker_query = $wp_query;

	$query = $marker_query->query_vars;

	if(is_singular(array('map', 'map-group'))) {
		global $mappress_map;
		$marker_query = new WP_Query();
		$marker_query->parse_query();
		$query = $marker_query->query_vars;
	}


	$markers_limit = mappress_get_markers_limit();
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

function mappress_register_marker_script() {
	wp_register_script('mappress.markers', get_template_directory_uri() . '/js/mappress.markers.js', array('mappress', 'underscore'), '0.2');
	wp_localize_script('mappress.markers', 'mappress_markers', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'query' => mappress_marker_query(),
		'markerextent' => mappress_use_marker_extent(),
		'markerextent_defaultzoom' => mappress_marker_extent_default_zoom()
	));
}
add_action('wp_enqueue_scripts', 'mappress_register_marker_script');

function mappress_enqueue_marker_script() {
	if(wp_script_is('mappress.markers', 'registered'))
		wp_enqueue_script('mappress.markers');
}
add_action('wp_footer', 'mappress_enqueue_marker_script');

function mappress_use_marker_extent() {
	$extent = true;
	if(is_front_page() || is_singular(array('map', 'map-group')))
		$extent = false;

	return apply_filters('mappress_use_marker_extent', $extent);
}

function mappress_marker_extent_default_zoom() {
	return apply_filters('mappress_marker_extent_default_zoom', false);
}

function mappress_get_markers_limit() {
	return apply_filters('mappress_markers_limit', 200);
}

function mappress_get_marker_bubble($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	ob_start();
	get_template_part('content', 'marker-bubble');
	$bubble = ob_get_contents();
	ob_end_clean();
	return apply_filters('mappress_marker_bubble', $bubble, $post);
}

function mappress_get_marker_icon() {
	global $post;
	$marker = array(
		'url' => get_template_directory_uri() . '/img/marker.png',
		'width' => 26,
		'height' => 30
	);
	return apply_filters('mappress_marker_icon', $marker, $post);
}

function mappress_get_marker_class() {
	global $post;
	$class = get_post_class();
	return apply_filters('mappress_marker_class', $class, $post);
}

function mappress_get_marker_properties() {
	global $post;
	$properties = array();
	$properties['id'] = 'post-' . $post->ID;
	$properties['postID'] = $post->ID;
	$properties['title'] = get_the_title();
	$properties['date'] = get_the_date(_x('m/d/Y', 'reduced date format', 'mappress'));
	$properties['url'] = get_permalink();
	$properties['bubble'] = mappress_get_marker_bubble();
	$properties['marker'] = mappress_get_marker_icon();
	$properties['class'] = implode(' ', mappress_get_marker_class());
	return apply_filters('mappress_marker_data', $properties, $post);
}

function mappress_get_marker_geometry() {
	global $post;
	$geometry = array();
	$geometry['type'] = 'Point';
	$geometry['coordinates'] = mappress_get_marker_coordinates();
	return apply_filters('mappress_marker_geometry', $geometry, $post);
}

function mappress_get_marker_coordinates($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$lat = get_post_meta($post_id, 'geocode_latitude', true);
	$lon = get_post_meta($post_id, 'geocode_longitude', true);

	if($lat && $lon)
		$coordinates = array($lon, $lat);
	else
		$coordinates = array(0, 0);

	return apply_filters('mappress_marker_coordinates', $coordinates);
}

function mappress_get_marker_conf_coordinates($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;

	$coordinates = mappress_get_marker_coordinates($post_id);
	return array('lat' => $coordinates[1], 'lon' => $coordinates[0]);
}

function mappress_has_marker_location($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	$coordinates = mappress_get_marker_coordinates($post_id);
	if($coordinates[0] !== 0)
		return true;
	return false;
}

/*
 * Markers in GeoJSON
 */

add_action('wp_ajax_nopriv_markers_geojson', 'mappress_get_markers_data');
add_action('wp_ajax_markers_geojson', 'mappress_get_markers_data');
function mappress_get_markers_data($query = false) {
	$query = $query ? $query : $_REQUEST['query'];

	if(!isset($query['singular_map']) || $query['singular_map'] !== true) {
		$query['posts_per_page'] = mappress_get_markers_limit();
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
				$data['features'][$i]['geometry'] = mappress_get_marker_geometry();

				// marker properties
				$data['features'][$i]['properties'] = mappress_get_marker_properties();

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

// clear markers data on post update
function mappress_flush_cache() {
	wp_cache_flush();
}
add_action('save_post', 'mappress_flush_cache');
add_action('delete_post', 'mappress_flush_cache');