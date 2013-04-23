<?php

class MapPress_MarkerIcons {

	function __construct() {
		$this::setup_post_type();
		$this::setup_menu();
		$this::setup_metabox();
	}

	/*
	 * Post type
	 */

	function setup_post_type() {
		add_action('after_setup_theme', array($this, 'register_post_type'));
	}

	function register_post_type() {

		$labels = array( 
			'name' => __('Marker icons', 'mappress'),
			'singular_name' => __('Marker icon', 'mappress'),
			'add_new' => __('Add marker icon', 'mappress'),
			'add_new_item' => __('Add new marker icon', 'mappress'),
			'edit_item' => __('Edit marker icon', 'mappress'),
			'new_item' => __('New marker icon', 'mappress'),
			'view_item' => __('View marker icon', 'mappress'),
			'search_items' => __('Search marker icons', 'mappress'),
			'not_found' => __('No marker icon found', 'mappress'),
			'not_found_in_trash' => __('No marker icon found in the trash', 'mappress'),
			'menu_name' => __('Marker icons', 'mappress')
		);

		$args = array( 
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __('MapPress marker icons', 'mappress'),
			'supports' => array('title'),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false
		);

		register_post_type('marker-icon', $args);

	}

	/*
	 * Menu
	 */

	function setup_menu() {
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function admin_menu() {
	    add_submenu_page('edit.php?post_type=map', __('Marker icons', 'mappress'), __('Marker icons', 'mappress'), 'edit_posts', 'edit.php?post_type=marker-icon');
	    add_submenu_page('edit.php?post_type=map', __('Add new marker icon', 'mappress'), __('Add new marker icon', 'mappress'), 'edit_posts', 'post-new.php?post_type=marker-icon');
	}

	/*
	 * Metabox
	 */

	function setup_metabox() {
		add_action('admin_footer', array($this, 'init_meta_box'));
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('save_post', array($this, 'save_postdata'));
	}

	function init_meta_box() {
		wp_enqueue_style('mappress-markericons', get_template_directory_uri() . '/inc/markericons/markericons.css');
		wp_enqueue_script('mappress-markericons', get_template_directory_uri() . '/inc/markericons/markericons.js', array('jquery', 'imagesloaded'), '0.0.2');
	}

	function add_meta_box() {
		add_meta_box(
			'mappress_markericon',
			__('Setup marker icon', 'mappress'),
			array($this, 'inner_meta_box'),
			'marker-icon',
			'advanced',
			'high'
		);
	}

	function inner_meta_box($post) {
		?>
		<div id="marker-icon-metabox">
			<p>
				<label for="marker_icon_image"><strong><?php _e('Choose image to use as marker icon', 'mappress'); ?></strong></label><br/>
				<small><?php _e('PNG image format is recomended', 'mappress'); ?></small><br/>
				<input type="file" name="marker_icon_image" id="marker_icon_image" />
			</p>
			<div class="clearfix">
				<div class="marker-icon-container">
					<div class="marker-icon-selector">
						<img src="http://leafletjs.com/dist/images/marker-icon.png" />
						<button class="button use-default"><?php _e('Use default', 'mappress'); ?></button>
						<button class="button cancel"><?php _e('Cancel', 'mappress'); ?></button>
						<button class="button-primary save"><?php _e('Save', 'mappress'); ?></button>
						<p class="console mouse">
							<strong><?php _e('Mouse', 'mappress'); ?></strong>
							<span class="x-console">X: <span class="x">0</span></span>
							<span class="y-console">Y: <span class="y">0</span></span>
						</p>
						<p class="console position">
							<strong><?php _e('Point', 'mappress'); ?></strong>
							<span class="x-console">X: <span class="x">0</span></span>
							<span class="y-console">Y: <span class="y">0</span></span>
						</p>
					</div>
					<small class="tip"><strong><?php _e('Tip:', 'mappress'); ?></strong> <?php _e('Use ARROWS to move the pointer, press ENTER to save or ESC to cancel.', 'mappress'); ?></small>
				</div>
				<div class="marker-icon-settings">
					<div class="marker-icon-anchor marker-icon-setting">
						<h4><?php _e('Icon anchor', 'mappress'); ?></h4>
						<p><?php _e('Coordinates to correctly position the marker on the map', 'mappress'); ?></p>
						<p>
							<button class="button enable-point-edit" data-xinput="marker_icon_anchor_x" data-yinput="marker_icon_anchor_y" data-anchortype="icon"><?php _e('Find coordinates'); ?></button>
						</p>
						<p>
							<input type="text" size="3" name="marker_icon_anchor_x" id="marker_icon_anchor_x" /> <label for="marker_icon_anchor_x"><?php _e('X'); ?></label><br/>
							<input type="text" size="3" name="marker_icon_anchor_y" id="marker_icon_anchor_y" /> <label for="marker_icon_anchor_y"><?php _e('Y'); ?></label>
						</p>
					</div>
					<div class="marker-icon-popup-anchor marker-icon-setting">
						<h4><?php _e('Popup anchor', 'mappress'); ?></h4>
						<p><?php _e('Coordinates to correctly position the marker\'s popup', 'mappress'); ?></p>
						<p>
							<button class="button enable-point-edit" data-xinput="marker_icon_popup_anchor_x" data-yinput="marker_icon_popup_anchor_y" data-anchortype="popup"><?php _e('Find coordinates'); ?></button>
						</p>
						<p>
							<input type="text" size="3" name="marker_icon_popup_anchor_x" id="marker_icon_popup_anchor_x" /> <label for="marker_icon_popup_anchor_x">X</label><br/>
							<input type="text" size="3" name="marker_icon_popup_anchor_y" id="marker_icon_popup_anchor_y" /> <label for="marker_icon_popup_anchor_y">Y</label>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	function save_postdata($post_id) {
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (defined('DOING_AJAX') && DOING_AJAX)
			return;

		if (false !== wp_is_post_revision($post_id))
			return;
	}

}

new MapPress_MarkerIcons;