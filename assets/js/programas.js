/**
 * Programas — resalta la tarjeta a la que lleva un ancla (los botones del
 * hero: #regular, #executive…). El scroll suave lo hace app.js (Lenis); aquí
 * solo se espera a que la tarjeta entre en pantalla para resaltarla, así el
 * usuario ve el efecto al llegar y no durante el recorrido. También se
 * resalta si se entra a la página con el ancla en la URL.
 */
( function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) ) { return; }

	function cardFor( hash ) {
		var el = hash && hash.length > 1 ? document.getElementById( decodeURIComponent( hash.slice( 1 ) ) ) : null;
		return el && el.classList.contains( 'match-prog-card' ) ? el : null;
	}

	function highlight( card ) {
		card.classList.remove( 'is-highlighted' );
		void card.offsetWidth; // Reinicia la animación si se vuelve a pulsar.
		card.classList.add( 'is-highlighted' );
	}

	function highlightWhenVisible( card ) {
		var done = false;
		var observer = new IntersectionObserver( function ( entries ) {
			if ( entries[0].isIntersecting ) { finish(); }
		}, { threshold: 0.4 } );

		function finish() {
			if ( done ) { return; }
			done = true;
			observer.disconnect();
			highlight( card );
		}

		observer.observe( card );
		setTimeout( finish, 1600 ); // Por si la tarjeta nunca llega al 40 % visible.
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( 'a[href*="#"]' );
		if ( ! link || link.pathname !== window.location.pathname ) { return; }
		var card = cardFor( link.hash );
		if ( card ) { highlightWhenVisible( card ); }
	} );

	/* Botón «Solicitar información» de una tarjeta: el scroll al formulario lo
	 * hace app.js; aquí se deja elegido ese programa en el selector. */
	document.addEventListener( 'click', function ( event ) {
		var cta    = event.target.closest( 'a[data-programa]' );
		var select = cta && document.querySelector( 'select[data-contact-programa]' );
		if ( ! select ) { return; }
		Array.prototype.some.call( select.options, function ( option ) {
			if ( option.value !== cta.getAttribute( 'data-programa' ) ) { return false; }
			select.value = option.value;
			select.classList.remove( 'is-picked' );
			void select.offsetWidth;
			select.classList.add( 'is-picked' );
			return true;
		} );
	} );

	document.addEventListener( 'animationend', function ( event ) {
		if ( 'match-prog-card-focus' === event.animationName ) { event.target.classList.remove( 'is-highlighted' ); }
	} );

	var initial = cardFor( window.location.hash );
	if ( initial ) {
		window.addEventListener( 'load', function () { highlightWhenVisible( initial ); }, { once: true } );
	}

	/* --- Por qué Match: cifras que cuentan y anillo que se llena ---
	 * Cuenta el primer número de cada [data-count] y conserva lo que lo rodea
	 * («+99k», «7/10», «x3», «71%»). El HTML trae el valor final: sin JS o con
	 * prefers-reduced-motion se ve tal cual. Corre una sola vez.
	 */
	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var DURATION = 1800;
	var easeOutExpo = function ( t ) { return t >= 1 ? 1 : 1 - Math.pow( 2, -10 * t ); };

	function counter( el ) {
		var match = el.textContent.trim().match( /^(\D*?)(\d+(?:[.,]\d+)?)([\s\S]*)$/ );
		if ( ! match ) { return null; }
		var sep      = match[2].indexOf( ',' ) > -1 ? ',' : '.';
		var decimals = ( match[2].split( sep )[1] || '' ).length;
		var target   = parseFloat( match[2].replace( ',', '.' ) );
		var render   = function ( value ) { el.textContent = match[1] + value.toFixed( decimals ).replace( '.', sep ) + match[3]; };
		return { render: render, target: target };
	}

	Array.prototype.forEach.call( document.querySelectorAll( '[data-prog-stats]' ), function ( section ) {
		if ( reducedMotion ) { return; }

		var counters = Array.prototype.map.call( section.querySelectorAll( '[data-count]' ), counter ).filter( Boolean );
		counters.forEach( function ( c ) { c.render( 0 ); } );
		section.classList.add( 'is-pending' );

		var observer = new IntersectionObserver( function ( entries ) {
			if ( ! entries[0].isIntersecting ) { return; }
			observer.disconnect();
			section.classList.remove( 'is-pending' ); // El anillo se llena por transición CSS.

			var start = null;
			requestAnimationFrame( function tick( now ) {
				if ( null === start ) { start = now; }
				var p = Math.min( 1, ( now - start ) / DURATION );
				counters.forEach( function ( c ) { c.render( c.target * easeOutExpo( p ) ); } );
				if ( p < 1 ) { requestAnimationFrame( tick ); }
			} );
		}, { threshold: 0.35 } );

		observer.observe( section.querySelector( '.match-prog-stats__grid' ) || section );
	} );
} )();
