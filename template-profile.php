<?php
/**
 * Template Name: Job Board — Perfil
 *
 * Perfil del candidato (Figma: Job-Board/Perfil, node 4216:3). Exige sesión.
 * Formularios independientes: datos de cuenta, CV, enlace de cambio de
 * contraseña y eliminar cuenta (solo candidatos, con confirmación en un
 * <dialog>); los procesa inc/jobboard.php vía admin-post.php.
 */
defined( 'ABSPATH' ) || exit;

$user   = wp_get_current_user();
$cv     = match_user_cv();
$notice = match_profile_notice();
// Solo candidatos (subscriber): admin y demás roles no ven "Eliminar cuenta".
$can_delete = match_user_can_self_delete( $user );

get_header( 'jobboard' );
?>
<div class="match-jb">
	<?php get_template_part( 'template-parts/jobboard/sidebar', null, array( 'current' => 'perfil' ) ); ?>

	<main id="main" class="match-jb__content">
		<?php get_template_part( 'template-parts/jobboard/topbar' ); ?>

		<div class="match-jb__body match-jb__body--center">
			<div class="match-jb-profile" data-aos="fade-up">
				<?php if ( $notice ) : ?>
					<p class="match-jb-notice match-jb-notice--<?php echo esc_attr( $notice['type'] ); ?>" role="<?php echo 'error' === $notice['type'] ? 'alert' : 'status'; ?>"><?php echo esc_html( $notice['text'] ); ?></p>
				<?php endif; ?>

				<div class="match-jb-avatar">
					<span class="match-jb-avatar__img" aria-hidden="true"><?php echo match_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="match-jb-avatar__text">
						<p class="match-jb-avatar__name"><?php echo esc_html( $user->display_name ); ?></p>
						<p class="match-jb-avatar__mail"><?php echo esc_html( $user->user_email ); ?></p>
					</div>
				</div>

				<div class="match-jb-profile__card">
					<form class="match-jb-profile__block" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<h2 class="match-jb-profile__title"><?php esc_html_e( 'Información de cuenta', 'match' ); ?></h2>
						<input type="hidden" name="action" value="match_profile_save">
						<?php wp_nonce_field( 'match_profile_save', '_match_nonce' ); ?>
						<div class="match-jb-profile__fields">
							<label class="match-jb-field">
								<span class="screen-reader-text"><?php esc_html_e( 'Correo electrónico', 'match' ); ?></span>
								<input class="match-jb-field__input" type="email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" placeholder="<?php esc_attr_e( 'Correo electrónico', 'match' ); ?>" required autocomplete="email">
							</label>
							<div class="match-jb-profile__row">
								<label class="match-jb-field">
									<span class="screen-reader-text"><?php esc_html_e( 'Nombre', 'match' ); ?></span>
									<input class="match-jb-field__input" type="text" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" placeholder="<?php esc_attr_e( 'Nombre', 'match' ); ?>" autocomplete="given-name" maxlength="50" pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e( 'Solo letras, espacios, apóstrofo o guion.', 'match' ); ?>" data-name-input>
								</label>
								<label class="match-jb-field">
									<span class="screen-reader-text"><?php esc_html_e( 'Apellido', 'match' ); ?></span>
									<input class="match-jb-field__input" type="text" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" placeholder="<?php esc_attr_e( 'Apellido', 'match' ); ?>" autocomplete="family-name" maxlength="50" pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e( 'Solo letras, espacios, apóstrofo o guion.', 'match' ); ?>" data-name-input>
								</label>
							</div>
							<button class="match-jb__btn match-jb__btn--dark match-jb__btn--block" type="submit"><?php esc_html_e( 'Guardar cambios', 'match' ); ?></button>
						</div>
					</form>

					<hr class="match-jb-profile__rule">

					<form class="match-jb-profile__block" id="cv" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<h2 class="match-jb-profile__title"><?php esc_html_e( 'Mi CV', 'match' ); ?></h2>
						<input type="hidden" name="action" value="match_profile_cv">
						<?php wp_nonce_field( 'match_profile_cv', '_match_nonce' ); ?>
						<div class="match-jb-profile__fields">
							<?php get_template_part( 'template-parts/jobboard/cv-row', null, array( 'cv' => $cv ) ); ?>
							<label class="match-jb__btn match-jb__btn--dark match-jb__btn--block">
								<?php echo $cv ? esc_html__( 'Reemplazar CV', 'match' ) : esc_html__( 'Subir CV', 'match' ); ?>
								<input class="screen-reader-text" type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required data-autosubmit>
							</label>
							<noscript><button class="match-jb__btn match-jb__btn--light match-jb__btn--block" type="submit"><?php esc_html_e( 'Guardar CV', 'match' ); ?></button></noscript>
						</div>
					</form>

					<hr class="match-jb-profile__rule">

					<form class="match-jb-profile__block" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<div class="match-jb-profile__head">
							<h2 class="match-jb-profile__title"><?php esc_html_e( 'Cambiar contraseña', 'match' ); ?></h2>
							<p class="match-jb-profile__help">
								<?php
								/* translators: %s: correo */
								printf( esc_html__( 'Te enviamos el link a %s para que puedas crear una nueva contraseña.', 'match' ), esc_html( $user->user_email ) );
								?>
							</p>
						</div>
						<input type="hidden" name="action" value="match_profile_reset">
						<?php wp_nonce_field( 'match_profile_reset', '_match_nonce' ); ?>
						<button class="match-jb__btn match-jb__btn--dark match-jb__btn--block" type="submit"><?php esc_html_e( 'Enviar link de reseteo', 'match' ); ?></button>
					</form>

					<?php if ( $can_delete ) : ?>
						<hr class="match-jb-profile__rule">

						<div class="match-jb-profile__block">
							<div class="match-jb-profile__head">
								<h2 class="match-jb-profile__title"><?php esc_html_e( 'Eliminar cuenta', 'match' ); ?></h2>
								<p class="match-jb-profile__help"><?php esc_html_e( 'Se borran tus datos, tu CV y tus vacantes guardadas. No se puede deshacer.', 'match' ); ?></p>
							</div>
							<button class="match-jb__btn match-jb__btn--danger match-jb__btn--block" type="button" hidden data-delete-open><?php esc_html_e( 'Eliminar cuenta', 'match' ); ?></button>
							<noscript>
								<form class="match-jb-profile__fields" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="match_profile_delete">
									<?php wp_nonce_field( 'match_profile_delete', '_match_nonce' ); ?>
									<label class="match-jb-field">
										<span class="screen-reader-text"><?php esc_html_e( 'Escribe ELIMINAR para confirmar', 'match' ); ?></span>
										<input class="match-jb-field__input" type="text" name="confirm" placeholder="<?php esc_attr_e( 'Escribe ELIMINAR para confirmar', 'match' ); ?>" required pattern="ELIMINAR" autocomplete="off">
									</label>
									<button class="match-jb__btn match-jb__btn--danger match-jb__btn--block" type="submit"><?php esc_html_e( 'Eliminar cuenta', 'match' ); ?></button>
								</form>
							</noscript>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</main>
</div>

<?php if ( $can_delete ) : ?>
	<dialog class="match-jb-result-modal" id="match-delete-account" aria-labelledby="match-delete-account-title">
		<form class="match-jb-result-modal__panel" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="match_profile_delete">
			<?php wp_nonce_field( 'match_profile_delete', '_match_nonce' ); ?>
			<div class="match-jb-result-modal__body">
				<div class="match-jb-result-modal__icon match-jb-result-modal__icon--error">
					<?php echo match_icon( 'error-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<h2 class="match-jb-result-modal__title" id="match-delete-account-title"><?php esc_html_e( '¿Eliminar tu cuenta?', 'match' ); ?></h2>
				<p class="match-jb-result-modal__hint"><?php esc_html_e( 'Se borran tus datos, tu CV y tus vacantes guardadas. Esta acción no se puede deshacer.', 'match' ); ?></p>
				<hr class="match-jb-result-modal__rule">
				<label class="match-jb-field match-jb-delete__field">
					<span class="match-jb-delete__label">
						<?php
						/* translators: %s: palabra de confirmación */
						printf( esc_html__( 'Escribe %s para confirmar', 'match' ), '<strong>ELIMINAR</strong>' );
						?>
					</span>
					<input class="match-jb-field__input" type="text" name="confirm" required pattern="ELIMINAR" autocomplete="off" spellcheck="false" data-delete-confirm>
				</label>
			</div>
			<div class="match-jb-result-modal__footer">
				<button class="match-jb__btn match-jb__btn--light" type="button" data-modal-close><?php esc_html_e( 'Cancelar', 'match' ); ?></button>
				<button class="match-jb__btn match-jb__btn--danger" type="submit" disabled data-delete-submit><?php esc_html_e( 'Eliminar cuenta', 'match' ); ?></button>
			</div>
		</form>
	</dialog>
<?php endif; ?>
<?php
get_footer( 'jobboard' );
