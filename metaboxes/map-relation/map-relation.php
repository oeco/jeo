<?php

add_action('add_meta_boxes', 'map_relation_add_meta_box');
add_action('save_post', 'map_relation_save_postdata');

function map_relation_add_meta_box() {
	add_meta_box(
		'map_relation',
		__('Set maps for this post', 'infoamazonia'),
		'map_relation_inner_custom_box',
		'post',
		'advanced',
		'high'
	);
}

function map_relation_inner_custom_box($post) {
	$post_maps = get_post_meta($post->ID, 'maps');
	if(!$post_maps)
		$post_maps = array();
	?>
	<div id="featured-metabox">
		<h4><?php _e('Select which maps this post belongs to. If you don\'t mark any they will appear in all maps.', 'infoamazonia'); ?></h4>
		<?php $maps = get_posts(array('post_type' => 'map', 'posts_per_page' => -1)); ?>
		<?php if($maps) : ?>
			<ul>
				<?php foreach($maps as $map) : ?>
					<li><input type="checkbox" name="post_maps[]" value="<?php echo $map->ID; ?>" id="post_map_<?php echo $map->ID; ?>" <?php if(in_array($map->ID, $post_maps)) echo 'checked'; ?> /> <label for="post_map_<?php echo $map->ID; ?>"><?php echo $map->post_title; ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php _e('You haven\'t created any map, yet!', 'infoamazonia'); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

function map_relation_save_postdata($post_id) {
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (defined('DOING_AJAX') && DOING_AJAX)
		return;

	if (false !== wp_is_post_revision($post_id))
		return;

	delete_post_meta($post_id, 'maps');
	if(isset($_POST['post_maps'])) {
		update_post_meta($post_id, 'has_maps', 1);
		foreach($_POST['post_maps'] as $map) {
			add_post_meta($post_id, 'maps', $map);
		}
	} else {
		delete_post_meta($post_id, 'has_maps');
	}

}

?>