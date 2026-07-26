<?php
/**
 * Bundle product add-to-cart area.
 *
 * Override this template by copying it to
 * yourtheme/bundle-product-for-woocommerce/single-product/add-to-cart/bundle.php
 *
 * @package BPFW\Templates
 * @version 1.1.0
 *
 * @var BPFW_Product_Bundle $bundle         Bundle product.
 * @var array               $pricing        Pricing summary.
 * @var string              $layout         Layout: list | grid | compact.
 * @var string              $included_title Heading above the items list.
 */

defined( 'ABSPATH' ) || exit;

$bpfw_items  = $bundle->get_bundled_products();
$bpfw_max    = $bundle->get_max_purchasable();
$bpfw_layout = isset( $layout ) && in_array( $layout, array( 'list', 'grid', 'compact' ), true ) ? $layout : bpfw_get_bundle_layout( $bundle );
$bpfw_title  = isset( $included_title ) && '' !== trim( (string) $included_title ) ? $included_title : __( "What's included", 'bundle-product-for-woocommerce' );

if ( ! $bpfw_items ) {
	return;
}

/**
 * Fires before the bundled items list on the product page.
 *
 * @since 1.0.0
 *
 * @param BPFW_Product_Bundle $bundle Bundle product.
 */
do_action( 'bpfw_before_bundled_items', $bundle );
?>

<div class="bpfw-bundled-items bpfw-layout-<?php echo esc_attr( $bpfw_layout ); ?>" aria-label="<?php esc_attr_e( 'Products included in this bundle', 'bundle-product-for-woocommerce' ); ?>">
	<h3 class="bpfw-bundled-items__title"><?php echo esc_html( $bpfw_title ); ?></h3>

	<ul class="bpfw-bundled-items__list">
		<?php foreach ( $bpfw_items as $bpfw_item ) : ?>
			<?php
			if ( $bpfw_item['hidden'] ) {
				continue;
			}
			$bpfw_child = $bpfw_item['product'];
			?>
			<li class="bpfw-bundled-item">
				<span class="bpfw-bundled-item__image">
					<?php echo wp_kses_post( $bpfw_child->get_image( 'woocommerce_gallery_thumbnail' ) ); ?>
				</span>
				<span class="bpfw-bundled-item__info">
					<span class="bpfw-bundled-item__name">
						<?php if ( $bpfw_child->is_visible() ) : ?>
							<a href="<?php echo esc_url( $bpfw_child->get_permalink() ); ?>"><?php echo esc_html( $bpfw_child->get_name() ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $bpfw_child->get_name() ); ?>
						<?php endif; ?>
					</span>
					<span class="bpfw-bundled-item__price"><?php echo wp_kses_post( $bpfw_child->get_price_html() ); ?></span>
				</span>
				<span class="bpfw-bundled-item__qty">&times;<?php echo esc_html( $bpfw_item['qty'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $pricing['savings'] > 0 ) : ?>
		<div class="bpfw-summary">
			<span class="bpfw-summary__label"><?php esc_html_e( 'Total bundle price:', 'bundle-product-for-woocommerce' ); ?></span>
			<span class="bpfw-summary__prices">
				<del><?php echo wp_kses_post( wc_price( $pricing['regular'] ) ); ?></del>
				<ins><?php echo wp_kses_post( wc_price( $pricing['price'] ) ); ?></ins>
			</span>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Fires after the bundled items list on the product page.
 *
 * @since 1.0.0
 *
 * @param BPFW_Product_Bundle $bundle Bundle product.
 */
do_action( 'bpfw_after_bundled_items', $bundle );

if ( ! $bundle->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $bundle ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( $bundle->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $bundle->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $bundle->get_min_purchase_quantity(), $bundle ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', null !== $bpfw_max ? $bpfw_max : $bundle->get_max_purchase_quantity(), $bundle ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $bundle->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>

		<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $bundle->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
			<?php echo esc_html( $bundle->single_add_to_cart_text() ); ?>
		</button>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
