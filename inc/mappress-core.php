<?php

// register map and map group post types
require_once(TEMPLATEPATH . '/inc/mappress-post-types.php');

/*
 * Map global vars and query settings
 */

global $mappress_map, $mappress_mapgroup_id, $mappress_map_count;

$mappress_map = false;
$mappress_map_count = 0;

function mappress_the_query($query) {

	if(is_admin())
		return;

	global $mappress_map;

	remove_action('pre_get_posts', 'mappress_the_query');

	if($query->is_main_query()) {
		if(is_home() && !$mappress_map) {
			$mappress_map = mappress_map_featured();
		} elseif($query->get('map') || $query->get('map-group')) {
			if($query->get('map'))
				$type = 'map';
			elseif($query->get('map-group'))
				$type = 'map-group';
			$mappress_map = get_page_by_path($query->get($type), 'OBJECT', $type);
		}
	}

	add_action('pre_get_posts', 'mappress_the_query');

	if(!$mappress_map)
		return;

	if(get_post_type($mappress_map->ID) == 'map') {
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key' => 'maps',
				'value' => $mappress_map->ID,
				'compare' => 'LIKE'
			),
			array(
				'key' => 'has_maps',
				'value' => '',
				'compare' => 'NOT EXISTS'
			)
		);
	} elseif(get_post_type($mappress_map->ID) == 'map-group') {
		/*
		This can get really huge and crash, not using for now.
		Plan to create a custom query var for the query string and try to create the query server-side.
		$groupdata = get_post_meta($mappress_map->ID, 'mapgroup_data', true);
		$meta_query = array('relation' => 'OR');
		$i = 1;
		foreach($groupdata['maps'] as $m) {
			$meta_query[$i] = array(
				'key' => 'maps',
				'value' => intval($m['id']),
				'compare' => 'LIKE'
			);
			$i++;
		}
		$meta_query[$i] = array(
			'key' => 'has_maps',
			'value' => '',
			'compare' => 'NOT EXISTS'
		);
		*/
	}
	$query->set('meta_query', $meta_query);

}
add_action('parse_query', 'mappress_the_query');

function mappress_the_post($post) {
	if(is_single() && mappress_has_marker_location() && !is_singular(array('map', 'map-group'))) {
		global $mappress_map;
		$post_maps = get_post_meta($post_id, 'maps');
		if(!$post_maps) {
			$mappress_map = mappress_map_featured();
		} else {
			$mappress_map = get_post(array_shift($post_maps));
		}
	}
}
add_action('the_post', 'mappress_the_post');

/*
 * Allow search box inside map page (disable `s` argument for the map query)
 */
function mappress_pre_get_map($query) {
	if($query->get('map')) {
		if(isset($_GET['s']))
			$query->set('s', null);
		do_action('mappress_pre_get_map', $query);
	}
}
add_action('pre_get_posts', 'mappress_pre_get_map');

function mappress_map_featured_type() {
	return apply_filters('mappress_featured_map_type', array('map', 'map-group'));
}

function mappress_map_featured($post_type = false) {
	$post_type = $post_type ? $post_type : mappress_map_featured_type();
	$featured_map_id = get_option('mappress_featured_map');
	if(!$featured_map_id) {
		$featured_map = mappress_map_latest($post_type);
	} else {
		$featured_map = get_post($featured_map_id);
	}
	return $featured_map;
}

function mappress_map_latest($post_type = false) {
	$post_type = $post_type ? $post_type : mappress_map_featured_type();
	$latest_map = get_posts(array('post_type' => $post_type, 'posts_per_page' => 1));
	if($latest_map)
		$map = array_shift($latest_map);

	return $map;
}

function mappress_is_map($map_id = false) {
	global $post;
	$map_id = $map_id ? $map_id : $post->ID;
	if(get_post_type($map_id) == 'map' || get_post_type($map_id) == 'map-group')
		return true;

	return false;
}

function mappress_setup_mapgroupdata($mapgroup) {
	global $mappress_mapgroup_id;
	$mappress_mapgroup_id = $mapgroup->ID;
	do_action('mappress_the_mapgroup', $mapgroup);
	return true;
}

/*
 * Featured map
 */

function mappress_get_map_featured() {
	$featured = mappress_map_featured();
	mappress_map($featured->ID);
}

/*
 * Display maps
 */

function mappress_map($map_id = false, $main_map = true) {
	global $mappress_map, $post, $mappress_map_count;
	if(is_single()) {
		if(!mappress_is_map() && !mappress_has_marker_location()) {
			return;
		} else {
			$single_post_maps_id = get_post_meta($post->ID, 'maps');
			if($single_post_maps_id && !$map_id)
				$map_id = array_shift($single_post_maps_id);
		}
	}

	if($map_id)
		$mappress_map = get_post($map_id);
	else
		$map_id = $mappress_map->ID;

	if($main_map) add_filter('mappress_map_conf', 'mappress_map_set_main');
	get_template_part('content', get_post_type($map_id));
	if($main_map) remove_filter('mappress_map_conf', 'mappress_map_set_main');

	$map_js_id = 'map_' . $map_id . '_' . $mappress_map_count;

	$mappress_map_count++;

	return $map_js_id;
}

function mappress_map_set_main($conf) {
	$conf['mainMap'] = true;
	return $conf;
}

function mappress_map_conf() {
	return json_encode(mappress_get_map_conf());
}

function mappress_get_map_conf() {
	global $mappress_map, $post, $mappress_map_count;
	$conf = array(
		'postID' => $mappress_map->ID,
		'count' => $mappress_map_count
	); // default
	if(is_post_type_archive('map')) {
		$conf['disableMarkers'] = true;
		$conf['disableHash'] = true;
		$conf['disableInteraction'] = true;
	}
	return apply_filters('mappress_map_conf', $conf, $mappress_map, $post);
}

function mappress_map_id() {
	global $mappress_map, $mappress_map_count;
	return $mappress_map->ID . '_' . $mappress_map_count;
}


/*
 * Register/enqueue core scripts & styles
 */
function mappress_scripts() {
	wp_register_script('imagesloaded', get_template_directory_uri() . '/lib/jquery.imagesloaded.min.js', array('jquery'));
	wp_register_script('underscore', get_template_directory_uri() . '/lib/underscore-min.js', array(), '1.4.3');
	wp_register_script('mapbox-js', get_template_directory_uri() . '/lib/mapbox.js', array(), '0.6.7');
	wp_enqueue_style('mapbox', get_template_directory_uri() . '/lib/mapbox.css', array(), '0.6.7');

	wp_register_script('d3js', get_template_directory_uri() . '/lib/d3.v2.min.js', array('jquery'), '3.0.5');

	wp_enqueue_script('mappress', get_template_directory_uri() . '/js/mappress.js', array('mapbox-js', 'underscore', 'jquery'), '0.0.15.6');
	wp_enqueue_script('mappress.hash', get_template_directory_uri() . '/js/mappress.hash.js', array('mappress', 'underscore'), '0.0.2.1');
	wp_enqueue_script('mappress.geocode', get_template_directory_uri() . '/js/mappress.geocode.js', array('mappress', 'd3js', 'underscore'), '0.0.2.9');
	wp_enqueue_script('mappress.filterLayers', get_template_directory_uri() . '/js/mappress.filterLayers.js', array('mappress', 'underscore'), '0.0.9');
	wp_enqueue_script('mappress.groups', get_template_directory_uri() . '/js/mappress.groups.js', array('mappress', 'underscore'), '0.0.7');
	wp_enqueue_script('mappress.ui', get_template_directory_uri() . '/js/mappress.ui.js', array('mappress'), '0.0.6.8');

	wp_enqueue_style('mappress', get_template_directory_uri() . '/css/mappress.css', array(), '0.0.1.2');

	wp_localize_script('mappress', 'mappress_localization', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'more_label' => __('More', 'mappress')
	));

	wp_localize_script('mappress.geocode', 'mappress_labels', array(
		'search_placeholder' => __('Find a location', 'mappress'),
		'results_title' => __('Results', 'mappress'),
		'clear_search' => __('Close search', 'mappress'),
		'not_found' => __('Nothing found, try something else.', 'mappress')
	));

	wp_localize_script('mappress.groups', 'mappress_groups', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'more_label' => __('More', 'mappress')
	));

	/* geocode scripts */
	$geocode_service = mappress_geocode_service();
	$gmaps_key = mappress_gmaps_api_key();
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
add_action('wp_enqueue_scripts', 'mappress_scripts');

// geocode service choice
function mappress_geocode_service() {
	// osm or gmaps (gmaps requires api key)
	return apply_filters('mappress_geocode_service', 'osm');
}

// gmaps api
function mappress_gmaps_api_key() {
	return apply_filters('mappress_gmaps_api_key', false);
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
	global $mappress_map;
	$group_id = $group_id ? $group_id : $mappress_map->ID;
	$data = array();
	if(get_post_type($group_id) != 'map-group')
		return;
	$group_data = get_post_meta($group_id, 'mapgroup_data', true);
	foreach($group_data['maps'] as $map) {
		$map_id = $map['id'];
		$data['maps'][$map_id] = mappress_get_map_data($map['id']);
	}
	return apply_filters('mappress_mapgroup_data', $data, $post);
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
	global $mappress_map;
	$map_id = $map_id ? $map_id : $mappress_map->ID;
	if(get_post_type($map_id) != 'map')
		return;
	$post = get_post($map_id);
	setup_postdata($post);
	$data = get_post_meta($map_id, 'map_data', true);
	$data['postID'] = $map_id;
	$data['title'] = get_the_title($map_id);
	$data['legend'] = mappress_get_map_legend($map_id);
	if(get_the_content())
		$data['legend_full'] = '<h2>' . $data['title'] . '</h2>' . apply_filters('the_content', get_the_content());
	wp_reset_postdata();
	return apply_filters('mappress_map_data', $data, $post);
}

function mappress_get_map_legend($map_id = false) {
	global $mappress_map;
	$map_id = $map_id ? $map_id : $mappress_map->ID;
	return apply_filters('mappress_map_legend', get_post_meta($map_id, 'legend', true), $map);
}

// disable canonical redirect on map/map-group post type for stories pagination
add_filter('redirect_canonical', 'mappress_disable_canonical');
function mappress_disable_canonical($redirect_url) {
	if(is_singular('map') || is_singular('map-group'))
		return false;
}

?>