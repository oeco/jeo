<?php

/**
 * JEO
 * Date rage picker plugin
 */

class JEO_Range_Slider {

	function __construct() {

		add_action('jeo_init', array($this, 'init'));

	}

	function init() {

		add_filter('jeo_marker_data', array($this, 'marker_data'));
		add_action('wp_footer', array($this, 'enqueue_scripts'));
		add_action('jeo_markers_enqueue_scripts', array($this, 'enqueue_scripts'), 5);
		add_action('jeo_map_setup_options', array($this, 'map_options'));

	}

	function enqueue_scripts() {

		wp_register_script('jquery-mousewheel', get_template_directory_uri() . '/lib/jquery.mousewheel.js', array('jquery'));

		wp_enqueue_style('range-slider', get_template_directory_uri() . '/lib/range-slider/css/classic-min.css');
		wp_enqueue_style('jeo-range-slider', get_template_directory_uri() . '/inc/css/range-slider.css');

		wp_register_script('range-slider', get_template_directory_uri() . '/lib/range-slider/jQAllRangeSliders-withRuler-min.js',  array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-mousewheel'));

		wp_register_script('moment-js', get_template_directory_uri() . '/lib/moment.js');

		wp_enqueue_script('jeo-range-slider', get_template_directory_uri() . '/inc/js/range-slider.js', array('range-slider', 'jeo.markers', 'jeo.groups', 'underscore', 'moment-js'), '0.1.3');

		$range_slider_options = apply_filters('jeo_range_slider_options', array(
			'rangeType' => 'dateRangeSlider',
			'options' => array(
				'dateFormat' => _x('MM/DD/YYYY', 'Range slider date format', 'jeo')
			)
		));

		wp_localize_script('jeo-range-slider', 'jeo_range_slider_options', $range_slider_options);

	}

	function marker_data($data) {
		global $post;
		$data['range_slider_property'] = apply_filters('jeo_range_slider_filter_property', strtotime($post->post_date));
		return $data;
	}

	function map_options($map_data) {
		?>
		<div class="handlers map-setting">
			<h4><?php _e('Date range slider filter', 'jeo'); ?></h4>
			<p>
				<input class="range-slider-filter-input" id="range_slider_filter" type="checkbox" name="map_data[rangeSliderFilter]" <?php if(isset($map_data['rangeSliderFilter']) && $map_data['rangeSliderFilter']) echo 'checked'; ?> />
				<label for="range_slider_filter"><?php _e('Enable date range slider filter for markers', 'jeo'); ?></label>
			</p>
		</div>
		<?php
	}

}

new JEO_Range_Slider();