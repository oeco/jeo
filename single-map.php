<?php get_header(); ?>

<?php mappress_map(); ?>

<?php query_posts(''); ?>
	<div class="section-title">
		<div class="container">
			<div class="twelve columns">
				<h2><?php _e('Latest articles on', 'mappress'); ?> <?php the_title(); ?></H2>
			</div>
		</div>
	</div>
	<?php get_template_part('loop'); ?>
<?php wp_reset_query(); ?>

<?php get_footer(); ?>