<?php
/**
 * Tarjetas de programas (Figma: Section — Programas, node 5476:4791).
 *
 * Cada fila de las tarjetas (imagen, título, descripción, datos, destacados,
 * botón) se alinea con la de sus vecinas por subgrid: si una tarjeta tiene
 * más texto o más destacados, crecen todas por igual (ver programas.css).
 * Cada tarjeta lleva el ID de su campo «ancla» (o el de su título): los
 * botones del hero apuntan a él y assets/js/programas.js la resalta.
 */
defined( 'ABSPATH' ) || exit;

$post_id = get_queried_object_id();
$field   = static fn( string $name ) => function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;

$programas = array_filter( (array) $field( 'prog_programas' ), static fn( $row ) => ! empty( $row['titulo'] ) );

if ( ! $programas ) {
	return;
}

// Solución a la que pertenecen los programas (la página madre, p. ej.
// Outplacement): va al correo como «Servicio»; arriba del formulario se lee
// el programa.
$parent   = wp_get_post_parent_id( $post_id );
$servicio = $parent ? get_the_title( $parent ) : '';
?>
<section class="match-prog-cards" id="programas">
	<?php match_section_head( (string) $field( 'prog_eyebrow' ), (string) $field( 'prog_titulo' ), (string) $field( 'prog_titulo_2' ) ); ?>

	<div class="match-prog-cards__grid">
		<?php foreach ( array_values( $programas ) as $i => $programa ) : ?>
			<?php
			$datos = array_filter( (array) ( $programa['datos'] ?: array() ), static fn( $row ) => '' !== (string) ( $row['valor'] ?? '' ) );
			$items = array_filter( (array) ( $programa['items'] ?: array() ), static fn( $row ) => ! empty( $row['texto'] ) );
			?>
			<article class="match-prog-card" id="<?php echo esc_attr( sanitize_title( ltrim( (string) ( $programa['ancla'] ?? '' ), '#' ) ?: $programa['titulo'] ) ); ?>"<?php echo match_aos( $i * 100 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<div class="match-prog-card__media">
					<?php if ( ! empty( $programa['imagen'] ) ) : ?>
						<?php echo wp_get_attachment_image( (int) $programa['imagen'], 'large', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 1024px) 100vw, 33vw' ) ); ?>
					<?php endif; ?>
				</div>

				<div class="match-prog-card__body">
					<header class="match-prog-card__head">
						<h3 class="match-prog-card__title"><?php echo esc_html( $programa['titulo'] ); ?></h3>
						<?php if ( ! empty( $programa['subtitulo'] ) ) : ?>
							<p class="match-prog-card__subtitle"><?php echo esc_html( $programa['subtitulo'] ); ?></p>
						<?php endif; ?>
					</header>

					<p class="match-prog-card__text"><?php echo esc_html( (string) $programa['descripcion'] ); ?></p>

					<?php if ( $datos ) : ?>
						<dl class="match-prog-card__data">
							<?php foreach ( $datos as $dato ) : ?>
								<div>
									<dt><?php echo esc_html( $dato['etiqueta'] ); ?></dt>
									<dd><?php echo esc_html( $dato['valor'] ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php else : ?>
						<div aria-hidden="true"></div>
					<?php endif; ?>

					<ul class="match-prog-card__items">
						<?php foreach ( $items as $item ) : ?>
							<li><?php echo esc_html( $item['texto'] ); ?></li>
						<?php endforeach; ?>
					</ul>

					<?php if ( ! empty( $programa['boton_texto'] ) ) : ?>
						<?php
						// Sin destino propio, abre el formulario emergente con «Profesional»
						// y el programa arriba (app.js); sin JS baja a «Hablemos».
						$destino = $programa['boton_destino'] ?: '#contacto';
						$modal   = '#contacto' === $destino ? ' data-contact-modal data-perfil="profesional" data-servicio="' . esc_attr( $servicio ) . '"' : '';
						?>
						<a class="match-btn match-btn--cta match-btn--block match-prog-card__cta" href="<?php echo esc_url( $destino ); ?>" data-programa="<?php echo esc_attr( $programa['titulo'] ); ?>"<?php echo $modal; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<?php echo match_cta_inner( $programa['boton_texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
