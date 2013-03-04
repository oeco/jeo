<?php

/*
REGISTER POST TYPES
*/

add_action( 'init', 'register_cpt_map' );

function register_cpt_map() {
    $labels = array( 
        'name' => __('Maps', 'mappress'),
        'singular_name' => __('Map', 'mappress'),
        'add_new' => __('Add new map', 'mappress'),
        'add_new_item' => __('Add new map', 'mappress'),
        'edit_item' => __('Edit map', 'mappress'),
        'new_item' => __('New map', 'mappress'),
        'view_item' => __('View map'),
        'search_items' => __('Search maps', 'mappress'),
        'not_found' => __('No map found', 'mappress'),
        'not_found_in_trash' => __('No map found in the trash', 'mappress'),
        'menu_name' => __('Maps', 'mappress')
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        'description' => __('MapBox Maps', 'mappress'),
        'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail'),

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
        'name' => __('Map groups', 'mappress'),
        'singular_name' => __('Map group', 'mappress'),
        'add_new' => __('Add new map group', 'mappress'),
        'add_new_item' => __('Add new map group', 'mappress'),
        'edit_item' => __('Edit map group', 'mappress'),
        'new_item' => __('New map group', 'mappress'),
        'view_item' => __('View map group', 'mappress'),
        'search_items' => __('Search map group', 'mappress'),
        'not_found' => __('No map group found', 'mappress'),
        'not_found_in_trash' => __('No map group found in the trash', 'mappress'),
        'menu_name' => __('Map groups', 'mappress')
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        'description' => __('MapBox maps agroupment', 'mappress'),
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
    add_submenu_page('edit.php?post_type=map', __('Map groups', 'mappress'), __('Map groups', 'mappress'), 'edit_posts', 'edit.php?post_type=map-group');
    add_submenu_page('edit.php?post_type=map', __('Add new group', 'mappress'), __('Add new map group', 'mappress'), 'edit_posts', 'post-new.php?post_type=map-group');
}

add_action('admin_menu', 'map_group_menu');

?>