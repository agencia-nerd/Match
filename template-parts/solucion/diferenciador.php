<?php

/**
 * Casos de éxito en carrusel (Figma: Section — Diferenciador, node 5496:4835).
 * Cada slide: a la izquierda el logo de la solución y, opcional, una cifra
 * destacada (Outplacement, node 5499:5090); a la derecha la cita y el
 * cliente; al pie, opcional, la fila de cifras del caso bajo una línea
 * (Executive). Todas las piezas son opcionales y solo se pintan con
 * contenido.
 * Carrusel con Embla (ver [data-embla] en assets/js/app.js); la maqueta del
 * slide sigue en solucion.css y el layout del carrusel en utilidades Tailwind.
 *
 * @var array $args
 */
defined('ABSPATH') || exit;
$cases = $args['cases'];
// Imagen de fondo del recuadro (PCF cases_bg); vacía, queda el color con halos.
$bg = $args['cases_bg'] ?? '';

if (! $cases) {
	return;
}
?>
<section class="match-sol-dif" data-embla data-embla-options="<?php echo esc_attr(wp_json_encode(array('loop' => true))); ?>">
	<div class="match-sol-dif__box<?php echo $bg ? ' match-sol-dif__box--image' : ''; ?>" data-aos="fade-up" <?php echo $bg ? 'style="background-image: url(\'' . esc_url($bg) . '\')"' : ''; ?>>
		<div class="overflow-hidden" data-embla-viewport>
			<div class="flex touch-pan-y">
				<?php foreach ($cases as $i => $case) : ?>
					<?php
					// Título, descripción mediana y descripción pequeña destacados son
					// independientes: cada uno se pinta si tiene texto.
					list($hl_value, $hl_sub, $hl_label) = array_pad((array) ($case['highlight'] ?? array()), 3, '');
					$has_highlight = $hl_value || $hl_sub || $hl_label;
					$has_attrib    = $case['role'] || $case['company'] || $case['logo'];
					?>
					<article class="match-sol-dif__slide min-w-0 shrink-0 grow-0 basis-full">
						<div class="match-sol-dif__top<?php echo $has_highlight ? ' match-sol-dif__top--highlight' : ''; ?>">
							<div class="match-sol-dif__left">
								<div class="match-sol-dif__logo" aria-label="<?php echo esc_attr('Match ' . $args['title']); ?>" role="img">
									<?php echo is_readable($args['logo']) ? file_get_contents($args['logo']) : ''; // phpcs:ignore 
									?>
								</div>
								<?php if ($has_highlight) : ?>
									<p class="match-sol-dif__highlight">
										<?php if ($hl_value) : ?>
											<strong><?php echo esc_html($hl_value); ?></strong>
										<?php endif; ?>
										<?php if ($hl_sub) : ?>
											<em><?php echo nl2br(esc_html(trim($hl_sub))); ?></em>
										<?php endif; ?>
										<?php if ($hl_label) : ?>
											<span><?php echo nl2br(esc_html(trim($hl_label))); ?></span>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</div>
							<div class="match-sol-dif__right">
								<?php if ($case['quote'] || $case['text']) : ?>
									<div>
										<?php if ($case['quote']) : ?>
											<p class="match-sol-dif__quote"><?php echo nl2br(esc_html(trim($case['quote']))); ?></p>
										<?php endif; ?>
										<?php if ($case['text']) : ?>
											<p class="match-sol-dif__text"><?php echo esc_html($case['text']); ?></p>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<?php if ($has_attrib) : ?>
									<div class="match-sol-dif__attrib">
										<span class="match-sol-dif__client">
											<?php if ($case['logo']) : ?>
												<img src="<?php echo esc_url($case['logo']); ?>" alt="" loading="lazy" decoding="async">
											<?php else : ?>
												<?php echo match_icon('isotipo'); // phpcs:ignore WordPress.Security.EscapeOutput 
												?>
											<?php endif; ?>
										</span>
										<div>
											<?php if ($case['role']) : ?>
												<p class="match-sol-dif__name"><?php echo esc_html($case['role']); ?></p>
											<?php endif; ?>
											<?php if ($case['company']) : ?>
												<p class="match-sol-dif__company"><?php echo esc_html($case['company']); ?></p>
											<?php endif; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php if ($case['stats']) : ?>
							<dl class="match-sol-dif__stats">
								<?php foreach ($case['stats'] as list($value, $label)) : ?>
									<div>
										<dt><?php echo esc_html($value); ?></dt>
										<dd><?php echo esc_html($label); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php if (count($cases) > 1) : ?>
		<button class="match-sol-dif__arrow match-sol-dif__arrow--prev" type="button" data-embla-prev>
			<span class="screen-reader-text"><?php esc_html_e('Caso anterior', 'match'); ?></span>
			<?php echo match_icon('arrow-circle'); // phpcs:ignore WordPress.Security.EscapeOutput 
			?>
		</button>
		<button class="match-sol-dif__arrow match-sol-dif__arrow--next" type="button" data-embla-next>
			<span class="screen-reader-text"><?php esc_html_e('Caso siguiente', 'match'); ?></span>
			<?php echo match_icon('arrow-circle'); // phpcs:ignore WordPress.Security.EscapeOutput 
			?>
		</button>
		<ul class="match-dots" data-embla-dots data-label="<?php esc_attr_e('Ir al caso', 'match'); ?>"></ul>
	<?php endif; ?>
</section>