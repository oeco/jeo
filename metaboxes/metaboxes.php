<?php

// enqueue mapbox scripts and styles
add_action('admin_footer', 'mappress_scripts');

/* gather metaboxes */

include(TEMPLATEPATH .  '/metaboxes/map-relation/map-relation.php');
include(TEMPLATEPATH .  '/metaboxes/geocode/geocode.php');
include(TEMPLATEPATH .  '/metaboxes/mapbox/mapbox.php');
include(TEMPLATEPATH .  '/metaboxes/mapbox/legend.php');
include(TEMPLATEPATH .  '/metaboxes/mapgroup/mapgroup.php');