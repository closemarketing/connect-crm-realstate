/**
 * Detect the administrator's public IP address when a local proxy hides it
 * from WordPress, then save it as the Inmovilla APIWEB IA parameter.
 *
 * @package Connect_CRM_RealState
 */

(function() {
	'use strict';

	if (typeof window.ccrmreClientIp === 'undefined' || typeof window.fetch !== 'function') {
		return;
	}

	fetch('https://api.ipify.org?format=json', { credentials: 'omit' })
		.then(function(response) {
			if (!response.ok) {
				throw new Error('Unable to detect client IP.');
			}

			return response.json();
		})
		.then(function(data) {
			if (!data.ip) {
				return;
			}

			return fetch(window.ccrmreClientIp.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: new URLSearchParams({
					action: 'ccrmre_store_inmovilla_client_ip',
					nonce: window.ccrmreClientIp.nonce,
					ip: data.ip
				})
			});
		})
		.then(function(response) {
			if (!response) {
				return;
			}

			return response.json();
		})
		.then(function(result) {
			if (result && result.success && result.data.updated && window.ccrmreClientIp.reloadPage) {
				window.location.reload();
			}
		})
		.catch(function() {
			// Leave IA empty when the external lookup is unavailable.
		});
})();
