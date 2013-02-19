<?php

add_action('admin_footer', 'mapgroup_init');
add_action('add_meta_boxes', 'mapgroup_add_meta_box');
add_action('save_post', 'mapgroup_save_postdata');

function mapgroup_init() {
	wp_enqueue_script('mapgroup-metabox', get_template_directory_uri() . '/inc/metaboxes/mapgroup/mapgroup.js', array('jquery', 'underscore', 'jquery-ui-sortable'), '0.0.2');
	wp_localize_script('mapgroup-metabox', 'mapgroup_metabox_localization', array(
		'map_item' => '
				<li class="map-item">
					<strong class="title"></strong>
					<input type="hidden" class="map_id" />
					<input type="hidden" class="map_title" />
					<input type="checkbox" class="more_list" value="1" /> ' . __('Add to "more" tab', 'infoamazonia') . '
					<span class="map-actions">
						<span class="sort"></span>
						<a href="#" class="remove-map button">' . __('Remove', 'infoamazonia') . '</a>
					</span>
				</li>'
		)
	);

	wp_enqueue_style('mapgroup-metabox', get_template_directory_uri() . '/inc/metaboxes/mapgroup/mapgroup.css', array(), '0.0.1');
}

function mapgroup_add_meta_box() {
	add_meta_box(
		'mapgroup',
		__('Map group', 'infoamazonia'),
		'mapgroup_inner_custom_box',
		'map-group',
		'advanced',
		'high'
	);
}

function mapgroup_inner_custom_box($post) {
	$data = get_post_meta($post->ID, 'mapgroup_data', true);
	$maps = false;
	if(isset($data['maps']))
		$maps = $data['maps'];
	?>
	<div id="mapgroup-metabox">
		<h4><?php _e('Select a map to add to your map group', 'infoamazonia'); ?></h4>
		<select id="mapgroup_maps">
			<?php
			$all_maps = get_posts('post_type=map&posts_per_page=-1');
			foreach($all_maps as $map) {
				?><option value="<?php echo $map->ID; ?>"><?php _e($map->post_title); ?></option><?php
			}
			?>
		</select>
		<a href="#" class="include-map button"><?php _e('Add map', 'infoamazonia'); ?></a>
		<ol class="map-list">
			<?php
			if($maps) { $i = 0;
				foreach($maps as $map) { ?>
					<li class="map-item map-<?php echo $map['id']; ?>">
						<strong class="title"><?php echo $map['title']; ?></strong>
						<input type="hidden" class="map_id" name="mapgroup_data[maps][<?php echo $i; ?>][id]" value="<?php echo $map['id']; ?>" />
						<input type="hidden" class="map_title" name="mapgroup_data[maps][<?php echo $i; ?>][title]" value="<?php echo $map['title']; ?>" />
						<input type="checkbox" class="more_list" name="mapgroup_data[maps][<?php echo $i; ?>][more]" value="1" <?php if(isset($map['more'])) echo 'checked'; ?>  />
						<?php _e('Add to "more" tab', 'infoamazonia'); ?>
						<span class="map-actions">
							<span class="sort"></span>
							<a href="#" class="remove-map button"><?php _e('Remove', 'infoamazonia'); ?></a>
						</span>
					</li> 
				<?php
				$i++;
				}
			} ?>
		</ol>
	</div>
	<?php
}

function mapgroup_save_postdata($post_id) {
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (defined('DOING_AJAX') && DOING_AJAX)
		return;

	if (false !== wp_is_post_revision($post_id))
		return;

	if(isset($_POST['mapgroup_data']))
		update_post_meta($post_id, 'mapgroup_data', $_POST['mapgroup_data']);

}

?>