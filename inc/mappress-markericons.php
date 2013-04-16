<?php

class MapPress_MarkerIcons {

	function __construct() {
		add_action('after_setup_theme', array($this, 'register_post_type'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function register_post_type() {

		$labels = array( 
			'name' => __('Marker icons', 'mappress'),
			'singular_name' => __('Marker icon', 'mappress'),
			'add_new' => __('Add marker icon', 'mappress'),
			'add_new_item' => __('Add new marker icon', 'mappress'),
			'edit_item' => __('Edit marker icon', 'mappress'),
			'new_item' => __('New marker icon', 'mappress'),
			'view_item' => __('View marker icon', 'mappress'),
			'search_items' => __('Search marker icons', 'mappress'),
			'not_found' => __('No marker icon found', 'mappress'),
			'not_found_in_trash' => __('No marker icon found in the trash', 'mappress'),
			'menu_name' => __('Marker icons', 'mappress')
		);

		$args = array( 
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __('MapPress marker icons', 'mappress'),
			'supports' => array('title'),

			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,

			'show_in_nav_menus' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => false,
			'capability_type' => 'post'
		);

		register_post_type( 'marker-icon', $args );

	}

	function admin_menu() {
	    add_submenu_page('edit.php?post_type=map', __('Marker icons', 'mappress'), __('Marker icons', 'mappress'), 'edit_posts', 'edit.php?post_type=marker-icon');
	    add_submenu_page('edit.php?post_type=map', __('Add new marker icon', 'mappress'), __('Add new marker icon', 'mappress'), 'edit_posts', 'post-new.php?post_type=marker-icon');
	}

}

new MapPress_MarkerIcons;