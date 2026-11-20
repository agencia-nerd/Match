/**
 * Casillas con orden (ver inc/admin-orden-casillas.php).
 *
 * El orden de las casillas en pantalla es el orden en que se guardan. Al
 * marcar una, se mueve al final de las marcadas; al desmarcarla, al inicio
 * de las no marcadas. También se pueden arrastrar. Las marcadas llevan un
 * número con su posición en la web.
 */
( function ( $ ) {
	'use strict';

	function refresh( $list ) {
		$list.children( 'li' ).each( function () {
			$( this ).toggleClass( 'is-checked', $( this ).find( 'input[type="checkbox"]' ).prop( 'checked' ) );
		} );
	}

	function init( root ) {
		$( root ).find( '.pcf-ordered .pcf-choice-list' ).each( function () {
			var $list = $( this );
			if ( $list.data( 'matchOrdered' ) ) { return; }
			$list.data( 'matchOrdered', true );

			refresh( $list );

			$list.on( 'change', 'input[type="checkbox"]', function () {
				// Justo después de las demás marcadas: al marcar queda última de
				// ellas; al desmarcar, primera de las no marcadas.
				var $li      = $( this ).closest( 'li' );
				var $checked = $list.children( 'li.is-checked' ).not( $li );

				$checked.length ? $li.insertAfter( $checked.last() ) : $list.prepend( $li );
				refresh( $list );
			} );

			if ( $.fn.sortable ) {
				$list.sortable( {
					items: '> li',
					tolerance: 'pointer',
					cancel: 'input',
					update: function () { refresh( $list ); }
				} );
			}
		} );
	}

	$( function () { init( document ); } );
	// Repeaters y bloques que PCF agrega después de cargar.
	$( document ).on( 'pcf/append', function ( e, el ) { init( el || document ); } );
} )( jQuery );
