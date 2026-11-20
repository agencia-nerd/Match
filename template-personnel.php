<?php

/**
 * Template Name: Solución — Personnel
 *
 * Página interna independiente de Personnel.
 */
defined('ABSPATH') || exit;

$solucion = match_solucion(get_queried_object_id());

add_filter(
	'body_class',
	static function (array $classes) use ($solucion): array {
		$classes[] = 'match-sol--' . sanitize_html_class($solucion['theme']);
		return $classes;
	}
);

get_header();

get_template_part('template-parts/solucion/hero', null, $solucion);
get_template_part('template-parts/solucion/metricas', null, $solucion);
get_template_part('template-parts/solucion/diferenciador', null, $solucion);
get_template_part('template-parts/solucion/perfiles', null, $solucion);
get_template_part('template-parts/solucion/clientes', null, $solucion);
get_template_part('template-parts/solucion/testimonios', null, $solucion);
get_template_part('template-parts/section', 'contacto', array('lead' => $solucion['contact_lead'], 'lead_muted' => ''));
get_template_part('template-parts/solucion/toast', null, $solucion);
get_template_part('template-parts/contact-modal');

get_footer();
