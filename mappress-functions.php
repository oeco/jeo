<?php

/*
 * Featured map
 */

function mappress_featured_map() {
	$featured_map_id = get_option('mappress_featured_map');
	if(!$featured_map_id) {
		$latest_map = get_posts(array('post_type' => array('map', 'map-group'), 'posts_per_page' => 1));
		if($latest_map) {
			$latest_map = array_shift($latest_map);
			$featured_map_id;
		} else {
			return false;
		}
	}
	mappress_map($featured_map_id);
}

/*
 * Display maps
 */

// display map

function mappress_map($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	if(!$post_id)
		return;
	if(get_post_type($post_id) == 'map-group')
		return mappress_mapgroup($post_id);
	setup_postdata(get_post($post_id));
	if(locate_template(array('content-map.php'))) {
		$post = get_post($post_id);
		setup_postdata($post);
		get_template_part('content', 'map');
		wp_reset_postdata();
	} else
		mappress_map_content($post_id);
	wp_reset_postdata();
}

function mappress_map_content($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	if(!$post_id);
		return;
	?>
	<div class="map-container"><div id="map_<?php echo $post_id; ?>" class="map"></div></div>
	<script type="text/javascript">mappress({postID: <?php echo $post_id; ?> });</script>
	<?php
}

/*
 * Map groups
 */

// display map group

function mappress_mapgroup($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	if(!$post_id)
		return;
	setup_postdata(get_post($post_id));
	get_template_part('content', 'map-group');
	wp_reset_postdata();
}

?>