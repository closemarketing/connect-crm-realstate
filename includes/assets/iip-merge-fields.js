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
		// Select2 configuration.
		var select2Config = {
			placeholder: ccrmreMergeFields.searchPlaceholder,
			allowClear: true,
			width: '100%',
			tags: true,
			createTag: function (params) {
				var term = $.trim(params.term);

				if (term === '') {
					return null;
				}

				// Validate field name: only lowercase, numbers, and underscores.
				var sanitized = term.toLowerCase().replace(/[^a-z0-9_]/g, '_');

				return {
					id: sanitized,
					text: sanitized + ' ' + ccrmreMergeFields.newFieldLabel,
					newTag: true
				};
			},
			templateResult: function(data) {
				var $result = $('<span></span>');
				$result.text(data.text);

				if (data.newTag) {
					$result.addClass('ccrmre-new-tag');
					$result.prepend('<span class="dashicons dashicons-plus-alt" style="font-size: 14px; margin-right: 5px;"></span>');
				}

				return $result;
			}
		};

		// Add language if available.
		if (typeof $.fn.select2.amd !== 'undefined' && $.fn.select2.amd.require('select2/i18n/es')) {
			select2Config.language = 'es';
		}

		// Initialize Select2 on merge fields.
		$('.ccrmre-select2-field').select2(select2Config);

		// Clear all selects button handler.
		$('#ccrmre-clear-all-selects-btn').on('click', function(e) {
			e.preventDefault();
			if (!confirm(ccrmreMergeFields.confirmClearAll)) {
				return;
			}
			$('.ccrmre-select2-field').each(function() {
				$(this).val(null).trigger('change');
			});
			showNotice('success', ccrmreMergeFields.clearAllDone);
		});

		// Auto-map fields button handler.
		$('#ccrmre-auto-map-btn').on('click', function(e) {
			e.preventDefault();

			// Confirm action.
			if (!confirm(ccrmreMergeFields.confirmAutoMap)) {
				return;
			}

			var $btn = $(this);
			var originalHtml = $btn.html();

			// Disable button and show loading.
			$btn.prop('disabled', true).html(
				'<span class="dashicons dashicons-update-alt spin" style="margin-top: 3px;"></span> ' +
				ccrmreMergeFields.autoMapping
			);

			// Make AJAX request.
			$.ajax({
				url: ccrmreMergeFields.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ccrmre_auto_map_fields',
					nonce: ccrmreMergeFields.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update select2 fields with new mappings.
						$.each(response.data.mappings, function(crmField, wpField) {
							var $select = $('select[name="ccrmre_merge_fields[' + crmField + ']"]');
							if ($select.length) {
								// Check if option exists, if not create it.
								if ($select.find('option[value="' + wpField + '"]').length === 0) {
									var newOption = new Option(wpField + ' ' + ccrmreMergeFields.newFieldLabel, wpField, true, true);
									$select.append(newOption);
								} else {
									$select.val(wpField);
								}
								$select.trigger('change');
							}
						});

						// Show success message.
						showNotice('success', response.data.message);

						// Auto-submit form to save.
						setTimeout(function() {
							$('#ccrmre-merge-form').submit();
						}, 1500);
					} else {
						showNotice('error', response.data.message || ccrmreMergeFields.autoMapError);
						$btn.prop('disabled', false).html(originalHtml);
					}
				},
				error: function() {
					showNotice('error', ccrmreMergeFields.autoMapError);
					$btn.prop('disabled', false).html(originalHtml);
				}
			});
		});

		/**
		 * Show admin notice
		 *
		 * @param {string} type Notice type (success, error, warning, info).
		 * @param {string} message Notice message.
		 */
		function showNotice(type, message) {
			var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
			$('.wrap h1').after($notice);

			// Auto-dismiss after 5 seconds.
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		}
	});

})(jQuery);

