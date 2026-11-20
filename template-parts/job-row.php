<?php
/**
 * Fila de vacante del Job Board en la portada (Figma: Card — Vacante
 * (propuesta) · Normal 6225:4481 / Hover 6225:4574).
 *
 * Todo a la vista, sin desplegable. Toda la fila lleva al detalle: el enlace
 * del puesto se estira sobre el <article> (::after en home.css); el botón de
 * guardar queda por encima. El círculo con flecha es solo visual.
 *
 * @var array $args { job: array (ver match_job_data) }
 */
defined( 'ABSPATH' ) || exit;

$job = $args['job'] ?? array();

if ( ! $job ) {
	return;
}

$row_id = 'job-' . sanitize_html_class( $job['id'] );
?>
<article class="match-job" id="<?php echo esc_attr( $row_id ); ?>">
	<div class="match-job__main">
		<div class="match-job__head">
			<div class="match-job__top">
				<div class="match-job__identity">
					<div class="match-job__logo">
						<?php if ( $job['logo'] ) : ?>
							<img src="<?php echo esc_url( $job['logo'] ); ?>" alt="" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="match-logo__monogram" aria-hidden="true"><?php echo esc_html( mb_substr( $job['company'] ?: '—', 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<div>
						<?php get_template_part( 'template-parts/job-submarcas', null, array( 'submarcas' => $job['submarcas'] ?? array() ) ); ?>
						<p class="match-job__company"><?php echo esc_html( $job['company'] ); ?></p>
						<h3 class="match-job__role"><a href="<?php echo esc_url( $job['url'] ); ?>"><?php echo esc_html( $job['title'] ); ?></a></h3>
					</div>
				</div>

				<div class="match-job__aside">
					<button class="match-job__fav<?php echo $job['saved'] ? ' is-saved' : ''; ?>" type="button" aria-pressed="<?php echo $job['saved'] ? 'true' : 'false'; ?>" data-job="<?php echo esc_attr( $job['id'] ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Guardar vacante', 'match' ); ?></span>
						<?php echo match_icon( 'fav', 'match-job__fav-off' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo match_icon( 'fav-saved', 'match-job__fav-on' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
					<?php if ( $job['salary'] ) : ?>
						<p class="match-job__salary"><?php echo esc_html( $job['salary'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $job['type'] || $job['place'] ) : ?>
				<ul class="match-job__meta">
					<?php if ( $job['type'] ) : ?>
						<li><?php echo match_icon( 'meta-clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $job['type'] ); ?></li>
					<?php endif; ?>
					<?php if ( $job['place'] ) : ?>
						<li><?php echo match_icon( 'meta-location' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $job['place'] ); ?></li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="match-job__foot">
			<?php if ( ! empty( $job['skills'] ) ) : ?>
				<ul class="match-job__tags">
					<?php foreach ( array_slice( $job['skills'], 0, 4 ) as $skill ) : ?>
						<li class="match-pill"><?php echo esc_html( $skill ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="match-job__actions">
				<?php if ( $job['date'] ) : ?>
					<time class="match-job__date" datetime="<?php echo esc_attr( $job['datetime'] ); ?>">
						<?php
						/* translators: %s: fecha */
						printf( esc_html__( 'Publicado %s', 'match' ), esc_html( $job['date'] ) );
						?>
					</time>
				<?php endif; ?>
				<?php // Mobile: botón con texto (no hay hover que avise que la fila es clickeable). Visual: el click lo recibe el enlace estirado. ?>
				<span class="match-btn match-btn--secondary match-job__cta" aria-hidden="true">
					<?php esc_html_e( 'Ver vacante', 'match' ); ?>
					<span class="match-btn__icon"><?php echo match_icon( 'arrow-dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</span>
			</div>
		</div>
	</div>

	<span class="match-btn match-btn--round match-job__go" aria-hidden="true"><?php echo match_icon( 'arrow-dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
</article>
