<?php query_posts(array('jeo_featured' => 1, 'posts_per_page' => 4)); ?>
	<?php if(have_posts() && get_post_type(jeo_get_the_ID()) == 'map') : ?>
		<section id="featured-content" class="posts-section featured">
			<?php $map_id = jeo_map(null, false, true); ?>
			<div class="container">
				<div class="eleven columns">
					<h2><?php _e('Featured', 'jeo'); ?></h2>
				</div>
				<div class="four columns">
					<div class="featured-content">
						<ul class="featured-list">
							<?php $class = 'slider-item'; ?>
							<?php $i = 0; while(have_posts()) : the_post(); ?>
								<?php $geometry = jeo_get_element_geometry_data(); ?>
								<?php if(!$geometry) continue; ?>
								<?php $active = $i >= 1 ? '' : ' active'; ?>
								<li id="post-<?php the_ID(); ?>" <?php post_class($class . ' ' . $active); ?> <?php echo $geometry; ?> <?php echo jeo_element_max_zoom(); ?>>
									<article id="post-<?php the_ID(); ?>">
										<header class="post-header">
											<h3><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>
											<p class="meta">
												<span class="date"><?php echo get_the_date(); ?></span>
												<span class="author"><?php _e('by', 'jeo'); ?> <?php the_author(); ?></span>
											</p>
										</header>
										<section class="post-content">
											<div class="post-excerpt">
												<?php the_excerpt(); ?>
											</div>
										</section>
										<aside class="actions">
											<a href="<?php the_permalink(); ?>"><?php _e('Read more', 'jeo'); ?></a>
										</aside>
									</article>
								</li>
							<?php $i++; endwhile; ?>
						</ul>
					</div>
					<div class="slider-controllers">
						<ul>
							<?php $i = 0; while(have_posts()) : the_post(); $i++; ?>
								<?php if(!jeo_get_element_geometry_data()) continue; ?>
								<li class="slider-item-controller" data-postid="post-<?php the_ID(); ?>" title="<?php _e('Go to', 'jeo'); ?> <?php the_title(); ?>"><?php _e('Go to', 'jeo'); ?> <?php the_title(); ?></li>
							<?php endwhile; ?>
						</ul>
				</div>
			</div>
		</section>
		<script type="text/javascript">
			jeo.ui.featuredSlider('featured-content', '<?php echo $map_id; ?>');
		</script>
	<?php endif; ?>
<?php wp_reset_query(); ?>