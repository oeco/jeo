<?php

add_action('admin_footer', 'mapbox_metabox_init');
add_action('add_meta_boxes', 'mapbox_add_meta_box');
add_action('save_post', 'mapbox_save_postdata');

function mapbox_metabox_init() {
	// javascript stuff for the metabox
	wp_enqueue_script('mapbox-metabox', get_template_directory_uri() . '/metaboxes/mapbox/mapbox.js', array('jquery', 'jeo', 'jquery-ui-sortable'), '0.5.1');
	wp_enqueue_style('mapbox-metabox', get_template_directory_uri() . '/metaboxes/mapbox/mapbox.css', array(), '0.0.9.1');
}

function mapbox_add_meta_box() {
	// register the metabox
	add_meta_box(
		'mapbox', // metabox id
		__('Map setup', 'jeo'), // metabox title
		'mapbox_inner_custom_box', // metabox inner code
		'map', // post type
		'advanced', // metabox position (advanced to show on main area)
		'high' // metabox priority (kind of an ordering)
	);
}

function mapbox_inner_custom_box($post) {
	// get previous data if any
	$map_data = get_post_meta($post->ID, 'map_data', true);
	if(!isset($map_data['server']) || !$map_data['server'])
		$map_data['server'] = 'mapbox'; // default map service

	$map_conf = get_post_meta($post->ID, 'map_conf', true);

	?>
	<div id="mapbox-metabox">

		<h4><?php _e('Edit the default layer and fill the IDs of the maps to overlay layers of your map, in order of appearance', 'jeo'); ?></h4>
		<div class="layers-container">

			<?php
			if(isset($map_data['base_layer']))  {
				$select_base_layer = $map_data['base_layer']['type'];
				$base_layer_url = $map_data['base_layer']['url'];
			} else {
				$select_base_layer = 'openstreetmap';
				$base_layer_url = '';
			}
			?>

			<div>
				<?php _e('Select base layer', 'jeo'); ?>
				<select name="map_data[base_layer][type]" id="baselayer_drop_down">
					<option value="openstreetmap" <?php echo $select_base_layer == 'openstreetmap' ? ' selected="selected"' : ''; ?> >OpenStreetMap</option>
					<option value="mapquest_osm" <?php echo $select_base_layer == 'mapquest_osm' ? ' selected="selected"' : ''; ?> >Mapquest OpenStreetMap</option>
					<option value="mapquest_sat" <?php echo $select_base_layer == 'mapquest_sat' ? ' selected="selected"' : ''; ?> >Mapquest Satellite</option>
					<option value="stamen_toner" <?php echo $select_base_layer == 'stamen_toner' ? ' selected="selected"' : ''; ?> >Stamen Toner</option>
					<option value="stamen_watercolor" <?php echo $select_base_layer == 'stamen_watercolor' ? ' selected="selected"' : ''; ?>>Stamen Watercolor</option>
					<option value="stamen_terrain" <?php echo $select_base_layer == 'stamen_terrain' ? ' selected="selected"' : ''; ?> >Stamen Terrain <?php _e('(USA Only)','jeo'); ?></option>
					<option value="custom" <?php echo $select_base_layer == 'custom' ? ' selected="selected"' : ''; ?> ><?php _e('Custom','jeo'); ?></option>
					<option value="none" <?php echo $select_base_layer == 'none' ? ' selected="selected"' : ''; ?> ><?php _e('None','jeo'); ?></option>
				</select>
				<input type="text" name="map_data[base_layer][url]" id="baselayer_url_box" class="layer_title" size="40" placeholder="<?php _e('Enter layer URL', 'jeo'); ?>" value="<?php echo $base_layer_url; ?>" />
			</div>

			<p><a class="button-primary preview-map" href="#"><?php _e('Update preview', 'jeo'); ?></a></p>
		</div>
		<h3><?php _e('Preview map', 'jeo'); ?></h3>
		<div class="map-container">
			<div id="map_preview" class="map"></div>
		</div>
		<div class="map-settings clearfix">
			<h3><?php _e('Map settings', 'jeo'); ?></h3>
			<div class="current map-setting">
				<h4><?php _e('Currently viewing', 'jeo'); ?></h4>
				<table>
					<tr>
						<td><?php _e('Center', 'jeo'); ?></td>
						<td><span class="center"></span></td>
					</tr>
					<tr>
						<td><?php _e('Zoom', 'jeo'); ?></td>
						<td><span class="zoom"></span></td>
					</tr>
					<tr>
						<td><?php _e('East', 'jeo'); ?></td>
						<td><span class="east"></span></td>
					</tr>
					<tr>
						<td><?php _e('North', 'jeo'); ?></td>
						<td><span class="north"></span></td>
					</tr>
					<tr>
						<td><?php _e('South', 'jeo'); ?></td>
						<td><span class="south"></span></td>
					</tr>
					<tr>
						<td><?php _e('West', 'jeo'); ?></td>
						<td><span class="west"></span></td>
					</tr>
				</table>
			</div>
			<div class="centerzoom map-setting">
				<h4><?php _e('Map center & zoom', 'jeo'); ?></h4>
				<p><a class="button set-map-centerzoom"><?php _e('Set current as map center & zoom', 'jeo'); ?></a></p>
				<table>
					<tr>
						<td><?php _e('Center', 'jeo'); ?></td>
						<td><span class="center">(<?php if(isset($map_data['center'])) echo $map_data['center']['lat']; ?>, <?php if(isset($map_data['center'])) echo $map_data['center']['lon']; ?>)</span></td>
					</tr>
					<tr>
						<td><?php _e('Zoom', 'jeo'); ?></td>
						<td><span class="zoom"><?php if(isset($map_data['zoom'])) echo $map_data['zoom']; ?></span></td>
					</tr>
					<tr>
						<td><label for="min-zoom-input"><?php _e('Min zoom', 'jeo'); ?></label></td>
						<td>
							<input type="text" size="2" id="min-zoom-input" value="<?php if(isset($map_data['min_zoom'])) echo $map_data['min_zoom']; ?>" name="map_data[min_zoom]" />
							<a class="button set-min-zoom" href="#"><?php _e('Current', 'jeo'); ?></a>
						</td>
					</tr>
					<tr>
						<td><label for="max-zoom-input"><?php _e('Max zoom', 'jeo'); ?></label></td>
						<td>
							<input type="text" size="2" id="max-zoom-input" value="<?php if(isset($map_data['center'])) echo $map_data['max_zoom']; ?>" name="map_data[max_zoom]" />
							<a class="button set-max-zoom" href="#"><?php _e('Current', 'jeo'); ?></a>
						</td>
					</tr>
				</table>
				<input type="hidden" class="center-lat" name="map_data[center][lat]" value="<?php if(isset($map_data['center'])) echo $map_data['center']['lat']; ?>" />
				<input type="hidden" class="center-lon" name="map_data[center][lon]" value="<?php if(isset($map_data['center'])) echo $map_data['center']['lon']; ?>" />
				<input type="hidden" class="zoom" name="map_data[zoom]" value="<?php if(isset($map_data['zoom'])) echo $map_data['zoom']; ?>" />
			</div>
			<div class="pan-limits map-setting">
				<h4><?php _e('Pan limits', 'jeo'); ?></h4>
				<p><a class="button set-map-pan"><?php _e('Set current as map panning limits', 'jeo'); ?></a></p>
				<table>
					<tr>
						<td><?php _e('East', 'jeo'); ?></td>
						<td><span class="east"><?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['east']; ?></span></td>
					</tr>
					<tr>
						<td><?php _e('North', 'jeo'); ?></td>
						<td><span class="north"><?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['north']; ?></span></td>
					</tr>
					<tr>
						<td><?php _e('South', 'jeo'); ?></td>
						<td><span class="south"><?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['south']; ?></span></td>
					</tr>
					<tr>
						<td><?php _e('West', 'jeo'); ?></td>
						<td><span class="west"><?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['west']; ?></span></td>
					</tr>
				</table>
				<input type="hidden" class="east" name="map_data[pan_limits][east]" value="<?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['east']; ?>" />
				<input type="hidden" class="north" name="map_data[pan_limits][north]" value="<?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['north']; ?>" />
				<input type="hidden" class="south" name="map_data[pan_limits][south]" value="<?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['south']; ?>" />
				<input type="hidden" class="west" name="map_data[pan_limits][west]" value="<?php if(isset($map_data['pan_limits'])) echo $map_data['pan_limits']['west']; ?>" />
			</div>
			<div class="geocode map-setting">
				<h4><?php _e('Enable geocoding service', 'jeo'); ?></h4>
				<p>
					<input class="enable-geocode" id="enable_geocode" type="checkbox" name="map_data[geocode]" <?php if(isset($map_data['geocode']) && $map_data['geocode']) echo 'checked'; ?> />
					<label for="enable_geocode"><?php _e('Enable geocode search service', 'jeo'); ?></label>
				</p>
			</div>
			<div class="handlers map-setting">
				<h4><?php _e('Map handlers', 'jeo'); ?></h4>
				<p>
					<input class="disable-mousewheel" id="disable_mousewheel" type="checkbox" name="map_data[disable_mousewheel]" <?php if(isset($map_data['disable_mousewheel']) && $map_data['disable_mousewheel']) echo 'checked'; ?> />
					<label for="disable_mousewheel"><?php _e('Disable mousewheel zooming', 'jeo'); ?></label>
				</p>
			</div>
			<?php do_action('jeo_map_setup_options', $map_data); ?>
		</div>
		<p>
			<a class="button-primary preview-map" href="#"><?php _e('Update preview', 'jeo'); ?></a>
			<input type="checkbox" class="toggle-preview-mode" id="toggle_preview_mode" checked /> <label for="toggle_preview_mode"><strong><?php _e('Preview mode', 'jeo'); ?></strong></label>
			<i><?php _e("(preview mode doesn't apply zoom range nor pan limits setup)", 'jeo'); ?></i>
		</p>
		<input type="hidden" id="mapConf" name="map_conf" value="" />
	</div>
	<?php
}

function mapbox_save_postdata($post_id) {
	// prevent data loss on autosave or any other ajaxed post update
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (defined('DOING_AJAX') && DOING_AJAX)
		return;

	if (false !== wp_is_post_revision($post_id))
		return;

	// save data
	if(isset($_POST['map_data']))
		update_post_meta($post_id, 'map_data', $_POST['map_data']);
	if(isset($_POST['map_conf']))
		update_post_meta($post_id, 'map_conf', $_POST['map_conf']);
}
