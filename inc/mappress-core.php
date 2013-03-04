<?php

function mappress_setup() {
	// register map and map group post types
	include(TEMPLATEPATH . '/inc/mappress-post-types.php');
}
add_action('after_setup_theme', 'mappress_setup');

/*
 * Register/enqueue scripts & styles
 */
function mappress_scripts() {
	wp_register_script('underscore', get_template_directory_uri() . '/lib/underscore-min.js', array(), '1.4.3');
	wp_register_script('mapbox-js', get_template_directory_uri() . '/lib/mapbox.js', array(), '0.6.7');
	wp_enqueue_style('mapbox', get_template_directory_uri() . '/lib/mapbox.css', array(), '0.6.7');

	wp_register_script('d3js', get_template_directory_uri() . '/lib/d3.v2.min.js', array('jquery'), '3.0.5');

	wp_enqueue_script('mappress', get_template_directory_uri() . '/js/mappress.js', array('mapbox-js', 'underscore', 'jquery'), '0.0.9.3');
	wp_enqueue_script('mappress.hash', get_template_directory_uri() . '/js/mappress.hash.js', array('mappress', 'underscore'), '0.0.1.12');
	wp_enqueue_script('mappress.geocode', get_template_directory_uri() . '/js/mappress.geocode.js', array('mappress', 'd3js', 'underscore'), '0.0.2.7');
	wp_enqueue_script('mappress.filterLayers', get_template_directory_uri() . '/js/mappress.filterLayers.js', array('mappress', 'underscore'), '0.0.7');
	wp_enqueue_script('mappress.groups', get_template_directory_uri() . '/js/mappress.groups.js', array('mappress', 'underscore'), '0.0.5.2');
	wp_enqueue_script('mappress.markers', get_template_directory_uri() . '/js/mappress.markers.js', array('mappress', 'underscore'), '0.0.5');

	wp_enqueue_style('mappress', get_template_directory_uri() . '/css/mappress.css', array(), '0.0.1.2');

	wp_localize_script('mappress', 'mappress_localization', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'more_label' => __('More', 'mappress')
	));

	wp_localize_script('mappress.geocode', 'mappress_labels', array(
		'search_placeholder' => __('Find a location', 'mappress'),
		'results_title' => __('Results', 'mappress'),
		'clear_search' => __('Clear search', 'mappress'),
		'not_found' => __('Nothing found, try something else.', 'mappress')
	));

	wp_localize_script('mappress.groups', 'mappress_groups', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'more_label' => __('More', 'mappress')
	));

	wp_localize_script('mappress.markers', 'mappress_markers', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'query' => mappress_get_marker_query_args()
	));
}
add_action('wp_enqueue_scripts', 'mappress_scripts');

// Plugins implementations and fixes
include(TEMPLATEPATH  . '/plugins/mappress-plugins.php');

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

// disable canonical redirect on map/map-group post type for stories pagination
add_filter('redirect_canonical', 'mappress_disable_canonical');
function mappress_disable_canonical($redirect_url) {
	if(is_singular('map') || is_singular('map-group'))
		return false;
}

// get data

add_action('wp_ajax_nopriv_mapgroup_data', 'mappress_get_mapgroup_json_data');
add_action('wp_ajax_mapgroup_data', 'mappress_get_mapgroup_json_data');
function mappress_get_mapgroup_json_data($group_id = false) {
	$group_id = $group_id ? $group_id : $_REQUEST['group_id'];
	$data = json_encode(mappress_get_mapgroup_data($group_id));
	header('Content Type: application/json');
	echo $data;
	exit;
}

function mappress_get_mapgroup_data($group_id) {
	global $post;
	$group_id = $group_id ? $group_id : $post->ID;
	$data = array();
	if(get_post_type($group_id) != 'map-group')
		return;
	$group_data = get_post_meta($group_id, 'mapgroup_data', true);
	foreach($group_data['maps'] as $map) {
		$map_id = 'map_' . $map['id'];
		$data['maps'][$map_id] = mappress_get_map_data($map['id']);
	}
	return apply_filters('mappress_mapgroup_data', $data);
}

add_action('wp_ajax_nopriv_map_data', 'mappress_get_map_json_data');
add_action('wp_ajax_map_data', 'mappress_get_map_json_data');
function mappress_get_map_json_data($map_id = false) {
	$map_id = $map_id ? $map_id : $_REQUEST['map_id'];
	$data = json_encode(mappress_get_map_data($map_id));
	header('Content Type: application/json');
	echo $data;
	exit;
}

function mappress_get_map_data($map_id = false) {
	global $post;
	$map_id = $map_id ? $map_id : $post->ID;
	if(get_post_type($map_id) != 'map')
		return;
	$post = get_post($map_id);
	setup_postdata($post);
	$data = get_post_meta($map_id, 'map_data', true);
	$data['postID'] = $map_id;
	$data['title'] = get_the_title($map_id);
	$data['legend'] = mappress_get_map_legend();
	if(get_the_content())
		$data['legend_full'] = '<h2>' . $data['title'] . '</h2>' .  apply_filters('the_content', get_the_content());
	wp_reset_postdata();
	return apply_filters('mappress_map_data', $data);
}

function mappress_get_map_legend($map_id = false) {
	global $post;
	$map_id = $map_id ? $map_id : $post->ID;
	return apply_filters('mappress_map_legend', get_post_meta($map_id, 'legend', true));
}

function mappress_get_marker_bubble($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	ob_start();
	get_template_part('content', 'marker-bubble');
	$bubble = ob_get_contents();
	ob_end_clean();
	return apply_filters('mappress_marker_bubble', $bubble);
}

function mappress_get_marker_icon() {
	$marker = array(
		'url' => get_template_directory_uri() . '/img/marker.png',
		'width' => 26,
		'height' => 30
	);
	return apply_filters('mappress_marker_icon', $marker);
}

function mappress_get_marker_class() {
	global $post;
	$class = get_post_class();
	return apply_filters('mappress_marker_class', $class);
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
	return apply_filters('mappress_marker_data', $properties);
}

/*
 * Markers in GeoJSON
 */

add_action('wp_ajax_nopriv_markers_geojson', 'mappress_get_markers_data');
add_action('wp_ajax_markers_geojson', 'mappress_get_markers_data');
function mappress_get_markers_data() {
	$query = $_REQUEST['query'];

	$cache_key = 'markers_';

	if($_REQUEST['lang'])
		$cache_key .= $_REQUEST['lang'] . '_';

	$query_id = md5(serialize($query));
	$cache_key .= $query_id;

	$data = false;
	//$data = get_transient($cache_key);

	if($data === false) {

		$data = array();

		$posts = apply_filters('mappress_the_markers_posts', get_posts($query), $query);

		if($posts) {
			global $post;
			$data['type'] = 'FeatureCollection';
			$data['features'] = array();
			$i = 0;
			foreach($posts as $post) {

				setup_postdata($post);

				$data['features'][$i]['type'] = 'Feature';

				$data['features'][$i]['geometry'] = array();
				$data['features'][$i]['geometry']['type'] = 'Point';

				$latitude = get_post_meta($post->ID, 'geocode_latitude', true);
				$longitude = get_post_meta($post->ID, 'geocode_longitude', true);

				if($latitude && $longitude)
					$data['features'][$i]['geometry']['coordinates'] = array($longitude, $latitude);
				else
					$data['features'][$i]['geometry']['coordinates'] = array(0, 0);

				// marker properties
				$data['features'][$i]['properties'] = mappress_get_marker_properties();

				$i++;

				wp_reset_postdata();
			}
		}
		$data['query_id'] = $query_id;
		$data = apply_filters('mappress_markers_data', $data);
		$data = json_encode($data);
		//set_transient($transient, $data, 60*60*1);
	}

	/* Browser caching */
	$expires = 60 * 10; // 10 minutes of browser cache
	header('Pragma: public');
	header('Cache-Control: maxage=' . $expires);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
	/* --------------- */

	header('Content Type: application/json');
	echo $data;
	exit;
}

?>