<?php

/*
REGISTER POST TYPES
*/

add_action( 'init', 'register_cpt_map' );

function register_cpt_map() {
    $labels = array( 
        'name' => __('Maps', 'infoamazonia'),
        'singular_name' => __('Map', 'infoamazonia'),
        'add_new' => __('Add new map', 'infoamazonia'),
        'add_new_item' => __('Add new map', 'infoamazonia'),
        'edit_item' => __('Edit map', 'infoamazonia'),
        'new_item' => __('New map', 'infoamazonia'),
        'view_item' => __('View map'),
        'search_items' => __('Search maps', 'infoamazonia'),
        'not_found' => __('No map found', 'infoamazonia'),
        'not_found_in_trash' => __('No map found in the trash', 'infoamazonia'),
        'menu_name' => __('Maps', 'infoamazonia')
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        'description' => __('MapBox Maps', 'infoamazonia'),
        'supports' => array( 'title', 'editor', 'excerpt'),

        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 4,

        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => 'maps',
        'query_var' => true,
        'can_export' => true,
        'rewrite' => array('slug' => 'maps', 'with_front' => false),
        'capability_type' => 'post'
    );

    register_post_type( 'map', $args );
}

add_action( 'init', 'register_cpt_map_group' );

function register_cpt_map_group() {
    $labels = array( 
        'name' => __('Map groups', 'infoamazonia'),
        'singular_name' => __('Map group', 'infoamazonia'),
        'add_new' => __('Add new map group', 'infoamazonia'),
        'add_new_item' => __('Add new map group', 'infoamazonia'),
        'edit_item' => __('Edit map group', 'infoamazonia'),
        'new_item' => __('New map group', 'infoamazonia'),
        'view_item' => __('View map group', 'infoamazonia'),
        'search_items' => __('Search map group', 'infoamazonia'),
        'not_found' => __('No map group found', 'infoamazonia'),
        'not_found_in_trash' => __('No map group found in the trash', 'infoamazonia'),
        'menu_name' => __('Map groups', 'infoamazonia')
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        'description' => __('MapBox maps agroupment', 'infoamazonia'),
        'supports' => array( 'title'),

        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,

        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => array('slug' => 'mapgroup', 'with_front' => false),
        'capability_type' => 'post'
    );

    register_post_type( 'map-group', $args );
}

function map_group_menu() {
    add_submenu_page('edit.php?post_type=map', __('Map groups', 'infoamazonia'), __('Map groups', 'infoamazonia'), 'edit_posts', 'edit.php?post_type=map-group');
    add_submenu_page('edit.php?post_type=map', __('Add new group', 'infoamazonia'), __('Add new map group', 'infoamazonia'), 'edit_posts', 'post-new.php?post_type=map-group');
}

add_action('admin_menu', 'map_group_menu');