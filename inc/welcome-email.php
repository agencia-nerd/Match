<?php
/**
 * Correo de bienvenida para cuentas nuevas del Job Board.
 *
 * Lo mandan el registro por correo (match_handle_register(), con la
 * contraseña que eligió el candidato) y "Continuar con Google"
 * (match_google_callback()). Reemplaza al correo nativo de WordPress para
 * fijar la contraseña: ya no hace falta porque el candidato la crea al
 * registrarse.
 *
 * HTML con versión de texto (AltBody) para clientes que no muestran HTML.
 * La plantilla vive en template-parts/emails/welcome.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $method 'email' (entra con correo y contraseña) o 'google'.
 */
function match_send_welcome_email( int $user_id, string $method = 'email' ): bool {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	$name = match_user_first_name( $user );
	$args = array(
		'name'      => $name,
		'method'    => 'google' === $method ? 'google' : 'email',
		'email'     => $user->user_email,
		'board_url' => match_jobboard_url(),
		'login_url' => match_login_page_url() ?: wp_login_url(),
	);

	ob_start();
	get_template_part( 'template-parts/emails/welcome', null, $args );
	$html = (string) ob_get_clean();

	$text = implode(
		"\n\n",
		array(
			$name ? sprintf( /* translators: %s: nombre */ __( 'Hola, %s:', 'match' ), $name ) : __( 'Hola:', 'match' ),
			__( 'Tu cuenta en Match está lista. Desde el Job Board puedes ver vacantes exclusivas, guardarlas y postular en segundos con tu CV.', 'match' ),
			'google' === $args['method']
				? __( 'Para entrar, usa «Continuar con Google» con esta misma cuenta.', 'match' )
				: sprintf( /* translators: %s: correo */ __( 'Para entrar, usa tu correo (%s) y la contraseña que creaste.', 'match' ), $user->user_email ),
			__( 'Ir al Job Board:', 'match' ) . ' ' . $args['board_url'],
			__( '— El equipo de Match', 'match' ),
		)
	);

	$mail = apply_filters(
		'match/welcome_email',
		array(
			/* translators: %s: nombre */
			'subject' => $name ? sprintf( __( 'Bienvenido a Match, %s', 'match' ), $name ) : __( 'Bienvenido a Match', 'match' ),
			'html'    => $html,
			'text'    => $text,
		),
		$user,
		$args
	);

	// HTML + texto solo para este envío: los filtros se quitan al terminar.
	$content_type = static function () {
		return 'text/html';
	};
	$alt_body = static function ( $phpmailer ) use ( $mail ) {
		$phpmailer->AltBody = $mail['text']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- propiedad de PHPMailer.
	};

	add_filter( 'wp_mail_content_type', $content_type );
	add_action( 'phpmailer_init', $alt_body );
	$sent = wp_mail( $user->user_email, $mail['subject'], $mail['html'] );
	remove_filter( 'wp_mail_content_type', $content_type );
	remove_action( 'phpmailer_init', $alt_body );

	return $sent;
}
