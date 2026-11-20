<?php
/**
 * Pie de página.
 *
 * Figma: Section — Footer (node 3570:79). Bloque de contacto, tres columnas
 * de enlaces, wordmark grande y barra legal.
 */
defined( 'ABSPATH' ) || exit;

$phone = get_theme_mod( 'match_phone', '(+51) 908825057' );
$email = get_theme_mod( 'match_email', 'conversemos@match.win' );
$ruc   = get_theme_mod( 'match_ruc', '20613126725' );
$razon = get_theme_mod( 'match_razon_social', 'MATCH GROUP S.A.C.' );

$columns = array(
	'footer_jobs'   => __( 'Job Board', 'match' ),
	'footer_nav'    => __( 'Navegación', 'match' ),
	'footer_social' => __( 'Síguenos', 'match' ),
);
?>
<footer class="match-footer">
	<div class="match-footer__top">
		<div class="match-footer__row">
			<div class="match-footer__contact">
				<div class="match-footer__lines">
					<p class="match-footer__line match-footer__phone">
						<?php echo match_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					</p>
					<p class="match-footer__line match-footer__email">
						<?php echo match_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
					</p>
				</div>

				<dl class="match-footer__legal">
					<div><dt><?php esc_html_e( 'RUC', 'match' ); ?></dt><dd><?php echo esc_html( $ruc ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Razón Social', 'match' ); ?></dt><dd><?php echo esc_html( $razon ); ?></dd></div>
				</dl>
			</div>

			<nav class="match-footer__nav" aria-label="<?php esc_attr_e( 'Navegación del pie', 'match' ); ?>">
				<?php foreach ( $columns as $location => $label ) : ?>
					<div class="match-footer__column">
						<p class="match-footer__column-title"><?php echo esc_html( $label ); ?></p>
						<?php match_footer_menu( $location ); ?>
					</div>
				<?php endforeach; ?>
			</nav>
		</div>

		<?php $iso_color = match_footer_iso_color(); ?>
		<?php if ( ! $iso_color ) : ?>
			<?php
			/*
			 * Degradado por defecto del isotipo del pie: linear-gradient(60deg,
			 * #0086F1 2.55%, #0081F0 21.84%, #0075EE 41.97%, #0060EB 62.94%,
			 * #0043E6 83.07%, #003FE6 86.43%). Los extremos de la línea salen de
			 * la caja del isotipo en el SVG (x 0–216, y 22–151) a 60°.
			 */
			?>
			<svg class="match-footer__defs" width="0" height="0" aria-hidden="true" focusable="false">
				<linearGradient id="match-footer-iso-gradient" x1="-0.9" y1="149.4" x2="216.9" y2="23.6" gradientUnits="userSpaceOnUse">
					<stop offset="0.0255" stop-color="#0086F1" />
					<stop offset="0.2184" stop-color="#0081F0" />
					<stop offset="0.4197" stop-color="#0075EE" />
					<stop offset="0.6294" stop-color="#0060EB" />
					<stop offset="0.8307" stop-color="#0043E6" />
					<stop offset="0.8643" stop-color="#003FE6" />
				</linearGradient>
			</svg>
		<?php endif; ?>
		<p class="match-footer__wordmark<?php echo $iso_color ? ' has-iso-color' : ''; ?>"<?php echo $iso_color ? ' style="--footer-iso: ' . esc_attr( $iso_color ) . '"' : ''; ?>>
			<?php echo match_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</p>
	</div>

	<div class="match-footer__bar">
		<div class="match-footer__bar-inner">
			<p>
				<?php
				printf(
					/* translators: %s: año */
					esc_html__( '© %s Match. Todos los derechos reservados.', 'match' ),
					esc_html( gmdate( 'Y' ) )
				);
				?>
			</p>
			<p class="match-footer__policies">
				<a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>"><?php esc_html_e( 'Política de Privacidad', 'match' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/terminos-de-servicio/' ) ); ?>"><?php esc_html_e( 'Términos de Servicio', 'match' ); ?></a>
			</p>
		</div>
	</div>
</footer>
