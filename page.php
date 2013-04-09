<?php get_header(); ?>

<?php if(have_posts()) : the_post(); ?>
	<section id="content" class="single-post">
		<header class="post-header">
			<div class="container">
				<div class="twelve columns">
					<h1><?php the_title(); ?></h1>
				</div>
			</div>
		</header>
		<div class="container">
			<div class="twelve columns">
				<?php the_content(); ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php get_footer(); ?>