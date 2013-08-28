<?php get_header(); ?>

<?php jeo_featured(); ?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h1><?php _e('Search results for:', 'jeo'); ?> <?php echo $_GET['s']; ?></h1>
		</div>
	</div>
</div>
<?php get_template_part('loop'); ?>

<?php get_footer(); ?>