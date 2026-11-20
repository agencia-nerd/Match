<?php
/**
 * Barra lateral del Job Board (Figma: público 4529:4, con sesión 4125:26).
 *
 * @var array $args { current: inicio|vacantes|procesos|guardados|perfil }
 */
defined( 'ABSPATH' ) || exit;

$current = $args['current'] ?? '';
$items   = array(
	'inicio'   => array( __( 'Inicio', 'match' ), match_jobboard_url(), 'home' ),
	'vacantes' => array( __( 'Vacantes', 'match' ), match_jobs_url(), 'briefcase' ),
);

$counts = array();

if ( is_user_logged_in() ) {
	// Las secciones aún sin página quedan visibles pero deshabilitadas.
	$items['procesos']  = array( __( 'Mis procesos', 'match' ), match_jobboard_page_url( 'template-procesos.php' ), 'address-book' );
	$items['guardados'] = array( __( 'Guardados', 'match' ), match_jobboard_page_url( 'template-guardados.php' ), 'bookmark-simple' );
	$items['perfil']    = array( __( 'Perfil', 'match' ), match_profile_url(), 'user-circle-nav' );
	$counts             = array(
		'procesos'  => count( match_user_applications() ),
		'guardados' => count( match_saved_map() ),
	);
} else {
	// Sin sesión: las secciones privadas se ven pero llevan al login y, al
	// entrar, el candidato aterriza en la que eligió.
	$locked = array(
		'procesos'  => array( __( 'Mis procesos', 'match' ), match_jobboard_page_url( 'template-procesos.php' ), 'address-book' ),
		'guardados' => array( __( 'Guardados', 'match' ), match_jobboard_page_url( 'template-guardados.php' ), 'bookmark-simple' ),
		'perfil'    => array( __( 'Perfil', 'match' ), match_profile_url(), 'user-circle-nav' ),
	);
	foreach ( $locked as $key => list( $label, $url, $icon ) ) {
		$items[ $key ] = array( $label, $url ?: match_jobboard_url(), $icon, true );
	}
}
?>
<aside class="match-jb__sidebar" data-jb-sidebar>
	<?php
	// Desktop: aplica el estado contraído guardado antes de pintar, sin salto.
	// .is-booting apaga transiciones y animaciones de la barra durante la carga
	// (si no, cada recarga —p. ej. al filtrar— repetía la animación del isotipo
	// y del ancho); se quita tras el primer pintado, para que el botón anime.
	?>
	<script>(function(el){el.classList.add('is-booting');try{if(localStorage.getItem('matchJbCollapsed')==='1'){el.classList.add('is-collapsed');}}catch(e){}var r=window.requestAnimationFrame||function(f){setTimeout(f,16);};r(function(){r(function(){el.classList.remove('is-booting');});});})(document.currentScript.parentNode);</script>
	<div class="match-jb__bar">
		<a class="match-jb__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php echo match_logo( 'match-jb__logo-full' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php // Barra contraída (desktop): solo el isotipo. ?>
			<?php echo match_icon( 'logo-sidebar', 'match-jb__logo-mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>

		<?php // Mobile: hamburguesa que abre el menú. Desktop: tirador redondo en el borde, a media altura, que contrae la barra a solo iconos. ?>
		<button class="match-jb__toggle" type="button" aria-expanded="false" aria-controls="match-jb-menu" aria-label="<?php esc_attr_e( 'Abrir el menú', 'match' ); ?>" data-label-open="<?php esc_attr_e( 'Abrir el menú', 'match' ); ?>" data-label-close="<?php esc_attr_e( 'Cerrar el menú', 'match' ); ?>" data-label-collapse="<?php esc_attr_e( 'Contraer el menú', 'match' ); ?>" data-label-expand="<?php esc_attr_e( 'Expandir el menú', 'match' ); ?>">
			<svg class="match-jb__burger" width="30" height="22" viewBox="0 0 30 22" fill="none" aria-hidden="true"><path d="M1.5 2H28.5M1.5 11H28.5M1.5 20H28.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
			<svg class="match-jb__close" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4L20 20M20 4L4 20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
			<svg class="match-jb__chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 3.5L5.5 8L10 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>

	<div class="match-jb__menu" id="match-jb-menu">
		<nav class="match-jb__nav" aria-label="<?php esc_attr_e( 'Job Board', 'match' ); ?>">
			<?php foreach ( $items as $key => $item ) : ?>
				<?php
				list( $label, $url, $icon ) = $item;
				$is_locked                  = ! empty( $item[3] );
				?>
				<?php if ( $url ) : ?>
					<a class="match-jb__navitem<?php echo $key === $current ? ' is-active' : ''; ?><?php echo $is_locked ? ' is-locked' : ''; ?>" href="<?php echo esc_url( $is_locked ? wp_login_url( $locked[ $key ][1] ?: match_jobboard_url() ) : $url ); ?>" data-tooltip="<?php echo esc_attr( $label ); ?>"<?php echo $is_locked ? ' data-login-required="' . esc_attr( $locked[ $key ][1] ?: match_jobboard_url() ) . '" data-login-title="' . esc_attr( sprintf( /* translators: %s: sección */ __( 'Inicia sesión para ver %s', 'match' ), $label ) ) . '"' : ''; ?><?php echo $is_locked ? ' title="' . esc_attr( sprintf( /* translators: %s: sección */ __( 'Inicia sesión para ver %s', 'match' ), $label ) ) . '"' : ''; ?><?php echo $key === $current ? ' aria-current="page"' : ''; ?>>
						<?php echo match_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="match-jb__navlabel"><?php echo esc_html( $label ); ?></span>
						<?php if ( $is_locked ) : ?>
							<svg class="match-jb__navlock" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2.5" fill="currentColor"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
							<span class="screen-reader-text"><?php esc_html_e( '(requiere iniciar sesión)', 'match' ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $counts[ $key ] ) ) : ?>
							<span class="match-jb__navbadge" data-nav-count="<?php echo esc_attr( $key ); ?>"><?php echo (int) $counts[ $key ]; ?></span>
						<?php endif; ?>
					</a>
				<?php else : ?>
					<span class="match-jb__navitem is-disabled" title="<?php esc_attr_e( 'Disponible próximamente', 'match' ); ?>" data-tooltip="<?php echo esc_attr( $label ); ?>" aria-disabled="true">
						<?php echo match_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="match-jb__navlabel"><?php echo esc_html( $label ); ?></span>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
	
		<div class="match-jb__auth">
			<?php // Mismo estilo e interacción que los ítems del menú (hover, tooltip contraído, foco). ?>
			<a class="match-jb__navitem match-jb__home" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Volver', 'match' ); ?>">
				<?php echo match_icon( 'arrow-circle-nav' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="match-jb__navlabel"><?php esc_html_e( 'Volver', 'match' ); ?></span>
			</a>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="match-jb__logout" href="<?php echo esc_url( match_logout_url() ); ?>" data-tooltip="<?php esc_attr_e( 'Cerrar sesión', 'match' ); ?>">
					<?php echo match_icon( 'logout-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="match-jb__navlabel"><?php esc_html_e( 'Cerrar sesión', 'match' ); ?></span>
				</a>
			<?php else : ?>
				<a class="match-jb__btn match-jb__btn--dark" href="<?php echo esc_url( match_registro_page_url() ?: home_url( '/#contacto' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Registrarse', 'match' ); ?>">
					<span class="match-jb__navlabel"><?php esc_html_e( 'Registrarse', 'match' ); ?></span>
					<?php echo match_icon( 'user-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
				<a class="match-jb__btn match-jb__btn--light" href="<?php echo esc_url( wp_login_url( match_jobboard_url() ) ); ?>" data-tooltip="<?php esc_attr_e( 'Iniciar sesión', 'match' ); ?>">
					<?php // Solo con la barra contraída: la puerta de "Cerrar sesión" sin voltear = entrar. ?>
					<?php echo match_icon( 'logout', 'match-jb__btn-rail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="match-jb__navlabel"><?php esc_html_e( 'Iniciar sesión', 'match' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</aside>
