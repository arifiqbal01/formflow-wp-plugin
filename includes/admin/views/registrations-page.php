<?php
/**
 * Registrations Page.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">

	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Registrations', 'formflow' ); ?>
	</h1>

	<hr class="wp-header-end">

	<table class="widefat striped">

		<thead>

			<tr>
				<th><?php esc_html_e( 'ID', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Name', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Contact', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Event', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Batch', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Status', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Date', 'formflow' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'formflow' ); ?></th>
			</tr>

		</thead>

		<tbody>

			<?php if ( empty( $submissions ) ) : ?>

				<tr>

					<td colspan="7">
						<?php esc_html_e( 'No registrations found.', 'formflow' ); ?>
					</td>

				</tr>

			<?php else : ?>

				<?php foreach ( $submissions as $submission ) : ?>

					<?php
					$registration = $submission->registration();
					$contact      = $submission->contact();
					?>

					<tr>

						<td>
							<?php echo esc_html( $registration->id ); ?>
						</td>

						<td>
							<?php echo esc_html( $submission->name() ?: '—' ); ?>
						</td>

						<td>

							<?php if ( ! empty( $contact['email'] ) ) : ?>

								<div><?php echo esc_html( $contact['email'] ); ?></div>

							<?php endif; ?>

							<?php if ( ! empty( $contact['phone'] ) ) : ?>

								<div><?php echo esc_html( $contact['phone'] ); ?></div>

							<?php endif; ?>

							<?php if ( empty( $contact['email'] ) && empty( $contact['phone'] ) ) : ?>

								&mdash;

							<?php endif; ?>

						</td>

						<td>

							<?php echo esc_html( $submission->event()->title ); ?>

						</td>

						<td>

							<?php if ( ! empty( $registration->batch_number ) ) : ?>

								<?php
								printf(
									esc_html__( 'Batch #%d', 'formflow' ),
									$registration->batch_number
								);
								?>

							<?php else : ?>

								&mdash;

							<?php endif; ?>

						</td>

						<td>

							<?php if ( 'registered' === $registration->status ) : ?>

								<span style="color:green;font-weight:600;">
									<?php esc_html_e( 'Registered', 'formflow' ); ?>
								</span>

							<?php elseif ( 'waiting' === $registration->status ) : ?>

								<span style="color:#d97706;font-weight:600;">
									<?php esc_html_e( 'Waiting List', 'formflow' ); ?>
								</span>

							<?php else : ?>

								<span style="color:#666;font-weight:600;">
									<?php echo esc_html( ucfirst( $registration->status ) ); ?>
								</span>

							<?php endif; ?>

						</td>

						<td>

							<?php echo esc_html( $registration->created_at ); ?>

						</td>

						<td>

							<button
								type="button"
								class="button button-small formflow-toggle-details"
								data-target="registration-<?php echo esc_attr( $registration->id ); ?>"
							>
								<?php esc_html_e( 'View', 'formflow' ); ?>
							</button>

						</td>

					</tr>

					<tr
						id="registration-<?php echo esc_attr( $registration->id ); ?>"
						class="formflow-registration-details"
						style="display:none;"
					>

						<td colspan="8">

							<?php
							require FORMFLOW_PATH .
								'includes/admin/views/registration-details.php';
							?>

						</td>

					</tr>

				<?php endforeach; ?>

			<?php endif; ?>

		</tbody>

	</table>

	<script>
		document.addEventListener('DOMContentLoaded', function () {

			document.querySelectorAll('.formflow-toggle-details').forEach(function (button) {

				button.addEventListener('click', function () {

					const row = document.getElementById(this.dataset.target);

					row.style.display =
						row.style.display === 'none'
							? 'table-row'
							: 'none';

				});

			});

		});
	</script>

</div>