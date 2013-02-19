(function($) {

	var maps = {};

	$(document).ready(function() {
		// update map list
		var $maps = $('#mapgroup-metabox .map-list li');

		$maps.parent().sortable();

		$maps.each(function() {
			map_id = $(this).find('.map_id').val();
			maps[map_id] = {};
			if($(this).find('more_list:checked').length)
				maps[map_id].type = 'more';
			else
				maps[map_id].type = 'display';
		});

		$('.include-map').click(function() {
			var map_id = $('#mapgroup_maps :selected').val();
			var map_title = $('#mapgroup_maps :selected').text();
			addMap(map_id, map_title);
			return false;
		});
		$('.more_list').live('change', function() {
			var map_id = $(this).parents('li').find('.map_id');
			if($(this).is(':checked')) {
				updateMap(map_id, 'more');
			} else {
				updateMap(map_id, 'display');
			}
		});
		$('.remove-map').live('click', function() {
			var map_id = $(this).parents('li').find('.map_id').val();
			removeMap(map_id);
			return false;
		});
	});

	function addMap(map_id, map_title) {
		if(maps[map_id])
			return false;
		var item = $(mapgroup_metabox_localization.map_item);
		var mapList = $('#mapgroup-metabox .map-list');
		var mapLength = mapList.find('li').length;

		item.addClass('map-' + map_id);
		item.find('.title').text(map_title);
		item.find('.map_id').attr('name', 'mapgroup_data[maps][' + mapLength + '][id]').val(map_id);
		item.find('.map_title').attr('name', 'mapgroup_data[maps][' + mapLength + '][title]').val(map_title);
		item.find('.more_list').attr('name', 'mapgroup_data[maps][' + mapLength + '][more_list]').val(map_id);

		maps[map_id] = {};
		maps[map_id].type = 'display';

		mapList.append(item);
		return mapList;
	}

	function removeMap(map_id) {
		delete maps[map_id];
		$('#mapgroup-metabox .map-list .map-' + map_id).remove();
	}

	function updateMap(map_id, map_type) {
		maps[map_id].type = map_type;
		return maps[map_id];
	}

})(jQuery);