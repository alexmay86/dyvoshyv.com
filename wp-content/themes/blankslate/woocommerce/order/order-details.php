<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 *
 * @var bool $show_downloads Controls whether the downloads table should be rendered.
 */

 // phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$downloads          = $order->get_downloadable_items();
$actions            = array_filter(
	wc_get_account_orders_actions( $order ),
	function ( $action ) {
		return pll__('View') !== $action['name'];
	}
);

// We make sure the order belongs to the user. This will also be true if the user is a guest, and the order belongs to a guest (userID === 0).
$show_customer_details = $order->get_user_id() === get_current_user_id();

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
?>
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<div class="woocommerce-order-details__title"><?php pll_e('Cart'); ?></div>

	<?php /*<div class="thankyou_order_review_trigger">
		<span><?php pll_e('Show order summary'); ?></span>
		<svg width="9" height="5" viewBox="0 0 9 5" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M8.01882 0.398047C8.24321 0.20065 8.60008 0.20065 8.82447 0.398047C9.06003 0.605269 9.06023 0.951166 8.8146 1.15795L4.89964 4.60195C4.78421 4.7035 4.63336 4.75 4.49681 4.75C4.34949 4.75 4.20703 4.7014 4.09398 4.60195L0.176418 1.15566C-0.058806 0.948733 -0.0588061 0.604975 0.176418 0.398048C0.400809 0.200651 0.757684 0.200651 0.982075 0.398048L4.50389 3.4962L8.01882 0.398047ZM4.50408 3.8587L0.790025 0.591432C0.673733 0.48913 0.48476 0.48913 0.368469 0.591432C0.252177 0.693734 0.252177 0.859975 0.368469 0.962276L4.28603 4.40857C4.34418 4.45972 4.41686 4.48529 4.49681 4.48529C4.56949 4.48529 4.64944 4.45972 4.70759 4.40857L8.62515 0.962276C8.74871 0.859974 8.74871 0.693733 8.63242 0.591431C8.51613 0.48913 8.32716 0.48913 8.21087 0.591431L4.50408 3.8587Z" fill="#353D3B"/><path d="M4.50408 3.8587L0.790025 0.591432C0.673733 0.48913 0.48476 0.48913 0.368469 0.591432C0.252177 0.693734 0.252177 0.859975 0.368469 0.962276L4.28603 4.40857C4.34418 4.45972 4.41686 4.48529 4.49681 4.48529C4.56949 4.48529 4.64944 4.45972 4.70759 4.40857L8.62515 0.962276C8.74871 0.859974 8.74871 0.693733 8.63242 0.591431C8.51613 0.48913 8.32716 0.48913 8.21087 0.591431L4.50408 3.8587Z" fill="#353D3B"/>
		</svg>
	</div>*/ ?>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>

		<?php
		if ( ! empty( $actions ) ) :
			?>
		<tfoot>
			<tr>
				<th class="order-actions--heading"><?php esc_html_e( 'Actions', 'woocommerce' ); ?>:</th>
				<td>
						<?php
						$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
						foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							if ( empty( $action['aria-label'] ) ) {
								// Generate the aria-label based on the action name.
								/* translators: %1$s Action name, %2$s Order number. */
								$action_aria_label = sprintf( __( '%1$s order number %2$s', 'woocommerce' ), $action['name'], $order->get_order_number() );
							} else {
								$action_aria_label = $action['aria-label'];
							}
								echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button' . esc_attr( $wp_button_class ) . ' button ' . sanitize_html_class( $key ) . ' order-actions-button " aria-label="' . esc_attr( $action_aria_label ) . '">' . esc_html( $action['name'] ) . '</a>';
								unset( $action_aria_label );
						}
						?>
					</td>
				</tr>
			</tfoot>
			<?php endif ?>
		<tfoot>
			<?php
			$totals = $order->get_order_item_totals();

			// Modify shipping total output
			if ( isset( $totals['shipping'] ) ) {
				$shipping_total = (float) $order->get_shipping_total();
			
				$totals['shipping']['value'] = $shipping_total <= 0
					? pll__( 'Free' )
					: wc_price( $shipping_total );
			
				// Optional: Replace label
				// $totals['shipping']['label'] = esc_html__( 'Shipping:', 'woocommerce' );
			}

			foreach ( $totals as $key => $total ) {
				?>
					<tr>
						<th scope="row"><?php echo esc_html( rtrim( $total['label'], ':' ) ); ?></th>
						<td><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
			}
			?>
			<?php if ( $order->get_customer_note() ) : ?>
				<tr>
					<th><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td><?php echo wp_kses( nl2br( wptexturize( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?></td>
				</tr>
			<?php endif; ?>
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
/**
 * Action hook fired after the order details.
 *
 * @since 4.4.0
 * @param WC_Order $order Order data.
 */
do_action( 'woocommerce_after_order_details', $order );
