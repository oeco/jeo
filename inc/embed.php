<?php

/*
 * MapPress embed tool
 */

class MapPress_Embed {

	function __construct() {
		add_filter('query_vars', array(&$this, 'query_var'));
		add_action('generate_rewrite_rules', array(&$this, 'generate_rewrite_rule'));
		add_action('template_redirect', array(&$this, 'template_redirect'));
	}

	function query_var($vars) {
		$vars[] = 'embed';
		return $vars;
	}

	function generate_rewrite_rule($wp_rewrite) {
		$widgets_rule = array(
			'embed$' => 'index.php?embed=1'
		);
		$wp_rewrite->rules = $widgets_rule + $wp_rewrite->rules;
	}

	function template_redirect() {
		if(get_query_var('embed')) {
			add_filter('show_admin_bar', '__return_false');
			do_action('mappress_before_embed');
			$this->template();
			do_action('mappress_after_embed');
			exit;
		}
	}

	function template() {
		get_template_part('content', 'embed');
		exit;
	}

	function get_embed_url($vars = array()) {
		$query = http_build_query($vars);
		return home_url('/embed/?' . $query);
	}
}

$mappress_embed = new MapPress_Embed();

function mappress_get_embed_url($vars = array()) {
	global $mappress_embed;
	return $mappress_embed->get_embed_url($vars);
}