<?php get_header(); ?>

<?php
if(is_front_page()) {
	$options = mappress_get_options();
	if(!$options || (isset($options['front_page']) && $options['front_page']['front_page_map'] == 'latest'))
		mappress_featured();
	else
		get_template_part('content', 'featured');
} else {
	mappress_featured();
}
?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h2><?php _e('Latest articles', 'mappress'); ?></H2>
		</div>
	</div>
</div>
<?php get_template_part('loop'); ?>

<?php get_footer(); ?>