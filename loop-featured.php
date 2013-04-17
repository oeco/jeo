<?php if(have_posts()) : ?>
	<section class="posts-section featured">
		<?php mappress_map(null, false); ?>
		<div class="container">
			<div class="eleven columns">
				<h2><?php _e('Featured', 'mappress'); ?></h2>
			</div>
			<div class="four columns">
				<div class="featured-content">
					<ul class="featured-list">
						<?php $i = 0; while(have_posts()) : the_post(); $i++; ?>
							<?php if($i >= 2) continue; ?>
							<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
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
										<?php echo mappress_find_post_on_map_button(); ?>
										<a href="<?php the_permalink(); ?>"><?php _e('Read more', 'mappress'); ?></a>
									</aside>
								</article>
							</li>
						<?php endwhile; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>