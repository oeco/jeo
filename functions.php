<?php

require_once(TEMPLATEPATH . '/inc/admin/settings.php');
require_once(TEMPLATEPATH . '/inc/core.php');

/*
 * Theme setup
 */
function jeo_setup() {

	// text domain
	load_theme_textdomain('jeo', get_template_directory() . '/languages');

	register_nav_menus(array(
		'header_menu' => __('Header menu', 'jeo'),
		'footer_menu' => __('Footer menu', 'jeo')
	));

	//sidebars
	register_sidebar(array(
		'name' => __('Post sidebar', 'jeo'),
		'id' => 'post',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));
	register_sidebar(array(
		'name' => __('General sidebar', 'jeo'),
		'id' => 'general',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));
	register_sidebar(array(
		'name' => __('Front page', 'jeo'),
		'id' => 'front_page',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));

}
add_action('after_setup_theme', 'jeo_setup');

function jeo_theme_scripts() {
	// styles
	wp_enqueue_style('jeo-base', get_template_directory_uri() . '/css/base.css', array(), '1.2');
	wp_enqueue_style('jeo-skeleton', get_template_directory_uri() . '/css/skeleton.css', array('jeo-base'), '1.2');
	wp_enqueue_style('font-opensans', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800');
	wp_enqueue_style('jeo-main', get_template_directory_uri() . '/css/main.css', array('jeo-skeleton', 'font-opensans'), '0.0.3');

	wp_enqueue_script('jquery-isotope', get_template_directory_uri() . '/lib/jquery.isotope.min.js', array('jquery'), '1.5.25');

	wp_enqueue_script('jeo-site', get_template_directory_uri() . '/js/site.js', array('jquery', 'jquery-isotope'));
}
add_action('wp_enqueue_scripts', 'jeo_theme_scripts', 5);

?>