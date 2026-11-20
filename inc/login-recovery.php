<?php
/**
 * Recuperar contraseña, restablecerla y cerrar sesión dentro del diseño del
 * login del tema (template-login.php), en vez de las pantallas de wp-login.php.
 *
 * Vistas de la página de login (?vista=):
 *   recuperar   → pide el correo y envía el enlace.
 *   restablecer → nueva contraseña (llega desde el enlace del correo).
 *
 * @package Match
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vista activa de la página de login.
 */
function match_login_view(): string {
	$view = isset( $_GET['vista'] ) ? sanitize_key( $_GET['vista'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return in_array( $view, array( 'recuperar', 'restablecer' ), true ) ? $view : 'login';
}

function match_login_view_url( string $view, array $args = array() ): string {
	$page = match_login_page_url();
	if ( ! $page ) {
		return '';
	}
	return add_query_arg( array_merge( array( 'vista' => $view ), $args ), $page );
}

/**
 * wp_lostpassword_url() → vista "recuperar" del tema.
 */
function match_filter_lostpassword_url( string $url ): string {
	return match_login_view_url( 'recuperar' ) ?: $url;
}
add_filter( 'lostpassword_url', 'match_filter_lostpassword_url' );

/**
 * GET a las pantallas de wp-login.php → vistas del tema. El enlace del correo
 * (wp-login.php?action=rp&key=…&login=…) sigue funcionando.
 */
function match_redirect_recovery_screens(): void {
	if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! match_login_page_url() || match_is_interim_login() ) {
		return;
	}
	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		wp_safe_redirect( match_login_view_url( 'recuperar' ) );
		exit;
	}

	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$url   = $key && $login
			? match_login_view_url( 'restablecer', array( 'key' => rawurlencode( $key ), 'login' => rawurlencode( $login ) ) )
			: match_login_view_url( 'recuperar', array( 'reset' => 'invalid' ) );
		wp_safe_redirect( $url );
		exit;
	}

	if ( 'checkemail' === $action ) {
		wp_safe_redirect( match_login_view_url( 'recuperar', array( 'reset' => 'sent' ) ) );
		exit;
	}
}
add_action( 'login_init', 'match_redirect_recovery_screens' );

/**
 * Cerrar sesión sin la pantalla "Do you really want to log out?". Esa
 * confirmación sale cuando el nonce del enlace ya no vale (p. ej. tras cambiar
 * la contraseña se renuevan las sesiones). Cerrar sesión no es una acción
 * que un tercero pueda aprovechar, así que se hace directo.
 */
function match_direct_logout(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}
	wp_logout();

	$login    = match_login_page_url();
	$fallback = $login ? add_query_arg( 'loggedout', 'true', $login ) : home_url( '/' );
	$target   = isset( $_REQUEST['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ), $fallback ) : $fallback; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	wp_safe_redirect( $target );
	exit;
}
add_action( 'login_form_logout', 'match_direct_logout', 1 );

function match_recovery_back( string $view, string $reset, array $args = array() ): void {
	wp_safe_redirect( match_login_view_url( $view, array_merge( array( 'reset' => $reset ), $args ) ) ?: home_url( '/' ) );
	exit;
}

/**
 * Envío del correo con el enlace. Responde igual exista o no la cuenta, para
 * no revelar qué correos están registrados.
 */
function match_handle_lostpassword(): void {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'match_lostpassword' ) ) {
		match_recovery_back( 'recuperar', 'error', array( 'msg' => rawurlencode( __( 'La sesión expiró. Inténtalo de nuevo.', 'match' ) ) ) );
	}

	$login = isset( $_POST['user_login'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) ) : '';
	if ( '' === $login ) {
		match_recovery_back( 'recuperar', 'error', array( 'msg' => rawurlencode( __( 'Escribe tu correo electrónico.', 'match' ) ) ) );
	}

	retrieve_password( $login );
	match_recovery_back( 'recuperar', 'sent' );
}
add_action( 'admin_post_nopriv_match_lostpassword', 'match_handle_lostpassword' );
add_action( 'admin_post_match_lostpassword', 'match_handle_lostpassword' );

/**
 * Guardar la nueva contraseña.
 */
function match_handle_resetpassword(): void {
	$key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
	$keys  = array( 'key' => rawurlencode( $key ), 'login' => rawurlencode( $login ) );

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'match_resetpassword' ) ) {
		match_recovery_back( 'restablecer', 'error', $keys + array( 'msg' => rawurlencode( __( 'La sesión expiró. Inténtalo de nuevo.', 'match' ) ) ) );
	}

	$user = check_password_reset_key( $key, $login );
	if ( is_wp_error( $user ) ) {
		match_recovery_back( 'recuperar', 'invalid' );
	}

	$pass1 = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass2 = isset( $_POST['pass2'] ) ? (string) wp_unslash( $_POST['pass2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	if ( strlen( $pass1 ) < 8 ) {
		match_recovery_back( 'restablecer', 'error', $keys + array( 'msg' => rawurlencode( __( 'La contraseña debe tener al menos 8 caracteres.', 'match' ) ) ) );
	}
	if ( $pass1 !== $pass2 ) {
		match_recovery_back( 'restablecer', 'error', $keys + array( 'msg' => rawurlencode( __( 'Las contraseñas no coinciden.', 'match' ) ) ) );
	}

	reset_password( $user, $pass1 );
	wp_safe_redirect( add_query_arg( 'reset', 'done', match_login_page_url() ?: home_url( '/' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_match_resetpassword', 'match_handle_resetpassword' );
add_action( 'admin_post_match_resetpassword', 'match_handle_resetpassword' );

/**
 * Avisos de la recuperación (?reset=sent|done|invalid|error&msg=).
 *
 * @return array{type:string,text:string}|null
 */
function match_recovery_notice(): ?array {
	$reset = isset( $_GET['reset'] ) ? sanitize_key( $_GET['reset'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$msg   = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	switch ( $reset ) {
		case 'sent':
			return array( 'type' => 'ok', 'text' => __( 'Si el correo está registrado, te enviamos un enlace para cambiar tu contraseña. Revisa también tu carpeta de spam.', 'match' ) );
		case 'done':
			return array( 'type' => 'ok', 'text' => __( 'Contraseña actualizada. Ya puedes iniciar sesión.', 'match' ) );
		case 'invalid':
			return array( 'type' => 'error', 'text' => __( 'El enlace no es válido o ya venció. Pide uno nuevo.', 'match' ) );
		case 'error':
			return array( 'type' => 'error', 'text' => $msg ?: __( 'No pudimos completar la solicitud. Inténtalo de nuevo.', 'match' ) );
	}
	return null;
}
