<?php
/**
 * Hablemos (Figma: Section — Contacto, node 3502:3508).
 *
 * El formulario lo procesa match_handle_contact() (admin-post) y envía el
 * mensaje al correo del Personalizador.
 *
 * $args opcionales de las internas:
 * - lead, lead_muted  Texto de apoyo junto al titular.
 * - programas         Nombres de programas: agrega el selector «Programa de
 *                     interés» (Programas). Los botones de cada tarjeta lo
 *                     preseleccionan (assets/js/programas.js).
 * - placeholder       Texto de ayuda del mensaje.
 */
defined( 'ABSPATH' ) || exit;

// Un envío desde el formulario emergente muestra su aviso allí, no aquí.
$status  = isset( $_GET['contacto'] ) && ! isset( $_GET['origen'] ) ? sanitize_key( wp_unslash( $_GET['contacto'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$profile = isset( $_GET['perfil'] ) && 'profesional' === $_GET['perfil'] ? 'profesional' : 'empresa'; // phpcs:ignore WordPress.Security.NonceVerification

// Las internas pasan su propio texto de apoyo; la portada usa sus campos PCF.
$home       = match_home();
$lead       = $args['lead'] ?? $home['c_lead'];
$lead_muted = array_key_exists( 'lead_muted', (array) $args ) ? $args['lead_muted'] : $home['c_lead_muted'];
$programas  = array_values( array_filter( array_map( 'strval', (array) ( $args['programas'] ?? array() ) ) ) );
$placeholder = $args['placeholder'] ?? __( 'Cuéntanos sobre tu situación o la de tu equipo.', 'match' );
$programa   = isset( $_GET['programa'] ) ? sanitize_text_field( wp_unslash( $_GET['programa'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="match-contacto" id="contacto"<?php echo match_aos(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="match-contacto__inner">
		<div class="match-contacto__bg" aria-hidden="true">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/contacto-bg.webp' ) ); ?>" alt="" loading="lazy" decoding="async">
		</div>

		<form class="match-glass match-contacto__card"<?php echo match_reveal( 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="match_contact">
			<input type="hidden" name="volver" value="<?php echo esc_url( get_permalink( get_queried_object_id() ) ?: home_url( '/' ) ); ?>">
			<?php wp_nonce_field( 'match_contact', 'match_contact_nonce' ); ?>
			<input type="text" name="match_web" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

			<div class="match-toggle">
				<span class="match-toggle__label" id="match-perfil-label"><?php esc_html_e( 'Selecciona tu perfil', 'match' ); ?></span>
				<div class="match-toggle__group" role="radiogroup" aria-labelledby="match-perfil-label">
					<button class="match-toggle__opt<?php echo 'empresa' === $profile ? ' is-active' : ''; ?>" type="button" role="radio" aria-checked="<?php echo 'empresa' === $profile ? 'true' : 'false'; ?>" data-value="empresa">
						<?php esc_html_e( 'Empresa', 'match' ); ?>
						<?php echo match_icon( 'buildings' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
					<button class="match-toggle__opt<?php echo 'profesional' === $profile ? ' is-active' : ''; ?>" type="button" role="radio" aria-checked="<?php echo 'profesional' === $profile ? 'true' : 'false'; ?>" data-value="profesional">
						<?php esc_html_e( 'Profesional', 'match' ); ?>
						<?php echo match_icon( 'user-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
				</div>
				<input type="hidden" name="perfil" value="<?php echo esc_attr( $profile ); ?>">
			</div>

			<div class="match-contacto__fields">
				<label class="match-field">
					<span class="match-field__label"><?php esc_html_e( 'Nombre completo', 'match' ); ?></span>
					<input class="match-field__input" type="text" name="nombre" placeholder="<?php esc_attr_e( 'Escribe tu nombre', 'match' ); ?>" required autocomplete="name" maxlength="50" pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e( 'Solo letras, espacios, apóstrofo o guion.', 'match' ); ?>" data-name-input>
				</label>
				<label class="match-field">
					<span class="match-field__label"><?php esc_html_e( 'Correo electrónico', 'match' ); ?></span>
					<input class="match-field__input" type="email" name="correo" placeholder="tu@empresa.com" required autocomplete="email">
				</label>
				<?php if ( $programas ) : ?>
					<label class="match-field">
						<span class="match-field__label"><?php esc_html_e( 'Programa de interés', 'match' ); ?></span>
						<span class="match-field__select">
							<select class="match-field__input" name="programa" data-contact-programa>
								<option value=""><?php esc_html_e( 'Aún no lo sé, quiero asesoría', 'match' ); ?></option>
								<?php foreach ( $programas as $nombre ) : ?>
									<option value="<?php echo esc_attr( $nombre ); ?>"<?php selected( $programa, $nombre ); ?>><?php echo esc_html( $nombre ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php echo match_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</label>
				<?php endif; ?>
				<label class="match-field">
					<span class="match-field__label"><?php esc_html_e( 'Mensaje', 'match' ); ?></span>
					<textarea class="match-field__input" name="mensaje" placeholder="<?php echo esc_attr( $placeholder ); ?>" required maxlength="2000" data-text-input></textarea>
				</label>
			</div>

			<div class="match-contacto__submit">
				<?php if ( 'ok' === $status ) : ?>
					<p class="match-contacto__notice" role="status"><?php esc_html_e( 'Gracias. Te responderemos a la brevedad.', 'match' ); ?></p>
				<?php elseif ( 'error' === $status ) : ?>
					<p class="match-contacto__notice match-contacto__notice--error" role="alert"><?php esc_html_e( 'No pudimos enviar tu mensaje. Revisa los datos e inténtalo de nuevo.', 'match' ); ?></p>
				<?php endif; ?>
				<button class="match-btn match-btn--cta" type="submit">
					<?php echo match_cta_inner( __( 'Enviar mensaje', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<p class="match-contacto__legal">
					<?php
					printf(
						/* translators: 1: enlace a Términos, 2: enlace a Política de Privacidad */
						esc_html__( 'Al enviar, aceptas nuestros %1$s y %2$s.', 'match' ),
						'<a href="' . esc_url( home_url( '/terminos-de-servicio/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Términos', 'match' ) . '</a>',
						'<a href="' . esc_url( home_url( '/politica-de-privacidad/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Política de Privacidad', 'match' ) . '</a>'
					); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</p>
			</div>
		</form>

		<div class="match-contacto__text">
			<h2 class="match-contacto__title"<?php echo match_reveal( 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php esc_html_e( 'Hablemos.', 'match' ); ?></h2>
			<p class="match-contacto__lead"<?php echo match_reveal(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php echo esc_html( $lead ); ?>
				<?php if ( $lead_muted ) : ?>
					<span class="is-muted"><?php echo esc_html( $lead_muted ); ?></span>
				<?php endif; ?>
			</p>
			<hr class="match-contacto__rule">
			<div class="match-contacto__bullets"<?php echo match_reveal(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php foreach ( $home['c_bullets'] as $bullet ) : ?>
					<div class="match-bullet">
						<p class="match-bullet__head"><?php echo match_icon( $bullet['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $bullet['head'] ); ?></p>
						<p class="match-bullet__text"><?php echo esc_html( $bullet['text'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
