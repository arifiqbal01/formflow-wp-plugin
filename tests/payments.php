<?php
/** Integration tests: actual WordPress hooks/dbDelta/wpdb + MariaDB; deterministic Pronamic double. */
error_reporting( E_ALL );
ob_start();
set_exception_handler( function ( $error ) { fwrite( STDERR, $error . PHP_EOL ); exit( 1 ); } );
define( 'WP_INSTALLING', true );
$root = getenv( 'WP_TEST_ROOT' );
if ( ! $root || ! is_file( $root . '/wp-load.php' ) ) { throw new RuntimeException( 'WP_TEST_ROOT must point to WordPress configured for a disposable database.' ); }
require $root . '/wp-load.php';
if ( ! preg_match( '/^formflow_test(?:_|$)/', DB_NAME ) ) { throw new RuntimeException( 'Use a disposable database named formflow_test or formflow_test_*.' ); }
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
add_filter( 'pre_wp_mail', '__return_true' );
if ( ! get_option( 'formflow_test_installed' ) ) {
	wp_install( 'FormFlow tests', 'testadmin', 'tests@example.test', true, '', 'local-test-password' );
	update_option( 'formflow_test_installed', 1 );
}
define( 'FORMFLOW_PATH', dirname( __DIR__ ) . '/' );
define( 'FORMFLOW_URL', 'http://example.test/wp-content/plugins/formflow/' );
define( 'FORMFLOW_VERSION', '1.2.1' );
require __DIR__ . '/pronamic-double.php';
foreach ( array( 'database/class-event-database', 'database/class-event-batch-database', 'database/class-registration-database', 'database/class-database', 'database/class-event-batches-table', 'database/class-events-table', 'database/class-registrations-table', 'dto/class-submission-data', 'services/class-capacity-service', 'services/class-email-service', 'services/class-registration-service', 'services/class-event-batch-service', 'services/class-payment-service', 'migrations/migration-1-2-0', 'migrations/migration-1-2-1', 'integrations/class-contact-form-7' ) as $file ) {
	require_once FORMFLOW_PATH . 'includes/' . $file . '.php';
}
class EmailSpy extends FormFlow_Email_Service {
	public $sent = array();
	public $fail = false;
	public function send_registration_email( $id ) { $this->sent[] = $id; return ! $this->fail; }
	public function send_waiting_email( $id ) { return true; }
	public function send_promotion_email( $id ) { return true; }
}
$checks = 0;
function check( $condition, $description ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( 'FAIL: ' . $description ); }
	++$checks;
	echo "PASS: $description\n";
}
function row( $id ) { global $db; return $db->registrations->get_registration( $id ); }
function pay( $result, $status = 'Success', $amount = null, $currency = 'EUR' ) {
	$p = get_pronamic_payment( row( $result['registration_id'] )->payment_id );
	if ( null !== $amount ) { $p->set_total_amount( new \Pronamic\WordPress\Money\Money( $amount, $currency ) ); }
	$p->set_status( $status ); $p->save();
	return $p;
}

// Independent PHP workers exercise actual MariaDB lock contention.
if ( getenv( 'FORMFLOW_TEST_WORKER' ) ) {
	class SlowCapacity extends FormFlow_Capacity_Service {
		public function is_full( $id ) { $full = parent::is_full( $id ); usleep( 200000 ); return $full; }
	}
	$db = new FormFlow_Database();
	$capacity = new SlowCapacity( $db );
	$email = new EmailSpy( $db );
	$payments = new FormFlow_Payment_Service( $db, $capacity, $email );
	\Pronamic\WordPress\Pay\Payments\Payment::$next_id = random_int( 1000000, 1000000000 );
	echo wp_json_encode( $payments->begin( (int) getenv( 'FORMFLOW_TEST_WORKER' ), array( 'your-email' => 'parent@example.test' ) ) );
	exit;
}
// Recreate only plugin tables in the explicitly disposable database.
foreach ( array( 'registrations', 'event_batches', 'events' ) as $table ) { $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}formflow_{$table}" ); }
FormFlow_Events_Table::create(); FormFlow_Event_Batches_Table::create(); FormFlow_Registrations_Table::create();
$db = new FormFlow_Database();
// Activation/update must be repeatable on an existing batch table (the reported failure).
$batch_fixture = $db->batches->insert_batch( array( 'event_id' => 99999, 'batch_number' => 1, 'status' => 'open', 'capacity' => 10 ) );
$batch_alters = array();
$capture_batch_query = function ( $query ) use ( &$batch_alters, $wpdb ) {
	if ( preg_match( '/^ALTER TABLE\s+' . preg_quote( $wpdb->prefix . 'formflow_event_batches', '/' ) . '\s/i', $query ) ) {
		$batch_alters[] = $query;
	}
	return $query;
};
add_filter( 'query', $capture_batch_query );
FormFlow_Event_Batches_Table::create();
FormFlow_Event_Batches_Table::create();
check( empty( $batch_alters ), 'repeated activation does not issue malformed ENUM column alterations' );
check( 'open' === $db->batches->get_batch( $batch_fixture )->status, 'repeated activation preserves an existing batch and its status' );
delete_option( 'formflow_batch_schema' );
FormFlow_Migration_1_2_1::run();
FormFlow_Migration_1_2_1::run();
check( '1.2.1' === get_option( 'formflow_batch_schema' ) && empty( $batch_alters ), 'batch-schema upgrade and rerun complete without changing existing data' );
remove_filter( 'query', $capture_batch_query );
$db->batches->delete_batch( $batch_fixture );
// Exercise upgrade from the real v1.1.0 columns and enum, preserving a historical attendee.
$historical = $db->registrations->insert_registration( array( 'event_id' => 1, 'status' => 'registered', 'submission_data' => '{}' ) );
foreach ( array( 'payment_id', 'payment_status', 'payment_amount', 'payment_currency', 'reservation_expires_at', 'confirmation_sent_at' ) as $column ) { $wpdb->query( "ALTER TABLE {$wpdb->prefix}formflow_registrations DROP COLUMN {$column}" ); }
$wpdb->query( "ALTER TABLE {$wpdb->prefix}formflow_registrations MODIFY status ENUM('registered','waiting','cancelled') NOT NULL DEFAULT 'registered'" );
foreach ( array( 'payment_required', 'payment_amount', 'pronamic_config_id' ) as $column ) { $wpdb->query( "ALTER TABLE {$wpdb->prefix}formflow_events DROP COLUMN {$column}" ); }
delete_option( 'formflow_payment_schema' );
FormFlow_Migration_1_2_0::run();
check( get_option( 'formflow_payment_schema' ) === '1.2.0', 'v1.1 schema upgrades successfully through real dbDelta' );
check( row( $historical )->status === 'registered' && row( $historical )->payment_status === 'not_required', 'historical registrations preserved without inventing payments' );
FormFlow_Migration_1_2_0::run();
check( row( $historical )->status === 'registered', 'migration rerun is harmless' );
$db->registrations->delete_registration( $historical );
$config = wp_insert_post( array( 'post_type' => 'pronamic_gateway', 'post_status' => 'publish', 'post_title' => 'Test Mollie' ) );
update_post_meta( $config, '_pronamic_gateway_id', 'mollie' ); update_option( 'pronamic_pay_config_id', $config );
$event_id = $db->events->insert_event( array( 'title' => 'Sakynah', 'cf7_form_id' => 42, 'payment_required' => 1, 'payment_amount' => '80.00' ) );
$batch_id = $db->batches->insert_batch( array( 'event_id' => $event_id, 'batch_number' => 1, 'capacity' => 1, 'status' => 'open' ) );
$capacity = new FormFlow_Capacity_Service( $db ); $email = new EmailSpy( $db );
wp_clear_scheduled_hook( 'formflow_payment_maintenance' );
// Constructor must not execute other plugins' cron schedule filters during plugins_loaded.
$cron_filter_calls = 0;
$cron_probe = function ( $schedules ) use ( &$cron_filter_calls ) { ++$cron_filter_calls; return $schedules; };
add_filter( 'cron_schedules', $cron_probe );
$payments = new FormFlow_Payment_Service( $db, $capacity, $email );
check( 0 === $cron_filter_calls, 'payment service construction does not trigger early cron/translation filters' );
$payments->schedule_maintenance();
check( (bool) wp_next_scheduled( 'formflow_payment_maintenance' ), 'maintenance is scheduled when initialization reaches init' );
remove_filter( 'cron_schedules', $cron_probe );
$registrations = new FormFlow_Registration_Service( $db, $capacity, $email );
$data = array( 'your-email' => 'parent@example.test', 'your-name' => 'Test Parent', 'child-name' => 'Test Child', 'price' => '0.01' );
$r = $payments->begin( $event_id, $data );
check( ! empty( $r['success'] ) && row( $r['registration_id'] )->status === 'pending_payment', 'submission creates pending checkout, not confirmed registration' );
check( row( $r['registration_id'] )->payment_amount === '80.00', 'browser price manipulation cannot change server price' );
check( count( $email->sent ) === 0 && $capacity->remaining_seats( $batch_id ) === 0, 'pending checkout reserves last seat without sending confirmation' );
check( empty( $payments->begin( $event_id, $data )['success'] ), 'second parent cannot take a reserved final seat' );
check( empty( $db->batches->update_batch( $batch_id, array( 'capacity' => 0 ) ) ), 'capacity cannot be reduced below a live reservation' );
check( empty( $registrations->register( $event_id, $data )['success'] ), 'legacy registration entry point cannot bypass mandatory payment' );
$p = pay( $r );
check( row( $r['registration_id'] )->status === 'registered' && count( $email->sent ) === 1, 'verified success confirms registration and sends one email' );
$p->save(); $payments->sync_payment( $p );
check( count( $email->sent ) === 1, 'repeated provider callbacks do not duplicate emails' );
pay( $r, 'Failure' );
check( row( $r['registration_id'] )->status === 'registered', 'stale failure cannot undo successful registration' );
pay( $r, 'Refunded' );
check( row( $r['registration_id'] )->status === 'cancelled' && $capacity->remaining_seats( $batch_id ) === 1, 'refund removes confirmed admission and releases capacity' );
pay( $r, 'Success' );
check( row( $r['registration_id'] )->payment_status === 'refunded', 'stale success cannot restore a refunded admission' );
foreach ( array( 'Failure', 'Cancelled', 'Expired' ) as $status ) {
	$r = $payments->begin( $event_id, $data ); pay( $r, $status );
	check( row( $r['registration_id'] )->status === 'cancelled' && $capacity->remaining_seats( $batch_id ) === 1, "$status never confirms and releases reservation" );
}
$r = $payments->begin( $event_id, $data ); pay( $r, 'On Hold' );
check( row( $r['registration_id'] )->status === 'pending_payment', 'on-hold payment is not treated as paid' );
pay( $r, 'Cancelled' );
$r = $payments->begin( $event_id, $data ); pay( $r, 'Success', '0.01' );
check( row( $r['registration_id'] )->status === 'payment_review', 'incorrect amount cannot confirm admission' );
$r = $payments->begin( $event_id, $data ); pay( $r, 'Success', '80.00', 'USD' );
check( row( $r['registration_id'] )->status === 'payment_review', 'incorrect currency cannot confirm admission' );
$r = $payments->begin( $event_id, $data );
$db->registrations->update_registration( $r['registration_id'], array( 'reservation_expires_at' => '2000-01-01 00:00:00' ) );
$new = $payments->begin( $event_id, $data );
check( ! empty( $new['success'] ), 'expired checkout releases its seat for another parent' );
pay( $r );
check( row( $r['registration_id'] )->status === 'payment_review' && row( $new['registration_id'] )->status === 'pending_payment', 'late success cannot steal another parent reservation' );
pay( $new, 'Cancelled' );
$r = $payments->begin( $event_id, $data );
$db->registrations->update_registration( $r['registration_id'], array( 'reservation_expires_at' => '2000-01-01 00:00:00' ) );
pay( $r );
check( row( $r['registration_id'] )->status === 'registered', 'late success may confirm when original batch still has a seat' );
$registrations->cancel( $r['registration_id'] ); pay( $r );
check( row( $r['registration_id'] )->status === 'cancelled', 'duplicate success cannot undo administrative cancellation' );
$r = $payments->begin( $event_id, $data ); $db->batches->update_batch( $batch_id, array( 'status' => 'closed' ) ); pay( $r );
check( row( $r['registration_id'] )->status === 'payment_review', 'payment for closed batch requires review, not admission' );
check( empty( $payments->begin( $event_id, $data )['success'] ), 'no open batch prevents checkout and unpaid waiting-list registration' );
$db->batches->update_batch( $batch_id, array( 'status' => 'open' ) );
$waiting = $db->registrations->insert_registration( array( 'event_id' => $event_id, 'status' => 'waiting', 'submission_data' => '{}' ) );
check( 0 === $registrations->promote_waiting_list( $event_id, $batch_id ), 'automatic waiting-list promotion disabled for paid events' );
check( empty( $registrations->assign_to_batch( $waiting, $batch_id )['success'] ), 'manual assignment cannot promote an unpaid waiting applicant' );
\Pronamic\WordPress\Pay\Plugin::$fail = true;
check( empty( $payments->begin( $event_id, $data )['success'] ) && $capacity->remaining_seats( $batch_id ) === 1, 'provider startup failure gives an error without admission or stuck reservation' );
\Pronamic\WordPress\Pay\Plugin::$fail = false;
$r = $payments->begin( $event_id, $data ); $email->fail = true; pay( $r );
check( null === row( $r['registration_id'] )->confirmation_sent_at, 'failed confirmation email remains retryable' );
$email->fail = false; $payments->maintenance();
check( null !== row( $r['registration_id'] )->confirmation_sent_at, 'maintenance retries failed confirmation delivery' );
$registrations->cancel( $r['registration_id'] );
update_post_meta( $config, '_pronamic_gateway_id', 'paypal' );
check( empty( $payments->begin( $event_id, $data )['success'] ), 'non-Mollie configuration rejected before reserving a seat' );
update_post_meta( $config, '_pronamic_gateway_id', 'mollie' );
// CF7 adapter contract: use actual WP_Hook so suspend/restore runs against WordPress iteration.
class WPCF7_ContactForm {
	function id() { return 42; }
}
class SubmissionFixture {
	function get_posted_data() { global $data; return $data; }
	function get_contact_form() { return new WPCF7_ContactForm(); }
}
eval( 'namespace Pronamic\\WordPress\\Pay\\Extensions\\ContactForm7; class Extension { public $calls = 0; function before_send_mail() { $this->calls++; } }' );
$native = new \Pronamic\WordPress\Pay\Extensions\ContactForm7\Extension();
add_action( 'wpcf7_before_send_mail', array( $native, 'before_send_mail' ), 10, 3 );
$adapter = new FormFlow_Contact_Form_7( $db, $registrations, $payments );
$abort = false; $form = new WPCF7_ContactForm(); $submission = new SubmissionFixture();
do_action_ref_array( 'wpcf7_before_send_mail', array( $form, &$abort, $submission ) );
$result = apply_filters( 'wpcf7_submission_result', array( 'status' => 'aborted' ), $submission );
check( $abort && $native->calls === 0, 'paid submission aborts CF7 mail and prevents duplicate native payment creation' );
check( $result['status'] === 'formflow_payment_redirect' && ! empty( $result['formflow_redirect_url'] ), 'CF7 response redirects to payment without claiming registration success' );
do_action( 'wpcf7_submit', $form, $result );
check( false !== has_action( 'wpcf7_before_send_mail', array( $native, 'before_send_mail' ) ), 'native Pronamic handler restored after managed submission' );
$validation = $adapter->submission_result( array( 'status' => 'validation_failed', 'message' => 'Required field missing' ), $submission );
check( $validation['status'] === 'validation_failed' && empty( $validation['formflow_redirect_url'] ), 'a later validation failure does not reuse a previous checkout result' );
$abort = false; $adapter->handle_submission( $form, $abort, $submission );
$result = $adapter->submission_result( array( 'status' => 'mail_sent' ), $submission );
check( $result['status'] === 'aborted' && empty( $result['formflow_redirect_url'] ), 'full capacity returns error instead of misleading CF7 success' );
$adapter->after_submit( $form, $result );
check( '' === $wpdb->last_error, 'database operations completed without SQL errors' );

// Existing free events retain capacity and FIFO waiting-list behaviour.
$free_event = $db->events->insert_event( array( 'title' => 'Free event', 'cf7_form_id' => 100, 'payment_required' => 0 ) );
$free_batch = $db->batches->insert_batch( array( 'event_id' => $free_event, 'batch_number' => 1, 'capacity' => 1, 'status' => 'open' ) );
$free_one = $registrations->register( $free_event, $data );
$free_two = $registrations->register( $free_event, $data );
check( $free_one['status'] === 'registered' && $free_two['status'] === 'waiting' && null === row( $free_two['registration_id'] )->batch_id, 'free registration and waiting-list behaviour preserved' );
$registrations->cancel( $free_one['registration_id'] );
$promoted = $registrations->promote_waiting_list( $free_event, $free_batch );
check( 1 === $promoted && 'registered' === row( $free_two['registration_id'] )->status, 'free waiting-list applicant can still be promoted' );
// A forged browser success value cannot confirm an unpaid submission.
$last = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}formflow_registrations WHERE status = 'pending_payment' ORDER BY id DESC LIMIT 1" );
$pending_payment = get_pronamic_payment( $last->payment_id );
add_filter( 'wp_die_handler', function () { return function ( $message, $title, $args ) { throw new RuntimeException( (string) $message, (int) $args['response'] ); }; } );
$_GET = array( 'formflow_payment' => $pending_payment->get_id(), 'key' => 'wrong', 'status' => 'success' );
try { $payments->render_return(); } catch ( RuntimeException $e ) { check( 403 === $e->getCode(), 'invalid payment key cannot read payment status' ); }
$_GET['key'] = $pending_payment->key;
try { $payments->render_return(); } catch ( RuntimeException $e ) { check( false !== strpos( $e->getMessage(), 'not confirmed' ), 'forged success query cannot confirm an unpaid registration' ); }
$_GET = array();
// Run two workers with an intentional delay after reading capacity. Without a shared lock
// both would see the final seat as available before either inserts a reservation.
$concurrent_event = $db->events->insert_event( array( 'title' => 'Concurrent', 'cf7_form_id' => 99, 'payment_required' => 1, 'payment_amount' => '80.00' ) );
$concurrent_batch = $db->batches->insert_batch( array( 'event_id' => $concurrent_event, 'batch_number' => 1, 'capacity' => 1, 'status' => 'open' ) );
putenv( 'FORMFLOW_TEST_WORKER=' . $concurrent_event );
$workers = array();
for ( $i = 0; $i < 2; $i++ ) {
	$pipes = array();
	$process = proc_open( array( 'php', __FILE__ ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	fclose( $pipes[0] );
	$workers[] = array( $process, $pipes );
}
putenv( 'FORMFLOW_TEST_WORKER' );
$successes = 0;
foreach ( $workers as $worker ) {
	$output = stream_get_contents( $worker[1][1] ); $errors = stream_get_contents( $worker[1][2] );
	fclose( $worker[1][1] ); fclose( $worker[1][2] );
	$code = proc_close( $worker[0] );
	check( 0 === $code && '' === $errors, 'concurrent worker completed without runtime errors' );
	$decoded = json_decode( $output, true );
	check( is_array( $decoded ), 'concurrent worker returned checkout result' );
	$successes += empty( $decoded['success'] ) ? 0 : 1;
}
check( 1 === $successes && 1 === $capacity->occupied_count( $concurrent_batch ), 'simultaneous parents cannot overbook the final seat across PHP workers' );
echo "Completed $checks payment integration checks.\n";

ob_end_flush();
