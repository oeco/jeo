<?php

/*
 * JEO WP API
 * Support JEO metadata on WP-API Plugin
 */

class JEO_WP_API {

  function __construct() {

    add_filter('json_prepare_post', array($this, 'json_prepare_map'), 10, 3);
    add_filter('json_prepare_post', array($this, 'json_prepare_mapgroup'), 10, 3);
    add_filter('json_prepare_post', array($this, 'json_prepare_layer'), 10, 3);
    add_filter('json_prepare_post', array($this, 'json_prepare_marker'), 10, 3);

  }

  function json_prepare_map($_post, $post, $context) {

    if($post['post_type'] == 'map') {

      $_post = array_merge($_post, jeo_get_map_data($post['ID']));
      unset($_post['dataReady']);
      unset($_post['postID']);

    }

    return $_post;

  }

  function json_prepare_mapgroup($_post, $post, $context) {

    if($post['post_type'] == 'map-group') {

      $_post = array_merge($_post, jeo_get_mapgroup_data($post['ID']));

    }

    return $_post;

  }

  function json_prepare_layer($_post, $post, $context) {

    if($post['post_type'] == 'map-layer') {

      $_post = array_merge($_post, jeo_get_layer($post['ID']));

    }

    return $_post;
  }

  function json_prepare_marker($_post, $post, $context) {

    if(in_array($post['post_type'], jeo_get_mapped_post_types())) {

      $_post['maps'] = get_post_meta($post['ID'], 'maps');
      $_post['coordinates'] = jeo_get_marker_coordinates($post['ID']);

    }

    return $_post;
  }

}

new JEO_WP_API();
