var groups = {};

(function($) {

	mappress.group = function(groupID) {

		var group = {};

		var fragment = mappress.fragment();

		$.getJSON(mappress_groups.ajaxurl,
		{
			action: 'mapgroup_data',
			group_id: groupID
		},
		function(data) {
			group.build(data);
		});

		group.build = function(data) {

			group.$ = $('#mapgroup_' + groupID);

			// store maps data
			group.mapsData = data.maps;

			// nodes
			group.$.nav = group.$.find('.map-nav');
			group.$.map = group.$.find('.map');

			group.id = group.$.map.attr('id');

			// prepare first map and group conf
			var firstMapID = group.$.nav.find('li:first-child a').data('map');
			if(fragment.get('map'))
				firstMapID = fragment.get('map');

			group.conf = mappress.convertMapConf(group.mapsData[firstMapID]);

			// set mappress conf containerID to group id
			group.conf.containerID = group.id;

			// force main map
			group.conf.mainMap = true;

			// store current map id
			group.currentMapID = firstMapID;

			// build group
			group.map = mappress(group.conf);
			group.map.currentMapID = firstMapID;

			group.updateUI();

			group.$.nav.find('li a').click(function() {

				// disable "more" tab click
				if($(this).hasClass('toggle-more'))
					return false;

				if($(this).hasClass('active'))
					return false;

				var mapID = $(this).data('map');

				// update layers
				group.update(mapID);

				// update ui
				group.updateUI();

				return false;
			});

		}

		group.updateUI = function() {

			var mapID = group.currentMapID;
			var $navEl = group.$.nav.find('[data-map="' + mapID + '"]');
			var moreLabel = group.$.nav.find('.more-tab > a').text(); // store more label

			group.$.nav.find('li a').removeClass('active');
			$navEl.addClass('active');

			// ui behaviour for more tab
			if($navEl.parent().hasClass('more-item')) {
				group.$.nav.find('.more-tab > a').addClass('active').text($navEl.text());
			} else {
				group.$.nav.find('.more-tab > a').removeClass('active').text(moreLabel);
			}

		}

		group.update = function(mapID) {

			group.map = mappress.maps[group.id];

			// prepare new conf and layers
			var conf = mappress.convertMapConf(group.mapsData[mapID]);
			var layers = mappress.setupLayers(conf.layers);

			// store new conf
			group.map.conf = conf;

			mapbox.load(layers, function(data) {

				group.map.setLayerAt(0, data.layer);
				group.map.interaction.refresh();

				// clear widgets

				group.map.$.widgets.empty();

				if(conf.geocode)
					mappress.geocode(group.map);

				if(conf.filteringLayers)
					mappress.filterLayers(group.map);

				group.map.ui.legend.remove();

				if(conf.legend)
					group.map.ui.legend.add().content(conf.legend);

				if(conf.legend_full)
					mappress.enableDetails(group.map, conf.legend, conf.legend_full);

			});

			// update current map id
			group.currentMapID = mapID;
			group.map.currentMapID = mapID;

			var fragmentEnabled = mappress.fragmentEnabled;
			if(fragmentEnabled)
				fragment.set({'map': mapID});
		}

		groups[group.id] = group;
		return group;
	}

})(jQuery);