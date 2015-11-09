<?php

/*
 * JEO Markers
 */

class JEO_Markers {

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
		add_action('jeo_init', array($this, 'setup'));
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
		$this->options = jeo_get_options();
		return $this->options;
	}

	function use_clustering() {
		if($this->options && isset($this->options['map']))
			$clustering = $this->options['map']['enable_clustering'];
		else
			$clustering = false;

		$this->use_clustering = apply_filters('jeo_enable_clustering', $clustering);
		return $this->use_clustering;
	}

	function use_transient() {
		return apply_filters('jeo_markers_enable_transient', true);
	}

	function use_browser_caching() {
		return apply_filters('jeo_markers_enable_browser_caching', true);
	}

	function geocode_type() {
		if($this->options && isset($this->options['geocode']))
			$type = $this->options['geocode']['type'];
		else
			$type = 'default';
		$this->geocode_type = apply_filters('jeo_geocode_type', $type);
		return $this->geocode_type;
	}

	function geocode_service() {
		if($this->options && isset($this->options['geocode']))
			$service = $this->options['geocode']['service'];
		else
			$service = 'osm';
		$this->geocode_service = apply_filters('jeo_geocode_service', $service);
		return $this->geocode_service;
	}

	function gmaps_api_key() {
		if($this->options && isset($this->options['geocode']))
			$key = $this->options['geocode']['gmaps_api_key'];
		else
			$key = false;
		$this->gmaps_api_key = apply_filters('jeo_gmaps_api_key', $key);
		return $this->gmaps_api_key;
	}

	function use_extent() {
		$this->use_extent = true;
		if(is_front_page() || is_singular(array('map', 'map-group')))
			$this->use_extent = false;

		return apply_filters('jeo_use_marker_extent', $this->use_extent);
	}

	function extent_default_zoom() {
		$this->extent_default_zoom = false;
		return apply_filters('jeo_marker_extent_default_zoom', $this->extent_default_zoom);
	}

	function setup_directories() {
		$this->directory = apply_filters('jeo_directory', get_template_directory() . '/inc');
		$this->directory_uri = apply_filters('jeo_directory_uri', get_template_directory_uri() . '/inc');
	}

	function setup_scripts() {
		add_action('jeo_enqueue_scripts', array($this, 'register_scripts'));
		add_action('wp_footer', array($this, 'enqueue_scripts'));
	}

	function enqueue_scripts() {
		if(wp_script_is('jeo.markers', 'registered')) {
			wp_enqueue_script('jeo.markers');

			wp_localize_script('jeo.markers', 'jeo_markers', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'query' => $this->query(),
				'markerextent' => $this->use_extent(),
				'markerextent_defaultzoom' => $this->extent_default_zoom(),
				'enable_clustering' => $this->use_clustering() ? true : false
			));

			do_action('jeo_markers_enqueue_scripts');
		}
	}

	function register_scripts() {

		wp_register_script('leaflet-markerclusterer', get_template_directory_uri() . '/lib/leaflet/leaflet.markercluster.js', array('jeo'), '0.2');
		wp_register_style('leaflet-markerclusterer', get_template_directory_uri() . '/lib/leaflet/MarkerCluster.Default.css', array(), '0.2');

		/*
		 * Clustering
		 */
		if($this->use_clustering()) {

			wp_enqueue_script('leaflet-markerclusterer');
			wp_enqueue_style('leaflet-markerclusterer');

			wp_localize_script('leaflet-markerclusterer', 'jeo_markerclusterer', array(
				'options' => apply_filters('jeo_markerclusterer_options', array())
			));

		}

		wp_register_script('jeo.markers', $this->directory_uri . '/js/markers.js', array('jeo', 'underscore'), '0.2.19');
	}

	function setup_query_vars() {
		add_filter('query_vars', array($this, 'query_vars'));
	}

	function query_vars($vars) {
		$vars[] = 'is_marker_query';
		return $vars;
	}

	function query() {
		global $wp_query;
		$marker_query = apply_filters('jeo_marker_base_query', $wp_query);

		$query = $marker_query->query_vars;

		if(isset($query['suppress_filters']))
			unset($query['suppress_filters']);

		if(is_singular(array('map', 'map-group'))) {
			global $post;
			$marker_query = apply_filters('jeo_marker_base_query', new WP_Query());
			$marker_query->parse_query();
			$query = $marker_query->query_vars;
			$query['map_id'] = $post->ID;
			unset($query['page_id']);
		}

		if($wp_query->get('map_id') && !$wp_query->get('p')) {
			$query['map_id'] = $wp_query->get('map_id');
		}

		if(!$query['post_type'])
			$query['post_type'] = jeo_get_mapped_post_types();

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

		return apply_filters('jeo_marker_query', $query);
	}

	function setup_post_map() {
		add_action('the_post', array($this, 'the_post_map'));
		add_action('wp_head', array($this, 'the_post_map'), 2);
	}

	function the_post_map($p = false) {
		global $post;
		$p = $p ? $p : $post;
		$map = jeo_the_map();
		if(is_single() && $this->has_location($p->ID) && !is_singular(array('map', 'map-group'))) {
			$post_maps = get_post_meta($p->ID, 'maps');
			if(!$map) {
				if($post_maps) {
					$map = get_post(array_shift($post_maps));
				} else {
					$map = jeo_map_featured();
				}
				jeo_set_map($map);
			}
		}
		return jeo_the_map();
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

		$query['is_marker_query'] = 1;

		$cache_key = 'mp_';

		if(function_exists('qtrans_getLanguage'))
			$cache_key .= qtrans_getLanguage() . '_';

		$query_id = md5(serialize($query));
		$cache_key .= $query_id;

		$cache_key = apply_filters('jeo_markers_cache_key', $cache_key, $query);

		$data = false;

		if($this->use_transient())
			$data = get_transient($cache_key, 'jeo_markers_query');

		if($data === false) {
			$data = array();

			$markers_query = new WP_Query($query);

			$data['type'] = 'FeatureCollection';
			$data['features'] = array();

			if($markers_query->have_posts()) {
				$i = 0;
				while($markers_query->have_posts()) {

					$markers_query->the_post();

					$geojson = $this->get_geojson();

					if($geojson) {
						$data['features'][$i] = $geojson;
						$i++;
					}
				}
			}
			wp_reset_postdata();
			$data = apply_filters('jeo_markers_data', $data, $markers_query);
			$data = json_encode($data);

			if($this->use_transient())
				set_transient($cache_key, $data, 60*10); // 10 minutes transient
		}

		$content_type = apply_filters('jeo_geojson_content_type', 'application/json');

		header('Content-Type: ' . $content_type . ';charset=UTF-8');

		if($this->use_browser_caching()) {
			/* Browser caching */
			$expires = 60 * 10; // 10 minutes of browser cache
			header('Pragma: public');
			header('Cache-Control: maxage=' . $expires);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
			/* --------------- */
		}

		do_action('jeo_markers_before_print');

		echo apply_filters('jeo_markers_geojson', $data);

		do_action('jeo_markers_after_print');
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
		add_action('jeo_enqueue_scripts', array($this, 'geocode_register_scripts'));
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

		wp_register_script('jeo.geocode.box', $this->directory_uri . '/js/geocode.box.js', $dependencies, '0.5.3');

		wp_localize_script('jeo.geocode.box', 'geocode_localization', array(
			'type' => $this->geocode_type,
			'service' => $this->geocode_service,
			'not_found' => __('We couldn\'t find what you are looking for, please try again.', 'jeo'),
			'results_found' => __('results found', 'jeo')
		));

		do_action('jeo_geocode_scripts');
	}

	function geocode_enqueue_scripts() {
		if($this->geocode_service == 'gmaps' && $this->gmaps_api_key)
			wp_enqueue_script('google-maps-api');
		wp_enqueue_script('jeo.geocode.box');
	}

	function geocode_add_meta_box() {
		$post_types = jeo_get_mapped_post_types();
		foreach($post_types as $post_type) {
			add_meta_box(
				'geocoding-address',
				__('Address and geolocation', 'jeo'),
				array($this, 'geocode_box'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	function geocode_box($post = false) {

		wp_enqueue_script('jeo.geocode.box');

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
			<h4><?php _e('Find the location', 'jeo'); ?></h4>
			<p class="clearfix">
				<input type="text" size="80" id="geocode_address" name="geocode_address" placeholder="<?php _e('Full address', 'jeo'); ?>" value="<?php if($geocode_address) echo $geocode_address; ?>" />
				<a class="button geocode_address secondary" href="#"><?php _e('Find', 'jeo'); ?></a>
			</p>
			<div class="geocode-map-container">
				<div class="results"></div>
				<?php if($this->geocode_service == 'gmaps' && $this->gmaps_api_key) : ?>
					<p class="draggable-tip"><?php _e('Drag the marker for a more precise result', 'jeo'); ?></p>
				<?php endif; ?>
				<div id="map_canvas" style="width:500px;height:300px"></div>
				<div class="latlng-container">
					<h4><?php _e('Result', 'jeo'); ?>:</h4>
					<p>
						<?php _e('Latitude', 'jeo'); ?>:
						<input type="text" id="geocode_lat" name="geocode_latitude" value="<?php if($geocode_latitude) echo $geocode_latitude; ?>" /><br/>

						<?php _e('Longitude', 'jeo'); ?>:
						<input type="text" id="geocode_lon" name="geocode_longitude" value="<?php if($geocode_longitude) echo $geocode_longitude; ?>" />
					</p>
					<input type="hidden" id="geocode_city" name="geocode_city" value="<?php if($geocode_city) echo $geocode_city; ?>" />
					<input type="hidden" id="geocode_country" name="geocode_country" value="<?php if($geocode_country) echo $geocode_country; ?>" />
					<input type="hidden" id="geocode_viewport" name="geocode_viewport" value="<?php if($geocode_viewport) echo $geocode_viewport; ?>" />
				</div>
			</div>
			<?php do_action('jeo_geocode_box', $post); ?>
		</div>
		<?php if(is_admin()) : ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					<?php if($this->geocode_service == 'gmaps') : ?>
						streetviewBox({ geocoder: geocodeBox() });
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

		do_action('jeo_geocode_box_save', $post_id);
	}

	/*
	 * Functions
	 */

	function get_limit() {
		return apply_filters('jeo_markers_limit', 200);
	}

	function get_bubble($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		ob_start();
		get_template_part('content', 'marker-bubble');
		$bubble = ob_get_contents();
		ob_end_clean();
		return apply_filters('jeo_marker_bubble', $bubble, $post);
	}

	function get_icon() {
		global $post;
		if($this->has_location()) {
			$marker = array(
				'iconUrl' => get_template_directory_uri() . '/img/marker.png',
				'iconSize' => array(26, 30),
				'iconAnchor' => array(13, 30),
				'popupAnchor' => array(0, -40),
				'markerId' => 'none'
			);
			return apply_filters('jeo_marker_icon', $marker, $post);
		}
		return null;
	}

	function get_class() {
		global $post;
		$class = get_post_class();
		return apply_filters('jeo_marker_class', $class, $post);
	}

	function get_properties() {
		global $post;
		$properties = array();
		$properties['id'] = 'post-' . $post->ID;
		$properties['postID'] = $post->ID;
		$properties['title'] = get_the_title();
		$properties['date'] = get_the_date(_x('m/d/Y', 'reduced date format', 'jeo'));
		$properties['url'] = apply_filters('the_permalink', get_permalink());
		$properties['bubble'] = $this->get_bubble();
		$properties['marker'] = $this->get_icon();
		$properties['class'] = implode(' ', $this->get_class());
		return apply_filters('jeo_marker_data', $properties, $post);
	}

	function get_geometry() {
		global $post;
		$coordinates = $this->get_coordinates();
		if(!$coordinates) {
			$geometry = false;
		} else {
			$geometry = array();
			$geometry['type'] = 'Point';
			$geometry['coordinates'] = array_map('floatval', $coordinates);
		}
		return apply_filters('jeo_marker_geometry', $geometry, $post);
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

		if($lat && is_numeric($lat) && $lon && is_numeric($lon))
			$coordinates = array(floatval($lon), floatval($lat));
		else
			$coordinates = false;

		return apply_filters('jeo_marker_coordinates', $coordinates, $post);
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
		return ($this->get_coordinates($post_id));
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
		return apply_filters('jeo_markers_geojson_key', '_mp_geojson');
	}

	function get_geojson_keys() {
		return apply_filters('jeo_markers_geojson_keys', array('_mp_geojson'));
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

		$geometry = $this->get_geometry();

		/*
		if(!$geometry) {
			$this->clean_geojson($post_id);
			return false;
		}
		*/

		$geojson = array();

		$geojson['type'] = 'Feature';

		// marker geometry
		if($geometry) {
			$geojson['geometry'] = $geometry;
		}

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

		if(is_int($post_id) && in_array(get_post_type($post_id), jeo_get_mapped_post_types())) {
			foreach($keys as $key) {
				delete_post_meta($post_id, $key);
			}
		} else {
			global $wpdb;
			foreach($keys as $key) {
				$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = '{$key}'", null));
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
			'title' => __('Clear GeoJSON Cache', 'jeo'),
			'href' => add_query_arg(array('jeo_clear_geojson' => 1))
		));
	}

	function geojson_cache_button_action() {
		if(isset($_REQUEST['jeo_clear_geojson']) && is_super_admin()) {
			$this->clean_geojson();
			add_action('admin_notices', array($this, 'geojson_cache_clean_message'));
		}
	}

	function geojson_cache_clean_message() {
		echo '<div class="updated fade"><p>' . __('<strong>Markers GeoJSON cache has been cleared. Don\'t worry! They will be dynamically regenerated.</strong>', 'jeo') . '</p><p>' . __('The next map markers load might take a little while, depending on the amount of markers. If you want to speed this up for your users, we recommend you clear your browser\'s cache and navigate through your website to let the markers cache be replaced.', 'jeo') . '</p></div>';
	}


}

$jeo_markers = new JEO_Markers();

require_once($jeo_markers->directory . '/streetview.php');
require_once($jeo_markers->directory . '/marker-icons.php');

function jeo_geocode_box($post = false) {
	global $jeo_markers;
	return $jeo_markers->geocode_box($post);
}

function jeo_geocode_save($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->geocode_save($post_id);
}

function jeo_get_geocode_service() {
	global $jeo_markers;
	return $jeo_markers->geocode_service;
}

function jeo_get_gmaps_api_key() {
	global $jeo_markers;
	return $jeo_markers->gmaps_api_key;
}

function jeo_use_clustering() {
	global $jeo_markers;
	return $jeo_markers->use_clustering();
}

function jeo_use_marker_extent() {
	global $jeo_markers;
	return $jeo_markers->use_extent();
}

function jeo_marker_extent_default_zoom() {
	global $jeo_markers;
	return $jeo_markers->extent_default_zoom();
}

function jeo_get_markers_limit() {
	global $jeo_markers;
	return $jeo_markers->get_limit();
}

function jeo_get_marker_latitude($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_latitude($post_id);
}

function jeo_get_marker_longitude($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_longitude($post_id);
}

function jeo_get_marker_bubble($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_bubble();
}

function jeo_get_marker_icon() {
	global $jeo_markers;
	return $jeo_markers->get_icon();
}

function jeo_get_marker_class() {
	global $jeo_markers;
	return $jeo_markers->get_class();
}

function jeo_get_marker_properties() {
	global $jeo_markers;
	return $jeo_markers->get_properties();
}

function jeo_get_marker_geometry() {
	global $jeo_markers;
	return $jeo_markers->get_geometry();
}

function jeo_get_marker_coordinates($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_coordinates($post_id);
}

function jeo_get_marker_conf_coordinates($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_conf_coordinates($post_id);
}

function jeo_has_marker_location($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->has_location($post_id);
}

function jeo_get_post_geojson($post_id = false) {
	global $jeo_markers;
	return $jeo_markers->get_geojson($post_id);
}
