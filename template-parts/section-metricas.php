<?php
/**
 * Resultados medibles (Figma: node 3502:3469).
 * Textos y foto: match_home() (inc/home-data.php).
 */
defined( 'ABSPATH' ) || exit;

$home  = match_home();
$hero  = $home['m_hero'];
$gauge = $home['m_gauge'];
?>
<section class="match-metricas" id="resultados">
	<?php match_section_head( $home['m_eyebrow'], $home['m_title'], $home['m_title_2'] ); ?>

	<div class="match-metricas__grid">
		<div class="match-stat-hero" data-reveal="up">
			<div class="match-stat-hero__bg" aria-hidden="true"></div>
			<figure class="match-stat-hero__figure" aria-hidden="true">
				<img src="<?php echo esc_url( $hero['photo'] ); ?>" alt="" loading="lazy" decoding="async">
			</figure>
			<div class="match-stat-hero__top">
				<p class="match-stat-hero__title"><?php echo esc_html( $hero['title'] ); ?></p>
				<?php if ( $hero['sub'] ) : ?>
					<p class="match-stat-hero__sub"><?php echo esc_html( $hero['sub'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="match-stat-hero__bottom">
				<p class="match-stat-hero__value"><?php echo esc_html( $hero['value'] ); ?></p>
				<p class="match-stat-hero__text"><?php echo esc_html( $hero['text'] ); ?></p>
			</div>
		</div>

		<?php
		/*
		 * Columna derecha (Figma 5528:5481): grilla de 2 × 2 con la primera
		 * cifra, el medidor y las demás cifras. El anillo se llena con el
		 * porcentaje del valor del medidor (ej. «97%»).
		 */
		$stats   = array_values( array_filter( (array) $home['m_stats'], static fn( $stat ) => is_array( $stat ) && ( ! empty( $stat['value'] ) || ! empty( $stat['title'] ) || ! empty( $stat['text'] ) ) ) );
		$percent = preg_match( '/\d+(?:[.,]\d+)?/', (string) ( $gauge['value'] ?? '' ), $m ) ? max( 0, min( 100, (float) str_replace( ',', '.', $m[0] ) ) ) : 100;

		$stat_card = static function ( array $stat ): void {
			?>
			<div class="match-stat" data-reveal="up">
				<div class="match-stat__head">
					<?php if ( ! empty( $stat['value'] ) ) : ?>
						<p class="match-stat__big"><?php echo esc_html( $stat['value'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $stat['title'] ) ) : ?>
						<p class="match-stat__title"><?php echo esc_html( $stat['title'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $stat['text'] ) ) : ?>
					<p class="match-stat__text"><?php echo esc_html( $stat['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		};
		?>
		<div class="match-metricas__col">
			<?php if ( $stats ) : ?>
				<?php $stat_card( $stats[0] ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $gauge['value'] ) || ! empty( $gauge['title'] ) || ! empty( $gauge['text'] ) ) : ?>
				<div class="match-stat match-stat--center" data-reveal="up">
					<div class="match-gauge" style="--gauge: <?php echo esc_attr( (string) $percent ); ?>">
						<svg viewBox="0 0 120 120" aria-hidden="true" focusable="false">
							<circle class="match-gauge__track" cx="60" cy="60" r="56" />
							<circle class="match-gauge__fill" cx="60" cy="60" r="56" />
						</svg>
						<p class="match-gauge__value"><?php echo esc_html( (string) ( $gauge['value'] ?? '' ) ); ?></p>
					</div>
					<?php if ( ! empty( $gauge['title'] ) ) : ?>
						<p class="match-stat__title"><?php echo esc_html( $gauge['title'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $gauge['text'] ) ) : ?>
						<p class="match-stat__text"><?php echo esc_html( $gauge['text'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php foreach ( array_slice( $stats, 1 ) as $stat ) : ?>
				<?php $stat_card( $stat ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
