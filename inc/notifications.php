<?php
/**
 * Notificaciones del Job Board (panel de la campana en la topbar).
 *
 * No se guardan: se derivan al renderizar de lo que ya existe, así que no
 * hacen falta hooks sobre el plugin ni una tabla. Tipos:
 *
 *  - "Postulación recibida": las postulaciones del usuario.
 *  - "Nueva vacante publicada": las vacantes publicadas hace poco.
 *  - Avance de proceso ("Match revisó tu perfil", Entrevista, Decisión
 *    final, Finalizado): una por aviso de estado del CRM (history de
 *    match_user_applications).
 *
 * "Vacante recomendada" queda fuera de esta fase: el CRM no cruza
 * candidatos con vacantes.
 *
 * Lo único que persiste es el estado de lectura, en user meta:
 *  - match_notif_read_at:  marca de tiempo; "Marcar todo" la fija a ahora.
 *  - match_notif_read_ids: ids leídos de a uno, al hacer clic.
 */
defined( 'ABSPATH' ) || exit;

// Ventanas del feed: cuánto atrás se mira y cuántas notificaciones se muestran.
const MATCH_NOTIF_JOB_DAYS = 14;
const MATCH_NOTIF_APP_DAYS = 30;
const MATCH_NOTIF_MAX      = 10;
const MATCH_NOTIF_READ_MAX = 100;

/**
 * "Hace 2 min", "Hace 3 h", "Ayer", "Hace 4 días". human_time_diff() no
 * cubre "Ayer" ni sale en el formato del diseño.
 */
function match_notif_ago( int $ts ): string {
	$diff = max( 0, time() - $ts );

	if ( $diff < MINUTE_IN_SECONDS ) {
		return __( 'Hace un momento', 'match' );
	}
	if ( $diff < HOUR_IN_SECONDS ) {
		/* translators: %d: minutos */
		return sprintf( __( 'Hace %d min', 'match' ), (int) floor( $diff / MINUTE_IN_SECONDS ) );
	}
	if ( $diff < DAY_IN_SECONDS ) {
		/* translators: %d: horas */
		return sprintf( __( 'Hace %d h', 'match' ), (int) floor( $diff / HOUR_IN_SECONDS ) );
	}
	if ( $diff < 2 * DAY_IN_SECONDS ) {
		return __( 'Ayer', 'match' );
	}

	/* translators: %d: días */
	return sprintf( __( 'Hace %d días', 'match' ), (int) floor( $diff / DAY_IN_SECONDS ) );
}

/**
 * Notificaciones del usuario, la más reciente primero. Cada una:
 * { id, type, title, subtitle, ts, ago, url, unread }.
 *
 * Solo datos reales: sin vacantes sincronizadas (modo demo) no hay
 * notificaciones y el panel muestra su estado vacío.
 */
function match_notifications( int $user_id = 0 ): array {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	$items = array();

	if ( match_job_board_active() && ! match_jobs_demo_mode() ) {
		$user  = get_userdata( $user_id );
		$since = max( time() - MATCH_NOTIF_JOB_DAYS * DAY_IN_SECONDS, $user ? (int) strtotime( $user->user_registered ) : 0 );

		foreach ( match_jobs_list( array(), MATCH_NOTIF_MAX )['jobs'] as $job ) {
			$ts = (int) strtotime( (string) $job['datetime'] );
			if ( $ts >= $since ) {
				$items[] = array(
					'id'   => 'job-' . $job['id'],
					'type' => 'job',
					'job'  => $job,
					'ts'   => $ts,
				);
			}
		}

		$app_since = time() - MATCH_NOTIF_APP_DAYS * DAY_IN_SECONDS;

		foreach ( match_user_applications() as $row ) {
			if ( $row['applied_ts'] >= $app_since ) {
				$items[] = array(
					'id'   => 'app-' . $row['job']['id'],
					'type' => 'application',
					'job'  => $row['job'],
					'ts'   => (int) $row['applied_ts'],
				);
			}

			// Una por cambio de etapa: en_entrevista → en_evaluacion siguen en "interview".
			$prev_stage = 'sent';
			foreach ( $row['history'] ?? array() as $entry ) {
				$changed    = $entry['stage'] !== $prev_stage;
				$prev_stage = $entry['stage'];
				if ( $changed && $entry['ts'] >= $app_since ) {
					$items[] = array(
						'id'    => 'prog-' . $entry['event_id'],
						'type'  => 'progress',
						'stage' => $entry['stage'],
						'job'   => $row['job'],
						'ts'    => (int) $entry['ts'],
					);
				}
			}
		}
	}

	$titles = array(
		'application' => __( 'Postulación recibida', 'match' ),
		'job'         => __( 'Nueva vacante publicada', 'match' ),
		'review'      => __( 'Match revisó tu perfil', 'match' ),
		'interview'   => __( 'Tu proceso avanzó a Entrevista', 'match' ),
		'final'       => __( 'Estás en Decisión final', 'match' ),
		'closed'      => __( 'Tu proceso finalizó', 'match' ),
	);

	$read_at  = (int) get_user_meta( $user_id, 'match_notif_read_at', true );
	$read_ids = match_user_meta_array( $user_id, 'match_notif_read_ids' );
	$procesos = match_jobboard_page_url( 'template-procesos.php' );

	usort( $items, fn( array $a, array $b ): int => $b['ts'] <=> $a['ts'] );

	$out = array();
	foreach ( array_slice( $items, 0, MATCH_NOTIF_MAX ) as $item ) {
		$job     = $item['job'];
		$is_app  = 'job' !== $item['type'];
		$out[]   = array(
			'id'       => $item['id'],
			'type'     => $item['type'],
			'title'    => $titles[ $item['stage'] ?? $item['type'] ] ?? '',
			'subtitle' => trim( implode( ' · ', array_filter( array( (string) $job['company'], (string) $job['title'] ) ) ) ),
			'ts'       => $item['ts'],
			'ago'      => match_notif_ago( $item['ts'] ),
			'url'      => $is_app && $procesos ? $procesos : (string) $job['url'],
			'unread'   => $item['ts'] > $read_at && ! in_array( $item['id'], $read_ids, true ),
		);
	}

	return $out;
}

/**
 * POST /wp-json/match/v1/notifications/read marca una notificación como leída
 * (con `id`) o todas (sin `id`). Sesión + nonce REST, como /saved.
 */
function match_notifications_rest_routes(): void {
	register_rest_route(
		'match/v1',
		'/notifications/read',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'is_user_logged_in',
			'callback'            => function ( WP_REST_Request $request ): WP_REST_Response {
				$user_id = get_current_user_id();
				$id      = sanitize_text_field( (string) $request->get_param( 'id' ) );

				if ( '' !== $id ) {
					$ids   = match_user_meta_array( $user_id, 'match_notif_read_ids' );
					$ids[] = $id;
					update_user_meta( $user_id, 'match_notif_read_ids', array_slice( array_values( array_unique( $ids ) ), -MATCH_NOTIF_READ_MAX ) );
				} else {
					update_user_meta( $user_id, 'match_notif_read_at', time() );
					delete_user_meta( $user_id, 'match_notif_read_ids' );
				}

				$unread = count( array_filter( match_notifications( $user_id ), fn( array $n ): bool => $n['unread'] ) );

				return new WP_REST_Response( array( 'unread' => $unread ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'match_notifications_rest_routes' );
