/**
 * File: /assets/js/tfcm-client-script.js
 *
 * Fetches the client IP address and logs the HTTP request via AJAX.
 * 
 * NOTE: This script exists **specifically** to log requests that cannot be captured otherwise 
 * due to page caching. Therefore, it **should NOT** include cache-busting mechanisms like 
 * adding a timestamp or random query strings to request URLs.
 *
 * @package TrafficMonitor
 */
document.addEventListener("DOMContentLoaded", function () {
	const TFCM_DEBUG = false; // Set to true for debugging
	if (TFCM_DEBUG) console.log("tfcm-client-script.js loaded.");

	// Fetch IP from WordPress internal AJAX endpoint. This is necessary because:
	// 1. $_SERVER['REMOTE_ADDR'] can be spoofed and is unreliable for logging real user IPs.
	// 2. AJAX requests do not include X-Forwarded-For headers, making server-side IP detection incomplete.
	// 3. This ensures consistency with HTTP request logging, which uses a best-IP selection logic.
	// 4. External services like ipify may return ipv6 while the http request logs ipv4
	fetch(tfcmClientAjax.ajax_url, {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: new URLSearchParams({
			action: "tfcm_get_ip"
		})
	})
		.then(response => response.json())
		.then(data => {
			if (TFCM_DEBUG) console.log("IP fetch response:", data);
			if (data.success && data.data.ip) {
				sendTfcmAjaxRequest(data.data.ip);
			} else {
				sendTfcmAjaxRequest(null);
			}
		})
		.catch(() => {
			sendTfcmAjaxRequest(null);
		});

	/**
	 * Sends an AJAX request to log the HTTP request.
	 *
	 * @param {string|null} ip - The client IP address (or null if not available).
	 */
	function sendTfcmAjaxRequest(ip) {
		const xhr = new XMLHttpRequest();
		const fullUrl = window.location.href;
		const urlObj = fullUrl ? new URL(fullUrl) : null;
		const referrer = document.referrer;
		const referrerObj = referrer ? new URL(referrer) : null;

		const params = new URLSearchParams({
			action: "tfcm_log_ajax_request",
			nonce: tfcmClientAjax.logging_nonce,
			ip_address: ip || "server-detected",
			request_url: urlObj ? urlObj.origin + urlObj.pathname : '', // Strip query string
			request_query: urlObj ? urlObj.search.substring(1) : '',
			referrer_url: referrerObj ? referrerObj.origin + referrerObj.pathname : '', // Strip query string
			referrer_query: referrerObj ? referrerObj.search.substring(1) : ''
		});

		if (TFCM_DEBUG) console.log("tfcm-client-script.js ip_address: ", params.get("ip_address"));
		if (TFCM_DEBUG) console.log("tfcm-client-script.js request_url: ", params.get("request_url"));
		if (TFCM_DEBUG) console.log("tfcm-client-script.js request_query: ", params.get("request_query"));
		if (TFCM_DEBUG) console.log("tfcm-client-script.js referrer_url: ", params.get("referrer_url"));
		if (TFCM_DEBUG) console.log("tfcm-client-script.js referrer_query: ", params.get("referrer_query"));


		xhr.open("POST", tfcmClientAjax.ajax_url, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		xhr.onreadystatechange = function () {
			if (xhr.readyState === XMLHttpRequest.DONE) {
				if (xhr.status >= 200 && xhr.status < 300) {
					if (TFCM_DEBUG) console.log("Success:", JSON.parse(xhr.responseText).data.message);
				} else {
					if (TFCM_DEBUG) console.error("AJAX error:", xhr.responseText);
				}
			}
		};

		xhr.send(params.toString());
	}
});