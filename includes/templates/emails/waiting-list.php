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
	<?php esc_html_e( 'U staat op de wachtlijst', 'formflow' ); ?>
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
		'Helaas is de huidige groep volledig vol. Daarom is de inschrijving van uw kind op de wachtlijst geplaatst.',
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
			'Hoe gaat het nu verder?',
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
				'Uw plaats op de wachtlijst is succesvol geregistreerd.',
				'formflow'
			); ?>
		</li>

		<li>
			<?php esc_html_e(
				'Zodra er een plaats beschikbaar komt, wordt uw kind automatisch doorgeschoven.',
				'formflow'
			); ?>
		</li>

		<li>
			<?php esc_html_e(
				'U ontvangt vervolgens automatisch een bevestigingsmail zodra de inschrijving definitief is.',
				'formflow'
			); ?>
		</li>

	</ul>

</div>

<p>

	<?php esc_html_e(
		'Bedankt voor uw geduld en uw interesse in Sakynah. Wij hopen uw kind binnenkort te mogen verwelkomen.',
		'formflow'
	); ?>

</p>

<p style="margin-top:36px;">

	<?php esc_html_e( 'Met vriendelijke groet,', 'formflow' ); ?>

	<br>

	<strong>Sakynah</strong>

</p>