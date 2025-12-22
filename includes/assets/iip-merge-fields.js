/**
 * Merge Fields JavaScript
 *
 * Initializes Select2 for merge fields page.
 *
 * @package Connect CRM Real State
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize Select2 on merge fields.
		$('.ccrmre-select2-field').select2({
			placeholder: ccrmreMergeFields.searchPlaceholder,
			allowClear: true,
			width: '100%'
		});
	});

})(jQuery);
