<?php
/**
 * Filtros de vacantes de la portada.
 *
 * La sección "Vacantes" filtra en el sitio sin salir de la portada: los
 * selects consultan el endpoint REST match/v1/jobs y reemplazan las filas.
 * Sin JavaScript el formulario sigue enviando al archivo de vacantes.
 *
 * Todo pasa por las taxonomías que define el plugin (MJB_Query::FILTERS):
 * ubicacion → mjb_location, nivel → mjb_seniority, modalidad → mjb_work_mode,
 * industria → mjb_industry. Los conteos de cada select son facetados: cuentan
 * las vacantes que quedarían al elegir esa opción con los demás filtros
 * activos, así nunca se ofrece una opción que deje la lista vacía.
 *
 * Modo demo: mientras el CRM no sincronice vacantes (o sin plugin), la lista
 * y las facetas se calculan en memoria sobre las filas de ejemplo de
 * match_demo_jobs(), que llevan sus propios valores de filtro. Así la
 * sección se puede revisar completa, filtros incluidos.
 *
 * Submarca: no es taxonomía. Sale de match_job_submarcas() (meta del CRM,
 * campo manual o filtro match/job_submarcas), así que se filtra en PHP
 * sobre los ids. Cuando el CRM envíe la submarca no hay que tocar nada aquí.
 */

defined( 'ABSPATH' ) || exit;

/** Filas que muestra la portada. */
const MATCH_HOME_JOBS = 6;

/** Parámetros de filtro, en el orden de la barra. */
const MATCH_JOBS_PARAMS = array( 'ubicacion', 'nivel', 'modalidad', 'industria', 'submarca' );

/**
 * Parámetros de filtro y su taxonomía. Vacío si el plugin no está activo.
 */
function match_jobs_filter_map(): array {
	return match_job_board_active() ? MJB_Query::FILTERS : array();
}

/**
 * ¿Toca usar las filas de ejemplo? Sin plugin o sin vacantes publicadas.
 */
function match_jobs_demo_mode(): bool {
	static $demo = null;

	if ( null === $demo ) {
		$demo = ! match_job_board_active() || 0 === (int) wp_count_posts( MJB_Helpers::job_post_type() )->publish;
	}

	return $demo;
}

/**
 * Filas de ejemplo que cumplen los filtros (en memoria).
 */
function match_jobs_demo_filtered( array $filters ): array {
	return array_values(
		array_filter(
			match_demo_jobs(),
			function ( array $job ) use ( $filters ): bool {
				// Cada filtro admite varios valores (OR); entre filtros es AND.
				foreach ( $filters as $param => $slugs ) {
					$slugs = (array) $slugs;
					if ( 'submarca' === $param ) {
						if ( ! array_intersect( $slugs, array_column( $job['submarcas'], 'slug' ) ) ) {
							return false;
						}
						continue;
					}
					if ( ! in_array( sanitize_title( $job['filters'][ $param ] ?? '' ), $slugs, true ) ) {
						return false;
					}
				}
				return true;
			}
		)
	);
}

/**
 * Filtros activos, saneados, a partir de $_GET o de un arreglo dado.
 *
 * Cada parámetro llega como "param=slug" o "param[]=a&param[]=b". Por defecto
 * se queda con un solo valor (el archivo de vacantes elige uno por filtro);
 * con $multi devuelve todos, que es lo que usa la portada.
 *
 * @return array<string,string|string[]> param => slug (o lista de slugs con $multi)
 */
function match_jobs_current_filters( ?array $source = null, bool $multi = false ): array {
	$source  = $source ?? $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtros públicos de lectura.
	$filters = array();

	foreach ( MATCH_JOBS_PARAMS as $param ) {
		$values = array();
		foreach ( (array) ( $source[ $param ] ?? array() ) as $raw ) {
			if ( ! is_scalar( $raw ) ) {
				continue;
			}
			$value = sanitize_title( wp_unslash( (string) $raw ) );
			if ( 'submarca' === $param ) {
				$value = match_submarca_slug( $value );
			}
			if ( '' !== $value && ! in_array( $value, $values, true ) ) {
				$values[] = $value;
			}
		}

		if ( $values ) {
			$filters[ $param ] = $multi ? $values : $values[0];
		}
	}

	return $filters;
}

/**
 * tax_query para un conjunto de filtros.
 */
function match_jobs_tax_query( array $filters ): array {
	$map       = match_jobs_filter_map();
	$tax_query = array();

	foreach ( $filters as $param => $slug ) {
		if ( isset( $map[ $param ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $map[ $param ],
				'field'    => 'slug',
				'terms'    => $slug,
			);
		}
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	return $tax_query;
}

/**
 * Ids de vacantes con la submarca elegida (dentro de los demás filtros), o
 * null si no se filtra por submarca.
 */
function match_jobs_submarca_ids( array $filters ): ?array {
	if ( empty( $filters['submarca'] ) ) {
		return null;
	}

	$ids = get_posts(
		array(
			'post_type'      => MJB_Helpers::job_post_type(),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => match_jobs_tax_query( $filters ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$wanted = (array) $filters['submarca'];

	return array_values( array_filter( $ids, fn( int $id ): bool => (bool) array_intersect( $wanted, array_column( match_job_submarcas( $id ), 'slug' ) ) ) );
}

/**
 * Argumentos de WP_Query para un conjunto de filtros (taxonomías + submarca).
 */
function match_jobs_query_args( array $filters ): array {
	$args = array( 'tax_query' => match_jobs_tax_query( $filters ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	$ids  = match_jobs_submarca_ids( $filters );
	if ( null !== $ids ) {
		$args['post__in'] = $ids ?: array( 0 );
	}

	return $args;
}

/**
 * Ids (hasta 500) de las vacantes que cumplen unos filtros, para las facetas.
 */
function match_jobs_facet_ids( array $filters ): array {
	return get_posts(
		array(
			'post_type'      => MJB_Helpers::job_post_type(),
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) + match_jobs_query_args( $filters )
	);
}

/**
 * Vacantes para la portada.
 *
 * @return array{ jobs: array, total: int, demo: bool }
 */
function match_jobs_list( array $filters, int $count = MATCH_HOME_JOBS ): array {
	if ( match_jobs_demo_mode() ) {
		$jobs = match_jobs_demo_filtered( $filters );
		return array( 'jobs' => array_slice( $jobs, 0, $count ), 'total' => count( $jobs ), 'demo' => true );
	}

	$query = new WP_Query(
		array(
			'post_type'      => MJB_Helpers::job_post_type(),
			'posts_per_page' => $count,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) + match_jobs_query_args( $filters )
	);

	$jobs = array_map( fn( WP_Post $post ): array => match_job_data( $post->ID ), $query->posts );

	return array( 'jobs' => $jobs, 'total' => (int) $query->found_posts, 'demo' => false );
}

/**
 * Opciones de cada filtro con conteo facetado.
 *
 * Para cada parámetro se consulta con los demás filtros aplicados (no con el
 * suyo) y se cuentan los términos presentes en ese resultado.
 *
 * @return array<string, array<int, array{slug:string,name:string,count:int}>>
 */
function match_jobs_facets( array $filters ): array {
	$facets = array();

	if ( match_jobs_demo_mode() ) {
		foreach ( MATCH_JOBS_PARAMS as $param ) {
			$others = array_diff_key( $filters, array( $param => true ) );
			$counts = array();
			foreach ( match_jobs_demo_filtered( $others ) as $job ) {
				$names = 'submarca' === $param
					? array_column( $job['submarcas'], 'label', 'slug' )
					: array( sanitize_title( $job['filters'][ $param ] ) => $job['filters'][ $param ] );
				foreach ( $names as $slug => $name ) {
					$counts[ $slug ] = $counts[ $slug ] ?? array( 'slug' => $slug, 'name' => $name, 'count' => 0 );
					$counts[ $slug ]['count']++;
				}
			}
			usort( $counts, fn( array $a, array $b ): int => $b['count'] <=> $a['count'] ?: strcasecmp( $a['name'], $b['name'] ) );
			$facets[ $param ] = array_values( $counts );
		}
		return $facets;
	}

	foreach ( match_jobs_filter_map() as $param => $taxonomy ) {
		$others = array_diff_key( $filters, array( $param => true ) );
		$ids    = match_jobs_facet_ids( $others );

		$counts = array();
		if ( $ids ) {
			$terms = wp_get_object_terms( $ids, $taxonomy, array( 'fields' => 'all_with_object_id' ) );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! isset( $counts[ $term->slug ] ) ) {
						$counts[ $term->slug ] = array( 'slug' => $term->slug, 'name' => $term->name, 'count' => 0 );
					}
					$counts[ $term->slug ]['count']++;
				}
			}
		}

		usort( $counts, fn( array $a, array $b ): int => $b['count'] <=> $a['count'] ?: strcasecmp( $a['name'], $b['name'] ) );
		$facets[ $param ] = array_values( $counts );
	}

	// Submarca: se cuenta vacante por vacante con match_job_submarcas().
	$counts = array();
	foreach ( match_jobs_facet_ids( array_diff_key( $filters, array( 'submarca' => true ) ) ) as $id ) {
		foreach ( match_job_submarcas( $id ) as $submarca ) {
			$counts[ $submarca['slug'] ] = $counts[ $submarca['slug'] ] ?? array( 'slug' => $submarca['slug'], 'name' => $submarca['label'], 'count' => 0 );
			$counts[ $submarca['slug'] ]['count']++;
		}
	}
	usort( $counts, fn( array $a, array $b ): int => $b['count'] <=> $a['count'] ?: strcasecmp( $a['name'], $b['name'] ) );
	$facets['submarca'] = array_values( $counts );

	return $facets;
}

/**
 * Nombre legible de un término elegido (aunque ya no aparezca en las facetas).
 */
function match_jobs_term_name( string $param, string $slug ): string {
	if ( 'submarca' === $param ) {
		return match_submarcas_catalog()[ $slug ] ?? $slug;
	}

	if ( match_jobs_demo_mode() ) {
		foreach ( match_demo_jobs() as $job ) {
			$name = $job['filters'][ $param ] ?? '';
			if ( sanitize_title( $name ) === $slug ) {
				return $name;
			}
		}
		return $slug;
	}

	$taxonomy = match_jobs_filter_map()[ $param ] ?? '';
	$term     = $taxonomy ? get_term_by( 'slug', $slug, $taxonomy ) : false;

	return $term instanceof WP_Term ? $term->name : $slug;
}

/**
 * URL del archivo de vacantes con los filtros actuales.
 */
function match_jobs_archive_url( array $filters ): string {
	return $filters ? add_query_arg( map_deep( $filters, 'rawurlencode' ), match_jobs_url() ) : match_jobs_url();
}

/**
 * Filas (o estado vacío) como HTML.
 */
function match_jobs_render_rows( array $jobs ): string {
	ob_start();

	if ( $jobs ) {
		foreach ( $jobs as $job ) {
			get_template_part( 'template-parts/job', 'row', array( 'job' => $job ) );
		}
	} else {
		?>
		<div class="match-vacantes__empty" role="status">
			<p class="match-vacantes__empty-title"><?php esc_html_e( 'No hay vacantes que coincidan', 'match' ); ?></p>
			<p class="match-vacantes__empty-text"><?php esc_html_e( 'Prueba quitando algún filtro para ver más resultados.', 'match' ); ?></p>
			<button class="match-btn match-btn--secondary" type="button" data-jobs-clear>
				<?php esc_html_e( 'Borrar filtros', 'match' ); ?>
			</button>
		</div>
		<?php
	}

	return (string) ob_get_clean();
}

/**
 * "Mostrando N vacantes" (y "de T" cuando hay más que las visibles).
 */
function match_jobs_count_html( int $shown, int $total ): string {
	$html = sprintf(
		'<span class="is-dim">%s</span> %s <span class="is-muted">%s</span>',
		esc_html__( 'Mostrando', 'match' ),
		esc_html( number_format_i18n( $shown ) ),
		esc_html( _n( 'vacante', 'vacantes', $shown, 'match' ) )
	);

	if ( $total > $shown ) {
		/* translators: %s: total de vacantes */
		$html .= ' <span class="is-muted">' . sprintf( esc_html__( 'de %s', 'match' ), esc_html( number_format_i18n( $total ) ) ) . '</span>';
	}

	return $html;
}

/**
 * GET /wp-json/match/v1/jobs?ubicacion=&nivel=&modalidad=&industria=&submarca=
 */
function match_jobs_rest_routes(): void {
	register_rest_route(
		'match/v1',
		'/jobs',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'args'                => array_fill_keys(
				MATCH_JOBS_PARAMS,
				array( 'type' => array( 'string', 'array' ) ) // Un valor o varios (param[]=a&param[]=b); se sanea en match_jobs_current_filters().
			),
			'callback'            => function ( WP_REST_Request $request ): WP_REST_Response {
				$filters = match_jobs_current_filters( $request->get_params(), true );
				$list    = match_jobs_list( $filters );
				$shown   = count( $list['jobs'] );

				$response = new WP_REST_Response(
					array(
						'html'        => match_jobs_render_rows( $list['jobs'] ),
						'count_html'  => match_jobs_count_html( $shown, $list['total'] ),
						'shown'       => $shown,
						'total'       => $list['total'],
						'demo'        => $list['demo'],
						'facets'      => match_jobs_facets( $filters ),
						'archive_url' => match_jobs_archive_url( $filters ),
					)
				);
				$response->header( 'Cache-Control', 'no-store' );

				return $response;
			},
		)
	);
}
add_action( 'rest_api_init', 'match_jobs_rest_routes' );
