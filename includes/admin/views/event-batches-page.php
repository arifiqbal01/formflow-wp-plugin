<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">

	<h1 class="wp-heading-inline">
		<?php
		printf(
			/* translators: %s: Event title. */
			esc_html__( 'Batches: %s', 'formflow' ),
			esc_html( $event->title )
		);
		?>
	</h1>

	<a
		href="<?php echo esc_url( admin_url( 'admin.php?page=formflow-events' ) ); ?>"
		class="page-title-action"
	>
		<?php esc_html_e( 'Back to Events', 'formflow' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php if ( isset( $_GET['created'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Batch created successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['capacity-updated'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Batch capacity updated successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['closed'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Batch closed successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['completed'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Batch completed successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( isset( $_GET['cancelled'] ) ) : ?>

		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Batch cancelled successfully.', 'formflow' ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( ! empty( $_GET['error'] ) ) : ?>

		<div class="notice notice-error">
			<p><?php echo esc_html( wp_unslash( $_GET['error'] ) ); ?></p>
		</div>

	<?php endif; ?>

	<?php if ( $open_batch ) : ?>

		<h2><?php esc_html_e( 'Current Open Batch', 'formflow' ); ?></h2>

		<table class="form-table">

			<tr>

				<th><?php esc_html_e( 'Batch', 'formflow' ); ?></th>

				<td>
					<?php
					printf(
						esc_html__( 'Batch #%d', 'formflow' ),
						$open_batch->batch_number
					);
					?>
				</td>

			</tr>

			<tr>

				<th><?php esc_html_e( 'Capacity', 'formflow' ); ?></th>

				<td>

				<form method="post">

					<?php wp_nonce_field(
						'formflow_update_capacity',
						'formflow_nonce'
					); ?>

					<input
						type="hidden"
						name="action"
						value="update-capacity"
					/>

					<input
						type="hidden"
						name="event_id"
						value="<?php echo esc_attr( $event->id ); ?>"
					/>

					<input
						type="hidden"
						name="batch_id"
						value="<?php echo esc_attr( $open_batch->id ); ?>"
					/>

					<input
						type="number"
						name="capacity"
						min="1"
						value="<?php echo esc_attr( $open_batch->capacity ); ?>"
						style="width:90px;"
					/>

					<?php
					submit_button(
						__( 'Update', 'formflow' ),
						'secondary',
						'',
						false
					);
					?>

				</form>

			</td>

			</tr>

			</tr>

			<tr>

				<th><?php esc_html_e( 'Registered', 'formflow' ); ?></th>

				<td>

					<?php
					printf(
						esc_html__( '%1$d / %2$d', 'formflow' ),
						$capacity['registered'],
						$capacity['capacity']
					);
					?>

				</td>

			</tr>

			<tr>

				<th><?php esc_html_e( 'Remaining Seats', 'formflow' ); ?></th>

				<td>

					<?php echo esc_html( $capacity['remaining'] ); ?>

				</td>

			</tr>

			<tr>

				<th><?php esc_html_e( 'Waiting List', 'formflow' ); ?></th>

				<td>

					<?php echo esc_html( $waiting ); ?>

				</td>

			</tr>

			<tr>

				<th><?php esc_html_e( 'Status', 'formflow' ); ?></th>

				<td><?php echo esc_html( ucfirst( $open_batch->status ) ); ?></td>

			</tr>

		</table>

		<p>

			<a
				class="button"
				href="<?php echo esc_url(
					wp_nonce_url(
						admin_url(
							'admin.php?page=formflow-event-batches&event=' .
							$event->id .
							'&action=close&batch=' .
							$open_batch->id
						),
						'formflow_close_batch'
					)
				); ?>"
			>
				<?php esc_html_e( 'Close Batch', 'formflow' ); ?>
			</a>

			<a
				class="button"
				href="<?php echo esc_url(
					wp_nonce_url(
						admin_url(
							'admin.php?page=formflow-event-batches&event=' .
							$event->id .
							'&action=complete&batch=' .
							$open_batch->id
						),
						'formflow_complete_batch'
					)
				); ?>"
			>
				<?php esc_html_e( 'Complete', 'formflow' ); ?>
			</a>

			<a
				class="button"
				href="<?php echo esc_url(
					wp_nonce_url(
						admin_url(
							'admin.php?page=formflow-event-batches&event=' .
							$event->id .
							'&action=cancel&batch=' .
							$open_batch->id
						),
						'formflow_cancel_batch'
					)
				); ?>"
				onclick="return confirm('<?php echo esc_js( __( 'Cancel this batch?', 'formflow' ) ); ?>');"
			>
				<?php esc_html_e( 'Cancel Batch', 'formflow' ); ?>
			</a>

		</p>

	<?php else : ?>

		<h2><?php esc_html_e( 'Create Batch', 'formflow' ); ?></h2>

		<form
			method="post"
			action="<?php echo esc_url(
				admin_url(
					'admin.php?page=formflow-event-batches&event=' .
					$event->id .
					'&action=create'
				)
			); ?>"
		>

			<?php wp_nonce_field( 'formflow_create_batch', 'formflow_nonce' ); ?>

			<input
				type="hidden"
				name="event_id"
				value="<?php echo esc_attr( $event->id ); ?>"
			/>

			<table class="form-table">

				<tr>

					<th>

						<label for="capacity">
							<?php esc_html_e( 'Capacity', 'formflow' ); ?>
						</label>

					</th>

					<td>

						<input
							type="number"
							id="capacity"
							name="capacity"
							min="1"
							value="10"
							required
						/>

					</td>

				</tr>

			</table>

			<?php submit_button( __( 'Create Batch', 'formflow' ) ); ?>

		</form>

	<?php endif; ?>

	<hr>

	<h2><?php esc_html_e( 'All Batches', 'formflow' ); ?></h2>

	<table class="widefat striped">

		<thead>

			<tr>

				<th><?php esc_html_e( 'Batch', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Capacity', 'formflow' ); ?></th>

				<th><?php esc_html_e( 'Status', 'formflow' ); ?></th>

			</tr>

		</thead>

		<tbody>

			<?php if ( empty( $batches ) ) : ?>

				<tr>

					<td colspan="3">

						<?php esc_html_e( 'No batches found.', 'formflow' ); ?>

					</td>

				</tr>

			<?php else : ?>

				<?php foreach ( $batches as $batch ) : ?>

					<tr>

						<td>

							<?php
							printf(
								esc_html__( 'Batch #%d', 'formflow' ),
								$batch->batch_number
							);
							?>

						</td>

						<td>

							<?php echo esc_html( $batch->capacity ); ?>

						</td>

						<td>

							<?php echo esc_html( ucfirst( $batch->status ) ); ?>

						</td>

					</tr>

				<?php endforeach; ?>

			<?php endif; ?>

		</tbody>

	</table>

</div>