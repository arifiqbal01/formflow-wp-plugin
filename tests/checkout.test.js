const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
let handler;
const navigations = [];
let fallback;
const context = {
    URL,
    document: {
        addEventListener: (name, callback) => { assert.equal(name, 'wpcf7submit'); handler = callback; },
        createElement: () => ({})
    },
    window: { location: { href: 'https://example.test/registration/', origin: 'https://example.test', assign: url => navigations.push(url) } }
};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/js/checkout.js'), 'utf8'), context);
function submit(result) {
    handler({ detail: { apiResponse: result }, target: { querySelector: () => ({ replaceChildren: link => { fallback = link; } }) } });
}
submit({status: 'mail_sent'});
submit({status: 'aborted', formflow_redirect_url: 'https://example.test/?payment_redirect=1'});
submit({status: 'formflow_payment_redirect', formflow_redirect_url: 'https://attacker.test/'});
submit({status: 'formflow_payment_redirect', formflow_redirect_url: 'javascript:alert(1)'});
assert.equal(navigations.length, 0);
submit({status: 'formflow_payment_redirect', message: 'Continue to payment', formflow_redirect_url: 'https://example.test/?payment_redirect=1&key=fixture'});
assert.equal(navigations.length, 1);
assert.equal(fallback.textContent, 'Continue to payment');
assert.equal(fallback.href, navigations[0]);
console.log('PASS: checkout navigates only for the pending-payment response to the same origin, with a fallback link');
