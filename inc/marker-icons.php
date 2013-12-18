<?php

/* 
 * JEO Marker Icons
 */

class JEO_Marker_Icons {

	var $post_type = 'marker-icon';
	var $connected_taxonomies = false;
	var $connected_post_types = false;

	function __construct() {
		// basic setup
		$this->setup();

		// relationships
		$this->setup_taxonomy_relationship();
		$this->setup_post_type_relationship();
	}

	/*
	 * Functions
	 */

	function get_markers($args = false) {
		if(!$args)
			$args = array('post_type' => $this->post_type, 'posts_per_page' => -1);
		return get_posts($args);
	}

	function get_marker($marker_id) {
		if(!$marker_id)
			return false;

		return get_post($marker_id);
	}

	function get_marker_image_url($marker_id) {
		$marker_image_id = get_post_meta($marker_id, '_marker_image_attachment', true);
		if($marker_image_id) {
			$marker_image = get_post($marker_image_id);
			return $marker_image->guid;
		}
		return false;
	}

	function get_marker_size($marker_id) {
		return array(
			intval(get_post_meta($marker_id, '_marker_image_width', true)),
			intval(get_post_meta($marker_id, '_marker_image_height', true))
		);
	}

	function get_marker_anchor($marker_id) {
		return array(
			intval(get_post_meta($marker_id, '_icon_anchor_x', true)),
			intval(get_post_meta($marker_id, '_icon_anchor_y', true))
		);
	}

	function get_marker_popup_anchor($marker_id) {
		return array(
			intval(get_post_meta($marker_id, '_popup_anchor_x', true)) - intval(get_post_meta($marker_id, '_icon_anchor_x', true)),
			intval(get_post_meta($marker_id, '_popup_anchor_y', true)) - intval(get_post_meta($marker_id, '_icon_anchor_y', true))
		);
	}

	function get_marker_formatted($post_id = false) {

		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$marker = array(
			'iconUrl' => get_template_directory_uri() . '/img/marker.png',
			'iconSize' => array(26, 30),
			'iconAnchor' => array(13, 30),
			'popupAnchor' => array(0, -40),
			'markerId' => 'none'
		);

		if($post_id) {
			$post_marker_id = $this->get_post_marker_id($post_id);

			if($post_marker_id && get_post($post_marker_id)) {
				$marker = array(
					'iconUrl' => $this->get_marker_image_url($post_marker_id),
					'iconSize' => $this->get_marker_size($post_marker_id),
					'iconAnchor' => $this->get_marker_anchor($post_marker_id),
					'popupAnchor' => $this->get_marker_popup_anchor($post_marker_id),
					'markerId' => $post_marker_id
				);
			}
		}

		return $marker;
	}

 	/*
 	 * Relationship functions
 	 */
	function get_term_marker_id($term_id) {
		$term_meta = get_option("taxonomy_term_$term_id");
		if($term_meta && $term_meta['marker_id'])
			return $term_meta['marker_id'];

		return false;
	}

	/*
	 * Post marker
	 */

	function get_post_marker_id($post_id) {

		// if post has marker
		$marker_id = get_post_meta($post_id, 'marker_id', true);
		if($marker_id)
			return $marker_id;

		// if post's terms has marker
		$taxonomies = $this->connected_taxonomies;
		foreach($taxonomies as $taxonomy) {
			$terms = get_the_terms($post_id, $taxonomy);
			if($terms) {
				foreach($terms as $term) {
					$marker_id = $this->get_term_marker_id($term->term_id);
					if($marker_id)
						return $marker_id;
				}
			}
		}

		return $this->get_default_marker_id();

	}

	/*
	 * Default marker functions
	 */

	function get_default_marker_id() {
		$marker_id = get_option('jeo_default_marker_id');
		return $marker_id ? $marker_id : false;
	}

	function get_default_marker() {
		$marker_id = $this->get_default_marker_id();
		return $marker_id ? get_post($marker_id) : false;
	}

	function set_default_marker($marker_id) {
		return update_option('jeo_default_marker_id', $marker_id);
	}

	/*
	 * Setup starts here
	 */

	function setup() {
		$this->setup_post_type();
		$this->setup_marker_custom_table();
		$this->setup_menu();
		$this->setup_metabox();
		$this->setup_post_marker_icon();
	}

	/*
	 * Setup post type
	 */

	function setup_post_type() {
		add_action('init', array($this, 'register_post_type'));
	}

	function register_post_type() {

		$labels = array( 
			'name' => __('Marker icons', 'jeo'),
			'singular_name' => __('Marker icon', 'jeo'),
			'add_new' => __('Add marker icon', 'jeo'),
			'add_new_item' => __('Add new marker icon', 'jeo'),
			'edit_item' => __('Edit marker icon', 'jeo'),
			'new_item' => __('New marker icon', 'jeo'),
			'view_item' => __('View marker icon', 'jeo'),
			'search_items' => __('Search marker icons', 'jeo'),
			'not_found' => __('No marker icon found', 'jeo'),
			'not_found_in_trash' => __('No marker icon found in the trash', 'jeo'),
			'menu_name' => __('Marker icons', 'jeo')
		);

		$args = array( 
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __('JEO marker icons', 'jeo'),
			'supports' => array('title'),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 4
		);

		register_post_type($this->post_type, $args);

	}

	/*
	 * Admin listing custom columns and action rows
	 */

	function setup_marker_custom_table() {
		add_filter("manage_{$this->post_type}_posts_columns", array($this, 'marker_columns'));
		add_action("manage_{$this->post_type}_posts_custom_column", array($this, 'marker_custom_column'), 10, 2);
		add_action('admin_head', array($this, 'marker_custom_column_styles'));
		add_filter('post_row_actions', array($this, 'action_row'), 10, 2);
		add_filter('admin_footer', array($this, 'action_row_js'));
		add_action('admin_init', array($this, 'save_default_marker'));
	}

	function marker_columns($column) {
		$i = 0;
		foreach($column as $k => $v) {
			$new_column[$k] = $v;
			if($i == 0) {
				$new_column['marker'] = __('Marker', 'jeo');
			}
			$i++;
		}
		return $new_column;
	}

	function marker_custom_column($column_name, $post_id) {
		switch($column_name) {
			case 'marker' :
				$image = $this->get_marker_image_url($post_id);
				if($image)
					echo '<img src="' . $image . '" />';
				$default_marker = $this->get_default_marker();
				if($default_marker->ID == $post_id)
					echo '(' . __('default', 'jeo') . ')';
				break;
			default:
		}
	}

	function marker_custom_column_styles() {
		?>
		<style type="text/css">
			.wp-list-table #marker { width: 150px; }
			#the-list .marker { text-align: center; font-weight: bold; padding-bottom: 10px; }
			#the-list .marker img { display: block; margin: 10px auto; }
			#adminmenu #menu-posts-<?php echo $this->post_type; ?>.menu-icon-post div.wp-menu-image:before {
			  font-family: 'jeo-dashicons' !important;
			  content: '\e608';
			}
		</style>
		<?php
	}

	function action_row($actions, $post) {
		if($post->post_type == $this->post_type) {
			unset($actions['inline hide-if-no-js']); // unset inline edition
			$default_marker = $this->get_default_marker();
			if(current_user_can('manage_options') && $default_marker->ID != $post->ID) {
				$i = 0;
				foreach($actions as $a => $v) {
					if($i == 0) {
						$new_actions['set_default'] .= '<input type="submit" class="button set_default_marker" data-marker="' . $post->ID . '" value="' . __('Set as default marker ', 'jeo') . '" />';
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
		if($screen->post_type == 'marker-icon') {
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
			$this->set_default_marker($_REQUEST['default_marker']);
			add_action('all_admin_notices', array($this, 'save_default_marker_notice'));
		}
	}
	function save_default_marker_notice() {
		echo '<div class="updated"><p>' . __('Default marker updated', 'jeo') . '</p></div>';
	}

	/*
	 * Setup menu
	 */

	function setup_menu() {
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function admin_menu() {
	    //add_theme_page(__('Marker icons', 'jeo'), __('Marker icons', 'jeo'), 'edit_posts', 'edit.php?post_type=marker-icon');
	    //add_theme_page(__('Add new marker icon', 'jeo'), __('Add new marker icon', 'jeo'), 'edit_posts', 'post-new.php?post_type=marker-icon');
	}

	/*
	 * Setup marker editor metabox
	 */

	function setup_metabox() {
		add_action('admin_footer', array($this, 'init_meta_box'));
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('save_post', array($this, 'save_marker_data'));
	}

	function init_meta_box() {
		wp_enqueue_style('jeo-markericons', get_template_directory_uri() . '/inc/css/marker-icons.css');
		wp_enqueue_script('jeo-markericons', get_template_directory_uri() . '/inc/js/marker-icons.js', array('jquery', 'imagesloaded'), '0.0.4');
	}

	function add_meta_box() {
		add_meta_box(
			'jeo_markericon',
			__('Setup marker icon', 'jeo'),
			array($this, 'inner_meta_box'),
			$this->post_type,
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
				<label for="marker_icon_image"><strong><?php _e('Choose image to use as marker icon', 'jeo'); ?></strong></label><br/>
				<small><?php _e('PNG image format is recomended', 'jeo'); ?></small><br/>
				<input type="file" name="marker_image" id="marker_icon_image" />
				<input type="hidden" name="marker_width" id="marker_icon_width" />
				<input type="hidden" name="marker_height" id="marker_icon_height" />
				<button class="button-primary"><?php _e('Upload image', 'jeo'); ?></button>
			</p>
			<div class="clearfix">
				<div class="marker-icon-container">
					<div class="marker-icon-selector">
						<?php if($marker_image_id) : ?>
							<img src="<?php echo $marker_image->guid; ?>" />
						<?php endif; ?>
						<button class="button use-default"><?php _e('Use default', 'jeo'); ?></button>
						<button class="button cancel"><?php _e('Cancel', 'jeo'); ?></button>
						<button class="button-primary save"><?php _e('Save', 'jeo'); ?></button>
						<p class="console mouse">
							<strong><?php _e('Mouse', 'jeo'); ?></strong>
							<span class="x-console">X: <span class="x">0</span></span>
							<span class="y-console">Y: <span class="y">0</span></span>
						</p>
						<p class="console position">
							<strong><?php _e('Point', 'jeo'); ?></strong>
							<span class="x-console">X: <span class="x">0</span></span>
							<span class="y-console">Y: <span class="y">0</span></span>
						</p>
					</div>
					<small class="tip"><strong><?php _e('Tip:', 'jeo'); ?></strong> <?php _e('Use ARROWS to move the pointer, press ENTER to save or ESC to cancel.', 'jeo'); ?></small>
				</div>
				<div class="marker-icon-settings">
					<div class="marker-icon-anchor marker-icon-setting">
						<h4><?php _e('Icon anchor', 'jeo'); ?></h4>
						<p><?php _e('Coordinates to correctly position the marker on the map', 'jeo'); ?></p>
						<p>
							<button class="button enable-point-edit" data-xinput="marker_icon_anchor_x" data-yinput="marker_icon_anchor_y" data-anchortype="icon"><?php _e('Find coordinates', 'jeo'); ?></button>
						</p>
						<p>
							<input type="text" size="3" name="marker_icon_anchor_x" id="marker_icon_anchor_x" value="<?php echo $icon_x; ?>" /> <label for="marker_icon_anchor_x"><?php _ex('X', 'Cartesian coordination system axis', 'jeo'); ?></label><br/>
							<input type="text" size="3" name="marker_icon_anchor_y" id="marker_icon_anchor_y" value="<?php echo $icon_y; ?>" /> <label for="marker_icon_anchor_y"><?php _ex('Y', 'Cartesian coordination system axis', 'jeo'); ?></label>
						</p>
					</div>
					<div class="marker-icon-popup-anchor marker-icon-setting">
						<h4><?php _e('Popup anchor', 'jeo'); ?></h4>
						<p><?php _e('Coordinates to correctly position the marker\'s popup', 'jeo'); ?></p>
						<p>
							<button class="button enable-point-edit" data-xinput="marker_icon_popup_anchor_x" data-yinput="marker_icon_popup_anchor_y" data-anchortype="popup"><?php _e('Find coordinates', 'jeo'); ?></button>
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

	function save_marker_data($post_id) {
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
			if(isset($_POST['marker_width']))
				update_post_meta($post_id, '_marker_image_width', $_POST['marker_width']);
			if(isset($_POST['marker_height']))
				update_post_meta($post_id, '_marker_image_height', $_POST['marker_height']);
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
		echo '<div class="error"><p>' . __('Could not save image file', 'jeo') . '</p></div>';
	}

	/*
	 *
	 * Relationships
	 *
	 */

	/*
	 * Taxonomy relationship
	 */

	function connected_taxonomies() {
		$this->connected_taxonomies = apply_filters('jeo_marker_taxonomies', array('category', 'post_tag'));
		return $this->connected_taxonomies;
	}

	function setup_taxonomy_relationship() {
		$taxonomies = $this->connected_taxonomies();
		foreach($taxonomies as $taxonomy) {
			add_action("{$taxonomy}_edit_form_fields", array($this, 'taxonomy_form_custom_field'));
			add_action("{$taxonomy}_add_form_fields", array($this, 'taxonomy_form_custom_field'));
			add_action("edited_{$taxonomy}", array($this, 'taxonomy_form_save'));
			// custom taxonomy columns
			add_filter("manage_edit-{$taxonomy}_columns", array($this, 'marker_columns'));
			add_action("manage_{$taxonomy}_custom_column", array($this, 'taxonomy_custom_column'), 10, 3);
		}
	}

	function taxonomy_form_custom_field($term) {
		$term_marker_id = $this->get_term_marker_id($term->term_id);
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="marker_id"><?php _e('Marker', 'jeo'); ?></label>
			</th>
			<td>
				<?php
				$markers = $this->get_markers();
				if($markers) : ?>
					<select name="term_meta[marker_id]" id="marker_id">
						<option value=""><?php _e('Default', 'jeo'); ?></option>
						<?php foreach($markers as $marker) : ?>
							<option value="<?php echo $marker->ID; ?>" <?php if($term_marker_id == $marker->ID) echo 'selected'; ?>><?php echo apply_filters('post_title', $marker->post_title); ?></option>
						<?php endforeach; ?>
					</select> <a href="post-new.php?post_type=<?php echo $this->post_type; ?>" target="_blank"><?php _e('Create a new marker', 'jeo'); ?></a><br />
					<span class="description"><?php _e('Select a marker', 'jeo'); ?></span>
				<?php else : ?>
					<span class="description"><?php _e('You don\'t have custom markers yet.', 'jeo'); ?> <a href="post-new.php?post_type=<?php echo $this->post_type; ?>" target="_blank"><?php _e('Create your first here!', 'jeo'); ?></a></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	function taxonomy_form_save($term_id) {
		if (isset($_POST['term_meta'])) {
			$t_id = $term_id;
			$term_meta = get_option( "taxonomy_term_$t_id" );
			$cat_keys = array_keys( $_POST['term_meta'] );
			foreach ($cat_keys as $key){
				if (isset($_POST['term_meta'][$key])){
					$term_meta[$key] = $_POST['term_meta'][$key];
				}
			}
			update_option("taxonomy_term_$t_id", $term_meta);
		}
	}

	function taxonomy_custom_column($out, $column_name, $term_id) {
		switch($column_name) {
			case 'marker' :
				$term_meta = get_option("taxonomy_term_$term_id");
				$default_marker = get_option('jeo_default_marker_id');
				if($term_meta && $term_meta['marker_id']) {
					$marker_id = $term_meta['marker_id'];
				} else {
					$marker_id = $default_marker;
				}
				$image = $this->get_marker_image_url($marker_id);
				if($image)
					echo '<img src="' . $image . '" />';
				if($default_marker == $marker_id)
					echo '(' . __('default', 'jeo') . ')';
				break;
			default:
		}
	}

	/*
	 * Post relationship
	 */

	function connected_post_types() {
		$this->connected_post_types = jeo_get_mapped_post_types();
		return $this->connected_post_types;
	}

	function setup_post_type_relationship() {
		add_action('admin_footer', array($this, 'relationship_init_meta_box'));
		add_action('add_meta_boxes', array($this, 'relationship_add_meta_box'));
		add_action('save_post', array($this, 'relationship_save_post_data'));
	}

	function relationship_init_meta_box() {
		wp_enqueue_style('jeo-markericons', get_template_directory_uri() . '/inc/markericons/markericons.css');
	}

	function relationship_add_meta_box() {
		$markers = $this->get_markers();
		if(!$markers)
			return false;
		$post_types = $this->connected_post_types();
		foreach($post_types as $post_type) {
			add_meta_box(
				'jeo_markericon_relationship',
				__('Custom marker', 'jeo'),
				array($this, 'relationship_inner_meta_box'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	function relationship_inner_meta_box($post) {
		$markers = $this->get_markers();
		$post_marker_id = get_post_meta($post->ID, 'marker_id', true);
		?>
		<div id="marker-icon-relationship-metabox">
			<h4><?php _e('Choose a custom marker for your content. If it\'s set to <em>auto</em> we\'ll try to find the marker based on categories or map markers.', 'jeo'); ?></h4>
			<?php if($markers) : ?>
				<ul id="markers-list" class="clearfix">
					<li>
						<label for="marker_0"><strong><?php _e('Auto', 'jeo'); ?></strong></label>
						<input type="radio" name="marker_id" id="marker_0" value="0" <?php if(!$post_marker_id) echo 'checked'; ?> />
					</li>
					<?php foreach($markers as $marker) : ?>
						<li>
							<label for="marker_<?php echo $marker->ID; ?>"><img src="<?php echo $this->get_marker_image_url($marker->ID); ?>" /></label>
							<input type="radio" name="marker_id" id="marker_<?php echo $marker->ID; ?>" value="<?php echo $marker->ID; ?>" <?php if($post_marker_id == $marker->ID) echo 'checked'; ?> />
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	function relationship_save_post_data($post_id) {
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (defined('DOING_AJAX') && DOING_AJAX)
			return;

		if (false !== wp_is_post_revision($post_id))
			return;

		if(isset($_POST['marker_id']) && $_POST['marker_id'])
			update_post_meta($post_id, 'marker_id', $_POST['marker_id']);
		else
			delete_post_meta($post_id, 'marker_id');
	}

	/*
	 * Send filter to jeo markers
	 */
	function setup_post_marker_icon() {
		add_filter('jeo_marker_icon', array($this, 'post_marker_icon'), 1, 2);
	}

	function post_marker_icon($marker, $post) {
		return $this->get_marker_formatted($post->ID);
	}

}

$jeo_marker_icons = new JEO_Marker_Icons();

/*
 * Marker icons functions api
 */

function jeo_get_markers() {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_markers();
}

function jeo_get_marker($marker_id) {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_marker($marker_id);
}

function jeo_get_marker_image_url($marker_id) {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_marker_image_url($marker_id);
}

function jeo_get_marker_formatted($marker_id) {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_marker_formatted($marker_id);
}

function jeo_get_term_marker_id($term_id) {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_term_marker_id($term_id);
}

function jeo_get_post_marker_id($post_id) {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_post_marker_id($post_id);
}

function jeo_get_default_marker_id() {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_default_marker_id();
}

function jeo_get_default_marker() {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_default_marker();
}

function jeo_formatted_default_marker() {
	global $jeo_marker_icons;
	return $jeo_marker_icons->get_marker_formatted();
}