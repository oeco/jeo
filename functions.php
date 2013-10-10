<?php

require_once(TEMPLATEPATH . '/inc/admin/settings.php');
require_once(TEMPLATEPATH . '/inc/core.php');
require_once(TEMPLATEPATH . '/inc/share-widget.php');

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
	wp_register_style('jeo-lsf', get_template_directory_uri() . '/css/lsf.css');
	wp_register_style('jeo-base', get_template_directory_uri() . '/css/base.css', array(), '1.2');
	wp_register_style('jeo-skeleton', get_template_directory_uri() . '/css/skeleton.css', array('jeo-base'), '1.2');
	wp_register_style('font-opensans', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800');
	wp_register_style('jeo-main', get_template_directory_uri() . '/css/main.css', array('jeo-skeleton', 'jeo-lsf', 'font-opensans'), '0.0.3');

	wp_register_script('jquery-isotope', get_template_directory_uri() . '/lib/jquery.isotope.min.js', array('jquery'), '1.5.25');

	wp_register_script('jeo-site', get_template_directory_uri() . '/js/site.js', array('jquery', 'jquery-isotope'));
}
add_action('wp_enqueue_scripts', 'jeo_theme_scripts', 5);

function jeo_enqueue_theme_scripts() {
	if(wp_style_is('jeo-main', 'registered'))
		wp_enqueue_style('jeo-main');

	if(wp_script_is('jeo-site', 'registered'))
		wp_enqueue_script('jeo-site');
}
add_action('wp_enqueue_scripts', 'jeo_enqueue_theme_scripts', 12);

function jeo_flush_rewrite() {
	global $pagenow;
	if(is_admin() && $_REQUEST['activated'] && $pagenow == 'themes.php') {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}
}
add_action('init', 'jeo_flush_rewrite');
?>