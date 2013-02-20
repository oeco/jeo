<?php
$data = get_post_meta($post->ID, 'mapgroup_data', true);
$main_maps = $more_maps = array();
// separate main maps from "more" maps
foreach($data['maps'] as $map) {
	if(!isset($map['more']))
		$main_maps[] = $map;
	else
		$more_maps[] = $map;
}
?>
<div class="mapgroup-container">
	<div id="mapgroup-<?php echo $post->ID; ?>" class="mapgroup">
		<ul class="map-nav">
			<?php
			$i = 0;
			foreach($main_maps as $map) :
				$post = get_post($map['id']);
				setup_postdata($post);
				?>
				<li><a href="#" data-map="map_<?php the_ID(); ?>" <?php if($i == 0) echo 'class="active"'; ?>><?php the_title(); ?></a></li>
				<?php
				wp_reset_postdata();
				$i++;
			endforeach; ?>
			<?php if($more_maps) : ?>
				<li class="more-tab">
					<a href="#" class="toggle-more"><?php _e('More...', 'infoamazonia'); ?></a>
					<ul class="more-maps-list">
						<?php foreach($more_maps as $map) :
							$post = get_post($map['id']);
							setup_postdata($post);
							?>
							<li class="more-item"><a href="#" data-map="map_<?php the_ID(); ?>" <?php if($i == 0) echo 'class="active"'; ?>><?php the_title(); ?></a></li>
							<?php
							wp_reset_postdata();
						endforeach; ?>
					</ul>
				</li>
			<?php endif; ?>
		</ul>
		<div class="map-container">
			<div id="mapgroup_<?php echo $post->ID; ?>_map" class="map">
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	var group = mappress.group(<?php echo $post->ID; ?>);
</script>