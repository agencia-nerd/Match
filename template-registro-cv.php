<?php
/**
 * Template Name: Job Board — Registro CV
 *
 * Paso 2 del registro (Figma: Job-Board/Subir CV, node 4462:3). Exige la
 * sesión que dejó el paso 1 (match_handle_register(), inc/jobboard.php).
 * "Continuar" sube el CV con match_handle_register_cv() (mismo mecanismo
 * que el CV del perfil); "Lo hago después" es un enlace directo al panel,
 * sin backend — subir el CV nunca es obligatorio para tener cuenta.
 */
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( match_registro_cv_page_url() ) );
	exit;
}

$notice = match_registro_cv_notice();

// Si el registro empezó desde «Postular ahora», «Lo hago después» también
// vuelve a esa vacante (el formulario de postular pide el CV ahí mismo).
$later = isset( $_COOKIE['match_apply_return'] ) ? wp_validate_redirect( esc_url_raw( rawurldecode( wp_unslash( $_COOKIE['match_apply_return'] ) ) ), '' ) : '';
$later = $later ?: match_jobboard_url();

get_header( 'jobboard' );
?>
<main id="main" class="match-login-page">
	<div class="match-login-page__bg" aria-hidden="true">
		<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/jobboard/login-bg.webp' ) ); ?>" alt="" width="1920" height="1280" fetchpriority="high" decoding="async">
	</div>

	<div class="match-login-page__inner">
		<div class="match-login-page__intro" data-aos="fade-up">
			<h1 class="match-login-page__title"><?php esc_html_e( 'Un paso más.', 'match' ); ?></h1>
			<p class="match-login-page__lead"><?php esc_html_e( 'Tu CV es lo único que necesitas para postularte a cualquier vacante del Job Board.', 'match' ); ?></p>
		</div>

		<form class="match-glass match-login-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" data-aos="fade-up" data-aos-delay="120">
			<a class="match-login-card__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php echo match_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>

			<p class="match-login-card__subtitle"><?php esc_html_e( 'Sube tu CV', 'match' ); ?></p>

			<?php if ( $notice ) : ?>
				<p class="match-login-card__notice match-login-card__notice--<?php echo esc_attr( $notice['type'] ); ?>" role="alert"><?php echo esc_html( $notice['text'] ); ?></p>
			<?php endif; ?>

			<div class="match-register-drop-wrap">
				<label class="match-register-drop" data-dropzone>
					<span class="match-register-drop__icon" aria-hidden="true"><?php echo match_icon( 'upload-cv' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="match-register-drop__title"><?php esc_html_e( 'Arrastra tu CV o haz clic para subir', 'match' ); ?></span>
					<span class="match-register-drop__hint" data-dropzone-hint><?php esc_html_e( 'PDF · máx. 5 MB', 'match' ); ?></span>
					<input class="screen-reader-text" type="file" name="cv" accept=".pdf,application/pdf" data-dropzone-input>
				</label>
				<?php // Con el archivo elegido, la caja se cambia por esta franja (Figma: Confirmación CV, node 6136:5500; app.js). ?>
				<div class="match-register-file" data-dropzone-file role="status" hidden>
					<span class="match-register-file__check" aria-hidden="true">✓</span>
					<span class="match-register-file__text">
						<span class="match-register-file__name" data-dropzone-file-name></span>
						<span class="match-register-file__state"><?php esc_html_e( 'Cargado correctamente', 'match' ); ?></span>
					</span>
					<button class="match-register-file__change" type="button" data-dropzone-change><?php esc_html_e( 'Cambiar', 'match' ); ?></button>
				</div>
				<p class="match-register-drop__note"><?php esc_html_e( 'Solo Match y las empresas a las que postules verán tu CV.', 'match' ); ?></p>
			</div>

			<input type="hidden" name="action" value="match_register_cv">
			<?php wp_nonce_field( 'match_register_cv', '_match_nonce' ); ?>

			<div class="match-login-card__submit">
				<button class="match-btn match-btn--cta" type="submit">
					<?php echo match_cta_inner( __( 'Continuar', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<p class="match-login-card__register">
					<a href="<?php echo esc_url( $later ); ?>"><?php esc_html_e( 'Lo hago después', 'match' ); ?></a>
				</p>
			</div>
		</form>
	</div>

	<footer class="match-login-page__legal">
		<p><?php printf( esc_html__( '© %s Match. Todos los derechos reservados.', 'match' ), esc_html( wp_date( 'Y' ) ) ); ?></p>
		<nav aria-label="<?php esc_attr_e( 'Legal', 'match' ); ?>">
			<a href="<?php echo esc_url( home_url( '/terminos-de-servicio/' ) ); ?>"><?php esc_html_e( 'Términos y condiciones', 'match' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>"><?php esc_html_e( 'Política de privacidad', 'match' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/#contacto' ) ); ?>"><?php esc_html_e( 'Contacto', 'match' ); ?></a>
		</nav>
	</footer>
</main>
<?php
get_footer( 'jobboard' );
