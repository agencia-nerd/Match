<?php
/**
 * Template Name: Programas
 *
 * Programas de una solución (Figma: Hero — Programas, node 5467:4752).
 * El contenido sale de los campos PCF de la página
 * (pcf-json/group_match_programas.json).
 */
defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/programas/hero' );
get_template_part( 'template-parts/programas/programas' );
get_template_part( 'template-parts/programas/por-que' );
get_template_part( 'template-parts/programas/faq' );

// Contacto compartido con la portada y Solución, con el selector de programas
// armado desde las tarjetas de arriba.
$match_prog_field = static fn( string $name ) => function_exists( 'get_field' ) ? get_field( $name, get_queried_object_id() ) : null;
get_template_part(
	'template-parts/section',
	'contacto',
	array(
		'lead'        => $match_prog_field( 'prog_contacto_lead' ) ?: __( 'Cuéntanos tu caso — hablemos del programa que mejor se ajusta a tu situación.', 'match' ),
		'lead_muted'  => '',
		'programas'   => wp_list_pluck( array_filter( (array) $match_prog_field( 'prog_programas' ), static fn( $row ) => ! empty( $row['titulo'] ) ), 'titulo' ),
		'placeholder' => __( 'Cuéntanos qué programa te interesa.', 'match' ),
	)
);

get_template_part( 'template-parts/contact-modal' );

get_footer();
