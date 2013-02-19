<?php

// enqueue mapbox scripts and styles
add_action('admin_footer', 'mappress_scripts');

/* gather metaboxes */

include(TEMPLATEPATH .  '/inc/mappress/metaboxes/map-relation/map-relation.php');
include(TEMPLATEPATH .  '/inc/mappress/metaboxes/geocode/geocode.php');
include(TEMPLATEPATH .  '/inc/mappress/metaboxes/mapbox/mapbox.php');
include(TEMPLATEPATH .  '/inc/mappress/metaboxes/mapbox/legend.php');
include(TEMPLATEPATH .  '/inc/mappress/metaboxes/mapgroup/mapgroup.php');