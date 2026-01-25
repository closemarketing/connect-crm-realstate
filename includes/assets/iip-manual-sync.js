// Tab switching functionality.
document.addEventListener('DOMContentLoaded', function() {
	const tabButtons = document.querySelectorAll('.ccrmre-tab-button');
	
	tabButtons.forEach(button => {
		button.addEventListener('click', function() {
			const targetTab = this.getAttribute('data-tab');
			
			// Remove active class from all buttons and panes.
			document.querySelectorAll('.ccrmre-tab-button').forEach(btn => {
				btn.classList.remove('active');
			});
			document.querySelectorAll('.ccrmre-tab-pane').forEach(pane => {
				pane.classList.remove('active');
			});
			
			// Add active class to clicked button and corresponding pane.
			this.classList.add('active');
			document.getElementById('tab-' + targetTab).classList.add('active');
		});
	});
});

function syncManualProperties( element, loop = 0, pagination, totalprop = 0 ) {
	// Get the spinner element and mode select.
	const spinner = element.parentElement.querySelector('.spinner');
	const importMode = document.getElementById('import-mode');
	const refreshButton = document.getElementById('refresh_stats');
	const mode = importMode ? importMode.value : 'updated';

	// Switch to manual tab when starting import.
	if ( loop === 0 ) {
		const manualTabButton = document.querySelector('.ccrmre-tab-button[data-tab="manual"]');
		if ( manualTabButton ) {
			manualTabButton.click();
		}
		// Clear previous log.
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
