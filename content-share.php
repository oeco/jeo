<?php get_header(); ?>

<?php
$page_title = __('Share a map', 'jeo');
$map = false;
if($_GET['map_id']) {
	$map = get_post($_GET['map_id']);
	if($map && get_post_type($map->ID) == 'map')
		$page_title = __('Share', 'jeo') . ' ' . get_the_title($map->ID);
	else
		$map = false;
}

// All maps
$maps = get_posts(array('post_type' => 'map', 'posts_per_page' => -1));

// Single map
if(!$map && count($maps) <= 1) {
	$map = array_shift($maps);
	$page_title = __('Share the map', 'jeo');
}

// check for layer count

$allow_layers = true;
$layers = false;

if($allow_layers) {
	if(isset($_GET['layers'])) {
		$layers = explode(',', $_GET['layers']);
	} elseif($map) {
		$layers = jeo_get_map_layers($map->ID);
		if(count($layers) <= 1) {
			$layers = false;
		}
	}
}

// post
$post_id = false;
if(isset($_GET['p']))
	$post_id = $_GET['p'];

// share url
if($post_id) {
	$share_url = jeo_get_share_url(array('p' => $post_id));
} else {
	$share_url = jeo_get_share_url();
}
?>

<section id="content" class="share-page">
	<header class="page-header">
		<div class="container">
			<div class="twelve columns">
				<h1><?php echo $page_title; ?></h1>
			</div>
		</div>
	</header>
	<div id="jeo-share-widget">
		<div id="configuration">
			<div class="container row">
				<?php

				if(count($maps) > 1 || ($map && $layers)) :
					?>
					<div class="section layer three columns">
						<div class='inner'>
							<?php if(!$map) : ?>
								<h4>
									<?php _e('Choose a map', 'jeo'); ?>
									<a class='tip' href='#'>
										?
										<span class="popup arrow-left">
											<?php _e('Choose any map from the list', 'jeo'); ?>
										</span>
									</a>
								</h4>
								<div id='maps'>
									<select id="map-select" data-placeholder="<?php _e('Select a map', 'jeo'); ?>" class="chzn-select">
										<?php foreach($maps as $map) : ?>
											<option value="<?php echo $map->ID; ?>"><?php echo get_the_title($map->ID); ?></option>
										<?php endforeach; ?>
									</select>
									<?php if($allow_layers) : ?>
										<a href="#" class="select-map-layers" style="display:block;margin-top:5px;"><?php _e('Select layers from this map', 'jeo'); ?></a>
									<?php endif; ?>
								</div>
							<?php elseif($map && $layers) : ?>
								<?php $map_id = $map->ID; ?>
								<h4>
									<?php if(!isset($_GET['layers'])) : ?>
										<?php echo __('Select layers', 'jeo'); ?>
									<?php else : ?>
										<?php _e('Select layers', 'jeo'); ?>
									<?php endif; ?>
									<a class="tip" href="#">
										?
										<span class="popup arrow-left">
											<?php _e('Choose any layers from the list', 'jeo'); ?>
										</span>
									</a>
								</h4>
								<div id="maps">
									<?php if($layers) : ?>
										<select id="layers-select" data-placeholder="<?php _e('Select layers', 'jeo'); ?>" data-mapid="<?php echo $map_id; ?>" class="chzn-select" multiple>
											<?php foreach($layers as $layer) : ?>
												<?php
												if(!is_array($layer)) :
													$l = array('id' => $layer, 'title' => $layer);
													$layer = $l;
												endif;
												?>
												<option value="<?php echo $layer['id']; ?>" selected><?php if($layer['title']) : echo $layer['title']; else : echo $layer['id']; endif; ?></option>
											<?php endforeach; ?>
										</select>
									<?php endif; ?>
									<a class="clear-layers" href="#"><?php _e('Back to default layer configuration', 'jeo'); ?></a>
									<?php if(count($maps) > 1) : ?>
										<p><a class="button" href="<?php echo $share_url; ?>"><?php _e('View all maps', 'jeo'); ?></a></p>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<h4>&nbsp;</h4>
								<input type="hidden" id="map_id" name="map_id" value="<?php echo $map->ID; ?>" />
								<p><a class="button" href="<?php echo $share_url; ?>"><?php _e('View all maps', 'jeo'); ?></a></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$taxonomies = jeo_get_share_widget_taxonomies();
				?>

				<div class="section two columns">
					<div class="inner">
						<h4>
							<?php _e('Filter content', 'jeo'); ?>
							<a class="tip" href="#">
								?
								<span class="popup arrow-left">
									<?php _e('Filter the content displayed on the map through our options', 'jeo'); ?>
								</span>
							</a>
						</h4>
						<div id="map-content">
							<select id="content-select" data-placeholder="<?php _e('Select content', 'jeo'); ?>" class="chzn-select">
								<?php
								if(isset($_GET['p'])) :
									$post = get_post($_GET['p']);
									if($post) : ?>
										<optgroup label="<?php _e('Selected content', 'jeo'); ?>">
											<option value="post&<?php echo $post->ID; ?>" selected><?php echo get_the_title($post->ID); ?></option>
										</optgroup>
									<?php endif; ?>
								<?php endif; ?>
								<optgroup label="<?php _e('General content', 'jeo'); ?>">
									<option value="latest"><?php if(!isset($_GET['map_id'])) _e('Content from the map', 'jeo'); else _e('Latest content', 'jeo'); ?></option>
									<option value="map-only"><?php _e('No content (map only)', 'jeo'); ?></option>
								</optgroup>
								<?php foreach($taxonomies as $taxonomy) :
									$taxonomy = get_taxonomy($taxonomy);
									if($taxonomy) :
										$terms = get_terms($taxonomy->name);
										if($terms) :
											?>
											<optgroup label="<?php echo __('By', 'jeo') . ' ' . strtolower($taxonomy->labels->name); ?>">
												<?php foreach($terms as $term) : ?>
													<option value="tax_<?php echo $taxonomy->name; ?>&<?php echo $term->slug; ?>"><?php echo $term->name; ?></option>
												<?php endforeach; ?>
											</optgroup>
										<?php
										endif;
									endif;
								endforeach; ?>
							</select>
						</div>
					</div>
				</div>

				<div class='section size three columns'>
					<div class='inner'>
						<h4>
							<?php _e('Width & Height', 'jeo'); ?>
							<a class='tip' href='#'>
								?
								<span class="popup arrow-left">
									<?php _e('Select the width and height proportions you would like to embed to be.', 'jeo'); ?>
								</span>
							</a>
						</h4>
						<ul id='sizes' class='sizes clearfix'>
							<li><a href='#' data-size='small' data-width='480' data-height='300'><?php _e('Small', 'jeo'); ?></a></li>
							<li><a href='#' data-size='medium' data-width='600' data-height='400'><?php _e('Medium', 'jeo'); ?></a></li>
							<li><a href='#' data-size='large' data-width='960' data-height='480' class='active'><?php _e('Large', 'jeo'); ?></a></li>
						</ul>
					</div>
				</div>

				<div class='section output two columns'>
					<div class='inner'>
						<h4>
							<div class='popup arrow-right'>
							</div>
							<?php _e('HTML Output', 'jeo'); ?>
							<a class='tip' href='#'>
								?
								<span class="popup arrow-left">
									<?php _e('Copy and paste this code into an HTML page to embed with it\'s current settings and location', 'jeo'); ?>
								</span>
							</a>
						</h4>
						<textarea id="output"></textarea>
                        <div class="sub-inner">                        
                            <h5>
                                <div class='popup arrow-right'>
                                </div>
                                <?php _e('URL', 'jeo'); ?>
                                <a class='tip' href='#'>
                                    ?
                                    <span class="popup arrow-left">
                                        <?php _e('Get the original to use as a link or a custom embed.', 'jeo'); ?>
                                    </span>
                                </a>
                            </h5>
                            <input type="text" id="url-output" />
                        </div>
					</div>
				</div>

				<div class="section social two columns">
					<div class="inner">
						<h4>
							<div class="popup arrow-right">
							</div>
							<?php _e('Share', 'jeo'); ?>
							<a class="tip" href="#">
								?
								<span class="popup arrow-left">
									<?php _e('Share this map, with it\'s current settings and location, on your social network', 'jeo'); ?>
								</span>
							</a>
						</h4>
					</div>
					<p id="jeo-share-social" class="links">
						<a href="#" class="facebook"><span class="lsf">&#xE047;</span></a>
						<a href="#" class="twitter"><span class="lsf">&#xE12f;</span></a>
					</p>
				</div>

			</div>
		</div>

		<div class="container">
			<div class="twelve columns">
				<h2 class="preview-title"><?php _e('Map preview', 'jeo'); ?></h2>
			</div>
		</div>
		<div id="embed-container">
			<div class="content" id="widget-content">
				<!-- iframe goes here -->
			</div>
		</div>

	</div>
</section>

<script type="text/javascript">
	jQuery(document).ready(function($) { 
		jeo_share_widget.controls();
	});
</script>

<?php get_footer(); ?>