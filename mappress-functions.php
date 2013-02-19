<?php

/*
 * Display maps
 */

// display map

function mappress_map($post_id = false) {
	global $post;
	$post_id = $post_id ? $post_id : $post->ID;
	if(!$post_id)
		return;
	setup_postdata(get_post($post_id));
	get_template_part('content', 'map');
	wp_reset_postdata();
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