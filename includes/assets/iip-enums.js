/**
 * Enums Import JavaScript
 *
 * Handles AJAX import of CRM enum values with live progress feedback.
 *
 * @package Connect CRM Real State
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		var $btn    = $('#ccrmre-import-enums-btn');
		var $log    = $('#ccrmre-enums-log');
		var $list   = $('#ccrmre-enums-log-list');
		var $tables = $('#ccrmre-enums-tables');

		$btn.on('click', function () {
			$btn.prop('disabled', true).text(ccrmreEnums.importing);
			$list.empty();
			$log.show();
			$tables.empty();

			$.post(
				ccrmreEnums.ajaxUrl,
				{
					action: 'ccrmre_import_enums',
					nonce:  ccrmreEnums.nonce,
				},
				function (response) {
					$btn.prop('disabled', false).text($btn.data('original-text'));

					if (!response.success) {
						$list.append(
							$('<li>').css('color', '#cc0000').text(
								response.data && response.data.message
									? response.data.message
									: ccrmreEnums.importError
							)
						);
						return;
					}

					var steps = response.data.steps || [];

					if (steps.length === 0) {
						$list.append($('<li>').text(ccrmreEnums.importError));
						return;
					}

					steps.forEach(function (step) {
						var $item = $('<li>');
						if (step.error) {
							$item.css('color', '#cc0000').text(step.key + ': ' + step.error);
						} else {
							$item.css('color', '#46b450').text(
								step.key + ': ' + step.count + ' values saved'
							);
						}
						$list.append($item);
					});

					// Reload page after short delay so the tables render from cache.
					setTimeout(function () {
						window.location.reload();
					}, 1500);
				},
				'json'
			).fail(function () {
				$btn.prop('disabled', false).text($btn.data('original-text'));
				$list.append($('<li>').css('color', '#cc0000').text(ccrmreEnums.importError));
			});
		});

		// Store original button text for restore after request.
		$btn.data('original-text', $btn.text().trim());
	});
}(jQuery));
