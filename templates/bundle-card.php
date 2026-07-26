<?php
/**
 * Bundle card — used by the [bundle] shortcode, Gutenberg block and Elementor widget.
 *
 * Override this template by copying it to
 * yourtheme/bundle-product-for-woocommerce/bundle-card.php
 *
 * @package BPFW\Templates
 * @version 1.0.0
 *
 * @var BPFW_Product_Bundle $bundle Bundle product.
 * @var array               $args   Display args (layout, show_image, show_items).
 */

defined( 'ABSPATH' ) || exit;

$bpfw_pricing = bpfw_get_bundle_pricing( $bundle );
$bpfw_layout  = in_array( $args['layout'], array( 'card', 'list' ), true ) ? $args['layout'] : 'card';
?>
<div class="bpfw-card bpfw-card--<?php echo esc_attr( $bpfw_layout ); ?>">

	<div class="bpfw-card__header">
		<h3 class="bpfw-card__title">
			<a href="<?php echo esc_url( $bundle->get_permalink() ); ?>"><?php echo esc_html( $bundle->get_name() ); ?></a>
		</h3>
		<?php if ( $bpfw_pricing['savings'] > 0 ) : ?>
			<p class="bpfw-card__subtitle">
				<?php
				printf(
					/* translators: %s: formatted savings amount. */
					esc_html__( 'Buy this bundle and save %s', 'bundle-product-for-woocommerce' ),
					wp_kses_post( wc_price( $bpfw_pricing['savings'] ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $args['show_image'] && $bundle->get_image_id() ) : ?>
		<a class="bpfw-card__image" href="<?php echo esc_url( $bundle->get_permalink() ); ?>">
			<?php echo wp_kses_post( $bundle->get_image( 'woocommerce_thumbnail' ) ); ?>
		</a>
	<?php endif; ?>

	<?php if ( $args['show_items'] ) : ?>
		<ul class="bpfw-card__items">
			<?php foreach ( $bundle->get_bundled_products() as $bpfw_item ) : ?>
				<?php
				if ( $bpfw_item['hidden'] ) {
					continue;
				}
				$bpfw_child = $bpfw_item['product'];
				?>
				<li class="bpfw-bundled-item">
					<span class="bpfw-bundled-item__image"><?php echo wp_kses_post( $bpfw_child->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></span>
					<span class="bpfw-bundled-item__info">
						<span class="bpfw-bundled-item__name"><?php echo esc_html( $bpfw_child->get_name() ); ?></span>
						<span class="bpfw-bundled-item__price"><?php echo wp_kses_post( $bpfw_child->get_price_html() ); ?></span>
					</span>
					<span class="bpfw-bundled-item__qty">&times;<?php echo esc_html( $bpfw_item['qty'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="bpfw-summary">
		<span class="bpfw-summary__label"><?php esc_html_e( 'Total bundle price:', 'bundle-product-for-woocommerce' ); ?></span>
		<span class="bpfw-summary__prices">
			<?php if ( $bpfw_pricing['savings'] > 0 ) : ?>
				<del><?php echo wp_kses_post( wc_price( $bpfw_pricing['regular'] ) ); ?></del>
			<?php endif; ?>
			<ins><?php echo wp_kses_post( wc_price( $bpfw_pricing['price'] ) ); ?></ins>
		</span>
	</div>

	<?php if ( $bundle->is_purchasable() && $bundle->is_in_stock() ) : ?>
		<form class="cart bpfw-card__form" action="<?php echo esc_url( $bundle->get_permalink() ); ?>" method="post">
			<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $bundle->get_id() ); ?>" class="button alt bpfw-card__button">
				<?php
				if ( $bpfw_pricing['savings'] > 0 ) {
					printf(
						/* translators: %s: formatted savings amount. */
						esc_html__( 'Add bundle to cart (%s OFF)', 'bundle-product-for-woocommerce' ),
						wp_strip_all_tags( wc_price( $bpfw_pricing['savings'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				} else {
					esc_html_e( 'Add bundle to cart', 'bundle-product-for-woocommerce' );
				}
				?>
			</button>
		</form>
	<?php else : ?>
		<p class="bpfw-card__unavailable"><?php esc_html_e( 'This bundle is currently unavailable.', 'bundle-product-for-woocommerce' ); ?></p>
	<?php endif; ?>
</div>
