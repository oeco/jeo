<?php
	$mapConf = array('postID' => $post->ID); // default;
	if(is_post_type_archive('map')) {
		$mapConf['disableMarkers'] = true;
		$mapConf['disableHash'] = true;
		$mapConf['disableInteraction'] = true;
	}
	$mapConf = json_encode($mapConf);
?>
<div class="map-container"><div id="map_<?php echo $post->ID; ?>" class="map"></div></div>
<script type="text/javascript">mappress(<?php echo $mapConf; ?>);</script>