/**
 * Settings Page JavaScript
 *
 * Handles dynamic field visibility on settings page.
 *
 * @package Connect CRM Real State
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		/**
		 * Toggle visibility of Inmovilla-specific fields
		 */
		function toggleInmovillaFields() {
			var selectedType = $('#type').val();
			var inmovillaFields = [
				'#numagencia'
			];

			inmovillaFields.forEach(function(fieldId) {
				var row = $(fieldId).closest('tr');
				if (selectedType === 'inmovilla') {
					row.show();
				} else {
					row.hide();
				}
			});
		}

		// Initial state on page load.
		toggleInmovillaFields();

		// Toggle on CRM type change.
		$('#type').on('change', function() {
			toggleInmovillaFields();
		});
	});

})(jQuery);
