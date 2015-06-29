<?php

if ( !class_exists( 'Admin_Page_Framework' ) )
	include_once( get_template_directory() . '/inc/admin/admin-page-framework.php' );

class JEO_Settings_Page extends Admin_Page_Framework {

	var $page_slug = 'jeo_settings';

	function SetUp() {

		$this->AddSubMenu(
			__('JEO Settings', 'jeo'),
			$this->page_slug
		);

		$this->ShowPageHeadingTabs(false);

		// Add in-page tabs in the first page.

		$page_tabs = apply_filters('jeo_settings_tabs', array(
			'home'		=> __('Front page', 'jeo'),
			'map'		=> __('Maps', 'jeo'),
			'geocode'	=> __('Geocode', 'jeo'),
			'mapbox' => __('MapBox', 'jeo')
		));

		$this->AddInPageTabs($this->page_slug, $page_tabs);

		/*
		 * Data
		 */

		$maps = get_posts(array('post_type' => array('map', 'map-group'), 'posts_per_page' => -1));

		$maps_input = array();
		if($maps) {
			foreach($maps as $map) {
				$maps_input[$map->ID] = $map->post_title . ' (' . get_post_type($map->ID) . ')';
			}
		} else {
			$maps_input[0] = __('No maps were found', 'jeo');
		}

		$mapped = jeo_get_mapped_post_types();

		$mapped_post_types = array();
		foreach($mapped as $post_type) {
			$mapped_post_types[$post_type] = true;
		}

		$pts = get_post_types(array('public' => true, '_builtin' => false));
		$pts = $pts + array('post', 'page');
		unset($pts['map']);
		unset($pts['map-group']);

		$post_types = array();
		foreach($pts as $pt) {
			$post_types[$pt] = $pt;
		}

		// Add form elements.
		// Here we have four sections as an example.
		// If you wonder what array keys are need to be used, please refer to http://en.michaeluno.jp/admin-page-framework/methods/

		$form_sections = apply_filters('jeo_settings_form_sections', array(
			array(
				'pageslug' => $this->page_slug,
				'tabslug' => 'home',
				'id' => 'front_page',
				'title' => __('Front page settings', 'jeo'),
				'description' => __('Set your front page contents.', 'jeo'),
				'fields' => array(	// Field Arrays
					// Dropdown List
					array(
						'id' => 'front_page_map',
						'title' => __('Front page map', 'jeo'),
						'description' => __('Select if the front page map should be the featured map with latest content or a selection of featured posts.', 'jeo'),
						'type' => 'radio',
						'default' => 'latest',
						'label' => array('latest' => __('Featured map with latest posts', 'jeo'), 'featured' => __('Selection of featured posts', 'jeo') . ' <strong>(' . __('featured map cannot be a map-group', 'jeo') . ')</strong>')
					),
					array(
						'id' => 'featured_map',
						'title' => __('Featured map', 'jeo'),
						'description' => __('Select the map to be featured on the homepage and posts.', 'jeo'),
						'type' => 'select',
						'default' => 0,
						'label' => $maps_input
					)
				)
			),
			array(
				'pageslug' => $this->page_slug,
				'tabslug' => 'geocode',
				'id' => 'geocode',
				'title' => __('Geocode settings', 'jeo'),
				'description' => '',
				'fields' => array(	// Field Arrays
					// Text Field
					array(
						'id' => 'type',
						'title' => __('Geocode type', 'jeo'),
						'description' => __('Choose simple latitude/longitude inputs or complete address lookup geocoding system', 'jeo'),	// additional notes besides the form field
						'type' => 'radio',
						'default' => 'default',
						'label' => array('default' => __('Address geocoding with interactive map (default)', 'jeo'), 'latlng' => __('Latitude/longitude inputs', 'jeo'))
					),
					array(
						'id' => 'service',
						'title' => __('Geocode service', 'jeo'),
						'description' => __('Choose the geocoding service to be used', 'jeo'),
						'type' => 'radio',
						'default' => 'osm',
						'label' => array('osm' => __('OpenStreetMaps with Nominatim', 'jeo'), 'gmaps' => 'Google Maps')
					),
					array(
						'id' => 'gmaps_api_key',
						'title' => __('Google Maps API Key', 'jeo'),
						'description' => sprintf(__('Key to use the Google Maps geocoding services. <a href="%s" target="_blank">Click here to get one.</a>', 'jeo'), 'https://developers.google.com/maps/documentation/javascript/tutorial#api_key'),
						'type' => 'text',
						'size' => 100
					)
				)
			),
			array(
				'pageslug' => $this->page_slug,
				'tabslug' => 'map',
				'id' => 'map',
				'title' => __('Map behaviours', 'jeo'),
				'fields' => array(
					array(
						'id' => 'use_hash',
						'title' => __('Fragment hash', 'jeo'),
						'description' => __('Enable use of fragment hash url to share map location, selected maps on mapgroup and fullscreen state. <br/>E.g.: yoursite.com/#!/loc=-23,-42,7&map=12&full=true', 'jeo'),
						'type' => 'checkbox',
						'default' => true,
						'label' => __('Enable', 'jeo')
					),
					array(
						'id' => 'enable_clustering',
						'title' => __('Enable marker clustering', 'jeo'),
						'description' => __('Enable marker clustering system.', 'jeo'),
						'type' => 'checkbox',
						'default' => true,
						'label' => __('Enable', 'jeo')
					),
					array(
						'id' => 'use_map_query',
						'title' => __('Map query', 'jeo'),
						'description' => __('Display posts only associated to the viewing map', 'jeo'),
						'type' => 'checkbox',
						'default' => true,
						'label' => __('Enable', 'jeo')
					),
					array(
						'id' => 'mapped_post_types',
						'title' => __('Mapped post types', 'jeo'),
						'description' => __('Post types to enable map functionalities', 'jeo'),
						'type' => 'checkbox',
						'default' => $mapped_post_types,
						'label' => $post_types
					),
				)
			),
			array(
				'pageslug' => $this->page_slug,
				'tabslug' => 'mapbox',
				'id' => 'mapbox',
				'title' => __('MapBox', 'jeo'),
				'description' => '',
				'fields' => array(
					array(
						'id' => 'access_token',
						'title' => __('Access token', 'jeo'),
						'description' => __('MapBox access token'),
						'type' => 'text',
						'size' => 100
					),
				)
			),
		), $this->page_slug);

		$this->AddFormSections($form_sections);

	}

	function do_jeo_settings() {

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'about' ) return;

		submit_button();

	}

	function content_jeo_settings_about( $strContent ) {

		return $strContent . '<h3>Documentation</h3>'
			. '<ul class="admin-page-framework">'
			. '<li><a href="http://en.michaeluno.jp/admin-page-framework/get-started/">Get Started</a></li>'
			. '<li><a href="http://en.michaeluno.jp/admin-page-framework/demos/">Demos</a></li>'
			. '<li><a href="http://en.michaeluno.jp/admin-page-framework/methods/">Methods</a></li>'
			. '<li><a href="http://en.michaeluno.jp/admin-page-framework/hooks-and-callbacks/">Hooks and Callbacks</a></li>'
			. '</ul>'
			. '<h3>Participate in the Project</h3>'
			. '<p>The repository is available at GitHub. <a href="https://github.com/michaeluno/admin-page-framework">https://github.com/michaeluno/admin-page-framework</a></p>'
		;

	}

}

function jeo_init_settings_page() {
	$GLOBALS['jeo_admin'] = new JEO_Settings_Page('jeo_settings', __FILE__);
}
add_action('jeo_init', 'jeo_init_settings_page', 100);

function jeo_admin_add_form_sections($sections = array()) {
	if(is_array($sections) && !empty($sections)) {
		global $jeo_admin;
		$jeo_admin->AddFormSections($sections);
	}
}
