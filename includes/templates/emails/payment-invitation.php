<?php
/**
 * Payment invitation email.
 *
 * Variables:
 * - object $event
 * - string $amount
 * - string $currency
 * - string $deadline
 * - string $payment_url
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;
?>

<h1 style="margin:0 0 24px;font-size:26px;line-height:1.3;color:#6F8B57;">
	Er is een plaats beschikbaar!
</h1>

<p style="margin:0 0 20px;">
	Beste ouder/verzorger,
</p>

<p style="margin:0 0 20px;">
	Er is een plaats beschikbaar voor
	<strong><?php echo esc_html( $event->title ); ?></strong>.
	We hebben deze plaats tijdelijk voor u gereserveerd.
</p>

<table
	role="presentation"
	width="100%"
	cellpadding="0"
	cellspacing="0"
	border="0"
	style="margin:24px 0;background:#F8F4EC;border:1px solid #ECE7DD;"
>
	<tr>
		<td style="padding:20px;">
			<p style="margin:0 0 12px;">
				<strong>Te betalen:</strong>
				<?php
				echo esc_html(
					sprintf( '%s %s', $amount, $currency )
				);
				?>
			</p>

			<p style="margin:0;">
				<strong>Betaal uiterlijk:</strong><br>
				<?php echo esc_html( $deadline ); ?>
			</p>
		</td>
	</tr>
</table>

<p style="margin:0 0 24px;">
	Klik op onderstaande knop om uw betaling veilig af te ronden.
	Uw inschrijving is pas definitief na een succesvolle betaling.
</p>

<table
	role="presentation"
	cellpadding="0"
	cellspacing="0"
	border="0"
	style="margin:0 0 28px;"
>
	<tr>
		<td
			align="center"
			bgcolor="#6F8B57"
			style="border-radius:6px;"
		>
			<a
				href="<?php echo esc_url( $payment_url ); ?>"
				style="display:inline-block;padding:15px 28px;border:1px solid #6F8B57;border-radius:6px;color:#FFFFFF;text-decoration:none;font-size:16px;font-weight:bold;"
			>
				Inschrijving afronden en betalen
			</a>
		</td>
	</tr>
</table>

<p style="margin:0 0 20px;">
	Na uw betaling ontvangt u een bevestiging per e-mail.
	Als de betalingstermijn verstrijkt, vervalt de reservering
	en kan de plaats aan iemand anders worden aangeboden.
</p>

<p style="margin:0 0 12px;font-size:13px;color:#777777;">
	Werkt de knop niet? Kopieer deze link naar uw browser:
</p>

<p style="margin:0 0 28px;font-size:13px;word-break:break-all;">
	<a
		href="<?php echo esc_url( $payment_url ); ?>"
		style="color:#6F8B57;text-decoration:underline;"
	>
		<?php echo esc_html( $payment_url ); ?>
	</a>
</p>

<p style="margin:0;">
	Met vriendelijke groet,<br>
	<strong>Team Sakynah</strong>
</p>