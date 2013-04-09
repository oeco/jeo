<?php get_header(); ?>

<?php mappress_get_map_featured(); ?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h2><?php _e('Latest articles', 'mappress'); ?></H2>
		</div>
	</div>
</div>
<?php get_template_part('loop'); ?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h2><?php _e('Featured articles', 'mappress'); ?></H2>
		</div>
	</div>
</div>
<?php get_template_part('loop', 'featured'); ?>

<?php get_footer(); ?>