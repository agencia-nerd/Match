<?php
/**
 * Preguntas frecuentes (Figma: Section — Preguntas frecuentes, node 5485:5017).
 *
 * Acordeón con <details>: funciona sin JS y es accesible por teclado. Todos
 * comparten name, así que abrir una pregunta cierra la anterior; la primera
 * arranca abierta, como en el diseño. La respuesta es un editor visual
 * (negritas, listas, enlaces). También se imprime el JSON-LD FAQPage para
 * que Google pueda mostrar las preguntas en los resultados.
 */
defined( 'ABSPATH' ) || exit;

$post_id = get_queried_object_id();
$field   = static fn( string $name ) => function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;

$preguntas = array_values( array_filter( (array) $field( 'prog_faq_items' ), static fn( $row ) => ! empty( $row['pregunta'] ) ) );

if ( ! $preguntas ) {
	return;
}

$etiqueta    = (string) $field( 'prog_faq_eyebrow' );
$titulo      = (string) $field( 'prog_faq_titulo' );
$titulo_2    = (string) $field( 'prog_faq_titulo_2' );
$descripcion = (string) $field( 'prog_faq_descripcion' );
?>
<section class="match-prog-faq" id="preguntas-frecuentes">
	<div class="match-prog-faq__inner">
		<header class="match-prog-faq__head"<?php echo match_aos(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<div class="match-prog-faq__titles">
				<?php if ( $etiqueta ) : ?>
					<p class="match-prog-faq__eyebrow"><?php echo esc_html( $etiqueta ); ?></p>
				<?php endif; ?>
				<?php if ( $titulo || $titulo_2 ) : ?>
					<h2 class="match-prog-faq__title">
						<?php echo esc_html( $titulo ); ?>
						<?php if ( $titulo_2 ) : ?>
							<span class="is-secondary"><?php echo esc_html( $titulo_2 ); ?></span>
						<?php endif; ?>
					</h2>
				<?php endif; ?>
			</div>
			<?php if ( $descripcion ) : ?>
				<p class="match-prog-faq__text"><?php echo esc_html( $descripcion ); ?></p>
			<?php endif; ?>
		</header>

		<div class="match-prog-faq__list"<?php echo match_aos( 150 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php foreach ( $preguntas as $i => $item ) : ?>
				<details class="match-prog-faq__item" name="match-prog-faq"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary class="match-prog-faq__question">
						<span><?php echo esc_html( $item['pregunta'] ); ?></span>
						<span class="match-prog-faq__icon" aria-hidden="true">
							<?php echo match_icon( 'plus', 'match-prog-faq__plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php echo match_icon( 'minus', 'match-prog-faq__minus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</summary>
					<?php if ( ! empty( $item['respuesta'] ) ) : ?>
						<div class="match-prog-faq__answer"><?php echo wp_kses_post( $item['respuesta'] ); ?></div>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
$schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map(
		static fn( $item ) => array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $item['pregunta'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_kses( (string) ( $item['respuesta'] ?? '' ), array( 'a' => array( 'href' => true ), 'strong' => array(), 'em' => array(), 'br' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array() ) ),
			),
		),
		array_filter( $preguntas, static fn( $item ) => ! empty( $item['respuesta'] ) )
	),
);

if ( $schema['mainEntity'] ) {
	printf( '<script type="application/ld+json">%s</script>', wp_json_encode( array_merge( $schema, array( 'mainEntity' => array_values( $schema['mainEntity'] ) ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) );
}
