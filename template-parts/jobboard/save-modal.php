<?php
/**
 * Aviso «Inicia sesión para guardar vacantes» (Figma J20).
 *
 * Se pinta una vez, en el pie de página, para quien no tiene sesión (ver
 * match_save_modal() en inc/jobboard.php). app.js lo abre al tocar el
 * marcador de cualquier vacante (.match-job__fav / .match-jb-card__fav) en vez
 * de mandar al login sin explicar nada. «Iniciar sesión» y «Regístrate» llevan
 * de vuelta a la misma página con ?match_save=ID: match_save_after_login()
 * guarda la vacante y limpia la URL.
 */
defined( 'ABSPATH' ) || exit;

$signup = function_exists( 'match_registro_page_url' ) ? match_registro_page_url() : '';
?>
<dialog class="match-save-modal" id="match-save-modal" aria-labelledby="match-save-modal-title" data-login-base="<?php echo esc_url( wp_login_url() ); ?>" data-lenis-prevent>
	<div class="match-save-modal__panel">
		<button class="match-save-modal__close" type="button" aria-label="<?php esc_attr_e( 'Cerrar', 'match' ); ?>" data-save-close>
			<?php echo match_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<h2 class="match-save-modal__title" id="match-save-modal-title" data-save-title data-default="<?php echo esc_attr__( 'Inicia sesión para guardar vacantes', 'match' ); ?>"><?php esc_html_e( 'Inicia sesión para guardar vacantes', 'match' ); ?></h2>
		<p class="match-save-modal__text" data-save-text data-default="<?php echo esc_attr__( 'Guarda las que te interesen y vuelve a ellas cuando quieras.', 'match' ); ?>"><?php esc_html_e( 'Guarda las que te interesen y vuelve a ellas cuando quieras.', 'match' ); ?></p>
		<a class="match-btn match-btn--cta" href="<?php echo esc_url( wp_login_url() ); ?>" data-save-login>
			<?php echo match_cta_inner( __( 'Iniciar sesión', 'match' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>
		<?php if ( $signup ) : ?>
			<p class="match-save-modal__alt">
				<?php esc_html_e( '¿No tienes cuenta?', 'match' ); ?>
				<a href="<?php echo esc_url( $signup ); ?>" data-save-signup><?php esc_html_e( 'Regístrate', 'match' ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</dialog>
