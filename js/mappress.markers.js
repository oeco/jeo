(function($) {

	mappress.markers = function(map) {

		if(!mappress_markers.query)
			return;

		var markers = map.markers = mappress.markers;
		var markersLayer = mapbox.markers.layer();
		var features;

		$.getJSON(mappress_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: mappress_markers.query
		},
		function(geojson) {
			if(geojson === 0)
				return;
			build(geojson);
		});

		var build = function(geojson) {

			map.addLayer(markersLayer);

			features = geojson.features;

			map.features = features;
			map.markersLayer = markersLayer;

			markersLayer
				.features(features)
				.key(function(f) {
					return f.properties.id;
				})
				.factory(function(x) {

					if(!markers.hasLocation(x))
						return;

					var e = document.createElement('div');

					$(e).addClass('story-points')
						.addClass(x.properties.id)
						.addClass(x.properties.marker_class)

					$(e).data('feature', x);

					// styles
					$(e).css({
						'background': 'url(' + x.properties.marker.url + ')',
						'width': x.properties.marker.width,
						'height': x.properties.marker.height,
						'margin-top': -x.properties.marker.height,
						'margin-left': -(x.properties.marker.width/2)
					});

					// POPUP

					var o = document.createElement('div');
					o.className = 'popup clearfix';
					$(o).css({
						'bottom': x.properties.marker.height + 11
					});
					e.appendChild(o);
					var content = document.createElement('div');
					content.className = 'post';
					content.innerHTML = x.properties.bubble;
					o.appendChild(content);

					$(e).click(function() {
						markers.open(x, false);
					});

					return e;

				});

		};

		markers.getMarker = function(id) {
			return _.find(features, function(m) { return m.properties.id === id; });
		}

		markers.open = function(marker, silent) {
			window.location = marker.properties.url;
			return false;
		};

		markers.hasLocation = function(marker) {
			if(marker.geometry.coordinates[0] ===  0 || !marker.geometry.coordinates[0])
				return false;
			else
				return true;
		}

		return map;

	}

})(jQuery);