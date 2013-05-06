var streetviewBox;

(function($) {

	mappress.streetview = function(options) {
		$(document).ready(function() {
			options.canvas = $('#' + options.containerID);
			$('body').addClass('displaying-map');
			enable(options);
		});
	}

	function enable(options) {

		var settings = {
			canvas: false,
			lat: 0,
			lng: 0,
			heading: 0,
			pitch: 0,
			callback: false
		};

		$.extend(settings, options);

		var panOptions = {
			position: new google.maps.LatLng(settings.lat, settings.lng),
			pov: {
				heading: settings.heading,
				pitch: settings.pitch
			},
			visible: true
		};

		panorama = new google.maps.StreetViewPanorama(settings.canvas[0], panOptions);

		if(typeof settings.callback === 'function')
			settings.callback(panorama);

		return panorama;
	}

	/*
	 * Editor settings
	 */

	streetviewBox = function(options) {

		var settings = {
			geocoder: false,
			containerID: 'mappress_streetview',
			force: false
		};

		if(typeof options === 'undefined')
			options = {};

		settings = $.extend(settings, options);

		var box = {};

		$.extend(box, {

			_init: function() {

				if(!box.geocoder.isGoogleMaps)
					return false;

				$.extend(box, $('#' + settings.containerID));

				if(!box.length)
					return false;

				box.canvas = box.find('#streetview_canvas');
				box.toggler = box.find('#enable_streetview');

				box.toggler.parent().hide();
				box.disable();

				box.geocoder.mapReady(box._initPanorama);

			},

			_initPanorama: function() {

				box.loadPanorama();

				var loc = box.geocoder.location().get();
				box.updatePosition(loc[0], loc[1]);

				box.updatePov(box.pitch.get(), box.heading.get());

				if(settings.force) {
					box.enable();
				} else {
					box.toggler.parent().show();
				}

				box.geocoder.locationChanged(function() {
					var loc = box.geocoder.location().get();
					box.updatePosition(loc[0], loc[1]);
				});

				box.toggler.change(function() {
					if($(this).is(':checked'))
						box.enable();
					else
						box.disable();
				});

			},

			loadPanorama: function() {

				box.panorama = enable({
					canvas: box.canvas,
					heading: 0,
					pitch: 0,
					callback: function(pan) {

						google.maps.event.addListener(pan, 'pov_changed', function() {

							box.pitch.set(pan.getPov().pitch);
							box.heading.set(pan.getPov().heading);

						});

					}
				});

				box.disable();

			},

			enable: function() {

				box.canvas.show();

			},

			disable: function() {

				box.canvas.hide();

			},

			updatePosition: function(lat, lng) {

				box.panorama.setPosition(new google.maps.LatLng(lat, lng));

				box.enable();

			},

			updatePov: function(pitch, heading) {

				box.panorama.setPov({
					heading: heading,
					pitch: pitch,
					zoom: 1
				});

				box.enable();

			},

			geocoder: settings.geocoder,

			pitch: {

				set: function(pitch) {
					box.find('#streetview_pitch').val(pitch);
				},

				get: function() {
					return parseFloat(box.find('#streetview_pitch').val());
				}
			},

			heading: {

				set: function(heading) {
					box.find('#streetview_heading').val(heading);
				},

				get: function() {
					return parseFloat(box.find('#streetview_heading').val());
				}
			}

		});

		box.geocoder.ready(box._init);

		return box;

	}

})(jQuery);