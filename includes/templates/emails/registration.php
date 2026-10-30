<?php
/**
 * Registration confirmation email.
 *
 * Variables:
 * - FormFlow_Submission $submission
 * - object $event
 * - object|null $batch
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

$name         = $submission->name();
$registration = $submission->registration();
$is_paid      = 'paid' === ( $registration->payment_status ?? '' );
?>

<h1 style="margin:0 0 24px;font-size:28px;line-height:1.3;font-weight:700;color:#6F8B57;">
	<?php esc_html_e( 'Uw inschrijving is bevestigd!', 'formflow' ); ?>
</h1>

<p style="margin:0 0 20px;">
	<?php
	if ( ! empty( $name ) ) {
		printf(
			/* translators: %s: recipient name. */
			esc_html__( 'Beste %s,', 'formflow' ),
			esc_html( $name )
		);
	} else {
		esc_html_e( 'Beste ouder/verzorger,', 'formflow' );
	}
	?>
</p>

<p style="margin:0 0 20px;">
	<?php
	printf(
		/* translators: %s: programme title. */
		esc_html__( 'Bedankt voor uw inschrijving voor %s.', 'formflow' ),
		esc_html( $event->title )
	);
	?>
</p>

<p style="margin:0 0 24px;">
	<?php
	if ( $is_paid ) {
		esc_html_e(
			'Wij hebben uw betaling ontvangen. De inschrijving van uw kind is definitief bevestigd en de plaats in de groep staat vast.',
			'formflow'
		);
	} else {
		esc_html_e(
			'De inschrijving van uw kind is definitief bevestigd en de plaats in de groep staat vast.',
			'formflow'
		);
	}
	?>
</p>

<table
	role="presentation"
	width="100%"
	cellpadding="0"
	cellspacing="0"
	border="0"
	style="margin:28px 0;background:#F8F4EC;border-left:4px solid #6F8B57;"
>
	<tr>
		<td style="padding:20px;">
			<strong style="font-size:17px;color:#2F2F2F;">
				<?php esc_html_e( 'Inschrijvingsgegevens', 'formflow' ); ?>
			</strong>

			<table
				role="presentation"
				width="100%"
				cellpadding="0"
				cellspacing="0"
				border="0"
				style="margin-top:14px;font-size:15px;line-height:1.6;"
			>
				<tr>
					<td style="padding:8px 12px 8px 0;font-weight:bold;vertical-align:top;">
						<?php esc_html_e( 'Programma', 'formflow' ); ?>
					</td>
					<td style="padding:8px 0;vertical-align:top;">
						<?php echo esc_html( $event->title ); ?>
					</td>
				</tr>

				<?php if ( ! empty( $batch ) ) : ?>
					<tr>
						<td style="padding:8px 12px 8px 0;font-weight:bold;">
							<?php esc_html_e( 'Groep', 'formflow' ); ?>
						</td>
						<td style="padding:8px 0;">
							<?php
							printf(
								/* translators: %d: group number. */
								esc_html__( 'Groep %d', 'formflow' ),
								(int) $batch->batch_number
							);
							?>
						</td>
					</tr>
				<?php endif; ?>

				<tr>
					<td style="padding:8px 12px 8px 0;font-weight:bold;">
						<?php esc_html_e( 'Status', 'formflow' ); ?>
					</td>
					<td style="padding:8px 0;color:#6F8B57;font-weight:bold;">
						<?php esc_html_e( 'Definitief bevestigd', 'formflow' ); ?>
					</td>
				</tr>

				<?php if ( $is_paid ) : ?>
					<tr>
						<td style="padding:8px 12px 8px 0;font-weight:bold;">
							<?php esc_html_e( 'Betaling', 'formflow' ); ?>
						</td>
						<td style="padding:8px 0;color:#6F8B57;">
							<?php esc_html_e( 'Ontvangen', 'formflow' ); ?>
						</td>
					</tr>

					<tr>
						<td style="padding:8px 12px 8px 0;font-weight:bold;">
							<?php esc_html_e( 'Betaald bedrag', 'formflow' ); ?>
						</td>
						<td style="padding:8px 0;">
							<?php
							echo esc_html(
								sprintf(
									'%s %s',
									number_format_i18n(
										(float) $registration->payment_amount,
										2
									),
									$registration->payment_currency
								)
							);
							?>
						</td>
					</tr>
				<?php endif; ?>
			</table>
		</td>
	</tr>
</table>

<p style="margin:0 0 20px;">
	<?php
	esc_html_e(
		'Eventuele aanvullende informatie over de start van het programma ontvangt u per e-mail.',
		'formflow'
	);
	?>
</p>

<p style="margin:0 0 28px;">
	<?php
	esc_html_e(
		'Heeft u nog vragen? Neem gerust contact met ons op.',
		'formflow'
	);
	?>
</p>

<p style="margin:0;">
	<?php esc_html_e( 'Met vriendelijke groet,', 'formflow' ); ?><br>
	<strong>Team Sakynah</strong>
</p>