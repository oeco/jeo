<?php

/*
 * JEO embed tool
 */

class JEO_Embed {

	var $query_var = 'jeo_embed';
	var $slug = 'embed';

	function __construct() {
		add_filter('query_vars', array(&$this, 'query_var'));
		add_action('generate_rewrite_rules', array(&$this, 'generate_rewrite_rule'));
		add_action('template_redirect', array(&$this, 'template_redirect'));
	}

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

			// Set embed map
			if(isset($_GET['map_id'])) {
				jeo_set_map(get_post($_GET['map_id']));
			} else {
				$maps = get_posts(array('post_type' => 'map', 'posts_per_page' => 1));
				if($maps) {
					jeo_set_map(array_shift($maps));
				} else {
					exit;
				}
			}

			// Set tax
			if(isset($_GET['tax'])) {
				global $wp_query;
				$wp_query->set('tax_query', array(
					array(
						'taxonomy' => $_GET['tax'],
						'field' => 'slug',
						'terms' => $_GET['term']
					)
				));
			}

			add_filter('show_admin_bar', '__return_false');
			do_action('jeo_before_embed');
			$this->template();
			do_action('jeo_after_embed');
			exit;
		}
	}

	function template() {
		wp_enqueue_style('jeo-embed', get_template_directory_uri() . '/inc/css/embed.css');
		get_template_part('content', 'embed');
		exit;
	}

	function get_embed_url($vars = array()) {
		$query = http_build_query($vars);
		return home_url('/' . $this->slug . '/?' . $query);
	}
}

$jeo_embed = new JEO_Embed();

function jeo_get_embed_url($vars = array()) {
	global $jeo_embed;
	return $jeo_embed->get_embed_url($vars);
}