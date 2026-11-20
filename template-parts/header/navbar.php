<?php
/**
 * Barra de navegación.
 *
 * Figma: Navbar (node 3502:3320) + reference-match-nav (mega menú de
 * Soluciones y menú mobile). Izquierda: logo + menú (Inicio, Soluciones,
 * Blog, Vacantes disponibles); derecha: solo el botón de sesión. "Soluciones" no enlaza a
 * /soluciones/: abre el mega menú con las páginas hijas
 * (match_nav_solutions() en inc/soluciones-data.php). El JS está en app.js.
 */
defined( 'ABSPATH' ) || exit;

$account_url   = is_user_logged_in() ? match_jobboard_url() : wp_login_url( match_jobboard_url() );
$account_label = is_user_logged_in() ? __( 'Mi cuenta', 'match' ) : __( 'Iniciar sesión', 'match' );
$solutions     = match_nav_solutions();

$blog_url    = match_blog_url();

$current = '';
if ( is_front_page() ) {
	$current = 'home';
} elseif ( is_page_template( array( 'template-solucion.php', 'template-executive.php', 'template-personnel.php', 'template-outplacement.php', 'template-assessment.php' ) ) ) {
	$current = 'soluciones';
} elseif ( is_home() || is_singular( 'post' ) || is_category() || is_page_template( 'template-blog.php' ) ) {
	$current = 'blog';
} elseif ( is_post_type_archive( 'match_job' ) || is_singular( 'match_job' ) ) {
	$current = 'vacantes';
}

$arrow = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8H13M9 4L13 8L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$chev  = '<svg class="match-nav__chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$first = $solutions[0] ?? null;
?>
<header class="match-navbar" id="match-navbar">
	<div class="match-navbar__inner">
		<div class="match-navbar__left">
			<a class="match-navbar__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<?php echo match_logo(); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG propio del tema. ?>
				<?php endif; ?>
			</a>

			<nav class="match-nav" aria-label="<?php esc_attr_e( 'Navegación principal', 'match' ); ?>">
				<a class="match-nav__link<?php echo 'home' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo 'home' === $current ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Inicio', 'match' ); ?></a>

				<?php if ( $solutions ) : ?>
					<div class="match-sol" data-mega>
						<button class="match-sol__btn<?php echo 'soluciones' === $current ? ' is-current' : ''; ?>" type="button" aria-expanded="false" aria-haspopup="true" aria-controls="match-mega">
							<?php esc_html_e( 'Soluciones', 'match' ); ?>
							<?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>

						<div class="match-mega" id="match-mega">
							<div class="match-mega__panel">
								<div class="match-mega__list">
									<?php foreach ( $solutions as $i => $item ) : ?>
										<a class="match-mega__item<?php echo 0 === $i ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>" style="--c: <?php echo esc_attr( $item['color'] ); ?>"
											data-name="<?php echo esc_attr( $item['name'] ); ?>"
											data-eyebrow="<?php echo esc_attr( $item['eyebrow'] ); ?>"
											data-body="<?php echo esc_attr( match_bold_text( $item['body'] ) ); ?>"
											<?php if ( $item['extra'] ) : ?>
												data-extra-href="<?php echo esc_url( $item['extra']['url'] ); ?>"
												data-extra-label="<?php echo esc_attr( $item['extra']['label'] ); ?>"
											<?php endif; ?>>
											<span class="match-mega__txt">
												<span class="match-mega__name"><?php echo esc_html( $item['name'] ); ?></span>
												<?php if ( $item['sub'] ) : ?>
													<span class="match-mega__sub"><?php echo esc_html( $item['sub'] ); ?></span>
												<?php endif; ?>
											</span>
											<span class="match-mega__arrow"><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
										</a>
									<?php endforeach; ?>
								</div>

								<div class="match-mega__preview">
									<div class="match-mega__preview-inner" data-mega-preview>
										<span class="match-mega__eyebrow" data-mega-eyebrow><?php echo esc_html( $first['eyebrow'] ); ?></span>
										<p class="match-mega__body" data-mega-body><?php echo match_bold_text( $first['body'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- match_bold_text() escapa el texto. ?></p>
										<div class="match-mega__links">
											<?php /* translators: %s: nombre de la solución */ $main_label = __( 'Conocer %s', 'match' ); ?>
											<a class="match-mega__link" href="<?php echo esc_url( $first['url'] ); ?>" data-mega-main data-label="<?php echo esc_attr( $main_label ); ?>">
												<span data-mega-main-label><?php echo esc_html( sprintf( $main_label, $first['name'] ) ); ?></span>
												<span class="match-mega__link-icon"><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="match-mega__spinner"></span></span>
											</a>
											<a class="match-mega__link" href="<?php echo esc_url( $first['extra']['url'] ?? '#' ); ?>" data-mega-extra<?php echo $first['extra'] ? '' : ' hidden'; ?>>
												<span data-mega-extra-label><?php echo esc_html( $first['extra']['label'] ?? '' ); ?></span>
												<span class="match-mega__link-icon"><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="match-mega__spinner"></span></span>
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<a class="match-nav__link<?php echo 'blog' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'match' ); ?></a>

				<a class="match-nav__link<?php echo 'vacantes' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( match_jobs_url() ); ?>"<?php echo 'vacantes' === $current ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Vacantes disponibles', 'match' ); ?></a>
			</nav>
		</div>

		<div class="match-navbar__right">
			<a class="match-btn match-btn--secondary" href="<?php echo esc_url( $account_url ); ?>">
				<?php echo esc_html( $account_label ); ?>
				<span class="match-btn__icon"><?php echo match_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</a>
		</div>

		<button class="match-navbar__toggle" type="button" aria-expanded="false" aria-controls="match-mobile-nav" aria-label="<?php esc_attr_e( 'Abrir el menú', 'match' ); ?>" data-label-open="<?php esc_attr_e( 'Abrir el menú', 'match' ); ?>" data-label-close="<?php esc_attr_e( 'Cerrar el menú', 'match' ); ?>">
			<svg class="match-navbar__burger" width="30" height="22" viewBox="0 0 30 22" fill="none" aria-hidden="true"><path d="M1.5 2H28.5M1.5 11H28.5M1.5 20H28.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
			<svg class="match-navbar__close" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4L20 20M20 4L4 20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
		</button>

		<?php // Mobile: el botón de sesión queda siempre a la vista, a la derecha de la hamburguesa. ?>
		<a class="match-btn match-btn--secondary match-navbar__account" href="<?php echo esc_url( $account_url ); ?>">
			<?php echo esc_html( $account_label ); ?>
			<span class="match-btn__icon"><?php echo match_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		</a>
	</div>

	<div class="match-mmenu" id="match-mobile-nav" hidden data-lenis-prevent>
		<a class="match-mmenu__link<?php echo 'home' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'match' ); ?></a>

		<?php if ( $solutions ) : ?>
			<button class="match-mmenu__link match-mmenu__acc<?php echo 'soluciones' === $current ? ' is-current' : ''; ?>" type="button" aria-expanded="false" aria-controls="match-msol">
				<?php esc_html_e( 'Soluciones', 'match' ); ?>
				<?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<div class="match-msol" id="match-msol" hidden>
				<?php foreach ( $solutions as $item ) : ?>
					<a class="match-msol__item" href="<?php echo esc_url( $item['url'] ); ?>" style="--c: <?php echo esc_attr( $item['color'] ); ?>">
						<span class="match-msol__dot" aria-hidden="true"></span>
						<span class="match-mega__txt">
							<span class="match-msol__name"><?php echo esc_html( $item['name'] ); ?></span>
							<?php if ( $item['sub'] ) : ?>
								<span class="match-msol__sub"><?php echo esc_html( $item['sub'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
					<?php if ( $item['extra'] ) : ?>
						<a class="match-msol__item match-msol__extra" href="<?php echo esc_url( $item['extra']['url'] ); ?>">
							<?php echo esc_html( $item['extra']['label'] ); ?>
							<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<a class="match-mmenu__link<?php echo 'blog' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'match' ); ?></a>
		<a class="match-mmenu__link<?php echo 'vacantes' === $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( match_jobs_url() ); ?>"><?php esc_html_e( 'Vacantes disponibles', 'match' ); ?></a>
		<a class="match-mmenu__login" href="<?php echo esc_url( $account_url ); ?>">
			<?php echo esc_html( $account_label ); ?>
			<span class="match-btn__icon"><?php echo match_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		</a>
	</div>
</header>
