<?php
/**
 * Registration Service.
 *
 * Handles registration business logic.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Registration_Service {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Capacity service.
	 *
	 * @var FormFlow_Capacity_Service
	 */
	private $capacity;

	/**
	 * Email service.
	 *
	 * @var FormFlow_Email_Service
	 */
	private $email;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database         $database Database facade.
	 * @param FormFlow_Capacity_Service $capacity Capacity service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Capacity_Service $capacity,
		FormFlow_Email_Service $email
	) {
		$this->database = $database;
		$this->capacity = $capacity;
		$this->email    = $email;
	}

	/**
     * Register a submission.
     *
     * @param int   $event_id        Event ID.
     * @param array $submission_data Submitted form data.
     * @return array
     */
    public function register( $event_id, array $submission_data ) {

        $event = $this->database->events->get_event( $event_id );

		if ( ! $event ) {
			return array(
				'success' => false,
				'message' => __( 'Event not found.', 'formflow' ),
			);
		}

		if ( ! $event->registration_open ) {
			return array(
				'success' => false,
				'message' => __( 'Registration is currently closed.', 'formflow' ),
			);
		}

        $batch = $this->database->batches->get_open_batch( $event_id );

        $status   = 'registered';
        $batch_id = null;

        // No open batch.
        if ( ! $batch ) {

            if ( ! $event->waiting_list_enabled ) {
                return array(
                    'success' => false,
                    'message' => __( 'Registration is currently closed.', 'formflow' ),
                );
            }

            $status = 'waiting';

        } else {

            $batch_id = $batch->id;

            if ( $this->capacity->is_full( $batch->id ) ) {

                if ( ! $event->waiting_list_enabled ) {
                    return array(
                        'success' => false,
                        'message' => __( 'Registration is full.', 'formflow' ),
                    );
                }

                $status = 'waiting';
            }
        }

        $registration_id = $this->database->registrations->insert_registration(
            array(
                'event_id'        => $event_id,
                'batch_id'        => $batch_id,
                'status'          => $status,
                'submission_data' => wp_json_encode( $submission_data ),
            )
        );

        if ( ! $registration_id ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to save registration.', 'formflow' ),
			);
		}

		if ( 'registered' === $status ) {

			$this->email->send_registration_email(
				$registration_id
			);
		}

		// Send waiting list email.
		if ( 'waiting' === $status ) {
			$this->email->send_waiting_email( $registration_id );
		}

		return array(
			'success'         => true,
			'registration_id' => $registration_id,
			'batch_id'        => $batch_id,
			'status'          => $status,
		);
    }

	/**
	 * Promote waiting registrations into a batch.
	 *
	 * Moves waiting registrations to the specified batch until
	 * the batch reaches capacity.
	 *
	 * @param int $event_id Event ID.
	 * @param int $batch_id Batch ID.
	 * @return int Number of promoted registrations.
	 */
	public function promote_waiting_list(
		$event_id,
		$batch_id
	) {

		$remaining = $this->capacity->remaining_seats(
			$batch_id
		);

		if ( $remaining < 1 ) {
			return 0;
		}

		$waiting = $this->database
			->registrations
			->get_waiting_registrations(
				$event_id,
				$remaining
			);

		if ( empty( $waiting ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $waiting as $registration ) {

			$this->database
				->registrations
				->move_to_batch(
					$registration->id,
					$batch_id
				);

			// Send promotion email.
			$event = $this->database
				->events
				->get_event( $event_id );

			if ( $event->promotion_email_enabled ) {

				$this->email->send_promotion_email(
					$registration->id
				);
			}

			$count++;
		}

		return $count;
	}

	/**
	 * Cancel a registration.
	 *
	 * @param int $registration_id Registration ID.
	 * @return bool
	 */
	public function cancel( $registration_id ) {

		return $this->database
			->registrations
			->update_registration(
				$registration_id,
				array(
					'status' => 'cancelled',
				)
			);
	}

	/**
	 * Move a registered attendee back to the waiting list.
	 *
	 * @param int $registration_id Registration ID.
	 * @return array
	 */
	public function move_to_waiting( $registration_id ) {

		$registration = $this->database
			->registrations
			->get_registration( $registration_id );

		if ( ! $registration ) {
			return array(
				'success' => false,
				'message' => __( 'Registration not found.', 'formflow' ),
			);
		}

		if ( 'registered' !== $registration->status ) {
			return array(
				'success' => false,
				'message' => __( 'Only registered attendees can be moved to the waiting list.', 'formflow' ),
			);
		}

		$updated = $this->database
			->registrations
			->update_registration(
				$registration_id,
				array(
					'status'   => 'waiting',
					'batch_id' => null,
				)
			);

		if ( ! $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to move registration to the waiting list.', 'formflow' ),
			);
		}

		return array(
			'success' => true,
		);
	}


	/**
	 * Assign a waiting registration to a batch.
	 *
	 * @param int $registration_id Registration ID.
	 * @param int $batch_id Batch ID.
	 * @return array
	 */
	public function assign_to_batch(
		$registration_id,
		$batch_id
	) {

		$registration = $this->database
			->registrations
			->get_registration( $registration_id );

		if ( ! $registration ) {
			return array(
				'success' => false,
				'message' => __( 'Registration not found.', 'formflow' ),
			);
		}

		if ( 'waiting' !== $registration->status ) {
			return array(
				'success' => false,
				'message' => __( 'Only waiting registrations can be assigned to a batch.', 'formflow' ),
			);
		}

		$batch = $this->database
			->batches
			->get_batch( $batch_id );

		if ( ! $batch ) {
			return array(
				'success' => false,
				'message' => __( 'Batch not found.', 'formflow' ),
			);
		}

		if ( (int) $batch->event_id !== (int) $registration->event_id ) {
			return array(
				'success' => false,
				'message' => __( 'The selected batch does not belong to this event.', 'formflow' ),
			);
		}

		if ( $this->capacity->is_full( $batch_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'The selected batch is full.', 'formflow' ),
			);
		}

		$updated = $this->database
			->registrations
			->update_registration(
				$registration_id,
				array(
					'status'   => 'registered',
					'batch_id' => $batch_id,
				)
			);

		if ( ! $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to assign registration to the batch.', 'formflow' ),
			);
		}

		$event = $this->database
			->events
			->get_event( $registration->event_id );

		if ( $event && $event->promotion_email_enabled ) {
			$this->email->send_promotion_email(
				$registration_id
			);
		}

		return array(
			'success' => true,
		);
	}
}