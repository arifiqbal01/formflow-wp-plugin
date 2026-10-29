/* CF7 fires wpcf7submit for custom statuses too. Never infer payment success here. */
document.addEventListener('wpcf7submit', function (event) {
    var result = event.detail && event.detail.apiResponse;
    if (!result || result.status !== 'formflow_payment_redirect' || !result.formflow_redirect_url) {
        return;
    }
    var url;
    try {
        url = new URL(result.formflow_redirect_url, window.location.href);
    } catch (error) {
        return;
    }
    // Pronamic's payment redirect endpoint is on this WordPress installation.
    if (url.origin !== window.location.origin) {
        return;
    }
    // Keep an accessible fallback link if navigation is prevented by the browser.
    var output = event.target.querySelector('.wpcf7-response-output');
    if (output) {
        var link = document.createElement('a');
        link.href = url.href;
        link.textContent = result.message;
        output.replaceChildren(link);
    }
    window.location.assign(url.href);
});
