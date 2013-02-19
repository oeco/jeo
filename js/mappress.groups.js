var groups = {};

(function($) {

	mappress.group = function(groupID) {

		var group = {};

		$.getJSON(mappress_groups.ajaxurl,
		{
			action: 'mapgroup_data',
			group_id: groupID
		},
		function(data) {
			group.build(data);
		});

		group.build = function(data) {

			group.$ = $('#mapgroup-' + groupID);

			// store maps data
			group.mapsData = data.maps;

			// nodes
			group.$.nav = group.$.find('.map-nav');
			group.$.map = group.$.find('.map');

			group.id = group.$.map.attr('id');

			// prepare first map and group conf
			var firstMapID = group.$.nav.find('li:first-child a').data('map');
			group.conf = mappress.convertMapConf(group.mapsData[firstMapID]);

			// set mappress conf containerID to group id
			group.conf.containerID = group.id;

			// store current map id
			group.currentMapID = firstMapID;

			// build group
			group.map = mappress(group.conf);
			group.map.currentMapID = firstMapID;

			// bind nav events
			var moreLabel = group.$.nav.find('.more-tab > a').text(); // store more label
			group.$.nav.find('li a').click(function() {

				// disable "more" tab click
				if($(this).hasClass('toggle-more'))
					return false;

				var mapID = $(this).data('map');

				if($(this).hasClass('active'))
					return false;

				group.$.nav.find('li a').removeClass('active');
				$(this).addClass('active');

				// ui behaviour for more tab
				if($(this).parent().hasClass('more-item')) {
					group.$.nav.find('.more-tab > a').addClass('active').text($(this).text());
				} else {
					group.$.nav.find('.more-tab > a').removeClass('active').text(moreLabel);
				}

				// update layers
				group.update(mapID);

				return false;
			});

		}

		group.update = function(mapID) {

			group.map = mappress.maps[group.id];

			// prepare new conf and layers
			var conf = mappress.convertMapConf(group.mapsData[mapID]);
			var layers = mappress.setupLayers(conf.layers);

			// store new conf
			mappress.maps[group.id].conf = conf;

			mapbox.load(layers, function(data) {

				group.map.setLayerAt(0, data.layer);
				group.map.interaction.refresh();

				// clear widgets

				group.map.$.widgets.empty();

				if(conf.geocode)
					mappress.geocode(group.id);

				if(conf.filteringLayers)
					mappress.filterLayers(group.id, conf.filteringLayers);

				group.map.ui.legend.remove();

				if(conf.legend)
					group.map.ui.legend.add().content(conf.legend);

				if(conf.legend_full)
					mappress.enableDetails(group.map, conf.legend, conf.legend_full);

				//group.map.markersLayer.features(group.map.features);

			});

			// update current map id
			group.currentMapID = mapID;
			group.map.currentMapID = mapID;
		}

		groups[group.id] = group;
		return group;
	}

})(jQuery);