<?php

/**
 * Listado de vacantes (Figma: Job-Board/Entrance, node 4009:3081).
 *
 * Marco del Job Board (barra lateral + topbar). Búsqueda, cuatro filtros
 * con conteo facetado, orden y paginación por GET: los selects envían el
 * formulario al cambiar. Los datos salen de match_jobs_archive_query()
 * (modo demo mientras no haya vacantes sincronizadas).
 */
defined('ABSPATH') || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtros públicos de lectura.
$current = match_jobs_current_filters(null, true);
$q     = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$order   = isset($_GET['orden']) && 'antiguo' === $_GET['orden'] ? 'antiguo' : 'reciente';
// phpcs:enable
$page    = max(1, (int) get_query_var('paged'));
$result  = match_jobs_archive_query($current, $q, $order, $page, MATCH_JOBS_PER_PAGE);
$facets  = match_jobs_facets($current);
$labels  = array(
	'ubicacion' => array(__('Ubicación', 'match'), 'filter-location'),
	'nivel'     => array(__('Nivel', 'match'), 'filter-level'),
	'modalidad' => array(__('Modalidad', 'match'), 'filter-mode'),
	'industria' => array(__('Industria', 'match'), 'filter-industry'),
	'submarca'  => array(__('Submarca', 'match'), 'filter-submarca'),
);
$sorts   = array('reciente' => __('Más reciente', 'match'), 'antiguo' => __('Más antiguo', 'match'));
$base    = match_jobs_url();

get_header('jobboard');
?>
<div class="match-jb">
	<?php get_template_part('template-parts/jobboard/sidebar', null, array('current' => 'vacantes')); ?>

	<main id="main" class="match-jb__content">
		<?php get_template_part('template-parts/jobboard/topbar'); ?>

		<div class="match-jb__body match-jb__body--archive">
			<form class="match-jb-filters" method="get" action="<?php echo esc_url($base); ?>" data-aos="fade-up" data-archive-filters data-filters-box>
				<label class="match-jb-search">
					<?php echo match_icon('search'); // phpcs:ignore WordPress.Security.EscapeOutput 
					?>
					<span class="screen-reader-text"><?php esc_html_e('Buscar', 'match'); ?></span>
					<input type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="<?php esc_attr_e('Buscar entre las vacantes...', 'match'); ?>" autocomplete="off">
				</label>

				<?php // Mobile (J06): junto al buscador, un botón «Filtros · n» que despliega los filtros; en escritorio no se ve. ?>
				<button class="match-filters-toggle match-filters-toggle--light" type="button" aria-expanded="false" aria-controls="match-jb-pills" data-filters-toggle data-label="<?php esc_attr_e('Filtros', 'match'); ?>">
					<span data-filters-toggle-label><?php echo count($current) ? esc_html(sprintf(/* translators: %d: filtros activos */ __('Filtros · %d', 'match'), count($current))) : esc_html__('Filtros', 'match'); ?></span>
					<?php echo match_icon('chevron'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<div class="match-jb-filters__pills" id="match-jb-pills" data-filter-group>
					<?php foreach ($labels as $param => list($label, $icon)) : ?>
						<?php
						// Un <select multiple> es la base; app.js dibuja encima el desplegable
						// con casillas (filterGroup) y la píldora muestra "Ubicación · 2".
						$options  = $facets[$param] ?? array();
						$selected = (array) ($current[$param] ?? array());
						$known    = array_column($options, 'slug');
						$names    = array_map(fn(string $slug): string => match_jobs_term_name($param, $slug), $selected);
						$text     = 1 === count($names) ? $names[0] : ($names ? $label . ' · ' . count($names) : $label);
						$modifier = 1 === count($selected) && 'submarca' === $param ? ' match-jb-filter--' . sanitize_html_class($selected[0]) : '';
						?>
						<div class="match-jb-filter<?php echo $selected ? ' is-active' : ''; ?><?php echo $modifier; ?>" data-filter>
							<button class="match-filter__toggle" type="button" aria-haspopup="true" aria-expanded="false">
								<?php echo match_icon($icon); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
								<span data-filter-label><?php echo esc_html($text); ?></span>
							</button>
							<select name="<?php echo esc_attr($param); ?>[]" multiple data-param="<?php echo esc_attr($param); ?>" data-label="<?php echo esc_attr($label); ?>" onchange="this.form.submit()" aria-label="<?php echo esc_attr($label); ?>">
								<?php foreach ($options as $option) : ?>
									<option value="<?php echo esc_attr($option['slug']); ?>" data-name="<?php echo esc_attr($option['name']); ?>" data-count="<?php echo (int) $option['count']; ?>" <?php selected(in_array($option['slug'], $selected, true)); ?>><?php echo esc_html($option['name']); ?> (<?php echo (int) $option['count']; ?>)</option>
								<?php endforeach; ?>
								<?php foreach ($selected as $i => $slug) : ?>
									<?php if (! in_array($slug, $known, true)) : ?>
										<option value="<?php echo esc_attr($slug); ?>" data-name="<?php echo esc_attr($names[$i]); ?>" data-count="0" selected><?php echo esc_html($names[$i]); ?> (0)</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endforeach; ?>

					<?php // «Borrar filtros» reserva siempre su sitio (invisible sin filtros activos) para que el buscador no se achique ni la fila se mueva al aparecer (J17). ?>
					<?php $clear_idle = ! ($current || $q); ?>
						<a class="match-jb-filters__clear<?php echo $clear_idle ? ' is-idle' : ''; ?>" href="<?php echo esc_url($base); ?>"<?php echo $clear_idle ? ' aria-hidden="true" tabindex="-1"' : ''; ?>>
							<?php echo match_icon('close'); // phpcs:ignore WordPress.Security.EscapeOutput 
							?>
							<?php esc_html_e('Borrar filtros', 'match'); ?>
						</a>
				</div>
				<input type="hidden" name="orden" value="<?php echo esc_attr($order); ?>">
				<button class="screen-reader-text" type="submit"><?php esc_html_e('Buscar', 'match'); ?></button>
			</form>

			<hr class="match-jb__rule">

			<section class="match-jb-list-wrap" data-aos="fade-up" data-aos-delay="80">
				<header class="match-jb-list__head">
					<h1 class="match-jb-list__title match-jb-list__title--sm">
						<?php esc_html_e('Lista de empleos', 'match'); ?>
						<span class="match-jb-list__count"><?php echo esc_html(number_format_i18n($result['total'])); ?></span>
					</h1>
					<form class="match-jb-sort" method="get" action="<?php echo esc_url($base); ?>">
						<?php foreach (array_merge($current, array_filter(array('q' => $q))) as $key => $value) : ?>
							<?php foreach ((array) $value as $item) : ?>
								<input type="hidden" name="<?php echo esc_attr($key); ?><?php echo is_array($value) ? '[]' : ''; ?>" value="<?php echo esc_attr($item); ?>">
							<?php endforeach; ?>
						<?php endforeach; ?>
						<label class="match-jb-sort__label">
							<span><?php esc_html_e('Ordenar por:', 'match'); ?></span>
							<strong><?php echo esc_html($sorts[$order]); ?></strong>
							<?php echo match_icon('chevron'); // phpcs:ignore WordPress.Security.EscapeOutput 
							?>
							<select name="orden" onchange="this.form.submit()" aria-label="<?php esc_attr_e('Ordenar por', 'match'); ?>">
								<?php foreach ($sorts as $value => $label) : ?>
									<option value="<?php echo esc_attr($value); ?>" <?php selected($order, $value); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</form>
				</header>

				<?php if ($result['jobs']) : ?>
					<div class="match-jb-list">
						<?php foreach ($result['jobs'] as $job) : ?>
							<?php get_template_part('template-parts/jobboard/job-row', null, array('job' => $job)); ?>
						<?php endforeach; ?>

						<?php if ($result['pages'] > 1) : ?>
							<nav class="match-jb-pager" aria-label="<?php esc_attr_e('Paginación de vacantes', 'match'); ?>">
								<?php
								echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
									array(
										'base'      => add_query_arg(array_merge($current, array_filter(array('q' => $q, 'orden' => 'antiguo' === $order ? 'antiguo' : null))), trailingslashit($base) . 'page/%#%/'),
										'format'    => '',
										'current'   => $result['page'],
										'total'     => $result['pages'],
										'prev_text' => __('Anterior', 'match'),
										'next_text' => __('Siguiente', 'match'),
										'mid_size'  => 1,
									)
								);
								?>
							</nav>
						<?php endif; ?>

						<?php if ($result['page'] >= $result['pages']) : ?>
							<p class="match-jb-list__end"><?php esc_html_e('Llegaste al final de esta lista', 'match'); ?></p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="match-jb-empty match-jb-empty--page">
						<span class="match-jb-empty__icon" aria-hidden="true"><?php echo match_icon('search'); // phpcs:ignore WordPress.Security.EscapeOutput 
																				?></span>
						<p class="match-jb-empty__title"><?php esc_html_e('No hay vacantes que coincidan', 'match'); ?></p>
						<p class="match-jb-empty__text"><?php esc_html_e('Prueba con otra búsqueda o quita algún filtro para ver más resultados.', 'match'); ?></p>
						<a class="match-jb__btn match-jb__btn--dark" href="<?php echo esc_url($base); ?>"><?php esc_html_e('Borrar filtros', 'match'); ?></a>
					</div>
				<?php endif; ?>
			</section>
		</div>
	</main>
</div>
<?php
get_footer('jobboard');
