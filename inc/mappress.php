<?php

function mappress_path() {
	$path = realpath(dirname(__FILE__));
	$theme_path = TEMPLATEPATH;
	if(is_link($theme_path))
		$theme_path = readlink($theme_path);

	$relative_path = substr($path, strlen($theme_path));

	return TEMPLATEPATH . '/' . $relative_path;
}

define('MAPPRESS_PATH', mappress_path());

/*
 * Mappress
 */

// map functions
include(MAPPRESS_PATH . '/mappress-core.php');
include(MAPPRESS_PATH . '/mappress-functions.php');

// add metaboxes
include(MAPPRESS_PATH . '/metaboxes/metaboxes.php');

?>