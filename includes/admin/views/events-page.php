<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">

	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'FormFlow Events', 'formflow' ); ?>
	</h1>

	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Event created successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Event updated successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Event deleted successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( ! empty( $_GET['error'] ) ) : ?>

		<div class="notice notice-error">
			<p><?php echo esc_html( wp_unslash( $_GET['error'] ) ); ?></p>
		</div>

	<?php endif; ?>

	<div class="formflow-event-form">

		<h2>
			<?php
			echo $event
				? esc_html__( 'Edit Event', 'formflow' )
				: esc_html__( 'Add Event', 'formflow' );
			?>
		</h2>

		<form method="post">

			<?php wp_nonce_field( 'formflow_save_event', 'formflow_nonce' ); ?>

			<?php if ( $event ) : ?>

				<input
					type="hidden"
					name="event_id"
					value="<?php echo esc_attr( $event->id ); ?>"
				/>

			<?php endif; ?>

			<table class="form-table">

				<tr>

					<th scope="row">
						<label for="title">
							<?php esc_html_e( 'Title', 'formflow' ); ?>
						</label>
					</th>

					<td>

						<input
							type="text"
							name="title"
							id="title"
							class="regular-text"
							required
							value="<?php echo esc_attr( $event ? $event->title : '' ); ?>"
						/>

					</td>

				</tr>

				<tr>

					<th scope="row">
						<label for="cf7_form_id">
							<?php esc_html_e( 'Form', 'formflow' ); ?>
						</label>
					</th>

					<td>

						<select
							name="cf7_form_id"
							id="cf7_form_id"
						>

							<option value="">
								<?php esc_html_e( 'Select a Form', 'formflow' ); ?>
							</option>

							<?php foreach ( $contact_forms as $form ) : ?>

								<option
									value="<?php echo esc_attr( $form->id() ); ?>"
									<?php
									selected(
										$event ? $event->cf7_form_id : '',
										$form->id()
									);
									?>
								>
									<?php echo esc_html( $form->title() ); ?>
								</option>

							<?php endforeach; ?>

						</select>

					</td>

				</tr>

				<?php if ( ! $event ) : ?>

				<tr>

					<th scope="row">
						<label for="capacity">
							<?php esc_html_e( 'Initial Batch Capacity', 'formflow' ); ?>
						</label>
					</th>

					<td>

						<input
							type="number"
							name="capacity"
							id="capacity"
							min="1"
							value="10"
							required
						/>

						<p class="description">
							<?php esc_html_e( 'Capacity for the first batch created with this event.', 'formflow' ); ?>
						</p>

					</td>

				</tr>

				<?php endif; ?>

				<tr>

					<th scope="row">
						<?php esc_html_e( 'Registration', 'formflow' ); ?>
					</th>

					<td>

						<label>

							<input
								type="checkbox"
								name="registration_open"
								value="1"
								<?php checked( $event ? $event->registration_open : 1 ); ?>
							/>

							<?php esc_html_e( 'Open registration', 'formflow' ); ?>

						</label>

					</td>

				</tr>

				<tr>

					<th scope="row">
						<?php esc_html_e( 'Waiting List', 'formflow' ); ?>
					</th>

					<td>

						<label>

							<input
								type="checkbox"
								name="waiting_list_enabled"
								value="1"
								<?php checked( $event ? $event->waiting_list_enabled : 1 ); ?>
							/>

							<?php esc_html_e( 'Enable waiting list', 'formflow' ); ?>

						</label>

					</td>

				</tr>

				<tr>

					<th scope="row">
						<?php esc_html_e( 'Promotion Email', 'formflow' ); ?>
					</th>

					<td>

						<label>

							<input
								type="checkbox"
								name="promotion_email_enabled"
								value="1"
								<?php checked( $event ? $event->promotion_email_enabled : 0 ); ?>
							/>

							<?php esc_html_e(
								'Automatically send promotion emails',
								'formflow'
							); ?>

						</label>

						<p class="description">

							<?php esc_html_e(
								'Send an email automatically when a waiting list registration is promoted to a batch.',
								'formflow'
							); ?>

						</p>

					</td>

				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Payment', 'formflow' ); ?></th>
					<td>
						<label><input type="checkbox" name="payment_required" value="1" <?php checked( $event ? $event->payment_required : 1 ); ?>> <?php esc_html_e( 'Require successful payment before registration', 'formflow' ); ?></label>
						<p class="description"><?php esc_html_e( 'Paid events do not accept waiting-list submissions or auto-promote unpaid applicants. Existing attendees are unchanged.', 'formflow' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="payment_amount"><?php esc_html_e( 'Programme price (EUR)', 'formflow' ); ?></label></th>
					<td><input type="number" min="0" max="99999999.99" step="0.01" id="payment_amount" name="payment_amount" value="<?php echo esc_attr( $event && (float) $event->payment_amount > 0 ? $event->payment_amount : '80.00' ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="pronamic_config_id"><?php esc_html_e( 'Pronamic Mollie configuration ID', 'formflow' ); ?></label></th>
					<td><input type="number" min="0" id="pronamic_config_id" name="pronamic_config_id" value="<?php echo esc_attr( $event ? $event->pronamic_config_id : 0 ); ?>">
					<p class="description"><?php esc_html_e( 'Use 0 for the default Pronamic configuration. It must be a published Mollie configuration. Checkout reservations last 30 minutes.', 'formflow' ); ?></p></td>
				</tr>

			</table>

			<?php
			submit_button(
				$event
					? __( 'Update Event', 'formflow' )
					: __( 'Save Event', 'formflow' )
			);
			?>

			<?php if ( $event ) : ?>

				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=formflow-events' ) ); ?>"
					class="button"
				>
					<?php esc_html_e( 'Cancel', 'formflow' ); ?>
				</a>

			<?php endif; ?>

		</form>

	</div>

	<hr>

	<h2><?php esc_html_e( 'Events', 'formflow' ); ?></h2>

	<table class="widefat striped">

		<thead>

			<tr>

				<th><?php esc_html_e( 'Title', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Contact Form', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Registration', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Waiting List', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Promotion Email', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Actions', 'formflow' ); ?></th>

			</tr>

		</thead>

		<tbody>

			<?php if ( empty( $events ) ) : ?>

				<tr>

					<td colspan="6">
						<?php esc_html_e( 'No events found.', 'formflow' ); ?>
					</td>

				</tr>

			<?php else : ?>

				<?php foreach ( $events as $event ) : ?>

					<tr>

						<td><?php echo esc_html( $event->title ); ?></td>

						<td><?php echo esc_html( $event->cf7_form_id ); ?></td>

						<td>

							<?php
							echo $event->registration_open
								? esc_html__( 'Open', 'formflow' )
								: esc_html__( 'Closed', 'formflow' );
							?>

						</td>

						<td>

							<?php
							echo $event->waiting_list_enabled
								? esc_html__( 'Enabled', 'formflow' )
								: esc_html__( 'Disabled', 'formflow' );
							?>

						</td>

						<td>

							<?php
							echo $event->promotion_email_enabled
								? esc_html__( 'Enabled', 'formflow' )
								: esc_html__( 'Disabled', 'formflow' );
							?>

						</td>

						<td>

							<a
								href="<?php echo esc_url(
									admin_url(
										'admin.php?page=formflow-events&action=edit&id=' . $event->id
									)
								); ?>"
							>
								<?php esc_html_e( 'Edit', 'formflow' ); ?>
							</a>

							|

							<a
								href="<?php echo esc_url(
									admin_url(
										'admin.php?page=formflow-event-batches&event=' . $event->id
									)
								); ?>"
							>
								<?php esc_html_e( 'Batches', 'formflow' ); ?>
							</a>

							|

							<a
								href="<?php echo esc_url(
									wp_nonce_url(
										admin_url(
											'admin.php?page=formflow-events&action=delete&event=' . $event->id
										),
										'formflow_delete_event'
									)
								); ?>"
								onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this event?', 'formflow' ) ); ?>');"
							>
								<?php esc_html_e( 'Delete', 'formflow' ); ?>
							</a>

						</td>

					</tr>

				<?php endforeach; ?>

			<?php endif; ?>

		</tbody>

	</table>

</div>