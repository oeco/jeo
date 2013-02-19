<?php

add_action('admin_footer', 'mapbox_legend_init');
add_action('add_meta_boxes', 'mapbox_legend_add_meta_box');
add_action('save_post', 'mapbox_legend_save_postdata');

function mapbox_legend_init() {
}

function mapbox_legend_add_meta_box() {
	add_meta_box(
		'mapbox_legend',
		__('Map legend', 'infoamazonia'),
		'mapbox_legend_inner_custom_box',
		'map',
		'side',
		'default'
	);
}

function mapbox_legend_inner_custom_box($post) {
	$legend = get_post_meta($post->ID, 'legend', true);
	?>
	<div id="mapbox-legend-metabox">
		<h4><?php _e('Enter your HTML code to use as legend on the map', 'infoamazonia'); ?></h4>
		<textarea style="width:100%;height:100px;" name="mapbox_legend" id="mapbox_legend_textarea"><?php if($legend) echo $legend; ?></textarea>
	</div>
	<?php
}

function mapbox_legend_save_postdata($post_id) {
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (defined('DOING_AJAX') && DOING_AJAX)
		return;

	if (false !== wp_is_post_revision($post_id))
		return;

	if(isset($_POST['mapbox_legend']))
		update_post_meta($post_id, 'legend', $_POST['mapbox_legend']);

}

?>