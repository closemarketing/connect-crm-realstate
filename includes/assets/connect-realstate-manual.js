function syncManualProperties( element, loop = 0 ) {
	element.classList.add('disabled');
	element.innerHTML = ajaxAction.label_sync + ' <span class="spinner is-active"></span>';
	console.log(loop);

	var class_task = 'odd';
	
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
			if(results.data.loop){
				syncManualProperties(element,results.data.loop);
			} else {
				//$(document).find('#start-sync').removeAttr('disabled');
				//$(document).find('.sync-wrapper .spinner').remove();
			}
		} else {
			//$(document).find('#start-sync').removeAttr('disabled');
			//$(document).find('.sync-wrapper .spinner').remove();
		}
		// Message.
		if( results.data.message != undefined ){
			progressElement = document.createElement('p');
			progressElement.className = class_task;
			document.querySelector('#logwrapper #loglist').appendChild(progressElement);
			progressElement.innerHTML = results.data.message;
		}
		if ( class_task == 'odd' ) {
			class_task = 'even';
		} else {
			class_task = 'odd';
		}
		
	})
	.catch(err => console.log(err));
}