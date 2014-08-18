<?php get_header(); ?>

<?php jeo_map(); ?>

<?php
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
query_posts(array(
	'post_type' => jeo_get_mapped_post_types(),
	'paged' => $paged,
	's' => isset($_GET['s']) ? $_GET['s'] : null
));
if(have_posts()) :
	?>
		<div class="section-title">
			<div class="container">
				<div class="twelve columns">
					<h2><?php _e('Latest articles on', 'jeo'); ?> <?php the_title(); ?></H2>
				</div>
			</div>
		</div>
		<?php get_template_part('loop'); ?>
<?php
endif;
wp_reset_query();
?>

<?php get_footer(); ?>