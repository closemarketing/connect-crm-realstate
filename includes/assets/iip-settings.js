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
				'#numagencia',
				'#ccrmre_inmovilla_ia',
				'#ccrmre_inmovilla_ib'
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

		function toggleIbOverride() {
			$('#ccrmre_inmovilla_ib').prop('disabled', ! $('#ccrmre_inmovilla_ib_override').is(':checked'));
		}

		// Initial state on page load.
		toggleInmovillaFields();
		toggleIbOverride();

		// Toggle on CRM type change.
		$('#type').on('change', function() {
			toggleInmovillaFields();
		});

		$('#ccrmre_inmovilla_ib_override').on('change', function() {
			toggleIbOverride();
		});
	});

})(jQuery);
