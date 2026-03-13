(function( $ ) {
	'use strict';

	$(function() {
		// Check for donationbox_url parameter in the URL
		var urlParams = new URLSearchParams(window.location.search);
		var donationboxUrl = urlParams.get('donationbox_url');

		if (donationboxUrl) {
			// Create lightbox overlay
			var $overlay = $('<div>', {
				'class': 'donationbox-lightbox-overlay'
			});

			var $container = $('<div>', {
				'class': 'donationbox-lightbox-container'
			});

			var $closeBtn = $('<button>', {
				'class': 'donationbox-lightbox-close',
				'type': 'button',
				'aria-label': 'Close'
			}).html('&times;');

			var $iframe = $('<iframe>', {
				'class': 'donationbox-lightbox-iframe',
				'src': donationboxUrl,
				'frameborder': '0',
				'allowtransparency': 'true'
			});

			$container.append($closeBtn, $iframe);
			$overlay.append($container);
			$('body').append($overlay);

			// Prevent body scrolling when lightbox is open
			$('body').addClass('donationbox-lightbox-open');

			// Close lightbox on button click
			$closeBtn.on('click', function() {
				$overlay.remove();
				$('body').removeClass('donationbox-lightbox-open');

				// Clean URL parameters
				urlParams.delete('donationbox_url');
				urlParams.delete('payment_id');
				var cleanUrl = window.location.pathname;
				var remaining = urlParams.toString();
				if (remaining) {
					cleanUrl += '?' + remaining;
				}
				window.history.replaceState({}, document.title, cleanUrl);
			});

			// Close lightbox on overlay background click
			$overlay.on('click', function(e) {
				if (e.target === $overlay[0]) {
					$closeBtn.trigger('click');
				}
			});

			// Close lightbox on Escape key
			$(document).on('keydown.donationboxLightbox', function(e) {
				if (e.key === 'Escape') {
					$closeBtn.trigger('click');
					$(document).off('keydown.donationboxLightbox');
				}
			});
		}
	});

})( jQuery );
