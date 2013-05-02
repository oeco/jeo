(function($) {

	var $container,
		$toggle,
		$canvas,
		panorama,
		lat,
		lng,
		pitch,
		heading;

	mappress.streetview = function(options) {
		$(document).ready(function() {
			$canvas = $('#' + options.containerID);
			lat = options.lat;
			lng = options.lng;
			pitch = options.pitch;
			heading = options.heading;
			$('body').addClass('displaying-map');
			enableStreetView();
		});
	}

	function enableStreetView() {
		$canvas.show();
		var options = {
			position: new google.maps.LatLng(lat, lng),
			pov: {
				heading: heading,
				pitch: pitch
			},
			visible: true
		};
		panorama = new google.maps.StreetViewPanorama($canvas[0], options);

		if($('#streetview_pitch').length) {
			google.maps.event.addListener(panorama, 'pov_changed', function() {
				$('#streetview_pitch').val(panorama.getPov().pitch);
				$('#streetview_heading').val(panorama.getPov().heading);
			});
		}
	}

	/*
	 * Editor settings
	 */

	$(document).ready(function() {
		$container = $('#mappress_streetview');

		if(!$container.length)
			return false;

		$toggle = $container.find('#enable_streetview');
		$canvas = $container.find('#streetview_canvas');

		updateSettings();
		checkLatLng();

		$('#geocode_lat, #geocode_lon').bind('change', function() {
			checkLatLng();
		});

		$toggle.change(function() {
			if($(this).is(':checked'))
				enableStreetView();
			else
				disableStreetView();
		});
	});

	function checkLatLng() {
		if($('#geocode_lat').val() && $('#geocode_lon').val()) {
			$toggle.show();
			lat = $('#geocode_lat').val();
			lng = $('#geocode_lon').val();
		} else {
			disableStreetView();
			$toggle.hide();
			lat = false;
			lng = false;
			return false;
		}
		if($toggle.is(':checked'))
			enableStreetView();
	}

	function updateSettings() {
		pitch = parseFloat($('#streetview_pitch').val());
		heading = parseFloat($('#streetview_heading').val());
	}

	function disableStreetView() {
		$canvas.hide();
		$toggle.attr('checked', false);
	}

})(jQuery);