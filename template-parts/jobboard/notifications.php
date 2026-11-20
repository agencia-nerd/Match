<?php
/**
 * Panel de notificaciones de la topbar (campana). Datos de
 * inc/notifications.php; el estado de lectura se guarda por la ruta REST
 * match/v1/notifications/read (assets/js/app.js).
 *
 * <details>/<summary> nativo, igual que el menú de cuenta: abre y cierra sin
 * JS y sin posicionamiento propio. Solo se muestra con sesión (lo decide
 * topbar.php).
 */
defined( 'ABSPATH' ) || exit;

$notifications = match_notifications();
$unread        = count( array_filter( $notifications, fn( array $n ): bool => $n['unread'] ) );
?>
<details class="match-jb-notif" data-notif>
	<summary class="match-jb__notif match-jb-notif__trigger" aria-label="<?php esc_attr_e( 'Notificaciones', 'match' ); ?>">
		<?php echo match_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="match-jb-notif__dot" data-notif-dot <?php echo $unread ? '' : 'hidden'; ?>></span>
		<span class="match-jb__notif-label"><?php esc_html_e( 'Notificaciones', 'match' ); ?></span>
	</summary>

	<div class="match-jb-notif__panel">
		<header class="match-jb-notif__head">
			<h2 class="match-jb-notif__title"><?php esc_html_e( 'Notificaciones', 'match' ); ?></h2>
			<button class="match-jb-notif__readall" type="button" data-notif-readall <?php echo $unread ? '' : 'hidden'; ?>>
				<?php esc_html_e( 'Marcar todo como leído', 'match' ); ?>
			</button>
		</header>

		<?php if ( $notifications ) : ?>
			<ul class="match-jb-notif__list" data-lenis-prevent>
				<?php foreach ( $notifications as $n ) : ?>
					<li>
						<a class="match-jb-notif__item<?php echo $n['unread'] ? ' is-unread' : ''; ?>" href="<?php echo esc_url( $n['url'] ?: '#' ); ?>" data-notif-id="<?php echo esc_attr( $n['id'] ); ?>">
							<span class="match-jb-notif__body">
								<span class="match-jb-notif__name"><?php echo esc_html( $n['title'] ); ?></span>
								<?php if ( $n['subtitle'] ) : ?>
									<span class="match-jb-notif__sub"><?php echo esc_html( $n['subtitle'] ); ?></span>
								<?php endif; ?>
								<span class="match-jb-notif__time"><?php echo esc_html( $n['ago'] ); ?></span>
							</span>
							<span class="match-jb-notif__unread" aria-hidden="true"></span>
							<?php if ( $n['unread'] ) : ?>
								<span class="screen-reader-text"><?php esc_html_e( 'No leída', 'match' ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p class="match-jb-notif__empty" data-notif-empty <?php echo $notifications ? 'hidden' : ''; ?>>
			<?php esc_html_e( 'Aún no tienes notificaciones. Te avisaremos cuando postules o se publiquen nuevas vacantes.', 'match' ); ?>
		</p>
	</div>
</details>
