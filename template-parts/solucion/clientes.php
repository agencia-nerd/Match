<?php
/**
 * Grilla de clientes (Figma: Section — Clientes, node 3765:6892).
 *
 * @var array $args
 */
defined( 'ABSPATH' ) || exit;
$logos = match_client_logos();

// Reserva para la rotación: el resto de los clientes del tema que no están en la
// grilla (app.js los va intercambiando casilla por casilla; ver [data-logo-pool]).
$pool = array();
foreach ( $logos as $slug => $name ) {
	if ( in_array( $slug, (array) $args['clients'], true ) || ! is_readable( get_theme_file_path( "assets/img/logos/{$slug}.png" ) ) ) {
		continue;
	}
	$pool[] = array(
		'src' => get_theme_file_uri( "assets/img/logos/{$slug}.png" ),
		'alt' => $name,
	);
}
?>
<section class="match-sol-clientes">
	<div class="match-sol-clientes__head" data-aos="fade-up">
		<p class="match-sol-clientes__label"><?php echo match_icon( 'isotipo-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Nuestros clientes', 'match' ); ?></p>
		<p class="match-sol-clientes__years"><?php echo esc_html( $args['years'] ); ?></p>
	</div>
	<ul class="match-sol-clientes__grid"<?php echo $pool ? ' data-logo-pool="' . esc_attr( wp_json_encode( $pool ) ) . '"' : ''; ?>>
		<?php foreach ( $args['clients'] as $i => $slug ) : ?>
			<li data-aos="fade-up" data-aos-delay="<?php echo (int) ( ( $i % 3 ) * 100 ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( "assets/img/logos/{$slug}.png" ) ); ?>" alt="<?php echo esc_attr( $logos[ $slug ] ?? $slug ); ?>" loading="lazy" decoding="async"></li>
		<?php endforeach; ?>
	</ul>
</section>
