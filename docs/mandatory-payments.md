# Mandatory registration payments (FormFlow 1.2)

FormFlow creates a pending checkout first. A parent is registered only after Pronamic reports a successful payment for the exact stored amount/currency. Browser redirects never prove payment.

## Set up Sakynah on staging

1. Install this branch as FormFlow, with Contact Form 7 and Pronamic Pay active. Keep the existing Mollie configuration in Pronamic. FormFlow uses Pronamic's payment API directly; it does not require the Premium CF7 extension for its own payment handling.
2. In **FormFlow → Events → Edit**, enable **Require successful payment before registration**. Set **Programme price (EUR)** to **80.00**. Set the configuration ID to the published Pronamic **Mollie** configuration, or use **0** to use Pronamic's default. Start with a Mollie **test** configuration.
3. Open a batch with the correct capacity (10 for the group). Existing events retain their previous payment setting during upgrade; enabling payment for this event is an explicit setup step. Newly created events have the payment checkbox selected in the admin form.
4. Keep the current CF7 parent/child/contact/consent fields. A valid parent email field with a name such as `your-email` is required. Add this immediately above the submit button:

   ```text
   [formflow_payment_summary]
   [submit "Inschrijven en betalen"]
   ```

   The summary displays the amount from the event settings. Posted price fields are never used to calculate the charge. Remove old `pronamic_pay_amount`, `pronamic_pay_method`, and issuer fields from this managed form: the customer chooses among the methods enabled in **Mollie's hosted checkout**. The native Pronamic CF7 payment callback is suspended for this paid submission and then restored for other forms, preventing two payments.
5. Update the page wording: payment is required, and a place is only confirmed after successful payment. Remove promises that this paid form automatically adds applicants to a waiting list. A full or closed batch blocks checkout. Waiting-list enquiries can use a separate ordinary contact form, without promising admission.
6. Ensure server cron runs WP-Cron regularly and Mollie/Pronamic webhooks can reach the website. FormFlow checks pending payments and retries failed confirmation delivery every five minutes. Exclude payment return URLs from full-page caching.
7. Complete the test checklist below before switching the selected Pronamic configuration to live mode.

## Behaviour

- Pending checkout reserves a place for **30 minutes**, using UTC timestamps. Active holds count against capacity, including admin capacity reductions. MySQL/MariaDB named locks serialize admissions across PHP workers; an unavailable lock fails closed.
- Payment records store the linked Pronamic payment ID, amount, currency, payment status, and reservation expiry. Registration details link to the Pronamic payment.
- Successful, correctly matched payment confirms admission and triggers the existing FormFlow customer confirmation email. CF7 Mail and Mail (2) are suppressed on paid submissions. CF7 mail templates are not replayed after payment. Admins inspect normal paid registrations in FormFlow; exceptional payments generate an admin review email.
- Failed, cancelled, or expired payments do not confirm admission. FormFlow retains their entries for administration; a stored pending/cancelled entry is not a registration confirmation.
- Refund status cancels admission. Cancelling admission in FormFlow does **not** issue a refund; process refunds separately in Pronamic/Mollie.
- An expired reservation can be confirmed by a late success only if its original batch is still open, registration is open, and a seat remains. Otherwise it becomes **Payment Review** and the administrator is notified. Amount/currency mismatches also require review. No automatic refund is issued.
- Duplicate success notifications cannot duplicate the confirmation email. Failed email delivery is retried by cron. A process crash after claiming email delivery can still need manual email follow-up; exactly-once email delivery cannot be guaranteed across SMTP failures.
- Paid events never auto-promote waiting-list applicants. An unpaid waiting applicant cannot be manually assigned. Ask historical unpaid applicants to complete the paid form once a place is offered. Existing confirmed attendees are preserved and are not retroactively charged or marked as paid.
- Free events retain the original registration and waiting-list workflow. Other CF7 forms retain the native Pronamic integration.
- Checkout redirects require CF7's normal frontend scripts. A non-AJAX redirect is included when CF7 JavaScript is explicitly disabled. Keep the WordPress Home URL and the form page on the same origin, with the same HTTPS scheme.

## Staging test checklist

- €80 displayed; the chosen Mollie **test** configuration receives an €80 EUR charge.
- Successful test payment → one registered attendee and one customer confirmation.
- Failed/cancelled payment → no admission and no confirmation.
- Close the browser before returning: webhook still confirms a successful payment.
- Refresh the return page and replay a provider notification: no duplicate admission or email.
- Two browsers compete for the final seat: only one active reservation is accepted.
- Expired hold → another parent can pay; a late payment cannot overbook the batch.
- Full/closed batch → useful error; no charge and no misleading CF7 success.
- Existing free forms and their waiting lists still behave normally.
- Test the actual site's cache, mail delivery, cron and webhook configuration.

## Automated checks

`tests/payments.php` uses a real WordPress installation and MariaDB, with deterministic Pronamic gateway doubles. It tests schema upgrades, payment transitions, CF7 hook interaction, email deduplication/retry, browser-status forgery, and two independent PHP workers competing for a final seat. It never contacts Mollie. `tests/checkout.test.js` tests safe frontend navigation.

Use a **disposable database**: the suite drops and recreates the three FormFlow tables. Copy `tests/wp-config.sample.php` to a local WordPress checkout as `wp-config.php` and configure its `FORMFLOW_TEST_DB_*` environment values for that database, install PHP with mysqli, start MariaDB, then run:

```bash
WP_TEST_ROOT=/path/to/wordpress bash tests/run.sh
```

The database must already exist and be named `formflow_test` or `formflow_test_*`; the suite rejects other database names. The suite installs WordPress tables if needed. The configured WordPress database and `FORMFLOW_TEST_DB_*` environment values must match. No credentials or live provider settings belong in the repository.

Automated gateway doubles do not replace a real Mollie test-mode checkout against the installed versions. No staging or live transaction was performed during implementation.
