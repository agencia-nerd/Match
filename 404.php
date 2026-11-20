<?php
/**
 * Página no encontrada (Figma J21): bloque centrado con isotipo, título, texto y los dos botones del sitio
 * (CTA principal y secundario). El título de la pestaña lo traduce match_404_title() en functions.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="match-container">
	<section class="match-404">
		<span class="match-404__mark" aria-hidden="true"><?php echo match_icon( 'isotipo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<p class="match-404__code" aria-hidden="true">404</p>
		<h1 class="match-404__title"><?php esc_html_e( 'Esta página ya no está aquí', 'match' ); ?></h1>
		<p class="match-404__text"><?php esc_html_e( 'Puede que la vacante haya cerrado o que el enlace esté mal escrito.', 'match' ); ?></p>
		<div class="match-404__actions">
			<a class="match-btn match-btn--cta" href="<?php echo esc_url( match_jobs_url() ); ?>">
				<?php echo match_cta_inner( __( 'Ver vacantes', 'match' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
			<a class="match-btn match-btn--secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Ir al inicio', 'match' ); ?>
			</a>
		</div>
	</section>
</main>
<?php
get_footer();
