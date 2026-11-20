<?php
/**
 * Etiquetas de submarca sobre el título de una vacante
 * (Figma: Card — Executive + Assessment, node 5528:5519).
 *
 * @var array $args { submarcas: array (ver match_job_submarcas), class: string }
 */
defined( 'ABSPATH' ) || exit;

$submarcas = $args['submarcas'] ?? array();
if ( ! $submarcas ) {
	return;
}
?>
<ul class="match-submarcas<?php echo ! empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : ''; ?>" aria-label="<?php esc_attr_e( 'Submarcas', 'match' ); ?>">
	<?php foreach ( $submarcas as $submarca ) : ?>
		<li class="match-submarca match-submarca--<?php echo esc_attr( $submarca['slug'] ); ?>"><?php echo esc_html( $submarca['label'] ); ?></li>
	<?php endforeach; ?>
</ul>
