<?php

/*
 * MapPress markers functions and utilities
 */

$marker_the_query = new Marker_Query();
$marker_query = $marker_the_query;

class Marker_Query extends WP_Query { }

// Marker script

function mappress_setup_marker_script() {
	global $marker_query;
	wp_enqueue_script('mappress.markers', get_template_directory_uri() . '/js/mappress.markers.js', array('mappress', 'underscore'), '0.0.6');
	wp_localize_script('mappress.markers', 'mappress_markers', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'query' => $marker_query->query
	));
}
add_action('wp_enqueue_scripts', 'mappress_setup_marker_script');

// Set global $marker_query according to main WP_Query
function mappress_setup_marker_query($query) {
	if($query->is_main_query()) {
		global $marker_query;
		remove_action('pre_get_posts', 'mappress_setup_marker_query');
		$marker_query = new Marker_Query(mappress_get_marker_query_vars());
		do_action('mappress_pre_get_markers', $marker_query);
		add_action('pre_get_posts', 'mappress_setup_marker_query');
	}
}
add_action('pre_get_posts', 'mappress_setup_marker_query');

// Setup marker query vars
function mappress_get_marker_query_vars() {
	global $wp_query;
	return apply_filters('mappress_markers_query', $wp_query->query);
}

// If accessing singular map, setup blank marker query
function mappress_map_marker_query($marker_query) {
	if(mappress_is_map()) {
		global $post;
		$marker_query = new Marker_Query();
		$marker_query->set('offset', 0);
		$marker_query->set('singular_map', true);
		// map exclusive post query
		if(is_singular('map')) {
			$meta_query = array(
				'relation' => 'OR',
				array(
					'key' => 'maps',
					'value' => $post->ID,
					'compare' => 'LIKE'
				),
				array(
					'key' => 'has_maps',
					'value' => '',
					'compare' => 'NOT EXISTS'
				)
			);
		} elseif(is_singular('map-group')) {
			$groupdata = get_post_meta($post->ID, 'mapgroup_data', true);
			$meta_query = array('relation' => 'OR');
			$i = 1;
			foreach($groupdata['maps'] as $map) {
				$meta_query[$i] = array(
					'key' => 'maps',
					'value' => intval($map['id']),
					'compare' => 'LIKE'
				);
				$i++;
			}
			$meta_query[$i] = array(
				'key' => 'has_maps',
				'value' => '',
				'compare' => 'NOT EXISTS'
			);
		}
		$marker_query->set('meta_query', $meta_query);
	}
}
add_action('mappress_pre_get_markers', 'mappress_map_marker_query');

function mappress_set_marker_query_offset($marker_query) {
	global $wp_query;
	$marker_query->set('offset', mappress_get_marker_query_offset($wp_query));
}
add_action('mappress_pre_get_markers', 'mappress_set_marker_query_offset', 1);

// Sync marker query offset to another WP_Query (eg. main query) according to limit
function mappress_get_marker_query_offset($wp_query) {
	$query = $wp_query->query;
	$markers_limit = mappress_get_markers_limit();
	if($markers_limit != -1) {
		$amount = $wp_query->found_posts;
		if($markers_limit > $amount) {
			$markers_limit = $amount;
		} else {
			$page = (get_query_var('paged')) ? get_query_var('paged') : 1;
			$offset = get_query_var('posts_per_page') * ($page - 1);
			if($offset <= ($amount - $markers_limit)) {
				if($offset !== 0) $offset = $offset - 1;
			} else {
				$offset = $amount - $markers_limit;
			}
		}
	}
	return $offset;
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