<?php
/**
 * Formulario emergente de contacto (soluciones y programas).
 *
 * Mismo formulario que «Hablemos» (section-contacto.php) y mismo handler
 * (match_handle_contact()). Lo abre cualquier enlace con data-contact-modal
 * (app.js), que marca el perfil (data-perfil) y escribe el servicio de la
 * línea «Estás consultando por …» (data-servicio) y, en Programas, el
 * programa (data-programa). Esos enlaces apuntan a #contacto: sin JS o sin
 * <dialog>, siguen bajando a la sección.
 *
 * Tras enviar se vuelve a la misma página con ?origen=modal y el modal se
 * reabre con el aviso de estado.
 */
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification -- Solo lectura del estado de vuelta.
$from_modal = isset( $_GET['origen'] ) && 'modal' === $_GET['origen'];
$status     = $from_modal && isset( $_GET['contacto'] ) ? sanitize_key( wp_unslash( $_GET['contacto'] ) ) : '';
$profile    = $from_modal && isset( $_GET['perfil'] ) && 'profesional' === $_GET['perfil'] ? 'profesional' : 'empresa';
$servicio   = $from_modal && isset( $_GET['servicio'] ) ? sanitize_text_field( wp_unslash( $_GET['servicio'] ) ) : '';
$programa   = $from_modal && isset( $_GET['programa'] ) ? sanitize_text_field( wp_unslash( $_GET['programa'] ) ) : '';
// phpcs:enable
?>
<dialog class="match-contact-modal" id="match-contact-modal" aria-labelledby="match-contact-modal-title" data-lenis-prevent<?php echo $status ? ' data-open-on-load' : ''; ?>>
	<form class="match-contact-modal__panel" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="match_contact">
		<input type="hidden" name="origen" value="modal">
		<input type="hidden" name="volver" value="<?php echo esc_url( get_permalink( get_queried_object_id() ) ?: home_url( '/' ) ); ?>">
		<input type="hidden" name="servicio" value="<?php echo esc_attr( $servicio ); ?>" data-contact-field="servicio">
		<input type="hidden" name="programa" value="<?php echo esc_attr( $programa ); ?>" data-contact-field="programa">
		<?php wp_nonce_field( 'match_contact', 'match_contact_nonce' ); ?>
		<input type="text" name="match_web" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

		<button class="match-contact-modal__close" type="button" data-contact-modal-close>
			<span class="screen-reader-text"><?php esc_html_e( 'Cerrar', 'match' ); ?></span>
			<?php echo match_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<?php // Figma 6153:1293: «Estás consultando por» arriba y el servicio o programa como título. ?>
		<header class="match-contact-modal__head">
			<p class="match-contact-modal__about"<?php echo ( $programa ?: $servicio ) ? '' : ' hidden'; ?> data-contact-about><?php esc_html_e( 'Estás consultando por', 'match' ); ?></p>
			<h2 class="match-contact-modal__title" id="match-contact-modal-title" data-contact-label data-default="<?php esc_attr_e( 'Hablemos', 'match' ); ?>"><?php echo esc_html( $programa ?: ( $servicio ?: __( 'Hablemos', 'match' ) ) ); ?></h2>
		</header>

		<div class="match-toggle">
			<span class="match-toggle__label" id="match-modal-perfil-label"><?php esc_html_e( 'Selecciona tu perfil', 'match' ); ?></span>
			<div class="match-toggle__group" role="radiogroup" aria-labelledby="match-modal-perfil-label">
				<?php foreach ( array( 'empresa' => array( __( 'Empresa', 'match' ), 'buildings' ), 'profesional' => array( __( 'Profesional', 'match' ), 'user-circle' ) ) as $value => list( $label, $icon ) ) : ?>
					<button class="match-toggle__opt<?php echo $value === $profile ? ' is-active' : ''; ?>" type="button" role="radio" aria-checked="<?php echo $value === $profile ? 'true' : 'false'; ?>" data-value="<?php echo esc_attr( $value ); ?>">
						<?php echo esc_html( $label ); ?>
						<?php echo match_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
				<?php endforeach; ?>
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
			<label class="match-field">
				<span class="match-field__label"><?php esc_html_e( 'Mensaje', 'match' ); ?></span>
				<textarea class="match-field__input" name="mensaje" placeholder="<?php esc_attr_e( 'Cuéntanos sobre tu situación o la de tu equipo.', 'match' ); ?>" required maxlength="2000" data-text-input></textarea>
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
</dialog>
