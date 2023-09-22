function syncManualProperties( element, loop = 0 ) {
	element.classList.add('disabled');
	element.innerHTML = ajaxAction.label_syncing + ' <span class="spinner is-active"></span>';
	console.log(loop);

	var class_task = class_task || 'odd';
	
	// AJAX request.
	fetch( ajaxAction.url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Cache-Control': 'no-cache',
		},
		body: 'action=manual_import&nonce=' + ajaxAction.nonce + '&loop=' + loop,
	})
	.then( (resp) => resp.json() )
	.then( function(results) {
		console.log(results);
		if ( results.success ){
			if( results.data.loop <= results.data.total ) {
				syncManualProperties(element,results.data.loop);
			} else {
				element.classList.remove('disabled');
				element.innerHTML = ajaxAction.label_sync;
			}
		} else {
			element.classList.remove('disabled');
			element.innerHTML = ajaxAction.label_sync;
		}
		// Message.
		if( results.data.message != undefined ){
			progressElement = document.createElement('p');
			progressElement.className = class_task;
			document.querySelector('#logwrapper #loglist').appendChild(progressElement);
			progressElement.innerHTML = results.data.message;
		}
		class_task = 'odd' === class_task ? 'even' : 'odd';
		//$(".woocommerce_page_connect_woocommerce #loglist").animate({ scrollTop: $(".woocommerce_page_connect_woocommerce #loglist")[0].scrollHeight}, 450);
	})
	.catch(err => console.log(err));
}