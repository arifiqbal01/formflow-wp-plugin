<?php
/**
 * Registration Details.
 *
 * Variables:
 * - FormFlow_Submission $submission
 * - object $registration
 * - array $contact
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;
?>

<table class="widefat striped">

	<tbody>

		<?php foreach ( $submission->to_array() as $field => $value ) : ?>

			<tr>

				<th style="width:220px;">
					<?php echo esc_html( $field ); ?>
				</th>

				<td>

					<?php
					if ( is_array( $value ) ) {
						echo esc_html(
							implode( ', ', $value )
						);
					} else {
						echo esc_html( $value );
					}
					?>

				</td>

			</tr>

		<?php endforeach; ?>

	</tbody>

</table>

<hr>

<h3>

	<?php esc_html_e(
		'Assignment',
		'formflow'
	); ?>

</h3>

<table class="form-table">

	<tbody>

		<tr>

			<th>

				<?php esc_html_e(
					'Status',
					'formflow'
				); ?>

			</th>

			<td>

				<?php
				if ( 'registered' === $registration->status ) {

					echo '<span style="color:green;font-weight:600;">';
					esc_html_e(
						'Registered',
						'formflow'
					);
					echo '</span>';

				} elseif ( 'waiting' === $registration->status ) {

					echo '<span style="color:#d97706;font-weight:600;">';
					esc_html_e(
						'Waiting List',
						'formflow'
					);
					echo '</span>';

				} elseif ( 'cancelled' === $registration->status ) {

					echo '<span style="color:#dc2626;font-weight:600;">';
					esc_html_e(
						'Cancelled',
						'formflow'
					);
					echo '</span>';

				} else {

					echo esc_html(
						ucfirst( $registration->status )
					);

				}
				?>

			</td>

		</tr>

		<tr>

			<th>

				<?php esc_html_e(
					'Event',
					'formflow'
				); ?>

			</th>

			<td>

				<?php
				echo esc_html(
					$submission->event()->title
				);
				?>

			</td>

		</tr>

		<tr>

			<th>

				<?php esc_html_e(
					'Batch',
					'formflow'
				); ?>

			</th>

			<td>

				<?php if ( ! empty( $registration->batch_number ) ) : ?>

					<?php
					printf(
						esc_html__(
							'Batch #%d',
							'formflow'
						),
						(int) $registration->batch_number
					);
					?>

				<?php else : ?>

					&mdash;

				<?php endif; ?>

			</td>

		</tr>

		<tr>

			<th>

				<?php esc_html_e(
					'Actions',
					'formflow'
				); ?>

			</th>

			<td>

                <?php if ( 'registered' === $registration->status ) : ?>

                    <p>
                        <a
                            class="button"
                            href="<?php
                            echo esc_url(
                                wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=formflow-registrations&action=move_to_waiting&registration=' .
                                        $registration->id
                                    ),
                                    'formflow_move_to_waiting'
                                )
                            );
                            ?>"
                            onclick="return confirm('<?php
                            echo esc_js(
                                __( 'Move this registration to the waiting list?', 'formflow' )
                            );
                            ?>');"
                        >
                            <?php
                            esc_html_e(
                                'Move to Waiting List',
                                'formflow'
                            );
                            ?>
                        </a>
                    </p>

                    <p>
                        <a
                            class="button button-secondary"
                            href="<?php
                            echo esc_url(
                                wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=formflow-registrations&action=cancel_registration&registration=' .
                                        $registration->id
                                    ),
                                    'formflow_cancel_registration'
                                )
                            ); ?>"
                            onclick="return confirm('<?php
                            echo esc_js(
                                __( 'Cancel this registration?', 'formflow' )
                            ); ?>');"
                        >
                            <?php esc_html_e( 'Cancel Registration', 'formflow' ); ?>
                        </a>
                    </p>

                <?php elseif ( 'waiting' === $registration->status ) : ?>

                    <?php if ( ! empty( $registration->open_batch ) ) : ?>

                        <p>
                            <?php
                            printf(
                                esc_html__(
                                    'Open Batch: #%d',
                                    'formflow'
                                ),
                                (int) $registration->open_batch->batch_number
                            );
                            ?>
                        </p>

                        <p>
                            <a
                                class="button button-primary"
                                href="<?php
                                echo esc_url(
                                    wp_nonce_url(
                                        admin_url(
                                            'admin.php?page=formflow-registrations&action=assign_to_batch&registration=' .
                                            $registration->id
                                        ),
                                        'formflow_assign_to_batch'
                                    )
                                );
                                ?>"
                                onclick="return confirm('<?php
                                echo esc_js(
                                    __( 'Assign this registration to the open batch?', 'formflow' )
                                );
                                ?>');"
                            >
                                <?php
                                esc_html_e(
                                    'Assign to Batch',
                                    'formflow'
                                );
                                ?>
                            </a>
                        </p>

                    <?php else : ?>

                        <p>
                            <?php
                            esc_html_e(
                                'No open batch available.',
                                'formflow'
                            );
                            ?>
                        </p>

                    <?php endif; ?>

                <?php elseif ( 'cancelled' === $registration->status ) : ?>

                    <p>
                        <?php
                        esc_html_e(
                            'No actions available.',
                            'formflow'
                        );
                        ?>
                    </p>

                <?php endif; ?>

            </td>

		</tr>

	</tbody>

</table>