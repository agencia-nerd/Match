<?php
/**
 * Aviso flotante de la solución y su burbuja minimizada (Figma: Aviso
 * 6219:1305 Personnel / 1328 Executive / 1351 Assessment / 1374
 * Outplacement; Launcher 6219:1322 / 1345 / 1368 / 1399).
 *
 * - Pregunta (toast) + descripción (toast_text) e ícono con el degradado de
 *   la paleta (assets/img/{theme}/toast-icon.svg).
 * - Con página de Programas hija (Outplacement): dos públicos, dos botones.
 *   «Ver programas» lleva a las tarjetas (no al hero) y «Para empresas» abre
 *   el formulario emergente con «Empresa» marcado.
 * - Sin ella: un solo botón (campo toast_cta) que abre el formulario
 *   emergente. Sin JS, los botones bajan a «Hablemos» (#contacto).
 * - La X minimiza el aviso a la burbuja (#match-toast-launcher), que lo
 *   vuelve a abrir. El estado se recuerda en la sesión (app.js).
 *
 * @var array $args
 */
defined( 'ABSPATH' ) || exit;

$programas_url = match_solucion_programas_url( get_queried_object_id() );
$servicio      = (string) $args['title'];
$toast_title   = (string) ( $args['toast'] ?? '' );
$toast_text    = (string) ( $args['toast_text'] ?? '' );

// Íconos por paleta; si la paleta no tiene los suyos, los de Executive.
$theme = (string) ( $args['theme'] ?? 'executive' );
if ( ! is_readable( get_theme_file_path( "assets/img/{$theme}/toast-icon.svg" ) ) ) {
	$theme = 'executive';
}
$icon_url     = get_theme_file_uri( "assets/img/{$theme}/toast-icon.svg" );
$launcher_url = get_theme_file_uri( "assets/img/{$theme}/toast-launcher.svg" );
?>
<aside class="match-toast<?php echo $programas_url ? ' match-toast--dual' : ''; ?>" id="match-toast" role="complementary" aria-label="<?php echo esc_attr( $toast_title ); ?>" tabindex="-1" hidden>
	<div class="match-toast__head">
		<img class="match-toast__logo" src="<?php echo esc_url( $icon_url ); ?>" width="44" height="44" alt="">
		<div class="match-toast__copy">
			<p class="match-toast__title"><?php echo esc_html( $toast_title ); ?></p>
			<?php if ( $toast_text ) : ?>
				<p class="match-toast__text"><?php echo esc_html( $toast_text ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<button class="match-toast__close" type="button" aria-controls="match-toast" aria-label="<?php esc_attr_e( 'Minimizar aviso', 'match' ); ?>" data-toast-close>
		<span aria-hidden="true">&times;</span>
	</button>

	<?php if ( $programas_url ) : ?>
		<div class="match-toast__actions">
			<a class="match-btn match-btn--cta" href="<?php echo esc_url( $programas_url ); ?>">
				<?php echo match_cta_inner( __( 'Ver programas', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
			<a class="match-btn match-btn--secondary" href="#contacto" data-contact-modal data-perfil="empresa" data-servicio="<?php echo esc_attr( $servicio ); ?>">
				<?php esc_html_e( 'Para empresas', 'match' ); ?>
			</a>
		</div>
	<?php else : ?>
		<a class="match-btn match-btn--cta" href="#contacto" data-contact-modal data-perfil="empresa" data-servicio="<?php echo esc_attr( $servicio ); ?>">
			<?php echo match_cta_inner( ( $args['toast_cta'] ?? '' ) ?: __( 'Agendar consultoría', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>
	<?php endif; ?>
</aside>

<?php /* translators: %s: pregunta del aviso */ $launcher_label = sprintf( __( 'Abrir aviso: %s', 'match' ), $toast_title ); ?>
<button class="match-toast-launcher" id="match-toast-launcher" type="button" aria-controls="match-toast" aria-expanded="false" aria-label="<?php echo esc_attr( $launcher_label ); ?>" hidden>
	<img src="<?php echo esc_url( $launcher_url ); ?>" width="128" height="128" alt="">
</button>
