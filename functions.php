<?php

require_once(TEMPLATEPATH . '/inc/mappress-core.php');
require_once(TEMPLATEPATH . '/inc/mappress-markers.php');
require_once(TEMPLATEPATH . '/inc/mappress-ui.php');

// Metaboxes
require_once(TEMPLATEPATH . '/metaboxes/metaboxes.php');

// API
require_once(TEMPLATEPATH . '/inc/mappress-api.php');

// Embed tool
require_once(TEMPLATEPATH . '/inc/mappress-embed.php');

// Plugins implementations and fixes
require_once(TEMPLATEPATH  . '/plugins/mappress-plugins.php');


/*
 * Theme setup
 */
function mappress_setup() {
	// register map and map group post types
	include(TEMPLATEPATH . '/inc/mappress-post-types.php');

	// text domain
	load_theme_textdomain('mappress', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'mappress_setup');

function mappress_theme_scripts() {
	// styles
	wp_enqueue_style('mappress-base', get_template_directory_uri() . '/css/base.css', array(), '1.2');
	wp_enqueue_style('mappress-skeleton', get_template_directory_uri() . '/css/skeleton.css', array('mappress-base'), '1.2');
	wp_enqueue_style('font-opensans', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800');
	wp_enqueue_style('mappress-main', get_template_directory_uri() . '/css/main.css', array('mappress-skeleton', 'font-opensans'), '0.0.1.1');
}
add_action('wp_enqueue_scripts', 'mappress_theme_scripts');

?>