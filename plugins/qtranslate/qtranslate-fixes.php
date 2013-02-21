<?php
/*
 * qTranslate fixes
 */

// fix forced formated date on qTranslate
function mappress_get_custom_format_date($date, $format) {
	if(function_exists('qtrans_getLanguage') && $format != '') {
		$post = get_post();
		$date = mysql2date($format, $post->post_date);
	}
	return $date;
}
add_filter('get_the_date', 'mappress_get_custom_format_date', 10, 2);


// send lang to ajax requests
function mappress_qtrans_admin_ajax_url($url, $path) {
	if($path == 'admin-ajax.php' && function_exists('qtrans_getLanguage'))
		$url .= '?lang=' . qtrans_getLanguage();

	return $url;
}
add_filter('admin_url', 'mappress_qtrans_admin_ajax_url', 10, 2);