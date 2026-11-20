<?php
/**
 * Panel lateral "Detalle del proceso" (Figma: 5548:5587). Lo usan el detalle
 * de vacante ("Ver proceso") y Mis procesos (una por fila; la tarjeta entera
 * lo abre). El JS (app.js) solo abre/cierra: el contenido es estático.
 *
 * Etapas: siempre se ve el recorrido completo (Enviado → En revisión →
 * Entrevista → Decisión final). Las alcanzadas van en azul con la fecha en
 * que llegó el aviso del CRM (`dates`) y las que faltan en gris con
 * "Pendiente". Si el proceso terminó, el recorrido se corta en la última
 * etapa alcanzada y las que faltaban quedan sin estado.
 *
 * @var array $args {
 *     id: string        id del <dialog>,
 *     job: array        (ver match_job_data),
 *     row: array        fila de match_user_applications (stage, status, applied, dates, cv)
 * }
 */
defined( 'ABSPATH' ) || exit;

$job = $args['job'] ?? array();
$row = $args['row'] ?? array();
if ( ! $job || ! $row ) {
	return;
}

$stages = match_application_stages();
$stage  = $row['stage'] ?? 'sent';
$closed = 'closed' === $stage;
$steps  = match_application_steps();
$dates  = $row['dates'] ?? array( 'sent' => $row['applied'] ?? '' );

// Etapa alcanzada en el recorrido. En un proceso terminado, la etapa en la
// que estaba justo antes de cerrarse (el CRM puede hacer retroceder la
// postulación, así que no vale "la más avanzada que tuvo alguna vez"). Sin
// avisos del CRM, la más avanzada con fecha (como mínimo, el envío).
if ( $closed ) {
	$reached = 0;
	foreach ( $row['history'] ?? array() as $entry ) {
		$index = array_search( $entry['stage'] ?? '', $steps, true );
		if ( false !== $index ) {
			$reached = (int) $index;
		}
	}
	if ( empty( $row['history'] ) ) {
		foreach ( $steps as $i => $step_key ) {
			if ( isset( $dates[ $step_key ] ) ) {
				$reached = $i;
			}
		}
	}
} else {
	$reached = (int) array_search( $stage, $steps, true );
}
?>
<dialog class="match-jb-status-drawer" id="<?php echo esc_attr( $args['id'] ?? 'match-status-drawer' ); ?>" aria-label="<?php esc_attr_e( 'Detalle del proceso', 'match' ); ?>" data-status-drawer data-lenis-prevent>
	<div class="match-jb-status-drawer__panel">
		<button class="match-jb-status-drawer__close" type="button" data-status-close>
			<span class="screen-reader-text"><?php esc_html_e( 'Cerrar', 'match' ); ?></span>
			<?php echo match_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<div class="match-jb-status-drawer__body">
			<div class="match-jb-status-drawer__top">
				<p class="match-jb-status-drawer__eyebrow"><?php esc_html_e( 'Detalle del proceso', 'match' ); ?></p>

				<div class="match-jb-status-drawer__head">
					<div class="match-jb-status-drawer__intro">
						<h2 class="match-jb-status-drawer__title"><?php echo esc_html( $job['company'] ?: $job['title'] ); ?></h2>
						<?php if ( $job['company'] ) : ?>
							<p class="match-jb-status-drawer__role"><?php echo esc_html( $job['title'] ); ?></p>
						<?php endif; ?>
					</div>

					<div class="match-jb-status-drawer__meta">
						<span class="match-jb-pill match-jb-pill--<?php echo esc_attr( $stages[ $stage ]['variant'] ); ?>"><?php echo esc_html( $stages[ $stage ]['label'] ); ?></span>
						<span class="match-jb-status-drawer__applied">
							<?php echo match_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php echo esc_html( sprintf( /* translators: %s: fecha de postulación. */ __( 'Postulado %s', 'match' ), $row['applied'] ) ); ?>
						</span>
					</div>
				</div>
			</div>

			<div class="match-jb-status-drawer__section match-jb-status-drawer__section--steps">
				<p class="match-jb-status-drawer__label"><?php esc_html_e( 'Etapas del proceso', 'match' ); ?></p>
				<ol class="match-jb-status__steps">
					<?php foreach ( $steps as $i => $step_key ) :
						if ( $i < $reached ) {
							$state = 'is-done';
						} elseif ( $i === $reached ) {
							$state = $closed ? 'is-done' : 'is-current';
						} else {
							$state = 'is-pending';
						}
						$is_last = $i === count( $steps ) - 1;
						?>
						<li class="match-jb-status__step <?php echo esc_attr( $state ); ?>"<?php echo 'is-current' === $state && ! $closed ? ' aria-current="step"' : ''; ?>>
							<span class="match-jb-status__rail" aria-hidden="true">
								<span class="match-jb-status__dot"></span>
								<?php if ( ! $is_last ) : ?><span class="match-jb-status__line<?php echo $i < $reached ? ' is-reached' : ''; ?>"></span><?php endif; ?>
							</span>
							<span class="match-jb-status__label">
								<?php echo esc_html( $stages[ $step_key ]['step'] ?? $stages[ $step_key ]['label'] ); ?>
								<?php if ( $i <= $reached && ! empty( $dates[ $step_key ] ) ) : ?>
									<span class="match-jb-status__date"><?php echo esc_html( $dates[ $step_key ] ); ?></span>
								<?php elseif ( 'is-pending' === $state && ! $closed ) : ?>
									<span class="match-jb-status__date"><?php esc_html_e( 'Pendiente', 'match' ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<div class="match-jb-status-drawer__section">
				<p class="match-jb-status-drawer__label"><?php esc_html_e( 'Próximo paso', 'match' ); ?></p>
				<div class="match-jb-status-drawer__hint">
					<p>
						<?php echo esc_html( match_application_hint( $row ) ); ?>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $row['cv'] ) ) : ?>
				<div class="match-jb-status-drawer__section">
					<p class="match-jb-status-drawer__label"><?php esc_html_e( 'CV enviado', 'match' ); ?></p>
					<?php get_template_part( 'template-parts/jobboard/cv-row', null, array( 'cv' => $row['cv'] ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</dialog>
