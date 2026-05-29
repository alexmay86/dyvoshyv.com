<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-billing-fields">

    <div class="checkout-steps-user-data checkout-steps-user-contacts-data">
    	<h3><?php pll_e('Information'); ?></h3>
        <div class="checkout-steps-user-data-item checkout-steps-user-contacts-data-name"><?php pll_e('Name'); ?>: <span></span></div>
        <div class="checkout-steps-user-data-item checkout-steps-user-contacts-data-phone"><?php pll_e('Phone'); ?>: <span></span></div>
        <div class="checkout-steps-user-data-item checkout-steps-user-contacts-data-email"><?php pll_e('Email'); ?>: <span></span></div>
    </div>
	<div class="checkout-steps-user-data checkout-steps-user-shipping-data">
	<h3><?php pll_e('Shipping Address'); ?></h3>
        <div class="checkout-steps-user-data-item checkout-steps-user-shipping-data-country"><?php pll_e('Country'); ?>: <span></span></div>
        <div class="checkout-steps-user-data-item checkout-steps-user-shipping-data-city"><?php pll_e('City'); ?>: <span></span></div>
        <div class="checkout-steps-user-data-item checkout-steps-user-shipping-data-zipcode"><?php pll_e('Zip-Code'); ?>: <span></span></div>
		<div class="checkout-steps-user-data-item checkout-steps-user-shipping-data-address"><?php pll_e('Address'); ?>: <span></span></div>
    </div>
	<div class="checkout-steps-user-data checkout-steps-user-shipping-data">
    	<h3><?php pll_e('Shipping Method'); ?></h3>
        <div class="checkout-steps-user-data-item checkout-steps-user-shipping-data-delivery"><?php pll_e('Shipping'); ?>: <span></span></div>
    </div>

	<h3 class="checkout-steps-heading checkout-steps-heading-contacts"><?php pll_e('Contact Information'); ?></h3>
	<h3 class="checkout-steps-heading checkout-steps-heading-shipping"><?php pll_e('Shipping Address'); ?></h3>
	<h3 class="checkout-steps-heading checkout-steps-heading-payment"><?php pll_e('Payment Method'); ?></h3>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		foreach ( $fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<div class="checkout-steps-user-actions checkout-steps-user-contacts-actions">
    <a href="<?php echo wc_get_cart_url(); ?>" class="checkout-steps-action checkout-steps-back checkout-steps-back-contacts">
        <svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.505025 5.50503C0.231658 5.77839 0.231658 6.22161 0.505025 6.49497L4.9598 10.9497C5.23316 11.2231 5.67638 11.2231 5.94975 10.9497C6.22311 10.6764 6.22311 10.2332 5.94975 9.9598L1.98995 6L5.94975 2.0402C6.22311 1.76684 6.22311 1.32362 5.94975 1.05025C5.67638 0.776886 5.23316 0.776886 4.9598 1.05025L0.505025 5.50503ZM1 6.7H17V5.3H1V6.7Z" fill="#353D3B"/></svg>
        <?php pll_e('Return to Cart'); ?>
    </a>
    <button type="button" class="checkout-steps-action checkout-steps-forward checkout-steps-forward-contacts"><?php pll_e('Go to Shipping'); ?></button>
</div>

<div class="checkout-steps-user-actions checkout-steps-user-shipping-actions">
    <button class="checkout-steps-action checkout-steps-back checkout-steps-back-shipping">
        <svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.505025 5.50503C0.231658 5.77839 0.231658 6.22161 0.505025 6.49497L4.9598 10.9497C5.23316 11.2231 5.67638 11.2231 5.94975 10.9497C6.22311 10.6764 6.22311 10.2332 5.94975 9.9598L1.98995 6L5.94975 2.0402C6.22311 1.76684 6.22311 1.32362 5.94975 1.05025C5.67638 0.776886 5.23316 0.776886 4.9598 1.05025L0.505025 5.50503ZM1 6.7H17V5.3H1V6.7Z" fill="#353D3B"/></svg>
        <?php pll_e('Return to Info'); ?>
    </button>
    <button type="button" class="checkout-steps-action checkout-steps-forward checkout-steps-forward-shipping"><?php pll_e('Go to Payment'); ?></button>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
