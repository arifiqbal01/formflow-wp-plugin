<?php
/**
 * Registration details and actions.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

$event = $submission->event();

$needs_payment = (
	! empty( $event->payment_required ) ||
	(float) $registration->payment_amount > 0
) && 'paid' !== $registration->payment_status;

$status_labels = array(
	'registered'      => __( 'Registered', 'formflow' ),
	'waiting'         => __( 'Waiting List', 'formflow' ),
	'pending_payment' => __( 'Pending Payment', 'formflow' ),
	'payment_review'  => __( 'Payment Review', 'formflow' ),
	'cancelled'       => __( 'Cancelled', 'formflow' ),
);

$status_label = $status_labels[ $registration->status ]
	?? ucwords( str_replace( '_', ' ', $registration->status ) );

$invitation_payment = null;

if (
	'pending_payment' === $registration->status &&
	! empty( $registration->payment_id ) &&
	function_exists( 'get_pronamic_payment' )
) {
	$invitation_payment = get_pronamic_payment(
		$registration->payment_id
	);
}

$is_invitation = (
	$invitation_payment &&
	'formflow' === $invitation_payment->get_source() &&
	(int) $invitation_payment->get_source_id() ===
		(int) $registration->id &&
	$invitation_payment->get_meta( 'formflow_invitation' )
);

$invitation_active = (
	$is_invitation &&
	! empty( $registration->reservation_expires_at ) &&
	$registration->reservation_expires_at >
		gmdate( 'Y-m-d H:i:s' )
);

/**
 * Render a POST button for sending or resending an invitation.
 * Local closure avoids redeclaring a function for each table row.
 */
$render_invitation_button = static function ( $label ) use ( $registration ) {
	?>
	<form
		method="post"
		action="<?php echo esc_url(
			admin_url( 'admin.php?page=formflow-registrations' )
		); ?>"
	>
		<input
			type="hidden"
			name="action"
			value="invite_payment"
		>

		<input
			type="hidden"
			name="registration"
			value="<?php echo esc_attr( $registration->id ); ?>"
		>

		<?php
		wp_nonce_field(
			'formflow_invite_payment_' . $registration->id
		);
		?>

		<p>
			<button
				type="submit"
				class="button button-primary"
				onclick="return confirm('<?php
				echo esc_js(
					__(
						'Send a payment invitation to the saved email address?',
						'formflow'
					)
				);
				?>');"
			>
				<?php echo esc_html( $label ); ?>
			</button>
		</p>
	</form>
	<?php
};
?>

<h3><?php esc_html_e( 'Payment', 'formflow' ); ?></h3>

<p>
	<?php
	echo esc_html(
		sprintf(
			'%s %s — %s',
			$registration->payment_amount,
			$registration->payment_currency,
			$registration->payment_status
		)
	);
	?>
</p>

<?php if ( ! empty( $registration->payment_id ) ) : ?>
	<p>
		<a href="<?php echo esc_url(
			admin_url(
				'post.php?post=' .
				absint( $registration->payment_id ) .
				'&action=edit'
			)
		); ?>">
			<?php esc_html_e( 'View payment in Pronamic', 'formflow' ); ?>
		</a>
	</p>
<?php endif; ?>

<?php if ( 'payment_review' === $registration->status ) : ?>
	<p>
		<?php
		esc_html_e(
			'Payment needs review. Verify the amount and available places. Arrange a refund in Pronamic/Mollie if admission is not possible. This entry is not confirmed.',
			'formflow'
		);
		?>
	</p>
<?php endif; ?>

<?php if (
	'pending_payment' === $registration->status &&
	! empty( $registration->reservation_expires_at )
) : ?>
	<?php
	$expiry = strtotime(
		$registration->reservation_expires_at . ' UTC'
	);
	?>

	<?php if ( false !== $expiry ) : ?>
		<p>
			<strong>
				<?php esc_html_e( 'Reservation expires:', 'formflow' ); ?>
			</strong>

			<?php
			echo esc_html(
				wp_date(
					get_option( 'date_format' ) . ' ' .
					get_option( 'time_format' ) . ' T',
					$expiry,
					wp_timezone()
				)
			);
			?>
		</p>
	<?php endif; ?>
<?php endif; ?>

<table class="widefat striped">
	<tbody>
		<?php foreach ( $submission->to_array() as $field => $value ) : ?>
			<tr>
				<th style="width:220px;">
					<?php echo esc_html( $field ); ?>
				</th>

				<td>
					<?php
					echo esc_html(
						is_array( $value ) || is_object( $value )
							? wp_json_encode(
								$value,
								JSON_UNESCAPED_UNICODE
							)
							: (string) $value
					);
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<hr>

<h3><?php esc_html_e( 'Assignment', 'formflow' ); ?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th><?php esc_html_e( 'Status', 'formflow' ); ?></th>
			<td>
				<strong><?php echo esc_html( $status_label ); ?></strong>
			</td>
		</tr>

		<tr>
			<th><?php esc_html_e( 'Event', 'formflow' ); ?></th>
			<td><?php echo esc_html( $event->title ); ?></td>
		</tr>

		<tr>
			<th><?php esc_html_e( 'Batch', 'formflow' ); ?></th>
			<td>
				<?php
				if ( ! empty( $registration->batch_number ) ) {
					printf(
						esc_html__( 'Batch #%d', 'formflow' ),
						(int) $registration->batch_number
					);
				} else {
					echo '&mdash;';
				}
				?>
			</td>
		</tr>

		<tr>
			<th><?php esc_html_e( 'Actions', 'formflow' ); ?></th>
			<td>
				<?php if ( ! current_user_can( 'manage_options' ) ) : ?>

					<p>
						<?php esc_html_e( 'No actions available.', 'formflow' ); ?>
					</p>

				<?php elseif ( 'registered' === $registration->status ) : ?>

					<p>
						<a
							class="button"
							href="<?php echo esc_url(
								wp_nonce_url(
									admin_url(
										'admin.php?page=formflow-registrations' .
										'&action=move_to_waiting&registration=' .
										absint( $registration->id )
									),
									'formflow_move_to_waiting'
								)
							); ?>"
							onclick="return confirm('<?php
							echo esc_js(
								__(
									'Move this registration to the waiting list?',
									'formflow'
								)
							);
							?>');"
						>
							<?php esc_html_e( 'Move to Waiting List', 'formflow' ); ?>
						</a>
					</p>

					<p>
						<a
							class="button button-secondary"
							href="<?php echo esc_url(
								wp_nonce_url(
									admin_url(
										'admin.php?page=formflow-registrations' .
										'&action=cancel_registration&registration=' .
										absint( $registration->id )
									),
									'formflow_cancel_registration'
								)
							); ?>"
							onclick="return confirm('<?php
							echo esc_js(
								__( 'Cancel this registration?', 'formflow' )
							);
							?>');"
						>
							<?php esc_html_e( 'Cancel Registration', 'formflow' ); ?>
						</a>
					</p>

				<?php elseif ( 'waiting' === $registration->status ) : ?>

					<?php if ( empty( $event->registration_open ) ) : ?>

						<p>
							<?php esc_html_e( 'Registration is currently closed.', 'formflow' ); ?>
						</p>

					<?php elseif ( empty( $registration->open_batch ) ) : ?>

						<p>
							<?php esc_html_e( 'No open batch available.', 'formflow' ); ?>
						</p>

					<?php else : ?>

						<p>
							<?php
							printf(
								esc_html__( 'Open Batch: #%d', 'formflow' ),
								(int) $registration->open_batch->batch_number
							);
							?>
						</p>

						<?php if ( $needs_payment ) : ?>

							<?php if (
								empty( $registration->payment_id ) &&
								'refunded' !== $registration->payment_status
							) : ?>

								<?php
								$render_invitation_button(
									__( 'Send payment invitation', 'formflow' )
								);
								?>

								<p class="description">
									<?php
									esc_html_e(
										'Reserves an available place for 24 hours and emails a payment invitation. Registration is confirmed only after successful payment.',
										'formflow'
									);
									?>
								</p>

							<?php else : ?>

								<p>
									<?php
									esc_html_e(
										'Review the existing payment before creating another invitation.',
										'formflow'
									);
									?>
								</p>

							<?php endif; ?>

						<?php else : ?>

							<p>
								<a
									class="button button-primary"
									href="<?php echo esc_url(
										wp_nonce_url(
											admin_url(
												'admin.php?page=formflow-registrations' .
												'&action=assign_to_batch&registration=' .
												absint( $registration->id )
											),
											'formflow_assign_to_batch'
										)
									); ?>"
									onclick="return confirm('<?php
									echo esc_js(
										__(
											'Assign this registration to the open batch?',
											'formflow'
										)
									);
									?>');"
								>
									<?php esc_html_e( 'Assign to Batch', 'formflow' ); ?>
								</a>
							</p>

						<?php endif; ?>

					<?php endif; ?>

				<?php elseif ( 'pending_payment' === $registration->status ) : ?>

					<p>
						<?php
						esc_html_e(
							'Registration is not confirmed. Successful payment is required.',
							'formflow'
						);
						?>
					</p>

					<?php if ( $invitation_active ) : ?>

						<?php
						$render_invitation_button(
							__( 'Resend payment invitation', 'formflow' )
						);
						?>

						<p class="description">
							<?php
							esc_html_e(
								'Reuses the existing invitation and deadline. No additional payment is created.',
								'formflow'
							);
							?>
						</p>

					<?php elseif ( $is_invitation ) : ?>

						<p>
							<?php esc_html_e( 'The payment invitation has expired.', 'formflow' ); ?>
						</p>

					<?php endif; ?>

				<?php elseif ( 'payment_review' === $registration->status ) : ?>

					<p>
						<?php
						esc_html_e(
							'Review the linked payment in Pronamic before changing this registration.',
							'formflow'
						);
						?>
					</p>

				<?php else : ?>

					<p>
						<?php esc_html_e( 'No actions available.', 'formflow' ); ?>
					</p>

				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>