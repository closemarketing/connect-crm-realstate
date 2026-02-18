document.addEventListener('DOMContentLoaded', function() {
	// Tab switching functionality.
	const tabButtons = document.querySelectorAll('.ccrmre-tab-button');
	const tabPanes = document.querySelectorAll('.ccrmre-tab-pane');
	
	tabButtons.forEach(button => {
		button.addEventListener('click', function() {
			const targetTab = this.getAttribute('data-tab');
			
			// Remove active class from all buttons and panes.
			tabButtons.forEach(btn => btn.classList.remove('active'));
			tabPanes.forEach(pane => pane.classList.remove('active'));
			
			// Add active class to clicked button and corresponding pane.
			this.classList.add('active');
			document.getElementById('tab-' + targetTab).classList.add('active');
		});
	});

	// Accordion functionality for automatic sync logs.
	const logItems = document.querySelectorAll('.ccrmre-log-item');
	
	logItems.forEach(item => {
		const header = item.querySelector('.ccrmre-log-header');
		const content = item.querySelector('.ccrmre-log-content');
		const toggle = item.querySelector('.ccrmre-log-toggle');
		
		header.addEventListener('click', function() {
			const isOpen = content.style.display === 'block';
			
			if (isOpen) {
				// Close accordion.
				content.style.display = 'none';
				toggle.classList.remove('dashicons-arrow-down');
				toggle.classList.add('dashicons-arrow-right');
				item.classList.remove('active');
			} else {
				// Open accordion and load content if not already loaded.
				content.style.display = 'block';
				toggle.classList.remove('dashicons-arrow-right');
				toggle.classList.add('dashicons-arrow-down');
				item.classList.add('active');
				
				// Load content via AJAX if not already loaded.
				if (!content.classList.contains('loaded')) {
					const filename = item.getAttribute('data-filename');
					
					fetch(ajaxAction.url, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'action=ccrmre_load_log_content&nonce=' + ajaxAction.nonce + '&filename=' + encodeURIComponent(filename),
					})
					.then(resp => resp.json())
					.then(function(response) {
						if (response && response.success) {
							content.innerHTML = '<div class="ccrmre-log-data">' + response.data.content + '</div>';
							content.classList.add('loaded');
						} else {
							const errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Error loading log';
							content.innerHTML = '<div class="error" style="padding: 10px; color: red;">' + errorMsg + '</div>';
						}
					})
					.catch(err => {
						console.error('Error loading log:', err);
						content.innerHTML = '<div class="error" style="padding: 10px; color: red;">Error: ' + err.message + '</div>';
					});
				}
			}
		});
	});
});

/**
 * Shows a countdown in the log and retries after waiting.
 */
function startWaitCountdown( element, totalSeconds, callback ) {
	const endTime = Date.now() + ( totalSeconds * 1000 );

	const countdownEl = document.createElement('p');
	countdownEl.style.cssText = 'color: #856404; background: #fff3cd; padding: 8px 12px; border-left: 4px solid #ffc107; margin: 5px 0;';

	const loglist = document.querySelector('#logwrapper #loglist');
	if ( loglist ) {
		loglist.appendChild(countdownEl);
	}

	function tick() {
		const remaining = Math.max( 0, Math.ceil( ( endTime - Date.now() ) / 1000 ) );

		if ( remaining <= 0 ) {
			const resumeLabel = ajaxAction.label_resuming || 'Resuming import...';
			countdownEl.innerHTML = '[' + new Date().toLocaleTimeString() + '] <strong style="color:green;">&#10003; ' + resumeLabel + '</strong>';
			element.textContent = ajaxAction.label_syncing;
			if ( loglist ) {
				loglist.scrollTo({ top: loglist.scrollHeight, behavior: 'smooth' });
			}
			callback();
			return;
		}

		const label = ajaxAction.label_rate_limit
			? ajaxAction.label_rate_limit.replace( '%s', remaining )
			: 'Waiting ' + remaining + ' seconds...';

		countdownEl.innerHTML = '<span class="dashicons dashicons-clock" style="margin-right: 5px;"></span>' + label;
		element.textContent = ajaxAction.label_waiting + ' (' + remaining + 's)';

		if ( loglist ) {
			loglist.scrollTo({ top: loglist.scrollHeight, behavior: 'smooth' });
		}

		setTimeout( tick, 1000 );
	}

	tick();
}

function syncManualProperties( element, loop = 0, pagination, totalprop = 0, isRetry = false ) {
	// Get the spinner element and mode select.
	const spinner = element.parentElement.querySelector('.spinner');
	const importMode = document.getElementById('import-mode');
	const refreshButton = document.getElementById('refresh_stats');
	const mode = importMode ? importMode.value : 'updated';

	// Switch to manual tab and clear log when starting a new import (loop 0, not a retry).
	if ( loop === 0 && ! isRetry ) {
		// Activate manual tab.
		const manualTabButton = document.querySelector('.ccrmre-tab-button[data-tab="manual"]');
		if ( manualTabButton ) {
			manualTabButton.click();
		}

		// Clear manual log.
		const loglist = document.querySelector('#logwrapper #loglist');
		if ( loglist ) {
			loglist.innerHTML = '';
		}
	}

	// Add disabled state and show spinner.
	element.disabled = true;
	element.textContent = ajaxAction.label_syncing;
	if ( importMode ) {
		importMode.disabled = true;
	}
	if ( refreshButton ) {
		refreshButton.disabled = true;
	}
	if ( spinner ) {
		spinner.classList.add('is-active');
	}

	const isOdd = number => number % 2 !== 0;
	const classTask = isOdd(loop) ? 'odd' : 'even';

	// AJAX request.
	fetch( ajaxAction.url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Cache-Control': 'no-cache',
		},
		body: 'action=manual_import&nonce=' + ajaxAction.nonce + '&loop=' + loop + '&pagination=' + pagination + '&totalprop=' + totalprop + '&mode=' + mode,
	})
	.then( (resp) => resp.json() )
	.then( function(results) {
		if ( results.success ){
			// Success - show message and continue or finish
			if( results.data && results.data.message ){
				const progressElement = document.createElement('p');
				progressElement.className = classTask;
				document.querySelector('#logwrapper #loglist').appendChild(progressElement);
				progressElement.innerHTML = results.data.message;
			}

			// Rate limit: wait and retry the same request.
			if ( results.data.rate_limit ) {
				const waitSeconds = results.data.wait_seconds || 60;
				startWaitCountdown( element, waitSeconds, function() {
					syncManualProperties( element, results.data.loop, results.data.pagination || pagination, results.data.totalprop || totalprop, true );
				});
				return;
			}

			if( ! results.data.finish ) {
				syncManualProperties(element, results.data.loop, results.data.pagination, results.data.totalprop );
			} else {
				element.disabled = false;
				element.textContent = ajaxAction.label_sync;
				if ( importMode ) {
					importMode.disabled = false;
				}
				if ( refreshButton ) {
					refreshButton.disabled = false;
				}
				if ( spinner ) {
					spinner.classList.remove('is-active');
				}
			}
		} else {
			// Error - show error message and stop
			element.disabled = false;
			element.textContent = ajaxAction.label_sync;
			if ( importMode ) {
				importMode.disabled = false;
			}
			if ( refreshButton ) {
				refreshButton.disabled = false;
			}
			if ( spinner ) {
				spinner.classList.remove('is-active');
			}
			
			// Show error message
			if ( results.data && results.data.message ) {
				const errorElement = document.createElement('p');
				errorElement.className = 'error';
				errorElement.style.color = 'red';
				document.querySelector('#logwrapper #loglist').appendChild(errorElement);
				errorElement.innerHTML = results.data.message;
			}
		}
		
		// Scroll to bottom
		const loglist = document.querySelector('#logwrapper #loglist');
		if ( loglist ) {
			loglist.scrollTo({ top: loglist.scrollHeight, behavior: "smooth" });
		}
	})
	.catch(err => {
		console.error('Import error:', err);
		element.disabled = false;
		element.textContent = ajaxAction.label_sync;
		if ( importMode ) {
			importMode.disabled = false;
		}
		if ( refreshButton ) {
			refreshButton.disabled = false;
		}
		if ( spinner ) {
			spinner.classList.remove('is-active');
		}
		const errorElement = document.createElement('p');
		errorElement.className = 'error';
		errorElement.style.color = 'red';
		errorElement.innerHTML = 'Error: ' + err.message;
		const loglist = document.querySelector('#logwrapper #loglist');
		if ( loglist ) {
			loglist.appendChild(errorElement);
		}
	});
}
