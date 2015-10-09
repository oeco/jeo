<?php

/*
 * JEO embed tool
 */

class JEO_Embed {

	var $query_var = 'jeo_map_embed';
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
		return apply_filters('jeo_embed_url', home_url('/' . $this->slug) . '/?' . $query);
	}

	function get_map_conf() {
		$conf = array();
		$conf['containerID'] = 'map_embed';
		$conf['disableHash'] = true;
		$conf['mainMap'] = true;
		if(isset($_GET['map_id'])) {
			$conf['postID'] = $_GET['map_id'];
		} else {
			$conf['postID'] = jeo_get_the_ID();
		}
		if(isset($_GET['map_only'])) {
			$conf['disableMarkers'] = true;
		}
		if(isset($_GET['layers'])) {
			$conf['layers'] = explode(',', $_GET['layers']);
			if(isset($conf['postID']))
				unset($conf['postID']);
		}
		if(isset($_GET['zoom'])) {
			$conf['zoom'] = $_GET['zoom'];
		}
		if(isset($_GET['lat']) && isset($_GET['lon'])) {
			$conf['center'] = array($_GET['lat'], $_GET['lon']);
			$conf['forceCenter'] = true;
		}
		$conf['disable_mousewheel'] = false;

		$conf = apply_filters('jeo_map_embed_conf', $conf);

		return apply_filters('jeo_map_embed_geojson_conf', json_encode($conf));
	}
}

$GLOBALS['jeo_embed'] = new JEO_Embed();

function jeo_get_embed_url($vars = array()) {
	return $GLOBALS['jeo_embed']->get_embed_url($vars);
}

function jeo_get_map_embed_conf() {
	return $GLOBALS['jeo_embed']->get_map_conf();
}
