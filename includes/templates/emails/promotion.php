<?php
/**
 * Promotion Email.
 *
 * Variables:
 * - FormFlow_Submission $submission
 * - object $event
 * - object $batch
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
	<?php esc_html_e( 'Registration Confirmed!', 'formflow' ); ?>
</h1>

<?php if ( ! empty( $name ) ) : ?>

	<p>

		<?php
		printf(
			/* translators: %s: Name. */
			esc_html__( 'Dear %s,', 'formflow' ),
			esc_html( $name )
		);
		?>

	</p>

<?php endif; ?>

<p>

	<?php
	printf(
		/* translators: %s: Event title. */
		esc_html__(
			'Great news! A place has become available for %s.',
			'formflow'
		),
		'<strong>' . esc_html( $event->title ) . '</strong>'
	);
	?>

</p>

<p>

	<?php esc_html_e(
		'Your registration has now been confirmed and your child has been successfully assigned to the next available batch.',
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
			'Registration Details',
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
				<?php esc_html_e( 'Programme', 'formflow' ); ?>
			</td>

			<td>
				<?php echo esc_html( $event->title ); ?>
			</td>

		</tr>

		<tr>

			<td style="font-weight:bold;">
				<?php esc_html_e( 'Batch', 'formflow' ); ?>
			</td>

			<td>

				<?php
				printf(
					esc_html__( 'Batch #%d', 'formflow' ),
					(int) $batch->batch_number
				);
				?>

			</td>

		</tr>

		<tr>

			<td style="font-weight:bold;">
				<?php esc_html_e( 'Status', 'formflow' ); ?>
			</td>

			<td style="color:#6F8B57;font-weight:bold;">
				<?php esc_html_e( 'Confirmed', 'formflow' ); ?>
			</td>

		</tr>

	</table>

</div>

<p>

	<?php esc_html_e(
		'We are delighted to welcome your child to Sakynah. If there are any further updates or important information before the programme begins, we will contact you by email.',
		'formflow'
	); ?>

</p>

<p>

	<?php esc_html_e(
		'If you have any questions, please feel free to reply to this email.',
		'formflow'
	); ?>

</p>

<p style="margin-top:36px;">

	<?php esc_html_e( 'Kind regards,', 'formflow' ); ?>

	<br>

	<strong>Sakynah</strong>

</p>