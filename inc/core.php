<?php

/*
 * JEO Core
 */

class JEO {

	var $directory = '';

	var $directory_uri = '';

	var $map = false;

	var $mapgroup_id = false;

	var $map_count = 0;

	var $has_main_map = false;

	var $mapped_post_types = false;

	var $options = array();

	function __construct() {
		add_action('init', array($this, 'init'));
		$this->plugin_fixes();
	}

	function init() {
		$this->setup_directories();
		$this->get_options();
		$this->setup_the_post_map();
		$this->setup_scripts();
		$this->setup_post_types();
		$this->setup_query();
		$this->setup_pre_get_map();
		$this->setup_ajax();
		$this->setup_canonical();
		do_action('jeo_init');
	}

	function setup_directories() {
		$this->directory = apply_filters('jeo_directory', get_template_directory() . '/inc');
		$this->directory_uri = apply_filters('jeo_directory_uri', get_template_directory_uri());
	}

	function get_options() {
		$options = get_option('jeo_settings');
		if($options && isset($options['jeo_settings'])) {
			$this->options = $options['jeo_settings'];
		} else {
			$this->options = false;
		}
		return $this->options;
	}

	function setup_scripts() {
		add_action('wp_head', array($this, 'scripts'), 2);
		add_action('admin_footer', array($this, 'scripts'));
	}

	function scripts() {

		if(is_admin()) {
			wp_enqueue_style('jeo-dashicons', get_template_directory_uri() . '/css/dashicons.css');
			?>
			<style>
			#adminmenu #menu-posts-map.menu-icon-post div.wp-menu-image:before {
			  font-family: 'jeo-dashicons' !important;
			  content: '\e609';
			}
			#adminmenu #menu-posts-map-group.menu-icon-post div.wp-menu-image:before {
			  font-family: 'jeo-dashicons' !important;
			  content: '\e607';
			}
			</style>
			<?php
		}

		/*
		 * Libraries
		 */

		// LEAFLET or CARTODB
		// If map contains CartoDB layer, use cartodb lib (includes own leaflet)
		$cartodb = true;
		// $layers = jeo_get_map_layers();
		// if($layers) {
		// 	foreach($layers as $layer) {
		// 		if($layer['type'] == 'cartodb')
		// 			$cartodb = true;
		// 	}
		// }

		if($cartodb || is_admin()) {

			wp_register_script('leaflet', get_template_directory_uri() . '/lib/cartodb.js', array(), '3.11.26');
			wp_enqueue_style('cartodb', get_template_directory_uri() . '/lib/cartodb.css');

		} else {

			wp_register_script('leaflet', get_template_directory_uri() . '/lib/leaflet/leaflet.js', array(), '0.7.3');
            wp_enqueue_style('leaflet', get_template_directory_uri() . '/lib/leaflet/leaflet.css');

		}

		wp_register_style('leaflet-ie', get_template_directory_uri() . '/lib/leaflet/leaflet.ie.css');
		$GLOBALS['wp_styles']->add_data('leaflet-ie', 'conditional', 'lte IE 8');
		wp_enqueue_style('leaflet-ie');

		// MAPBOX
		wp_register_script('mapbox-js', get_template_directory_uri() . '/lib/mapbox/mapbox.standalone.js', array('leaflet'), '2.2.1');
		wp_enqueue_style('mapbox-js', get_template_directory_uri() . '/lib/mapbox/mapbox.standalone.css');

		wp_register_script('imagesloaded', get_template_directory_uri() . '/lib/jquery.imagesloaded.min.js', array('jquery'));
		wp_register_script('underscore', get_template_directory_uri() . '/lib/underscore-min.js', array(), '1.4.3');

		/*
		 * Local
		 */
		wp_enqueue_script('jeo', get_template_directory_uri() . '/inc/js/jeo.js', array('mapbox-js', 'underscore', 'jquery'), '0.4.3');

		wp_enqueue_script('jeo.groups', get_template_directory_uri() . '/inc/js/groups.js', array('jeo'), '0.2.7');

		wp_enqueue_script('jeo.geocode', get_template_directory_uri() . '/inc/js/geocode.js', array('jeo'), '0.0.5');
		wp_enqueue_script('jeo.fullscreen', get_template_directory_uri() . '/inc/js/fullscreen.js', array('jeo'), '0.0.7');
		wp_enqueue_script('jeo.filterLayers', get_template_directory_uri() . '/inc/js/filter-layers
			.js', array('jeo'), '0.1.3');
		wp_enqueue_script('jeo.ui', get_template_directory_uri() . '/inc/js/ui.js', array('jeo'), '0.0.9');
		wp_enqueue_style('jeo', get_template_directory_uri() . '/inc/css/jeo.css', array(), '0.0.2');

		if($this->use_hash())
			wp_enqueue_script('jeo.hash', get_template_directory_uri() . '/inc/js/hash.js', array('jeo'), '0.1.0');

		wp_localize_script('jeo', 'jeo_localization', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'more_label' => __('More', 'jeo')
		));

		wp_localize_script('jeo', 'jeo_settings', array(
			'mapbox_access_token' => $this->mapbox_access_token()
		));

		wp_localize_script('jeo.geocode', 'jeo_labels', array(
			'search_placeholder' => __('Find a location', 'jeo'),
			'results_title' => __('Results', 'jeo'),
			'clear_search' => __('Close search', 'jeo'),
			'not_found' => __('Nothing found, try something else.', 'jeo')
		));

		wp_localize_script('jeo.groups', 'jeo_groups', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'more_label' => __('More', 'jeo')
		));

		if(!is_admin()) {
			do_action('jeo_enqueue_scripts');
		}
	}

	function setup_post_types() {
		$this->register_post_types();
		$this->mapped_post_types();
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function register_post_types() {
		/*
		 * Map
		 */
		$labels = array(
			'name' => __('Maps', 'jeo'),
			'singular_name' => __('Map', 'jeo'),
			'add_new' => __('Add new map', 'jeo'),
			'add_new_item' => __('Add new map', 'jeo'),
			'edit_item' => __('Edit map', 'jeo'),
			'new_item' => __('New map', 'jeo'),
			'view_item' => __('View map', 'jeo'),
			'search_items' => __('Search maps', 'jeo'),
			'not_found' => __('No map found', 'jeo'),
			'not_found_in_trash' => __('No map found in the trash', 'jeo'),
			'menu_name' => __('Maps', 'jeo')
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'description' => __('JEO Maps', 'jeo'),
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
			'rewrite' => array('slug' => 'maps'),
			'public' => true,
			'show_in_menu' => true,
			'menu_position' => 4,
			'has_archive' => true,
			'exclude_from_search' => true,
			'capability_type' => 'page'
		);

		register_post_type('map', $args);

		/*
		 * Map group
		 */
		$labels = array(
			'name' => __('Map groups', 'jeo'),
			'singular_name' => __('Map group', 'jeo'),
			'add_new' => __('Add new map group', 'jeo'),
			'add_new_item' => __('Add new map group', 'jeo'),
			'edit_item' => __('Edit map group', 'jeo'),
			'new_item' => __('New map group', 'jeo'),
			'view_item' => __('View map group', 'jeo'),
			'search_items' => __('Search map group', 'jeo'),
			'not_found' => __('No map group found', 'jeo'),
			'not_found_in_trash' => __('No map group found in the trash', 'jeo'),
			'menu_name' => __('Map groups', 'jeo')
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'description' => __('JEO Map Groups', 'jeo'),
			'supports' => array( 'title'),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 4,
			'exclude_from_search' => true,
			'rewrite' => array('slug' => 'mapgroup', 'with_front' => false),
			'capability_type' => 'page'
		);

		register_post_type('map-group', $args);
	}

	function admin_menu() {
		//add_theme_page(__('Map groups', 'jeo'), __('Map groups', 'jeo'), 'edit_posts', 'edit.php?post_type=map-group');
		//add_theme_page(__('Add new group', 'jeo'), __('Add new map group', 'jeo'), 'edit_posts', 'post-new.php?post_type=map-group');
	}

	function mapped_post_types() {
		$custom = get_post_types(array('public' => true, '_builtin' => false));
		$this->mapped_post_types = $custom + array('post');
		unset($this->mapped_post_types['map']);
		unset($this->mapped_post_types['map-layer']);
		unset($this->mapped_post_types['map-group']);
		return apply_filters('jeo_mapped_post_types', $this->mapped_post_types);
	}

	function setup_query() {
		if($this->use_the_query()) {
			add_filter('query_vars', array($this, 'query_vars'));
			add_action('parse_query', array($this, 'parse_query'), 5, 1);
			add_filter('posts_clauses', array($this, 'posts_clauses'), 5, 2);
		}
	}

	function use_the_query() {
		$options = $this->get_options();
		if(isset($options['map']['use_map_query']))
			$use_query = $options['map']['use_map_query'];
		else
			$use_query = true;
		return apply_filters('jeo_use_map_query', $use_query);
	}

	function use_hash() {
		$options = $this->get_options();
		if(isset($options['map']))
			$use_hash = $options['map']['use_hash'] ? true : false;
		else
			$use_hash = true;
		return apply_filters('jeo_use_hash', $use_hash);
	}

	function mapbox_access_token() {
		$options = $this->get_options();
		if(isset($options['mapbox']))
			$access_token = $options['mapbox']['access_token'];
		else
			$access_token = false;
		return apply_filters('jeo_mapbox_access_token', $access_token);
	}

	function query_vars($vars) {
		$vars[] = 'map_id';
		$vars[] = 'without_map_query';
		return $vars;
	}

	function posts_clauses($clauses, $query) {

		if((is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) || !$this->map || $query->get('without_map_query'))
			return $clauses;

		global $wpdb;

		$map_id = $this->map->ID;

		$join = "
			LEFT JOIN {$wpdb->postmeta} AS m_has_maps ON ({$wpdb->posts}.ID = m_has_maps.post_id AND m_has_maps.meta_key = 'has_maps')
			INNER JOIN {$wpdb->postmeta} m_maps ON ({$wpdb->posts}.ID = m_maps.post_id)
			";


		// MAP
		if(get_post_type($map_id) == 'map') {

			$where = "
				AND (
					(
						m_maps.meta_key = 'maps'
						AND CAST(m_maps.meta_value AS CHAR) = '{$map_id}'
					)
					OR m_has_maps.post_id IS NULL
				) ";

		// MAPGROUP
		} elseif(get_post_type($map_id) == 'map-group') {

			$groupdata = get_post_meta($map_id, 'mapgroup_data', true);

			$where = "
				AND (
			";

			if(isset($groupdata['maps']) && is_array($groupdata['maps'])) {
				foreach($groupdata['maps'] as $m) {

					$c_map_id = $m['id'];

					$where .= "
					(
						m_maps.meta_key = 'maps'
						AND CAST(m_maps.meta_value AS CHAR) = '{$c_map_id}'
					)
					OR
					";

				}
			}

			$where .= "
				m_has_maps.post_id IS NULL
			) ";

		}

		$groupby = '';
		if(!$clauses['groupby'])
			$groupby = " {$wpdb->posts}.ID ";

		// hooks
		$join = apply_filters('jeo_posts_clauses_join', $join, $clauses, $query);
		$where = apply_filters('jeo_posts_clauses_where', $where, $clauses, $query);
		$groupby = apply_filters('jeo_posts_clauses_groupby', $groupby, $clauses, $query);

		$clauses['join'] .= $join;
		$clauses['where'] .= $where;
		$clauses['groupby'] .= $groupby;

		return $clauses;
	}

	function parse_query($query) {

		if(is_admin() && !(defined('DOING_AJAX') && DOING_AJAX))
			return $query;

		if($query->get('map_id')) {
			$map_id = $query->get('map_id');
			$this->set_map(get_post($map_id));

		} else {

			if($query->is_main_query()) {
				if(is_home() && !$this->map) {
					$this->set_map($this->featured());
				} elseif($query->get('map') || $query->get('map-group')) {
					if($query->get('map'))
						$type = 'map';
					elseif($query->get('map-group'))
						$type = 'map-group';
					$this->set_map(get_page_by_path($query->get($type), 'OBJECT', $type));
				}
			}

		}

		return $query;
	}

	/*
	 * Set map when post is initialized
	 */
	function setup_the_post_map() {
		add_action('wp', array($this, 'the_post_map'));
	}
	function the_post_map() {
		if(!$this->map) {
			global $post;
			$post_maps_ids = get_post_meta($post->ID, 'maps');
			if($post_maps_ids)
				$map = get_post(array_shift($post_maps_ids));
			else
				$map = $this->featured();

			$this->set_map($map);
		}
	}

	function set_map($post) {
		$this->map = $post;
		return $this->map;
	}


	/*
	 * Allow search box inside map page (disable `s` argument for the map query)
	 */
	function setup_pre_get_map() {
		add_action('pre_get_posts', array($this, 'pre_get_map'));
	}
	function pre_get_map($query) {
		if($query->get('map')) {
			if(isset($_GET['s']))
				$query->set('s', null);
			do_action('jeo_pre_get_map', $query);
		}
	}

	function get_id() {
		return $this->map->ID . '_' . $this->map_count;
	}

	function get_the_ID() {
		return $this->map->ID;
	}

	function featured_map_type() {
		return apply_filters('jeo_featured_map_type', array('map', 'map-group'));
	}

	function featured($post_type = false) {
		$post_type = $post_type ? $post_type : $this->featured_map_type();

		if(isset($this->options['front_page']) && $this->options['front_page']['featured_map'])
			$featured_id = $this->options['front_page']['featured_map'];

		if(!$featured_id) {
			$featured = $this->latest($post_type);
		} else {
			$featured = get_post($featured_id);
		}
		return $featured;
	}

	function latest($post_type = false) {
		$post_type = $post_type ? $post_type : $this->featured_map_type();
		$latest_map = get_posts(array('post_type' => $post_type, 'posts_per_page' => 1));
		if($latest_map)
			$map = array_shift($latest_map);

		return $map;
	}

	function is_map($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		if(get_post_type($post_id) == 'map' || get_post_type($post_id) == 'map-group')
			return true;

		return false;
	}

	/*
	 * Display maps
	 */

	function get_map($map_id = false, $main_map = true, $force = false) {

		global $post;
		if(is_single()) {
			if(!$this->is_map() && !jeo_has_marker_location() && !$force) {
				return;
			} else {
				$single_post_maps_id = get_post_meta($post->ID, 'maps');
				if($single_post_maps_id && !$map_id)
					$map_id = array_shift($single_post_maps_id);
			}
		}

		if($map_id) {
			$this->set_map(get_post($map_id));
		} else
			$map_id = $this->map->ID;

		if($main_map) add_filter('jeo_map_conf', array($this, 'set_main'));
		get_template_part('content', get_post_type($map_id));
		if($main_map) remove_filter('jeo_map_conf', array($this, 'set_main'));

		$map_js_id = 'map_' . $map_id . '_' . $this->map_count;

		$this->map_count++;

		return $map_js_id;
	}

	// display featured map
	function get_featured($main_map = true, $force = false) {
		$featured_id = $this->featured()->ID;
		if(!$featured_id && current_user_can('edit_posts')) {
			return $this->create_map_message();
		}
		return $this->get_map($this->featured()->ID, $main_map, $force);
	}

	function create_map_message() {
		?>
		<div id="first-map-message">
			<h2><?php _e('You haven\'t created any maps!', 'jeo'); ?></h2>
			<h3><a href="<?php echo admin_url('/post-new.php?post_type=map'); ?>"><?php _e('Click here to create your first', 'jeo'); ?></a></h3>
		</div>
		<?php
	}

	function set_main($conf) {
		$this->has_main_map = true;
		$conf['mainMap'] = true;
		return $conf;
	}

	function map_conf() {
		return json_encode($this->get_map_conf());
	}

	function get_map_conf($map_id = false) {
		global $post;
		$map = $map_id ? get_post($map_id) : $this->map;
		$conf = array(
			'postID' => $map_id ? $map_id :  $this->map->ID,
			'count' => $this->map_count
		); // default
		if(is_post_type_archive('map')) {
			$conf['disableMarkers'] = true;
			$conf['disableHash'] = true;
			$conf['disableInteraction'] = true;
		}
		return apply_filters('jeo_map_conf', $conf, $map, $post);
	}

	function mapgroup_conf() {
		return json_encode($this->get_mapgroup_conf());
	}

	function get_mapgroup_conf() {
		global $post;
		$conf = array(
			'postID' => $this->map->ID,
			'count' => $this->map_count
		); // default
		if(is_post_type_archive('map-group')) {
			$conf['disableMarkers'] = true;
			$conf['disableHash'] = true;
			$conf['disableInteraction'] = true;
		}
		return apply_filters('jeo_mapgroup_conf', $conf, $this->map, $post);
	}

	function get_map_data($map_id = false) {
		$map_id = $map_id ? $map_id : $this->map->ID;
		if(get_post_type($map_id) != 'map')
			return;
		$post = get_post($map_id);
		setup_postdata($post);
		$data = get_post_meta($map_id, 'map_data', true);
		$data['dataReady'] = true;
		$data['postID'] = $map_id;
		$data['title'] = get_the_title($map_id);
		$data['legend'] = $this->get_map_legend($map_id);
		$data['layers'] = jeo_get_map_layers($map_id);
		if($data['base_layer']) {
			array_unshift($data['layers'], array(
				'type' => 'tilelayer',
				'tile_url' => $data['base_layer']['url']
			));
		}
		if($post->post_content)
			$data['legend_full'] = '<h2>' . $data['title'] . '</h2>' . apply_filters('the_content', $post->post_content);
		$data = apply_filters('jeo_map_data', $data, $post);
		wp_reset_postdata();
		return $data;
	}

	function get_map_center($map_id = false) {
		$map_id = $map_id ? $map_id : $this->map->ID;
		$map_data = $this->get_map_data($map_id);
		return $map_data['center'];
	}

	function get_map_zoom($map_id = false) {
		$map_id = $map_id ? $map_id : $this->map->ID;
		$map_data = $this->get_map_data($map_id);
		return $map_data['zoom'];
	}

	function get_map_max_zoom($map_id = false) {
		$map_id = $map_id ? $map_id : $this->map->ID;
		$map_data= $this->get_map_data($map_id);
		return $map_data['max_zoom'];
	}

	function get_mapgroup_data($group_id = false) {
		$group_id = $group_id ? $group_id : $this->map->ID;
		$data = array();
		if(get_post_type($group_id) != 'map-group')
			return;
		$group_data = get_post_meta($group_id, 'mapgroup_data', true);
		if(is_array($group_data['maps'])) {
			foreach($group_data['maps'] as $map) {
				$map_id = $map['id'];
				$data['maps'][$map_id] = $map;
				$data['maps'][$map_id] += $this->get_map_data($map['id']);
			}
		}
		return apply_filters('jeo_mapgroup_data', $data, $post);
	}

	function get_map_legend($map_id = false) {
		$map_id = $map_id ? $map_id : $this->map->ID;
		return apply_filters('jeo_map_legend', get_post_meta($map_id, 'legend', true), $this->map);
	}

	/*
	 * Ajax
	 */

	function setup_ajax() {
		add_action('wp_ajax_nopriv_mapgroup_data', array($this, 'get_mapgroup_json_data'));
		add_action('wp_ajax_mapgroup_data', array($this, 'get_mapgroup_json_data'));
		add_action('wp_ajax_nopriv_map_data', array($this, 'get_map_json_data'));
		add_action('wp_ajax_map_data', array($this, 'get_map_json_data'));
	}

	function get_mapgroup_json_data($group_id = false) {
		$group_id = $group_id ? $group_id : $_REQUEST['group_id'];
		$data = json_encode($this->get_mapgroup_data($group_id));
		header('Content-Type: application/json');
		echo $data;
		exit;
	}

	function get_map_json_data($map_id = false) {
		$map_id = $map_id ? $map_id : $_REQUEST['map_id'];
		$data = json_encode($this->get_map_data($map_id));
		header('Content-Type: application/json');
		echo $data;
		exit;
	}

	/*
	 * Disable canonical redirect on map/map-group post type for stories pagination
	 */
	function setup_canonical() {
		add_filter('redirect_canonical', array($this, 'disable_canonical'));
	}
	function disable_canonical($redirect_url) {
		if(is_singular('map') || is_singular('map-group'))
			return false;
	}

	/*
	 * Plugin fixes
	 */

	function plugin_fixes() {
		$this->fix_qtranslate();
	}

	function fix_qtranslate() {
		if(function_exists('qtrans_getLanguage')) {
			add_filter('get_the_date', array($this, 'qtranslate_get_the_date'), 10, 2);
			add_filter('admin_url', array($this, 'qtranslate_admin_url'), 10, 2);
			add_action('post_type_archive_link', 'qtrans_convertURL');
		}
	}

	// enable custom format date
	function qtranslate_get_the_date($date, $format) {
		if($format != '') {
			$post = get_post();
			$date = mysql2date($format, $post->post_date);
		}
		return $date;
	}

	// send lang to ajax requests
	function qtranslate_admin_url($url, $path) {
		if($path == 'admin-ajax.php' && function_exists('qtrans_getLanguage'))
			$url .= '?lang=' . qtrans_getLanguage();

		return $url;
	}
}

$jeo = new JEO();

require_once(get_template_directory() . '/inc/layers.php');
require_once(get_template_directory() . '/inc/markers.php');
require_once(get_template_directory() . '/inc/ui.php');
// GeoJSON API
require_once(get_template_directory() . '/inc/api.php');
// Embed functionality
require_once(get_template_directory() . '/inc/embed.php');
// Metaboxes
require_once(get_template_directory() . '/metaboxes/metaboxes.php');
require_once(get_template_directory() . '/inc/featured.php');
include_once(get_template_directory() . '/inc/range-slider.php');

// WP API
include_once(get_template_directory() . '/inc/wp-api.php');

/*
 * JEO functions api
 */

function jeo_get_options() {
	global $jeo;
	return $jeo->get_options();
}

function jeo_get_mapbox_access_token() {
	global $jeo;
	return $jeo->mapbox_access_token();
}

function jeo_the_query($query) {
	global $jeo;
	return $jeo->the_query($query);
}

// mapped post types
function jeo_get_mapped_post_types() {
	global $jeo;
	return $jeo->mapped_post_types();
}

function jeo_set_map($post) {
	global $jeo;
	return $jeo->set_map($post);
}

// get the main map post
function jeo_the_map() {
	global $jeo;
	return $jeo->map;
}


// get the featured map post
function jeo_map_featured($post_type = false) {
	global $jeo;
	return $jeo->featured($post_type);
}


// get the latest map post
function jeo_map_latest($post_type = false) {
	global $jeo;
	return $jeo->latest($post_type);
}

// if post is map
function jeo_is_map($map_id = false) {
	global $jeo;
	return $jeo->is_map($map_id);
}

// display the featured map
function jeo_featured($main_map = true, $force = false) {
	global $jeo;
	return $jeo->get_featured($main_map, $force);
}

// display map
function jeo_map($map_id = false, $main_map = true, $force = false) {
	global $jeo;
	return $jeo->get_map($map_id, $main_map, $force = false);
}

// get JSON map conf
function jeo_map_conf() {
	global $jeo;
	return $jeo->map_conf();
}

// get ARRAY map conf
function jeo_get_map_conf($map_id = false) {
	global $jeo;
	return $jeo->get_map_conf($map_id);
}

// get the map conf
function jeo_mapgroup_conf() {
	global $jeo;
	return $jeo->mapgroup_conf();
}

// get the main map id
function jeo_get_map_id() {
	global $jeo;
	return $jeo->get_id();
}

// get the main map id
function jeo_get_the_ID() {
	global $jeo;
	return $jeo->get_the_ID();
}

function jeo_get_mapgroup_data($map_id = false) {
	global $jeo;
	return $jeo->get_mapgroup_data($map_id);
}

// get the map formatted data
function jeo_get_map_data($map_id = false) {
	global $jeo;
	return $jeo->get_map_data($map_id);
}

function jeo_get_map_center($map_id = false) {
	global $jeo;
	return $jeo->get_map_center($map_id);
}

function jeo_get_map_zoom($map_id = false) {
	global $jeo;
	return $jeo->get_map_zoom($map_id);
}

function jeo_get_map_max_zoom($map_id = false) {
	global $jeo;
	return $jeo->get_map_max_zoom($map_id);
}

function jeo_get_map_legend($map_id = false) {
	global $jeo;
	return $jeo->get_map_legend($map_id);
}

function jeo_has_main_map() {
	global $jeo;
	return $jeo->has_main_map;
}

?>
