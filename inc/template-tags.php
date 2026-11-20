<?php

/**
 * Utilidades de plantilla.
 */

defined('ABSPATH') || exit;

/**
 * Meta de una vacante lista para pintar: jornada y lugar.
 */
function match_job_meta(int $job_id): array
{
	if (! match_job_board_active()) {
		return array();
	}

	$type    = (string) MJB_Helpers::get_meta($job_id, 'employment_type');
	$mode    = (string) MJB_Helpers::get_meta($job_id, 'work_mode');
	$city    = (string) MJB_Helpers::get_meta($job_id, 'city');
	$country = (string) MJB_Helpers::get_meta($job_id, 'country');

	$place = array_filter(array($mode ? MJB_Helpers::work_mode_label($mode) : '', $city ?: $country));

	return array_values(
		array_filter(
			array(
				$type ? MJB_Helpers::employment_type_label($type) : '',
				$place ? implode(', ', $place) : '',
			)
		)
	);
}

/**
 * Salario formateado.
 */
function match_job_salary(int $job_id): string
{
	return match_job_board_active() ? MJB_Helpers::format_salary($job_id) : '';
}

/**
 * Enlace al archivo de vacantes, seguro aunque el plugin esté apagado.
 */
function match_jobs_url(): string
{
	if (! match_job_board_active()) {
		return home_url('/');
	}
	return (string) get_post_type_archive_link(MJB_Helpers::job_post_type());
}

/**
 * SVG inline desde assets/img/icons. Los iconos usan currentColor, así que
 * heredan el color del texto que los rodea.
 */
function match_icon(string $name, string $class = ''): string
{
	$file = get_theme_file_path("assets/img/icons/{$name}.svg");
	if (! file_exists($file)) {
		return '';
	}

	$attrs = 'aria-hidden="true" focusable="false"' . ($class ? ' class="' . esc_attr($class) . '"' : '');

	return (string) preg_replace('/<svg /', "<svg {$attrs} ", (string) file_get_contents($file), 1);
}

/**
 * Texto con partes en negrita marcadas con asteriscos: «*Alta dirección.* Resto»
 * → <strong>Alta dirección.</strong> Resto. Todo se escapa antes de marcar, así
 * que el editor no puede meter HTML; un asterisco suelto (sin cierre) se deja
 * tal cual. Lo usa la descripción del acordeón de soluciones (portada).
 */
function match_bold_text(string $text): string
{
	return (string) preg_replace('/\*([^*\n]+)\*/u', '<strong>$1</strong>', esc_html($text));
}

/**
 * Interior del botón CTA del Hero (.match-btn--cta): destellos, brillo, texto y,
 * opcionalmente, la flecha en círculo. El botón principal de todo el sitio usa
 * este mismo markup; app.js anima cada .match-btn--cta.
 */
function match_cta_inner(string $label, bool $arrow = true): string
{
	$html  = '<span class="match-btn__cta-inner">';
	$html .= '<span class="match-btn__cta-blob" aria-hidden="true"></span>';
	$html .= '<span class="match-btn__cta-blob" aria-hidden="true"></span>';
	$html .= '<span class="match-btn__cta-glow" aria-hidden="true"></span>';
	$html .= '<span class="match-btn__cta-label">' . esc_html($label) . '</span>';
	if ($arrow) {
		$html .= '<span class="match-btn__cta-arrow" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"/></svg></span>';
	}
	$html .= '</span>';

	return $html;
}

/**
 * Wordmark de Match. El texto va en currentColor: negro en la barra y el
 * pie, blanco sobre el hero. El isotipo conserva su celeste.
 *
 * $decorative: el SVG queda oculto a lectores de pantalla porque quien lo
 * llama ya pone el nombre en texto (el H1 del hero).
 */
function match_logo(string $class = '', bool $decorative = false): string
{
	$file = get_theme_file_path('assets/img/match-logo.svg');
	if (! file_exists($file)) {
		return '<span class="match-wordmark"' . ($decorative ? ' aria-hidden="true"' : '') . '>' . esc_html(get_bloginfo('name')) . '</span>';
	}

	$attrs = ($decorative ? 'aria-hidden="true" focusable="false"' : 'role="img" aria-label="' . esc_attr(get_bloginfo('name')) . '"') . ($class ? ' class="' . esc_attr($class) . '"' : '');
	$svg   = (string) file_get_contents($file);

	// El logo se pinta varias veces por página (navbar, hero, pie). Si los id
	// del degradado se repiten, url(#id) apunta al primero, y cuando ese está
	// oculto (el logo del navbar en mobile) el isotipo se queda sin relleno.
	static $count = 0;
	$suffix = '-' . (++$count);
	$svg    = (string) preg_replace('/\bid="([^"]+)"/', 'id="$1' . $suffix . '"', $svg);
	$svg    = (string) preg_replace('/url\(#([^)]+)\)/', 'url(#$1' . $suffix . ')', $svg);

	return (string) preg_replace('/<svg /', "<svg {$attrs} ", $svg, 1);
}

/**
 * Columna del pie. Igual que arriba: menú asignado o enlaces del diseño.
 */
function match_footer_menu(string $location): void
{
	if (has_nav_menu($location)) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'match-footer__links',
				'depth'          => 1,
			)
		);
		return;
	}

	// Job Board: "Subir mi CV" va al perfil (donde se sube el CV) y "Destacado" al
	// panel del Job Board. Sin sesión, match_profile_guard() manda el perfil al
	// login con vuelta, y el panel muestra su versión pública; con sesión,
	// "Iniciar sesión" pasa a "Mi panel". Si el Job Board no está montado, todo
	// cae en el listado de vacantes.
	$panel   = function_exists('match_jobboard_url') ? match_jobboard_url() : match_jobs_url();
	$profile = function_exists('match_jobboard_page_url') ? match_jobboard_page_url('template-profile.php') : '';
	$account = is_user_logged_in()
		? array(__('Mi panel', 'match'), $panel)
		: array(__('Iniciar sesión', 'match'), wp_login_url());

	$fallback = array(
		'footer_jobs'   => array(
			array(__('Subir mi CV', 'match'), $profile ?: $panel),
			$account,
			array(__('Destacado', 'match'), $panel),
		),
		'footer_nav'    => array(
			array(__('Inicio', 'match'), home_url('/')),
			array(__('Vacantes', 'match'), match_jobs_url()),
			array(__('Soluciones', 'match'), home_url('/#soluciones')),
		),
		'footer_social' => array(
			array('LinkedIn', 'https://www.linkedin.com/'),
			array('Instagram', 'https://www.instagram.com/'),
			array('X (Twitter)', 'https://x.com/'),
		),
	);

	if (empty($fallback[$location])) {
		return;
	}

	echo '<ul class="match-footer__links">';
	foreach ($fallback[$location] as list($label, $url)) {
		printf('<li><a href="%s">%s</a></li>', esc_url($url), esc_html($label));
	}
	echo '</ul>';
}

/**
 * Logos de clientes del diseño (marquee del hero y grilla de testimonios).
 * Archivos en assets/img/logos/{slug}.png.
 */
function match_client_logos(): array
{
	return array(
		'mmg'        => 'MMG',
		'ferreycorp' => 'Ferreycorp',
		'elektra'    => 'Elektra',
		'beat'       => 'Beat',
		'credicorp'  => 'Credicorp',
		'olx'        => 'OLX',
		'abb'        => 'ABB',
		'intercorp'  => 'Intercorp',
		'alfa-laval' => 'Alfa Laval',
		'aenza'      => 'Aenza',
	);
}

/**
 * Vacantes publicadas (para el "+N posiciones" del hero).
 */
function match_open_jobs_count(): int
{
	if (! match_job_board_active()) {
		return 25;
	}
	$count = (int) wp_count_posts(MJB_Helpers::job_post_type())->publish;
	return $count > 0 ? $count : 25;
}

/**
 * Datos de una vacante listos para la fila de la portada.
 */
function match_job_data(int $job_id): array
{
	$city    = (string) MJB_Helpers::get_meta($job_id, 'city');
	$country = (string) MJB_Helpers::get_meta($job_id, 'country');
	$mode    = (string) MJB_Helpers::get_meta($job_id, 'work_mode');
	$type    = (string) MJB_Helpers::get_meta($job_id, 'employment_type');

	return array(
		'id'       => (string) $job_id,
		'company'  => (string) MJB_Helpers::get_meta($job_id, 'company_name'),
		'logo'     => (string) MJB_Helpers::get_meta($job_id, 'company_logo'),
		'title'    => get_the_title($job_id),
		'url'      => (string) get_permalink($job_id),
		'salary'   => MJB_Helpers::format_salary($job_id),
		'type'     => $type ? MJB_Helpers::employment_type_label($type) : '',
		'place'    => trim(implode(', ', array_filter(array($mode ? MJB_Helpers::work_mode_label($mode) : '', $city ?: $country))), ', '),
		'skills'   => (array) MJB_Helpers::get_meta($job_id, 'skills', array()),
		'date'     => get_the_date('j M, Y', $job_id),
		'datetime' => get_the_date('c', $job_id),
		'saved'    => is_user_logged_in() && MJB_Query::is_saved($job_id),
		'submarcas' => match_job_submarcas($job_id),
	);
}

/**
 * Submarcas de Match que pueden etiquetar una vacante (slug => nombre), las
 * del manual de marca 2026: son los valores de `sub_brand` en el CRM.
 * El color de cada una vive en CSS: .match-submarca--{slug}.
 */
function match_submarcas_catalog(): array
{
	return array(
		'executive'    => 'Executive',
		'personnel'    => 'Personnel',
		'outplacement' => 'Outplacement',
		'assessment'   => 'Assessment',
	);
}

/**
 * Lleva un valor cualquiera («Executive», «EXECUTIVE SEARCH», «assessment»…)
 * al slug del catálogo; vacío si no se reconoce.
 */
function match_submarca_slug($raw): string
{
	if (is_array($raw)) {
		$raw = $raw['slug'] ?? $raw['value'] ?? $raw['name'] ?? $raw['label'] ?? '';
	}
	$value = sanitize_title((string) $raw);
	if ('' === $value) {
		return '';
	}
	foreach (array_keys(match_submarcas_catalog()) as $slug) {
		if ($value === $slug || str_starts_with($value, $slug . '-')) {
			return $slug;
		}
	}
	return '';
}

/**
 * Normaliza una lista cruda (array o texto separado por comas) a
 * [['slug' => …, 'label' => …], …], sin repetidos y en el orden del catálogo.
 */
function match_submarcas_normalize($raw): array
{
	if (is_string($raw)) {
		$raw = explode(',', $raw);
	}
	$found = array();
	foreach ((array) $raw as $item) {
		$slug = match_submarca_slug(is_string($item) ? trim($item) : $item);
		if ($slug) {
			$found[$slug] = true;
		}
	}

	$list = array();
	foreach (match_submarcas_catalog() as $slug => $label) {
		if (isset($found[$slug])) {
			$list[] = array('slug' => $slug, 'label' => $label);
		}
	}
	return $list;
}

/**
 * Slugs de las submarcas que nombra un texto de búsqueda: «exec» o
 * «executive search» → executive. Pide al menos 3 letras.
 */
function match_submarcas_search(string $q): array
{
	$needle = mb_strtolower(trim($q));
	if (mb_strlen($needle) < 3) {
		return array();
	}

	$slugs = array();
	foreach (match_submarcas_catalog() as $slug => $label) {
		$label = mb_strtolower($label);
		if (false !== mb_strpos($label, $needle) || false !== mb_strpos($needle, $label)) {
			$slugs[] = $slug;
		}
	}
	return $slugs;
}

/**
 * Submarcas de una vacante. Es el único punto que sabe de dónde salen:
 *  1. La meta «submarcas» que guarda el sync a partir de `sub_brand` del
 *     CRM (MJB_Mapper). Sin submarca (null) la vacante no lleva chip.
 *  2. El campo manual del admin (pcf-json/group_match_vacante.json).
 *  3. El filtro match/job_submarcas, para otra fuente sin tocar el tema.
 */
function match_job_submarcas(int $job_id): array
{
	$raw = MJB_Helpers::get_meta($job_id, 'submarcas', array());
	if (empty($raw) && function_exists('get_field')) {
		$raw = get_field('submarcas', $job_id);
	}

	return (array) apply_filters('match/job_submarcas', match_submarcas_normalize($raw ?: array()), $job_id, $raw);
}

/**
 * Vacantes de la portada: las últimas del Job Board (con los filtros de la
 * URL) o, si aún no hay sincronización, las cuatro filas de ejemplo.
 */
function match_home_jobs(int $count = 4): array
{
	return match_jobs_list(match_jobs_current_filters(), $count)['jobs'];
}

/**
 * Las cuatro filas de ejemplo del diseño, para revisar la maqueta sin CRM.
 */
function match_demo_jobs(): array
{
	$jobs = array();
	$logo = fn(string $slug): string => get_theme_file_uri("assets/img/logos/{$slug}.png");
	// Las cuatro del diseño más dos, para que los filtros de ejemplo tengan
	// con qué trabajar. La última columna son los valores de filtro
	// (ubicación, nivel, modalidad, industria) que usa inc/jobs-filters.php.
	$demo = array(
		array('novatech', 'NovaTech', 'Senior Product Manager de Growth', 'S/ 9,000–13,000 /mes', 'Tiempo completo, ejecutivo', 'Remoto, Perú', array('Roadmap', 'Agile', 'Stakeholders', 'Priorización'), array('Perú', 'Ejecutivo', 'Remoto', 'Tecnología')),
		array('intercorp', 'Grupo Intercorp', 'Analista de Datos y Business Intelligence', 'S/ 6,000–8,500 /mes', 'Tiempo completo', 'Híbrido, Lima', array('SQL', 'Power BI', 'ETL'), array('Lima', 'Analista', 'Híbrido', 'Retail')),
		array('abb', 'ABB Technology', 'Community Manager de Redes Sociales', 'S/ 3,500–5,000 /mes', 'Tiempo completo', 'Presencial, Lima', array('Contenido', 'Meta Ads', 'Analítica'), array('Lima', 'Junior', 'Presencial', 'Industrial')),
		array('mmg', 'MMG Limited', 'Gerente de Finanzas Corporativas', 'S/ 10,000–14,000 /mes', 'Tiempo completo, ejecutivo', 'Presencial, Lima', array('Finanzas', 'Tesorería', 'Minería'), array('Lima', 'Gerente', 'Presencial', 'Minería')),
		array('beat', 'Beat', 'Country Manager', 'S/ 15,000–20,000 /mes', 'Tiempo completo, ejecutivo', 'Híbrido, Lima', array('Liderazgo', 'P&L', 'Expansión'), array('Lima', 'Gerente', 'Híbrido', 'Tecnología')),
		array('alfa-laval', 'Alfa Laval', 'Ingeniero de Ventas Industriales', 'S/ 7,000–9,000 /mes', 'Tiempo completo', 'Remoto, Arequipa', array('Ventas B2B', 'Procesos', 'CRM'), array('Arequipa', 'Senior', 'Remoto', 'Industrial')),
	);

	// Submarcas de ejemplo, en el mismo orden que $demo.
	$submarcas = array(
		array('executive'),
		array('personnel'),
		array(),
		array('executive'),
		array('executive', 'personnel'),
		array('personnel'),
	);

	foreach ($demo as $i => list($slug, $company, $title, $salary, $type, $place, $skills, $filters)) {
		$jobs[] = array(
			'id'       => 'demo-' . $i,
			'company'  => $company,
			'logo'     => $logo($slug),
			'title'    => $title,
			'url'      => add_query_arg('vacante', 'demo-' . $i, match_jobboard_url()),
			'salary'   => $salary,
			'type'     => $type,
			'place'    => $place,
			'skills'   => $skills,
			'date'     => '8 abr, 2026',
			'datetime' => '2026-04-08',
			'saved'    => is_user_logged_in() && function_exists('match_saved_map') && isset(match_saved_map()['demo-' . $i]),
			'logo_bg'  => 'novatech' === $slug ? 'var(--match-azul-900)' : '', // logo blanco sobre transparente
			'submarcas' => match_submarcas_normalize($submarcas[$i] ?? array()),
			'filters'  => array_combine(array('ubicacion', 'nivel', 'modalidad', 'industria'), $filters),
		);
	}

	return $jobs;
}
