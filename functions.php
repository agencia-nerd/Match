<?php
/**
 * Tema Match.
 */

defined( 'ABSPATH' ) || exit;

define( 'MATCH_VERSION', '0.1.0' );

/**
 * Versión de un asset del tema según su fecha de modificación, para que el
 * navegador descargue de nuevo el archivo cada vez que cambia (cache busting).
 */
function match_asset_ver( string $path ): string {
	$file = get_theme_file_path( $path );
	return file_exists( $file ) ? (string) filemtime( $file ) : MATCH_VERSION;
}

/**
 * Soportes del tema.
 */
function match_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 46, 'width' => 140, 'flex-width' => true ) );

	register_nav_menus(
		array(
			'footer_jobs' => __( 'Footer — Job Board', 'match' ),
			'footer_nav'  => __( 'Footer — Navegación', 'match' ),
			'footer_social' => __( 'Footer — Redes', 'match' ),
		)
	);
}
add_action( 'after_setup_theme', 'match_setup' );

/**
 * Estilos y scripts.
 *
 * Orden de carga: fuentes → tokens → base → componentes → header → footer →
 * (solución) → AOS → Lenis → Tailwind. Tailwind va al final a propósito: sus
 * utilidades no están en @layer y deben ganarle al CSS BEM a igual
 * especificidad. Se compila con `npm run build` (ver README).
 *
 * Las fuentes se sirven localmente desde assets/fonts para no depender de
 * Google Fonts: evita el salto de layout y el problema de privacidad de
 * enviar la IP de cada visitante a un tercero.
 */
function match_assets(): void {
	$css = array(
		'match-fonts'      => array( 'assets/css/fonts.css', array() ),
		'match-tokens'     => array( 'assets/css/tokens.css', array( 'match-fonts' ) ),
		'match-base'       => array( 'assets/css/base.css', array( 'match-tokens' ) ),
		'match-components' => array( 'assets/css/components.css', array( 'match-base' ) ),
		'match-home'       => array( 'assets/css/home.css', array( 'match-components' ) ),
		'match-header'     => array( 'assets/css/header.css', array( 'match-home' ) ),
		'match-footer'     => array( 'assets/css/footer.css', array( 'match-components' ) ),
	);

	foreach ( $css as $handle => list( $path, $deps ) ) {
		wp_enqueue_style( $handle, get_theme_file_uri( $path ), $deps, match_asset_ver( $path ) );
	}

	if ( match_uses_preloader() ) {
		wp_enqueue_style( 'match-preloader', get_theme_file_uri( 'assets/css/preloader.css' ), array( 'match-fonts' ), match_asset_ver( 'assets/css/preloader.css' ) );
		wp_enqueue_script( 'match-preloader', get_theme_file_uri( 'assets/js/preloader.js' ), array(), match_asset_ver( 'assets/js/preloader.js' ), array( 'in_footer' => false, 'strategy' => 'defer' ) );
	}

	if ( is_page_template( array( 'template-solucion.php', 'template-executive.php', 'template-personnel.php', 'template-outplacement.php', 'template-assessment.php' ) ) ) {
		wp_enqueue_style( 'match-solucion', get_theme_file_uri( 'assets/css/solucion.css' ), array( 'match-footer' ), match_asset_ver( 'assets/css/solucion.css' ) );
	}

	if ( is_page_template( 'template-programas.php' ) ) {
		wp_enqueue_style( 'match-programas', get_theme_file_uri( 'assets/css/programas.css' ), array( 'match-footer' ), match_asset_ver( 'assets/css/programas.css' ) );
		wp_enqueue_script( 'match-programas', get_theme_file_uri( 'assets/js/programas.js' ), array( 'match-app' ), match_asset_ver( 'assets/js/programas.js' ), true );
	}

	if ( is_page_template( 'template-blog.php' ) || is_singular( 'post' ) ) {
		wp_enqueue_style( 'match-blog', get_theme_file_uri( 'assets/css/blog.css' ), array( 'match-footer' ), match_asset_ver( 'assets/css/blog.css' ) );
	}

	if ( is_page_template( 'template-design-system.php' ) ) {
		wp_enqueue_style( 'match-styleguide', get_theme_file_uri( 'assets/css/styleguide.css' ), array( 'match-footer' ), match_asset_ver( 'assets/css/styleguide.css' ) );
	}

	if ( match_is_jobboard_view() ) {
		wp_enqueue_style( 'match-jobboard', get_theme_file_uri( 'assets/css/jobboard.css' ), array( 'match-footer' ), match_asset_ver( 'assets/css/jobboard.css' ) );
	}

	wp_enqueue_style( 'match-style', get_stylesheet_uri(), array( 'match-header', 'match-footer' ), match_asset_ver( 'style.css' ) );

	// Animaciones de entrada y utilidades (Tailwind), siempre al final. Las
	// páginas migradas a GSAP (match_uses_gsap) ya no cargan AOS.
	$gsap = match_uses_gsap();

	if ( ! $gsap ) {
		wp_enqueue_style( 'match-aos', get_theme_file_uri( 'assets/vendor/aos.css' ), array( 'match-style' ), '2.3.4' );
	}
	wp_enqueue_style( 'match-lenis', get_theme_file_uri( 'assets/vendor/lenis.css' ), array( $gsap ? 'match-style' : 'match-aos' ), '1.3.26' );
	wp_enqueue_style( 'match-tailwind', get_theme_file_uri( 'assets/css/tailwind.css' ), array( 'match-lenis' ), match_asset_ver( 'assets/css/tailwind.css' ) );

	$app_deps = array( 'embla-carousel', 'lenis' );

	if ( $gsap ) {
		// En <head> con defer: se descargan en paralelo al HTML y corren tras el
		// parseo, después de app.js (que expone window.matchLenis).
		$defer = array( 'in_footer' => false, 'strategy' => 'defer' );
		wp_enqueue_script( 'gsap', get_theme_file_uri( 'assets/vendor/gsap.min.js' ), array(), '3.15.0', $defer );
		wp_enqueue_script( 'gsap-scrolltrigger', get_theme_file_uri( 'assets/vendor/ScrollTrigger.min.js' ), array( 'gsap' ), '3.15.0', $defer );
		wp_enqueue_script( 'gsap-splittext', get_theme_file_uri( 'assets/vendor/SplitText.min.js' ), array( 'gsap' ), '3.15.0', $defer );
		wp_enqueue_script( 'match-reveal', get_theme_file_uri( 'assets/js/reveal.js' ), array( 'gsap', 'gsap-scrolltrigger', 'gsap-splittext' ), match_asset_ver( 'assets/js/reveal.js' ), $defer );
	} else {
		wp_enqueue_script( 'aos', get_theme_file_uri( 'assets/vendor/aos.js' ), array(), '2.3.4', true );
		$app_deps[] = 'aos';
	}

	wp_enqueue_script( 'embla-carousel', get_theme_file_uri( 'assets/vendor/embla-carousel.umd.js' ), array(), '8.6.0', true );
	wp_enqueue_script( 'lenis', get_theme_file_uri( 'assets/vendor/lenis.min.js' ), array(), '1.3.26', true );
	wp_enqueue_script( 'match-app', get_theme_file_uri( 'assets/js/app.js' ), $app_deps, match_asset_ver( 'assets/js/app.js' ), true );
	wp_localize_script(
		'match-app',
		'MatchJB',
		array(
			'rest'     => esc_url_raw( rest_url( 'match/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'loggedIn' => is_user_logged_in(),
			'loginUrl' => wp_login_url( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ) ),
		)
	);

	// El plugin hereda los tokens del tema.
	if ( wp_style_is( 'mjb-front', 'registered' ) ) {
		wp_style_add_data( 'mjb-front', 'after', '' );
	}
}
add_action( 'wp_enqueue_scripts', 'match_assets' );

/**
 * Precarga de las fuentes que se ven en el primer pintado.
 */
function match_preload_fonts(): void {
	$fonts = array(
		'assets/fonts/poppins-regular.woff2',
		'assets/fonts/poppins-medium.woff2',
		'assets/fonts/poppins-semibold.woff2',
		'assets/fonts/open-sauce-sans-bold.woff2',
	);

	foreach ( $fonts as $font ) {
		if ( file_exists( get_theme_file_path( $font ) ) ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( get_theme_file_uri( $font ) )
			);
		}
	}
}
add_action( 'wp_head', 'match_preload_fonts', 1 );

/**
 * ¿La vista usa las animaciones de entrada con GSAP (assets/js/reveal.js)?
 *
 * Migración progresiva desde AOS: por ahora solo la portada. El resto sigue
 * con data-aos hasta que se migre.
 */
function match_uses_gsap(): bool {
	return is_front_page();
}

/**
 * Estado inicial de las animaciones GSAP, antes del primer pintado.
 *
 * html.has-reveal oculta los [data-reveal] (solo visibility, no mueve el
 * layout: sin CLS) hasta que reveal.js los anima. No se añade con
 * prefers-reduced-motion ni sin JavaScript, y se retira si GSAP no llega a
 * cargar, para que el contenido nunca quede oculto. El lead del hero no se
 * oculta del todo (opacity .01) para que siga contando como candidato a LCP, y
 * la foto del hero arranca ampliada (solo transform) para su zoom de entrada.
 */
function match_reveal_boot(): void {
	if ( ! match_uses_gsap() ) {
		return;
	}
	?>
	<style id="match-reveal-boot">
		.has-reveal [data-reveal]:not(.is-revealed) { visibility: hidden; }
		.has-reveal [data-reveal="hero-lead"]:not(.is-revealed) { visibility: visible; opacity: .01; }
		.has-reveal .match-hero__media img:not(.is-revealed) { transform: scale(1.12); }
	</style>
	<script>
		( function ( html ) {
			if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { return; }
			html.classList.add( 'has-reveal' );
			setTimeout( function () { if ( ! window.matchReveal ) { html.classList.remove( 'has-reveal' ); } }, 4000 );
		} )( document.documentElement );
	</script>
	<?php
}
add_action( 'wp_head', 'match_reveal_boot', 2 );

/**
 * ¿La vista puede mostrar el preloader? En todo el front salvo el Customizer.
 */
function match_uses_preloader(): bool {
	return ! is_admin() && ! is_customize_preview();
}

/**
 * Preloader solo al entrar tras 12 h sin visitar la web.
 *
 * Decide en el cliente, antes del primer pintado, porque el HTML sale de la
 * caché de página (SiteGround) y es igual para todos. Usa una cookie de 12 h
 * que se renueva en cada carga: la comparten todas las pestañas, así que no se
 * repite al recargar, al abrir otra pestaña o ventana ni al navegar; vuelve a
 * salir tras 12 h de inactividad. No se usa cookie de sesión porque Chrome y
 * Edge la conservan al "continuar donde lo dejaste" o al quedar en segundo
 * plano. La cookie se pone al empezar, no al terminar: recargar a mitad
 * tampoco lo repite. Sin cookies no se puede recordar, así que no se muestra.
 * Si preloader.js no llega a correr, el seguro libera la página.
 */
function match_preloader_boot(): void {
	if ( ! match_uses_preloader() ) {
		return;
	}
	?>
	<script id="match-preloader-boot">
		( function ( html ) {
			var seen = /(?:^|;\s*)match_preloader_seen=1/;
			var wasSeen = seen.test( document.cookie );
			document.cookie = 'match_preloader_seen=1; path=/; max-age=43200; SameSite=Lax' + ( location.protocol === 'https:' ? '; Secure' : '' );
			if ( wasSeen || ! seen.test( document.cookie ) ) { return; }
			html.classList.add( 'match-preloading' );
			setTimeout( function () { if ( ! window.matchPreloader ) { html.classList.remove( 'match-preloading' ); } }, 4000 );
		} )( document.documentElement );
	</script>
	<?php
}
add_action( 'wp_head', 'match_preloader_boot', 0 );

/**
 * Markup del preloader, primer elemento del <body> (header.php y
 * header-jobboard.php llaman a wp_body_open).
 */
function match_preloader_markup(): void {
	if ( match_uses_preloader() ) {
		get_template_part( 'template-parts/preloader' );
	}
}
add_action( 'wp_body_open', 'match_preloader_markup', 1 );

/**
 * Atributos de animación de entrada para piezas compartidas entre la portada
 * (GSAP) y las internas (AOS). Devuelve el atributo con espacio inicial.
 *
 * match_reveal(): data-reveal solo en vistas GSAP (vacío en el resto).
 * match_aos():    data-aos solo en vistas AOS (vacío en las GSAP).
 */
function match_reveal( string $type = 'up' ): string {
	return match_uses_gsap() ? sprintf( ' data-reveal="%s"', esc_attr( $type ) ) : '';
}

function match_aos( int $delay = 0 ): string {
	if ( match_uses_gsap() ) {
		return '';
	}
	return ' data-aos="fade-up"' . ( $delay ? sprintf( ' data-aos-delay="%d"', $delay ) : '' );
}

/**
 * Job Board: la animación de entrada (AOS) solo la primera vez que se entra.
 *
 * Los filtros, el orden, la paginación y el paso entre páginas del Job Board
 * recargan la página (GET); si la entrada se repetía, parecía que se había
 * recargado todo el sitio. Se decide en el cliente, antes del primer pintado,
 * porque el HTML sale de la caché de página y es igual para todos: la primera
 * vista del Job Board en la pestaña guarda una marca en sessionStorage y las
 * siguientes llevan html.match-jb-no-intro, que muestra los [data-aos] ya en
 * su sitio. app.js, con esa clase, no inicia AOS. Sin sessionStorage se anima
 * siempre, como antes.
 */
function match_jobboard_intro_boot(): void {
	?>
	<style id="match-jb-intro-boot">
		.match-jb-no-intro [data-aos] { opacity: 1 !important; transform: none !important; transition: none !important; }
	</style>
	<script>
		( function ( html ) {
			try {
				var key = 'match-jb-intro-seen';
				if ( window.sessionStorage.getItem( key ) ) { html.classList.add( 'match-jb-no-intro' ); }
				window.sessionStorage.setItem( key, '1' );
			} catch ( e ) {}
		} )( document.documentElement );
	</script>
	<?php
}

/**
 * Solo en las vistas del Job Board, las que cargan header-jobboard.php.
 */
function match_jobboard_intro_hook( ?string $name ): void {
	if ( 'jobboard' === $name ) {
		add_action( 'wp_head', 'match_jobboard_intro_boot', 3 );
	}
}
add_action( 'get_header', 'match_jobboard_intro_hook' );

/**
 * ¿Está activo el plugin del Job Board?
 *
 * Todas las plantillas de vacantes lo comprueban antes de pintar nada, para que
 * el tema no reviente si el plugin se desactiva.
 */
function match_job_board_active(): bool {
	return class_exists( 'MJB_Helpers' );
}

/**
 * Logo de empresa con respaldo de monograma.
 *
 * El CRM entrega logos tipo favicon (32–64 px) y algunos vienen vacíos, así que
 * la plantilla nunca asume que hay imagen.
 */
function match_company_logo( int $job_id, int $size = 96 ): void {
	if ( ! match_job_board_active() ) {
		return;
	}

	$logo    = (string) MJB_Helpers::get_meta( $job_id, 'company_logo' );
	$company = (string) MJB_Helpers::get_meta( $job_id, 'company_name' );

	printf( '<div class="match-logo" style="--logo-size:%dpx">', (int) $size );

	if ( $logo ) {
		printf(
			'<img src="%s" alt="" loading="lazy" decoding="async">',
			esc_url( $logo )
		);
	} else {
		printf(
			'<span class="match-logo__monogram" aria-hidden="true">%s</span>',
			esc_html( mb_substr( $company ?: '—', 0, 1 ) )
		);
	}

	echo '</div>';
}

/**
 * Título de sección reutilizable (eyebrow + titular en dos tonos).
 *
 * $dark pinta la variante blanca (sección Vacantes).
 */
function match_section_head( string $eyebrow, string $line1, string $line2 = '', string $size = 'h2', bool $dark = false ): void {
	?>
	<header class="match-section__head<?php echo $dark ? ' match-section__head--dark' : ''; ?>"<?php echo match_aos() . match_reveal( 'head' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<p class="match-eyebrow">
			<span class="match-eyebrow__mark" aria-hidden="true"><?php echo match_icon( $dark ? 'eyebrow-mark-light' : 'eyebrow-mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php echo esc_html( $eyebrow ); ?>
		</p>
		<h2 class="match-<?php echo esc_attr( $size ); ?>">
			<?php echo esc_html( $line1 ); ?>
			<?php if ( $line2 ) : ?>
				<span class="is-secondary"><?php echo esc_html( $line2 ); ?></span>
			<?php endif; ?>
		</h2>
	</header>
	<?php
}

/**
 * Longitud del extracto en las tarjetas.
 */
function match_excerpt_length(): int {
	return 24;
}
add_filter( 'excerpt_length', 'match_excerpt_length' );

/**
 * Chevron en los ítems del menú que despliegan submenú (Figma: Icon/Chevron).
 */
function match_nav_chevron( string $title, WP_Post $item, stdClass $args ): string {
	if ( 'primary' !== ( $args->theme_location ?? '' ) || ! in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		return $title;
	}
	return $title . match_icon( 'chevron', 'match-nav__chevron' );
}
add_filter( 'nav_menu_item_title', 'match_nav_chevron', 10, 3 );

/**
 * Datos de contacto del pie, editables desde el Personalizador.
 */
function match_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'match_contact',
		array( 'title' => __( 'Match — Contacto', 'match' ), 'priority' => 30 )
	);

	$fields = array(
		'match_phone'        => array( __( 'Teléfono', 'match' ), '(+51) 908825057' ),
		'match_email'        => array( __( 'Correo', 'match' ), 'conversemos@match.win' ),
		'match_ruc'          => array( __( 'RUC', 'match' ), '20613126725' ),
		'match_razon_social' => array( __( 'Razón social', 'match' ), 'MATCH GROUP S.A.C.' ),
	);

	foreach ( $fields as $id => list( $label, $default ) ) {
		$wp_customize->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( $id, array( 'label' => $label, 'section' => 'match_contact', 'type' => 'text' ) );
	}

	// Color por defecto del isotipo del logo grande del pie. Vacío = su degradado
	// celeste original. Cada página puede cambiarlo (ver match_footer_iso_color()).
	$wp_customize->add_setting( 'match_footer_iso_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'match_footer_iso_color',
			array(
				'label'       => __( 'Color del isotipo del pie (por defecto)', 'match' ),
				'description' => __( 'Ícono del logo grande al final de cada página. Vacío (recomendado): el degradado azul de 60°. Ojo: un color aquí se aplica a TODAS las páginas sin color propio; para una sola página usa el campo de su editor.', 'match' ),
				'section'     => 'match_contact',
			)
		)
	);
}

/**
 * Color del isotipo del logo del pie: el de la página, si lo tiene; en las
 * soluciones, si no, el de su paleta (CSS); en el resto, el del
 * Personalizador. Cadena vacía = lo decide el CSS (paleta o degradado).
 */
function match_footer_iso_color(): string {
	$color = '';
	if ( is_singular() && function_exists( 'get_field' ) ) {
		$color = (string) get_field( 'footer_iso_color', get_queried_object_id() );
	}
	if ( sanitize_hex_color( $color ) ) {
		return sanitize_hex_color( $color );
	}
	// En las soluciones manda la paleta de la página (--sol-iso en solucion.css).
	if ( is_page_template( array( 'template-solucion.php', 'template-executive.php', 'template-personnel.php', 'template-outplacement.php', 'template-assessment.php' ) ) ) {
		return '';
	}
	return (string) sanitize_hex_color( (string) get_theme_mod( 'match_footer_iso_color', '' ) );
}

add_action( 'customize_register', 'match_customize_register' );

/**
 * Formulario "Hablemos": valida, envía por correo y vuelve a la página desde
 * la que se envió (campo "volver"; solo URLs de este sitio) con el estado en
 * la URL. En Programas incluye el programa de interés.
 */
function match_handle_contact(): void {
	// phpcs:disable WordPress.Security.NonceVerification -- El nonce se verifica abajo; esto solo arma la vuelta.
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['volver'] ?? '' ) ), home_url( '/' ) );
	// Desde el formulario emergente se vuelve sin #contacto: el modal se
	// reabre con el aviso (template-parts/contact-modal.php).
	$modal  = isset( $_POST['origen'] ) && 'modal' === $_POST['origen'];
	$anchor = $modal ? '' : '#contacto';
	// phpcs:enable

	if ( ! isset( $_POST['match_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['match_contact_nonce'] ), 'match_contact' ) || ! empty( $_POST['match_web'] ) ) {
		wp_safe_redirect( add_query_arg( array_filter( array( 'contacto' => 'error', 'origen' => $modal ? 'modal' : '' ) ), $back ) . $anchor );
		exit;
	}

	$perfil   = isset( $_POST['perfil'] ) && 'profesional' === $_POST['perfil'] ? 'profesional' : 'empresa';
	$nombre   = match_clean_person_name( $_POST['nombre'] ?? '' );
	$correo   = sanitize_email( wp_unslash( $_POST['correo'] ?? '' ) );
	$mensaje  = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );
	$programa = sanitize_text_field( wp_unslash( $_POST['programa'] ?? '' ) );
	$servicio = sanitize_text_field( wp_unslash( $_POST['servicio'] ?? '' ) );
	$estado   = array_filter(
		array(
			'perfil'   => $perfil,
			'programa' => $programa,
			'servicio' => $servicio,
			'origen'   => $modal ? 'modal' : '',
		)
	);

	// Nombre y mensaje con las mismas reglas que el front (data-name-input / data-text-input).
	if ( ! $nombre || ! match_valid_person_name( $nombre ) || ! is_email( $correo ) || ! $mensaje || ! match_valid_message( $mensaje ) ) {
		wp_safe_redirect( add_query_arg( array( 'contacto' => 'error' ) + $estado, $back ) . $anchor );
		exit;
	}

	$to      = get_theme_mod( 'match_email', get_option( 'admin_email' ) );
	$subject = sprintf( '[Match] Contacto (%s): %s', $programa ?: ( $servicio ?: $perfil ), $nombre );
	$body    = "Perfil: {$perfil}\n" . ( $servicio ? "Servicio: {$servicio}\n" : '' ) . ( $programa ? "Programa: {$programa}\n" : '' ) . "Nombre: {$nombre}\nCorreo: {$correo}\nPágina: {$back}\n\n{$mensaje}";
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $correo ) );

	wp_safe_redirect( add_query_arg( array( 'contacto' => $sent ? 'ok' : 'error' ) + $estado, $back ) . $anchor );
	exit;
}
add_action( 'admin_post_match_contact', 'match_handle_contact' );
add_action( 'admin_post_nopriv_match_contact', 'match_handle_contact' );

require_once get_theme_file_path( 'inc/template-tags.php' );
require_once get_theme_file_path( 'inc/jobs-filters.php' );
require_once get_theme_file_path( 'inc/jobboard.php' );
require_once get_theme_file_path( 'inc/login-recovery.php' );
require_once get_theme_file_path( 'inc/google-auth.php' );
require_once get_theme_file_path( 'inc/welcome-email.php' );
require_once get_theme_file_path( 'inc/notifications.php' );
require_once get_theme_file_path( 'inc/soluciones-data.php' );
require_once get_theme_file_path( 'inc/home-data.php' );
require_once get_theme_file_path( 'inc/legal-data.php' );
require_once get_theme_file_path( 'inc/blog.php' );
require_once get_theme_file_path( 'inc/admin-orden-casillas.php' );

// TEMPORAL: pruebas de usabilidad. Quitar junto con inc/usability-test*.php.
if ( defined( 'MATCH_UX_TEST' ) && MATCH_UX_TEST ) {
	require_once get_theme_file_path( 'inc/usability-test.php' );
}



/**
 * Título de pestaña de la 404 en español (J21).
 */
function match_404_title( array $parts ): array {
	if ( is_404() ) {
		$parts['title'] = __( 'Página no encontrada', 'match' );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'match_404_title' );
