<?php get_header(); ?>

<?php if(have_posts()) : the_post(); ?>

	<?php jeo_map(); ?>

	<article id="content" class="single-post">
		<header class="single-post-header" class="clearfix">
			<div class="container">
				<div class="eight columns">
					<?php the_category(); ?>
					<h1><?php the_title(); ?></h1>
				</div>
				<div class="three columns offset-by-one">
					<div class="post-meta">
						<p class="author"><span class="lsf">&#xE137;</span> <?php _e('by', 'jeo'); ?> <?php the_author(); ?></p>
						<p class="date"><span class="lsf">&#xE12b;</span> <?php the_date(); ?></p>
					</div>
				</div>
				</div>
			</div>
		</header>
		<section class="content">
			<div class="container">
				<div class="eight columns">
					<?php the_content(); ?>
				</div>
				<div class="three columns offset-by-one">
					<aside id="sidebar">
						<ul class="widgets">
							<?php dynamic_sidebar('post'); ?>
						</ul>
					</aside>
				</div>
			</div>
		</section>
	</article>

<?php endif; ?>

<?php get_footer(); ?>