<?php
/**
 * Testimonios (Figma: Section — Testimonios, node 5496:4908).
 * Fila de dos piezas: a la izquierda el carrusel (Embla) solo con las
 * reseñas, y a la derecha la foto «¿Qué dicen nuestros clientes?», fija.
 * En escritorio (≥1440px) quedan las proporciones del diseño, 454 / 454 /
 * 660 sobre 1600, con dos reseñas visibles; por debajo la foto baja bajo el
 * carrusel. Los puntos se muestran siempre (data-embla-dots-always).
 * Anchos en solucion.css (.match-sol-testimonios__row).
 *
 * @var array $args
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="match-sol-testimonios">
	<?php match_section_head( __( 'Historias reales', 'match' ), __( 'Testimonios.', 'match' ), '', 'h2-xl' ); ?>

	<div class="match-sol-testimonios__row" data-aos="fade-up" data-aos-delay="100">
		<div class="match-sol-testimonios__carousel flex flex-col gap-4" data-embla>
			<div class="overflow-hidden" data-embla-viewport>
				<div class="flex touch-pan-y gap-4">
					<?php foreach ( $args['reviews'] as $review ) : ?>
						<blockquote class="match-review match-sol-testimonios__slide">
							<div class="match-review__photo<?php echo 'contain' === $review['fit'] ? ' match-review__photo--contain' : ''; ?>" style="--bg: <?php echo esc_attr( $review['bg'] ); ?>">
								<img src="<?php echo esc_url( $review['photo'] ); ?>" alt="" loading="lazy" decoding="async">
							</div>
							<div class="match-review__body">
								<p class="match-review__text"><?php echo esc_html( $review['text'] ); ?></p>
								<hr>
								<footer>
									<p class="match-review__name"><?php echo esc_html( $review['name'] ); ?></p>
									<p class="match-review__role"><?php echo esc_html( $review['role'] ); ?></p>
								</footer>
							</div>
						</blockquote>
					<?php endforeach; ?>
				</div>
			</div>

			<ul class="match-dots" data-embla-dots data-embla-dots-always data-label="<?php esc_attr_e( 'Ir al testimonio', 'match' ); ?>"></ul>
		</div>

		<div class="match-review match-review--dark match-sol-testimonios__aside">
			<img src="<?php echo esc_url( $args['reviews_photo'] ); ?>" alt="" loading="lazy" decoding="async">
			<p class="match-review__headline"><?php esc_html_e( '¿Qué dicen nuestros clientes?', 'match' ); ?></p>
		</div>
	</div>
</section>
