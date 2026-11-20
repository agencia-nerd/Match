<?php
/**
 * Acordeón de soluciones (Figma: node 4618:5140).
 *
 * Paneles: match_home()['soluciones'] (inc/home-data.php). Cada uno define su
 * foto y el tono del velo que la cubre (--overlay); con velo claro la etiqueta
 * pasa a gris oscuro (--label).
 */
defined( 'ABSPATH' ) || exit;

$home = match_home();
?>
<section class="match-soluciones" id="soluciones">
	<?php match_section_head( $home['s_eyebrow'], $home['s_title'], $home['s_title_2'] ); ?>

	<div class="match-accordion">
		<?php foreach ( $home['soluciones'] as $index => $item ) : ?>
			<?php
			$style = '--overlay:' . $item['overlay'] . ( $item['light'] ? ';--label:var(--match-neutral-500)' : '' );
			?>
			<article class="match-accordion__panel<?php echo 0 === $index ? ' is-open' : ''; ?>" id="sol-<?php echo esc_attr( $item['slug'] ); ?>" style="<?php echo esc_attr( $style ); ?>" data-reveal="up">
				<div class="match-accordion__visual">
					<img src="<?php echo esc_url( $item['photo'] ); ?>" alt="" loading="lazy" decoding="async">
				</div>

				<?php // En la columna (≤1279) la tarjeta cerrada lleva kicker, título y flecha (Figma: Arrow, node 6166:1231). ?>
				<button class="match-accordion__trigger" type="button" aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="sol-body-<?php echo esc_attr( $item['slug'] ); ?>">
					<span class="match-accordion__trigger-text">
						<?php if ( $item['kicker'] ) : ?>
							<span class="match-accordion__trigger-kicker"><?php echo esc_html( $item['kicker'] ); ?></span>
						<?php endif; ?>
						<span class="match-accordion__trigger-title"><?php echo esc_html( $item['title'] ); ?></span>
					</span>
					<span class="match-accordion__arrow" aria-hidden="true"><?php echo match_icon( 'arrow-circle-nav' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</button>

				<div class="match-accordion__content" id="sol-body-<?php echo esc_attr( $item['slug'] ); ?>">
					<div class="match-accordion__copy">
						<?php if ( $item['kicker'] ) : ?>
							<p class="match-accordion__kicker"><?php echo esc_html( $item['kicker'] ); ?></p>
						<?php endif; ?>
						<h3 class="match-accordion__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="match-accordion__text"><?php echo match_bold_text( $item['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado dentro del helper. ?></p>
					</div>
					<a class="match-btn match-btn--cta" href="<?php echo esc_url( $item['url'] ); ?>">
						<?php echo match_cta_inner( __( 'Más información', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
