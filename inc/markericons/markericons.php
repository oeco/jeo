<?php

class MapPress_MarkerIcons {

	function __construct() {
		// basic setup
		self::setup_post_type();
		self::setup_custom_table();
		self::setup_menu();
		self::setup_metabox();

		// relationships
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
	 * Admin listing custom columns and action rows
	 */

	function setup_custom_table() {
		add_filter('manage_marker-icon_posts_columns', array($this, 'posts_columns'));
		add_action('manage_marker-icon_posts_custom_column', array($this, 'posts_custom_column'), 10, 2);
		add_action('admin_head', array($this, 'posts_custom_column_styles'));
		add_filter('post_row_actions', array($this, 'action_row'), 10, 2);
		add_filter('admin_footer', array($this, 'action_row_js'));
		add_action('admin_init', array($this, 'save_default_marker'));
	}

	function posts_columns($column) {
		$i = 0;
		foreach($column as $k => $v) {
			$new_column[$k] = $v;
			if($i == 0) {
				$new_column['marker'] = __('Marker', 'mappress');
			}
			$i++;
		}
		return $new_column;
	}

	function posts_custom_column($column_name, $post_id) {
		switch($column_name) {
			case 'marker' :
				$marker_image_id = get_post_meta($post_id, '_marker_image_attachment', true);
				if($marker_image_id) {
					$marker_image = get_post($marker_image_id);
					echo '<img src="' . $marker_image->guid . '" />';
				}
				$default_marker = get_option('mappress_default_marker_id');
				if($default_marker == $post_id)
					echo '(default)';
				break;
			default:
		}
	}

	function posts_custom_column_styles() {
		?>
		<style type="text/css">
			.wp-list-table #marker { width: 150px; }
			#the-list .marker { text-align: center; font-weight: bold; padding-bottom: 10px; }
			#the-list .marker img { display: block; margin: 10px auto; }
		</style>
		<?php
	}

	function action_row($actions, $post) {
		if($post->post_type == 'marker-icon') {
			unset($actions['inline hide-if-no-js']); // unset inline edition
			$default_marker = get_option('mappress_default_marker_id');
			if(current_user_can('manage_options') && $default_marker != $post->ID) {
				$i = 0;
				foreach($actions as $a => $v) {
					if($i == 0) {
						$new_actions['set_default'] .= '<input type="submit" class="button set_default_marker" data-marker="' . $post->ID . '" value="' . __('Set as default marker ', 'mappress') . '" />';
					}
					$new_actions[$a] = $v;
					$i++;
				}
				return $new_actions;
			}
		}
		return $actions;
	}

	function action_row_js() {
		$screen = get_current_screen();
		if($screen->parent_base == 'edit' && $screen->post_type == 'marker-icon') {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('.set_default_marker').click(function() {
						$(this).parents('form').append($('<input name="default_marker" value="' + $(this).data('marker') + '" type="hidden" />'));
					});
				});
			</script>
			<?php
		}
	}

	function save_default_marker() {
		if(isset($_REQUEST['default_marker']) && current_user_can('manage_options')) {
			update_option('mappress_default_marker_id', $_REQUEST['default_marker']);
			add_action('all_admin_notices', array($this, 'save_default_marker_notice'));
			error_log(get_option('mappress_default_marker_id'));
		}
	}
	function save_default_marker_notice() {
		echo '<div class="updated"><p>' . __('Default marker updated', 'mappress') . '</p></div>';
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
		wp_enqueue_script('mappress-markericons', get_template_directory_uri() . '/inc/markericons/markericons.js', array('jquery', 'imagesloaded'), '0.0.3');
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
		$marker_image_id = get_post_meta($post->ID, '_marker_image_attachment', true);
		if($marker_image_id)
			$marker_image = get_post($marker_image_id);
		$icon_x = get_post_meta($post->ID, '_icon_anchor_x', true);
		$icon_y = get_post_meta($post->ID, '_icon_anchor_y', true);
		$popup_x = get_post_meta($post->ID, '_popup_anchor_x', true);
		$popup_y = get_post_meta($post->ID, '_popup_anchor_y', true);
		?>
		<div id="marker-icon-metabox">
			<p>
				<label for="marker_icon_image"><strong><?php _e('Choose image to use as marker icon', 'mappress'); ?></strong></label><br/>
				<small><?php _e('PNG image format is recomended', 'mappress'); ?></small><br/>
				<input type="file" name="marker_image" id="marker_icon_image" />
				<button class="button-primary"><?php _e('Upload image', 'mappress'); ?></button>
			</p>
			<div class="clearfix">
				<div class="marker-icon-container">
					<div class="marker-icon-selector">
						<?php if($marker_image_id) : ?>
							<img src="<?php echo $marker_image->guid; ?>" />
						<?php endif; ?>
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
							<input type="text" size="3" name="marker_icon_anchor_x" id="marker_icon_anchor_x" value="<?php echo $icon_x; ?>" /> <label for="marker_icon_anchor_x"><?php _e('X'); ?></label><br/>
							<input type="text" size="3" name="marker_icon_anchor_y" id="marker_icon_anchor_y" value="<?php echo $icon_y; ?>" /> <label for="marker_icon_anchor_y"><?php _e('Y'); ?></label>
						</p>
					</div>
					<div class="marker-icon-popup-anchor marker-icon-setting">
						<h4><?php _e('Popup anchor', 'mappress'); ?></h4>
						<p><?php _e('Coordinates to correctly position the marker\'s popup', 'mappress'); ?></p>
						<p>
							<button class="button enable-point-edit" data-xinput="marker_icon_popup_anchor_x" data-yinput="marker_icon_popup_anchor_y" data-anchortype="popup"><?php _e('Find coordinates'); ?></button>
						</p>
						<p>
							<input type="text" size="3" name="marker_icon_popup_anchor_x" id="marker_icon_popup_anchor_x" value="<?php echo $popup_x; ?>" /> <label for="marker_icon_popup_anchor_x">X</label><br/>
							<input type="text" size="3" name="marker_icon_popup_anchor_y" id="marker_icon_popup_anchor_y" value="<?php echo $popup_y; ?>" /> <label for="marker_icon_popup_anchor_y">Y</label>
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

		if(isset($_FILES['marker_image']) && $_FILES['marker_image']['size'] > 0) {
			$marker_image = media_handle_upload('marker_image', $post_id);
			if(is_wp_error($marker_image)) {
				add_action('all_admin_notices', array($this, 'save_marker_image_error_notice'));
			} else {
				update_post_meta($post_id, '_marker_image_attachment', $marker_image);
				update_post_meta($post_id, '_icon_anchor_x', 0);
				update_post_meta($post_id, '_icon_anchor_y', 0);
				update_post_meta($post_id, '_popup_anchor_x', 0);
				update_post_meta($post_id, '_popup_anchor_y', 0);
			}
		} else {
			if(isset($_POST['marker_icon_anchor_x']))
				update_post_meta($post_id, '_icon_anchor_x', $_POST['marker_icon_anchor_x']);
			if(isset($_POST['marker_icon_anchor_y']))
				update_post_meta($post_id, '_icon_anchor_y', $_POST['marker_icon_anchor_y']);
			if(isset($_POST['marker_icon_popup_anchor_x']))
				update_post_meta($post_id, '_popup_anchor_x', $_POST['marker_icon_popup_anchor_x']);
			if(isset($_POST['marker_icon_popup_anchor_y']))
				update_post_meta($post_id, '_popup_anchor_y', $_POST['marker_icon_popup_anchor_y']);
		}
	}
	function save_marker_image_error_notice() {
		echo '<div class="error"><p>' . __('Could not save image file', 'mappress') . '</p></div>';
	}

	/*
	 * Relationships
	 */



}

new MapPress_MarkerIcons;