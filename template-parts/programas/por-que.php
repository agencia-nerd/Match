<?php
/**
 * Por qué Match (Figma: Section — Por qué Match, node 5485:4925).
 *
 * Bento de cifras: tarjeta azul destacada a la izquierda y cuatro tarjetas
 * fijas a la derecha (cifra, porcentaje en anillo, cifra, cifra). Las cifras
 * cuentan desde 0 y el anillo se llena al entrar la sección en pantalla
 * (assets/js/programas.js); el HTML ya trae el valor final.
 */
defined( 'ABSPATH' ) || exit;

$post_id = get_queried_object_id();
$field   = static fn( string $name ) => function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;

$destacado  = (array) $field( 'prog_pq_destacado' );
$porcentaje = (array) $field( 'prog_pq_porcentaje' );
$pct        = max( 0, min( 100, (float) ( $porcentaje['valor'] ?? 0 ) ) );
$foto_id    = (int) ( $destacado['foto'] ?? 0 );
$foto_id    = $foto_id && wp_attachment_is_image( $foto_id ) ? $foto_id : 0; // Imagen borrada de la biblioteca: no se pinta nada.

// Tarjetas de cifra en el orden del diseño; null = la del anillo.
$tarjetas = array( (array) $field( 'prog_pq_cifra_1' ), null, (array) $field( 'prog_pq_cifra_2' ), (array) $field( 'prog_pq_cifra_3' ) );

if ( empty( $destacado['numero'] ) && empty( $tarjetas[0]['valor'] ) ) {
	return;
}
?>
<section class="match-prog-stats" id="por-que-match" data-prog-stats>
	<?php match_section_head( (string) $field( 'prog_pq_eyebrow' ), (string) $field( 'prog_pq_titulo' ), (string) $field( 'prog_pq_titulo_2' ) ); ?>

	<div class="match-prog-stats__grid">
		<article class="match-prog-stat match-prog-stat--hero<?php echo $foto_id ? ' has-photo' : ''; ?>"<?php echo match_aos(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<div class="match-prog-stat__bg" aria-hidden="true"></div>
			<?php if ( $foto_id ) : ?>
				<figure class="match-prog-stat__figure" aria-hidden="true">
					<?php echo wp_get_attachment_image( $foto_id, 'large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
				</figure>
			<?php endif; ?>
			<?php if ( ! empty( $destacado['etiqueta'] ) ) : ?>
				<p class="match-prog-stat__eyebrow"><?php echo esc_html( $destacado['etiqueta'] ); ?></p>
			<?php endif; ?>
			<div class="match-prog-stat__bottom">
				<p class="match-prog-stat__hero-value">
					<span data-count><?php echo esc_html( (string) ( $destacado['numero'] ?? '' ) ); ?></span>
					<?php if ( ! empty( $destacado['unidad'] ) ) : ?>
						<span class="match-prog-stat__unit"><?php echo esc_html( $destacado['unidad'] ); ?></span>
					<?php endif; ?>
				</p>
				<?php if ( ! empty( $destacado['descripcion'] ) ) : ?>
					<p class="match-prog-stat__hero-text"><?php echo esc_html( $destacado['descripcion'] ); ?></p>
				<?php endif; ?>
			</div>
		</article>

		<?php foreach ( $tarjetas as $i => $tarjeta ) : ?>
			<?php if ( null === $tarjeta ) : ?>
				<article class="match-prog-stat match-prog-stat--gauge"<?php echo match_aos( 100 * ( $i + 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<div class="match-prog-gauge" style="--gauge: <?php echo esc_attr( (string) $pct ); ?>">
						<svg viewBox="0 0 120 120" aria-hidden="true" focusable="false">
							<circle class="match-prog-gauge__track" cx="60" cy="60" r="56" />
							<circle class="match-prog-gauge__fill" cx="60" cy="60" r="56" />
						</svg>
						<p class="match-prog-gauge__value"><span data-count><?php echo esc_html( rtrim( rtrim( number_format( $pct, 1, '.', '' ), '0' ), '.' ) ); ?>%</span></p>
					</div>
					<?php if ( ! empty( $porcentaje['subtitulo'] ) ) : ?>
						<h3 class="match-prog-stat__label"><?php echo esc_html( $porcentaje['subtitulo'] ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $porcentaje['descripcion'] ) ) : ?>
						<p class="match-prog-stat__text"><?php echo esc_html( $porcentaje['descripcion'] ); ?></p>
					<?php endif; ?>
				</article>
			<?php else : ?>
				<article class="match-prog-stat"<?php echo match_aos( 100 * ( $i + 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<div>
						<p class="match-prog-stat__value"><span data-count><?php echo esc_html( (string) ( $tarjeta['valor'] ?? '' ) ); ?></span></p>
						<?php if ( ! empty( $tarjeta['subtitulo'] ) ) : ?>
							<h3 class="match-prog-stat__label"><?php echo esc_html( $tarjeta['subtitulo'] ); ?></h3>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $tarjeta['descripcion'] ) ) : ?>
						<p class="match-prog-stat__text"><?php echo esc_html( $tarjeta['descripcion'] ); ?></p>
					<?php endif; ?>
				</article>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</section>
