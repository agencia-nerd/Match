/**
 * Match — animaciones de entrada con GSAP + ScrollTrigger + SplitText.
 *
 * Reemplaza a AOS en las vistas de match_uses_gsap() (por ahora la portada).
 * Atributos:
 *   data-reveal="hero-logo"  h1 del hero: las letras del SVG suben una a una
 *   data-reveal="hero-lead"  lead del hero: entra por líneas (no se oculta del
 *                            todo, ver match_reveal_boot en functions.php)
 *   data-reveal="hero"       tarjeta y franja inferior del hero
 *   data-reveal="head"       cabecera de sección: eyebrow + h2 por líneas
 *   data-reveal="title"      título suelto por líneas (contacto)
 *   data-reveal="up"         sube y aparece (con escala en acordeón/métricas)
 *   data-reveal="rows"       anima sus hijos uno a uno (filas de vacantes)
 *   data-reveal="left"       entra desde la izquierda con giro 3D (en móvil, sube)
 * Además, en escritorio: parallax con scrub en la foto del hero y en el fondo
 * de contacto.
 *
 * Rendimiento: solo transform y opacity; un único ScrollTrigger.batch para
 * todo lo que entra con scroll, once: true y scrub solo en el parallax;
 * SplitText se aplica al entrar en pantalla y se revierte al terminar;
 * will-change solo mientras dura cada animación. Al terminar se limpian los
 * estilos en línea y el elemento queda marcado con .is-revealed.
 */
( function () {
	'use strict';

	var html = document.documentElement;
	var gsap = window.gsap;
	var ScrollTrigger = window.ScrollTrigger;
	var SplitText = window.SplitText;

	// Si el arranque de functions.php ya retiró .has-reveal (GSAP tardó
	// demasiado) el contenido está visible: animarlo ahora sería un parpadeo.
	if ( ! gsap || ! ScrollTrigger || ! SplitText || ! html.classList.contains( 'has-reveal' ) ) {
		return;
	}

	window.matchReveal = true;
	gsap.registerPlugin( ScrollTrigger, SplitText );

	// Lenis mueve el scroll nativo; así ScrollTrigger lo lee en el mismo frame.
	if ( window.matchLenis ) {
		window.matchLenis.on( 'scroll', ScrollTrigger.update );
	}

	var CLEAR = 'transform,transformOrigin,transformPerspective,opacity,visibility,willChange';
	var cfg; // valores del breakpoint activo (ver mm.add)

	function type( el ) {
		return el.getAttribute( 'data-reveal' );
	}

	function pending( selector ) {
		return gsap.utils.toArray( selector + ':not(.is-revealed)' );
	}

	function will( targets ) {
		return function () { gsap.set( targets, { willChange: 'transform, opacity' } ); };
	}

	function done( els, targets ) {
		gsap.set( targets, { clearProps: CLEAR } );
		els.forEach( function ( el ) { el.classList.add( 'is-revealed' ); } );
		gsap.set( els, { clearProps: 'visibility' } );
	}

	// Estado de partida de cada elemento según su tipo/componente.
	function fromVars( t ) {
		var cl = t.classList;
		var k = cfg.k;
		if ( cl.contains( 'match-accordion__panel' ) ) { return { y: cfg.y + 20 * k, scale: 1 - 0.06 * k, transformOrigin: '50% 100%' }; }
		if ( cl.contains( 'match-stat' ) || cl.contains( 'match-stat-hero' ) ) { return { y: cfg.y, scale: 1 - 0.08 * k }; }
		if ( cl.contains( 'match-hero__card' ) ) { return { y: cfg.y * 0.66, scale: 1 - 0.04 * k }; }
		if ( cl.contains( 'match-hero__bottom' ) || cl.contains( 'match-job' ) ) { return { y: cfg.y * 0.5 }; }
		if ( type( t ) === 'left' && cfg.x ) { return { x: cfg.x, rotationY: cfg.rot, transformPerspective: 1200, transformOrigin: '0% 50%' }; }
		return { y: cfg.y };
	}

	// Elementos que realmente se mueven: "rows" anima sus hijos.
	function targetsOf( els ) {
		var targets = [];
		els.forEach( function ( el ) {
			if ( type( el ) === 'rows' && el.children.length ) {
				gsap.set( el, { visibility: 'visible' } );
				targets.push.apply( targets, el.children );
			} else {
				targets.push( el );
			}
		} );
		return targets;
	}

	function tween( els, vars ) {
		var targets = targetsOf( els );
		var fv = targets.map( fromVars );
		var get = function ( prop, base ) {
			return function ( i ) { return prop in fv[ i ] ? fv[ i ][ prop ] : base; };
		};
		return gsap.fromTo( targets, {
			autoAlpha: 0,
			x: get( 'x', 0 ),
			y: get( 'y', 0 ),
			scale: get( 'scale', 1 ),
			rotationY: get( 'rotationY', 0 ),
			transformOrigin: get( 'transformOrigin', '50% 50%' ),
			transformPerspective: get( 'transformPerspective', 0 ),
		}, Object.assign( {
			autoAlpha: 1,
			x: 0,
			y: 0,
			scale: 1,
			rotationY: 0,
			duration: cfg.duration,
			ease: 'expo.out',
			stagger: cfg.stagger,
			overwrite: true,
			onStart: will( targets ),
			onComplete: function () { done( els, targets ); },
		}, vars ) );
	}

	// Títulos: líneas que emergen desde una máscara. Se parte al entrar en
	// pantalla (fuentes ya cargadas, ancho real) y se revierte al terminar.
	function revealTitle( el, delay ) {
		var head = type( el ) === 'head';
		var title = head ? el.querySelector( 'h2' ) : el;
		var eyebrow = head ? el.querySelector( '.match-eyebrow' ) : null;
		var split = SplitText.create( title, { type: 'lines', mask: 'lines' } );
		var tl = gsap.timeline( {
			delay: delay || 0,
			onComplete: function () {
				split.revert();
				done( [ el ], eyebrow ? [ eyebrow ] : [] );
			},
		} );

		gsap.set( el, { visibility: 'visible' } );
		if ( eyebrow ) {
			tl.fromTo( eyebrow, { autoAlpha: 0, x: -20 }, { autoAlpha: 1, x: 0, duration: 0.8, ease: 'power3.out' }, 0 );
		}
		tl.fromTo( split.lines, { yPercent: 105 }, {
			yPercent: 0,
			duration: cfg.duration,
			ease: 'expo.out',
			stagger: 0.1,
			onStart: will( split.lines ),
		}, eyebrow ? 0.1 : 0 );
		return tl;
	}

	function heroIntro() {
		var tl = gsap.timeline();
		var img = pending( '.match-hero__media img' );
		var logo = pending( '[data-reveal="hero-logo"]' );
		var lead = pending( '[data-reveal="hero-lead"]' );
		var rest = pending( '[data-reveal="hero"]' );

		// Zoom-out lento de la foto (solo transform: no afecta al LCP).
		if ( img.length ) {
			tl.fromTo( img, { scale: 1.12 }, {
				scale: 1,
				duration: 1.8,
				ease: 'power2.out',
				onStart: will( img ),
				onComplete: function () { done( img, img ); },
			}, 0 );
		}

		// Letras del logo: el <svg> recorta por su viewBox y hace de máscara.
		if ( logo.length ) {
			var paths = logo[ 0 ].querySelectorAll( 'path' );
			gsap.set( logo, { visibility: 'visible' } );
			tl.fromTo( paths, { yPercent: 110 }, {
				yPercent: 0,
				duration: 0.9,
				ease: 'expo.out',
				stagger: 0.06,
				onStart: will( paths ),
				onComplete: function () { done( logo, paths ); },
			}, 0.1 );
		}

		// Lead por líneas, sin máscara: parte de opacity .01 (candidato LCP).
		if ( lead.length ) {
			var split = SplitText.create( lead[ 0 ], { type: 'lines' } );
			gsap.set( lead, { opacity: 1 } );
			tl.fromTo( split.lines, { opacity: 0.01, y: 20 }, {
				opacity: 1,
				y: 0,
				duration: 0.8,
				ease: 'power3.out',
				stagger: 0.08,
				onStart: will( split.lines ),
				onComplete: function () { split.revert(); done( lead, lead ); },
			}, 0.45 );
		}

		if ( rest.length ) {
			tl.add( tween( rest, { stagger: 0.15 } ), 0.65 );
		}
	}

	function parallax( target, trigger, from, to, start ) {
		var el = document.querySelector( target );
		if ( ! el ) { return; }
		gsap.fromTo( el, from, Object.assign( {
			ease: 'none',
			scrollTrigger: {
				trigger: trigger,
				start: start,
				end: 'bottom top',
				scrub: true,
				onToggle: function ( self ) { el.style.willChange = self.isActive ? 'transform' : ''; },
			},
		}, to ) );
	}

	function init() {
		var mm = gsap.matchMedia();

		mm.add( {
			desktop: '(min-width: 768px) and (prefers-reduced-motion: no-preference)',
			mobile: '(max-width: 767px) and (prefers-reduced-motion: no-preference)',
			reduce: '(prefers-reduced-motion: reduce)',
		}, function ( context ) {
			var c = context.conditions;

			// Movimiento reducido (también si se activa con la página abierta).
			if ( c.reduce ) {
				html.classList.remove( 'has-reveal' );
				return;
			}

			// Móvil: menos recorrido y escala, más corto, sin 3D ni parallax.
			cfg = c.desktop
				? { y: 60, x: -60, rot: 8, k: 1, duration: 1.1, stagger: 0.1 }
				: { y: 24, x: 0, rot: 0, k: 0.5, duration: 0.6, stagger: 0.05 };

			// Con preloader, el hero entra cuando el telón empieza a subir.
			if ( html.classList.contains( 'match-preloading' ) && ! window.matchPreloaderExit ) {
				window.addEventListener( 'match:preloader-exit', heroIntro, { once: true } );
			} else {
				heroIntro();
			}

			/* Todo lo demás: un solo batch. Lo que entra junto en pantalla
			 * comparte una cascada; los títulos van primero. */
			var items = pending( '[data-reveal]' ).filter( function ( el ) {
				return type( el ).indexOf( 'hero' ) !== 0;
			} );

			ScrollTrigger.batch( items, {
				start: 'top 88%',
				once: true,
				batchMax: 8,
				onEnter: function ( batch ) {
					var titles = batch.filter( function ( el ) { return type( el ) === 'head' || type( el ) === 'title'; } );
					var rest = batch.filter( function ( el ) { return titles.indexOf( el ) === -1; } );
					titles.forEach( function ( el, i ) { revealTitle( el, i * 0.1 ); } );
					if ( rest.length ) { tween( rest, { delay: titles.length ? 0.2 : 0 } ); }
				},
			} );

			if ( c.desktop ) {
				// Foto del hero (en su contenedor, no el img del zoom): baja más
				// lento que el scroll; el hueco superior queda fuera de pantalla.
				parallax( '.match-hero__media', '.match-hero', { yPercent: 0 }, { yPercent: 18 }, 'top top' );
				parallax( '.match-contacto__bg img', '.match-contacto', { scale: 1.15, yPercent: -6 }, { scale: 1.15, yPercent: 6 }, 'top bottom' );
			}
		} );

		// Las imágenes lazy pueden mover el layout tras el arranque.
		if ( document.readyState === 'complete' ) {
			ScrollTrigger.refresh();
		} else {
			window.addEventListener( 'load', function () { ScrollTrigger.refresh(); }, { once: true } );
		}
	}

	// SplitText necesita las fuentes finales para medir las líneas. Están
	// precargadas, así que suele ser inmediato; como máximo se espera 1 s.
	var fonts = document.fonts && document.fonts.ready
		? Promise.race( [ document.fonts.ready, new Promise( function ( r ) { setTimeout( r, 1000 ); } ) ] )
		: Promise.resolve();

	fonts.then( init );
} )();
