<?php
/**
 * Vacantes activas (Figma: node 3502:3421).
 *
 * Filtra en el sitio: los selects consultan match/v1/jobs (inc/jobs-filters.php)
 * y app.js reemplaza las filas, el conteo y las opciones. Sin JavaScript el
 * formulario envía al archivo de vacantes. Mientras el CRM no sincronice
 * vacantes, se muestran las cuatro filas de ejemplo del diseño.
 */
defined( 'ABSPATH' ) || exit;

$current = match_jobs_current_filters( null, true );
$list    = match_jobs_list( $current );
$facets  = match_jobs_facets( $current );
$jobs    = $list['jobs'];

$filters = array(
	'ubicacion' => array( __( 'Ubicación', 'match' ), 'filter-location' ),
	'nivel'     => array( __( 'Nivel', 'match' ), 'filter-level' ),
	'modalidad' => array( __( 'Modalidad', 'match' ), 'filter-mode' ),
	'industria' => array( __( 'Industria', 'match' ), 'filter-industry' ),
	'submarca'  => array( __( 'Submarca', 'match' ), 'filter-submarca' ), // Temporal: quitar esta línea para ocultar el filtro.
);
?>
<section class="match-vacantes-section" id="vacantes">
	<div class="match-vacantes">
		<?php match_section_head( match_home()['v_eyebrow'], match_home()['v_title'], '', 'h2-xl', true ); ?>

		<div class="match-vacantes__body">
			<div class="match-vacantes__toolbar" data-reveal="up">
				<div class="match-vacantes__bar">
					<form class="match-vacantes__filters" method="get" action="<?php echo esc_url( match_jobs_url() ); ?>" data-jobs-form data-filters-box data-endpoint="<?php echo esc_url( rest_url( 'match/v1/jobs' ) ); ?>">
						<?php // Mobile (J06): un botón «Filtros · n» despliega los filtros; en escritorio no se ve. ?>
						<button class="match-filters-toggle" type="button" aria-expanded="false" aria-controls="match-vacantes-pills" data-filters-toggle data-label="<?php esc_attr_e( 'Filtros', 'match' ); ?>">
							<span data-filters-toggle-label><?php echo count( $current ) ? esc_html( sprintf( /* translators: %d: filtros activos */ __( 'Filtros · %d', 'match' ), count( $current ) ) ) : esc_html__( 'Filtros', 'match' ); ?></span>
							<?php echo match_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<div class="match-vacantes__pills" id="match-vacantes-pills" data-filter-group>
							<?php foreach ( $filters as $param => list( $label, $icon ) ) : ?>
								<?php
								// Un solo <select multiple> es la base (datos y envío); en escritorio
								// app.js dibuja encima el desplegable con casillas, en mobile se usa
								// el selector nativo del teléfono.
								$options  = $facets[ $param ] ?? array();
								$selected = (array) ( $current[ $param ] ?? array() );
								$known    = array_column( $options, 'slug' );
								$names    = array_map( fn( string $slug ): string => match_jobs_term_name( $param, $slug ), $selected );
								$text     = 1 === count( $names ) ? $names[0] : ( $names ? $label . ' · ' . count( $names ) : $label );
								?>
								<div class="match-filter<?php echo $selected ? ' is-active' : ''; ?>" data-filter>
									<button class="match-filter__toggle" type="button" aria-haspopup="true" aria-expanded="false">
										<?php echo match_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span data-filter-label><?php echo esc_html( $text ); ?></span>
									</button>
									<select name="<?php echo esc_attr( $param ); ?>[]" multiple data-param="<?php echo esc_attr( $param ); ?>" data-label="<?php echo esc_attr( $label ); ?>" onchange="this.form.submit()" aria-label="<?php echo esc_attr( $label ); ?>">
										<?php foreach ( $options as $option ) : ?>
											<option value="<?php echo esc_attr( $option['slug'] ); ?>" data-name="<?php echo esc_attr( $option['name'] ); ?>" data-count="<?php echo (int) $option['count']; ?>"<?php selected( in_array( $option['slug'], $selected, true ) ); ?>><?php echo esc_html( $option['name'] ); ?> (<?php echo (int) $option['count']; ?>)</option>
										<?php endforeach; ?>
										<?php foreach ( $selected as $i => $slug ) : ?>
											<?php if ( ! in_array( $slug, $known, true ) ) : ?>
												<option value="<?php echo esc_attr( $slug ); ?>" data-name="<?php echo esc_attr( $names[ $i ] ); ?>" data-count="0" selected><?php echo esc_html( $names[ $i ] ); ?> (0)</option>
											<?php endif; ?>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endforeach; ?>
						</div>

						<span class="match-vacantes__divider" aria-hidden="true" data-jobs-divider<?php echo $current ? '' : ' hidden'; ?>></span>

						<button class="match-vacantes__clear" type="reset" data-jobs-clear<?php echo $current ? '' : ' hidden'; ?>>
							<?php echo match_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php esc_html_e( 'Borrar filtros', 'match' ); ?>
						</button>
						<button class="screen-reader-text" type="submit"><?php esc_html_e( 'Aplicar filtros', 'match' ); ?></button>
					</form>

					<a class="match-vacantes__all" href="<?php echo esc_url( match_jobs_archive_url( $current ) ); ?>" data-jobs-all>
						<?php esc_html_e( 'Ver todas las vacantes', 'match' ); ?>
						<?php echo match_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</div>

				<p class="match-vacantes__count" data-jobs-count aria-live="polite">
					<?php echo match_jobs_count_html( count( $jobs ), $list['total'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</p>
			</div>

			<div class="match-vacantes__list" data-jobs-list aria-busy="false" data-reveal="rows">
				<?php echo match_jobs_render_rows( $jobs ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</div>
</section>
