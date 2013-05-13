<?php

/*
 * MapPress
 * Featured content
 */

class MapPress_Featured {

	var $post_types = array('post');

	var $featured_var = 'mappress_featured';

	var $featured_meta = '_mappress_featured';

	function __construct() {
		$this->set_post_types();
		$this->setup();
	}

	function set_post_types() {
		$this->post_types = apply_filters('mappress_featured_post_types', $this->post_types);
		return $this->post_types;
	}

	function setup() {
		add_action('init', array($this, 'query_var'));
		add_action('add_meta_boxes', array($this, 'add_metabox'));
		add_action('save_post', array($this, 'save'));
	}

	function query_var() {

		global $wp;
		$wp->add_query_var($this->featured_var);

		add_action('pre_get_posts', array($this, 'wp_query'));
	}

	function wp_query($query) {
		$query->query_vars = $this->verify_query($query->query_vars);
		return $query;
	}

	function verify_query($query) {
		if($query[$this->featured_var]) {
			$query = $this->query($query);
			add_filter('mappress_marker_query', array($this, 'query'));
		}
		return $query;
	}

	function query($query) {
		if(!$query['meta_query']) 
			$query['meta_query'] = array();

		$query['meta_query'][] = array(
			'key' => '_mappress_featured',
			'value' => 1
		);
		return $query;
	}

	function add_metabox() {
		foreach($this->post_types as $post_type) {
			add_meta_box(
				'featured-metabox',
				__('Featured', 'mappress'),
				array($this, 'box'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	function box($post = false) {
		$featured = ($post) ? $this->is_featured($post->ID) : false;
		?>
		<div class="featured-box">
			<input type="checkbox" name="featured_content" id="featured_content" value="1" <?php if($featured) echo 'checked'; ?> />
			<label for="featured_content"><?php _e('Featured content', 'mappress'); ?></label>
		</div>
		<?php
	}

	function save($post_id) {

		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (defined('DOING_AJAX') && DOING_AJAX)
			return;

		if (wp_is_post_revision($post_id) !== false)
			return;

		if(isset($_REQUEST['featured_content']) && $_REQUEST['featured_content'])
			update_post_meta($post_id, $this->featured_meta, $_REQUEST['featured_content']);
		else
			delete_post_meta($post_id, $this->featured_meta);
	}

	function is_featured($post_id) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;
		return get_post_meta($post_id, $this->featured_meta, true);
	}

}

$featured = new MapPress_Featured;

function mappress_is_featured($post_id = false) {
	global $featured;
	return $featured->is_featured($post_id);
}