function syncManualProperties( element, loop = 0, pagination, totalprop = 0 ) {
	// Get the spinner element.
	const spinner = element.parentElement.querySelector('.spinner');

	// Add disabled state and show spinner.
	element.disabled = true;
	element.textContent = ajaxAction.label_syncing;
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
		body: 'action=manual_import&nonce=' + ajaxAction.nonce + '&loop=' + loop + '&pagination=' + pagination + '&totalprop=' + totalprop,
	})
	.then( (resp) => resp.json() )
	.then( function(results) {
		if ( results.success ){
			if( ! results.data.finish ) {
				syncManualProperties(element, results.data.loop, results.data.pagination, results.data.totalprop );
			} else {
				element.disabled = false;
				element.textContent = ajaxAction.label_sync;
				if ( spinner ) {
					spinner.classList.remove('is-active');
				}
				results.data.message = ajaxAction.label_sync_complete + ' ' + results.data.loop;
			}
		} else {
			element.disabled = false;
			element.textContent = ajaxAction.label_sync;
			if ( spinner ) {
				spinner.classList.remove('is-active');
			}
			// Show error message if available.
			if ( results.data && results.data.message ) {
				const errorElement = document.createElement('p');
				errorElement.className = 'error';
				errorElement.style.color = 'red';
				document.querySelector('#logwrapper #loglist').appendChild(errorElement);
				errorElement.innerHTML = results.data.message;
			}
		}
		// Message.
		if( results.data && results.data.message ){
			const progressElement = document.createElement('p');
			progressElement.className = classTask;
			document.querySelector('#logwrapper #loglist').appendChild(progressElement);
			progressElement.innerHTML = results.data.message;
		}
		const loglist = document.querySelector('#logwrapper #loglist');
		if ( loglist ) {
			loglist.scrollTo({ top: loglist.scrollHeight, behavior: "smooth" });
		}
	})
	.catch(err => {
		console.error('Import error:', err);
		element.disabled = false;
		element.textContent = ajaxAction.label_sync;
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