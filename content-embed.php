<html id="map-embed" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<title><?php
	global $page, $paged;

	wp_title( '|', true, 'right' );

	bloginfo( 'name' );

	$site_description = get_bloginfo('description', 'display');
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . __('Page', 'jeo') . max($paged, $page);

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/img/favicon.ico" type="image/x-icon" />
<?php wp_head(); ?>
</head>
<body <?php body_class(get_bloginfo('language')); ?>>

	<header id="embed-header">
		<h1><a href="<?php echo home_url('/'); ?>" target="_blank"><?php bloginfo('name'); ?><span>&nbsp;</span></a></h1>
	</header>

	<div class="map-container"><div id="map_embed" class="map"></div></div>

	<input type="hidden" id="latitude" />
	<input type="hidden" id="longitude" />
	<input type="hidden" id="zoom" />

	<?php
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
	$json_conf = json_encode($conf);
	?>

	<script type="text/javascript">
		(function($) {
			jeo(<?php echo $json_conf; ?>, function(map) {

				var track = function() {
					var c = map.getCenter();
					$('#latitude').val(c.lat);
					$('#longitude').val(c.lng);
					$('#zoom').val(map.getZoom());
				}

				map.on('zoomend', track);
				map.on('dragend', track);

			});

		})(jQuery);
	</script>

<?php wp_footer(); ?>
</body>
</html>