<?php
/**
 * Dashboard Page
 *
 * Variables:
 * $stats = [
 *     'events'      => 0,
 *     'registered'  => 0,
 *     'waiting'     => 0,
 * ];
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap formflow-dashboard">

	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'FormFlow Dashboard', 'formflow' ); ?>
	</h1>

	<hr class="wp-header-end">

	<div class="postbox">

		<div class="postbox-header">
			<h2 class="hndle"><?php esc_html_e( 'Overview', 'formflow' ); ?></h2>
		</div>

		<div class="inside">

			<div class="formflow-dashboard-cards">

				<div class="card formflow-stat-card">
					<div class="formflow-stat-number">
						<?php echo esc_html( $stats['events'] ); ?>
					</div>

					<div class="formflow-stat-label">
						<?php esc_html_e( 'Events', 'formflow' ); ?>
					</div>
				</div>

				<div class="card formflow-stat-card">
					<div class="formflow-stat-number">
						<?php echo esc_html( $stats['registered'] ); ?>
					</div>

					<div class="formflow-stat-label">
						<?php esc_html_e( 'Registered', 'formflow' ); ?>
					</div>
				</div>

				<div class="card formflow-stat-card">
					<div class="formflow-stat-number">
						<?php echo esc_html( $stats['waiting'] ); ?>
					</div>

					<div class="formflow-stat-label">
						<?php esc_html_e( 'Waiting List', 'formflow' ); ?>
					</div>
				</div>

			</div>

		</div>

	</div>

	<div class="postbox">

		<div class="postbox-header">
			<h2 class="hndle"><?php esc_html_e( 'Quick Actions', 'formflow' ); ?></h2>
		</div>

		<div class="inside">

			<p class="formflow-actions">

				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=formflow-events' ) ); ?>"
					class="button button-primary"
				>
					<?php esc_html_e( 'Manage Events', 'formflow' ); ?>
				</a>

				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=formflow-registrations' ) ); ?>"
					class="button"
				>
					<?php esc_html_e( 'View Registrations', 'formflow' ); ?>
				</a>

			</p>

		</div>

	</div>

	<div class="postbox">

		<div class="postbox-header">
			<h2 class="hndle">
				<?php esc_html_e( 'Events Overview', 'formflow' ); ?>
			</h2>
		</div>

		<div class="inside">

			<table class="widefat striped">

				<thead>

					<tr>

						<th><?php esc_html_e( 'Event', 'formflow' ); ?></th>

						<th><?php esc_html_e( 'Open Batch', 'formflow' ); ?></th>

						<th><?php esc_html_e( 'Registered', 'formflow' ); ?></th>

						<th><?php esc_html_e( 'Waiting', 'formflow' ); ?></th>

					</tr>

				</thead>

				<tbody>

				<?php if ( empty( $events ) ) : ?>

					<tr>

						<td colspan="4">
							<?php esc_html_e( 'No events found.', 'formflow' ); ?>
						</td>

					</tr>

				<?php else : ?>

					<?php foreach ( $events as $event ) : ?>

						<?php

						$batch = $this->database
							->batches
							->get_open_batch( $event->id );

						$registered = 0;
						$capacity   = 0;

						if ( $batch ) {

							$registered = $this->database
								->registrations
								->count_batch_registrations(
									$batch->id,
									'registered'
								);

							$capacity = $batch->capacity;
						}

						$waiting = $this->database
							->registrations
							->count_waiting( $event->id );

						?>

						<tr>

							<td>
								<?php echo esc_html( $event->title ); ?>
							</td>

							<td>

								<?php

								if ( $batch ) {

									printf(
										esc_html__( 'Batch #%d', 'formflow' ),
										$batch->batch_number
									);

								} else {

									esc_html_e( 'None', 'formflow' );

								}

								?>

							</td>

							<td>

								<?php

								if ( $batch ) {

									printf(
										'%d / %d',
										$registered,
										$capacity
									);

								} else {

									echo '&mdash;';

								}

								?>

							</td>

							<td>

								<?php echo esc_html( $waiting ); ?>

							</td>

						</tr>

					<?php endforeach; ?>

				<?php endif; ?>

				</tbody>

			</table>

		</div>

	</div>

</div>