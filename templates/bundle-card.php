<?php
/**
 * Bundle card — used by the [cbfw_bundle] shortcode, Gutenberg block and Elementor widget.
 *
 * Override this template by copying it to
 * yourtheme/codeholt-bundles-for-woocommerce/bundle-card.php
 *
 * @package CBFW\Templates
 * @version 1.0.0
 *
 * @var CBFW_Product_Bundle $bundle Bundle product.
 * @var array               $args   Display args (layout, show_image, show_items).
 */

defined( 'ABSPATH' ) || exit;

$cbfw_pricing = cbfw_get_bundle_pricing( $bundle );
$cbfw_layout  = in_array( $args['layout'], array( 'card', 'list' ), true ) ? $args['layout'] : 'card';
?>
<div class="cbfw-card cbfw-card--<?php echo esc_attr( $cbfw_layout ); ?>">

	<div class="cbfw-card__header">
		<h3 class="cbfw-card__title">
			<a href="<?php echo esc_url( $bundle->get_permalink() ); ?>"><?php echo esc_html( $bundle->get_name() ); ?></a>
		</h3>
		<?php if ( $cbfw_pricing['savings'] > 0 ) : ?>
			<p class="cbfw-card__subtitle">
				<?php
				printf(
					/* translators: %s: formatted savings amount. */
					esc_html__( 'Buy this bundle and save %s', 'codeholt-bundles-for-woocommerce' ),
					wp_kses_post( wc_price( $cbfw_pricing['savings'] ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $args['show_image'] && $bundle->get_image_id() ) : ?>
		<a class="cbfw-card__image" href="<?php echo esc_url( $bundle->get_permalink() ); ?>">
			<?php echo wp_kses_post( $bundle->get_image( 'woocommerce_thumbnail' ) ); ?>
		</a>
	<?php endif; ?>

	<?php if ( $args['show_items'] ) : ?>
		<ul class="cbfw-card__items">
			<?php foreach ( $bundle->get_bundled_products() as $cbfw_item ) : ?>
				<?php
				if ( $cbfw_item['hidden'] ) {
					continue;
				}
				$cbfw_child = $cbfw_item['product'];
				?>
				<li class="cbfw-bundled-item">
					<span class="cbfw-bundled-item__image"><?php echo wp_kses_post( $cbfw_child->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></span>
					<span class="cbfw-bundled-item__info">
						<span class="cbfw-bundled-item__name"><?php echo esc_html( $cbfw_child->get_name() ); ?></span>
						<span class="cbfw-bundled-item__price"><?php echo wp_kses_post( $cbfw_child->get_price_html() ); ?></span>
					</span>
					<span class="cbfw-bundled-item__qty">&times;<?php echo esc_html( $cbfw_item['qty'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="cbfw-summary">
		<span class="cbfw-summary__label"><?php esc_html_e( 'Total bundle price:', 'codeholt-bundles-for-woocommerce' ); ?></span>
		<span class="cbfw-summary__prices">
			<?php if ( $cbfw_pricing['savings'] > 0 ) : ?>
				<del><?php echo wp_kses_post( wc_price( $cbfw_pricing['regular'] ) ); ?></del>
			<?php endif; ?>
			<ins><?php echo wp_kses_post( wc_price( $cbfw_pricing['price'] ) ); ?></ins>
		</span>
	</div>

	<?php if ( $bundle->is_purchasable() && $bundle->is_in_stock() ) : ?>
		<form class="cart cbfw-card__form" action="<?php echo esc_url( $bundle->get_permalink() ); ?>" method="post">
			<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $bundle->get_id() ); ?>" class="button alt cbfw-card__button">
				<?php
				if ( $cbfw_pricing['savings'] > 0 ) {
					printf(
						/* translators: %s: formatted savings amount. */
						esc_html__( 'Add bundle to cart (%s OFF)', 'codeholt-bundles-for-woocommerce' ),
						wp_strip_all_tags( wc_price( $cbfw_pricing['savings'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				} else {
					esc_html_e( 'Add bundle to cart', 'codeholt-bundles-for-woocommerce' );
				}
				?>
			</button>
		</form>
	<?php else : ?>
		<p class="cbfw-card__unavailable"><?php esc_html_e( 'This bundle is currently unavailable.', 'codeholt-bundles-for-woocommerce' ); ?></p>
	<?php endif; ?>
</div>
