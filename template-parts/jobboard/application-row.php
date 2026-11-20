<?php
/**
 * Fila de postulación (Figma: Card — Vacante (Row) de Mis procesos, node 4110:2441).
 *
 * Con `drawer` (id del panel de estado, ver status-drawer.php) la tarjeta
 * entera lo abre: un <button> estirado sobre ella (::after), así sigue
 * siendo un solo control para teclado y lector de pantalla.
 *
 * @var array $args { job: array, stage: string, applied: string, drawer?: string }
 */
defined( 'ABSPATH' ) || exit;

$job   = $args['job'] ?? array();
$stage = match_application_stages()[ $args['stage'] ?? 'sent' ] ?? match_application_stages()['sent'];
if ( ! $job ) {
	return;
}
$drawer = $args['drawer'] ?? '';
?>
<article class="match-jb-app<?php echo $drawer ? ' match-jb-app--open' : ''; ?>">
	<div class="match-jb-app__who">
		<div class="match-jb-app__logo"<?php echo ! empty( $job['logo_bg'] ) ? ' style="--logo-bg:' . esc_attr( $job['logo_bg'] ) . '"' : ''; ?>>
			<?php if ( ! empty( $job['logo'] ) ) : ?>
				<img src="<?php echo esc_url( $job['logo'] ); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="match-logo__monogram" aria-hidden="true"><?php echo esc_html( mb_substr( $job['company'] ?: '—', 0, 1 ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="match-jb-app__names">
			<?php get_template_part( 'template-parts/job-submarcas', null, array( 'submarcas' => $job['submarcas'] ?? array() ) ); ?>
			<p class="match-jb-app__company"><?php echo esc_html( $job['company'] ); ?></p>
			<h2 class="match-jb-app__role">
				<?php if ( $drawer ) : ?>
					<button class="match-jb-app__open" type="button" data-status-trigger="<?php echo esc_attr( $drawer ); ?>" aria-haspopup="dialog">
						<?php echo esc_html( $job['title'] ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '— ver detalle del proceso', 'match' ); ?></span>
					</button>
				<?php elseif ( ! empty( $job['url'] ) ) : ?>
					<a href="<?php echo esc_url( $job['url'] ); ?>"><?php echo esc_html( $job['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $job['title'] ); ?>
				<?php endif; ?>
			</h2>
		</div>
	</div>

	<div class="match-jb-app__aside">
		<span class="match-jb-pill match-jb-pill--<?php echo esc_attr( $stage['variant'] ); ?>"><?php echo esc_html( $stage['label'] ); ?></span>
		<p class="match-jb-app__date">
			<?php echo match_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php
			/* translators: %s: fecha */
			printf( esc_html__( 'Postulado %s', 'match' ), esc_html( $args['applied'] ?? '' ) );
			?>
		</p>
		<ul class="match-jb-app__features">
			<?php if ( ! empty( $job['type'] ) ) : ?>
				<li><?php echo match_icon( 'meta-clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $job['type'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $job['place'] ) ) : ?>
				<li><?php echo match_icon( 'meta-location' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $job['place'] ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</article>
