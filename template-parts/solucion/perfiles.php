<?php

/**
 * Perfiles que acompañamos.
 *
 * Dos variantes del mismo bloque, según el contenido de la página:
 * - Con «Tarjetas de perfiles con detalle» (audiences): encabezado arriba y
 *   tarjetas con foto, textos, lista y/o grupos de lista y botón (Figma
 *   Outplacement 5503:5171, Assessment 5504:5254). Todo opcional.
 * - Sin ellas: fotos con etiqueta (levels) junto al titular (Figma: Section —
 *   Perfiles, node 3901:5088).
 * El titular y el alcance salen de la pestaña Perfiles en ambas.
 *
 * @var array $args
 */
defined('ABSPATH') || exit;

$audiences = $args['audiences'] ?? array();
// Etiqueta sobre el titular: la del campo o, si está vacío, «nombre®».
$brand     = ! empty($args['levels_brand']) ? $args['levels_brand'] : strtolower($args['title']) . '®';
?>
<?php if ($audiences) : ?>
	<section class="match-sol-perfiles">
		<div class="match-sol-perfiles__card match-sol-perfiles__card--detail" data-aos="fade-up">
			<header class="match-sol-perfiles__head">
				<div>
					<p class="match-sol-perfiles__brand"><?php echo esc_html($brand); ?></p>
					<?php if ($args['levels_title'] || $args['levels_title_2']) : ?>
						<h2 class="match-sol-perfiles__title">
							<?php echo esc_html($args['levels_title']); ?>
							<?php if ($args['levels_title_2']) : ?>
								<span class="is-secondary"><?php echo esc_html($args['levels_title_2']); ?></span>
							<?php endif; ?>
						</h2>
					<?php endif; ?>
				</div>
				<?php if ($args['scope'] || $args['scope_2']) : ?>
					<p class="match-sol-perfiles__scope-text">
						<?php if ($args['scope']) : ?>
							<strong><?php echo esc_html($args['scope']); ?></strong>
						<?php endif; ?>
						<?php echo esc_html($args['scope_2']); ?>
					</p>
				<?php endif; ?>
			</header>

			<div class="match-aud-grid">
				<?php foreach ($audiences as $i => $aud) : ?>
					<article class="match-aud" data-aos="fade-up" data-aos-delay="<?php echo (int) (100 + $i * 100); ?>">
						<?php if ($aud['image']) : ?>
							<figure class="match-aud__media">
								<?php echo wp_get_attachment_image($aud['image'], 'large', false, array('loading' => 'lazy', 'sizes' => '(max-width: 1024px) 100vw, 50vw')); ?>
							</figure>
						<?php endif; ?>

						<div class="match-aud__body">
							<div class="match-aud__top">
								<?php if ($aud['title'] || $aud['subtitle']) : ?>
									<header class="match-aud__head">
										<?php if ($aud['title']) : ?>
											<h3 class="match-aud__title"><?php echo esc_html($aud['title']); ?></h3>
										<?php endif; ?>
										<?php if ($aud['subtitle']) : ?>
											<p class="match-aud__subtitle"><?php echo esc_html($aud['subtitle']); ?></p>
										<?php endif; ?>
									</header>
								<?php endif; ?>

								<?php if ($aud['text']) : ?>
									<div class="match-aud__text">
										<?php foreach (preg_split('/\R\s*\R/', trim($aud['text'])) as $paragraph) : // Una línea en blanco separa párrafos. 
										?>
											<p><?php echo nl2br(esc_html(trim($paragraph))); ?></p>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ($aud['list_title'] || $aud['items']) : ?>
									<div class="match-aud__list">
										<?php if ($aud['list_title']) : ?>
											<p class="match-aud__list-title"><?php echo esc_html($aud['list_title']); ?></p>
										<?php endif; ?>
										<?php if ($aud['items']) : ?>
											<ul>
												<?php foreach ($aud['items'] as $item) : ?>
													<li>
														<?php if (! empty($item['title'])) : ?>
															<strong><?php echo esc_html($item['title']); ?></strong>
														<?php endif; ?>
														<?php if (! empty($item['text'])) : ?>
															<span><?php echo esc_html($item['text']); ?></span>
														<?php endif; ?>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php if (! empty($aud['groups'])) : ?>
									<div class="match-aud__groups">
										<?php foreach ($aud['groups'] as $group) : ?>
											<div class="match-aud__group">
												<?php if ($group['title']) : ?>
													<p class="match-aud__list-title"><?php echo esc_html($group['title']); ?></p>
												<?php endif; ?>
												<?php if ($group['items']) : ?>
													<ul>
														<?php foreach ($group['items'] as $text) : ?>
															<li><?php echo esc_html($text); ?></li>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>

							<?php if (! empty($aud['button']['url']) && ! empty($aud['button']['title'])) : ?>
								<a class="match-btn match-btn--cta match-btn--block match-aud__cta" href="<?php echo esc_url($aud['button']['url']); ?>" <?php echo '_blank' === ($aud['button']['target'] ?? '') ? ' target="_blank" rel="noopener"' : ''; ?>>
									<?php echo match_cta_inner($aud['button']['title']); // phpcs:ignore WordPress.Security.EscapeOutput 
									?>
								</a>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php else : ?>
	<section class="match-sol-perfiles">
		<div class="match-sol-perfiles__card" data-aos="fade-up">
			<div class="match-sol-perfiles__col">
				<div>
					<p class="match-sol-perfiles__brand"><?php echo esc_html($brand); ?></p>
					<h2 class="match-sol-perfiles__title">
						<?php echo esc_html($args['levels_title']); ?>
						<span class="is-secondary"><?php echo esc_html($args['levels_title_2']); ?></span>
					</h2>
				</div>
				<div class="match-sol-perfiles__scope">
					<p class="match-sol-perfiles__scope-label"><?php echo match_icon('executive' === $args['theme'] ? 'eyebrow-mark' : 'isotipo-circle'); // phpcs:ignore WordPress.Security.EscapeOutput -- Executive usa el isotipo en círculo (Figma 5512:5397). 
																?><?php esc_html_e('Alcance', 'match'); ?></p>
					<p class="match-sol-perfiles__scope-text">
						<strong><?php echo esc_html($args['scope']); ?></strong>
						<?php echo esc_html($args['scope_2']); ?>
					</p>
				</div>
			</div>

			<ul class="match-levels<?php echo count($args['levels']) <= 2 ? ' match-levels--stack' : ''; ?>">
				<?php foreach ($args['levels'] as $i => list($label, $photo)) : ?>
					<li class="match-level" data-aos="fade-up" data-aos-delay="<?php echo (int) (150 + $i * 100); ?>">
						<img src="<?php echo esc_url($photo); ?>" alt="" loading="lazy" decoding="async">
						<?php if ($args['levels_icon']) : ?>
							<span class="match-level__icon"><?php echo match_icon($args['levels_icon']); // phpcs:ignore WordPress.Security.EscapeOutput 
															?></span>
						<?php endif; ?>
						<p class="match-level__label"><?php echo esc_html($label); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>