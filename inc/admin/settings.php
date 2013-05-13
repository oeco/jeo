<?php

if ( !class_exists( 'Admin_Page_Framework' ) ) 
	include_once( TEMPLATEPATH . '/inc/admin/admin-page-framework.php' );

class MapPress_Settings_Page extends Admin_Page_Framework {

	function SetUp() {
					
		$this->SetRootMenu(__('MapPress', 'mappress'));	
		
		$this->AddSubMenu(
			'MapPress Settings',
			'mappress_settings'
		);

		$this->ShowPageHeadingTabs(false);
		
		// Add in-page tabs in the first page.			
		$this->AddInPageTabs(
			'mappress_settings',	
			array(	// slug => title
				'home'		=> __('Front page', 'mappress'),
				'map'		=> __('Maps', 'mappress'),
				'geocode'	=> __('Geocode', 'mappress'),
				'about'		=> __('About', 'mappress')
			) 
		);

		/*
		 * Data
		 */

		$maps = get_posts(array('post_type' => array('map', 'map-group')));

		$maps_input = array();
		if($maps) {
			foreach($maps as $map) {
				$maps_input[$map->ID] = $map->post_title . ' (' . get_post_type($map->ID) . ')';
			}
		} else {
			$maps_input[0] = __('No maps were found', 'mappress');
		}

		$mapped = mappress_get_mapped_post_types();

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
		$this->AddFormSections( 
			// Section Arrays - numerically indexed.
			array(
				array(  
					'pageslug' => 'mappress_settings',
					'tabslug' => 'home',
					'id' => 'front_page', 
					'title' => __('Front page settings', 'mappress'),
					'description' => __('Set your front page contents.', 'mappress'),
					'fields' => array(	// Field Arrays
						// Dropdown List
						array(
							'id' => 'front_page_map',
							'title' => __('Front page map', 'mappress'),
							'description' => __('Select if the front page map should be the featured map with latest content or a selection of featured posts.', 'mappress'),
							'type' => 'radio',
							'default' => 'latest',
							'label' => array('latest' => __('Featured map with latest posts', 'mappress'), 'featured' => __('Selection of featured posts') . ' <strong>(' . __('featured map cannot be a map-group', 'mappress') . ')</strong>')
						),
						array(  
							'id' => 'featured_map',
							'title' => __('Featured map', 'mappress'),
							'description' => __('Select the map to be featured on the homepage and posts.', 'mappress'),
							'type' => 'select',
							'default' => 0,
							'label' => $maps_input
						)
					)
				),
				array(  
					'pageslug' => 'mappress_settings',
					'tabslug' => 'geocode',
					'id' => 'geocode', 
					'title' => __('Geocode settings', 'mappress'),
					'description' => '',
					'fields' => array(	// Field Arrays
						// Text Field
						array(  
							'id' => 'type', 
							'title' => __('Geocode type', 'mappress'),
							'description' => __('Choose simple latitude/longitude inputs or complete address lookup geocoding system', 'mappress'),	// additional notes besides the form field
							'type' => 'radio',
							'default' => 'default',
							'label' => array('default' => __('Address geocoding with interactive map (default)', 'mappress'), 'latlng' => __('Latitude/longitude inputs', 'mappress')) 
						),
						array(
							'id' => 'service',
							'title' => __('Geocode service', 'mappress'),
							'description' => __('Choose the geocoding service to be used', 'mappress'),
							'type' => 'radio',
							'default' => 'osm',
							'label' => array('osm' => __('OpenStreetMaps with Nominatim', 'mappress'), 'gmaps' => __('Google Maps'))
						),
						array(
							'id' => 'gmaps_api_key',
							'title' => __('Google Maps API Key', 'mappress'),
							'description' => sprintf(__('Key to use the Google Maps geocoding services. <a href="%s" target="_blank">Click here to get one.</a>', 'mappress'), 'https://developers.google.com/maps/documentation/javascript/tutorial#api_key'),
							'type' => 'text',
							'size' => 100
						)
					)
				),
				array(  
					'pageslug' => 'mappress_settings',
					'tabslug' => 'map',
					'id' => 'map', 
					'title' => __('Map behaviours', 'mappress'),
					'fields' => array(
						array(  
							'id' => 'use_hash', 
							'title' => __('Fragment hash', 'mappress'),
							'description' => __('Enable use of fragment hash url to share map location, selected maps on mapgroup and fullscreen state. <br/>E.g.: yoursite.com/#!/loc=-23,-42,7&map=12&full=true', 'mappress'),
							'type' => 'checkbox',
							'default' => true,
							'label' => __('Enable', 'mappress') 
						),
						array(  
							'id' => 'use_map_query',
							'title' => __('Map query', 'mappress'),
							'description' => __('Display posts only associated to the viewing map', 'mappress'),
							'type' => 'checkbox',
							'default' => true,
							'label' => __('Enable', 'mappress')
						),	
						array(  
							'id' => 'mapped_post_types',
							'title' => __('Mapped post types', 'mappress'),
							'description' => __('Post types to enable map functionalities', 'mappress'),
							'type' => 'checkbox',
							'default' => $mapped_post_types,
							'label' => $post_types
						),							
					)
				),
			)
		);

	}

	function do_mappress_settings() {

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'about' ) return;
	
		submit_button();

	}
	
	function content_mappress_settings_about( $strContent ) {
		
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

new MapPress_Settings_Page('mappress_settings', __FILE__);