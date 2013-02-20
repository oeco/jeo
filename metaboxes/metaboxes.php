<?php

// enqueue mapbox scripts and styles
add_action('admin_footer', 'mappress_scripts');

/* gather metaboxes */

include(MAPPRESS_PATH .  '/metaboxes/map-relation/map-relation.php');
include(MAPPRESS_PATH .  '/metaboxes/geocode/geocode.php');
include(MAPPRESS_PATH .  '/metaboxes/mapbox/mapbox.php');
include(MAPPRESS_PATH .  '/metaboxes/mapbox/legend.php');
include(MAPPRESS_PATH .  '/metaboxes/mapgroup/mapgroup.php');