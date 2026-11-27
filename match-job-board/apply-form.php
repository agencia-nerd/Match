<?php

/**
 * Formulario de postulación (override del plugin match-job-board).
 * Figma: Modal — Postular (node 5545:5555).
 *
 * El modal solo pide CV, teléfono y carta de presentación. Nombre, apellidos
 * y correo salen del perfil del usuario en el servidor (ver
 * match_apply_fill_from_profile() en inc/jobboard.php), así que no se piden
 * ni viajan en el formulario. Excepción: una cuenta sin nombre (creada antes
 * de pedirlo en el registro) ve Nombre y Apellido, que se guardan en su perfil.
 *
 * El CV se muestra con la misma fila del perfil (jobboard/cv-row.php);
 * "Cambiar CV" es el <label> del input de archivo, así que funciona sin JS.
 * Con JS (app.js), la fila se actualiza con el archivo elegido.
 *
 * @var int     $job_id
 * @var WP_User $user
 * @var string  $cv_file
 * @var string  $cv_name
 * @var bool    $applied
 * @var string  $error
 */
defined('ABSPATH') || exit;

if (! empty($applied)) {
	echo '<div class="mjb-notice mjb-notice--ok"><p>' . esc_html__('Listo, tu postulación llegó a Match. Te escribiremos al correo de tu cuenta.', 'match') . '</p></div>';
	return;
}

if (! is_user_logged_in()) {
	// Sin sesión (Figma J12): un solo contenedor, con título, texto, «Iniciar sesión» a todo el ancho y el
	// enlace a registrarse. Al entrar o registrarse se vuelve a esta vacante con el modal de postular abierto
	// (#postular lo abre app.js).
	$return = get_permalink($job_id) . '#postular';
	$signup = function_exists('match_registro_page_url') ? match_registro_page_url() : '';
	?>
	<div class="mjb-signin">
		<h2 class="mjb-signin__title"><?php esc_html_e('Inicia sesión para postular', 'match'); ?></h2>
		<p class="mjb-signin__text"><?php esc_html_e('Postula en segundos con tu cuenta de Match.', 'match'); ?></p>
		<a class="match-jb__btn match-jb__btn--dark mjb-signin__btn" href="<?php echo esc_url(wp_login_url($return)); ?>"><?php esc_html_e('Iniciar sesión', 'match'); ?></a>
		<?php if ($signup) : ?>
			<p class="mjb-signin__alt">
				<?php esc_html_e('¿No tienes cuenta?', 'match'); ?>
				<a href="<?php echo esc_url($signup); ?>" data-apply-return="<?php echo esc_attr($return); ?>"><?php esc_html_e('Regístrate', 'match'); ?></a>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return;
}

$cv = match_user_cv($user->ID);
// Tope del API del CRM para applicant.cover_letter (maxLength 2000).
$cover_max = 2000;
?>
<form class="mjb-apply" method="post" enctype="multipart/form-data"
	action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

	<input type="hidden" name="action" value="mjb_apply">
	<input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
	<?php wp_nonce_field('mjb_apply', 'mjb_apply_nonce'); ?>

	<div class="mjb-apply__head">
		<h2 class="mjb-apply__title"><?php esc_html_e('Postular a la vacante', 'match'); ?></h2>
		<?php
		// Subtítulo «Puesto · Empresa» (Figma, J07).
		$apply_company = class_exists('MJB_Helpers') ? (string) MJB_Helpers::get_meta($job_id, 'company_name') : '';
		?>
		<p class="mjb-apply__sub"><?php echo esc_html($apply_company ? get_the_title($job_id) . ' · ' . $apply_company : get_the_title($job_id)); ?></p>
	</div>

	<?php if (! empty($error)) : ?>
		<div class="mjb-notice mjb-notice--error">
			<p><?php echo esc_html($error); ?></p>
		</div>
	<?php endif; ?>

	<div class="mjb-apply__fields">
		<?php if ('' === match_user_first_name($user)) : ?>
			<?php // Cuenta sin nombre (creada antes de pedirlo en el registro): el CRM lo exige. Se guarda en el perfil (match_apply_fill_from_profile()). ?>
			<div class="mjb-field">
				<label for="mjb-first"><?php esc_html_e('Nombre', 'match'); ?></label>
				<input id="mjb-first" name="first_name" type="text" maxlength="50" required autocomplete="given-name"
					pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e('Solo letras, espacios, apóstrofo o guion.', 'match'); ?>" data-name-input
					value="<?php echo esc_attr($user->first_name); ?>">
			</div>
			<div class="mjb-field">
				<label for="mjb-last"><?php esc_html_e('Apellido', 'match'); ?> <span class="mjb-optional"><?php esc_html_e('(opcional)', 'match'); ?></span></label>
				<input id="mjb-last" name="last_name" type="text" maxlength="50" autocomplete="family-name"
					pattern="\p{L}+(?:[ '\-]\p{L}+)*" title="<?php esc_attr_e('Solo letras, espacios, apóstrofo o guion.', 'match'); ?>" data-name-input
					value="<?php echo esc_attr($user->last_name); ?>">
				<p class="mjb-field__hint"><?php esc_html_e('Lo guardaremos en tu perfil.', 'match'); ?></p>
			</div>
		<?php endif; ?>

		<div class="mjb-field mjb-field--cv" data-cv-field>
			<input class="mjb-cv-input" id="mjb-cv" name="cv" type="file"
				accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp"
				data-cv-input data-max-bytes="<?php echo esc_attr(10 * MB_IN_BYTES); ?>"
				<?php echo $cv ? '' : 'required'; ?>>

			<div class="mjb-cv-head">
				<span class="mjb-cv-head__label"><?php esc_html_e('CV a usar', 'match'); ?></span>
				<label class="mjb-cv-head__btn" for="mjb-cv">
					<?php echo $cv ? esc_html__('Cambiar CV', 'match') : esc_html__('Subir CV', 'match'); ?>
				</label>
			</div>

			<?php get_template_part('template-parts/jobboard/cv-row', null, array('cv' => $cv)); ?>
			<p class="mjb-field__hint mjb-field__hint--error" data-cv-error hidden></p>
		</div>

		<div class="mjb-field">
			<label for="mjb-phone"><?php esc_html_e('Teléfono', 'match'); ?> <span class="mjb-optional"><?php esc_html_e('(opcional)', 'match'); ?></span></label>
			<input id="mjb-phone" name="phone" type="tel" inputmode="tel" maxlength="24" pattern="[0-9 +]*" placeholder="+51 987 654 321" autocomplete="tel"
				aria-describedby="mjb-phone-error" data-phone-input
				value="<?php echo esc_attr(get_user_meta($user->ID, 'mjb_phone', true)); ?>">
			<p class="mjb-field__hint mjb-field__hint--error" id="mjb-phone-error" data-phone-error hidden><?php esc_html_e('Ingresa un teléfono válido: solo números, espacios y +, de 9 a 15 dígitos.', 'match'); ?></p>
		</div>

		<div class="mjb-field">
			<label for="mjb-cover"><?php esc_html_e('Carta de presentación', 'match'); ?> <span class="mjb-optional"><?php esc_html_e('(opcional)', 'match'); ?></span></label>
			<textarea id="mjb-cover" name="cover_letter" rows="3" maxlength="<?php echo esc_attr($cover_max); ?>"
				aria-describedby="mjb-cover-count" data-text-input data-count-input
				placeholder="<?php esc_attr_e('Si quieres agregar contexto adicional sobre tu experiencia o motivación...', 'match'); ?>"></textarea>
			<p class="mjb-field__count" id="mjb-cover-count" data-count-output>
				<span data-count-value>0</span> / <?php echo esc_html(number_format_i18n($cover_max)); ?>
			</p>
		</div>
	</div>

	<div class="mjb-apply__actions">
		<button class="match-jb__btn match-jb__btn--light mjb-apply__cancel" type="button" data-apply-close>
			<?php esc_html_e('Cancelar', 'match'); ?>
		</button>
		<button class="match-jb__btn match-jb__btn--dark mjb-apply__submit" type="submit">
			<?php esc_html_e('Confirmar postulación', 'match'); ?>
		</button>
	</div>
</form>
