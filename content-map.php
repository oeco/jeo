<div class="map-container">
	<div id="map_<?php echo mappress_map_id(); ?>" class="map"></div>
	<?php if(is_single()) : ?>
		<?php if(mappress_has_marker_location()) : ?>
			<div class="highlight-point transition has-end" data-end="1300"></div>
		<?php endif; ?>
	<?php endif; ?>
</div>
<script type="text/javascript">mappress(<?php echo mappress_map_conf(); ?>);</script>