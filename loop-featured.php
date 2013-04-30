<?php if(have_posts()) : ?>
	<section id="featured-content" class="posts-section featured">
		<?php $map_id = mappress_map(null, false, true); ?>
		<div class="container">
			<div class="eleven columns">
				<h2><?php _e('Featured', 'mappress'); ?></h2>
			</div>
			<div class="four columns">
				<div class="featured-content">
					<ul class="featured-list">
						<?php $class = 'slider-item'; ?>
						<?php $i = 0; while(have_posts()) : the_post(); ?>
							<?php $geometry = mappress_element_geometry_data(); ?>
							<?php if(!$geometry) continue; ?>
							<?php $active = $i >= 1 ? '' : ' active'; ?>
							<li id="post-<?php the_ID(); ?>" <?php post_class($class . ' ' . $active); ?> <?php echo $geometry; ?> <?php echo mappress_element_max_zoom(); ?>>
								<article id="post-<?php the_ID(); ?>">
									<header class="post-header">
										<h3><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>
										<p class="meta">
											<span class="date"><?php echo get_the_date(); ?></span>
											<span class="author"><?php _e('by', 'mappress'); ?> <?php the_author(); ?></span>
										</p>
									</header>
									<section class="post-content">
										<div class="post-excerpt">
											<?php the_excerpt(); ?>
										</div>
									</section>
									<aside class="actions">
										<a href="<?php the_permalink(); ?>"><?php _e('Read more', 'mappress'); ?></a>
									</aside>
								</article>
							</li>
						<?php $i++; endwhile; ?>
					</ul>
				</div>
				<div class="slider-controllers">
					<ul>
						<?php $i = 0; while(have_posts()) : the_post(); $i++; ?>
							<?php if(!mappress_element_geometry_data()) continue; ?>
							<li class="slider-item-controller" data-postid="post-<?php the_ID(); ?>" title="<?php _e('Go to', 'mappress'); ?> <?php the_title(); ?>"><?php _e('Go to', 'mappress'); ?> <?php the_title(); ?></li>
						<?php endwhile; ?>
					</ul>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		mappress.ui.featuredSlider('featured-content', '<?php echo $map_id; ?>');
	</script>
<?php endif; ?>