<?php get_header(); ?>

<section id="content" class="not-found">
	<header class="single-post-header">
		<div class="container">
			<div class="twelve columns">
				<h1><?php _e('404 - Page not found', 'jeo'); ?></h1>
			</div>
		</div>
	</header>
	<div class="container">
		<div class="eight columns">
			<p><?php _e('Trying searching:', 'jeo'); ?></p>
			<?php get_search_form(); ?>
		</div>
		<div class="three columns offset-by-one">
			<aside id="sidebar">
				<ul class="widgets">
					<?php dynamic_sidebar('general'); ?>
				</ul>
			</aside>
		</div>
	</div>
</section>

<?php get_footer(); ?>