var geocodeBox;

(function($) {

	geocodeBox = function(options) {

		var box = {},
			latInput,
			lngInput,
			boundsInput,
			addressInput,
			geocodeButton,
			resultsContainer,
			mapCanvasID = 'map_canvas';

		var settings = {
			containerID: 	'geocode_box',
			service: 		 geocode_localization.service
		}

		if(typeof options === 'undefined');
			options = {};

		settings = box.settings = $.extend(settings, options);

		box.clearResults = function() {
			resultsContainer.empty();
		}

		box.geocode = function() {
			box._getAddress(addressInput.val());
		};

		box.address = function() {
			f = {
				set: function(address) {
					addressInput.val(address);
					runCallbacks('addressChanged', [box]);
				},
				get: function() {
					return addressInput.val();
				}
			}
			return f;
		}

		box.location = function() {
			f = {
				set: function(lat, lng) {
					latInput.val(lat);
					lngInput.val(lng);
					runCallbacks('locationChanged', [box]);
				},
				setLat: function(lat) {
					latInput.val(lat);
					runCallbacks('locationChanged', [box]);
				},
				setLng: function(lng) {
					lngInput.val(lng);
					runCallbacks('locationChanged', [box]);
				},
				get: function() {
					return [latInput.val(), lngInput.val()];
				},
				getLat: function() {
					return latInput.val();
				},
				getLng: function() {
					return lngInput.val();
				}
			}
			return f;
		}

		box.bounds = function() {
			f = {
				set: function(bounds) {
					boundsInput.val(bounds);
					runCallbacks('boundsChanged', [box]);
				},
				get: function() {
					return boundsInput.val();
				}
			}
			return f;
		}

		function _init() {

			box 				= $.extend(box, $('#' + settings.containerID));

			if(!box.length)
				return false;

			latInput 			= box.find('#geocode_lat');
			lngInput 			= box.find('#geocode_lon');
			boundsInput 		= box.find('#geocode_viewport');
			addressInput 		= box.find('#geocode_address');
			resultsContainer 	= box.find('.results');

			if(settings.service == 'osm') {
				box = $.extend(box, osm);

			} else if(settings.service == 'gmaps') {
				box = $.extend(box, gmaps);
			}

			var lat 	= box.location().getLat();
			var lng 	= box.location().getLng();
			var bounds 	= box.bounds().get();

			bindEvents();

			runCallbacks('ready', [box]);

			box.update(lat, lng, bounds);

		}

		/*
		 * UI events
		 */

		function bindEvents() {
			addressInput.keyup(function(e) {
				if(e.keyCode === 13) {
					box.geocode();
					return false;
				}
			});
			box.on('click', '.geocode_address', function() {
				box.geocode();
				return false;
			});
		};

		/*
		 * Services
		 */

		var gmaps = {

			isGoogleMaps: true,

			_map: function(position) {

				var options = {
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					streetViewControl: false
				};

				if(typeof position !== 'undefined')
					options.center = position;

				box.map = new google.maps.Map(document.getElementById(mapCanvasID), options);

				// events

				google.maps.event.addListener(box.map, 'bounds_changed', function() {
					box.bounds().set(box.map.getBounds());
				});

				box.isMapReady = true;
				runCallbacks('mapReady', [box]);

				return box.map;

			},

			_getAddress: function(address) {

				if(!box.geocoder)
					box.geocoder = new google.maps.Geocoder();

				box.geocoder.geocode({ address: address }, function(results, status) {

					if(status == google.maps.GeocoderStatus.OK) {

						_results(box._formatResults(results), 'address', 'lat', 'lng', 'bounds');

						runCallbacks('queried', [box, results]);

					}

				});

			},

			_formatResults: function(results) {

				var formattedResults = [];
				$.each(results, function(i, result) {
					formattedResults.push({
						address: result.formatted_address,
						lat: result.geometry.location.lat(),
						lng: result.geometry.location.lng(),
						bounds: result.geometry.viewport
					});
				});
				return formattedResults;

			},

			_convertToViewport: function(bounds) {

				if(typeof bounds !== 'string')
					return bounds;

				var viewport = bounds.split('), (');

				var viewportSW = viewport[0];
				var viewportNE = viewport[1];

				viewportSW = viewportSW.substr(2);
				viewportNE = viewportNE.substr(0, viewportNE.length - 2);

				viewportSW = viewportSW.split(', ');
				viewportNE = viewportNE.split(', ');

				viewport = new google.maps.LatLngBounds(
					new google.maps.LatLng(viewportSW[0], viewportSW[1]),
					new google.maps.LatLng(viewportNE[0], viewportNE[1])
				);

				return viewport;

			},

			update: function(lat, lng, bounds) {

				if(lat && lng) {

					var position = new google.maps.LatLng(lat, lng);

					if(!box.marker) {

						box.marker = new google.maps.Marker({
							draggable: 	true,
							position: 	position
						});

						google.maps.event.addListener(box.marker, 'position_changed', function() {
							box.location().set(box.marker.position.lat(), box.marker.position.lng());
						});

					} else {

						box.marker.setPosition(position);

					}

					if(!box.isMapReady)
						box._map(position);

					box.map.setCenter(position);
					box.marker.setMap(box.map);

					if(typeof bounds !== 'undefined')
						box.map.fitBounds(box._convertToViewport(bounds));

				}

			},

			clearMarkers: function() {

				box.marker.setMap(null);
				box.map.setCenter(new google.maps.LatLng(0,0));
				box.map.setZoom(1);

			}

		}

		var osm = {

			isOSM: true,

			_map: function(lat, lng, bounds) {

				box.markerLayer = mapbox.markers.layer();
				box.map = mapbox.map(mapCanvasID);

				box.map.addLayer(new MM.TemplatedLayer('http://a.tile.openstreetmap.org/{Z}/{X}/{Y}.png'));
				box.map.zoom(1);
				box.map.addLayer(box.markerLayer);

				box.isMapReady = true;
				runCallbacks('mapReady', [box]);

			},

			_getAddress: function(address) {

				query = {
					q: address,
					polygon_geojson: 1,
					format: 'json'
				};

				$.getJSON('http://nominatim.openstreetmap.org/search.php?json_callback=?', query, function(results) {

					_results(results, 'display_name', 'lat', 'lon', 'boundingbox');

					runCallbacks('queried', [box, results]);

				});

			},

			update: function(lat, lng, bounds) {

				if(!box.isMapReady)
					box._map();

				box.clearMarkers();

				box.map.addLayer(box.markerLayer);

				var position = {
					lat: lat,
					lon: lng
				};

				box.map.center(position);

				if(typeof bounds !== 'undefined') {
					var extent = new MM.Extent(
						parseFloat(bounds[1]),
						parseFloat(bounds[2]),
						parseFloat(bounds[0]),
						parseFloat(bounds[3])
					);
				}

				if(typeof extent !== 'undefined')
					box.map.setExtent(extent);

				var features = [
					{
						geometry: {
							coordinates: [lng, lat]
						}
					}
				];

				box.markerLayer.features(features);

			},

			clearMarkers: function() {

				box.map.removeLayer(box.markerLayer);

			}

		};

		function _results(results, addressKey, latKey, lngKey, boundsKey) {

			var resultsList = $('<ul />');

			function headers() {

				resultsContainer.append($('<p><strong>' + results.length + ' ' + geocode_localization.results_found + '</strong>'));
				resultsContainer.append(resultsList);

			}

			function appendResult(i, data) {

				var result = $('<li class="result-' + i + '" />');
				result
					.text(data[addressKey])
					.data('lat', data[latKey])
					.data('lng', data[lngKey])
					.data('bounds', data[boundsKey]);

				if(i === 0) {
					_updateFromItem(result);
				}

				resultsList.append(result);

			}

			function bindEvents() {

				resultsList.on('click', 'li', function() {

					resultsList.find('li').removeClass('active');
					$(this).addClass('active');

					_updateFromItem($(this));

				});

			}

			resultsContainer.empty();

			if(results.length) {

				headers();

				$.each(results, function(i, result) {

					appendResult(i, result);

				});

				bindEvents();

			} else {

				resultsContainer.append('<p>' + geocode_labels.not_found + '</p>');

			}

		}

		function _updateFromItem(el) {

			var address 	= el.text(),
				lat 		= parseFloat(el.data('lat')),
				lng 		= parseFloat(el.data('lng')),
				bounds 		= el.data('bounds');

			box.address().set(address);
			box.location().set(lat, lng);

			box.update(lat, lng, bounds);

		}

		/*
		 * Callback manager
		 */

		var callbacks = {};

		var createCallback = function(name) {
			callbacks[name] = [];
			box[name] = function(callback) {
				callbacks[name].push(callback);
			}
		}

		var runCallbacks = function(name, args) {
			if(!callbacks[name]) {
				return false;
			}
			if(!callbacks[name].length) {
				return false;	
			}

			var _run = function(callbacks) {
				if(callbacks) {
					_.each(callbacks, function(c, i) {
						if(c instanceof Function)
							c.apply(this, args);
					});
				}
			}
			_run(callbacks[name]);
		}

		createCallback('ready');
		createCallback('mapReady');
		createCallback('queried');
		createCallback('addressChanged');
		createCallback('locationChanged');
		createCallback('boundsChanged');

		if($.isReady)
			_init();
		else
			$(document).ready(_init);

		return box;

	}

	if(geocode_localization.autorun) {
		geocodeBox();
	}

})(jQuery);