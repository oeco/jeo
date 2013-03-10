<?php

/*
 * MapPress markers functions and utilities
 */

// marker query args

function mappress_get_marker_query_args($posts_per_page = -1) {
	global $post;
	if(is_singular(array('map', 'map-group'))) {
		$query = array('post_type' => 'post');
		// map exclusive post query
		if(is_singular('map')) {
			$query['meta_query'] = array(
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
		} else  {
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
			$query['meta_query'] = $meta_query;
		}
	} else {
		global $wp_query;
		$query = $wp_query->query_vars;
	}
	$query['posts_per_page'] = $posts_per_page;
	if($posts_per_page == -1 && isset($query['paged']))
		unset($query['paged']);
	else 
		$query['paged'] = (get_query_var('paged')) ? get_query_var('paged') : 1;
	
	$query = apply_filters('mappress_markers_query', $query);
	return $query;
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

	$cache_key = 'markers_';

	if(function_exists('qtrans_getLanguage'))
		$cache_key .= qtrans_getLanguage() . '_';

	$query_id = md5(serialize($query));
	$cache_key .= $query_id;

	$cache_key = apply_filters('mappress_markers_cache_key', $cache_key, $query);

	$data = false;
	//$data = get_transient($cache_key);

	if($data === false) {

		$data = array();

		$posts = apply_filters('mappress_the_markers_posts', get_posts($query), $query);

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
		//set_transient($transient, $data, 60*60*1);
	}

	header('Content Type: application/json');

	/* Browser caching */
	//$expires = 60 * 10; // 10 minutes of browser cache
	//header('Pragma: public');
	//header('Cache-Control: maxage=' . $expires);
	//header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
	/* --------------- */

	echo $data;
	exit;
}