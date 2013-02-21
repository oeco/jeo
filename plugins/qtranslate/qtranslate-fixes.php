<?php
/*
 * qTranslate fixes
 */

// fix forced formated date on qtranslate
function get_the_orig_date($format = false) {
	global $post;
	$date = get_the_date($format);
	if(function_exists('qtrans_getLanguage')) {
		remove_filter('get_the_date', 'qtrans_dateFromPostForCurrentLanguage', 0, 4);
		$date = get_the_date($format);
		add_filter('get_the_date', 'qtrans_dateFromPostForCurrentLanguage', 0, 4);
	}
	return $date;
}


function mappress_qtrans_admin_ajax_url($url, $path) {
	if($path == 'admin-ajax.php' && function_exists('qtrans_getLanguage'))
		$url .= '?lang=' . qtrans_getLanguage();

	return $url;
}
add_filter('admin_url', 'mappress_qtrans_admin_ajax_url', 10, 2);