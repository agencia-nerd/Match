<?php
/**
 * Blog (Figma: Blog 3455:142, Blog — Entrada 3933:77).
 *
 * El listado vive en la página con la plantilla "Blog" (template-blog.php).
 * Filtra por ?categoria= y ?buscar= y pagina con ?pagina=, así que los
 * archivos de categoría redirigen allí para que exista una sola URL por vista.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Entradas por página: la grilla repite un patrón de 7 (destacada, 5
 * tarjetas y otra destacada).
 */
const MATCH_BLOG_PER_PAGE = 7;

/**
 * ID de la página que usa la plantilla Blog; 0 si no existe.
 */
function match_blog_page_id(): int {
	static $id = null;

	if ( null === $id ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'template-blog.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$id    = (int) ( $pages[0] ?? 0 );
	}

	return $id;
}

/**
 * URL del listado, opcionalmente filtrado por categoría.
 */
function match_blog_url( string $category = '' ): string {
	$id  = match_blog_page_id();
	$url = $id ? get_permalink( $id ) : home_url( '/' );
	return $category ? add_query_arg( 'categoria', $category, $url ) : $url;
}

/**
 * Categorías que aparecen como filtros (las que tienen entradas, sin la
 * categoría por defecto).
 *
 * @return WP_Term[]
 */
function match_blog_categories(): array {
	return get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
			'orderby'    => 'term_id',
		)
	);
}

/**
 * Categoría que se muestra en tarjetas y cabecera: la primera que no sea la
 * de por defecto.
 */
function match_post_category( int $post_id ): ?WP_Term {
	foreach ( get_the_category( $post_id ) as $term ) {
		if ( (int) get_option( 'default_category' ) !== $term->term_id ) {
			return $term;
		}
	}
	return null;
}

/**
 * Fecha corta en español ("20 mar, 2026"), independiente del idioma del
 * sitio.
 */
function match_short_date( int $post_id ): string {
	$months = array( 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic' );
	$time   = (int) get_post_time( 'U', false, $post_id );
	return sprintf( '%d %s, %d', (int) wp_date( 'j', $time ), $months[ (int) wp_date( 'n', $time ) - 1 ], (int) wp_date( 'Y', $time ) );
}

/**
 * Minutos de lectura estimados (200 palabras por minuto).
 */
function match_reading_time( int $post_id ): int {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Entradas relacionadas: primero de la misma categoría, luego las más
 * recientes.
 *
 * @return int[]
 */
function match_related_posts( int $post_id, int $count = 3 ): array {
	$term = match_post_category( $post_id );
	$ids  = array();

	if ( $term ) {
		$ids = get_posts(
			array(
				'cat'            => $term->term_id,
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => $count,
				'fields'         => 'ids',
			)
		);
	}

	if ( count( $ids ) < $count ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post__not_in'   => array_merge( array( $post_id ), $ids ),
					'posts_per_page' => $count - count( $ids ),
					'fields'         => 'ids',
				)
			)
		);
	}

	return array_map( 'intval', $ids );
}

/**
 * Las entradas cuelgan de la página del blog: /blog/{slug}/ en lugar de
 * /{slug}/. Se hace en el tema (no en Ajustes → Enlaces permanentes) para
 * que valga igual en todos los entornos y no mueva otros archivos bajo /blog/.
 *
 * Base = slug de la página con la plantilla Blog; vacío si no existe o si
 * el sitio no usa enlaces permanentes (entonces todo queda como antes).
 */
function match_blog_base(): string {
	$id = match_blog_page_id();
	return $id && get_option( 'permalink_structure' ) ? (string) get_post_field( 'post_name', $id ) : '';
}

/**
 * URL de cada entrada: /blog/{slug}/. Los borradores sin publicar conservan
 * su enlace de vista previa (?p=); $leavename deja %postname% para que el
 * editor permita cambiar el slug.
 */
function match_blog_post_link( string $url, WP_Post $post, bool $leavename ): string {
	$base = match_blog_base();
	if ( ! $base || false !== strpos( $url, '?' ) ) {
		return $url;
	}

	$slug = $leavename ? '%postname%' : $post->post_name;
	return home_url( user_trailingslashit( $base . '/' . $slug, 'single' ) );
}
add_filter( 'post_link', 'match_blog_post_link', 10, 3 );

/**
 * Regla para /blog/{slug}/ (y su paginación interna /blog/{slug}/2/). Las
 * reglas se regeneran solas una vez cuando cambia la base (p. ej. si se
 * renombra la página del blog).
 */
function match_blog_rewrite(): void {
	$base = match_blog_base();
	if ( ! $base ) {
		return;
	}

	$quoted = preg_quote( $base, '#' );
	add_rewrite_rule( '^' . $quoted . '/([^/]+)(?:/([0-9]+))?/?$', 'index.php?name=$matches[1]&page=$matches[2]', 'top' );

	if ( get_option( 'match_blog_rewrite_base' ) !== $base ) {
		update_option( 'match_blog_rewrite_base', $base );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'match_blog_rewrite', 20 );

/**
 * Las URLs antiguas de entradas (/{slug}/) redirigen con 301 a /blog/{slug}/,
 * conservando la query, para no perder enlaces compartidos ni posicionamiento.
 */
function match_blog_redirect_old_posts(): void {
	$base = match_blog_base();
	if ( ! $base || ! is_singular( 'post' ) || is_preview() ) {
		return;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$path = trim( preg_replace( '#^' . preg_quote( $home, '#' ) . '#', '', $path ), '/' );

	if ( 0 === strpos( $path . '/', $base . '/' ) ) {
		return;
	}

	$target = get_permalink();
	$query  = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	wp_safe_redirect( $query ? $target . ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query : $target, 301 );
	exit;
}
add_action( 'template_redirect', 'match_blog_redirect_old_posts', 1 );

/**
 * Los archivos de categoría usan el listado del blog con su filtro.
 */
function match_blog_redirect_categories(): void {
	if ( is_category() && match_blog_page_id() ) {
		wp_safe_redirect( match_blog_url( (string) get_queried_object()->slug ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'match_blog_redirect_categories' );

/**
 * La URL antigua /prensa/ (y lo que cuelgue de ella) pasa a /blog/ con 301,
 * conservando la query (?categoria=, ?buscar=, ?pagina=). No redirige si la
 * página aún tiene el slug «prensa», para no entrar en bucle.
 */
function match_blog_redirect_prensa(): void {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$path = trim( preg_replace( '#^' . preg_quote( $home, '#' ) . '#', '', $path ), '/' );

	if ( 'prensa' !== $path && 0 !== strpos( $path, 'prensa/' ) ) {
		return;
	}

	$target = match_blog_url();
	if ( 'prensa' === trim( (string) wp_parse_url( $target, PHP_URL_PATH ), '/' ) ) {
		return;
	}

	$query = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	wp_safe_redirect( $query ? $target . ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query : $target, 301 );
	exit;
}
add_action( 'template_redirect', 'match_blog_redirect_prensa', 1 );

/**
 * Newsletter: valida y avisa por correo al equipo, luego vuelve al listado
 * con el estado en la URL.
 */
function match_handle_newsletter(): void {
	$back = match_blog_url();

	if ( ! isset( $_POST['match_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['match_newsletter_nonce'] ), 'match_newsletter' ) || ! empty( $_POST['match_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'newsletter', 'error', $back ) . '#newsletter' );
		exit;
	}

	$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$correo = sanitize_email( wp_unslash( $_POST['correo'] ?? '' ) );

	if ( ! is_email( $correo ) ) {
		wp_safe_redirect( add_query_arg( 'newsletter', 'error', $back ) . '#newsletter' );
		exit;
	}

	$to   = get_theme_mod( 'match_email', get_option( 'admin_email' ) );
	$sent = wp_mail( $to, '[Match] Nueva suscripción al newsletter', "Nombre: {$nombre}\nCorreo: {$correo}", array( 'Reply-To: ' . $correo ) );

	wp_safe_redirect( add_query_arg( 'newsletter', $sent ? 'ok' : 'error', $back ) . '#newsletter' );
	exit;
}
add_action( 'admin_post_match_newsletter', 'match_handle_newsletter' );
add_action( 'admin_post_nopriv_match_newsletter', 'match_handle_newsletter' );
