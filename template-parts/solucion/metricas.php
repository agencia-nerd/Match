<?php
/**
 * Métricas sobre foto (Figma: Section — Métricas, node 3765:6834).
 *
 * @var array $args
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="match-sol-metricas">
	<div class="match-sol-metricas__photo" data-aos="fade-up">
		<img src="<?php echo esc_url( $args['metrics_photo'] ); ?>" alt="" loading="lazy" decoding="async">
		<ul class="match-sol-metricas__row">
			<?php foreach ( $args['metrics'] as $i => list( $value, $label ) ) : ?>
				<?php
				// Outplacement (Figma): cifra en grande arriba y la palabra debajo ("7/10" / "profesionales").
				$parts = 'outplacement' === ( $args['theme'] ?? '' ) ? preg_split( '/\s+/', trim( (string) $value ), 2 ) : array( $value );
				?>
				<li data-aos="fade-up" data-aos-delay="<?php echo (int) ( 200 + $i * 120 ); ?>">
					<?php if ( count( $parts ) > 1 ) : ?>
						<span class="match-sol-metricas__value match-sol-metricas__value--split"><span class="match-sol-metricas__num"><?php echo esc_html( $parts[0] ); ?></span><span class="match-sol-metricas__unit"><?php echo esc_html( $parts[1] ); ?></span></span>
					<?php else : ?>
						<span class="match-sol-metricas__value"><?php echo esc_html( $value ); ?></span>
					<?php endif; ?>
					<span class="match-sol-metricas__label"><?php echo esc_html( $label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
