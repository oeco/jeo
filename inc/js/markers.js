(function($) {

	markers = function(map) {

		if(map.conf.disableMarkers || map.conf.admin)
			return false;

		$.getJSON(jeo_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: jeo_markers.query
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

			if(jeo_markers.enable_clustering)
				parentLayer = new L.MarkerClusterGroup();
			else
				parentLayer = new L.layerGroup();

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

					if(icons[f.properties.marker.markerId]) {
						var fIcon = icons[f.properties.marker.markerId];
					} else {
						var fIcon = new icon(f.properties.marker);
						icons[f.properties.marker.markerId] = fIcon;
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

			//layer.addTo(parentLayer);

			var bounds = layer.getBounds();
			if(!jeo.fragment().get('loc') && !map.conf.forceCenter && jeo_markers.markerextent && bounds.isValid()) {
				map.fitBounds(layer.getBounds());
			}

			jeo.runCallbacks('markersReady', [map]);

			return layer;

		};
	}
	jeo.mapReady(markers);
	jeo.createCallback('markersReady');

})(jQuery);