(function($) {

	markers = function(map) {

		if(map.conf.disableMarkers || map.conf.admin)
			return false;

		$.getJSON(jeo_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: map.conf.marker_query || jeo_markers.query
		},
		function(geojson) {
			if(geojson === 0)
				return;
			_build(geojson);
		});

		var _build = function(geojson) {

			var icon = L.Icon.extend({});
			var icons = {};
			var parentLayer;

			if(jeo_markers.enable_clustering) {
				var options = (typeof jeo_markerclusterer.options == 'object') ? jeo_markerclusterer.options : {};
				parentLayer = new L.MarkerClusterGroup(options);
			} else {
				parentLayer = new L.layerGroup();
			}

			map._markerLayer = parentLayer;

			map.addLayer(parentLayer);
			map._markers = [];

			var layer = L.geoJson(geojson, {
				pointToLayer: function(f, latLng) {

					var marker = new L.marker(latLng, {
						riseOnHover: true,
						riseOffset: 9999
					});
					map._markers.push(marker);
					parentLayer.addLayer(marker);
					return marker;

				},
				onEachFeature: function(f, l) {

					var markerId = f.properties.marker.markerId ? f.properties.marker.markerId : f.properties.marker.iconUrl;

					if(icons[markerId]) {
						var fIcon = icons[markerId];
					} else {
						var fIcon = new icon(f.properties.marker);
						icons[markerId] = fIcon;
					}

					l.setIcon(fIcon);

					l.bindPopup(f.properties.bubble);

					l.on('mouseover', function(e) {
						e.target.openPopup();
					});
					l.on('mouseout', function(e) {
						e.target.closePopup();
					});
					l.on('click', function(e) {
						if(window.self === window.top) {
							window.location = f.properties.url;
						} else {
							window.open(f.properties.url, '_blank');
						}
						return false;
					});

				}
			});

			var bounds = layer.getBounds();

			var fragmentLoc = false;
			if(jeo.fragment) {
				fragmentLoc = jeo.fragment().get('loc');
			}

			if(!fragmentLoc && !map.conf.forceCenter && jeo_markers.markerextent && bounds.isValid()) {
					map.fitBounds(bounds);
			}

			jeo.runCallbacks('markersReady', [map]);

			return layer;

		};
	}
	jeo.mapReady(markers);
	jeo.createCallback('markersReady');

})(jQuery);
