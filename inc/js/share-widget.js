var BASEURL = jeo_share_widget_settings.baseurl + '?';
var DEFAULTMAP = jeo_share_widget_settings.defaultmap;

// indexOf shim via
// developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Array/indexOf
if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
		'use strict';
		if (this === null) {
			throw new TypeError();
		}
		var t = Object(this);
		var len = t.length >>> 0;
		if (len === 0) {
			return -1;
		}
		var n = 0;
		if (arguments.length > 1) {
			n = Number(arguments[1]);
			if (n !== n) { // shortcut for verifying if it's NaN
				n = 0;
			} else if (n !== 0 && n != Infinity && n != -Infinity) {
				n = (n > 0 || -1) * Math.floor(Math.abs(n));
			}
		}
		if (n >= len) {
			return -1;
		}
		var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
		for (; k < len; k++) {
			if (k in t && t[k] === searchElement) {
				return k;
			}
		}
		return -1;
	};
}

(function($) {

	// Utils
	// ========================

	// Match on type of property
	function startsWith(string, pattern) {
		return string.slice(0, pattern.length) === pattern;
	}

	// value from url property splitting
	function value(string) {
		return string.split('=')[1];
	}

	// Group template partials into an accessible object
	var templates =  _($('script[data-template]')).reduce(function(memo, el) {
		memo[el.getAttribute('data-template')] = _(el.innerHTML).template();
		return memo;
	}, {});

	// All textarea in the app should auto select
	// its content if the element is in focus
	function autoSelect($el) {
		$el.focus(function() {
			$textarea = $(this);
			$textarea.select();
			// Unbind the mouseup event for chrome
			$textarea.mouseup(function() {
				$textarea.off('mouseup');
				return false;
			});
		});
	}
	var jeo_share_widget = {};

	// Widget Controls View
	// ========================
	jeo_share_widget.controls = function() {
		var $context = $('#jeo-share-widget');
		var $maps = $('#maps');
		var $stories = $('#stories');
		var $output = $('#output');
		var iframe = document.getElementById('iframe');
		var hash = location.href.split('#/')[1];

		// autoselect the contents of the textarea
		autoSelect($output);

		var embed = {
			p: undefined,
			tax: undefined,
			term: undefined,
			map_only: undefined,
			map_id: DEFAULTMAP,
			width: 960,
			height: 480
		};

		if($('#map-select').length) {
			$('#map-select').find('option[value="' + DEFAULTMAP + '"]').attr('selected', 'selected');
			$('a.select-map-lapers').attr('href', '?map_id=' + DEFAULTMAP);
		}

		function serialize(obj) {
			var str = [];
			for(var p in obj) {
				if(obj[p] && typeof obj[p] !== 'undefined') str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
			}
			return str.join("&");
		}

		function updateOutput() {
			$output.html('<iframe src="' + BASEURL + serialize(embed) + '" width="' + embed.width + '" height="' + embed.height + '" frameborder="0"></iframe>');
		}

		function updateIframe() {
			//location.href = '#/' + serialize();
			//iframe.src = BASEURL + serialize();
			$('#widget-content').html($('<iframe id="iframe" src="' + BASEURL + serialize(embed) + '" frameborder="0"></iframe>'));
			iframe = document.getElementById('iframe');
		}

		var updateInputs = function() {

			var val = $(this).val();

			if (this.id === 'content-select') {

				if (val.split('&')[0].indexOf('tax') !== -1) {
					embed.p = undefined;
					embed.tax = val.split('&')[0].split('tax_')[1];
					embed.term = val.split('&')[1];
					embed.map_only = undefined;

				} else if (val.split('&')[0] === 'post') {
					embed.p = val.split('&')[1];
					embed.tax = undefined;
					embed.term = undefined;
					embed.map_only = undefined;

				} else if (val === 'map-only') {
					embed.p = undefined;
					embed.tax = undefined;
					embed.term = undefined;
					embed.map_only = 1;

				} else if (val === 'latest') {
					embed.p = undefined;
					embed.tax = undefined;
					embed.term = undefined;
					embed.map_only = undefined;

				} else {
					embed.p = val;
					embed.tax = undefined;
					embed.map_only = undefined;
				}

			} else if (this.id === 'map-select') {

				embed.map_id = val;
				$('a.select-map-layers').attr('href', '?map_id=' + val);

			} else if (this.id === 'layers-select') {

				if($('#layers-select').data('mapid'))
					embed.map_id = $('#layers-select').data('mapid');
				else
					embed.map_id = undefined;

				embed.layers = undefined;

				if($('#layers-select').val()) {
					
					if($('#layers-select').val().length === $('#layers-select option').length) {

						if(!embed.map_id)
							embed.layers = $('#layers-select').val().join();
						$('.clear-layers').hide();

					} else {

						embed.layers = $('#layers-select').val().join();
						$('.clear-layers').show();

					}

				}

			} else if (this.id === 'map_id') {

				embed.map_id = val;

			}

			// Defer these next actions until the
			// stack is cleared. the change event otherwise fires too quickly.
			_.defer(function() {
				updateOutput();
				updateIframe();
			});

		}

		$('.chzn-select, #jeo-share-widget input').each(updateInputs);
		$('.chzn-select').chosen().change(updateInputs);

		$('.clear-layers').click(function() {
			$('#layers-select option').attr('selected', 'selected').trigger('liszt:updated');
			$('.chzn-select').change();
			selectorsTooltip();
			return false;
		});
		selectorsTooltip();

		/*
		 * Chosen selectors tooltip
		 */
		function selectorsTooltip() {
			$('.search-choice').on('mouseover', 'span', function() {
				$(this).parent().append('<p class="search-tip">' + $(this).text() + '</p>');
			});

			$('.search-choice').on('mouseout', 'span', function() {
				$(this).parent().find('.search-tip').remove();
			});
		}

		$('#output').focus(function() {

			embed.lat = parseFloat($('#iframe').contents().find('#latitude').val())
			embed.lon = parseFloat($('#iframe').contents().find('#longitude').val());
			embed.zoom = parseInt($('#iframe').contents().find('#zoom').val());

			$('.zoom .val').text(embed.zoom);
			$('.latitude .val').text(embed.lat);
			$('.longitude .val').text(embed.lon);

			updateOutput();

			$(this).select();
			// Unbind the mouseup event for chrome
			$(this).mouseup(function() {
				$textarea.off('mouseup');
				return false;
			});

		});

		$('.grab-centerzoom').click(function() {

			embed.lat = parseFloat($('iframe').contents().find('#latitude').val())
			embed.lon = parseFloat($('iframe').contents().find('#longitude').val());
			embed.zoom = parseInt($('iframe').contents().find('#zoom').val());

			$('.zoom .val').text(embed.zoom);
			$('.latitude .val').text(embed.lat);
			$('.longitude .val').text(embed.lon);

			updateOutput();
			updateIframe();

			return false;

		});

		$('.default-centerzoom').click(function() {

			embed.lat = embed.lon = embed.zoom = undefined;

			$('.zoom .val, .latitude .val, .longitude .val').text(jeo_share_widget_settings.default_label);

			updateOutput();
			updateIframe();

			return false;

		});

		$('#widget-content').css({
			'width': '960px',
			'height': '480px'
		});

		$('#sizes a').click(function() {
			var width = $(this).data('width');
			var height = $(this).data('height');
			var size = $(this).data('size');

			$('#widget-content').css({
				'width': width + 'px',
				'height': height + 'px'
			});

			$('#sizes a').removeClass('active');
			$(this).addClass('active');

			embed.width = width;
			embed.height = height;

			// re-run
			updateOutput();
			return false;
		});
	};

	this.jeo_share_widget = jeo_share_widget;

})(jQuery);
