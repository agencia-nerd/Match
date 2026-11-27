/**
 * Match — interacciones del tema.
 * Vanilla con mejora progresiva. Dependencias (assets/vendor): AOS para las
 * animaciones de entrada (en las vistas que aún no migran a GSAP, ver
 * reveal.js), Embla para los carruseles y Lenis para el scroll suave.
 */
( function () {
	'use strict';

	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* --- Scroll suave (Lenis) ---
	 * Usa el scroll nativo (no transform), así AOS, ScrollTrigger,
	 * IntersectionObserver y los anclas siguen funcionando. Se omite con
	 * prefers-reduced-motion. Se expone en window.matchLenis para que reveal.js
	 * sincronice ScrollTrigger con su scroll.
	 */
	var lenis = null;

	if ( window.Lenis && ! reducedMotion ) {
		lenis = new window.Lenis( { autoRaf: true, lerp: 0.1 } );
		window.matchLenis = lenis;

		// Sin scroll mientras corre el preloader (assets/js/preloader.js).
		if ( document.documentElement.classList.contains( 'match-preloading' ) ) {
			lenis.stop();
			window.addEventListener( 'match:preloader-done', function () { lenis.start(); }, { once: true } );
		}

		/* Anclas a mano en vez de la opción `anchors` de Lenis: esa mide con
		 * getBoundingClientRect, que incluye el translateY(100px) que AOS/GSAP
		 * ponen a las secciones aún no animadas, y el destino queda 100 px pasado.
		 * offsetTop ignora los transforms. El destino puede pedir más aire por
		 * arriba (el navbar es sticky) con scroll-margin-top en CSS, como
		 * .match-legal__body h2; sin eso se usan los 16 px de siempre.
		 */
		document.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[href*="#"]' );
			if ( ! link || link.origin !== window.location.origin || link.pathname !== window.location.pathname ) { return; }
			var target = link.hash.length > 1 ? document.getElementById( decodeURIComponent( link.hash.slice( 1 ) ) ) : null;
			if ( ! target ) { return; }
			event.preventDefault();
			var y = 0;
			for ( var el = target; el; el = el.offsetParent ) { y += el.offsetTop; }
			var margin = parseFloat( getComputedStyle( target ).scrollMarginTop ) || 16;
			lenis.scrollTo( Math.max( 0, y - margin ) );
			if ( window.history.pushState ) { window.history.pushState( null, '', link.hash ); }
		} );
	}

	/* --- Menú: mega menú de Soluciones (escritorio) y tarjeta mobile ---
	 * Adaptado de reference-match-nav/js/match-nav.js. Las animaciones usan
	 * la Web Animations API y se omiten con prefers-reduced-motion.
	 */
	var EASE_OUT   = 'cubic-bezier(.22,1,.36,1)';
	var EASE_IO    = 'cubic-bezier(.65,0,.35,1)';
	var mqMobile   = window.matchMedia( '(max-width: 1279px)' );
	var canAnimate = ! reducedMotion && typeof Element.prototype.animate === 'function';
	var closeMega  = function () {};

	// Si una animación queda "colgada" (pestaña en segundo plano, etc.) la completa.
	var settle = function ( el, ms ) {
		window.setTimeout( function () {
			el.getAnimations().forEach( function ( a ) { if ( a.playState !== 'finished' ) { a.finish(); } } );
		}, ms + 120 );
	};

	// Entrada en cascada: sube unos px y aparece.
	var rise = function ( el, delay, duration, dy ) {
		if ( ! canAnimate ) { return; }
		el.animate(
			[ { opacity: 0, transform: 'translateY(' + dy + 'px)' }, { opacity: 1, transform: 'none' } ],
			{ duration: duration, delay: delay, easing: EASE_OUT, fill: 'backwards' }
		);
		settle( el, delay + duration );
	};

	// Enlaces del mega menú: la flecha pasa a spinner mientras navega; con "#" no navega.
	var bindLoadingLink = function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			if ( link.classList.contains( 'is-loading' ) ) { event.preventDefault(); return; }
			var href = link.getAttribute( 'href' );
			if ( ! href || href === '#' ) { event.preventDefault(); return; }
			link.classList.add( 'is-loading' );
			link.setAttribute( 'aria-busy', 'true' );
		} );
	};

	// Al volver con "atrás" (bfcache) quita el spinner.
	window.addEventListener( 'pageshow', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '.match-mega__link.is-loading' ), function ( link ) {
			link.classList.remove( 'is-loading' );
			link.removeAttribute( 'aria-busy' );
		} );
	} );

	var mega = document.querySelector( '[data-mega]' );

	if ( mega ) {
		var megaBtn     = mega.querySelector( '.match-sol__btn' );
		var megaItems   = Array.prototype.slice.call( mega.querySelectorAll( '.match-mega__item' ) );
		var preview     = mega.querySelector( '[data-mega-preview]' );
		var pEyebrow    = mega.querySelector( '[data-mega-eyebrow]' );
		var pBody       = mega.querySelector( '[data-mega-body]' );
		var pMain       = mega.querySelector( '[data-mega-main]' );
		var pMainLabel  = mega.querySelector( '[data-mega-main-label]' );
		var pExtra      = mega.querySelector( '[data-mega-extra]' );
		var pExtraLabel = mega.querySelector( '[data-mega-extra-label]' );
		var megaOpen    = false;
		var megaActive  = 0;
		var megaTimer   = null;

		var animatePreview = function ( delay, duration ) {
			if ( ! canAnimate ) { return; }
			preview.getAnimations().forEach( function ( a ) { a.cancel(); } );
			preview.animate(
				[ { opacity: 0, transform: 'translateX(-12px)' }, { opacity: 1, transform: 'none' } ],
				{ duration: duration, delay: delay, easing: EASE_OUT, fill: 'backwards' }
			);
			settle( preview, delay + duration );
		};

		// Vista previa derecha: lee los data-* del ítem.
		var selectItem = function ( index ) {
			if ( index === megaActive ) { return; }
			megaActive = index;
			megaItems.forEach( function ( item, k ) { item.classList.toggle( 'is-active', k === index ); } );

			var data = megaItems[ index ].dataset;
			pEyebrow.textContent   = data.eyebrow || '';
			// data-body ya viene escapado desde PHP (match_bold_text): solo trae <strong>.
			pBody.innerHTML        = data.body || '';
			pMain.href             = megaItems[ index ].getAttribute( 'href' );
			pMainLabel.textContent = ( pMain.dataset.label || '%s' ).replace( '%s', data.name );

			pExtra.hidden = ! data.extraHref;
			if ( data.extraHref ) {
				pExtra.href             = data.extraHref;
				pExtraLabel.textContent = data.extraLabel || '';
			}

			if ( megaOpen ) { animatePreview( 0, 200 ); }
		};

		var setMega = function ( open ) {
			if ( open === megaOpen ) { return; }
			megaOpen = open;
			mega.classList.toggle( 'is-open', open );
			megaBtn.setAttribute( 'aria-expanded', String( open ) );

			if ( open ) {
				// 1) marco (CSS) → 2) lista en cascada → 3) vista previa
				megaItems.forEach( function ( item, i ) { rise( item, 80 + i * 40, 320, 8 ); } );
				animatePreview( 200, 340 );
			}
		};

		closeMega = function () { window.clearTimeout( megaTimer ); setMega( false ); };

		mega.addEventListener( 'mouseenter', function () {
			if ( mqMobile.matches ) { return; }
			window.clearTimeout( megaTimer );
			megaTimer = window.setTimeout( function () { setMega( true ); }, 90 );
		} );
		mega.addEventListener( 'mouseleave', function () {
			window.clearTimeout( megaTimer );
			megaTimer = window.setTimeout( function () { setMega( false ); }, 160 );
		} );
		megaBtn.addEventListener( 'click', function () {
			window.clearTimeout( megaTimer );
			setMega( ! megaOpen );
		} );

		megaItems.forEach( function ( item, i ) {
			item.addEventListener( 'mouseenter', function () { selectItem( i ); } );
			item.addEventListener( 'focus', function () { selectItem( i ); } );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && megaOpen ) { closeMega(); megaBtn.focus(); }
		} );
		document.addEventListener( 'click', function ( event ) {
			if ( megaOpen && ! mega.contains( event.target ) ) { closeMega(); }
		} );
		mqMobile.addEventListener( 'change', function ( event ) { if ( event.matches ) { closeMega(); } } );

		[ pMain, pExtra ].forEach( bindLoadingLink );
	}

	var toggle = document.querySelector( '.match-navbar__toggle' );
	var panel = document.getElementById( 'match-mobile-nav' );

	if ( toggle && panel ) {
		var acc       = panel.querySelector( '.match-mmenu__acc' );
		var accList   = document.getElementById( 'match-msol' );
		var panelAnim = null;

		var isPanelOpen = function () { return toggle.getAttribute( 'aria-expanded' ) === 'true'; };

		// Abre con un clip de arriba abajo + cascada; cierra con el clip inverso y luego [hidden].
		var setPanel = function ( open ) {
			toggle.setAttribute( 'aria-expanded', String( open ) );
			toggle.setAttribute( 'aria-label', open ? toggle.dataset.labelClose : toggle.dataset.labelOpen );
			document.body.classList.toggle( 'has-open-nav', open );
			if ( lenis ) { open ? lenis.stop() : lenis.start(); }

			if ( panelAnim ) { panelAnim.cancel(); panelAnim = null; }

			if ( open ) {
				panel.hidden = false;
				if ( canAnimate ) {
					panelAnim = panel.animate(
						[ { clipPath: 'inset(0 0 100% 0 round 22px)' }, { clipPath: 'inset(0 0 0 0 round 22px)' } ],
						{ duration: 380, easing: EASE_IO }
					);
					Array.prototype.forEach.call( panel.children, function ( el, i ) {
						if ( ! el.hidden ) { rise( el, 80 + i * 40, 300, 8 ); }
					} );
				}
				return;
			}

			if ( ! canAnimate || panel.hidden ) { panel.hidden = true; return; }

			panelAnim = panel.animate(
				[ { clipPath: 'inset(0 0 0 0 round 22px)' }, { clipPath: 'inset(0 0 100% 0 round 22px)' } ],
				{ duration: 260, easing: EASE_IO }
			);
			panelAnim.onfinish = function () { panel.hidden = true; panelAnim = null; };
			settle( panel, 260 );
		};

		toggle.addEventListener( 'click', function () { setPanel( ! isPanelOpen() ); } );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && isPanelOpen() ) {
				setPanel( false );
				toggle.focus();
			}
		} );

		// Clic/toque fuera de la tarjeta (y fuera de la hamburguesa) la cierra.
		document.addEventListener( 'click', function ( event ) {
			if ( isPanelOpen() && ! panel.contains( event.target ) && ! toggle.contains( event.target ) ) {
				setPanel( false );
			}
		} );

		mqMobile.addEventListener( 'change', function ( event ) {
			if ( ! event.matches && isPanelOpen() ) { setPanel( false ); }
		} );

		// Acordeón "Soluciones" dentro de la tarjeta.
		if ( acc && accList ) {
			acc.addEventListener( 'click', function () {
				var open = accList.hidden;
				accList.hidden = ! open;
				acc.setAttribute( 'aria-expanded', String( open ) );

				if ( open && canAnimate ) {
					accList.animate(
						[ { clipPath: 'inset(0 0 100% 0 round 16px)' }, { clipPath: 'inset(0 0 0 0 round 16px)' } ],
						{ duration: 320, easing: EASE_IO }
					);
					Array.prototype.forEach.call( accList.querySelectorAll( '.match-msol__item' ), function ( el, i ) {
						rise( el, 60 + i * 35, 280, 6 );
					} );
				}
			} );
		}
	}

	/* --- Acordeón de soluciones --- */
	var panels = Array.prototype.slice.call( document.querySelectorAll( '.match-accordion__panel' ) );

	panels.forEach( function ( item ) {
		var trigger = item.querySelector( '.match-accordion__trigger' );

		if ( ! trigger ) {
			return;
		}

		trigger.addEventListener( 'click', function () {
			panels.forEach( function ( other ) {
				var otherTrigger = other.querySelector( '.match-accordion__trigger' );
				var isTarget = other === item;

				other.classList.toggle( 'is-open', isTarget );
				if ( otherTrigger ) {
					otherTrigger.setAttribute( 'aria-expanded', isTarget ? 'true' : 'false' );
				}
			} );
		} );
	} );

	/* --- Contacto: selector de perfil --- */
	document.querySelectorAll( '.match-toggle__group' ).forEach( function ( group ) {
		var input = group.parentNode.querySelector( 'input[name="perfil"]' );
		group.querySelectorAll( '.match-toggle__opt' ).forEach( function ( opt ) {
			opt.addEventListener( 'click', function () {
				group.querySelectorAll( '.match-toggle__opt' ).forEach( function ( other ) {
					var active = other === opt;
					other.classList.toggle( 'is-active', active );
					other.setAttribute( 'aria-checked', active ? 'true' : 'false' );
				} );
				if ( input ) {
					input.value = opt.dataset.value;
				}
			} );
		} );
	} );

	/* --- Formulario emergente de contacto (template-parts/contact-modal.php) ---
	 * Lo abre cualquier enlace con data-contact-modal: marca el perfil
	 * (data-perfil), guarda servicio y programa en los campos ocultos y escribe
	 * «Estás consultando por …» (el programa si hay, si no el servicio). Se
	 * escucha en captura para adelantarse al scroll a #contacto de Lenis; sin
	 * <dialog> no se intercepta y el enlace baja a «Hablemos» como siempre.
	 * Tras enviar, la página vuelve con ?origen=modal y se reabre con el aviso.
	 */
	var contactModal = document.getElementById( 'match-contact-modal' );
	if ( contactModal && typeof contactModal.showModal === 'function' ) {
		var contactAbout = contactModal.querySelector( '[data-contact-about]' );
		var contactLabel = contactModal.querySelector( '[data-contact-label]' );
		var contactField = function ( name ) { return contactModal.querySelector( '[data-contact-field="' + name + '"]' ); };

		document.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest( '[data-contact-modal]' );
			if ( ! trigger ) { return; }
			event.preventDefault();
			event.stopPropagation();

			var perfil   = trigger.getAttribute( 'data-perfil' ) || 'empresa';
			var servicio = trigger.getAttribute( 'data-servicio' ) || '';
			var programa = trigger.getAttribute( 'data-programa' ) || '';
			var opt      = contactModal.querySelector( '.match-toggle__opt[data-value="' + perfil + '"]' );
			if ( opt ) { opt.click(); }
			contactField( 'servicio' ).value = servicio;
			contactField( 'programa' ).value = programa;
			contactLabel.textContent = programa || servicio || contactLabel.getAttribute( 'data-default' );
			contactAbout.hidden = ! ( programa || servicio );

			// Un aviso de un envío anterior no aplica a esta consulta.
			contactModal.querySelectorAll( '.match-contacto__notice' ).forEach( function ( n ) { n.remove(); } );
			contactModal.showModal();
		}, true );

		contactModal.querySelector( '[data-contact-modal-close]' ).addEventListener( 'click', function () { contactModal.close(); } );
		// Clic en el fondo (fuera del panel) cierra.
		contactModal.addEventListener( 'click', function ( event ) {
			if ( event.target === contactModal ) { contactModal.close(); }
		} );

		if ( contactModal.hasAttribute( 'data-open-on-load' ) ) {
			contactModal.showModal();
			// Limpia la URL para que recargar no vuelva a abrirlo.
			if ( window.history.replaceState ) { window.history.replaceState( null, '', window.location.pathname ); }
		}
	}

	/* --- Filtros de vacantes (portada y listado /vacantes/) ---
	 * Cada filtro es un <select multiple> (la base de datos y del envío). En
	 * todos los dispositivos se dibuja encima el desplegable de la marca, con
	 * casillas, cantidad por opción y los botones "Limpiar todo" y "Aplicar";
	 * la píldora muestra cuántas opciones hay marcadas ("Ubicación · 2").
	 * No se usa el selector nativo: Chrome Android pinta un <select multiple>
	 * como lista en línea (sin ventana emergente), así que invisible sobre la
	 * píldora marcaba opciones al tocar sin mostrar nada.
	 *
	 * filterGroup() arma esa interfaz sobre los [data-filter] de un contenedor.
	 * onApply() corre al pulsar "Aplicar"; onNativeChange() si el <select>
	 * cambia por otra vía.
	 */
	/* Si el bloque «Filtros» (mobile) está abierto al enviar el formulario, la
	 * página siguiente lo vuelve a abrir (ver [data-filters-box] más abajo). */
	var FILTERS_OPEN_KEY = 'match-filters-open';

	function keepFiltersOpen( form ) {
		try {
			if ( form.classList.contains( 'is-filters-open' ) ) { window.sessionStorage.setItem( FILTERS_OPEN_KEY, '1' ); }
		} catch ( e ) {}
	}

	function filterGroup( root, onApply, onNativeChange ) {
		var filters = Array.prototype.slice.call( root.querySelectorAll( '[data-filter]' ) );

		function selectOf( filter ) { return filter.querySelector( 'select' ); }

		function chosen( select ) {
			return Array.prototype.filter.call( select.options, function ( option ) { return option.selected; } );
		}

		function syncPill( filter ) {
			var select = selectOf( filter );
			var picked = chosen( select );
			var text = filter.querySelector( '[data-filter-label]' );
			filter.classList.toggle( 'is-active', picked.length > 0 );
			if ( picked.length === 1 ) {
				text.textContent = picked[ 0 ].dataset.name || picked[ 0 ].textContent;
			} else {
				text.textContent = picked.length ? select.dataset.label + ' · ' + picked.length : select.dataset.label;
			}
		}

		function panelOf( filter ) { return filter.querySelector( '.match-filter__panel' ); }

		function syncChecks( filter ) {
			var picked = chosen( selectOf( filter ) ).map( function ( option ) { return option.value; } );
			panelOf( filter ).querySelectorAll( 'input' ).forEach( function ( input ) {
				input.checked = picked.indexOf( input.value ) !== -1;
				input.closest( '.match-filter__option' ).classList.toggle( 'is-checked', input.checked );
			} );
		}

		function setOpen( filter, open ) {
			var toggle = filter.querySelector( '.match-filter__toggle' );

			if ( open ) {
				filters.forEach( function ( other ) { if ( other !== filter ) { setOpen( other, false ); } } );
				syncChecks( filter ); // descarta lo que quedó sin aplicar
			}
			filter.classList.toggle( 'is-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

			if ( open ) {
				// Alinea el panel con su píldora sin salirse de la fila (offsetParent = [data-filter-group]).
				var panel = panelOf( filter );
				var row = filter.offsetParent;
				var x = filter.offsetLeft;
				if ( row ) { x = Math.max( 0, Math.min( x, row.clientWidth - panel.offsetWidth ) ); }
				panel.style.setProperty( '--filter-x', x + 'px' );
			}
		}

		function buildPanel( filter ) {
			var select = selectOf( filter );
			var panel = panelOf( filter );
			var listEl;

			if ( ! panel ) {
				panel = document.createElement( 'div' );
				panel.className = 'match-filter__panel';
				panel.setAttribute( 'role', 'group' );
				panel.setAttribute( 'aria-label', select.dataset.label );
				filter.appendChild( panel );
			}
			panel.textContent = '';

			var title = document.createElement( 'p' );
			title.className = 'match-filter__title';
			title.textContent = select.dataset.label;
			panel.appendChild( title );

			listEl = document.createElement( 'ul' );
			listEl.className = 'match-filter__options';
			// Sin esto Lenis captura la rueda y mueve la página en vez de la lista.
			listEl.setAttribute( 'data-lenis-prevent', '' );
			Array.prototype.forEach.call( select.options, function ( option ) {
				var item = document.createElement( 'li' );
				var label = document.createElement( 'label' );
				var input = document.createElement( 'input' );
				var name = document.createElement( 'span' );
				var qty = document.createElement( 'span' );

				label.className = 'match-filter__option';
				input.type = 'checkbox';
				input.value = option.value;
				input.addEventListener( 'change', function () { label.classList.toggle( 'is-checked', input.checked ); } );
				name.className = 'match-filter__option-name';
				name.textContent = option.dataset.name || option.value;
				qty.className = 'match-filter__option-count';
				qty.textContent = option.dataset.count || '0';

				label.appendChild( input );
				label.appendChild( name );
				label.appendChild( qty );
				item.appendChild( label );
				listEl.appendChild( item );
			} );
			panel.appendChild( listEl );

			var foot = document.createElement( 'div' );
			var reset = document.createElement( 'button' );
			var apply = document.createElement( 'button' );

			foot.className = 'match-filter__foot';
			reset.type = 'button';
			reset.className = 'match-filter__reset';
			reset.textContent = 'Limpiar todo';
			apply.type = 'button';
			apply.className = 'match-filter__apply';
			apply.textContent = 'Aplicar';

			reset.addEventListener( 'click', function () {
				panel.querySelectorAll( 'input' ).forEach( function ( input ) {
					input.checked = false;
					input.closest( '.match-filter__option' ).classList.remove( 'is-checked' );
				} );
			} );

			apply.addEventListener( 'click', function () {
				var on = {};
				panel.querySelectorAll( 'input:checked' ).forEach( function ( input ) { on[ input.value ] = true; } );
				Array.prototype.forEach.call( select.options, function ( option ) { option.selected = !! on[ option.value ]; } );
				setOpen( filter, false );
				syncPill( filter );
				onApply();
			} );

			foot.appendChild( reset );
			foot.appendChild( apply );
			panel.appendChild( foot );
			syncChecks( filter );
		}

		filters.forEach( function ( filter ) {
			var select = selectOf( filter );
			var toggle = filter.querySelector( '.match-filter__toggle' );

			select.onchange = null;
			select.removeAttribute( 'onchange' );
			// Con el desplegable de la marca el <select> es solo la base de datos, fuera del foco.
			select.tabIndex = -1;
			select.setAttribute( 'aria-hidden', 'true' );
			filter.classList.add( 'is-enhanced' ); // CSS: el <select> deja de recibir toques
			select.addEventListener( 'change', function () { syncPill( filter ); onNativeChange(); } );

			buildPanel( filter );
			toggle.addEventListener( 'click', function () {
				setOpen( filter, ! filter.classList.contains( 'is-open' ) );
			} );
			filter.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Escape' && filter.classList.contains( 'is-open' ) ) {
					setOpen( filter, false );
					toggle.focus();
				}
			} );
		} );

		document.addEventListener( 'click', function ( event ) {
			filters.forEach( function ( filter ) {
				if ( filter.classList.contains( 'is-open' ) && ! filter.contains( event.target ) ) { setOpen( filter, false ); }
			} );
		} );

		return {
			// Filtros activos como param[]=valor (el formato de match/v1/jobs y de la URL).
			query: function () {
				var query = new URLSearchParams();
				filters.forEach( function ( filter ) {
					var select = selectOf( filter );
					chosen( select ).forEach( function ( option ) { query.append( select.dataset.param + '[]', option.value ); } );
				} );
				return query;
			},

			clearAll: function () {
				filters.forEach( function ( filter ) {
					Array.prototype.forEach.call( selectOf( filter ).options, function ( option ) { option.selected = false; } );
					syncPill( filter );
					syncChecks( filter );
				} );
			},

			// Opciones nuevas (conteos facetados) conservando lo elegido.
			rebuild: function ( facets ) {
				filters.forEach( function ( filter ) {
					var select = selectOf( filter );
					var items = facets && facets[ select.dataset.param ];
					var keep = {};

					// No se reconstruye mientras el selector nativo está abierto.
					if ( ! items || document.activeElement === select ) { return; }

					chosen( select ).forEach( function ( option ) { keep[ option.value ] = option.dataset.name || option.value; } );
					select.length = 0;
					items.forEach( function ( item ) {
						var option = new Option( item.name + ' (' + item.count + ')', item.slug );
						option.dataset.name = item.name;
						option.dataset.count = item.count;
						if ( keep[ item.slug ] !== undefined ) {
							option.selected = true;
							delete keep[ item.slug ];
						}
						select.add( option );
					} );
					// Lo elegido que ya no combina con los demás filtros se conserva con (0).
					Object.keys( keep ).forEach( function ( slug ) {
						var option = new Option( keep[ slug ] + ' (0)', slug, false, true );
						option.dataset.name = keep[ slug ];
						option.dataset.count = 0;
						select.add( option );
					} );
					syncPill( filter );
					buildPanel( filter );
				} );
			}
		};
	}

	/* --- Portada ---
	 * Cada cambio consulta match/v1/jobs (inc/jobs-filters.php) y reemplaza
	 * filas, conteo, enlace "Ver todas" y las opciones de los demás filtros.
	 * La URL se actualiza para poder compartirla. Si fetch falla, se envía el
	 * formulario al archivo como sin JavaScript.
	 */
	document.querySelectorAll( '[data-jobs-form]' ).forEach( function ( form ) {
		var endpoint = form.dataset.endpoint;
		var section = form.closest( '.match-vacantes' );
		var list = section && section.querySelector( '[data-jobs-list]' );

		if ( ! endpoint || ! list || ! window.fetch || ! window.AbortController ) { return; }

		var count = section.querySelector( '[data-jobs-count]' );
		var all = section.querySelector( '[data-jobs-all]' );
		var clear = form.querySelector( '[data-jobs-clear]' );
		var divider = form.querySelector( '[data-jobs-divider]' );
		var controller = null;
		var group = filterGroup( form, function () { load(); }, function () { load(); } );

		function load() {
			var query = group.query();
			var own = new AbortController();

			if ( controller ) { controller.abort(); }
			controller = own;

			list.classList.add( 'is-loading' );
			list.setAttribute( 'aria-busy', 'true' );
			// "Borrar filtros" solo existe cuando hay algo que borrar
			var none = '' === query.toString();
			if ( clear ) { clear.hidden = none; }
			if ( divider ) { divider.hidden = none; }

			fetch( endpoint + ( endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) + query.toString(), { signal: own.signal, headers: { Accept: 'application/json' } } )
				.then( function ( response ) { return response.ok ? response.json() : Promise.reject( new Error( response.status ) ); } )
				.then( function ( data ) {
					list.innerHTML = data.html;
					list.querySelectorAll( '.match-job' ).forEach( function ( row ) {
						row.classList.add( 'is-entering' );
					} );
					if ( count ) { count.innerHTML = data.count_html; }
					if ( all && data.archive_url ) { all.href = data.archive_url; }
					group.rebuild( data.facets );

					var search = query.toString();
					window.history.replaceState( null, '', window.location.pathname + ( search ? '?' + search : '' ) + '#vacantes' );
				} )
				.catch( function ( error ) {
					if ( error && error.name === 'AbortError' ) { return; }
					keepFiltersOpen( form );
					form.submit();
				} )
				.then( function () {
					if ( controller === own ) {
						list.classList.remove( 'is-loading' );
						list.setAttribute( 'aria-busy', 'false' );
					}
				} );
		}

		form.addEventListener( 'submit', function ( event ) { event.preventDefault(); load(); } );
		form.addEventListener( 'reset', function ( event ) {
			event.preventDefault();
			group.clearAll();
			load();
		} );

		// "Borrar filtros" del estado vacío (llega con las filas del endpoint)
		section.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '.match-vacantes__empty [data-jobs-clear]' ) ) { form.reset(); }
		} );
	} );

	/* --- Listado /vacantes/ ---
	 * Recarga la página (GET): "Aplicar" envía el formulario con los filtros,
	 * la búsqueda y el orden. Con el selector nativo se espera un momento tras
	 * el último cambio, para no recargar mientras se marcan varias opciones.
	 */
	document.querySelectorAll( '[data-archive-filters]' ).forEach( function ( form ) {
		var timer = 0;

		function send() {
			keepFiltersOpen( form );
			form.submit();
		}

		// Búsqueda con Enter, orden, etc. (form.submit() no dispara este evento).
		form.addEventListener( 'submit', function () { keepFiltersOpen( form ); } );

		filterGroup(
			form,
			send,
			function () {
				window.clearTimeout( timer );
				timer = window.setTimeout( send, 700 );
			}
		);
	} );

	/* --- Animaciones de entrada (AOS) ---
	 * Solo en las vistas que aún no usan GSAP (la portada usa reveal.js).
	 * Los elementos llevan data-aos="fade-up" (y data-aos-delay para escalonar).
	 * Con prefers-reduced-motion AOS se desactiva y todo se muestra de una.
	 */
	// Job Board: la entrada solo la primera vez (functions.php → match_jobboard_intro_boot).
	if ( window.AOS && ! document.documentElement.classList.contains( 'match-jb-no-intro' ) ) {
		var initAOS = function () {
			window.AOS.init( {
				duration: 700,
				easing: 'ease-out-cubic',
				offset: 80,
				once: true,
				disable: reducedMotion,
			} );
		};

		// Con preloader, se espera a que termine para que se vea la entrada.
		if ( document.documentElement.classList.contains( 'match-preloading' ) ) {
			window.addEventListener( 'match:preloader-done', initAOS, { once: true } );
		} else {
			initAOS();
		}
	}

	/* --- Carruseles (Embla) ---
	 * Estructura:
	 *   [data-embla data-embla-options='{"loop":true}']
	 *     [data-embla-viewport] > .flex > slides
	 *     [data-embla-prev] [data-embla-next]   (opcionales)
	 *     ul[data-embla-dots]                    (opcional; los puntos se generan
	 *                                             según los snaps del viewport actual)
	 */
	document.querySelectorAll( '[data-embla]' ).forEach( function ( root ) {
		var viewport = root.querySelector( '[data-embla-viewport]' );

		if ( ! viewport || ! window.EmblaCarousel ) {
			return;
		}

		var options = { align: 'start', containScroll: 'trimSnaps', skipSnaps: false };
		try { Object.assign( options, JSON.parse( root.dataset.emblaOptions || '{}' ) ); } catch ( e ) {}
		if ( reducedMotion ) { options.duration = 0; }

		var embla = window.EmblaCarousel( viewport, options );
		var prev = root.querySelector( '[data-embla-prev]' );
		var next = root.querySelector( '[data-embla-next]' );
		var dotsList = root.querySelector( '[data-embla-dots]' );
		var dots = [];

		function buildDots() {
			if ( ! dotsList ) { return; }
			dotsList.innerHTML = '';
			dots = embla.scrollSnapList().map( function ( _, i ) {
				var li = document.createElement( 'li' );
				var btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.innerHTML = '<span class="screen-reader-text">' + ( dotsList.dataset.label || 'Ir a' ) + ' ' + ( i + 1 ) + '</span>';
				btn.addEventListener( 'click', function () { embla.scrollTo( i ); } );
				li.appendChild( btn );
				dotsList.appendChild( li );
				return btn;
			} );
			// [data-embla-dots-always]: los puntos se muestran aunque haya una sola página (Testimonios, Figma).
			dotsList.hidden = dots.length < 2 && ! dotsList.hasAttribute( 'data-embla-dots-always' );
		}

		function sync() {
			var index = embla.selectedScrollSnap();
			dots.forEach( function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
				if ( i === index ) { dot.setAttribute( 'aria-current', 'true' ); } else { dot.removeAttribute( 'aria-current' ); }
			} );
			if ( prev ) { prev.disabled = ! embla.canScrollPrev(); }
			if ( next ) { next.disabled = ! embla.canScrollNext(); }
			var single = embla.scrollSnapList().length < 2;
			if ( prev ) { prev.hidden = single; }
			if ( next ) { next.hidden = single; }
		}

		if ( prev ) { prev.addEventListener( 'click', function () { embla.scrollPrev(); } ); }
		if ( next ) { next.addEventListener( 'click', function () { embla.scrollNext(); } ); }

		embla.on( 'init', function () { buildDots(); sync(); } );
		embla.on( 'reInit', function () { buildDots(); sync(); } );
		embla.on( 'select', sync );
		buildDots();
		sync();
	} );

	/* --- Job Board: caja de CV (solo validación en el navegador) --- */
	document.querySelectorAll( '[data-dropzone]' ).forEach( function ( zone ) {
		var input = zone.querySelector( '[data-dropzone-input]' );
		var hint = zone.querySelector( '[data-dropzone-hint]' );
		var desktopText = hint ? hint.textContent : '';
		var touchText = hint && hint.dataset.hintTouch ? hint.dataset.hintTouch : desktopText;
		var touchQuery = window.matchMedia( '(max-width: 767px), (pointer: coarse)' );
		// Texto de ayuda en reposo: con puntero fino se puede arrastrar; en mobile / táctil, no.
		var initial = touchQuery.matches ? touchText : desktopText;
		if ( hint ) { hint.textContent = initial; }
		var types = [ 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ];
		// Registro (paso 2): con el archivo listo, la caja se cambia por una franja
		// con check, nombre y «Cambiar» (template-registro-cv.php).
		var strip = zone.parentNode.querySelector( '[data-dropzone-file]' );

		function showStrip( file ) {
			if ( ! strip ) { return; }
			strip.hidden = ! file;
			zone.hidden = !! file;
			if ( file ) { strip.querySelector( '[data-dropzone-file-name]' ).textContent = file.name; }
		}

		if ( strip && input ) {
			strip.querySelector( '[data-dropzone-change]' ).addEventListener( 'click', function () { input.click(); } );
		}

		function accept( file ) {
			var ok = file && ( types.indexOf( file.type ) !== -1 || /\.(pdf|docx?)$/i.test( file.name ) );
			if ( ! ok ) {
				zone.classList.add( 'is-error' ); zone.classList.remove( 'is-ready' );
				if ( hint ) { hint.textContent = 'Solo PDF o Word.'; }
				showStrip( null );
				return;
			}
			if ( file.size > 5 * 1024 * 1024 ) {
				zone.classList.add( 'is-error' ); zone.classList.remove( 'is-ready' );
				if ( hint ) { hint.textContent = 'El archivo supera los 5 MB.'; }
				showStrip( null );
				return;
			}
			zone.classList.add( 'is-ready' ); zone.classList.remove( 'is-error' );
			if ( hint ) { hint.textContent = file.name + ' · listo para enviar'; }
			showStrip( file );
		}

		if ( input ) {
			input.addEventListener( 'change', function () {
				// Selector cancelado (Chrome vacía el input): vuelve la caja en reposo, sin error.
				if ( ! input.files.length ) {
					zone.classList.remove( 'is-ready', 'is-error' );
					if ( hint ) { hint.textContent = touchQuery.matches ? touchText : desktopText; }
					showStrip( null );
					return;
				}
				accept( input.files[ 0 ] );
			} );
		}
		[ 'dragenter', 'dragover' ].forEach( function ( type ) { zone.addEventListener( type, function ( e ) { e.preventDefault(); zone.classList.add( 'is-dragover' ); } ); } );
		[ 'dragleave', 'drop' ].forEach( function ( type ) { zone.addEventListener( type, function ( e ) { e.preventDefault(); zone.classList.remove( 'is-dragover' ); } ); } );
		zone.addEventListener( 'drop', function ( e ) {
			var file = e.dataTransfer && e.dataTransfer.files[ 0 ];
			if ( file && input ) { try { input.files = e.dataTransfer.files; } catch ( err ) {} }
			accept( file );
			if ( hint && ! zone.classList.contains( 'is-ready' ) && ! zone.classList.contains( 'is-error' ) ) { hint.textContent = touchQuery.matches ? touchText : desktopText; }
		} );
	} );

	/* --- Job Board: estados de carga (login, filtros del listado) --- */
	document.querySelectorAll( '.match-login-card' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function () {
			var btn = form.querySelector( 'button[type="submit"]' );
			if ( btn ) { btn.disabled = true; btn.classList.add( 'is-loading' ); }
		} );
	} );
	document.querySelectorAll( '.match-jb-filters select, .match-jb-sort select' ).forEach( function ( select ) {
		select.addEventListener( 'change', function () {
			var list = document.querySelector( '.match-jb-list, .match-jb-list-wrap' );
			if ( list ) { list.classList.add( 'is-loading' ); }
		} );
	} );

	/* --- Job Board: el CV del perfil se envía al elegir el archivo --- */
	document.querySelectorAll( '[data-autosubmit]' ).forEach( function ( input ) {
		input.addEventListener( 'change', function () {
			if ( input.files.length && input.form ) { input.form.submit(); }
		} );
	} );

	/* --- Job Board: nombre y apellido solo con letras ---
	 * Quita al vuelo números y símbolos (también al pegar). El `pattern` del
	 * input exige además que espacios, apóstrofos y guiones vayan entre letras;
	 * inc/jobboard.php repite la validación en el servidor. */
	document.querySelectorAll( '[data-name-input]' ).forEach( function ( input ) {
		var message = input.title;
		var check = function () {
			input.setCustomValidity( '' );
			if ( ! input.validity.valid && input.validity.patternMismatch ) { input.setCustomValidity( message ); }
		};
		input.addEventListener( 'input', function () {
			var clean = input.value.replace( /[^\p{L} '\-]/gu, '' );
			if ( clean !== input.value ) {
				var pos = Math.max( 0, input.selectionStart - ( input.value.length - clean.length ) );
				input.value = clean;
				input.setSelectionRange( pos, pos );
			}
			check();
		} );
		check();
	} );

	/* --- Mensajes y carta ([data-text-input]): solo texto normal ---
	 * Letras, números, espacios, saltos de línea y puntuación común. Quita al vuelo
	 * emojis y símbolos (< > { } [ ] \ | ^ ~ ` * = …), también al pegar.
	 * Misma regla que match_valid_message() en inc/jobboard.php. */
	document.querySelectorAll( '[data-text-input]' ).forEach( function ( field ) {
		field.addEventListener( 'input', function () {
			var clean = field.value.replace( /[^\p{L}\p{M}\p{N}\s.,;:¿?¡!'"“”‘’()\-–—\/@%&+#$€°ºª_]/gu, '' );
			if ( clean !== field.value ) {
				var pos = Math.max( 0, field.selectionStart - ( field.value.length - clean.length ) );
				field.value = clean;
				field.setSelectionRange( pos, pos );
			}
		} );
	} );

	/* --- Mostrar/ocultar contraseña ([data-password-toggle], registro) ---
	 * El botón empieza oculto: sin JS queda el campo normal. Busca el input
	 * dentro del mismo .match-field__secret. */
	document.querySelectorAll( '[data-password-toggle]' ).forEach( function ( button ) {
		var input = button.parentNode.querySelector( 'input' );
		if ( ! input ) { return; }
		button.hidden = false;
		button.addEventListener( 'click', function () {
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			button.setAttribute( 'aria-pressed', show ? 'true' : 'false' );
			button.setAttribute( 'aria-label', show ? button.dataset.labelHide : button.dataset.labelShow );
			input.focus();
		} );
	} );

	/* --- Aviso de inicio de sesión (J20): lo abren el marcador de guardar y las secciones privadas del menú ---
	 * Devuelve false si el navegador no soporta <dialog> (el llamador cae al login normal). */
	function openLoginModal( returnUrl, title, text ) {
		var modal = document.getElementById( 'match-save-modal' );
		if ( ! modal || typeof modal.showModal !== 'function' ) { return false; }
		var base = modal.dataset.loginBase || '/';
		modal.querySelector( '[data-save-login]' ).href = base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + 'redirect_to=' + encodeURIComponent( returnUrl );
		var signup = modal.querySelector( '[data-save-signup]' );
		if ( signup ) { signup.setAttribute( 'data-apply-return', returnUrl ); }
		var t = modal.querySelector( '[data-save-title]' );
		var p = modal.querySelector( '[data-save-text]' );
		t.textContent = title || t.dataset.default;
		p.textContent = text || p.dataset.default;
		if ( ! modal.open ) {
			modal.showModal();
			if ( window.matchLenis ) { window.matchLenis.stop(); }
		}
		return true;
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '[data-login-required]' );
		if ( ! link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.button ) { return; }
		if ( openLoginModal( link.getAttribute( 'data-login-required' ), link.getAttribute( 'data-login-title' ), 'Es una sección de tu cuenta: entra y te llevamos directo a ella.' ) ) { event.preventDefault(); }
	} );

	/* --- Job Board: guardar vacante ---
	 * Tarjetas del Job Board y filas de Vacantes de la portada.
	 * Sin sesión lleva al login. Con sesión llama a POST match/v1/saved/{id}
	 * (inc/jobboard.php) y actualiza el marcador; en la página Guardados la
	 * tarjeta se retira y baja el contador.
	 */
	document.addEventListener( 'click', function ( event ) {
		var fav = event.target.closest( '.match-job__fav, .match-jb-card__fav' );
		if ( ! fav ) { return; }
		var cfg = window.MatchJB || {};
		if ( ! cfg.loggedIn ) {
			// Sin sesión (J20): un aviso que explica por qué, no el login a ciegas. Entrar o registrarse devuelve a
			// esta misma página con ?match_save=ID y la vacante queda guardada.
			var back = new URL( window.location.href );
			back.searchParams.set( 'match_save', fav.dataset.job );
			if ( openLoginModal( back.toString() ) ) { return; }
			window.location.href = cfg.loginUrl || '/';
			return;
		}
		if ( fav.disabled ) { return; }

		var wasSaved = fav.classList.contains( 'is-saved' );
		fav.classList.toggle( 'is-saved', ! wasSaved );
		fav.setAttribute( 'aria-pressed', wasSaved ? 'false' : 'true' );
		fav.disabled = true;

		fetch( cfg.rest + 'saved/' + encodeURIComponent( fav.dataset.job ), { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.nonce } } )
			.then( function ( r ) { return r.ok ? r.json() : Promise.reject( new Error( r.status ) ); } )
			.then( function ( data ) {
				fav.classList.toggle( 'is-saved', !! data.saved );
				fav.setAttribute( 'aria-pressed', data.saved ? 'true' : 'false' );
				var count = document.querySelector( '[data-saved-count]' );
				if ( count ) { count.textContent = data.count + ' ' + ( data.count === 1 ? 'vacante' : 'vacantes' ); }
				var badge = document.querySelector( '[data-nav-count="guardados"]' );
				var navItem = badge && badge.closest( '.match-jb__navitem' );
				if ( badge ) { badge.textContent = data.count; badge.hidden = ! data.count; }
				else if ( data.count && navItem === null ) {
					var guardados = Array.prototype.slice.call( document.querySelectorAll( '.match-jb__navitem' ) ).filter( function ( n ) { return /Guardados/.test( n.textContent ); } )[ 0 ];
					if ( guardados ) { var b = document.createElement( 'span' ); b.className = 'match-jb__navbadge'; b.setAttribute( 'data-nav-count', 'guardados' ); b.textContent = data.count; guardados.appendChild( b ); }
				}
				if ( ! wasSaved && data.saved ) { fav.classList.remove( 'is-pop' ); void fav.offsetWidth; fav.classList.add( 'is-pop' ); }
				var grid = fav.closest( '[data-saved-grid]' );
				if ( grid && ! data.saved ) {
					var card = fav.closest( '.match-jb-card' );
					card.classList.add( 'is-leaving' );
					window.setTimeout( function () {
						card.remove();
						if ( ! grid.querySelector( '.match-jb-card' ) ) { var empty = document.querySelector( '[data-saved-empty]' ); if ( empty ) { empty.hidden = false; } grid.hidden = true; }
					}, 250 );
				}
			} )
			.catch( function () {
				fav.classList.toggle( 'is-saved', wasSaved );
				fav.setAttribute( 'aria-pressed', wasSaved ? 'true' : 'false' );
			} )
			.then( function () { fav.disabled = false; } );
	} );

	/* --- Aviso de guardar sin sesión: cierre con la X, el fondo o Esc --- */
	var saveModalEl = document.getElementById( 'match-save-modal' );
	if ( saveModalEl ) {
		saveModalEl.addEventListener( 'click', function ( event ) {
			if ( event.target === saveModalEl || event.target.closest( '[data-save-close]' ) ) { saveModalEl.close(); }
		} );
		saveModalEl.addEventListener( 'close', function () { if ( window.matchLenis ) { window.matchLenis.start(); } } );
	}

	/* --- Job Board: menú de cuenta de la topbar ---
	 * <details>/<summary> nativo: sin JS ya es accesible y no necesita
	 * posicionamiento propio. Solo se añade el cierre al hacer clic fuera,
	 * al elegir una opción o al presionar Esc, para que no se quede abierto.
	 */
	document.querySelectorAll( '.match-jb-account' ).forEach( function ( account ) {
		document.addEventListener( 'click', function ( event ) {
			if ( account.open && ! account.contains( event.target ) ) { account.open = false; }
		} );
		account.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '.match-jb-account__item' ) ) { account.open = false; }
		} );
		account.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && account.open ) {
				account.open = false;
				account.querySelector( '.match-jb-account__trigger' ).focus();
			}
		} );
	} );

	/* --- Job Board: menú hamburguesa de la barra lateral ---
	 * ≤1024px: la navegación y la sesión van en un panel bajo la barra. Se
	 * cierra con la X, Esc, clic fuera, al elegir un enlace o al volver a desktop.
	 * Desktop: el mismo botón contrae la barra a un rail de iconos; el estado
	 * se guarda en localStorage (el sidebar lo aplica antes de pintar).
	 */
	var jbSidebar = document.querySelector( '[data-jb-sidebar]' );
	var jbToggle  = jbSidebar && jbSidebar.querySelector( '.match-jb__toggle' );
	if ( jbToggle ) {
		var mqJbMobile = window.matchMedia( '(max-width: 1024px)' );

		var isJbMenuOpen = function () { return jbToggle.getAttribute( 'aria-expanded' ) === 'true'; };

		var setJbMenu = function ( open ) {
			jbSidebar.classList.toggle( 'is-open', open );
			jbToggle.setAttribute( 'aria-expanded', String( open ) );
			jbToggle.setAttribute( 'aria-label', open ? jbToggle.dataset.labelClose : jbToggle.dataset.labelOpen );
			document.body.classList.toggle( 'has-open-jbmenu', open );
			if ( lenis ) { open ? lenis.stop() : lenis.start(); }
		};

		var isJbCollapsed = function () { return jbSidebar.classList.contains( 'is-collapsed' ); };

		// En desktop aria-expanded indica si la barra está expandida.
		var syncJbDesktopToggle = function () {
			var collapsed = isJbCollapsed();
			jbToggle.setAttribute( 'aria-expanded', String( ! collapsed ) );
			jbToggle.setAttribute( 'aria-label', collapsed ? jbToggle.dataset.labelExpand : jbToggle.dataset.labelCollapse );
		};

		var setJbCollapsed = function ( collapsed ) {
			jbSidebar.classList.toggle( 'is-collapsed', collapsed );
			syncJbDesktopToggle();
			try { localStorage.setItem( 'matchJbCollapsed', collapsed ? '1' : '0' ); } catch ( e ) {}
		};

		// Al terminar la animación del ancho, que el resto del panel recalcule.
		jbSidebar.addEventListener( 'transitionend', function ( event ) {
			if ( event.target === jbSidebar && event.propertyName === 'flex-basis' ) {
				window.dispatchEvent( new Event( 'resize' ) );
			}
		} );

		if ( ! mqJbMobile.matches ) { syncJbDesktopToggle(); }

		jbToggle.addEventListener( 'click', function () {
			if ( mqJbMobile.matches ) {
				setJbMenu( ! isJbMenuOpen() );
			} else {
				setJbCollapsed( ! isJbCollapsed() );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && mqJbMobile.matches && isJbMenuOpen() ) {
				setJbMenu( false );
				jbToggle.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! mqJbMobile.matches || ! isJbMenuOpen() ) { return; }
			if ( ! jbSidebar.contains( event.target ) || event.target.closest( '.match-jb__menu a' ) ) {
				setJbMenu( false );
			}
		} );

		// Al cruzar el corte, aria-expanded vuelve a describir el estado de ese modo.
		mqJbMobile.addEventListener( 'change', function ( event ) {
			if ( event.matches ) {
				setJbMenu( false );
			} else {
				if ( jbSidebar.classList.contains( 'is-open' ) ) { setJbMenu( false ); }
				syncJbDesktopToggle();
			}
		} );
	}

	/* --- Job Board: notificaciones de la topbar ---
	 * Mismo <details> nativo que el menú de cuenta. Se cierra al hacer clic
	 * fuera o con Esc, y al abrir uno se cierra el otro. Marcar como leída
	 * llama a POST match/v1/notifications/read (inc/notifications.php): una
	 * al hacer clic en un aviso (id) y todas con "Marcar todo como leído".
	 */
	var notif = document.querySelector( '[data-notif]' );
	if ( notif ) {
		var notifDot = notif.querySelector( '[data-notif-dot]' );
		var notifAll = notif.querySelector( '[data-notif-readall]' );

		var notifMark = function ( id ) {
			var cfg = window.MatchJB || {};
			var body = new FormData();
			if ( id ) { body.append( 'id', id ); }
			return fetch( cfg.rest + 'notifications/read', { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'X-WP-Nonce': cfg.nonce }, body: body } )
				.then( function ( r ) { return r.ok ? r.json() : Promise.reject( new Error( r.status ) ); } )
				.then( function ( data ) {
					if ( notifDot ) { notifDot.hidden = ! data.unread; }
					if ( notifAll ) { notifAll.hidden = ! data.unread; }
				} );
		};

		document.addEventListener( 'click', function ( event ) {
			if ( notif.open && ! notif.contains( event.target ) ) { notif.open = false; }
		} );
		notif.addEventListener( 'toggle', function () {
			var account = document.querySelector( '.match-jb-account' );
			if ( notif.open && account ) { account.open = false; }
		} );
		notif.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && notif.open ) {
				notif.open = false;
				notif.querySelector( 'summary' ).focus();
			}
		} );
		notif.addEventListener( 'click', function ( event ) {
			var item = event.target.closest( '[data-notif-id]' );
			if ( item && item.classList.contains( 'is-unread' ) ) {
				item.classList.remove( 'is-unread' );
				notifMark( item.dataset.notifId ).catch( function () {} );
			}
		} );
		if ( notifAll ) {
			notifAll.addEventListener( 'click', function () {
				notifMark( '' ).then( function () {
					notif.querySelectorAll( '.is-unread' ).forEach( function ( el ) { el.classList.remove( 'is-unread' ); } );
				} ).catch( function () {} );
			} );
		}
		document.querySelectorAll( '.match-jb-account' ).forEach( function ( account ) {
			account.addEventListener( 'toggle', function () {
				if ( account.open ) { notif.open = false; }
			} );
		} );
	}

	/* --- Job Board: modal de postulación ---
	 * El formulario del plugin vive en #postular, en el flujo normal de la
	 * página (así funciona sin JavaScript). Con JavaScript ese mismo nodo se
	 * traslada al <dialog> apenas carga y ya no vuelve: el formulario solo
	 * se ve en el modal. Nunca se clona: un segundo formulario duplicaría
	 * los id y permitiría un envío doble.
	 */
	var applyModal = document.getElementById( 'match-apply-modal' );
	var applySection = document.getElementById( 'postular' );

	if ( applyModal && applySection && typeof applyModal.showModal === 'function' ) {
		var applyBody = applyModal.querySelector( '[data-apply-body]' );
		applyBody.appendChild( applySection );

		var openApplyModal = function () {
			if ( applyModal.open ) { return; }
			applyModal.showModal();
			// Lenis mueve la página con la rueda aunque <html> tenga overflow:hidden.
			if ( lenis ) { lenis.stop(); }
			var field = applyBody.querySelector( 'input:not([type="hidden"]):not([type="file"]), textarea, select, a.mjb-btn' );
			if ( field ) { field.focus(); }
		};

		var closeApplyModal = function () {
			if ( applyModal.open ) { applyModal.close(); }
		};

		// Un enlace directo a …/#postular abre el modal en vez de bajar a la sección.
		if ( window.location.hash === '#postular' && ! document.querySelector( '#match-apply-success, #match-apply-error' ) ) {
			openApplyModal();
		}

		document.querySelectorAll( '[data-apply-trigger]' ).forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				openApplyModal();
			} );
		} );

		applyModal.querySelectorAll( '[data-apply-close]' ).forEach( function ( button ) {
			button.addEventListener( 'click', closeApplyModal );
		} );

		applyModal.addEventListener( 'click', function ( event ) {
			if ( event.target === applyModal ) { closeApplyModal(); } // clic en el fondo
		} );

		// "close" cubre Cancelar, fondo y Esc.
		applyModal.addEventListener( 'close', function () {
			if ( lenis ) { lenis.start(); }
		} );
	}

	/* --- Detalle de vacante: barra fija «Postular ahora» (escritorio) ---
	 * Aparece cuando el botón del encabezado ya pasó por arriba de la pantalla
	 * y se va al volver a verlo. Mientras está oculta, inert la saca del foco y
	 * del lector de pantalla. En ≤1024 el CSS no la muestra. */
	var stickyApply = document.querySelector( '[data-sticky-apply]' );
	var headerApply = document.querySelector( '.match-jb-detail__actions .match-jb-detail__apply' );
	if ( stickyApply && headerApply && 'IntersectionObserver' in window ) {
		new IntersectionObserver( function ( entries ) {
			var entry = entries[ 0 ];
			var show = ! entry.isIntersecting && entry.boundingClientRect.top < 0;
			stickyApply.classList.toggle( 'is-visible', show );
			stickyApply.inert = ! show;
			stickyApply.setAttribute( 'aria-hidden', show ? 'false' : 'true' );
		} ).observe( headerApply );
	}

	/* --- Postulación: el teléfono admite solo números, espacios y «+», de 9 a 15 dígitos (J07) ---
	 * Valida al salir del campo y al enviar; el mensaje va debajo del campo. */
	document.querySelectorAll( '[data-phone-input]' ).forEach( function ( input ) {
		var field = input.closest( '.mjb-field' );
		var message = field && field.querySelector( '[data-phone-error]' );
		var form = input.form;

		function valid() {
			var value = input.value.trim();
			var digits = ( value.match( /\d/g ) || [] ).length;
			return value === '' || ( /^[\d\s+]+$/.test( value ) && digits >= 9 && digits <= 15 );
		}

		function show() {
			var ok = valid();
			input.classList.toggle( 'is-invalid', ! ok );
			input.setAttribute( 'aria-invalid', ok ? 'false' : 'true' );
			if ( message ) { message.hidden = ok; }
			return ok;
		}

		// No deja escribir ni pegar letras ni símbolos: solo números, espacios y «+», con tope de 15 dígitos.
		function clean() {
			var value = input.value.replace( /[^\d\s+]/g, '' );
			var count = 0;
			value = value.replace( /\d/g, function ( digit ) { count += 1; return count <= 15 ? digit : ''; } );
			if ( value !== input.value ) { input.value = value; }
		}

		input.addEventListener( 'blur', show );
		input.addEventListener( 'input', function () {
			clean();
			if ( input.classList.contains( 'is-invalid' ) ) { show(); }
		} );
		clean();
		if ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				if ( ! show() ) { event.preventDefault(); input.focus(); }
			} );
		}
	} );

	// «Regístrate» del modal de postular sin sesión (J12): se recuerda la vacante durante el registro (30 min)
	// para volver a ella con el modal abierto al terminar (ver match_handle_register_cv).
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest && event.target.closest( '[data-apply-return]' );
		if ( link ) {
			document.cookie = 'match_apply_return=' + encodeURIComponent( link.getAttribute( 'data-apply-return' ) ) + '; path=/; max-age=1800; samesite=lax';
		}
	} );

	// Refuerzo delegado (no depende de cuándo se montó el campo ni de que el formulario se haya movido al modal):
	// en cualquier input del teléfono, escritura o pegado, se descarta todo salvo números, espacios y «+», máx. 15 dígitos.
	document.addEventListener( 'input', function ( event ) {
		var input = event.target;
		if ( ! input || ! input.matches || ! input.matches( '[data-phone-input]' ) ) { return; }
		var count = 0;
		var value = input.value.replace( /[^\d\s+]/g, '' ).replace( /\d/g, function ( digit ) { count += 1; return count <= 15 ? digit : ''; } );
		if ( value !== input.value ) { input.value = value; }
	} );

	/* --- Job Board: formulario de postulación (match-job-board/apply-form.php) ---
	 * "Cambiar CV" es el <label> del input de archivo; acá solo se refleja el
	 * archivo elegido en la fila del CV y se avisa antes de enviar si pasa los
	 * 10 MB (el plugin igual lo valida en el servidor). El contador acompaña
	 * el maxlength de la carta (2000, tope del API del CRM).
	 */
	document.querySelectorAll( '[data-cv-field]' ).forEach( function ( field ) {
		var input = field.querySelector( '[data-cv-input]' );
		var card = field.querySelector( '.match-jb-cv' );
		var error = field.querySelector( '[data-cv-error]' );
		var button = field.querySelector( '.mjb-cv-head__btn' );
		if ( ! input || ! card ) { return; }

		var original = card.cloneNode( true );
		var maxBytes = parseInt( input.getAttribute( 'data-max-bytes' ), 10 ) || 0;

		var formatSize = function ( bytes ) {
			return bytes < 1048576
				? Math.max( 1, Math.round( bytes / 1024 ) ) + ' KB'
				: ( bytes / 1048576 ).toFixed( 1 ) + ' MB';
		};

		var restore = function () {
			var fresh = original.cloneNode( true );
			card.replaceWith( fresh );
			card = fresh;
		};

		input.addEventListener( 'change', function () {
			var file = input.files && input.files[ 0 ];
			error.hidden = true;
			restore();
			if ( ! file ) { return; }

			if ( maxBytes && file.size > maxBytes ) {
				input.value = '';
				error.textContent = 'El archivo pesa ' + formatSize( file.size ) + '. El máximo es 10 MB.';
				error.hidden = false;
				return;
			}

			var ext = ( file.name.split( '.' ).pop() || '' ).toUpperCase();
			card.classList.remove( 'match-jb-cv--empty' );
			card.removeAttribute( 'href' ); // el enlace apuntaba al CV anterior
			card.querySelector( '.match-jb-cv__type' ).classList.remove( 'match-jb-cv__type--empty' );
			card.querySelector( '.match-jb-cv__type' ).textContent = ext;
			card.querySelector( '.match-jb-cv__name' ).textContent = file.name;
			card.querySelector( '.match-jb-cv__info' ).textContent = 'Nuevo archivo  ·  ' + formatSize( file.size );
			if ( button ) { button.textContent = 'Cambiar CV'; }
		} );
	} );

	document.querySelectorAll( '[data-count-input]' ).forEach( function ( textarea ) {
		var output = document.getElementById( textarea.getAttribute( 'aria-describedby' ) );
		var value = output && output.querySelector( '[data-count-value]' );
		var max = parseInt( textarea.getAttribute( 'maxlength' ), 10 );
		if ( ! value ) { return; }

		var update = function () {
			value.textContent = textarea.value.length;
			output.classList.toggle( 'is-limit', textarea.value.length >= max );
		};
		textarea.addEventListener( 'input', update );
		update();
	} );

	/* --- Job Board: modales de resultado de la postulación (éxito/error) ---
	 * El envío es un POST normal (sin fetch): class-mjb-applications.php
	 * redirige con ?mjb_applied=1 o ?mjb_error=…, y job-detail.php renderiza
	 * el <dialog> que corresponde solo si aplica. Acá solo se abre, se cierra
	 * y se limpia la URL para que un refresh no lo vuelva a mostrar.
	 */
	[ 'match-apply-success', 'match-apply-error' ].forEach( function ( id ) {
		var modal = document.getElementById( id );
		if ( ! modal || typeof modal.showModal !== 'function' ) { return; }

		modal.querySelectorAll( '[data-modal-close]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { modal.close(); } );
		} );

		modal.addEventListener( 'click', function ( event ) {
			if ( event.target === modal ) { modal.close(); } // clic en el fondo
		} );

		// "Reintentar" (solo en el de error) abre el modal de postulación de
		// nuevo; este solo se encarga de cerrarse a sí mismo primero.
		modal.querySelectorAll( '[data-apply-trigger]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { modal.close(); } );
		} );

		modal.showModal();
	} );

	/* --- Job Board: eliminar cuenta (template-profile.php) ---
	 * El botón empieza oculto (sin JS se usa el formulario del <noscript>).
	 * El envío solo se habilita al escribir ELIMINAR; el servidor lo vuelve a
	 * comprobar en match_profile_delete().
	 */
	var deleteModal = document.getElementById( 'match-delete-account' );
	var deleteOpen  = document.querySelector( '[data-delete-open]' );
	if ( deleteModal && deleteOpen && typeof deleteModal.showModal === 'function' ) {
		var deleteInput  = deleteModal.querySelector( '[data-delete-confirm]' );
		var deleteSubmit = deleteModal.querySelector( '[data-delete-submit]' );

		deleteOpen.hidden = false;
		deleteOpen.addEventListener( 'click', function () {
			deleteInput.value     = '';
			deleteSubmit.disabled = true;
			deleteModal.showModal();
			deleteInput.focus();
		} );

		deleteInput.addEventListener( 'input', function () {
			deleteSubmit.disabled = deleteInput.value.trim() !== 'ELIMINAR';
		} );

		deleteModal.querySelectorAll( '[data-modal-close]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { deleteModal.close(); } );
		} );

		deleteModal.addEventListener( 'click', function ( event ) {
			if ( event.target === deleteModal ) { deleteModal.close(); } // clic en el fondo
		} );
	}

	try {
		var resultUrl = new URL( window.location.href );
		if ( resultUrl.searchParams.has( 'mjb_applied' ) || resultUrl.searchParams.has( 'mjb_error' ) ) {
			resultUrl.searchParams.delete( 'mjb_applied' );
			resultUrl.searchParams.delete( 'mjb_error' );
			window.history.replaceState( null, '', resultUrl.toString() );
		}
	} catch ( error ) { /* URL no soportada: no se limpia, no pasa nada más. */ }

	/* --- Job Board: panel de estado de la postulación ---
	 * <dialog> anclado al borde derecho (ver .match-jb-status-drawer en
	 * jobboard.css) en vez de centrado: contenido estático, así que no hace
	 * falta la relocación del modal de postulación, solo abrir/cerrar.
	 * Uno en el detalle de vacante (#match-status-drawer, "Ver proceso") y
	 * uno por fila en Mis procesos: data-status-trigger="<id>" elige cuál.
	 */
	document.querySelectorAll( '[data-status-drawer]' ).forEach( function ( drawer ) {
		if ( typeof drawer.showModal !== 'function' ) { return; }

		drawer.querySelectorAll( '[data-status-close]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { drawer.close(); } );
		} );

		drawer.addEventListener( 'click', function ( event ) {
			if ( event.target === drawer ) { drawer.close(); } // clic en el fondo
		} );

		// "close" cubre la X, el fondo y Esc.
		drawer.addEventListener( 'close', function () {
			if ( lenis ) { lenis.start(); }
		} );
	} );

	document.querySelectorAll( '[data-status-trigger]' ).forEach( function ( trigger ) {
		var drawer = document.getElementById( trigger.getAttribute( 'data-status-trigger' ) || 'match-status-drawer' );
		if ( ! drawer || typeof drawer.showModal !== 'function' ) { return; }

		trigger.setAttribute( 'aria-controls', drawer.id );
		trigger.addEventListener( 'click', function () {
			drawer.showModal();
			// Lenis movería la página con la rueda aunque el panel esté encima.
			if ( lenis ) { lenis.stop(); }
		} );
	} );

	/* --- Job Board: modal de bienvenida (una vez por usuario) ---
	 * <dialog> nativo: fondo, foco y Esc vienen de serie. Al cerrarse (o al
	 * pulsar un botón, que navega) se marca en user meta por AJAX.
	 */
	var onboarding = document.querySelector( '[data-onboarding]' );

	if ( onboarding && typeof onboarding.showModal === 'function' ) {
		var onboardingSent = false;
		var onboardingDone = function () {
			if ( onboardingSent || ! window.fetch ) { return; }
			onboardingSent = true;
			var body = new window.FormData();
			body.append( 'action', 'match_onboarding_done' );
			body.append( 'nonce', onboarding.getAttribute( 'data-onboarding-nonce' ) );
			window.fetch( onboarding.getAttribute( 'data-onboarding-url' ), { method: 'POST', body: body, credentials: 'same-origin', keepalive: true } ).catch( function () {} );
		};

		onboarding.showModal();
		if ( lenis ) { lenis.stop(); }

		onboarding.addEventListener( 'close', function () {
			if ( lenis ) { lenis.start(); }
			onboardingDone();
		} );
		onboarding.addEventListener( 'click', function ( event ) {
			if ( event.target === onboarding ) { onboarding.close(); return; } // clic en el fondo
			if ( event.target.closest( '[data-onboarding-close]' ) ) { onboarding.close(); return; } // botón X
			if ( event.target.closest( '[data-onboarding-done]' ) ) { onboardingDone(); }
		} );
	}

	/* --- Vacantes (mobile): el botón «Filtros · n» despliega / cierra los filtros ---
	 * El número cuenta los filtros con algo elegido; se recalcula cuando cambia el
	 * estado de las píldoras ([data-filter].is-active), también tras filtrar por AJAX.
	 */
	document.querySelectorAll( '[data-filters-box]' ).forEach( function ( box ) {
		var toggle = box.querySelector( '[data-filters-toggle]' );
		var label = toggle && toggle.querySelector( '[data-filters-toggle-label]' );

		if ( ! toggle || ! label ) { return; }

		function count() {
			var n = box.querySelectorAll( '[data-filter].is-active' ).length;
			label.textContent = n ? toggle.dataset.label + ' · ' + n : toggle.dataset.label;
		}

		function setOpen( open ) {
			box.classList.toggle( 'is-filters-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! box.classList.contains( 'is-filters-open' ) );
		} );

		// Recién aplicados los filtros (recarga GET): se mantiene abierto (keepFiltersOpen).
		try {
			if ( window.sessionStorage.getItem( FILTERS_OPEN_KEY ) ) {
				window.sessionStorage.removeItem( FILTERS_OPEN_KEY );
				setOpen( true );
			}
		} catch ( e ) {}

		if ( 'MutationObserver' in window ) {
			new MutationObserver( count ).observe( box, { subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
		}
		count();
	} );

	/* --- Blog (mobile): el botón «Filtros» despliega / cierra la lista de categorías --- */
	document.querySelectorAll( '[data-blog-filters]' ).forEach( function ( box ) {
		var toggle = box.querySelector( '[data-blog-filter-toggle]' );

		if ( ! toggle ) { return; }

		function setOpen( open ) {
			box.classList.toggle( 'is-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		toggle.addEventListener( 'click', function () { setOpen( ! box.classList.contains( 'is-open' ) ); } );
		box.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && box.classList.contains( 'is-open' ) ) { setOpen( false ); toggle.focus(); }
		} );
	} );

	/* --- Clientes: los logos rotan casilla por casilla ---
	 * La grilla muestra los logos de la página; los demás clientes esperan en
	 * data-logo-pool. Cada 2,4 s una casilla (en orden) funde su logo a 0,
	 * lo cambia por el siguiente de la reserva y lo vuelve a fundir adentro; el
	 * que sale pasa al final de la reserva, así acaban viéndose todos. Solo corre
	 * con la grilla a la vista y la pestaña activa; con prefers-reduced-motion
	 * queda estática.
	 */
	document.querySelectorAll( '[data-logo-pool]' ).forEach( function ( grid ) {
		var pool;
		try { pool = JSON.parse( grid.getAttribute( 'data-logo-pool' ) ); } catch ( e ) { return; }

		var cells = Array.prototype.slice.call( grid.querySelectorAll( 'li img' ) );

		if ( reducedMotion || ! Array.isArray( pool ) || ! pool.length || ! cells.length ) { return; }

		var next = 0;
		var timer = 0;
		var visible = false;

		pool.forEach( function ( logo ) { ( new Image() ).src = logo.src; } ); // precarga

		function swap() {
			var img = cells[ next % cells.length ];
			var incoming = pool.shift();

			next += 1;
			img.style.opacity = '0';

			window.setTimeout( function () {
				pool.push( { src: img.currentSrc || img.src, alt: img.alt } );
				img.src = incoming.src;
				img.alt = incoming.alt;
				img.style.opacity = '1';
			}, 600 );
		}

		function start() {
			if ( timer || ! visible || document.hidden ) { return; }
			timer = window.setInterval( swap, 2400 );
		}

		function stop() {
			window.clearInterval( timer );
			timer = 0;
		}

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
				if ( visible ) { start(); } else { stop(); }
			} ).observe( grid );
		}

		document.addEventListener( 'visibilitychange', function () { if ( document.hidden ) { stop(); } else { start(); } } );
	} );

	/* --- Toast de la solución y su burbuja (Figma: Aviso / Launcher 6219) ---
	 * El aviso queda siempre a la vista. La X lo minimiza a la burbuja y la
	 * burbuja lo vuelve a abrir; el estado minimizado se recuerda en la sesión,
	 * así las demás soluciones cargan directo con la burbuja.
	 * Primera aparición: escritorio a los 1,5 s; mobile (≤1024 px) tapaba el
	 * hero nada más cargar, así que espera 300 px de scroll o 8 s.
	 */
	var toast = document.getElementById( 'match-toast' );
	var launcher = document.getElementById( 'match-toast-launcher' );

	if ( toast && launcher ) {
		var TOAST_KEY = 'match-toast';
		var minimized = false;
		// '1' era el valor anterior (aviso cerrado): también cuenta como minimizado.
		try { minimized = /^(min|1)$/.test( window.sessionStorage.getItem( TOAST_KEY ) || '' ); } catch ( e ) {}

		var setMinimized = function ( value ) {
			try {
				if ( value ) { window.sessionStorage.setItem( TOAST_KEY, 'min' ); } else { window.sessionStorage.removeItem( TOAST_KEY ); }
			} catch ( e ) {}
		};

		var openToast = function ( focus ) {
			launcher.hidden = true;
			launcher.setAttribute( 'aria-expanded', 'true' );
			toast.hidden = false;
			if ( focus ) { toast.focus( { preventScroll: true } ); }
		};

		var minimizeToast = function ( focus ) {
			toast.hidden = true;
			launcher.hidden = false;
			launcher.setAttribute( 'aria-expanded', 'false' );
			if ( focus ) { launcher.focus( { preventScroll: true } ); }
		};

		toast.querySelector( '[data-toast-close]' ).addEventListener( 'click', function () {
			minimizeToast( true );
			setMinimized( true );
		} );

		launcher.addEventListener( 'click', function () {
			openToast( true );
			setMinimized( false );
		} );

		if ( minimized ) {
			minimizeToast( false );
		} else {
			var toastTimer;
			var showToast = function () {
				window.removeEventListener( 'scroll', onToastScroll );
				window.clearTimeout( toastTimer );
				if ( launcher.hidden ) { openToast( false ); }
			};
			var onToastScroll = function () { if ( window.scrollY > 300 ) { showToast(); } };

			if ( window.matchMedia( '(max-width: 1024px)' ).matches ) {
				window.addEventListener( 'scroll', onToastScroll, { passive: true } );
				toastTimer = window.setTimeout( showToast, 8000 );
			} else {
				toastTimer = window.setTimeout( showToast, 1500 );
			}
		}
	}

	/* --- Wordmark del pie en parallax (Figma: Wordmark - match, node 3502:3533) ---
	 * Cada pieza (isotipo, luego m-a-t-c-h) sube y aparece escalonada según
	 * cuánto del pie entró en pantalla, con la curva del diseño
	 * (cubic-bezier .16,1,.3,1 ≈ expo-out). Va ligada al scroll, así que se
	 * deshace al volver arriba. El SVG trae el isotipo al final: se reordena.
	 */
	var wordmark = document.querySelector( '.match-footer__wordmark svg' );

	if ( wordmark && ! reducedMotion ) {
		var pieces = Array.prototype.slice.call( wordmark.querySelectorAll( 'path' ) );
		pieces.unshift( pieces.pop() );

		var viewBox = wordmark.viewBox.baseVal;
		var travel  = ( viewBox && viewBox.height ? viewBox.height : 24 ) * ( 20 / 128 ); // 20 px sobre 128 px del diseño, en unidades del SVG
		var ease    = function ( t ) { return t >= 1 ? 1 : 1 - Math.pow( 2, -10 * t ); };
		var ticking = false;

		var paint = function () {
			ticking = false;
			var rect = wordmark.getBoundingClientRect();
			var vh   = window.innerHeight;
			// 0 cuando el borde superior toca el fondo de la ventana; 1 cuando el logo entero está a un 15 % del fondo.
			var progress = ( vh - rect.top ) / ( rect.height + vh * 0.15 );

			pieces.forEach( function ( piece, i ) {
				var t = ease( Math.min( 1, Math.max( 0, ( progress - i * 0.08 ) / 0.5 ) ) );
				piece.style.translate = '0 ' + ( ( 1 - t ) * travel ).toFixed( 3 ) + 'px';
				piece.style.opacity   = t.toFixed( 3 );
			} );
		};

		var onScroll = function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( paint );
			}
		};

		paint();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll );
	}

	/* --- Barra "pegada" al hacer scroll ---
	 * .is-stuck se activa cuando el centinela (48 px: franja clara + esquinas
	 * de la pestaña de la portada) sale del viewport, para que la transición
	 * no se dispare en el primer píxel de scroll.
	 */
	var navbar = document.querySelector( '.match-navbar' );

	if ( navbar && 'IntersectionObserver' in window ) {
		var sentinel = document.createElement( 'div' );
		sentinel.style.cssText = 'position:absolute;top:0;height:48px;width:1px;pointer-events:none;';
		document.body.prepend( sentinel );

		new IntersectionObserver( function ( entries ) {
			navbar.classList.toggle( 'is-stuck', ! entries[ 0 ].isIntersecting );
		} ).observe( sentinel );
	}

	/* --- Barra que se oculta al bajar ---
	 * .is-hidden (translate en header.css) al bajar más de 8 px pasada la
	 * altura de la barra; al subir vuelve. Nunca con el menú móvil abierto.
	 * Lenis usa el scroll nativo, así que basta con el evento scroll.
	 */
	if ( navbar ) {
		var lastY = window.scrollY;
		var navTicking = false;

		var updateNavbar = function () {
			navTicking = false;
			var y = window.scrollY;
			var delta = y - lastY;

			if ( document.body.classList.contains( 'has-open-nav' ) || y <= navbar.offsetHeight ) {
				navbar.classList.remove( 'is-hidden' );
				lastY = y;
			} else if ( Math.abs( delta ) > 8 ) {
				navbar.classList.toggle( 'is-hidden', delta > 0 );
				if ( delta > 0 ) { closeMega(); } // el mega menú se va con la barra
				lastY = y;
			}
		};

		window.addEventListener( 'scroll', function () {
			if ( ! navTicking ) {
				navTicking = true;
				window.requestAnimationFrame( updateNavbar );
			}
		}, { passive: true } );

		// Con teclado, el foco dentro de la barra la vuelve a mostrar.
		navbar.addEventListener( 'focusin', function () { navbar.classList.remove( 'is-hidden' ); } );
	}

	/* --- Índice de las páginas legales (scroll-spy) ---
	 * Resalta en el índice el h2 que está bajo la franja superior del
	 * viewport; los enlaces son anclas normales, el clic ya lo maneja el
	 * gestor de arriba (scroll suave con Lenis si está disponible).
	 */
	var legalToc = document.querySelector( '[data-legal-toc]' );

	if ( legalToc && 'IntersectionObserver' in window ) {
		var legalLinks = Array.prototype.slice.call( legalToc.querySelectorAll( '[data-legal-toc-link]' ) );
		var legalSections = legalLinks
			.map( function ( link ) { return document.getElementById( link.hash.slice( 1 ) ); } )
			.filter( Boolean );

		var setActiveLegalLink = function ( id ) {
			legalLinks.forEach( function ( link ) {
				link.classList.toggle( 'is-active', link.hash.slice( 1 ) === id );
			} );
		};

		var legalObserver = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) { setActiveLegalLink( entry.target.id ); }
				} );
			},
			{ rootMargin: '-96px 0px -70% 0px', threshold: 0 }
		);

		legalSections.forEach( function ( section ) { legalObserver.observe( section ); } );
		setActiveLegalLink( legalSections.length ? legalSections[ 0 ].id : '' );
	}

	/* --- Botón CTA del hero (.match-btn--cta) ---
	 * Port de MATCH-CTA-BUTTON/Boton Subir CV: los dos destellos recorren el
	 * contorno redondeado (uno frente al otro) en 8 s; en hover o foco se
	 * frenan, el botón brilla y el borde suma un degradado cónico blanco. El
	 * bucle solo corre mientras el botón está en pantalla. Con
	 * prefers-reduced-motion los destellos no giran pero el hover sí responde.
	 */
	Array.prototype.forEach.call( document.querySelectorAll( '.match-btn--cta' ), function ( btn ) {
		var inner = btn.querySelector( '.match-btn__cta-inner' );
		var blobs = btn.querySelectorAll( '.match-btn__cta-blob' );
		var glowEl = btn.querySelector( '.match-btn__cta-glow' );

		if ( ! inner || blobs.length < 2 || ! glowEl ) { return; }

		var DURATION = 8000;
		var LIN_STOPS = 'rgba(135,129,255,0.4) 0%, #FFFFFF 50%, rgba(135,129,255,0.4) 100%';
		var phi = -Math.PI / 2;
		var speed = 1;
		var glow = 0;
		var paintP = 0;
		var hover = false;
		var raf = 0;
		var last = 0;

		var setHover = function ( value ) {
			return function () { hover = value; };
		};

		btn.addEventListener( 'pointerenter', setHover( true ) );
		btn.addEventListener( 'pointerleave', setHover( false ) );
		btn.addEventListener( 'focus', setHover( true ) );
		btn.addEventListener( 'blur', setHover( false ) );

		var paint = function () {
			var w = inner.offsetWidth;
			var h = inner.offsetHeight;
			var r = blobs[ 0 ].offsetWidth / 2;
			var m = 6;
			var W = w - 2 * m;
			var H = h - 2 * m;
			var rc = Math.min( 14, H / 2 );
			var L1 = W - 2 * rc;
			var L2 = H - 2 * rc;
			var A = Math.PI / 2 * rc;
			var P = 2 * L1 + 2 * L2 + 4 * A;
			var hw = W / 2;
			var hh = H / 2;
			var d = ( ( phi + Math.PI / 2 ) / ( Math.PI * 2 ) ) * P + L1 / 2;
			var segs = [
				[ L1, function ( u ) { return [ -hw + rc + u, -hh ]; } ],
				[ A, function ( u ) { var a = -Math.PI / 2 + u / rc; return [ hw - rc + rc * Math.cos( a ), -hh + rc + rc * Math.sin( a ) ]; } ],
				[ L2, function ( u ) { return [ hw, -hh + rc + u ]; } ],
				[ A, function ( u ) { var a = u / rc; return [ hw - rc + rc * Math.cos( a ), hh - rc + rc * Math.sin( a ) ]; } ],
				[ L1, function ( u ) { return [ hw - rc - u, hh ]; } ],
				[ A, function ( u ) { var a = Math.PI / 2 + u / rc; return [ -hw + rc + rc * Math.cos( a ), hh - rc + rc * Math.sin( a ) ]; } ],
				[ L2, function ( u ) { return [ -hw, hh - rc - u ]; } ],
				[ A, function ( u ) { var a = Math.PI + u / rc; return [ -hw + rc + rc * Math.cos( a ), -hh + rc + rc * Math.sin( a ) ]; } ]
			];
			var x = 0;
			var y = 0;

			d = ( ( d % P ) + P ) % P;

			for ( var i = 0; i < segs.length; i++ ) {
				if ( d <= segs[ i ][ 0 ] ) {
					var pt = segs[ i ][ 1 ]( d );
					x = pt[ 0 ];
					y = pt[ 1 ];
					break;
				}
				d -= segs[ i ][ 0 ];
			}

			blobs[ 0 ].style.transform = 'translate(' + ( w / 2 + x - r ) + 'px, ' + ( h / 2 + y - r ) + 'px)';
			blobs[ 1 ].style.transform = 'translate(' + ( w / 2 - x - r ) + 'px, ' + ( h / 2 - y - r ) + 'px)';

			// Borde: degradado lineal que gira con los destellos y, en hover,
			// un cónico blanco que se abre desde ellos (ease-in-out cúbico).
			var angle = Math.atan2( -y, -x ) * 180 / Math.PI;
			var e = paintP < 0.5 ? 4 * paintP * paintP * paintP : 1 - Math.pow( -2 * paintP + 2, 3 ) / 2;
			var th = Math.atan2( x, -y ) * 180 / Math.PI;
			var s = e * 100;
			var f = 1 + 34 * ( 1 - e );
			var WH = '#FFFFFF';
			var T = 'rgba(255,255,255,0)';
			var lin = 'linear-gradient(' + angle + 'deg, ' + LIN_STOPS + ')';
			var conic = 'conic-gradient(from ' + th + 'deg, ' +
				WH + ' 0deg, ' + WH + ' ' + s + 'deg, ' + T + ' ' + ( s + f ) + 'deg, ' +
				T + ' ' + ( 180 - s - f ) + 'deg, ' + WH + ' ' + ( 180 - s ) + 'deg, ' +
				WH + ' ' + ( 180 + s ) + 'deg, ' + T + ' ' + ( 180 + s + f ) + 'deg, ' +
				T + ' ' + ( 360 - s - f ) + 'deg, ' + WH + ' ' + ( 360 - s ) + 'deg, ' + WH + ' 360deg)';

			btn.style.background = paintP > 0.001 ? conic + ', ' + lin : lin;
		};

		var tick = function ( now ) {
			raf = window.requestAnimationFrame( tick );

			var dt = Math.min( 64, now - last );
			last = now;

			speed += ( ( hover ? 0 : 1 ) - speed ) * ( 1 - Math.pow( 0.04, dt / 1000 ) );
			glow += ( ( hover ? 1 : 0 ) - glow ) * ( 1 - Math.pow( 0.002, dt / 1000 ) );
			paintP = hover ? Math.min( 1, paintP + dt / 1100 ) : Math.max( 0, paintP - dt / 700 );

			btn.style.boxShadow = '0 0 ' + ( 18 * glow ) + 'px rgba(135,129,255,' + ( 0.55 * glow ) + '), 0 0 ' + ( 56 * glow ) + 'px rgba(27,92,216,' + ( 0.45 * glow ) + ')';
			glowEl.style.opacity = glow;

			if ( ! reducedMotion ) { phi += speed * ( dt / DURATION ) * Math.PI * 2; }

			paint();
		};

		var start = function () {
			if ( raf ) { return; }
			last = performance.now();
			raf = window.requestAnimationFrame( tick );
		};

		var stop = function () {
			window.cancelAnimationFrame( raf );
			raf = 0;
		};

		btn.classList.add( 'is-live' );
		paint();

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) { start(); } else { stop(); }
			} ).observe( btn );
		} else {
			start();
		}
	} );
}() );

/* --- Mostrar/ocultar contraseña ---
 * Todo <input type="password"> del sitio recibe un botón de ojo que alterna entre texto y puntos. Se añade por JS
 * para cubrir también los formularios que se agreguen después; sin JS el campo funciona igual, solo sin el ojo. */
( function () {
	var eyeOn = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
	var eyeOff = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7c1.7 0 3.2-.4 4.5-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';

	document.querySelectorAll( 'input[type="password"]' ).forEach( function ( input ) {
		if ( input.closest( '.match-pass' ) ) { return; }
		var wrap = document.createElement( 'span' );
		wrap.className = 'match-pass';
		input.parentNode.insertBefore( wrap, input );
		wrap.appendChild( input );

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'match-pass__toggle';
		btn.setAttribute( 'aria-label', 'Mostrar contraseña' );
		btn.setAttribute( 'aria-pressed', 'false' );
		btn.innerHTML = eyeOn;
		wrap.appendChild( btn );

		btn.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			btn.innerHTML = show ? eyeOff : eyeOn;
			btn.setAttribute( 'aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña' );
			btn.setAttribute( 'aria-pressed', show ? 'true' : 'false' );
			input.focus();
		} );
	} );
}() );
