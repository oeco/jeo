<?php

/*
 * JEO Layers
 */

class JEO_Layers {

	function __construct() {

		add_action('jeo_init', array($this, 'register_post_type'));
		add_action('jeo_init', array($this, 'register_layer_type_taxonomy'));
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('save_post', array($this, 'layer_save'));
		add_action('admin_footer', array($this, 'meta_box_scripts'));
		add_action('save_post', array($this, 'map_save'));

	}

	function register_post_type() {

		/*
		 * Layer
		 */
		$labels = array(
			'name' => __('Layers', 'jeo'),
			'singular_name' => __('Layer', 'jeo'),
			'add_new' => __('Add new layer', 'jeo'),
			'add_new_item' => __('Add new layer', 'jeo'),
			'edit_item' => __('Edit layer', 'jeo'),
			'new_item' => __('New layer', 'jeo'),
			'view_item' => __('View layer', 'jeo'),
			'search_items' => __('Search layers', 'jeo'),
			'not_found' => __('No layer found', 'jeo'),
			'not_found_in_trash' => __('No layer found in the trash', 'jeo'),
			'menu_name' => __('Layers', 'jeo')
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'description' => __('JEO Layers', 'jeo'),
			'supports' => array('title'),
			'rewrite' => array('slug' => 'layers'),
			'public' => true,
			'show_in_menu' => false,
			'has_archive' => true,
			'exclude_from_search' => true,
			'capability_type' => 'page'
		);

		register_post_type('map-layer', $args);

	}

	function register_layer_type_taxonomy() {
		$labels = array(
			'name'              => _x( 'Layer type', 'taxonomy general name' ),
			'singular_name'     => _x( 'Layer type', 'taxonomy singular name' ),
			'search_items'      => __( 'Search layer types' ),
			'all_items'         => __( 'All layer types' ),
			'parent_item'       => __( 'Parent layer type:' ),
			'parent_item_colon' => __( 'Parent layer type:' ),
			'edit_item'         => __( 'Edit layer type' ),
			'update_item'       => __( 'Update layer type' ),
			'add_new_item'      => __( 'Add new layer type' ),
			'new_item_name'     => __( 'New layer type category Name' ),
			'menu_name'         => __( 'Layer types' ),
		);
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'			=> false,
			'show_in_nav_menus'	=> false,
			'show_admin_column'	=> true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'layer-type' ),
		);
		register_taxonomy('layer-type', array( 'map-layer' ), $args );
	}

	function admin_menu() {
		add_submenu_page('edit.php?post_type=map', __('Layers', 'jeo'), __('Layers', 'jeo'), 'edit_posts', 'edit.php?post_type=map-layer');
		add_submenu_page('edit.php?post_type=map', __('Add new layer', 'jeo'), __('Add new layer', 'jeo'), 'edit_posts', 'post-new.php?post_type=map-layer');
	}

	function add_meta_box() {
		// Layer settings
		add_meta_box(
			'layer-settings',
			__('Layer settings', 'jeo'),
			array($this, 'settings_box'),
			'map-layer',
			'advanced',
			'high'
		);
		// Layer legend
		add_meta_box(
			'layer-legend',
			__('Layer legend', 'jeo'),
			array($this, 'legend_box'),
			'map-layer',
			'side',
			'default'
		);
		// Post layers
		add_meta_box(
			'post-layers',
			__('Layers', 'jeo'),
			array($this, 'post_layers_box'),
			'map',
			'advanced',
			'high'
		);
	}

	function meta_box_scripts() {
		wp_enqueue_script('underscore');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('json2');
		wp_enqueue_script('knockoutjs', get_template_directory_uri() . '/lib/knockout-3.2.0.js');
	}

	function settings_box($post = false) {

		$layer_type = $post ? $this->get_layer_type($post->ID) : false;

		$mapbox_token = jeo_get_mapbox_access_token();

		?>
		<div id="layer_settings_box">
			<div class="layer-type">
				<h4><?php _e('Layer type', 'jeo'); ?></h4>
				<p>
					<input type="radio" id="layer_type_tilelayer" name="layer_type" value="tilelayer" <?php if($layer_type == 'tilelayer' || !$layer_type) echo 'checked'; ?> />
					<label for="layer_type_tilelayer"><?php _e('Tilelayer', 'jeo'); ?></label>

					<input type="radio" id="layer_type_mapbox" name="layer_type" value="mapbox" <?php if($layer_type == 'mapbox') echo 'checked'; ?> <?php if(!$mapbox_token) echo 'disabled'; ?> />
					<label for="layer_type_mapbox">
						<?php _e('MapBox', 'jeo'); ?>
						<?php if(!$mapbox_token) : ?>
							(<a href="<?php echo admin_url('/themes.php?page=jeo_settings&tab=mapbox'); ?>"><?php _e('activate MapBox with an access token', 'jeo'); ?></a>)
						<?php endif; ?>
					</label>

					<input type="radio" id="layer_type_cartodb" name="layer_type" value="cartodb" <?php if($layer_type == 'cartodb') echo 'checked'; ?> />
					<label for="layer_type_cartodb"><?php _e('CartoDB', 'jeo'); ?></label>
				</p>
			</div>
			<table class="form-table type-setting tilelayer">
				<?php

				$tileurl = $post ? get_post_meta($post->ID, '_tilelayer_tile_url', true) : '';
				$utfgridurl = $post ? get_post_meta($post->ID, '_tilelayer_utfgrid_url', true) : '';
				$utfgrid_template = $post ? get_post_meta($post->ID, '_tilelayer_utfgrid_template', true) : '';
				$tms = $post ? get_post_meta($post->ID, '_tilelayer_tms', true) : '';

				?>
				<tbody>
					<tr>
						<th><label for="tilelayer_tile_url"><?php _e('URL', 'jeo'); ?></label></th>
						<td>
							<input id="tilelayer_tile_url" type="text" placeholder="<?php _e('http://{s}.example.com/{z}/{x}/{y}.png', 'jeo'); ?>" size="40" name="_tilelayer_tile_url" value="<?php echo $tileurl; ?>" />
							<p class="description"><?php _e('Tilelayer URL. E.g.: http://{s}.example.com/{z}/{x}/{y}.png', 'jeo'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="tilelayer_utfgrid_url"><?php _e('UTFGrid URL (optional)', 'jeo'); ?></label></th>
						<td>
							<input id="tilelayer_utfgrid_url" type="text" placeholder="<?php _e('http://{s}.example.com/{z}/{x}/{y}.grid.json', 'jeo'); ?>" size="40" name="_tilelayer_utfgrid_url" value="<?php echo $utfgridurl; ?>" />
							<p class="description"><?php _e('Optional UTFGrid URL. E.g.: http://{s}.example.com/{z}/{x}/{y}.grid.json', 'jeo'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="tilelayer_utfgrid_template"><?php _e('UTFGrid Template (optional)', 'jeo'); ?></label></th>
						<td>
							<textarea id="tilelayer_utfgrid_template" rows="10" cols="40" name="_tilelayer_utfgrid_template"><?php echo $utfgrid_template; ?></textarea>
							<p class="description"><?php _e('UTFGrid template using mustache.<br/>E.g.: City: {{city}}'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="tilelayer_tms"><?php _e('TMS', 'jeo'); ?></label></th>
						<td>
							<input id="tilelayer_tms" type="checkbox" name="_tilelayer_tms" <?php if($tms) echo 'checked'; ?> /> <label for="tilelayer_tms"><?php _e('Enable TMS', 'jeo'); ?></label>
							<p class="description"><?php _e('Inverses Y axis numbering for tiles (turn this on for TMS services).'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="form-table type-setting mapbox">
				<?php

				$mapbox_id = $post ? get_post_meta($post->ID, '_mapbox_id', true) : '';

				?>
				<tbody>
					<tr>
						<th><label for="mapbox_id"><?php _e('MapBox ID', 'jeo'); ?></label></th>
						<td>
							<input id="mapbox_id" type="text" placeholder="examples.map-20v6611k" size="40" name="_mapbox_id" value="<?php echo $mapbox_id; ?>" />
							<p class="description"><?php _e('MapBox map ID. E.g.: examples.map-20v6611k', 'jeo'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="form-table type-setting cartodb">
				<?php

				// opt
				$cartodb_type = $post ? get_post_meta($post->ID, '_cartodb_type', true) : 'viz';

				// viz
				$vizurl = $post ? get_post_meta($post->ID, '_cartodb_viz_url', true) : '';

				// custom
				$username = $post ? get_post_meta($post->ID, '_cartodb_username', true) : '';
				$table = $post ? get_post_meta($post->ID, '_cartodb_table', true) : '';
				$where = $post ? get_post_meta($post->ID, '_cartodb_where', true) : '';
				$cartocss = $post ? get_post_meta($post->ID, '_cartodb_cartocss', true) : '';
				$template = $post ? get_post_meta($post->ID, '_cartodb_template', true) : '';

				?>
				<tbody>
					<tr>
						<th><?php _e('Visualization type', 'jeo'); ?></th>
						<td>
							<input name="_cartodb_type" id="cartodb_viz_type_viz" type="radio" value="viz" <?php if($cartodb_type == 'viz' || !$cartodb_type) echo 'checked'; ?> />
							<label for="cartodb_viz_type_viz"><?php _e('Visualization', 'jeo'); ?></label>
							<input name="_cartodb_type" id="cartodb_viz_type_custom" type="radio" value="custom" disabled <?php if($cartodb_type == 'custom') echo 'checked'; ?> />
							<label for="cartodb_viz_type_custom"><?php _e('Advanced (build from your tables)', 'jeo'); ?> - <?php _e('coming soon', 'jeo'); ?></label>
						</td>
					</tr>
					<tr class="subopt viz_type_viz">
						<th><label for="cartodb_viz_url"><?php _e('CartoDB URL', 'jeo'); ?></label></th>
						<td>
							<input id="cartodb_viz_url" type="text" placeholder="http://user.cartodb.com/api/v2/viz/621d23a0-5eaa-11e4-ab03-0e853d047bba/viz.json" size="40" name="_cartodb_viz_url" value="<?php echo $vizurl; ?>" />
							<p class="description"><?php _e('CartoDB visualization URL.<br/>E.g.: http://infoamazonia.cartodb.com/api/v2/viz/621d23a0-5eaa-11e4-ab03-0e853d047bba/viz.json', 'jeo'); ?></p>
						</td>
					</tr>
					<tr class="subopt viz_type_custom">
						<th><label for="cartodb_viz_username"><?php _e('Username', 'jeo'); ?></label></th>
						<td>
							<input id="cartodb_viz_username" type="text" placeholder="johndoe" name="_cartodb_username" value="<?php echo $username; ?>" />
							<p class="description"><?php _e('Your CartoDB username.'); ?></p>
						</td>
					</tr>
					<tr class="subopt viz_type_custom">
						<th><label for="cartodb_viz_table"><?php _e('Table', 'jeo'); ?></label></th>
						<td>
							<input id="cartodb_viz_table" type="text" placeholder="deforestation_2012" name="_cartodb_table" value="<?php echo $table; ?>" />
							<p class="description"><?php _e('The CartoDB table you\'d like to visualize.'); ?></p>
						</td>
					</tr>
					<tr class="subopt viz_type_custom">
						<th><label for="cartodb_viz_where"><?php _e('Where (optional)', 'jeo'); ?></label></th>
						<td>
							<textarea id="cartodb_viz_where" rows="3" cols="40" name="_cartodb_where"><?php echo $where; ?></textarea>
							<p class="description"><?php _e('Query data from your table.<br/>E.g.: region = "north"'); ?></p>
						</td>
					</tr>
					<tr class="subopt viz_type_custom">
						<th><label for="cartodb_viz_cartocss"><?php _e('CartoCSS', 'jeo'); ?></label></th>
						<td>
							<textarea id="cartodb_viz_cartocss" rows="10" cols="40" name="_cartodb_cartocss"><?php echo $cartocss; ?></textarea>
							<p class="description"><?php printf(__('Styles for your table. <a href="%s" target="_blank">Learn more</a>.'), 'https://www.mapbox.com/tilemill/docs/manual/carto/'); ?></p>
						</td>
					</tr>
					<tr class="subopt viz_type_custom">
						<th><label for="cartodb_viz_template"><?php _e('Template', 'jeo'); ?></label></th>
						<td>
							<textarea id="cartodb_viz_template" rows="10" cols="40" name="_cartodb_template"><?php echo $template; ?></textarea>
							<p class="description"><?php _e('UTFGrid template using mustache.<br/>E.g.: City: {{city}}'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<style type="text/css">
			.layer-type label,
			.form-table label {
				margin-right: 10px;
			}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($) {

				var $container = $('#layer_settings_box');
				var $layerSelection = $container.find('input[name="layer_type"]');
				var $forms = $container.find('.form-table');

				$forms.hide();

				var showForms = function() {

					var selected = $layerSelection.filter(':checked').val();

					$forms.hide().filter('.' + selected).show();

				}

				$layerSelection.on('change', function() {
					showForms();
				});
				showForms();

				/*
				 * CartoDB sub options
				 */

				var $form = $forms.filter('.cartodb');

				var $subOpts = $form.find('tr.subopt');

				$subOpts.hide();

				var showSubOpts = function() {
					var selected = $form.find('input[name="_cartodb_type"]:checked').val();
					$subOpts.hide().filter('.viz_type_' + selected).show();
				};

				$form.find('input[name="_cartodb_type"]').on('change', function() {
					showSubOpts();
				});
				showSubOpts();

			});
		</script>
		<?php

	}

	function legend_box($post = false) {

		$legend = $post ? get_post_meta($post->ID, '_layer_legend', true) : '';

		?>
		<h4><?php _e('Enter your HTML code to use as legend on the layer', 'jeo'); ?></h4>
		<textarea name="_layer_legend" style="width:100%;height: 200px;"><?php echo $legend; ?></textarea>
		<?php

	}

	function layer_save($post_id) {

		if(get_post_type($post_id) == 'map-layer') {
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return;

			if (false !== wp_is_post_revision($post_id))
				return;

			/*
			 * Layer legend
			 */
			if(isset($_REQUEST['_layer_legend']))
				update_post_meta($post_id, '_layer_legend', $_REQUEST['_layer_legend']);

			/*
			 * Layer type
			 */
			if(isset($_REQUEST['layer_type']))
				wp_set_object_terms($post_id, $_REQUEST['layer_type'], 'layer-type', false);

			/*
			 * Tilelayer
			 */

			if(isset($_REQUEST['_tilelayer_tile_url']))
				update_post_meta($post_id, '_tilelayer_tile_url', $_REQUEST['_tilelayer_tile_url']);

			if(isset($_REQUEST['_tilelayer_utfgrid_url']))
				update_post_meta($post_id, '_tilelayer_utfgrid_url', $_REQUEST['_tilelayer_utfgrid_url']);

			if(isset($_REQUEST['_tilelayer_utfgrid_template']))
				update_post_meta($post_id, '_tilelayer_utfgrid_template', $_REQUEST['_tilelayer_utfgrid_template']);

			if(isset($_REQUEST['_tilelayer_tms'])) {
				if($_REQUEST['_tilelayer_tms'])
					update_post_meta($post_id, '_tilelayer_tms', true);
			} else {
				delete_post_meta($post_id, '_tilelayer_tms');
			}

			/*
			 * MapBox
			 */

			if(isset($_REQUEST['_mapbox_id']))
				update_post_meta($post_id, '_mapbox_id', $_REQUEST['_mapbox_id']);

			/*
			 * CartoDB
			 */

			if(isset($_REQUEST['_cartodb_type']))
				update_post_meta($post_id, '_cartodb_type', $_REQUEST['_cartodb_type']);

			if(isset($_REQUEST['_cartodb_viz_url']))
				update_post_meta($post_id, '_cartodb_viz_url', $_REQUEST['_cartodb_viz_url']);

			if(isset($_REQUEST['_cartodb_username']))
				update_post_meta($post_id, '_cartodb_username', $_REQUEST['_cartodb_username']);

			if(isset($_REQUEST['_cartodb_table']))
				update_post_meta($post_id, '_cartodb_table', $_REQUEST['_cartodb_table']);

			if(isset($_REQUEST['_cartodb_where']))
				update_post_meta($post_id, '_cartodb_where', $_REQUEST['_cartodb_where']);

			if(isset($_REQUEST['_cartodb_cartocss']))
				update_post_meta($post_id, '_cartodb_cartocss', $_REQUEST['_cartodb_cartocss']);

			if(isset($_REQUEST['_cartodb_template']))
				update_post_meta($post_id, '_cartodb_template', $_REQUEST['_cartodb_template']);

			do_action('jeo_layer_save', $post_id);
		}

	}

	function post_layers_box($post = false) {

		$layer_query = new WP_Query(array('post_type' => 'map-layer', 'posts_per_page' => -1));
		$layers = array();
		$post_layers = $post ? $this->get_map_layers($post->ID) : false;
		?>

		<p>
			<?php
			printf(__('Add and manage <a href="%s" target="_blank">layers</a> on your map.', 'jeo'), admin_url('edit.php?post_type=map-layer'));
			if(!$layer_query->have_posts())
				printf(__(' You haven\'t created any layers yet, <a href="%s" target="_blank">click here</a> to create your first!'), admin_url('post-new.php?post_type=map-layer'));
			?>
		</p>

		<?php
		if($layer_query->have_posts()) {
			while($layer_query->have_posts()) {
				$layer_query->the_post();
				$layers[] = $this->get_layer(get_the_ID());
				wp_reset_postdata();
			}
			?>
			<input type="text" data-bind="textInput: search" placeholder="<?php _e('Search for layers', 'jeo'); ?>" size="50">

			<!-- ko if: !search() -->
				<h4 class="results-title"><?php _e('Latest layers', 'jeo'); ?></h4>
			<!-- /ko -->

			<!-- ko if: search() -->
				<h4 class="results-title"><?php _e('Search results', 'jeo'); ?></h4>
			<!-- /ko -->

			<!-- ko if: !filteredLayers().length && !search() -->
				<p style="font-style:italic;color: #999;"><?php _e('You are using all of your layers.', 'jeo'); ?></p>
			<!-- /ko -->

			<!-- ko if: !filteredLayers().length && search() -->
				<p style="font-style:italic;color: #999;"><?php _e('No layers were found.', 'jeo'); ?></p>
			<!-- /ko -->

			<table class="layers-list available-layers">
				<tbody data-bind="foreach: filteredLayers">
					<tr>
						<td><strong data-bind="text: title"></strong></td>
						<td data-bind="text: type"></td>
						<td style="width:1%;"><a class="button" data-bind="click: $parent.addLayer" href="javascript:void(0);" title="<?php _e('Add layer', 'jeo'); ?>">+ <?php _e('Add'); ?></a></td>
					</tr>
				</tbody>
			</table>

			<h4 class="selected-title"><?php _e('Selected layers', 'jeo'); ?></h4>

			<table class="layers-list selected-layers">
				<tbody class="selected-layers-list">
					<!-- ko foreach: {data: selectedLayers} -->
						<tr class="layer-item">
							<td style="width: 30%;">
								<p><strong data-bind="text: title"></strong></p>
								<p data-bind="text: type"></p>
							</td>
							<td>
								<p><?php _e('Layer options', 'jeo'); ?></p>
								<div class="filter-opts">
									<input type="radio" value="fixed" data-bind="attr: {name: ID + '_filtering_opt', id: ID + '_filtering_opt_fixed'}, checked: $data.filtering" />
									<label data-bind="attr: {for: ID + '_filtering_opt_fixed'}"><?php _e('Fixed', 'jeo'); ?></label>
									<input  type="radio" value="switch" data-bind="attr: {name: ID + '_filtering_opt', id: ID + '_filtering_opt_switch'}, checked: $data.filtering" />
									<label data-bind="attr: {for: ID + '_filtering_opt_switch'}"><?php _e('Switchable', 'jeo'); ?></label>
									<input type="radio" value="swap"  data-bind="attr: {name: ID + '_filtering_opt', id: ID + '_filtering_opt_swap'}, checked: $data.filtering" />
									<label data-bind="attr: {for: ID + '_filtering_opt_swap'}"><?php _e('Swapable', 'jeo'); ?></label>

									<div class="filtering-opts">
										<!-- ko if: $data.filtering() == 'switch' -->
											<input type="checkbox" data-bind="attr: {id: ID + '_switch_hidden'}, checked: $data.hidden" />
											<label data-bind="attr: {for: ID + '_switch_hidden'}"><?php _e('Hidden', 'jeo'); ?></label>
										<!-- /ko -->
										<!-- ko if: $data.filtering() == 'swap' -->
											<input type="radio" data-bind="attr: {id: ID + '_first_swap'}, checked: $data.first_swap" name="_jeo_map_layer_first_swap" />
											<label data-bind="attr: {for: ID + '_first_swap'}"><?php _e('Default swap option', 'jeo'); ?></label>
										<!-- /ko -->
									</div>
								</div>
							</td>
							<td style="width:1%;"><a class="button" data-bind="click: $parent.removeLayer" href="javascript:void(0);" title="<?php _e('Remove layer', 'jeo'); ?>"><?php _e('Remove'); ?></a></td>
						</tr>
					<!-- /ko -->
				</tbody>
			</table>

			<input type="hidden" name="_jeo_map_layers" data-bind="textInput: selection" />

			<style type="text/css">
				#post-layers input[type='text'] {
					width: 100%;
				}
				#post-layers .layers-list {
					background: #fcfcfc;
					border-collapse: collapse;
					width: 100%;
				}
				#post-layers .selected-layers .layer-item {
					width: 100%;
					height: 100px;
				}
				#post-layers .layers-list tr td {
					margin: 0;
					border: 1px solid #f0f0f0;
					padding: 5px 8px;
				}
				#post-layers .layers-list tr:hover td {
					background: #fff;
				}
			</style>

			<script type="text/javascript">
				function LayersModel() {
					var self = this;

					var origLayers = <?php echo json_encode($layers); ?>;

					self.search = ko.observable('');

					self.addLayer = function(layer) {
						var layer = layer || this;
						if(typeof layer.filtering !== 'function')
							layer.filtering = ko.observable(layer.filtering || 'fixed');
						if(typeof layer.hidden !== 'function')
							layer.hidden = ko.observable(layer.hidden || false);
						if(typeof layer.first_swap !== 'function')
							layer.first_swap = ko.observable(layer.first_swap || false);
						self.selectedLayers.push(layer);
						self.layers.remove(layer);
					};

					self.removeLayer = function(layer) {
						var layer = layer || this;
						self.layers.push(layer);
						self.selectedLayers.remove(layer);
					};

					/*
					 * Layer list
					 */

					self.layers = ko.observableArray(origLayers.slice(0));

					self.filteredLayers = ko.computed(function() {
						if(!self.search()) {
							return self.layers().slice(0, 4);
						} else {
							return ko.utils.arrayFilter(self.layers(), function(l) {
								return l.title.toLowerCase().indexOf(self.search().toLowerCase()) !== -1;
							}).slice(0, 4);
						}
					});

					/*
					 * Layer selection
					 */

					self.selectedLayers = ko.observableArray([]);

					var initSelection = <?php if($post_layers) echo json_encode($post_layers); else echo '[]'; ?>;

					if(initSelection.length) {
						_.each(initSelection, function(l) {
							var layer = _.extend(_.find(self.layers(), function(layer) {
								if(layer.ID == l.ID) {
									_.extend(l, layer);
									return true;
								}
								return false;
							}), l);
							self.addLayer(layer);
						});
					}

					self.selection = ko.computed(function() {
						var layers = [];
						_.each(self.selectedLayers(), function(layer) {
							var layer = _.extend({}, layer);
							layer.filtering = layer.filtering();
							layer.hidden = layer.hidden();
							layer.first_swap = layer.first_swap();
							layers.push(layer);
						});
						window.editingLayers = layers;
						return JSON.stringify(layers);
					});

					/*
					 * Sortable selected layers
					 */

					// jquery sort binding method
					self.bindSort = function(listSelector, listKey) {
						var startIndex = -1;

						var sortableSetup = {

							// on sorting start
							start: function (event, ui) {
								// cache the item index when the dragging starts
								startIndex = ui.item.index();
							},

							// on sorting stop
							stop: function (event, ui) {

								// get the new location item index
								var newIndex = ui.item.index();

								if (startIndex > -1) {
									//  get the item to be moved
									var item = self[listKey]()[startIndex];

									//  move the item
									self[listKey].remove(item);
									self[listKey].splice(newIndex, 0, item);

									//  ko rebinds to the array so we need to remove duplicate ui item
									ui.item.remove();
								}

							}
						};

						// bind jquery using the .fruitList class selector
						jQuery(listSelector).sortable( sortableSetup );

					};

				}

				jQuery(document).ready(function() {
					var model = new LayersModel();
					model.bindSort('.selected-layers-list', 'selectedLayers');
					ko.applyBindings(model);
				});
			</script>
			<?php

		}

	}

	function map_save($post_id) {

		if(get_post_type($post_id) == 'map') {
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return;

			if (false !== wp_is_post_revision($post_id))
				return;

			if(isset($_REQUEST['_jeo_map_layers'])) {
				update_post_meta($post_id, '_jeo_map_layers', json_decode(stripslashes($_REQUEST['_jeo_map_layers']), true));
			}
		}

	}

	function get_layer_type($post_id = false) {

		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$terms = get_the_terms($post_id, 'layer-type');

		if($terms) {
			return array_shift($terms)->name;
		} else {
			return false;
		}

	}

	function get_layer($post_id = false) {

		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$post = get_post($post_id);
		setup_postdata($post);

		$type = $this->get_layer_type();

		$layer = array(
			'ID' => $post->ID,
			'title' => get_the_title(),
			'type' => $type,
			'legend' => get_post_meta($post->ID, '_layer_legend', true)
		);

		if($type == 'tilelayer') {
			$layer['tile_url'] = htmlspecialchars(urldecode(get_post_meta($post->ID, '_tilelayer_tile_url', true)));
			$layer['utfgrid_url'] = get_post_meta($post->ID, '_tilelayer_utfgrid_url', true);
			$layer['utfgrid_template'] = get_post_meta($post->ID, '_tilelayer_utfgrid_template', true);
			$layer['tms'] = get_post_meta($post->ID, '_tilelayer_tms', true);
		} elseif($type == 'mapbox') {
			$layer['mapbox_id'] = get_post_meta($post->ID, '_mapbox_id', true);
		} elseif($type == 'cartodb') {
			$layer['cartodb_type'] = get_post_meta($post->ID, '_cartodb_type', true);
			if($layer['cartodb_type'] == 'viz') {
				$layer['cartodb_viz_url'] = get_post_meta($post->ID, '_cartodb_viz_url', true);
			} else {
				$layer['cartodb_username'] = get_post_meta($post->ID, '_cartodb_username', true);
				$layer['cartodb_table'] = get_post_meta($post->ID, '_cartodb_table', true);
				$layer['cartodb_where'] = get_post_meta($post->ID, '_cartodb_where', true);
				$layer['cartodb_cartocss'] = get_post_meta($post->ID, '_cartodb_cartocss', true);
				$layer['cartodb_template'] = get_post_meta($post->ID, '_cartodb_template', true);
			}
		}

		wp_reset_postdata();

		return $layer;

	}

	function get_map_layers($post_id = false) {
		global $post;
		$post_id = $post_id ? $post_id : $post->ID;

		$layers = array();

		$map_layers = get_post_meta($post_id, '_jeo_map_layers', true);

		if($map_layers) {
			foreach($map_layers as $l) {
				$layer = $this->get_layer($l['ID']);
				$layer['filtering'] = $l['filtering'];
				if($layer['filtering'] == 'swap') {
					$layer['first_swap'] = $l['first_swap'];
				} elseif($layer['filtering'] == 'switch') {
					$layer['hidden'] = $l['hidden'];
				}
				$layers[] = $layer;
			}
		}

		return $layers;

	}

}

$GLOBALS['jeo_layers'] = new JEO_Layers();

function jeo_get_layer($post_id = false) {
	return $GLOBALS['jeo_layers']->get_layer($post_id);
}

function jeo_get_map_layers($post_id = false) {
	return $GLOBALS['jeo_layers']->get_map_layers($post_id);
}
