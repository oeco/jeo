var geocodeBox;

(function($) {

	geocodeBox = function(options) {

		var box = {},
			latInput,
			lngInput,
			cityInput,
			countryInput,
			boundsInput,
			addressInput,
			geocodeButton,
			resultsContainer,
			mapContainer,
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
			box.close();
		}

		box.close = function() {
			mapContainer.hide();
			runCallbacks('closed');
		}

		box.open = function() {
			mapContainer.show();
			runCallbacks('opened');
		}

		box.geocode = function() {
			box.open();
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
					return [parseFloat(latInput.val()), parseFloat(lngInput.val())];
				},
				getLat: function() {
					return parseFloat(latInput.val());
				},
				getLng: function() {
					return parseFloat(lngInput.val());
				}
			}
			return f;
		}

		box.city = function() {
			f = {
				set: function(city) {
					cityInput.val(city);
				},
				get: function() {
					return cityInput.val();
				}
			}
			return f;
		}

		box.country = function() {
			f = {
				set: function(country) {
					countryInput.val(country);
				},
				get: function() {
					return countryInput.val();
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
			cityInput 			= box.find('#geocode_city');
			countryInput 		= box.find('#geocode_country');
			boundsInput 		= box.find('#geocode_viewport');
			addressInput 		= box.find('#geocode_address');
			resultsContainer 	= box.find('.results');
			mapContainer		= box.find('.geocode-map-container');

			if(geocode_localization.type == 'latlng') {
				var latLngInputs = $('.latlng-container').clone(true);
				box.empty().append(latLngInputs);
				return box;
			}

			mapContainer.hide();

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
			addressInput.bind('keypress', function(e) {
				var code = (e.keyCode ? e.keyCode : e.which);
				if(code === 13) {
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

				mapContainer.show();

				if(!box.isMapReady) {

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
					setTimeout(function() {
						runCallbacks('mapReady', [box]);
					}, 500);

				}

				return box.map;

			},

			_geocode: function(args, callback) {

				if(!box.geocoder)
					box.geocoder = new google.maps.Geocoder();

				box.geocoder.geocode(args, callback);
			},

			_getAddress: function(address, reversed) {

				var args = { address: address };

				if(reversed) {
					args = { location: new google.maps.LatLng(address[0], address[1]) };
				}

				box._geocode(args, function(results, status) {

					if(status == google.maps.GeocoderStatus.OK) {

						_results(box._formatResults(results), 'address', 'lat', 'lng', 'bounds', 'city', 'country');

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
						bounds: result.geometry.viewport,
						city: box._getAddressComponent(result, 'locality', 'long_name'),
						country: box._getAddressComponent(result, 'country', 'long_name')
					});
				});
				return formattedResults;

			},

			_getAddressComponent: function(result, c, nameType) {
				var val;
				$.each(result.address_components, function(i, component) {
					if(component.types[0] == c) {
						val = component[nameType];
						return false;
					}
				});
				return val;
			},

			_convertToViewport: function(bounds) {

				if(typeof bounds !== 'string')
					return bounds;

				var viewport = bounds.split('), (');

				if(viewport.length !== 2)
					return false;

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

						google.maps.event.addListener(box.marker, 'dragend', function() {
							box._getAddress([ box.marker.position.lat(), box.marker.position.lng() ], true);
						});

					} else {

						box.marker.setPosition(position);

					}

					if(!box.isMapReady)
						box._map(position);

					box.map.setCenter(position);
					box.map.setZoom(2);

					if(box.marker)
						box.marker.setMap(box.map);

					if(typeof bounds !== 'undefined') {
						var viewport = box._convertToViewport(bounds);
						if(viewport)
							box.map.fitBounds(viewport);
					}

				}

			},

			clearMarkers: function() {

				if(box.marker)
					box.marker.setMap(null);

				if(box.isMapReady) {
					box.map.setCenter(new google.maps.LatLng(0,0));
					box.map.setZoom(1);
				}

			}

		}

		var osm = {

			isOSM: true,

			_map: function(lat, lng, bounds) {

				mapContainer.show();

				box.markerLayer = new L.layerGroup();
				box.map = new L.map(mapCanvasID);
				box.map.setView([0,0], 2);

				box.map.addLayer(L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png'));
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

				box.map.invalidateSize(true);

				box.clearMarkers();

				if(lat && lng) {

					if($.isArray(bounds))
						box.map.fitBounds([
							[
								parseFloat(bounds[0]),
								parseFloat(bounds[2])
							],
							[
								parseFloat(bounds[1]),
								parseFloat(bounds[3])
							]
						]);
					
					box.map.panTo([lat, lng]);

					var features = [
						{
							type: 'Feature',
							geometry: {
								type: 'Point',
								coordinates: [lng, lat]
							}
						}
					];

					box.markerLayer.addLayer(new L.geoJson({type:'FeatureCollection', features: features}));

				}

			},

			clearMarkers: function() {

				box.markerLayer.clearLayers();

			}

		};

		function _results(results, addressKey, latKey, lngKey, boundsKey, cityKey, countryKey) {

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

				if(typeof cityKey !== 'undefined')
					result.data('city', data[cityKey]);

				if(typeof countryKey !== 'undefined')
					result.data('country', data[countryKey]);

				if(i === 0) {
					_updateFromItem(result);
				}

				resultsList.append(result);

			}

			function bindEvents() {

				resultsList.on('click', 'li', function() {

					resultsList.find('li').removeClass('active');
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

				resultsContainer.append('<p>' + geocode_localization.not_found + '</p>');

			}

		}

		function _updateFromItem(el) {

			el.addClass('active');

			var address 	= el.text(),
				lat 		= parseFloat(el.data('lat')),
				lng 		= parseFloat(el.data('lng')),
				bounds 		= el.data('bounds');

			box.address().set(address);
			box.location().set(lat, lng);

			if(el.data('city'))
				box.city().set(el.data('city'));

			if(el.data('country'))
				box.country().set(el.data('country'));

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
		createCallback('opened');
		createCallback('closed');
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