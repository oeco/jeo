<?php

class MapPress_StreetView extends MapPress_Markers {

	function __construct() {

		if($this->geocode_service() !== 'gmaps' && !$this->gmaps_api_key())
			return false;

		$this->setup();
	}

	function setup() {
		add_action('admin_footer', array($this, 'scripts'));
		add_action('wp_enqueue_scripts', array($this, 'scripts'));
		add_action('mappress_geocode_box', array($this, 'editor'));
		add_action('mappress_geocode_box_save', array($this, 'save'));
		add_action('mappress_map', array($this, 'apply'));
	}

	function scripts() {
		wp_enqueue_script('mappress-streetview', get_template_directory_uri() . '/inc/js/streetview.js', array('google-maps-api', 'jquery'), '0.0.7');
	}

	function editor($post) {
		$streetview = $this->is_streetview();
		$pitch = $this->get_pitch();
		$heading = $this->get_heading();
		if(!$pitch)
			$pitch = 0;
		if(!$heading)
			$heading = 0;
		?>
		<div id="mappress_streetview" class="clearfix">
			<p><input type="checkbox" name="enable_streetview" id="enable_streetview" <?php if($streetview) echo 'checked'; ?> /> <label for="enable_streetview"><?php _e('Use Google Street View', 'mappress'); ?></label></p>
			<div id="streetview_canvas" style="width:60%;height:400px;float:left;">
			</div>
			<input type="hidden" name="streetview_pitch" id="streetview_pitch" value="<?php echo $pitch; ?>" />
			<input type="hidden" name="streetview_heading" id="streetview_heading" value="<?php echo $heading; ?>" />
		</div>
		<?php
	}

	function save($post_id) {
		if(isset($_POST['enable_streetview']))
			update_post_meta($post_id, '_streetview', $_POST['enable_streetview']);
		else
			delete_post_meta($post_id, '_streetview');

		if(isset($_POST['streetview_pitch']))
			update_post_meta($post_id, '_streetview_pitch', $_POST['streetview_pitch']);

		if(isset($_POST['streetview_heading']))
			update_post_meta($post_id, '_streetview_heading', $_POST['streetview_heading']);
	}

	function apply() {
		global $post;
		if(is_single() && $this->is_streetview()) :
			?>
			<div id="streetview_canvas" class="streetview">
			</div>
			<script type="text/javascript">
				mappress.streetview({
					containerID: 'streetview_canvas',
					lat: <?php echo $this->get_latitude(); ?>,
					lng: <?php echo $this->get_longitude(); ?>,
					pitch: <?php echo $this->get_pitch(); ?>,
					heading: <?php echo $this->get_heading(); ?>
				});
			</script>
			<?php
			return true;
		endif;
	}

	function is_streetview($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, '_streetview', true);
	}

	function get_pitch($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, '_streetview_pitch', true);
	}

	function get_heading($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		return get_post_meta($post_id, '_streetview_heading', true);
	}
}

new MapPress_StreetView;
?>