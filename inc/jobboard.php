<?php
/**
 * Job Board: login y panel (Figma: Login 4308:3581, Inicio público 4529:3).
 *
 * Páginas (se crean desde el admin con estas plantillas):
 * - "Job Board"       → template-jobboard.php  (panel con barra lateral)
 * - "Iniciar sesión"  → template-login.php     (hija de Job Board)
 *
 * El login usa el flujo nativo: el formulario envía a wp-login.php y aquí se
 * redirige de vuelta con el error o al panel al entrar. wp_login_url() pasa
 * a devolver la página de login del tema, así el navbar y el plugin
 * apuntan al diseño en vez de a wp-login.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Página que usa una plantilla, o null.
 */
function match_page_with_template( string $template ): ?WP_Post {
	static $cache = array();

	if ( ! array_key_exists( $template, $cache ) ) {
		$pages              = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$cache[ $template ] = $pages ? $pages[0] : null;
	}

	return $cache[ $template ];
}

function match_jobboard_url(): string {
	$page = match_page_with_template( 'template-jobboard.php' );
	return $page ? (string) get_permalink( $page ) : match_jobs_url();
}

function match_login_page_url(): string {
	$page = match_page_with_template( 'template-login.php' );
	return $page ? (string) get_permalink( $page ) : '';
}

/**
 * Registro en dos pasos (Figma: Registro 4341:3554, Subir CV 4462:3).
 */
function match_registro_page_url(): string {
	return match_jobboard_page_url( 'template-registro.php' );
}

function match_registro_cv_page_url(): string {
	return match_jobboard_page_url( 'template-registro-cv.php' );
}

/**
 * wp_login_url() → página de login del tema (con redirect_to).
 */
function match_filter_login_url( string $login_url, string $redirect ): string {
	$page = match_login_page_url();

	if ( ! $page ) {
		return $login_url;
	}

	return $redirect ? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $page ) : $page;
}
add_filter( 'login_url', 'match_filter_login_url', 10, 2 );

/**
 * Login interino: el modal de sesión caducada del admin (wp-auth-check) carga
 * wp-login.php?interim-login=1 en un iframe y solo se cierra si el flujo
 * nativo termina en su pantalla de éxito. Ahí no se redirige al tema.
 */
function match_is_interim_login(): bool {
	return ! empty( $_REQUEST['interim-login'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * wp-login.php sin acción (GET) → página de login del tema. Las acciones
 * (lostpassword, register, logout, postpass…) siguen en wp-login.php.
 */
function match_redirect_wp_login(): void {
	$page = match_login_page_url();
	if ( ! $page || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || match_is_interim_login() ) {
		return;
	}
	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' !== $action && 'login' !== $action ) {
		return;
	}
	$args = array();
	foreach ( array( 'redirect_to', 'loggedout', 'login' ) as $key ) {
		if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args[ $key ] = rawurlencode( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}
	wp_safe_redirect( add_query_arg( $args, $page ) );
	exit;
}
add_action( 'login_init', 'match_redirect_wp_login' );

/**
 * Credenciales incorrectas → de vuelta al login del tema con el aviso.
 */
function match_login_failed(): void {
	$page = match_login_page_url();
	if ( ! $page || match_is_interim_login() ) {
		return;
	}
	$redirect = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	wp_safe_redirect( add_query_arg( array_filter( array( 'login' => 'failed', 'redirect_to' => $redirect ? rawurlencode( $redirect ) : '' ) ), $page ) );
	exit;
}
add_action( 'wp_login_failed', 'match_login_failed' );

/**
 * Tras entrar, al panel (salvo redirect_to explícito). Los administradores
 * siguen yendo al escritorio.
 */
function match_login_redirect( string $redirect_to, string $requested, $user ): string {
	if ( $user instanceof WP_User && user_can( $user, 'manage_options' ) ) {
		return $redirect_to;
	}
	return $requested && admin_url() !== $requested ? $redirect_to : match_jobboard_url();
}
add_filter( 'login_redirect', 'match_login_redirect', 10, 3 );

/**
 * Usuario ya identificado en el login → al panel.
 */
function match_login_page_guard(): void {
	if ( ! is_page_template( 'template-login.php' ) ) {
		return;
	}
	// Quien ya tiene sesión también puede abrir el enlace de cambio de contraseña.
	if ( is_user_logged_in() && 'login' === match_login_view() ) {
		wp_safe_redirect( match_jobboard_url() );
		exit;
	}
	// wp-login.php rechaza el envío si no existe la cookie de prueba, que
	// normalmente pone él mismo al mostrar su formulario.
	if ( ! headers_sent() ) {
		setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		if ( SITECOOKIEPATH !== COOKIEPATH ) {
			setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}
}
add_action( 'template_redirect', 'match_login_page_guard' );

/**
 * Aviso del login según la URL (?login=failed, ?loggedout=true, ?cuenta=eliminada,
 * ?registered=1).
 *
 * @return array{type:string,text:string}|null
 */
function match_login_notice(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) {
		return array( 'type' => 'error', 'text' => __( 'Correo o contraseña incorrectos. Inténtalo de nuevo.', 'match' ) );
	}
	if ( isset( $_GET['login'] ) && 'google_failed' === $_GET['login'] ) {
		return array( 'type' => 'error', 'text' => __( 'No pudimos iniciar sesión con Google. Inténtalo de nuevo.', 'match' ) );
	}
	if ( ! empty( $_GET['loggedout'] ) ) {
		return array( 'type' => 'ok', 'text' => __( 'Cerraste sesión correctamente.', 'match' ) );
	}
	if ( isset( $_GET['cuenta'] ) && 'eliminada' === $_GET['cuenta'] ) {
		return array( 'type' => 'ok', 'text' => __( 'Tu cuenta fue eliminada.', 'match' ) );
	}
	if ( ! empty( $_GET['registered'] ) ) {
		return array( 'type' => 'ok', 'text' => __( 'Cuenta creada. Ya puedes iniciar sesión.', 'match' ) );
	}
	// phpcs:enable
	return null;
}

/**
 * Aviso del paso 1 de registro según ?registro=error&msg=.
 *
 * @return array{type:string,text:string}|null
 */
function match_registro_notice(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( 'error' !== ( $_GET['registro'] ?? '' ) ) {
		return null;
	}
	$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
	// phpcs:enable
	return array( 'type' => 'error', 'text' => $msg ?: __( 'No pudimos crear tu cuenta. Inténtalo de nuevo.', 'match' ) );
}

/**
 * Aviso del paso 2 de registro (subir CV) según ?paso2=error&msg=.
 *
 * @return array{type:string,text:string}|null
 */
function match_registro_cv_notice(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( 'error' !== ( $_GET['paso2'] ?? '' ) ) {
		return null;
	}
	$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
	// phpcs:enable
	return array( 'type' => 'error', 'text' => $msg ?: __( 'No pudimos subir tu CV. Inténtalo de nuevo.', 'match' ) );
}

/**
 * Crea un candidato (rol subscriber) con usuario derivado del correo. Lo usan
 * el registro por correo (con la contraseña que elige el candidato) y
 * "Continuar con Google" (inc/google-auth.php), que no pasa contraseña: se
 * genera una aleatoria que nunca se expone.
 *
 * @param array $extra Campos extra para wp_insert_user() (first_name, last_name, user_pass…).
 * @return int|WP_Error
 */
function match_insert_candidate( string $email, array $extra = array() ) {
	$username = sanitize_user( current( explode( '@', $email ) ), true ) ?: 'usuario';
	$base     = $username;
	$i        = 1;
	while ( username_exists( $username ) ) {
		$username = $base . ( ++$i );
	}

	$full = trim( ( $extra['first_name'] ?? '' ) . ' ' . ( $extra['last_name'] ?? '' ) );

	$user_id = wp_insert_user(
		array_merge(
			array(
				'user_pass'    => wp_generate_password( 24, true, true ),
				'display_name' => $full,
			),
			array_filter( $extra, 'strlen' ),
			array(
				'user_login' => $username,
				'user_email' => $email,
				'role'       => 'subscriber',
			)
		)
	);

	// Prueba de usabilidad: tras el registro no hay pantalla de bienvenida; se
	// pasa directo a «Sube tu CV». Se marca como vista para que el modal de
	// onboarding del panel no aparezca después (?onboarding=1 lo sigue forzando).
	if ( ! is_wp_error( $user_id ) ) {
		update_user_meta( $user_id, 'match_onboarding_done', current_time( 'mysql' ) );
	}

	return $user_id;
}

/**
 * Vuelve al paso 1 con el error y lo ya escrito (nunca la contraseña), para
 * que template-registro.php vuelva a llenar los campos.
 *
 * @param array{first?:string,last?:string,email?:string} $fields
 */
function match_registro_back( string $message = '', array $fields = array() ): void {
	$args = array( 'registro' => 'error', 'msg' => $message ? rawurlencode( $message ) : '' );
	foreach ( $fields as $key => $value ) {
		$args[ 'r_' . $key ] = rawurlencode( $value );
	}
	wp_safe_redirect( add_query_arg( array_filter( $args ), match_registro_page_url() ?: home_url( '/' ) ) );
	exit;
}

/**
 * Lo escrito en el paso 1 antes de un error (?r_first=&r_last=&r_email=).
 *
 * @return array{first:string,last:string,email:string}
 */
function match_registro_old(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	return array(
		'first' => isset( $_GET['r_first'] ) ? sanitize_text_field( wp_unslash( $_GET['r_first'] ) ) : '',
		'last'  => isset( $_GET['r_last'] ) ? sanitize_text_field( wp_unslash( $_GET['r_last'] ) ) : '',
		'email' => isset( $_GET['r_email'] ) ? sanitize_email( wp_unslash( $_GET['r_email'] ) ) : '',
	);
	// phpcs:enable
}

/**
 * Paso 1: crea la cuenta con nombre, apellido, correo y la contraseña que
 * elige el candidato (el CRM exige applicant.full_name al postular, así que
 * el nombre es obligatorio). Manda el correo de bienvenida y loguea
 * automáticamente para que el paso 2 (subir CV) no pida iniciar sesión.
 */
function match_handle_register(): void {
	if ( ! isset( $_POST['_match_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_match_nonce'] ), 'match_register' ) || ! empty( $_POST['match_web'] ) ) {
		match_registro_back();
	}

	$first = match_clean_person_name( $_POST['first_name'] ?? '' );
	$last  = match_clean_person_name( $_POST['last_name'] ?? '' );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$pass  = isset( $_POST['pass'] ) ? (string) wp_unslash( $_POST['pass'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- contraseña: no se altera.
	$old   = array( 'first' => $first, 'last' => $last, 'email' => $email );

	if ( '' === $first ) {
		match_registro_back( __( 'Escribe tu nombre.', 'match' ), $old );
	}
	if ( ! match_valid_person_name( $first ) || ! match_valid_person_name( $last ) ) {
		match_registro_back( match_person_name_error(), $old );
	}
	if ( ! is_email( $email ) ) {
		match_registro_back( __( 'Escribe un correo válido.', 'match' ), $old );
	}
	if ( email_exists( $email ) ) {
		match_registro_back( __( 'Ese correo ya tiene una cuenta. Inicia sesión.', 'match' ), $old );
	}
	// Mismo mínimo que la pantalla de nueva contraseña (match_handle_resetpassword()).
	if ( strlen( $pass ) < 8 ) {
		match_registro_back( __( 'La contraseña debe tener al menos 8 caracteres.', 'match' ), $old );
	}

	$user_id = match_insert_candidate(
		$email,
		array(
			'first_name' => $first,
			'last_name'  => $last,
			'user_pass'  => $pass,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		match_registro_back( $user_id->get_error_message(), $old );
	}

	match_send_welcome_email( $user_id, 'email' );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	wp_safe_redirect( match_registro_cv_page_url() ?: match_jobboard_url() );
	exit;
}
add_action( 'admin_post_match_register', 'match_handle_register' );
add_action( 'admin_post_nopriv_match_register', 'match_handle_register' );

/**
 * Paso 2: sube el CV con el mismo mecanismo que el perfil (MJB_CV::store(),
 * mismos metas mjb_cv_*, ver match_profile_cv()), pero termina en el panel
 * en vez de en Perfil. No hace falta borrar un CV anterior: la cuenta recién
 * se creó en el paso 1.
 */
function match_handle_register_cv(): void {
	$back = match_registro_cv_page_url() ?: match_jobboard_url();

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( $back ) );
		exit;
	}
	if ( ! isset( $_POST['_match_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_match_nonce'] ), 'match_register_cv' ) ) {
		wp_safe_redirect( add_query_arg( 'paso2', 'error', $back ) );
		exit;
	}
	if ( ! match_job_board_active() ) {
		wp_safe_redirect( add_query_arg( array( 'paso2' => 'error', 'msg' => rawurlencode( __( 'El Job Board no está activo.', 'match' ) ) ), $back ) );
		exit;
	}
	if ( empty( $_FILES['cv']['name'] ) ) {
		wp_safe_redirect( add_query_arg( array( 'paso2' => 'error', 'msg' => rawurlencode( __( 'Elige un archivo.', 'match' ) ) ), $back ) );
		exit;
	}

	$stored = MJB_CV::store( $_FILES['cv'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( is_wp_error( $stored ) ) {
		wp_safe_redirect( add_query_arg( array( 'paso2' => 'error', 'msg' => rawurlencode( $stored->get_error_message() ) ), $back ) );
		exit;
	}

	$user = wp_get_current_user();
	update_user_meta( $user->ID, 'mjb_cv_file', $stored );
	update_user_meta( $user->ID, 'mjb_cv_name', sanitize_file_name( wp_unslash( $_FILES['cv']['name'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	update_user_meta( $user->ID, 'mjb_cv_date', current_time( 'mysql' ) );

	// Si el registro empezó desde «Postular ahora» (J12), se vuelve a esa vacante con el modal abierto.
	$return = isset( $_COOKIE['match_apply_return'] ) ? wp_validate_redirect( esc_url_raw( rawurldecode( wp_unslash( $_COOKIE['match_apply_return'] ) ) ), '' ) : '';
	if ( $return ) {
		setcookie( 'match_apply_return', '', time() - HOUR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN );
		wp_safe_redirect( $return );
		exit;
	}

	wp_safe_redirect( match_jobboard_url() );
	exit;
}
add_action( 'admin_post_match_register_cv', 'match_handle_register_cv' );

/**
 * URL de "Continuar con Google". Vacía hasta que se conecte un proveedor
 * OAuth (filtro match/google_login_url).
 */
function match_google_login_url(): string {
	return (string) apply_filters( 'match/google_login_url', '' );
}

/**
 * Vacantes destacadas del panel: las que el CRM marca como destacadas y, si
 * no alcanzan, las más recientes (MJB_Query::featured_ids). En modo demo,
 * las primeras de ejemplo.
 */
function match_jobboard_featured( int $count = 3 ): array {
	if ( ! match_job_board_active() || match_jobs_demo_mode() ) {
		return match_jobs_list( array(), $count )['jobs'];
	}

	return array_map( 'match_job_data', MJB_Query::featured_ids( $count ) );
}

/**
 * Áreas del panel: vacantes agrupadas por industria (facetas), hasta $areas
 * industrias con $per vacantes cada una.
 *
 * @return array<int, array{name:string,slug:string,jobs:array}>
 */
function match_jobboard_areas( int $areas = 3, int $per = 3 ): array {
	$facets = match_jobs_facets( array() )['industria'] ?? array();
	$out    = array();

	foreach ( array_slice( $facets, 0, $areas ) as $facet ) {
		$jobs = match_jobs_list( array( 'industria' => $facet['slug'] ), $per )['jobs'];
		if ( $jobs ) {
			$out[] = array( 'name' => $facet['name'], 'slug' => $facet['slug'], 'jobs' => $jobs );
		}
	}

	return $out;
}

/**
 * Oculta la barra de administración de WordPress en el frontend.
 *
 * TEMPORAL: por ahora se oculta para todos los usuarios. Cuando se quiera
 * restaurar para editores, volver a: current_user_can( 'edit_posts' ) ? $show : false
 */
function match_hide_admin_bar( bool $show ): bool {
	return false;
}
add_filter( 'show_admin_bar', 'match_hide_admin_bar' );

/**
 * Onboarding del panel (Figma: Job-Board/Onboarding — Bienvenida, 4409:3).
 * Se muestra una vez a cada usuario; al cerrarlo, app.js llama a
 * match_onboarding_done y queda en user meta. ?onboarding=1 lo fuerza
 * para revisarlo.
 */
function match_jobboard_show_onboarding(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['onboarding'] ) ) {
		return true;
	}
	return ! get_user_meta( get_current_user_id(), 'match_onboarding_done', true );
}

function match_onboarding_done(): void {
	check_ajax_referer( 'match_onboarding', 'nonce' );
	update_user_meta( get_current_user_id(), 'match_onboarding_done', current_time( 'mysql' ) );
	wp_send_json_success();
}
add_action( 'wp_ajax_match_onboarding_done', 'match_onboarding_done' );

/**
 * URL del perfil: la página con la plantilla "Job Board — Perfil" o, si no
 * existe, el perfil nativo de WordPress (filtro match/profile_url).
 */
function match_profile_url(): string {
	return (string) apply_filters( 'match/profile_url', match_jobboard_page_url( 'template-profile.php' ) ?: get_edit_profile_url() );
}

/**
 * URL de "Cerrar sesión": vuelve al login del tema (con el aviso de
 * ?loggedout=true) o, si no existe esa página, al inicio del sitio.
 * Única fuente de esta URL: la usan la barra lateral y el menú de cuenta
 * de la topbar.
 */
function match_logout_url(): string {
	$login = match_login_page_url();
	return wp_logout_url( $login ? add_query_arg( 'loggedout', 'true', $login ) : home_url( '/' ) );
}

/**
 * Clases de <body> para las plantillas del Job Board.
 */
function match_jobboard_body_class( array $classes ): array {
	if ( match_is_jobboard_view() ) {
		$classes[] = 'match-jobboard';
	}
	if ( is_page_template( 'template-login.php' ) ) {
		$classes[] = 'match-login';
	}
	return $classes;
}
add_filter( 'body_class', 'match_jobboard_body_class' );

/* ======================================================================
 * Sesión iniciada: perfil, CV, guardados y procesos
 * (Figma: Inicio 4125:25, Perfil 4216:3)
 * ====================================================================== */

/**
 * URL de una página del Job Board por plantilla, o '' si no existe.
 */
function match_jobboard_page_url( string $template ): string {
	$page = match_page_with_template( $template );
	return $page ? (string) get_permalink( $page ) : '';
}

/**
 * El perfil exige sesión: sin ella, al login con vuelta.
 */
function match_profile_guard(): void {
	if ( is_page_template( 'template-profile.php' ) && ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( (string) get_permalink() ) );
		exit;
	}
}
add_action( 'template_redirect', 'match_profile_guard' );

/**
 * Nombre corto para el saludo. Vacío si el candidato aún no tiene nombre: el
 * registro por correo solo pide el correo, y WordPress rellena display_name
 * con user_login (la parte del correo antes de la @), que no es un nombre.
 */
function match_user_first_name( ?WP_User $user = null ): string {
	$user = $user ?: wp_get_current_user();
	if ( $user->first_name ) {
		return $user->first_name;
	}
	$display = trim( $user->display_name );
	if ( '' === $display || $display === $user->user_login || is_email( $display ) ) {
		return '';
	}
	return explode( ' ', $display )[0];
}

/**
 * Postulación: nombre, apellidos y correo salen del perfil, no del formulario.
 *
 * El modal (match-job-board/apply-form.php) solo pide teléfono, CV y carta.
 * Antes de que el plugin procese el envío (prioridad 10), se rellenan esos
 * campos desde la cuenta; se sobrescribe lo que venga en el POST para que el
 * perfil sea la única fuente. El teléfono se guarda para precargarlo la
 * próxima vez.
 */
function match_apply_fill_from_profile(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user    = wp_get_current_user();
	$job_url = get_permalink( absint( $_POST['job_id'] ?? 0 ) ) ?: match_jobboard_url();

	// Cuentas creadas antes de pedir el nombre en el registro: el modal lo
	// pide (apply-form.php) y aquí se guarda en el perfil. Antes se mandaba
	// user_login (la parte del correo) y el CRM recibía ese texto como nombre.
	if ( '' === match_user_first_name( $user ) ) {
		$first = match_clean_person_name( $_POST['first_name'] ?? '' );
		$last  = match_clean_person_name( $_POST['last_name'] ?? '' );
		if ( '' === $first || ! match_valid_person_name( $first ) || ! match_valid_person_name( $last ) ) {
			wp_safe_redirect( add_query_arg( 'mjb_error', rawurlencode( '' === $first ? __( 'Escribe tu nombre.', 'match' ) : match_person_name_error() ), $job_url ) );
			exit;
		}
		wp_update_user(
			array(
				'ID'           => $user->ID,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => trim( $first . ' ' . $last ),
			)
		);
		$user = get_userdata( $user->ID );
	}

	$_POST['first_name'] = match_user_first_name( $user );
	$_POST['last_name']  = $user->last_name;
	$_POST['email']      = $user->user_email;

	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	if ( '' !== $phone ) {
		// Solo números, espacios y «+», de 9 a 15 dígitos (J07). El navegador ya lo avisa; esto cubre el envío sin JavaScript.
		$digits = preg_match_all( '/\d/', $phone );
		if ( ! preg_match( '/^[\d\s+]+$/', $phone ) || $digits < 9 || $digits > 15 ) {
			wp_safe_redirect( add_query_arg( 'mjb_error', rawurlencode( __( 'El teléfono no es válido: usa solo números, espacios y +, de 9 a 15 dígitos.', 'match' ) ), $job_url ) );
			exit;
		}
		update_user_meta( $user->ID, 'mjb_phone', $phone );
	}

	// Carta (tope de 2000 en el API): el maxlength del navegador cuenta cada
	// salto de línea como 1, pero lo envía como \r\n. Sin normalizar, una carta
	// que llega justo a 2000 en pantalla supera los 2000 aquí y el plugin la
	// recorta en silencio (mb_substr), así que se pierde el final.
	if ( isset( $_POST['cover_letter'] ) ) {
		$_POST['cover_letter'] = str_replace( array( "\r\n", "\r" ), "\n", (string) $_POST['cover_letter'] );
		if ( ! match_valid_message( (string) wp_unslash( $_POST['cover_letter'] ) ) ) {
			wp_safe_redirect( add_query_arg( 'mjb_error', rawurlencode( __( 'La carta solo puede tener letras, números y puntuación común.', 'match' ) ), $job_url ) );
			exit;
		}
	}
}
add_action( 'admin_post_mjb_apply', 'match_apply_fill_from_profile', 5 );

/**
 * La spec 1.1.195 del webhook marca `applicant.full_name` como obligatorio
 * (sin él responde 400); el plugin solo manda first_name y last_name.
 */
function match_apply_payload_full_name( array $payload ): array {
	if ( empty( $payload['applicant']['full_name'] ) ) {
		$payload['applicant']['full_name'] = trim( ( $payload['applicant']['first_name'] ?? '' ) . ' ' . ( $payload['applicant']['last_name'] ?? '' ) );
	}
	return $payload;
}
add_filter( 'mjb/application_payload', 'match_apply_payload_full_name' );

/**
 * CV guardado en el perfil (lo almacena el plugin en su carpeta protegida).
 *
 * @return array{file:string,name:string,ext:string,date:string,size:string,url:string}|null
 */
function match_user_cv( int $user_id = 0 ): ?array {
	$user_id = $user_id ?: get_current_user_id();

	if ( ! $user_id || ! match_job_board_active() ) {
		return null;
	}

	return match_cv_info(
		(string) get_user_meta( $user_id, 'mjb_cv_file', true ),
		(string) get_user_meta( $user_id, 'mjb_cv_name', true ),
		(string) get_user_meta( $user_id, 'mjb_cv_date', true )
	);
}

/**
 * Datos de un CV de la carpeta protegida del plugin para cv-row.php: el del
 * perfil (match_user_cv) o el que viajó con una postulación.
 *
 * @return array{file:string,name:string,ext:string,date:string,size:string,url:string}|null
 */
function match_cv_info( string $file, string $name = '', string $date = '' ): ?array {
	if ( '' === $file || ! match_job_board_active() || ! MJB_CV::exists( $file ) ) {
		return null;
	}

	$name = $name ?: $file;

	return array(
		'file' => $file,
		'name' => $name,
		'ext'  => strtoupper( pathinfo( $name, PATHINFO_EXTENSION ) ?: pathinfo( $file, PATHINFO_EXTENSION ) ),
		'date' => $date ? date_i18n( 'j M, Y', strtotime( $date ) ) : '',
		'size' => size_format( (int) filesize( MJB_CV::path( $file ) ), 1 ),
		'url'  => MJB_CV::signed_url( $file, 600 ),
	);
}

/**
 * Vuelve al perfil con el estado en la URL.
 */
function match_profile_redirect( string $status, string $message = '' ): void {
	wp_safe_redirect( add_query_arg( array_filter( array( 'perfil' => $status, 'msg' => $message ? rawurlencode( $message ) : '' ) ), match_profile_url() ) );
	exit;
}

/**
 * Aviso del perfil según ?perfil=.
 *
 * @return array{type:string,text:string}|null
 */
function match_profile_notice(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$status = isset( $_GET['perfil'] ) ? sanitize_key( $_GET['perfil'] ) : '';
	$custom = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
	// phpcs:enable
	$texts = array(
		'saved' => __( 'Datos guardados.', 'match' ),
		'cv'    => __( 'CV actualizado.', 'match' ),
		'reset' => __( 'Te enviamos el enlace para crear una nueva contraseña.', 'match' ),
		'error' => $custom ?: __( 'No pudimos guardar los cambios. Inténtalo de nuevo.', 'match' ),
	);

	if ( ! isset( $texts[ $status ] ) ) {
		return null;
	}

	return array( 'type' => 'error' === $status ? 'error' : 'ok', 'text' => $texts[ $status ] );
}

/**
 * Comprobación común de los formularios del perfil.
 */
function match_profile_check( string $action ): WP_User {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( match_profile_url() ) );
		exit;
	}
	if ( ! isset( $_POST['_match_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_match_nonce'] ), $action ) ) {
		match_profile_redirect( 'error', __( 'La sesión expiró. Vuelve a intentarlo.', 'match' ) );
	}
	return wp_get_current_user();
}

/**
 * Nombre o apellido tal como llega del formulario: sin espacios de más.
 *
 * @param mixed $raw Valor sin tratar de $_POST.
 */
function match_clean_person_name( $raw ): string {
	return (string) preg_replace( '/\s+/u', ' ', trim( sanitize_text_field( wp_unslash( (string) $raw ) ) ) );
}

/**
 * Solo letras; espacios, apóstrofo y guion únicamente entre letras; máx. 50.
 * Vacío cuenta como válido: cada formulario decide si el campo es obligatorio.
 * Misma regla que el `pattern` de los inputs con data-name-input.
 */
function match_valid_person_name( string $name ): bool {
	return '' === $name || ( mb_strlen( $name ) <= 50 && (bool) preg_match( "/^\p{L}+(?:[ '\-]\p{L}+)*$/u", $name ) );
}

function match_person_name_error(): string {
	return __( 'El nombre y el apellido solo pueden tener letras, espacios, apóstrofo o guion.', 'match' );
}

/**
 * Mensajes y carta de presentación: letras, números, espacios, saltos de línea
 * y puntuación común; sin emojis ni símbolos. Máx. 2000 (tope del CRM).
 * Misma regla que los textarea con data-text-input (assets/js/app.js).
 */
function match_valid_message( string $text ): bool {
	return mb_strlen( $text ) <= 2000 && (bool) preg_match( '/^[\p{L}\p{M}\p{N}\s.,;:¿?¡!\'"“”‘’()\-–—\/@%&+#$€°ºª_]*$/u', $text );
}

/**
 * Información de cuenta: correo, nombre y apellido.
 */
function match_profile_save(): void {
	$user  = match_profile_check( 'match_profile_save' );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$first = match_clean_person_name( $_POST['first_name'] ?? '' );
	$last  = match_clean_person_name( $_POST['last_name'] ?? '' );

	if ( ! match_valid_person_name( $first ) || ! match_valid_person_name( $last ) ) {
		match_profile_redirect( 'error', match_person_name_error() );
	}

	if ( ! is_email( $email ) ) {
		match_profile_redirect( 'error', __( 'Escribe un correo válido.', 'match' ) );
	}
	$owner = email_exists( $email );
	if ( $owner && (int) $owner !== $user->ID ) {
		match_profile_redirect( 'error', __( 'Ese correo ya está en uso.', 'match' ) );
	}

	$result = wp_update_user(
		array(
			'ID'           => $user->ID,
			'user_email'   => $email,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => trim( $first . ' ' . $last ) ?: $user->display_name,
		)
	);

	match_profile_redirect( is_wp_error( $result ) ? 'error' : 'saved' );
}
add_action( 'admin_post_match_profile_save', 'match_profile_save' );

/**
 * Subir o reemplazar el CV. Se guarda con el plugin (carpeta protegida,
 * nombre opaco) y en los mismos metas que usa el formulario de postulación.
 */
function match_profile_cv(): void {
	$user = match_profile_check( 'match_profile_cv' );

	if ( ! match_job_board_active() ) {
		match_profile_redirect( 'error', __( 'El Job Board no está activo.', 'match' ) );
	}
	if ( empty( $_FILES['cv']['name'] ) ) {
		match_profile_redirect( 'error', __( 'Elige un archivo.', 'match' ) );
	}

	$stored = MJB_CV::store( $_FILES['cv'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( is_wp_error( $stored ) ) {
		match_profile_redirect( 'error', $stored->get_error_message() );
	}

	$previous = (string) get_user_meta( $user->ID, 'mjb_cv_file', true );
	if ( $previous && $previous !== $stored ) {
		MJB_CV::delete( $previous );
	}

	update_user_meta( $user->ID, 'mjb_cv_file', $stored );
	update_user_meta( $user->ID, 'mjb_cv_name', sanitize_file_name( wp_unslash( $_FILES['cv']['name'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	update_user_meta( $user->ID, 'mjb_cv_date', current_time( 'mysql' ) );

	match_profile_redirect( 'cv' );
}
add_action( 'admin_post_match_profile_cv', 'match_profile_cv' );

/**
 * Enviar el enlace de cambio de contraseña al correo de la cuenta.
 */
function match_profile_reset(): void {
	$user   = match_profile_check( 'match_profile_reset' );
	$result = retrieve_password( $user->user_login );

	match_profile_redirect( is_wp_error( $result ) ? 'error' : 'reset', is_wp_error( $result ) ? __( 'No pudimos enviar el correo. Inténtalo más tarde.', 'match' ) : '' );
}
add_action( 'admin_post_match_profile_reset', 'match_profile_reset' );

/**
 * Solo los candidatos pueden eliminar su cuenta desde el perfil: el registro
 * del tema (match_insert_candidate) los crea como subscriber. Cualquier otro
 * rol, o un subscriber con un rol adicional, queda fuera.
 */
function match_user_can_self_delete( ?WP_User $user = null ): bool {
	$user = $user ?: wp_get_current_user();
	return $user->exists() && array( 'subscriber' ) === array_values( $user->roles ) && ! is_super_admin( $user->ID );
}

/**
 * Eliminar la cuenta: borra el CV de la carpeta protegida y el usuario con
 * todos sus metas. Las postulaciones se conservan como historial: pasan al
 * primer administrador. Lo ya enviado al CRM no se toca.
 */
function match_profile_delete(): void {
	$user = match_profile_check( 'match_profile_delete' );

	if ( ! match_user_can_self_delete( $user ) ) {
		match_profile_redirect( 'error', __( 'Esta cuenta no se puede eliminar desde aquí.', 'match' ) );
	}
	if ( 'ELIMINAR' !== trim( sanitize_text_field( wp_unslash( $_POST['confirm'] ?? '' ) ) ) ) {
		match_profile_redirect( 'error', __( 'Escribe ELIMINAR para confirmar.', 'match' ) );
	}

	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID' ) );
	if ( ! $admins ) {
		match_profile_redirect( 'error', __( 'No pudimos eliminar tu cuenta. Inténtalo más tarde.', 'match' ) );
	}

	$cv = (string) get_user_meta( $user->ID, 'mjb_cv_file', true );
	if ( $cv && match_job_board_active() ) {
		MJB_CV::delete( $cv );
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_logout();
	wp_delete_user( $user->ID, (int) $admins[0] );

	$login = match_login_page_url();
	wp_safe_redirect( $login ? add_query_arg( 'cuenta', 'eliminada', $login ) : home_url( '/' ) );
	exit;
}
add_action( 'admin_post_match_profile_delete', 'match_profile_delete' );

/**
 * Meta de usuario como arreglo. get_user_meta devuelve '' cuando no existe
 * y (array) '' da [''], que se colaría como un id 0.
 */
function match_user_meta_array( int $user_id, string $key ): array {
	$value = get_user_meta( $user_id, $key, true );
	return is_array( $value ) ? $value : array();
}

/**
 * Guardados del usuario: id → marca de tiempo. Los reales viven en el meta
 * del plugin (mjb_saved_jobs, solo ids) y las fechas en match_saved_dates;
 * los de ejemplo ("demo-N") en match_saved_demo para poder revisar la
 * pantalla sin CRM.
 *
 * @return array<string,int>
 */
function match_saved_map( int $user_id = 0 ): array {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	$dates = match_user_meta_array( $user_id, 'match_saved_dates' );
	$map   = array();

	if ( match_job_board_active() ) {
		foreach ( MJB_Query::saved_jobs( $user_id ) as $id ) {
			if ( $id > 0 ) {
				$map[ (string) $id ] = (int) ( $dates[ $id ] ?? 0 );
			}
		}
	}
	foreach ( match_user_meta_array( $user_id, 'match_saved_demo' ) as $key => $ts ) {
		if ( 0 === strpos( (string) $key, 'demo-' ) ) {
			$map[ (string) $key ] = (int) $ts;
		}
	}

	return $map;
}

/**
 * Alterna un guardado. Devuelve el estado final.
 */
function match_toggle_saved( string $id, int $user_id = 0 ): bool {
	$user_id = $user_id ?: get_current_user_id();

	if ( 0 === strpos( $id, 'demo-' ) ) {
		$demo = match_user_meta_array( $user_id, 'match_saved_demo' );
		if ( isset( $demo[ $id ] ) ) {
			unset( $demo[ $id ] );
			$saved = false;
		} else {
			$demo[ $id ] = time();
			$saved       = true;
		}
		update_user_meta( $user_id, 'match_saved_demo', $demo );
		return $saved;
	}

	$job_id = (int) $id;
	$ids    = match_job_board_active() ? MJB_Query::saved_jobs( $user_id ) : array();
	$dates  = match_user_meta_array( $user_id, 'match_saved_dates' );

	if ( in_array( $job_id, $ids, true ) ) {
		$ids = array_values( array_diff( $ids, array( $job_id ) ) );
		unset( $dates[ $job_id ] );
		$saved = false;
	} else {
		$ids[]            = $job_id;
		$dates[ $job_id ] = time();
		$saved            = true;
	}

	update_user_meta( $user_id, 'mjb_saved_jobs', $ids );
	update_user_meta( $user_id, 'match_saved_dates', $dates );

	return $saved;
}

/**
 * Aviso «Inicia sesión para guardar vacantes» (J20) para quien no tiene sesión, en las vistas con marcador de
 * guardar: portada y Job Board.
 */
function match_save_modal(): void {
	if ( is_user_logged_in() || ! ( is_front_page() || ( function_exists( 'match_is_jobboard_view' ) && match_is_jobboard_view() ) ) ) {
		return;
	}
	get_template_part( 'template-parts/jobboard/save-modal' );
}
add_action( 'wp_footer', 'match_save_modal' );

/**
 * Tras iniciar sesión (o registrarse) desde el aviso de guardar, la página vuelve con ?match_save=ID: se guarda
 * esa vacante (sin alternar: si ya estaba guardada se deja así) y se limpia la URL, conservando lo demás.
 */
function match_save_after_login(): void {
	if ( ! isset( $_GET['match_save'] ) || ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$id = sanitize_key( wp_unslash( $_GET['match_save'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' !== $id && ! isset( match_saved_map()[ $id ] ) ) {
		match_toggle_saved( $id );
	}

	wp_safe_redirect( remove_query_arg( 'match_save' ) );
	exit;
}
add_action( 'template_redirect', 'match_save_after_login', 5 );

/**
 * POST /wp-json/match/v1/saved/{id} alterna el guardado (sesión + nonce REST).
 */
function match_saved_rest_routes(): void {
	register_rest_route(
		'match/v1',
		'/saved/(?P<id>[a-z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'is_user_logged_in',
			'callback'            => function ( WP_REST_Request $request ): WP_REST_Response {
				$id    = sanitize_key( $request['id'] );
				$saved = match_toggle_saved( $id );
				return new WP_REST_Response( array( 'id' => $id, 'saved' => $saved, 'count' => count( match_saved_map() ) ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'match_saved_rest_routes' );

/**
 * Vacantes guardadas del usuario, listas para la tarjeta y ordenadas por
 * fecha de guardado (más reciente primero). Cada una lleva saved_at.
 */
function match_jobboard_saved_jobs( int $limit = 0 ): array {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$map = match_saved_map();
	arsort( $map );
	$jobs = array();

	foreach ( $map as $id => $ts ) {
		if ( 0 === strpos( (string) $id, 'demo-' ) ) {
			if ( ! match_jobs_demo_mode() ) {
				continue;
			}
			$job = match_demo_jobs()[ (int) substr( $id, 5 ) ] ?? null;
		} else {
			$job = (int) $id > 0 && match_job_board_active() && 'publish' === get_post_status( (int) $id ) ? match_job_data( (int) $id ) : null;
		}
		if ( ! $job ) {
			continue;
		}
		$job['saved']    = true;
		$job['saved_at'] = $ts ? wp_date( 'j M, Y', $ts ) : '';
		$jobs[]          = $job;
		if ( $limit && count( $jobs ) >= $limit ) {
			break;
		}
	}

	return $jobs;
}

/**
 * IDs de vacantes a las que el usuario ya postuló.
 */
function match_jobboard_applied_ids(): array {
	if ( ! is_user_logged_in() || ! match_job_board_active() ) {
		return array();
	}

	return array_values( array_filter( array_map( fn( WP_Post $app ): int => (int) MJB_Helpers::get_meta( $app->ID, 'job_id' ), MJB_Query::my_applications() ) ) );
}

/**
 * Claves de vacantes postuladas como string ("12" o "demo-0"), para
 * compararlas con las tarjetas de cualquier origen.
 */
function match_jobboard_applied_keys(): array {
	if ( match_jobs_demo_mode() ) {
		return array_map( fn( array $row ): string => (string) $row['job']['id'], match_user_applications() );
	}
	return array_map( 'strval', match_jobboard_applied_ids() );
}

/* ======================================================================
 * Mis procesos (Figma: 4105:97; vacío 4406:3)
 * ====================================================================== */

/**
 * Etapas de una postulación: meta `stage` del plugin → etiqueta, variante
 * visual de la pastilla y el texto de "Próximo paso" (columna de estado en
 * el detalle de vacante, Figma 4158:4284 / 5548:5587). `step` es el nombre
 * en la línea de etapas del panel, cuando difiere de la pastilla.
 *
 * El avance llega por los avisos de estado del CRM (MJB_Status en el plugin):
 * cada aviso trae el estado público (`status`: recibida, en_revision,
 * en_entrevista…) y el plugin lo traduce a una de estas etapas en el meta
 * `stage`, con su fecha en `status_history`. "closed" agrupa los tres
 * finales (seleccionada, no_seleccionada, proceso_cerrado); su "Próximo
 * paso" depende del resultado (ver match_application_final_hint).
 *
 * Respaldo sin aviso: si la VACANTE figura cerrada en GET /public/jobs
 * (`closed` o desaparecida del CRM), la postulación pasa a "closed" igual.
 *
 * @return array<string, array{label:string,variant:string,hint:string,step?:string}>
 */
function match_application_stages(): array {
	return array(
		'sent'      => array(
			'label' => __( 'Enviado', 'match' ),
			'variant' => 'sent',
			'hint' => __( 'Recibimos tu CV. Lo revisamos y lo enviamos al cliente pronto.', 'match' ),
		),
		'review'    => array(
			'label' => __( 'En revisión', 'match' ),
			'variant' => 'review',
			'hint' => __( 'El equipo de Match está evaluando tu perfil para este proceso.', 'match' ),
		),
		'interview' => array(
			'label' => __( 'Entrevista agendada', 'match' ),
			'step' => __( 'Entrevista', 'match' ),
			'variant' => 'ok',
			'hint' => __( 'Match coordinará la entrevista contigo. Revisa tu correo.', 'match' ),
		),
		'final'     => array(
			'label' => __( 'Decisión final', 'match' ),
			'variant' => 'final',
			'hint' => __( 'El cliente está decidiendo. Match te contactará en cuanto haya una respuesta.', 'match' ),
		),
		'closed'    => array(
			'label' => __( 'Finalizado', 'match' ),
			'variant' => 'muted',
			'hint' => __( 'Match cerró este proceso. Si avanzaste, te contactarán por correo.', 'match' ),
		),
	);
}

/**
 * Recorrido del panel "Etapas del proceso" (Figma 5548:5587), en orden.
 * "closed" no es un paso: es el cierre del proceso, que corta el recorrido.
 *
 * @return string[]
 */
function match_application_steps(): array {
	return array( 'sent', 'review', 'interview', 'final' );
}

/**
 * "Próximo paso" de una fila de match_user_applications(). En "closed" el
 * texto depende de cómo terminó: seleccionado, descartado o proceso cerrado
 * (por aviso del CRM o porque la vacante figura cerrada).
 */
function match_application_hint( array $row ): string {
	$stage = $row['stage'] ?? 'sent';

	if ( 'closed' !== $stage ) {
		return match_application_stages()[ $stage ]['hint'] ?? '';
	}

	switch ( $row['status'] ?? '' ) {
		case 'seleccionada':
			return __( '¡Felicitaciones! Fuiste seleccionado/a. Match se pondrá en contacto contigo para los siguientes pasos.', 'match' );
		case 'no_seleccionada':
			return __( 'Gracias por tu interés. En esta ocasión no continúas en el proceso; te tendremos en cuenta para futuras vacantes.', 'match' );
	}

	if ( ! empty( $row['closed_at'] ) ) {
		/* translators: %s: fecha de cierre del proceso. */
		return sprintf( __( 'Match cerró este proceso el %s. Si avanzaste, te contactarán por correo.', 'match' ), $row['closed_at'] );
	}

	return match_application_stages()['closed']['hint'];
}

/**
 * Historial de avisos de estado de una postulación (meta del plugin), en
 * orden: [{ event_id, status, stage, at, ts }]. Las entradas sin etapa
 * conocida se descartan.
 */
function match_application_history( int $application_id ): array {
	$history = array();

	foreach ( (array) MJB_Helpers::get_meta( $application_id, 'status_history', array() ) as $entry ) {
		$ts = strtotime( (string) ( $entry['at'] ?? '' ) );
		if ( $ts && ! empty( $entry['stage'] ) && isset( match_application_stages()[ $entry['stage'] ] ) ) {
			$history[] = array_merge( $entry, array( 'ts' => $ts ) );
		}
	}

	return $history;
}

/**
 * Postulación del usuario actual a una vacante puntual (real o "demo-N"), o
 * null si no ha postulado: la misma fila de match_user_applications().
 * Reutiliza esa función para no duplicar la lógica real/demo; en modo demo
 * esas cinco filas son fijas, así que sirven para revisar las cinco etapas
 * sin CRM.
 *
 * @return array{job:array,stage:string,status:string,applied:string,applied_ts:int,closed_at:string,dates:array,history:array,cv:?array}|null
 */
function match_job_application_data( string $job_key ): ?array {
	if ( ! is_user_logged_in() ) {
		return null;
	}

	foreach ( match_user_applications() as $row ) {
		if ( (string) $row['job']['id'] === $job_key ) {
			return $row;
		}
	}

	return null;
}

/**
 * Postulaciones del usuario listas para la fila: job (ver match_job_data),
 * stage, status (estado público del CRM), applied (fecha legible),
 * applied_ts para ordenar, dates (etapa → fecha en que se alcanzó) y
 * history (avisos del CRM, ver match_application_history).
 *
 * En modo demo (sin vacantes sincronizadas) devuelve cinco filas de ejemplo
 * con todas las etapas, salvo que se pida ?demo=0.
 */
function match_user_applications( string $order = 'reciente' ): array {
	$rows = array();

	if ( match_job_board_active() && ! match_jobs_demo_mode() ) {
		foreach ( MJB_Query::my_applications() as $application ) {
			$job_id = (int) MJB_Helpers::get_meta( $application->ID, 'job_id' );
			$job    = $job_id && 'publish' === get_post_status( $job_id ) ? match_job_data( $job_id ) : array(
				'id'      => (string) $job_id,
				'company' => (string) MJB_Helpers::get_meta( $job_id, 'company_name' ),
				'logo'    => '',
				'title'   => get_the_title( $application ),
				'url'     => '',
				'type'    => '',
				'place'   => '',
				'submarcas' => $job_id ? match_job_submarcas( $job_id ) : array(),
			);
			$stage   = (string) MJB_Helpers::get_meta( $application->ID, 'stage', 'sent' );
			$stage   = isset( match_application_stages()[ $stage ] ) ? $stage : 'sent';
			$when    = strtotime( (string) MJB_Helpers::get_meta( $application->ID, 'applied_at' ) ) ?: (int) get_post_time( 'U', true, $application );
			$history = match_application_history( $application->ID );

			// Fecha en que se llegó por primera vez a cada etapa.
			$dates = array( 'sent' => wp_date( 'j M, Y', $when ) );
			foreach ( $history as $entry ) {
				$dates[ $entry['stage'] ] = $dates[ $entry['stage'] ] ?? wp_date( 'j M, Y', $entry['ts'] );
			}

			// Respaldo sin aviso del CRM: la vacante figura cerrada o desapareció
			// del CRM sin pasar por `closed` (el sync la marca missing_in_crm).
			if ( 'closed' !== $stage && $job_id && in_array( MJB_Helpers::get_meta( $job_id, 'status' ), array( 'closed', 'missing_in_crm' ), true ) ) {
				$stage     = 'closed';
				$closed_ts = strtotime( (string) MJB_Helpers::get_meta( $job_id, 'closed_at' ) );
				if ( $closed_ts ) {
					$dates['closed'] = wp_date( 'j M, Y', $closed_ts );
				}
			}

			// El CV que viajó con esta postulación, no el que hoy tiene el perfil.
			$cv_file = (string) MJB_Helpers::get_meta( $application->ID, 'cv_file' );
			$cv_name = $cv_file === (string) get_user_meta( get_current_user_id(), 'mjb_cv_file', true ) ? (string) get_user_meta( get_current_user_id(), 'mjb_cv_name', true ) : '';

			$rows[] = array(
				'job'        => $job,
				'stage'      => $stage,
				'status'     => (string) MJB_Helpers::get_meta( $application->ID, 'status' ),
				'applied'    => wp_date( 'j M, Y', $when ),
				'applied_ts' => $when,
				'closed_at'  => $dates['closed'] ?? '',
				'dates'      => $dates,
				'history'    => $history,
				'cv'         => match_cv_info( $cv_file, $cv_name, wp_date( 'Y-m-d H:i:s', $when ) ),
			);
		}
	} elseif ( match_jobs_demo_mode() && '0' !== ( $_GET['demo'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$stages = array( 'review', 'interview', 'closed', 'sent', 'final' );
		$cv     = match_user_cv();
		foreach ( array_slice( match_demo_jobs(), 0, 5 ) as $i => $job ) {
			$applied = strtotime( '2026-04-08' ) - $i * DAY_IN_SECONDS;
			$dates   = array( 'sent' => date_i18n( 'j M, Y', $applied ) );
			foreach ( array_slice( match_application_steps(), 1, max( 0, (int) array_search( $stages[ $i ], match_application_steps(), true ) ) ) as $n => $step ) {
				$dates[ $step ] = date_i18n( 'j M, Y', $applied + ( $n + 1 ) * 3 * DAY_IN_SECONDS );
			}
			if ( 'closed' === $stages[ $i ] ) {
				$dates['closed'] = date_i18n( 'j M, Y', strtotime( '2026-05-02' ) );
			}
			$rows[] = array(
				'job'        => $job,
				'stage'      => $stages[ $i ],
				'status'     => '',
				'applied'    => $dates['sent'],
				'applied_ts' => $applied,
				'closed_at'  => $dates['closed'] ?? '',
				'dates'      => $dates,
				'history'    => array(),
				'cv'         => $cv,
			);
		}
	}

	usort( $rows, fn( array $a, array $b ): int => 'antiguo' === $order ? $a['applied_ts'] <=> $b['applied_ts'] : $b['applied_ts'] <=> $a['applied_ts'] );

	return $rows;
}

/**
 * Plantillas del Job Board (comparten header, CSS y clase de <body>).
 */
function match_jobboard_templates(): array {
	return array( 'template-jobboard.php', 'template-login.php', 'template-registro.php', 'template-registro-cv.php', 'template-profile.php', 'template-procesos.php', 'template-guardados.php' );
}

/**
 * Mis procesos exige sesión.
 */
function match_procesos_guard(): void {
	if ( is_page_template( array( 'template-procesos.php', 'template-guardados.php' ) ) && ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( (string) get_permalink() ) );
		exit;
	}
}
add_action( 'template_redirect', 'match_procesos_guard' );

/* ======================================================================
 * Detalle de vacante (Figma: Job-Board/Detalle de vacante, 4047:3)
 * ====================================================================== */

/**
 * ¿Estamos en una vista del Job Board? Plantillas propias o el detalle de
 * una vacante real (single del CPT del plugin).
 */
function match_is_jobboard_view(): bool {
	if ( is_page_template( match_jobboard_templates() ) ) {
		return true;
	}
	return match_job_board_active() && ( is_singular( MJB_Helpers::job_post_type() ) || is_post_type_archive( MJB_Helpers::job_post_type() ) );
}

/**
 * Datos completos de una vacante para el detalle. $id es el ID del post o
 * "demo-N" para las filas de ejemplo. Null si no existe.
 *
 * Además de match_job_data(): description_html, benefits_html, requirements
 * (lista; el CRM no lo entrega aparte, así que en vacantes reales va vacío),
 * summary (filas del "Resumen del rol"), chips, closed e is_demo.
 */
function match_job_detail_data( string $id ): ?array {
	if ( 0 === strpos( $id, 'demo-' ) ) {
		$index = (int) substr( $id, 5 );
		$demo  = match_demo_jobs();
		if ( ! isset( $demo[ $index ] ) ) {
			return null;
		}
		$job = $demo[ $index ];
		$f   = $job['filters'];

		$job['description_html'] = wpautop( implode( "\n\n", array(
			sprintf( __( 'Buscamos un %1$s para formar parte del equipo de %2$s. Serás responsable de transformar grandes volúmenes de información en decisiones accionables que guíen el negocio, desde el diseño de la estrategia hasta la presentación de resultados a la alta dirección.', 'match' ), $job['title'], $job['company'] ),
			__( 'Trabajarás en estrecha colaboración con distintas áreas de la organización, diseñando tableros, modelos robustos y reportes ejecutivos que soporten la estrategia comercial. Tu trabajo tendrá impacto directo en decisiones de millones de soles.', 'match' ),
			__( 'El equipo es dinámico, orientado a resultados y con una cultura de mejora continua. Buscamos a alguien que no solo analice, sino que convierta la complejidad en historias claras que muevan a la acción.', 'match' ),
		) ) );
		$job['benefits_html'] = '<ul>' . implode( '', array_map( fn( string $s ): string => '<li>' . esc_html( $s ) . '</li>', array(
			__( 'Trabajo flexible con horario adaptable: organizas tu día según tus picos de productividad.', 'match' ),
			__( 'Seguro médico privado desde el primer mes, con cobertura para ti y un familiar directo.', 'match' ),
			__( 'Acceso a plataformas de aprendizaje y presupuesto para certificaciones.', 'match' ),
			__( '5 días de vacaciones adicionales a los legales para que puedas desconectarte de verdad.', 'match' ),
			__( 'Ambiente inclusivo y diverso, con políticas activas de equidad.', 'match' ),
		) ) ) . '</ul>';
		$job['requirements'] = array(
			__( 'Experiencia mínima de 2 años en roles similares.', 'match' ),
			__( 'Dominio avanzado de las herramientas propias del rol.', 'match' ),
			__( 'Experiencia liderando proyectos con impacto en el negocio.', 'match' ),
			__( 'Manejo de Excel avanzado (tablas dinámicas, macros, Power Query).', 'match' ),
			__( 'Capacidad analítica y orientación al detalle.', 'match' ),
			__( 'Comunicación efectiva para presentar hallazgos a stakeholders no técnicos.', 'match' ),
		);
		$job['skills']  = array_merge( $job['skills'], array( 'Excel avanzado', 'Dashboards', 'Comunicación' ) );
		$job['summary'] = array(
			__( 'Nivel', 'match' )     => $f['nivel'],
			__( 'Modalidad', 'match' ) => $f['modalidad'],
			__( 'Jornada', 'match' )   => __( 'Tiempo completo', 'match' ),
			__( 'Ubicación', 'match' ) => $f['ubicacion'] . ', Perú',
			__( 'Salario', 'match' )   => $job['salary'],
			__( 'Publicado', 'match' ) => __( '8 de abril, 2026', 'match' ),
		);
		$job['chips']   = array_filter( array( $job['type'] ? explode( ',', $job['type'] )[0] : '', $job['place'], str_replace( ' /mes', '', $job['salary'] ) ) );
		$job['closed']  = false;
		$job['is_demo'] = true;
		return $job;
	}

	$post_id = (int) $id;
	if ( ! $post_id || ! match_job_board_active() || MJB_Helpers::job_post_type() !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		return null;
	}

	$job      = match_job_data( $post_id );
	$benefits = (string) MJB_Helpers::get_meta( $post_id, 'benefits' );
	$deadline = (string) MJB_Helpers::get_meta( $post_id, 'deadline' );
	$city     = (string) MJB_Helpers::get_meta( $post_id, 'city' );
	$country  = (string) MJB_Helpers::get_meta( $post_id, 'country' );

	$job['description_html'] = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );
	$job['benefits_html']    = $benefits ? wp_kses_post( wpautop( $benefits ) ) : '';
	$job['requirements']     = array(); // El CRM lo incluye dentro de la descripción.
	$job['summary']          = array_filter(
		array(
			__( 'Nivel', 'match' )         => MJB_Helpers::seniority_label( (string) MJB_Helpers::get_meta( $post_id, 'seniority' ) ),
			__( 'Modalidad', 'match' )     => MJB_Helpers::work_mode_label( (string) MJB_Helpers::get_meta( $post_id, 'work_mode' ) ),
			__( 'Jornada', 'match' )       => MJB_Helpers::employment_type_label( (string) MJB_Helpers::get_meta( $post_id, 'employment_type' ) ),
			__( 'Ubicación', 'match' )     => trim( implode( ', ', array_filter( array( $city, $country ) ) ), ', ' ),
			__( 'Salario', 'match' )       => $job['salary'],
			__( 'Publicado', 'match' )     => get_the_date( 'j \d\e F, Y', $post_id ),
			__( 'Postula hasta', 'match' ) => $deadline ? date_i18n( 'j \d\e F, Y', strtotime( $deadline ) ) : '',
		),
		fn( $v ): bool => '' !== trim( (string) $v )
	);
	$job['chips']   = array_filter( array( $job['type'], $job['place'], $job['salary'] ) );
	$job['closed']  = 'published' !== (string) MJB_Helpers::get_meta( $post_id, 'status', 'published' );
	$job['is_demo'] = false;

	return $job;
}

/* ======================================================================
 * Listado de vacantes (Figma: Job-Board/Entrance, 4009:3081)
 * ====================================================================== */

/** Vacantes por página del listado. */
const MATCH_JOBS_PER_PAGE = 10;

/**
 * El plugin pone su propio tamaño de página (12) en la consulta principal del archivo; se iguala al del listado
 * para que WordPress cuente las mismas páginas que la paginación.
 */
add_filter( 'mjb/jobs_per_page', fn(): int => MATCH_JOBS_PER_PAGE );

/**
 * El listado no sale de la consulta principal (búsqueda por empresa/submarca y modo demo van aparte), así que
 * /vacantes/page/N/ no debe dar 404 aunque la consulta principal tenga menos páginas: la plantilla ya pinta el
 * estado vacío si la página no existe.
 */
function match_jobs_archive_no_404( $preempt, WP_Query $query ) {
	if ( class_exists( 'MJB_Helpers' ) && $query->is_main_query() && $query->is_post_type_archive( MJB_Helpers::job_post_type() ) ) {
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'match_jobs_archive_no_404', 10, 2 );

/**
 * Ids de vacantes que coinciden con la búsqueda por empresa (taxonomía
 * mjb_company, por trozo del nombre) o por submarca. $ids_args es la consulta
 * de ids sin texto, con los filtros activos.
 */
function match_jobs_search_extra_ids( string $q, array $ids_args ): array {
	$ids = array();

	$companies = get_terms(
		array(
			'taxonomy'   => 'mjb_company',
			'name__like' => $q,
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);
	if ( $companies && ! is_wp_error( $companies ) ) {
		$tax_query             = $ids_args['tax_query'] ?? array();
		$tax_query[]           = array(
			'taxonomy' => 'mjb_company',
			'field'    => 'term_id',
			'terms'    => $companies,
		);
		$tax_query['relation'] = 'AND';
		$ids                   = ( new WP_Query( array_merge( $ids_args, array( 'tax_query' => $tax_query ) ) ) )->posts; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$slugs = match_submarcas_search( $q );
	if ( $slugs ) {
		foreach ( ( new WP_Query( $ids_args ) )->posts as $id ) {
			if ( array_intersect( $slugs, array_column( match_job_submarcas( $id ), 'slug' ) ) ) {
				$ids[] = $id;
			}
		}
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

/**
 * Consulta del listado: filtros por taxonomía, búsqueda libre, orden y
 * paginación. En modo demo trabaja en memoria sobre las filas de ejemplo.
 *
 * @return array{jobs:array,total:int,pages:int,page:int,demo:bool}
 */
function match_jobs_archive_query( array $filters, string $q = '', string $order = 'reciente', int $page = 1, int $per_page = 10 ): array {
	$page = max( 1, $page );

	if ( match_jobs_demo_mode() ) {
		$jobs = match_jobs_demo_filtered( $filters );
		if ( '' !== $q ) {
			$needle = mb_strtolower( $q );
			$jobs   = array_values( array_filter( $jobs, fn( array $job ): bool => false !== mb_strpos( mb_strtolower( $job['title'] . ' ' . $job['company'] . ' ' . implode( ' ', $job['skills'] ) . ' ' . implode( ' ', array_column( $job['submarcas'], 'label' ) ) ), $needle ) ) );
		}
		if ( 'antiguo' === $order ) {
			$jobs = array_reverse( $jobs );
		}
		$total = count( $jobs );
		return array(
			'jobs'  => array_slice( $jobs, ( $page - 1 ) * $per_page, $per_page ),
			'total' => $total,
			'pages' => max( 1, (int) ceil( $total / $per_page ) ),
			'page'  => $page,
			'demo'  => true,
		);
	}

	$args = array(
		'post_type'      => MJB_Helpers::job_post_type(),
		'posts_per_page' => $per_page,
		'paged'          => $page,
		's'              => $q,
		'orderby'        => 'date',
		'order'          => 'antiguo' === $order ? 'ASC' : 'DESC',
	) + match_jobs_query_args( $filters );

	// La búsqueda de WordPress solo mira título y contenido: se suman las
	// vacantes cuya empresa o submarca coincide con el texto.
	if ( '' !== $q ) {
		$ids_args = array_merge( $args, array( 'fields' => 'ids', 'posts_per_page' => -1, 'paged' => 1, 'no_found_rows' => true ) );
		$extra    = match_jobs_search_extra_ids( $q, array_merge( $ids_args, array( 's' => '' ) ) );
		if ( $extra ) {
			$ids  = array_unique( array_merge( ( new WP_Query( $ids_args ) )->posts, $extra ) );
			$args = array_merge( $args, array( 's' => '', 'post__in' => $ids ) );
		}
	}

	$query = new WP_Query( $args );

	return array(
		'jobs'  => array_map( fn( WP_Post $post ): array => match_job_data( $post->ID ), $query->posts ),
		'total' => (int) $query->found_posts,
		'pages' => max( 1, (int) $query->max_num_pages ),
		'page'  => $page,
		'demo'  => false,
	);
}

/**
 * Los candidatos (usuarios sin permiso de edición) no entran al escritorio de WordPress: /wp-admin/ y
 * /wp-admin/profile.php los devuelven a su panel. Quedan fuera admin-ajax.php y admin-post.php, que usa el sitio
 * (login con Google, newsletter, onboarding, etc.). Administradores y editores no se ven afectados.
 */
function match_block_admin_for_candidates(): void {
	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( 'admin-post.php' === basename( wp_parse_url( $_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH ) ?: '' ) ) {
		return;
	}
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( match_jobboard_url() );
		exit;
	}
}
add_action( 'admin_init', 'match_block_admin_for_candidates' );

/**
 * Animación de entrada del listado del Job Board: solo en la primera carga
 * limpia de la sesión. Si la URL trae filtros, búsqueda, orden o paginación
 * (?q, ?nivel[], ?orden, /page/2/) o ya se animó antes (sessionStorage
 * jbAnimated), se marca <html> con .jb-no-anim y el CSS la desactiva. Va en el
 * <head> para decidirlo antes del primer pintado y evitar parpadeos.
 */
function match_jb_animation_gate(): void {
	?>
<script>
(function(){try{var d=document.documentElement,s=window.sessionStorage;
if(s.getItem('jbAnimated')||window.location.search||/\/page\/\d+/.test(window.location.pathname)){d.classList.add('jb-no-anim');return;}
window.addEventListener('load',function(){setTimeout(function(){try{s.setItem('jbAnimated','1');}catch(e){}},1200);});
}catch(e){}})();
</script>
	<?php
}
add_action( 'wp_head', 'match_jb_animation_gate', 1 );
