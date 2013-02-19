<?php

add_action('admin_footer', 'geocoding_init');
add_action('add_meta_boxes', 'geocoding_add_meta_box');
add_action('save_post', 'geocoding_save_postdata');

function geocoding_init() {
	wp_enqueue_script('google-maps-api', 'http://maps.googleapis.com/maps/api/js?key=AIzaSyAKPKeHezMTxwc8fyXpqWVBBAE5Wr5O7og&sensor=true');
	wp_enqueue_script('geocoding-metabox', get_template_directory_uri() . '/inc/metaboxes/geocode/geocode.js', array('jquery', 'google-maps-api'));
}

function geocoding_add_meta_box() {
	add_meta_box(
		'geocoding-address',
		__('Address and geolocation', 'infoamazonia'),
		'geocoding_inner_custom_box',
		'post',
		'advanced',
		'high'
	);
}

function geocoding_inner_custom_box($post) {
	$geocode_address = get_post_meta($post->ID, 'geocode_address', true);
	$geocode_latitude = get_post_meta($post->ID, 'geocode_latitude', true);
	$geocode_longitude = get_post_meta($post->ID, 'geocode_longitude', true);
	$geocode_viewport = get_post_meta($post->ID, 'geocode_viewport', true);
	?>
	<div id="geolocate">
	<h4><?php _e('Write an address', 'infoamazonia'); ?></h4>
	<p>
		<input type="text" size="80" id="geocode_address" name="geocode_address" value="<?php if($geocode_address) echo $geocode_address; ?>" />
	    <a class="button" href="#" onclick="codeAddress();return false;"><?php _e('Geolocate', 'infoamazonia'); ?></a>
	</p>
	<div class="results"></div>
	<p><?php _e('Drag the marker for a more precise result', 'infoamazonia'); ?></p>
	<div id="geolocate_canvas" style="width:500px;height:300px"></div>
	<h4><?php _e('Result', 'infoamazonia'); ?>:</h4>
	<p>
	    <?php _e('Latitude', 'infoamazonia'); ?>:
	    <input type="text" id="geocode_lat" name="geocode_latitude" value="<?php if($geocode_latitude) echo $geocode_latitude; ?>" /><br/>

	    <?php _e('Longitude', 'infoamazonia'); ?>:
	    <input type="text" id="geocode_lon" name="geocode_longitude" value="<?php if($geocode_longitude) echo $geocode_longitude; ?>" />
	</p>
	<input type="hidden" id="geocode_viewport" name="geocode_viewport" value="<?php if($geocode_viewport) echo $geocode_viewport; ?>" />
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#geolocate_canvas").geolocate();
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
	<?php
}

function geocoding_save_postdata($post_id) {
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (defined('DOING_AJAX') && DOING_AJAX)
		return;

	if (false !== wp_is_post_revision($post_id))
		return;

	if(isset($_POST['geocode_address']))
		update_post_meta($post_id, 'geocode_address', $_POST['geocode_address']);


	if(isset($_POST['geocode_latitude']))
		update_post_meta($post_id, 'geocode_latitude', $_POST['geocode_latitude']);


	if(isset($_POST['geocode_longitude']))
		update_post_meta($post_id, 'geocode_longitude', $_POST['geocode_longitude']);


	if(isset($_POST['geocode_viewport']))
		update_post_meta($post_id, 'geocode_viewport', $_POST['geocode_viewport']);

}

?>