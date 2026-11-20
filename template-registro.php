<?php

/**
 * Template Name: Job Board — Registro
 *
 * Paso 1 del registro (Figma: Job-Board/Registro, node 4341:3554). Pide
 * nombre, apellido, correo y contraseña: match_handle_register()
 * (inc/jobboard.php) crea la cuenta, manda el correo de bienvenida
 * (inc/welcome-email.php) y loguea automáticamente para pasar al paso 2.
 * El nombre es obligatorio porque el CRM exige applicant.full_name al postular.
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
	wp_safe_redirect(match_jobboard_url());
	exit;
}

$notice     = match_registro_notice();
$old        = match_registro_old();
$google_url = match_google_login_url();

get_header('jobboard');
?>
<main id="main" class="match-login-page">
	<div class="match-login-page__bg" aria-hidden="true">
		<img src="<?php echo esc_url(get_theme_file_uri('assets/img/jobboard/login-bg.webp')); ?>" alt="" width="1920" height="1280" fetchpriority="high" decoding="async">
	</div>

	<div class="match-login-page__inner">
		<div class="match-login-page__intro" data-aos="fade-up">
			<h1 class="match-login-page__title"><?php esc_html_e('Crea tu cuenta.', 'match'); ?></h1>
			<p class="match-login-page__lead"><?php esc_html_e('Crea tu cuenta y accede a vacantes exclusivas gestionadas por Match.', 'match'); ?></p>
		</div>

		<form class="match-glass match-login-card" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-aos="fade-up" data-aos-delay="120">
			<a class="match-login-card__logo" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
				<?php echo match_logo(); // phpcs:ignore WordPress.Security.EscapeOutput 
				?>
			</a>

			<a class="match-login-card__google<?php echo $google_url ? '' : ' is-disabled'; ?>" href="<?php echo esc_url($google_url ?: '#'); ?>" <?php echo $google_url ? '' : ' aria-disabled="true" title="' . esc_attr__('Disponible próximamente', 'match') . '"'; ?>>
				<span class="match-login-card__google-icon" aria-hidden="true"><?php echo match_icon('google'); // phpcs:ignore WordPress.Security.EscapeOutput 
																				?></span>
				<?php esc_html_e('Continuar con Google', 'match'); ?>
			</a>

			<p class="match-login-card__or" aria-hidden="true"><span><?php esc_html_e('o continuar con Email', 'match'); ?></span></p>

			<?php if ($notice) : ?>
				<p class="match-login-card__notice match-login-card__notice--<?php echo esc_attr($notice['type']); ?>" role="<?php echo 'error' === $notice['type'] ? 'alert' : 'status'; ?>"><?php echo esc_html($notice['text']); ?></p>
			<?php endif; ?>

			<div class="match-login-card__fields">
				<div class="match-login-card__row">
					<label class="match-field">
						<span class="match-field__label"><?php esc_html_e('Nombre', 'match'); ?></span>
						<input class="match-field__input" type="text" name="first_name" value="<?php echo esc_attr($old['first']); ?>" placeholder="<?php esc_attr_e('John', 'match'); ?>" required autocomplete="given-name" maxlength="50" pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e('Solo letras, espacios, apóstrofo o guion.', 'match'); ?>" data-name-input>
					</label>
					<label class="match-field">
						<span class="match-field__label"><?php esc_html_e('Apellido', 'match'); ?></span>
						<input class="match-field__input" type="text" name="last_name" value="<?php echo esc_attr($old['last']); ?>" placeholder="<?php esc_attr_e('Doe', 'match'); ?>" autocomplete="family-name" maxlength="50" pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e('Solo letras, espacios, apóstrofo o guion.', 'match'); ?>" data-name-input>
					</label>
				</div>
				<label class="match-field">
					<span class="match-field__label"><?php esc_html_e('Correo electrónico', 'match'); ?></span>
					<input class="match-field__input" type="email" name="email" value="<?php echo esc_attr($old['email']); ?>" placeholder="tucorreo@ejemplo.com" required autocomplete="email">
				</label>
				<div class="match-field">
					<label class="match-field__label" for="match-register-pass"><?php esc_html_e('Contraseña', 'match'); ?></label>
					<span class="match-field__secret">
						<input class="match-field__input" id="match-register-pass" type="password" name="pass" placeholder="••••••••••••" required minlength="8" autocomplete="new-password" aria-describedby="match-register-pass-hint">
						<button class="match-field__toggle" type="button" aria-pressed="false" aria-label="<?php esc_attr_e('Mostrar contraseña', 'match'); ?>" data-password-toggle data-label-show="<?php esc_attr_e('Mostrar contraseña', 'match'); ?>" data-label-hide="<?php esc_attr_e('Ocultar contraseña', 'match'); ?>" hidden>
							<span class="match-field__toggle-show"><?php echo match_icon('eye'); // phpcs:ignore WordPress.Security.EscapeOutput 
																	?></span>
							<span class="match-field__toggle-hide"><?php echo match_icon('eye-off'); // phpcs:ignore WordPress.Security.EscapeOutput 
																	?></span>
						</button>
					</span>
					<p class="match-field__hint" id="match-register-pass-hint"><?php esc_html_e('Mínimo 8 caracteres.', 'match'); ?></p>
				</div>
			</div>

			<input type="hidden" name="action" value="match_register">
			<?php wp_nonce_field('match_register', '_match_nonce'); ?>
			<input type="text" name="match_web" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

			<div class="match-login-card__submit">
				<button class="match-btn match-btn--cta" type="submit">
					<?php echo match_cta_inner( __( 'Continuar', 'match' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<p class="match-login-card__register">
					<?php esc_html_e('¿Ya tienes cuenta?', 'match'); ?>
					<a href="<?php echo esc_url(match_login_page_url() ?: home_url('/')); ?>"><?php esc_html_e('Inicia sesión', 'match'); ?></a>
				</p>
			</div>
		</form>
	</div>

	<footer class="match-login-page__legal">
		<p><?php printf(esc_html__('© %s Match. Todos los derechos reservados.', 'match'), esc_html(wp_date('Y'))); ?></p>
		<nav aria-label="<?php esc_attr_e('Legal', 'match'); ?>">
			<a href="<?php echo esc_url(home_url('/terminos-de-servicio/')); ?>"><?php esc_html_e('Términos y condiciones', 'match'); ?></a>
			<a href="<?php echo esc_url(home_url('/politica-de-privacidad/')); ?>"><?php esc_html_e('Política de privacidad', 'match'); ?></a>
			<a href="<?php echo esc_url(home_url('/#contacto')); ?>"><?php esc_html_e('Contacto', 'match'); ?></a>
		</nav>
	</footer>
</main>
<?php
get_footer('jobboard');
