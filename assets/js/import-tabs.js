/**
 * Import page tab switching without page reload.
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		var tabButtons  = document.querySelectorAll('.ccrmre-import-tab-btn');
		var tabContents = document.querySelectorAll('.ccrmre-import-tab-content');

		if ( ! tabButtons.length || ! tabContents.length ) {
			return;
		}

		function switchTab( targetTab ) {
			tabButtons.forEach(function( btn ) {
				if ( btn.getAttribute('data-tab') === targetTab ) {
					btn.classList.add('active');
				} else {
					btn.classList.remove('active');
				}
			});

			tabContents.forEach(function( content ) {
				if ( content.getAttribute('data-tab') === targetTab ) {
					content.style.display = '';
				} else {
					content.style.display = 'none';
				}
			});
		}

		tabButtons.forEach(function( btn ) {
			btn.addEventListener('click', function() {
				switchTab( this.getAttribute('data-tab') );
			});
		});

		// Default to first available tab button.
		switchTab( tabButtons[0].getAttribute('data-tab') );
	});
})();
