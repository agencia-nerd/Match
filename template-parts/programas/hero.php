<?php
/**
 * Hero de Programas (Figma: node 5467:4752).
 *
 * - Migas: página madre + página actual, salen de la jerarquía de páginas.
 * - Título: el de la página, con el punto final del diseño.
 * - Descripción, enlaces e imagen: campos PCF. Lo que esté vacío no se pinta.
 */
defined( 'ABSPATH' ) || exit;

$post_id = get_queried_object_id();
$parent  = (int) wp_get_post_parent_id( $post_id );
$field   = static fn( string $name ) => function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;

$descripcion = (string) $field( 'prog_descripcion' );
$enlaces     = array_filter( (array) $field( 'prog_enlaces' ), static fn( $row ) => ! empty( $row['texto'] ) );
$imagen      = (int) $field( 'prog_imagen' );
?>
<section class="match-prog-hero">
	<?php if ( $parent ) : ?>
		<nav class="match-prog-hero__crumbs" aria-label="<?php esc_attr_e( 'Migas de pan', 'match' ); ?>"<?php echo match_aos(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<ol>
				<li><a href="<?php echo esc_url( get_permalink( $parent ) ); ?>"><?php echo esc_html( get_the_title( $parent ) ); ?></a></li>
				<li aria-current="page"><?php echo esc_html( get_the_title( $post_id ) ); ?></li>
			</ol>
		</nav>
	<?php endif; ?>

	<div class="match-prog-hero__body">
		<div class="match-prog-hero__text"<?php echo match_aos( 100 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<h1 class="match-prog-hero__title"><?php echo esc_html( rtrim( get_the_title( $post_id ), '.' ) ); ?>.</h1>
			<?php if ( $descripcion ) : ?>
				<p class="match-prog-hero__lead"><?php echo esc_html( $descripcion ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $enlaces ) : ?>
			<ul class="match-prog-hero__pills"<?php echo match_aos( 200 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php foreach ( $enlaces as $enlace ) : ?>
					<?php
					// Un ID suelto («regular») apunta a la tarjeta con ese ID; las URL pasan tal cual.
					$destino = trim( (string) $enlace['destino'] );
					if ( $destino && ! preg_match( '~^(#|/|https?:)~', $destino ) ) {
						$destino = '#' . sanitize_title( $destino );
					}
					?>
					<li><a class="match-prog-hero__pill" href="<?php echo esc_url( $destino ?: '#' ); ?>"><?php echo esc_html( $enlace['texto'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( $imagen ) : ?>
		<figure class="match-prog-hero__media"<?php echo match_aos( 300 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php echo wp_get_attachment_image( $imagen, 'full', false, array( 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 1920px) 100vw, 1600px' ) ); ?>
		</figure>
	<?php endif; ?>
</section>
