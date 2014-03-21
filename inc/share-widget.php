<?php

/*
 * JEO Share Widget
 */

class JEO_Share_Widget {

	var $query_var = 'jeo_share';
	var $slug = 'share';

	// Tool settings

	var $taxonomies = array('category', 'post_tag');

	function __construct() {
		add_filter('jeo_settings_tabs', array($this, 'admin_settings_tab'));
		add_filter('jeo_settings_form_sections', array($this, 'admin_settings_form_section'), 10, 2);
		// add_filter('wp_title', array($this, 'wp_title')); FIX

		if($this->is_enabled()) {
			add_filter('query_vars', array($this, 'query_var'));
			add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rule'));
			add_action('template_redirect', array($this, 'template_redirect'));
			if(!apply_filters('jeo_disable_share_map_menu', false)) {
				add_filter('wp_nav_menu_items', array($this, 'nav'), 10, 2);
			}
		}
	}

	function wp_title($title) {
		global $wp_query;
		if(isset($wp_query->query[$this->query_var])) {
			$title = __('Share', 'jeo');
		}
		return $title;
	}

	/*
	 * Admin settings
	 */

	function is_enabled() {
		$options = jeo_get_options();
		return ($options && isset($options['share-widget']) && $options['share-widget']['enable_share_widget']);
	}

	function admin_settings_tab($tabs = array()) {
		$tabs['share-widget'] = __('Map embed tool', 'jeo');
		return $tabs;
	}

	function admin_settings_form_section($sections = array(), $page_slug) {

		$taxonomies = get_taxonomies(array('public' => true));

		foreach($taxonomies as $taxonomy) {
			$taxonomies[$taxonomy] = $taxonomy;
		}

		$default_taxonomies = $this->taxonomies;

		$section = array(
			'pageslug' => $page_slug,
			'tabslug' => 'share-widget',
			'id' => 'share-widget',
			'title' => __('Embed tool settings', 'jeo'),
			'description' => __('Setup your embed tool. Here you can enable or disable it and set which taxonomies and maps are allowed to share.', 'jeo'),
			'fields' => array(
				array(
					'id' => 'enable-share-widget',
					'title' => __('Enable embed tool', 'jeo'),
					'description' => __('Select if you\'d like to enable your embed tool', 'jeo'),
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Enable', 'jeo')
				),
				array(
					'id' => 'share_widget_taxonomies',
					'title' => __('Filter taxonomies', 'jeo'),
					'description' => __('Choose the taxonomies that can be filtered as an embed result', 'jeo'),
					'type' => 'checkbox',
					'default' => $default_taxonomies,
					'label' => $taxonomies
				)
			)
		);

		if($this->is_enabled()) {
			$section['description'] = sprintf(__('Your embed tool is <strong>enabled</strong>! <a href="%s" target="_blank">Check it out</a>.', 'jeo'), $this->get_share_url()) . '<br/><br/>' . $section['description'];
		}

		$sections[] = $section;
		return $sections;
	}

	function get_options() {
		$options = jeo_get_options();
		if($options && isset($options['share-widget'])) {
			return $options['share-widget'];
		}
	}

	function get_taxonomies() {
		$options = $this->get_options();
		if(isset($options['share_widget_taxonomies'])) {
			$enabled = $options['share_widget_taxonomies'];
			$taxonomies = array();
			foreach($enabled as $tax => $val) {
				if($val) {
					$taxonomies[] = $tax;
				}
			}
			return $taxonomies;
		}
		return $this->taxonomies;
	}

	/*
	 * Tool
	 */

	function query_var($vars) {
		$vars[] = $this->query_var;
		return $vars;
	}

	function generate_rewrite_rule($wp_rewrite) {
		$widgets_rule = array(
			$this->slug . '$' => 'index.php?' . $this->query_var . '=1'
		);
		$wp_rewrite->rules = $widgets_rule + $wp_rewrite->rules;
	}

	function template_redirect() {
		if(get_query_var($this->query_var)) {
			$this->template();
			exit;
		}
	}

	function template() {

		wp_register_script('chosen', get_template_directory_uri() . '/lib/chosen.jquery.min.js', array('jquery'));

		wp_enqueue_script('jeo-share-widget', get_template_directory_uri() . '/inc/js/share-widget.js', array('jquery', 'underscore', 'chosen'), '1.5.6');

		wp_localize_script('jeo-share-widget', 'jeo_share_widget_settings', array(
			'baseurl' => jeo_get_embed_url(),
			'default_label' => __('default', 'jeo')
		));
		wp_enqueue_style('jeo-share-widget', get_template_directory_uri() . '/inc/css/share-widget.css', array(), '1.0');
		get_template_part('content', 'share');
		exit;

	}

	function nav($items, $args) {
		$share = '<li class="share' . ((get_query_var($this->query_var)) ? ' current_page_item' : '') . '"><a href="' . $this->get_share_url() . '">' . __('Share a map', 'jeo') . '</a></li>';
		return $items . $share;
	}

	// functions

	function get_share_url($vars = array()) {
		$query = http_build_query($vars);
		return apply_filters('jeo_share_url', home_url('/' . $this->slug . '/?' . $query));
	}
	
}

$jeo_share_widget = new JEO_Share_Widget();

function jeo_get_share_url($vars = array()) {
	global $jeo_share_widget;
	return $jeo_share_widget->get_share_url($vars);
}

function jeo_get_share_widget_taxonomies() {
	global $jeo_share_widget;
	return $jeo_share_widget->get_taxonomies();
}