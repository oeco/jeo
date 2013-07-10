<?php

/*
 * MapPress Markers
 */

class MapPress_Markers {

	var $directory = '';

	var $directory_uri = '';

	var $options = array();

	var $use_clustering = false;

	var $geocode_service = '';

	var $geocode_type = 'default';

	var $gmaps_api_key = false;

	var $use_extent = false;

	var $extent_default_zoom = false;

	function __construct() {
		$this->setup_directories();
		add_action('mappress_init', array($this, 'setup'));
	}

	/*
	 * Settings
	 */

	function setup() {
		$this->setup_scripts();
		$this->setup_post_map();
		$this->setup_ajax();
		$this->setup_cache_flush();
		$this->geocode_setup();
		$this->get_options();
		$this->use_clustering();
		$this->geocode_type();
		$this->geocode_service();
		$this->gmaps_api_key();
		$this->use_extent();
		$this->geojson_cache_hooks();
	}

	function get_options() {
		$this->options = mappress_get_options();
		return $this->options;
	}

	function use_clustering() {
		if($this->options && isset($this->options['map']))
			$clustering = $this->options['map']['enable_clustering'];
		else
			$clustering = false;

		$this->use_clustering = apply_filters('mappress_enable_clustering', $clustering);
		return $this->use_clustering;
	}

	function geocode_type() {
		if($this->options && isset($this->options['geocode']))
			$type = $this->options['geocode']['type'];
		else
			$type = 'default';
		$this->geocode_type = apply_filters('mappress_geocode_type', $type);
		return $this->geocode_type;
	}

	function geocode_service() {
		if($this->options && isset($this->options['geocode']))
			$service = $this->options['geocode']['service'];
		else
			$service = 'osm';
		$this->geocode_service = apply_filters('mappress_geocode_service', $service);
		return $this->geocode_service;
	}

	function gmaps_api_key() {
		if($this->options && isset($this->options['geocode']))
			$key = $this->options['geocode']['gmaps_api_key'];
		else
			$key = false;
		$this->gmaps_api_key = apply_filters('mappress_gmaps_api_key', $key);
		return $this->gmaps_api_key;
	}

	function use_extent() {
		$this->use_extent = true;
		if(is_front_page() || is_singular(array('map', 'map-group')))
			$this->use_extent = false;

		return apply_filters('mappress_use_marker_extent', $this->use_extent);
	}

	function extent_default_zoom() {
		$this->extent_default_zoom = false;
		return apply_filters('mappress_marker_extent_default_zoom', $this->extent_default_zoom);
	}

	function setup_directories() {
		$this->directory = apply_filters('mappress_directory', get_template_directory() . '/inc');
		$this->directory_uri = apply_filters('mappress_directory_uri', get_template_directory_uri() . '/inc');
	}

	function setup_scripts() {
		add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
		add_action('wp_footer', array($this, 'enqueue_scripts'));
	}

	function enqueue_scripts() {
		if(wp_script_is('mappress.markers', 'registered')) {
			wp_enqueue_script('mappress.markers');
		}
	}

	function register_scripts() {

		/* 
		 * Clustering
		 */
		if($this->use_clustering()) {

			wp_enqueue_script('leaflet-markerclusterer', get_template_directory_uri() . '/lib/leaflet/leaflet.markercluster.js', array('mappress'));
			wp_enqueue_style('leaflet-markerclusterer', get_template_directory_uri() . '/lib/leaflet/MarkerCluster.Default.css');

		}

		wp_register_script('mappress.markers', $this->directory_uri . '/js/markers.js', array('mappress', 'underscore'), '0.2.8');

		wp_localize_script('mappress.markers', 'mappress_markers', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'query' => $this->query(),
			'markerextent' => $this->use_extent(),
			'markerextent_defaultzoom' => $this->extent_default_zoom(),
			'enable_clustering' => $this->use_clustering() ? true : false
		));
	}

	function query() {
		global $wp_query;
		$marker_query = $wp_query;

		$query = $marker_query->query_vars;

		if(isset($query['suppress_filters']))
			unset($query['suppress_filters']);

		if(is_singular(array('map', 'map-group'))) {
			global $post;
			$marker_query = new WP_Query();
			$marker_query->parse_query();
			$query = $marker_query->query_vars;
			$query['map_id'] = $post->ID;
		}

		if($wp_query->get('map_id') && !$wp_query->get('p')) {
			$query['map_id'] = $wp_query->get('map_id');
		}

		if(!$query['post_type'])
			$query['post_type'] = mappress_get_mapped_post_types();

		$query['post_status'] = 'publish';

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

	function setup_post_map() {
		add_action('the_post', array($this, 'the_post_map'));
		add_action('wp_head', array($this, 'the_post_map'), 2);
	}

	function the_post_map($p = false) {
		global $post;
		$p = $p ? $p : $post;
		if(is_single() && $this->has_location($p->ID) && !is_singular(array('map', 'map-group'))) {
			$post_maps = get_post_meta($p->ID, 'maps');
			if(!$post_maps) {
				mappress_set_map(mappress_map_featured());
			} else {
				mappress_set_map(get_post(array_shift($post_maps)));
			}
		}
		return mappress_the_map();
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

		//$data = get_transient($cache_key, 'mappress_markers_query');
		$data = false;

		if($data === false) {
			$data = array();

			$markers_query = new WP_Query($query);

			$data['query_id'] = $cache_key;

			if($markers_query->have_posts()) {
				$data['type'] = 'FeatureCollection';
				$data['features'] = array();
				$i = 0;
				while($markers_query->have_posts()) {

					$markers_query->the_post();

					$data['features'][$i] = $this->get_geojson();

					$i++;
				}
			}
			wp_reset_postdata();
			$data = apply_filters('mappress_markers_data', $data);
			$data = json_encode($data);
			//set_transient($cache_key, $data, 60*10); // 10 minutes transient
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

	/*
	 * Geocode tool
	 */

	function geocode_setup() {
		add_action('wp_enqueue_scripts', array($this, 'geocode_register_scripts'));
		add_action('admin_footer', array($this, 'geocode_register_scripts'));
		add_action('admin_footer', array($this, 'geocode_enqueue_scripts'));
		add_action('add_meta_boxes', array($this, 'geocode_add_meta_box'));
		add_action('save_post', array($this, 'geocode_save'));
	}

	function geocode_register_scripts() {

		$dependencies = array('jquery');

		if($this->geocode_service == 'gmaps' && $this->gmaps_api_key) {
			wp_register_script('google-maps-api', 'http://maps.googleapis.com/maps/api/js?v=3&key=' . $this->gmaps_api_key . '&sensor=true');
			$dependencies[] = 'google-maps-api';
		}

		wp_register_script('mappress.geocode.box', $this->directory_uri . '/js/geocode.box.js', $dependencies, '0.4');

		wp_localize_script('mappress.geocode.box', 'geocode_localization', array(
			'type' => $this->geocode_type,
			'service' => $this->geocode_service,
			'not_found' => __('We couldn\'t find what you are looking for, please try again.', 'mappress'),
			'results_found' => __('results found', 'mappress')
		));

		do_action('mappress_geocode_scripts');
	}

	function geocode_enqueue_scripts() {
		if($this->geocode_service == 'gmaps' && $this->gmaps_api_key)
			wp_enqueue_script('google-maps-api');
		wp_enqueue_script('mappress.geocode.box');
	}

	function geocode_add_meta_box() {
		$post_types = mappress_get_mapped_post_types();
		foreach($post_types as $post_type) {
			add_meta_box(
				'geocoding-address',
				__('Address and geolocation', 'mappress'),
				array($this, 'geocode_box'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	function geocode_box($post = false) {

		wp_enqueue_script('mappress.geocode.box');

		$geocode_latitude = '';
		$geocode_longitude = '';
		$geocode_city = '';
		$geocode_country = '';
		$geocode_address = '';
		$geocode_viewport = '';

		if($post) {
			$geocode_latitude = $this->get_latitude();
			$geocode_longitude = $this->get_longitude();
			$geocode_city = $this->get_city();
			$geocode_country = $this->get_country();
			$geocode_address = get_post_meta($post->ID, 'geocode_address', true);
			$geocode_viewport = get_post_meta($post->ID, 'geocode_viewport', true);
		}

		?>
		<div id="geocode_box" class="clearfix">
			<h4><?php _e('Find the location', 'mappress'); ?></h4>
			<p class="clearfix">
				<input type="text" size="80" id="geocode_address" name="geocode_address" placeholder="<?php _e('Full address', 'mappress'); ?>" value="<?php if($geocode_address) echo $geocode_address; ?>" />
				<a class="button geocode_address secondary" href="#"><?php _e('Find', 'mappress'); ?></a>
			</p>
			<div class="geocode-map-container">
				<div class="results"></div>
				<?php if($this->geocode_service == 'gmaps' && $this->gmaps_api_key) : ?>
					<p class="draggable-tip"><?php _e('Drag the marker for a more precise result', 'mappress'); ?></p>
				<?php endif; ?>
				<div id="map_canvas" style="width:500px;height:300px"></div>
				<div class="latlng-container">
					<h4><?php _e('Result', 'mappress'); ?>:</h4>
					<p>
						<?php _e('Latitude', 'mappress'); ?>:
						<input type="text" id="geocode_lat" name="geocode_latitude" value="<?php if($geocode_latitude) echo $geocode_latitude; ?>" /><br/>

						<?php _e('Longitude', 'mappress'); ?>:
						<input type="text" id="geocode_lon" name="geocode_longitude" value="<?php if($geocode_longitude) echo $geocode_longitude; ?>" />
					</p>
					<input type="hidden" id="geocode_city" name="geocode_city" value="<?php if($geocode_city) echo $geocode_city; ?>" />
					<input type="hidden" id="geocode_country" name="geocode_country" value="<?php if($geocode_country) echo $geocode_country; ?>" />
					<input type="hidden" id="geocode_viewport" name="geocode_viewport" value="<?php if($geocode_viewport) echo $geocode_viewport; ?>" />
				</div>
			</div>
			<?php do_action('mappress_geocode_box', $post); ?>
		</div>
		<?php if(is_admin()) : ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					<?php if($this->geocode_service == 'gmaps') : ?>
						streetviewBox({geocoder: geocodeBox() });
					<?php else : ?>
						geocodeBox();
					<?php endif; ?>
				});
			</script>
			<style>
				#geocoding-address .results ul li {
					cursor: pointer;
					text-decoration: underline;
				}
				#geocoding-address .results ul li.active {
					cursor: default;
					text-decoration: none;
				}
			</style>
		<?php endif; ?>
		<?php
	}

	function geocode_save($post_id) {
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (false !== wp_is_post_revision($post_id))
			return;

		if(isset($_REQUEST['geocode_address']))
			update_post_meta($post_id, 'geocode_address', $_REQUEST['geocode_address']);

		if(isset($_REQUEST['geocode_latitude']))
			update_post_meta($post_id, 'geocode_latitude', $_REQUEST['geocode_latitude']);

		if(isset($_REQUEST['geocode_longitude']))
			update_post_meta($post_id, 'geocode_longitude', $_REQUEST['geocode_longitude']);

		if(isset($_REQUEST['geocode_city']))
			update_post_meta($post_id, '_geocode_city', $_REQUEST['geocode_city']);

		if(isset($_REQUEST['geocode_country']))
			update_post_meta($post_id, '_geocode_country', $_REQUEST['geocode_country']);

		if(isset($_REQUEST['geocode_viewport']))
			update_post_meta($post_id, 'geocode_viewport', $_REQUEST['geocode_viewport']);

		do_action('mappress_geocode_box_save', $post_id);
	}

	/*
	 * Functions
	 */

	function get_limit() {
		return apply_filters('mappress_markers_limit', 200);
	}

	function get_bubble($post_id = false) {
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
		$properties['url'] = apply_filters('the_permalink', get_permalink());
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

	function get_conf_coordinates($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$coordinates = $this->get_coordinates($post_id);
		return array('lat' => $coordinates[1], 'lon' => $coordinates[0]);
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

	function get_latitude($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, 'geocode_latitude', true);
	}

	function get_longitude($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, 'geocode_longitude', true);
	}

	function has_location($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		$coordinates = $this->get_coordinates($post_id);
		if($coordinates[0] !== 0)
			return true;
		return false;
	}

	function get_city($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, '_geocode_city', true);
	}

	function get_country($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, '_geocode_country', true);
	}

	function get_geojson_key() {
		return apply_filters('mappress_markers_geojson_key', '_mp_geojson');
	}

	function get_geojson_keys() {
		return apply_filters('mappress_markers_geojson_keys', array('_mp_geojson'));
	}

	function get_geojson($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$geojson = get_post_meta($post_id, $this->get_geojson_key(), true);

		if(!$geojson)
			return $this->update_geojson($post_id);

		return $geojson;
	}

	function update_geojson($post_id = false) {
		if(!$post_id)
			return false;

		global $post;
		setup_postdata(get_post($post_id));

		$geojson = array();

		$geojson['type'] = 'Feature';

		// marker geometry
		$geojson['geometry'] = $this->get_geometry();

		// marker properties
		$geojson['properties'] = $this->get_properties();

		update_post_meta($post_id, $this->get_geojson_key(), $geojson);

		wp_reset_postdata();

		return $geojson;
	}

	function clean_geojson($post_id = false) {

		$keys = $this->get_geojson_keys();

		if(is_int($post_id) && get_post_type($post_id) == 'revision')
			return false;

		if(is_int($post_id) && in_array(get_post_type($post_id), mappress_get_mapped_post_types())) {
			foreach($keys as $key) {
				delete_post_meta($post_id, $key);
			}
		} else {
			global $wpdb;
			foreach($keys as $key) {
				$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = '$key'"));
			}
		}

	}

	function geojson_cache_hooks() {
		add_action('save_post', array($this, 'clean_geojson'));
		add_action('create_term', array($this, 'clean_geojson'));
		add_action('delete_term', array($this, 'clean_geojson'));
		add_action('edit_term', array($this, 'clean_geojson'));

		// buttons
		add_action('admin_bar_menu', array($this, 'geojson_cache_button'), 200);
		$this->geojson_cache_button_action();
	}

	function geojson_cache_button() {
		global $wp_admin_bar;

		if ( !is_super_admin() || !is_admin_bar_showing() )
			return;

		$wp_admin_bar->add_menu( array(
			'id' => 'mp_geojson_clean',
			'title' => __('Clear GeoJSON Cache', 'mappress'),
			'href' => add_query_arg(array('mappress_clear_geojson' => 1))
		));
	}

	function geojson_cache_button_action() {
		if(isset($_REQUEST['mappress_clear_geojson']) && is_super_admin()) {
			$this->clean_geojson();
			add_action('admin_notices', array($this, 'geojson_cache_clean_message'));
		}
	}

	function geojson_cache_clean_message() {
		echo '<div class="updated fade"><p>' . __('<strong>Markers GeoJSON cache has been cleared. Don\'t worry! They will be dynamically regenerated.</strong>', 'mappress') . '</p><p>' . __('The next map markers load might take a little while, depending on the amount of markers. If you want to speed this up for your users, we recommend you clear your browser\'s cache and navigate through your website to let the markers cache be replaced.', 'mappress') . '</p></div>';
	}


}

$mappress_markers = new MapPress_Markers();

require_once($mappress_markers->directory . '/streetview.php');
require_once($mappress_markers->directory . '/marker-icons.php');

function mappress_geocode_box($post = false) {
	global $mappress_markers;
	return $mappress_markers->geocode_box($post);
}

function mappress_geocode_save($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->geocode_save($post_id);
}

function mappress_get_geocode_service() {
	global $mappress_markers;
	return $mappress_markers->geocode_service;
}

function mappress_get_gmaps_api_key() {
	global $mappress_markers;
	return $mappress_markers->gmaps_api_key;
}

function mappress_use_clustering() {
	global $mappress_markers;
	return $mappress_markers->use_clustering();
}

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

function mappress_get_marker_latitude($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->get_latitude($post_id);
}

function mappress_get_marker_longitude($post_id = false) {
	global $mappress_markers;
	return $mappress_markers->get_longitude($post_id);
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