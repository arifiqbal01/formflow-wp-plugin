<?php
/** Deterministic gateway doubles. Never contacts Mollie or performs a transaction. */
namespace Pronamic\WordPress\Money {
	class Currency {
		private $code;
		function __construct( $code ) { $this->code = $code; }
		function get_alphabetic_code() { return $this->code; }
	}
	class Money {
		private $value;
		private $currency;
		function __construct( $value, $currency ) { $this->value = $value; $this->currency = new Currency( $currency ); }
		function get_value() { return $this->value; }
		function get_currency() { return $this->currency; }
	}
}
namespace Pronamic\WordPress\Pay {
	class Customer { function set_email( $value ) {} }
	class Plugin {
		static $fail = false;
		static function get_gateway( $id ) { return $id > 0 ? (object) array() : null; }
		static function start_payment( $payment ) {
			if ( self::$fail ) { throw new \RuntimeException( 'Test provider failure' ); }
			$payment->transaction_id = 'tr_test_' . $payment->get_id();
			$payment->set_status( 'Open' );
			$payment->save();
			return $payment;
		}
		static function update_payment( $payment, $can_redirect = true ) { $payment->save(); }
	}
}
namespace Pronamic\WordPress\Pay\Payments {
	class PaymentStatus {
		const SUCCESS = 'Success', FAILURE = 'Failure', CANCELLED = 'Cancelled', EXPIRED = 'Expired', REFUNDED = 'Refunded';
	}
	class Payment {
		static $payments = array();
		static $next_id = 1000;
		private $id;
		private $source;
		private $source_id;
		private $config_id;
		private $amount;
		private $description;
		private $status;
		public $transaction_id;
		public $key = 'fixture-private-key';
		public $title;
		function set_source( $value ) { $this->source = $value; }
		function get_source() { return $this->source; }
		function set_source_id( $value ) { $this->source_id = $value; }
		function get_source_id() { return $this->source_id; }
		function set_config_id( $value ) { $this->config_id = $value; }
		function set_total_amount( $value ) { $this->amount = $value; }
		function get_total_amount() { return $this->amount; }
		function set_description( $value ) { $this->description = $value; }
		function get_description() { return $this->description; }
		function set_customer( $value ) {}
		function get_id() { return $this->id; }
		function get_transaction_id() { return $this->transaction_id; }
		function get_status() { return $this->status; }
		function set_status( $value ) { $this->status = $value; }
		function save() {
			if ( ! $this->id ) { $this->id = self::$next_id++; }
			self::$payments[ $this->id ] = $this;
			\do_action( 'pronamic_payment_status_update_formflow', $this );
		}
		function get_pay_redirect_url() { return 'http://example.test/?payment_redirect=' . $this->id . '&key=' . $this->key; }
	}
}
namespace {
	function get_pronamic_payment( $id ) { return \Pronamic\WordPress\Pay\Payments\Payment::$payments[ $id ] ?? null; }
}
