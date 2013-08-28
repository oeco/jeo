<?php

class JEO_StreetView extends JEO_Markers {

	function __construct() {
		add_action('init', array($this, 'setup'));
	}

	function setup() {

		if(jeo_get_geocode_service() !== 'gmaps' || !jeo_get_gmaps_api_key())
			return false;

		add_action('admin_footer', array($this, 'scripts'));
		add_action('jeo_geocode_scripts', array($this, 'scripts'));
		add_action('jeo_geocode_box', array($this, 'editor'));
		add_action('jeo_geocode_box_save', array($this, 'save'));
		add_action('jeo_map', array($this, 'apply'));
	}

	function scripts() {
		wp_enqueue_script('jeo-streetview', get_template_directory_uri() . '/inc/js/streetview.js', array('google-maps-api', 'jquery', 'jeo.geocode.box'), '0.5');
	}

	function editor($post = false) {
		$pitch = 0;
		$heading = 0;
		if($post) {
			$streetview = $this->is_streetview();
			$pitch = $this->get_pitch();
			$heading = $this->get_heading();
		}
		?>
		<div id="jeo_streetview">
			<p class="streetview-toggler">
				<input type="checkbox" name="enable_streetview" id="enable_streetview" <?php if($streetview) echo 'checked'; ?> />
				<label for="enable_streetview"><?php _e('Use Google Street View', 'jeo'); ?></label>
			</p>
			<div id="streetview_canvas" style="width:60%;height:400px;float:left;"></div>
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
				new jeo.streetview({
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

$jeo_streetview = new JEO_StreetView;

function jeo_is_streetview($post_id = false) {
	global $jeo_streetview;
	return $jeo_streetview->is_streetview($post_id);
}
?>