<?php
/**
 * Casillas con orden (editor de WordPress).
 *
 * Un checkbox de PCF guarda los valores en el orden en que llegan del
 * formulario, que es el orden de las casillas en pantalla. Para los campos
 * con la clase de envoltorio «pcf-ordered» (Ajustes del campo → wrapper):
 * - al abrir el editor, las casillas marcadas se ponen primero, en el orden
 *   guardado (filtro de abajo);
 * - al marcar una, pasa al final de las marcadas; al desmarcarla, vuelve con
 *   las demás; y se pueden arrastrar para cambiar el orden
 *   (assets/js/admin-orden-casillas.js).
 * Así la web muestra los logos en el orden en que se eligieron.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reordena las opciones para que las marcadas vayan primero, en el orden guardado.
 */
function match_pcf_ordered_choices( $field ) {
	if ( ! is_array( $field ) || 'checkbox' !== ( $field['type'] ?? '' ) || false === strpos( (string) ( $field['wrapper']['class'] ?? '' ), 'pcf-ordered' ) ) {
		return $field;
	}

	$choices = (array) ( $field['choices'] ?? array() );
	$ordered = array();

	foreach ( (array) ( $field['value'] ?? array() ) as $value ) {
		if ( isset( $choices[ $value ] ) ) {
			$ordered[ $value ] = $choices[ $value ];
		}
	}

	$field['choices'] = $ordered + $choices;
	return $field;
}
add_filter( 'pcf/prepare_field', 'match_pcf_ordered_choices' );

/**
 * Script y estilos de las casillas ordenables, solo en el editor de entradas/páginas.
 */
function match_pcf_ordered_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$path = 'assets/js/admin-orden-casillas.js';
	wp_enqueue_script( 'match-admin-orden-casillas', get_theme_file_uri( $path ), array( 'jquery', 'jquery-ui-sortable' ), match_asset_ver( $path ), true );

	wp_add_inline_style(
		'wp-admin',
		'.pcf-ordered .pcf-choice-list { counter-reset: pcf-orden; }
		.pcf-ordered .pcf-choice-list li { cursor: grab; }
		.pcf-ordered .pcf-choice-list li.is-checked label::before { counter-increment: pcf-orden; content: counter( pcf-orden ); display: inline-grid; place-items: center; width: 18px; height: 18px; margin-inline-end: 6px; border-radius: 50%; background: #2271b1; color: #fff; font-size: 11px; font-weight: 600; vertical-align: middle; }
		.pcf-ordered .pcf-choice-list .ui-sortable-helper { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.15); }'
	);
}
add_action( 'admin_enqueue_scripts', 'match_pcf_ordered_assets' );
