<?php
/**
 * Email Layout.
 *
 * Variables:
 * - string $content
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

$logo = home_url(
	'/wp-content/uploads/2025/12/Islamic-awareness-for-kids-1-e1766616324242-1.png'
);
?>

<!DOCTYPE html>
<html>

<head>

	<meta charset="UTF-8">

	<meta
		name="viewport"
		content="width=device-width, initial-scale=1.0"
	>

	<title><?php esc_html_e( 'Sakynah', 'formflow' ); ?></title>

</head>

<body
	style="
		margin:0;
		padding:0;
		background:#F5F3EE;
		font-family:Arial, Helvetica, sans-serif;
		color:#2F2F2F;
	"
>

	<table
		role="presentation"
		width="100%"
		cellpadding="0"
		cellspacing="0"
		border="0"
		style="padding:40px 20px;"
	>

		<tr>

			<td align="center">

				<table
					role="presentation"
					width="640"
					cellpadding="0"
					cellspacing="0"
					border="0"
					style="
						background:#ffffff;
						border-radius:14px;
						overflow:hidden;
						box-shadow:0 6px 24px rgba(0,0,0,.08);
					"
				>

					<tr>

						<td
							align="center"
							style="
								background:#F8F4EC;
								padding:36px;
								border-bottom:1px solid #ECE7DD;
							"
						>

							<img
								src="<?php echo esc_url( $logo ); ?>"
								alt="Sakynah"
								width="180"
								style="display:block;border:0;"
							/>

						</td>

					</tr>

					<tr>

						<td
							style="
								padding:48px;
								font-size:16px;
								line-height:1.8;
								color:#4B4B4B;
							"
						>

							<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						</td>

					</tr>

					<tr>

						<td
							align="center"
							style="
								padding:28px;
								background:#F8F4EC;
								border-top:1px solid #ECE7DD;
								font-size:13px;
								color:#777;
							"
						>

							<strong style="color:#6F8B57;">
								Sakynah
							</strong>

							<br><br>

							<?php esc_html_e(
								'Helping children build a strong Islamic foundation.',
								'formflow'
							); ?>

							<br><br>

							<a
								href="<?php echo esc_url( home_url() ); ?>"
								style="
									color:#6F8B57;
									text-decoration:none;
									font-weight:600;
								"
							>
								<?php echo esc_html( home_url() ); ?>
							</a>

						</td>

					</tr>

				</table>

			</td>

		</tr>

	</table>

</body>

</html>