<?php
/**
 * "Continuar con Google" (OAuth 2.0, Authorization Code) sin plugins.
 *
 * Si el correo de Google ya tiene cuenta, inicia sesión; si no, la crea
 * (match_insert_candidate()) y pasa al paso 2 del registro (subir CV).
 *
 * Credenciales en wp-config.php (nunca en el repo ni la BD):
 *   define( 'MATCH_GOOGLE_CLIENT_ID', '...' );
 *   define( 'MATCH_GOOGLE_CLIENT_SECRET', '...' );
 * URI de redirección a registrar en Google Cloud: match_google_redirect_uri().
 */

defined( 'ABSPATH' ) || exit;

function match_google_enabled(): bool {
	return defined( 'MATCH_GOOGLE_CLIENT_ID' ) && MATCH_GOOGLE_CLIENT_ID
		&& defined( 'MATCH_GOOGLE_CLIENT_SECRET' ) && MATCH_GOOGLE_CLIENT_SECRET;
}

function match_google_redirect_uri(): string {
	return admin_url( 'admin-post.php?action=match_google_callback' );
}

/**
 * URL del botón (filtro de match_google_login_url()). Vacía sin credenciales:
 * el botón sigue deshabilitado.
 */
function match_google_filter_login_url( string $url ): string {
	if ( ! match_google_enabled() ) {
		return $url;
	}
	$start    = admin_url( 'admin-post.php?action=match_google_start' );
	$redirect = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return $redirect ? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $start ) : $start;
}
add_filter( 'match/google_login_url', 'match_google_filter_login_url' );

/**
 * Vuelve al login del tema con el aviso indicado.
 */
function match_google_fail( string $code ): void {
	$page = match_login_page_url() ?: wp_login_url();
	wp_safe_redirect( add_query_arg( 'login', $code, $page ) );
	exit;
}

/**
 * Inicio: guarda el state (anti-CSRF) y manda a Google.
 */
function match_google_start(): void {
	if ( is_user_logged_in() ) {
		wp_safe_redirect( match_jobboard_url() );
		exit;
	}
	if ( ! match_google_enabled() ) {
		match_google_fail( 'google_failed' );
	}

	$state    = wp_generate_password( 32, false );
	$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	set_transient( 'match_g_state_' . $state, array( 'redirect_to' => $redirect ), 10 * MINUTE_IN_SECONDS );

	$auth = add_query_arg(
		array(
			'client_id'     => rawurlencode( MATCH_GOOGLE_CLIENT_ID ),
			'redirect_uri'  => rawurlencode( match_google_redirect_uri() ),
			'response_type' => 'code',
			'scope'         => rawurlencode( 'openid email profile' ),
			'state'         => $state,
			'prompt'        => 'select_account',
		),
		'https://accounts.google.com/o/oauth2/v2/auth'
	);

	wp_redirect( $auth ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- dominio externo (Google).
	exit;
}
add_action( 'admin_post_nopriv_match_google_start', 'match_google_start' );
add_action( 'admin_post_match_google_start', 'match_google_start' );

/**
 * Vuelta desde Google: valida state, canjea el code y abre sesión con el
 * correo (verificado), creando la cuenta si no existe.
 */
function match_google_callback(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- el state hace de nonce.
	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
	$data  = $state ? get_transient( 'match_g_state_' . $state ) : false;
	if ( $state ) {
		delete_transient( 'match_g_state_' . $state );
	}
	if ( ! is_array( $data ) || ! match_google_enabled() || ! empty( $_GET['error'] ) || empty( $_GET['code'] ) ) {
		match_google_fail( 'google_failed' );
	}
	$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
	// phpcs:enable

	$token = wp_remote_post(
		'https://oauth2.googleapis.com/token',
		array(
			'timeout' => 15,
			'body'    => array(
				'code'          => $code,
				'client_id'     => MATCH_GOOGLE_CLIENT_ID,
				'client_secret' => MATCH_GOOGLE_CLIENT_SECRET,
				'redirect_uri'  => match_google_redirect_uri(),
				'grant_type'    => 'authorization_code',
			),
		)
	);
	$token = is_wp_error( $token ) ? array() : json_decode( wp_remote_retrieve_body( $token ), true );
	if ( empty( $token['access_token'] ) ) {
		match_google_fail( 'google_failed' );
	}

	$info = wp_remote_get(
		'https://openidconnect.googleapis.com/v1/userinfo',
		array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => 'Bearer ' . $token['access_token'] ),
		)
	);
	$info = is_wp_error( $info ) ? array() : json_decode( wp_remote_retrieve_body( $info ), true );
	if ( empty( $info['sub'] ) || empty( $info['email'] ) || true !== ( $info['email_verified'] ?? false ) ) {
		match_google_fail( 'google_failed' );
	}

	$sub   = sanitize_text_field( $info['sub'] );
	$users = get_users(
		array(
			'meta_key'   => 'match_google_sub', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $sub, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		)
	);
	$email = sanitize_email( $info['email'] );
	$user  = $users ? $users[0] : get_user_by( 'email', $email );
	$new   = false;

	// Correo sin cuenta: se crea igual que en el registro por correo, pero con
	// contraseña aleatoria (entra con Google; si la quiere, "¿Olvidaste tu
	// contraseña?"). El nombre viene de Google.
	if ( ! $user instanceof WP_User ) {
		$user_id = match_insert_candidate(
			$email,
			array(
				'first_name' => sanitize_text_field( $info['given_name'] ?? '' ),
				'last_name'  => sanitize_text_field( $info['family_name'] ?? '' ),
			)
		);
		if ( is_wp_error( $user_id ) ) {
			match_google_fail( 'google_failed' );
		}
		$user = get_user_by( 'id', $user_id );
		$new  = true;
	}

	if ( ! get_user_meta( $user->ID, 'match_google_sub', true ) ) {
		update_user_meta( $user->ID, 'match_google_sub', $sub );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- hook nativo.

	// Cuenta nueva → bienvenida y paso 2 del registro (subir CV), como el registro por correo.
	if ( $new ) {
		match_send_welcome_email( $user->ID, 'google' );
		wp_safe_redirect( match_registro_cv_page_url() ?: match_jobboard_url() );
		exit;
	}

	// Mismo destino que el login por correo (match_login_redirect()).
	$requested = (string) $data['redirect_to'];
	$redirect  = apply_filters( 'login_redirect', $requested ?: admin_url(), $requested, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- hook nativo.
	wp_safe_redirect( $redirect ?: match_jobboard_url() );
	exit;
}
add_action( 'admin_post_nopriv_match_google_callback', 'match_google_callback' );
add_action( 'admin_post_match_google_callback', 'match_google_callback' );
