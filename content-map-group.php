<?php
global $map, $mapgroup_id;
mappress_setup_mapgroupdata($map);
$data = get_post_meta($mapgroup_id, 'mapgroup_data', true);
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
	<div id="mapgroup-<?php echo $mapgroup_id; ?>" class="mapgroup">
		<ul class="map-nav">
			<?php
			foreach($main_maps as $map) :
				$post = get_post($map['id']);
				setup_postdata($post);
				?>
				<li><a href="<?php the_permalink(); ?>" data-map="<?php the_ID(); ?>"><?php the_title(); ?></a></li>
				<?php
				mappress_reset_mapdata();
			endforeach; ?>
			<?php if($more_maps) : ?>
				<li class="more-tab">
					<a href="#" class="toggle-more"><?php _e('More...', 'mappress'); ?></a>
					<ul class="more-maps-list">
						<?php foreach($more_maps as $map) :
							$post = get_post($map['id']);
							setup_postdata($post);
							?>
							<li class="more-item"><a href="<?php the_permalink(); ?>" data-map="<?php the_ID(); ?>"><?php the_title(); ?></a></li>
							<?php
							mappress_reset_mapdata();
						endforeach; ?>
					</ul>
				</li>
			<?php endif; ?>
		</ul>
		<div class="map-container">
			<div id="mapgroup_<?php echo $mapgroup_id; ?>_map" class="map">
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	var group = mappress.group(<?php echo $mapgroup_id; ?>);
</script>