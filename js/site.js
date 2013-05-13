(function($) {

	$(document).ready(function() {

		$('.posts-section').each(function() {

			$(this).find('.posts-list').isotope({
				itemSelector: 'li',
				layoutMode: 'masonry'
			});

		});

	});

})(jQuery);