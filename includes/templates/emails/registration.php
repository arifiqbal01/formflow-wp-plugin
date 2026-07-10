<?php
/**
 * Registration Confirmation Email.
 *
 * Variables:
 * - FormFlow_Submission $submission
 * - object $event
 * - object|null $batch
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

$name = $submission->name();
?>

<h1
	style="
		margin:0 0 24px;
		font-size:30px;
		font-weight:700;
		color:#2F2F2F;
	"
>
	<?php esc_html_e( 'Inschrijving Bevestigd!', 'formflow' ); ?>
</h1>

<?php if ( ! empty( $name ) ) : ?>

	<p>

		<?php
		printf(
			/* translators: %s: Name. */
			esc_html__( 'Beste %s,', 'formflow' ),
			esc_html( $name )
		);
		?>

	</p>

<?php endif; ?>

<p>

	<?php
	printf(
		/* translators: %s: Event title. */
		__(
			'Bedankt voor uw inschrijving voor <strong>%s</strong>.',
			'formflow'
		),
		esc_html( $event->title )
	);
	?>

</p>

<p>

	<?php esc_html_e(
		'Met plezier bevestigen wij dat de inschrijving van uw kind succesvol is ontvangen en dat de plaats is gereserveerd.',
		'formflow'
	); ?>

</p>

<div
	style="
		background:#F8F4EC;
		border-left:4px solid #6F8B57;
		padding:18px 20px;
		margin:32px 0;
		border-radius:8px;
	"
>

	<strong style="color:#2F2F2F;">

		<?php esc_html_e(
			'Inschrijvingsgegevens',
			'formflow'
		); ?>

	</strong>

	<table
		role="presentation"
		width="100%"
		cellpadding="6"
		cellspacing="0"
		style="margin-top:14px;"
	>

		<tr>

			<td style="font-weight:bold;width:150px;">
				<?php esc_html_e( 'Programma', 'formflow' ); ?>
			</td>

			<td>
				<?php echo esc_html( $event->title ); ?>
			</td>

		</tr>

		<?php if ( ! empty( $batch ) ) : ?>

			<tr>

				<td style="font-weight:bold;">
					<?php esc_html_e( 'Groep', 'formflow' ); ?>
				</td>

				<td>

					<?php
					printf(
						esc_html__( 'Groep %d', 'formflow' ),
						(int) $batch->batch_number
					);
					?>

				</td>

			</tr>

		<?php endif; ?>

		<tr>

			<td style="font-weight:bold;">
				<?php esc_html_e( 'Status', 'formflow' ); ?>
			</td>

			<td style="color:#6F8B57;font-weight:bold;">
				<?php esc_html_e( 'Bevestigd', 'formflow' ); ?>
			</td>

		</tr>

	</table>

</div>

<p>

	<?php esc_html_e(
		'Mocht er vóór de start van het programma nog belangrijke informatie zijn, dan nemen wij per e-mail contact met u op.',
		'formflow'
	); ?>

</p>

<p>

	<?php esc_html_e(
		'Heeft u nog vragen? U kunt gerust op deze e-mail reageren.',
		'formflow'
	); ?>

</p>

<p style="margin-top:36px;">

	<?php esc_html_e( 'Met vriendelijke groet,', 'formflow' ); ?>

	<br>

	<strong>Sakynah</strong>

</p>