<?php
/**
 * Waiting List Email.
 *
 * Variables:
 * - FormFlow_Submission $submission
 * - object $event
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
	<?php esc_html_e( 'You’re on the Waiting List', 'formflow' ); ?>
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
			'Thank you for registering for %s.',
			'formflow'
		),
		'<strong>' . esc_html( $event->title ) . '</strong>'
	);
	?>

</p>

<p>

	<?php esc_html_e(
		'Unfortunately, the current batch has reached capacity, so your registration has been placed on the waiting list.',
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
			'What happens next?',
			'formflow'
		); ?>

	</strong>

	<ul
		style="
			margin:12px 0 0 18px;
			padding:0;
			line-height:1.8;
		"
	>

		<li>
			<?php esc_html_e(
				'Your place on the waiting list has been secured.',
				'formflow'
			); ?>
		</li>

		<li>
			<?php esc_html_e(
				'If a place becomes available, you will automatically be promoted.',
				'formflow'
			); ?>
		</li>

		<li>
			<?php esc_html_e(
				'You will receive another email once your registration has been confirmed.',
				'formflow'
			); ?>
		</li>

	</ul>

</div>

<p>

	<?php esc_html_e(
		'Thank you for your patience and interest in Sakynah. We appreciate your trust and hope to welcome your child soon.',
		'formflow'
	); ?>

</p>

<p style="margin-top:36px;">

	<?php esc_html_e( 'Kind regards,', 'formflow' ); ?>

	<br>

	<strong>Sakynah</strong>

</p>