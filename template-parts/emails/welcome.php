<?php
/**
 * Correo de bienvenida (HTML). Lo arma match_send_welcome_email()
 * (inc/welcome-email.php).
 *
 * Tablas y estilos en línea: los clientes de correo no leen hojas de estilo
 * ni variables CSS, así que los colores son los de tokens.css copiados en
 * hex. El logo es un PNG (match-logo-email.png, texto oscuro) porque Gmail y
 * Outlook no muestran SVG; si la imagen se bloquea, el alt y los estilos de
 * la celda mantienen la palabra «match».
 *
 * @var array $args { name, method: email|google, email, board_url, login_url }
 */

defined( 'ABSPATH' ) || exit;

$name   = $args['name'] ?? '';
$google = 'google' === ( $args['method'] ?? '' );

$font   = "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
$negro  = '#1c1c1c'; // --match-negro
$texto  = '#5e5e5e'; // --match-neutral-400
$suave  = '#8a899f'; // --match-azul-400
$fondo  = '#f5f5f5'; // --match-neutral-100
$borde  = '#e3e3e8'; // --match-azul-100
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
	<title><?php esc_html_e( 'Bienvenido a Match', 'match' ); ?></title>
</head>
<body style="margin:0;padding:0;background:<?php echo esc_attr( $fondo ); ?>;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo esc_attr( $fondo ); ?>;">
		<tr>
			<td align="center" style="padding:40px 16px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border:1px solid <?php echo esc_attr( $borde ); ?>;border-radius:24px;">
					<tr>
						<td style="padding:40px 40px 0;font-family:<?php echo esc_attr( $font ); ?>;font-size:28px;font-weight:700;letter-spacing:-0.5px;color:<?php echo esc_attr( $negro ); ?>;">
							<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/match-logo-email.png' ) ); ?>" width="140" height="26" alt="match" style="display:block;width:140px;height:auto;border:0;outline:none;text-decoration:none;">
						</td>
					</tr>
					<tr>
						<td style="padding:32px 40px 0;font-family:<?php echo esc_attr( $font ); ?>;">
							<h1 style="margin:0 0 16px;font-size:24px;font-weight:500;line-height:1.4;color:<?php echo esc_attr( $negro ); ?>;">
								<?php
								echo $name
									/* translators: %s: nombre */
									? esc_html( sprintf( __( '¡Bienvenido, %s!', 'match' ), $name ) )
									: esc_html__( '¡Bienvenido a Match!', 'match' );
								?>
							</h1>
							<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:<?php echo esc_attr( $texto ); ?>;">
								<?php esc_html_e( 'Tu cuenta está lista. Desde el Job Board puedes ver vacantes exclusivas gestionadas por Match, guardarlas y postular en segundos con tu CV.', 'match' ); ?>
							</p>
							<p style="margin:0;font-size:16px;line-height:1.6;color:<?php echo esc_attr( $texto ); ?>;">
								<?php
								if ( $google ) {
									esc_html_e( 'Para entrar, usa «Continuar con Google» con esta misma cuenta.', 'match' );
								} else {
									/* translators: %s: correo */
									printf( esc_html__( 'Para entrar, usa tu correo (%s) y la contraseña que creaste.', 'match' ), '<strong style="color:' . esc_attr( $negro ) . ';font-weight:500;">' . esc_html( $args['email'] ?? '' ) . '</strong>' );
								}
								?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding:32px 40px 0;">
							<table role="presentation" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="border-radius:12px;background:<?php echo esc_attr( $negro ); ?>;">
										<a href="<?php echo esc_url( $args['board_url'] ?? home_url( '/' ) ); ?>" style="display:inline-block;padding:14px 28px;font-family:<?php echo esc_attr( $font ); ?>;font-size:16px;font-weight:500;color:#ffffff;text-decoration:none;border-radius:12px;">
											<?php esc_html_e( 'Ir al Job Board', 'match' ); ?>
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:32px 40px 40px;font-family:<?php echo esc_attr( $font ); ?>;font-size:14px;line-height:1.6;color:<?php echo esc_attr( $suave ); ?>;">
							<?php if ( ! $google ) : ?>
								<?php
								/* translators: %s: enlace a «Iniciar sesión» */
								printf( esc_html__( '¿Olvidaste tu contraseña? Puedes crear una nueva desde %s.', 'match' ), '<a href="' . esc_url( $args['login_url'] ?? wp_login_url() ) . '" style="color:' . esc_attr( $negro ) . ';">' . esc_html__( 'Iniciar sesión', 'match' ) . '</a>' );
								?>
								<br><br>
							<?php endif; ?>
							<?php esc_html_e( '— El equipo de Match', 'match' ); ?>
						</td>
					</tr>
				</table>
				<p style="margin:24px 0 0;font-family:<?php echo esc_attr( $font ); ?>;font-size:12px;color:<?php echo esc_attr( $suave ); ?>;">
					<?php
					/* translators: %s: año */
					printf( esc_html__( '© %s Match. Recibes este correo porque creaste una cuenta en el Job Board.', 'match' ), esc_html( wp_date( 'Y' ) ) );
					?>
				</p>
			</td>
		</tr>
	</table>
</body>
</html>
