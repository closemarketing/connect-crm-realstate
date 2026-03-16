/**
 * Import Properties page: load and display statistics via AJAX.
 */
var loadImportStats;

(function() {
	'use strict';

	loadImportStats = function() {
		var btn = document.getElementById('refresh_stats');
		var cards = document.querySelectorAll('.ccrmre-stat-card');

		if ( ! btn || btn.disabled ) {
			return;
		}

		btn.disabled = true;
		cards.forEach(function(card) {
			card.classList.add('loading');
		});

		jQuery.ajax({
			url: typeof ajaxurl !== 'undefined' ? ajaxurl : '',
			type: 'POST',
			data: {
				action: 'ccrmre_get_import_stats',
				security: typeof ccrmreImportStats !== 'undefined' ? ccrmreImportStats.nonce : ''
			},
			success: function(response) {
				if ( response.success ) {
					document.getElementById('stat-available-count').textContent = response.data.available_count.toLocaleString();
					document.getElementById('stat-api-count').textContent = response.data.api_count.toLocaleString();
					var filteredByProvince = typeof response.data.filtered_by_province_count !== 'undefined' ? response.data.filtered_by_province_count : 0;
					var wrap = document.getElementById('stat-filtered-province-wrap');
					if ( wrap ) {
						document.getElementById('stat-filtered-province-count').textContent = filteredByProvince.toLocaleString();
						wrap.style.display = filteredByProvince > 0 ? '' : 'none';
					}
					document.getElementById('stat-wp-count').textContent = response.data.wp_count.toLocaleString();
					document.getElementById('stat-import-count').textContent = response.data.import_count.toLocaleString();
					document.getElementById('stat-new-count').textContent = response.data.new_count.toLocaleString();
					document.getElementById('stat-outdated-count').textContent = response.data.outdated_count.toLocaleString();
					document.getElementById('stat-delete-count').textContent = response.data.delete_count.toLocaleString();
				} else {
					showStatsError( response.data && response.data.message ? response.data.message : 'Unknown error' );
				}
			},
			error: function(xhr, status, error) {
				var msg = ( typeof ccrmreImportStats !== 'undefined' && ccrmreImportStats.errorLoadingStatistics ) ? ccrmreImportStats.errorLoadingStatistics : 'Error loading statistics';
				showStatsError( msg );
			},
			complete: function() {
				btn.disabled = false;
				cards.forEach(function(card) {
					card.classList.remove('loading');
				});
			}
		});
	}

	function showStatsError(message) {
		var existingNotice = document.querySelector('.ccrmre-stats-error');
		if ( existingNotice ) {
			existingNotice.remove();
		}

		var label = ( typeof ccrmreImportStats !== 'undefined' && ccrmreImportStats.statisticsErrorLabel ) ? ccrmreImportStats.statisticsErrorLabel : 'Statistics Error:';
		var notice = document.createElement('div');
		notice.className = 'notice notice-error ccrmre-stats-error';
		notice.style.marginTop = '10px';
		notice.innerHTML = '<p><strong>' + ( label ) + '</strong> ' + message + '</p>';

		var statsContainer = document.querySelector('.ccrmre-import-stats');
		if ( statsContainer ) {
			statsContainer.parentNode.insertBefore( notice, statsContainer.nextSibling );
		}
	}

	jQuery(document).ready(function() {
		loadImportStats();
	});
})();
