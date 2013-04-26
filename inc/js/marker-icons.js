(function($) {

	var	$container,
		$pointSelector,
		$icon,
		$point,
		$xinput,
		$yinput,
		defaultAnchor,
		position,
		anchorType;

	$(document).ready(function() {
		setup();
	});

	var setup = function() {

		$container = $('#marker-icon-metabox');

		$container.parents('form').attr('enctype', 'multipart/form-data');

		if(!$container.length)
			return false;

		$pointSelector = $container.find('.marker-icon-selector');
		$pointSelector.find('button').hide();

		$icon = $pointSelector.find('img');
		if($icon.length)
			parseIcon();
		else {
			$pointSelector.parent().hide();
			return false;
		}

		$container.find('.marker-icon-settings').show();

		$container.find('.enable-point-edit').click(function() {

			if($pointSelector.hasClass('editing'))
				disablePointEdit();

			$xinput = $('#' + $(this).data('xinput'));
			$yinput = $('#' + $(this).data('yinput'));
			anchorType = $(this).data('anchortype');
			pointEdit($(this).data('anchortype'));
			return false;
		});

		// mousehover log selector position
		$pointSelector.bind('mousemove', function(ev) {
			var $console = $pointSelector.find('.console.mouse');
			var $x = $console.find('.x');
			var $y = $console.find('.y');

			var position = getPosition(ev);
			$x.text(position[0]);
			$y.text(position[1]);
		});
	}

	var parseIcon = function() {
		$container.imagesLoaded(function() {
			// set position inside container
			$icon.css({
				'margin-top': $icon.height() / -2,
				'margin-left': $icon.width() / -2
			});
			// default positions
			defaultAnchor = function(type) {
				if(type == 'icon')
					return [parseInt($icon.width()/2), $icon.height()];
				if(type == 'popup')
					return [parseInt($icon.width()/2), -10];
			}
			$container.find('input#marker_icon_width').val($icon.width());
			$container.find('input#marker_icon_height').val($icon.height());
		});
	};

	var pointEdit = function() {
		
		$pointSelector.addClass('editing');
		$container.find('.tip').show();

		position = defaultAnchor(anchorType);

		if($xinput.val()) {
			position[0] = parseInt($xinput.val());
		}
		if($yinput.val()) {
			position[1] = parseInt($yinput.val());
		}

		$point = $('<div class="point" />');
		$pointSelector.append($point);

		updatePoint();
		storePosition();

		// bind events
		$(document).bind('keydown', bindPositionWithArrows);
		$pointSelector.bind('click', bindPointSelectorClick);
		$pointSelector.find('.save').show().bind('click', bindSaveButton);
		$pointSelector.find('.cancel').show().bind('click', bindCancelButton);
		$pointSelector.find('.use-default').show().bind('click', bindSetDefaultValues);
	}

	var disablePointEdit = function() {
		$pointSelector.removeClass('editing');

		// unbind events
		$(document).unbind('keydown', bindPositionWithArrows);
		$pointSelector.unbind('click', bindPointSelectorClick);
		$pointSelector.find('.save').hide().unbind('click', bindSaveButton);
		$pointSelector.find('.cancel').hide().unbind('click', bindCancelButton);
		$pointSelector.find('.use-default').hide().unbind('click', bindSetDefaultValues);

		$pointSelector.find('.point').remove();
		$container.find('.tip').hide();
	}

	var getPosition = function(e) {
		var clientX = e.clientX + $(window).scrollLeft();
		var clientY = e.clientY + $(window).scrollTop();
		return [clientX - $icon.offset().left, clientY - $icon.offset().top];
	}

	var storePosition = function() {
		$xinput.val(position[0]);
		$yinput.val(position[1]);
	}

	var updatePoint = function() {
		$point.css({
			top: $icon.position().top + position[1] - ($icon.height()/2),
			left: $icon.position().left + position[0] - ($icon.width()/2)
		});
		var $console = $pointSelector.find('.console.position');
		var $x = $console.find('.x');
		var $y = $console.find('.y');
		$x.text(position[0]);
		$y.text(position[1]);
	}

	var bindPointSelectorClick = function(e) {
		position = getPosition(e);
		updatePoint($point, position);
		return false;
	}

	var bindPositionWithArrows = function(e) {
		var amount;
		if(e.shiftKey)
			amount = 10;
		else
			amount = 1;
		if(e.keyCode == 37) {
			e.preventDefault();
			position[0] -= amount;
		}
		if(e.keyCode == 39) {
			e.preventDefault();
			position[0] += amount;
		}
		if(e.keyCode == 38) {
			e.preventDefault();
			position[1] -= amount;
		}
		if(e.keyCode == 40) {
			e.preventDefault();
			position[1] += amount;
		}
		updatePoint($point, position);
		if(e.keyCode == 13) {
			e.preventDefault();
			disablePointEdit();
			storePosition(position, $xinput, $yinput);
		}
		if(e.keyCode == 27) {
			e.preventDefault();
			disablePointEdit();
		}
	}

	var bindSaveButton = function() {
		storePosition(position, $xinput, $yinput);
		disablePointEdit();
		return false;
	}

	var bindCancelButton = function() {
		disablePointEdit();
		return false;
	}

	var bindSetDefaultValues = function() {
		position = defaultAnchor(anchorType);
		updatePoint($point, position);
		return false;
	}

})(jQuery);