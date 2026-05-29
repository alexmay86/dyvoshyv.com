<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

<div class="checkout-steps-scale">
	<div class="checkout-steps-scale-step checkout-steps-scale-info"><?php pll_e('Info'); ?></div>
	<div class="checkout-steps-scale-step checkout-steps-scale-shipping"><?php pll_e('Shipping'); ?></div>
	<div class="checkout-steps-scale-step checkout-steps-scale-payment"><?php pll_e('Payment'); ?></div>
</div>	

<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="col2-set" id="customer_details">
			<div class="col-1">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>
	
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	
	<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
	<div class="order_review_trigger">
		<span><?php pll_e('Show order summary'); ?></span>
		<svg width="9" height="5" viewBox="0 0 9 5" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M8.01882 0.398047C8.24321 0.20065 8.60008 0.20065 8.82447 0.398047C9.06003 0.605269 9.06023 0.951166 8.8146 1.15795L4.89964 4.60195C4.78421 4.7035 4.63336 4.75 4.49681 4.75C4.34949 4.75 4.20703 4.7014 4.09398 4.60195L0.176418 1.15566C-0.058806 0.948733 -0.0588061 0.604975 0.176418 0.398048C0.400809 0.200651 0.757684 0.200651 0.982075 0.398048L4.50389 3.4962L8.01882 0.398047ZM4.50408 3.8587L0.790025 0.591432C0.673733 0.48913 0.48476 0.48913 0.368469 0.591432C0.252177 0.693734 0.252177 0.859975 0.368469 0.962276L4.28603 4.40857C4.34418 4.45972 4.41686 4.48529 4.49681 4.48529C4.56949 4.48529 4.64944 4.45972 4.70759 4.40857L8.62515 0.962276C8.74871 0.859974 8.74871 0.693733 8.63242 0.591431C8.51613 0.48913 8.32716 0.48913 8.21087 0.591431L4.50408 3.8587Z" fill="#353D3B"/><path d="M4.50408 3.8587L0.790025 0.591432C0.673733 0.48913 0.48476 0.48913 0.368469 0.591432C0.252177 0.693734 0.252177 0.859975 0.368469 0.962276L4.28603 4.40857C4.34418 4.45972 4.41686 4.48529 4.49681 4.48529C4.56949 4.48529 4.64944 4.45972 4.70759 4.40857L8.62515 0.962276C8.74871 0.859974 8.74871 0.693733 8.63242 0.591431C8.51613 0.48913 8.32716 0.48913 8.21087 0.591431L4.50408 3.8587Z" fill="#353D3B"/>
		</svg>
	</div>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
