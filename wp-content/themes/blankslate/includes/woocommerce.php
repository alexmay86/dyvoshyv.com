<?php 
function custom_woocommerce_cart_totals( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			// Для кастомної модалки (#custom-side-cart) на сторінках, де is_cart()/is_checkout() = false.
			'mini_cart' => 'no',
		),
		$atts,
		'custom_cart_totals'
	);
	$is_mini = ( 'yes' === $atts['mini_cart'] );
	ob_start();

	echo '<div class="blankslate-side-cart-totals">';

	// Показувати підсумок (і кнопку) лише якщо кошик НЕ порожній
	if ( ! WC()->cart->is_empty() ) {
		$allow = $is_mini || is_checkout() || is_cart();
		if ( $allow ) {
			wc_get_template( 'cart/cart-totals.php' );
			if ( $is_mini ) {
				echo '<div class="mini-cart-cart"><a href="' . esc_url( wc_get_cart_url() ) . '" class="mini-cart-link">' . pll__( 'View Cart' ) . '</a></div>';
			}
		}
	} /*else {
		echo '<div class="mini-cart-empty"><a href="' . get_permalink(pll_get_post(6751) ) . '" class="mini-cart-link">' . pll__('Start Shopping') . '</a></div>';
	}*/

	echo '</div>';

	return ob_get_clean();
}
add_shortcode( 'custom_cart_totals', 'custom_woocommerce_cart_totals' );

// Функція для виведення атрибутів товару
function display_product_attributes($atts) {
    // Отримати атрибути шорткоду
    $atts = shortcode_atts(array(
        'product_id' => ''
    ), $atts, 'product_attributes');

    // Отримати об'єкт товару за ID
    if (!empty($atts['product_id'])) {
        $product = wc_get_product($atts['product_id']);
    } else {
        global $product;
    }

    // Перевірка, чи є об'єкт товару
    if (!$product || !is_a($product, 'WC_Product')) {
        return 'Товар не знайдено або ID товару не вказано.';
    }

    // Отримати атрибути товару
    $attributes = $product->get_attributes();
    if (empty($attributes)) {
        return 'Атрибути товару відсутні.';
    }

    // Список дозволених атрибутів (з префіксом 'pa_' для глобальних атрибутів)
    $allowed_attributes = array('pa_color', 'pa_material', 'pa_size'); // Змініть на ваші слаги атрибутів

    // Перевірка наявності дозволених атрибутів
    $found = false;

    // Формувати вихідний HTML
    $output = '<div class="product-attributes">';
    foreach ($attributes as $attribute) {
        // Отримати слаг атрибуту
        $attr_name = $attribute->get_name();

        // Додатковий налагоджувальний вивід
        // Ви можете закоментувати ці рядки після перевірки
        // $output .= '<!-- Attribute found: ' . esc_html($attr_name) . ' -->';

        // Перевірка, чи атрибут є в списку дозволених
        if (!in_array($attr_name, $allowed_attributes)) {
            continue; // Пропустити атрибут, якщо він не в списку
        }

        $found = true; // Знайдено принаймні один дозволений атрибут

        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attr_name, array('fields' => 'names'));
            $output .= '<p><strong>' . esc_html(wc_attribute_label($attr_name)) . ':</strong> ' . esc_html(implode(', ', $terms)) . '</p>';
        } else {
            $output .= '<p><strong>' . esc_html(wc_attribute_label($attr_name)) . ':</strong> ' . esc_html(implode(', ', $attribute->get_options())) . '</p>';
        }
    }
    $output .= '</div>';

    if (!$found) {
        return 'Не знайдено дозволених атрибутів (Color, Material, Size).';
    }

    return $output;
}

// Реєстрація шорткоду
add_shortcode('product_attributes', 'display_product_attributes');

add_filter( 'woocommerce_quantity_input_args', 'custom_remove_qty_label', 10, 2 );

function custom_remove_qty_label( $args, $product ) {
    $args['input_value'] = isset( $args['input_value'] ) ? $args['input_value'] : 1; // За замовчуванням кількість 1.
    $args['label'] = ''; // Прибирає текст "QTY".
    return $args;
}

// Функция для получения номера заказа
function dyvoshyv_order_number_shortcode() {
    if ( is_order_received_page() ) {
        // Получаем ID заказа из URL
        $order_id = absint( get_query_var( 'order-received' ) );
        // Получаем объект заказа
        $order = wc_get_order( $order_id );
        if ( $order ) {
            // Возвращаем номер заказа с нужным классом
            return '<span class="order-number">' . esc_html( $order->get_order_number() ) . '</span>';
        }
    }
    return '';
}
// Регистрируем шорткод [dyvoshyv_order_number]
add_shortcode( 'dyvoshyv_order_number', 'dyvoshyv_order_number_shortcode' );

/* DISABLE LIQPAY FOR EURO CURRENCY */
/*add_filter('woocommerce_available_payment_gateways', function ($available_gateways) {
    if (is_admin() || !is_checkout()) {
        return $available_gateways;
    }

    // Get the current currency
    $current_currency = get_woocommerce_currency();

    // Define the currency in which LiqPay should be hidden
    if (in_array($current_currency, ['EUR', 'USD']) && isset($available_gateways['liqpay-webplus'])) {
        unset($available_gateways['liqpay-webplus']);
    }

    return $available_gateways;
});*/

/*  PAYMENT METHODS ICONS */
add_filter('woocommerce_gateway_icon', 'custom_woocommerce_payment_gateway_icons', 10, 2);
function custom_woocommerce_payment_gateway_icons($icon, $gateway_id) {
    if ($gateway_id === 'ppcp-gateway') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/paypal.svg" alt="PayPal" width="65" height="40">';
    }

    if ($gateway_id === 'liqpay-webplus') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/liqpay.svg" alt="LiqPay" width="65" height="42" style="">';
        $icon .= '<span style="display:inline-block;height:34px;border-left:1px solid #D5D8DC;margin-left:16px;margin-right:16px;vertical-align:top"></span>';
        $icon .= '<img src="' . get_template_directory_uri() . '/img/payment_methods.svg" alt="Payment Platforms" width="284" height="40" style="margin-left:0">';
    }

    return $icon;
}

/* SHIPPING METHODS STRINGS TRANSLATION */
add_filter('wcus_checkout_i18n', function ($i18n, $lang) {
    // Пример текстов для русской версии
    $i18n = [
        'shipping_method_name' => pll__('Нова Пошта по Україні'),
        'fields_title' => pll__('Вкажіть адресу доставки'),
        'shipping_type_warehouse' => pll__('На відділення (або в Поштомат)'),
        'shipping_type_doors' => pll__('На адресу'),
        'ui' => [
            'city_placeholder' => pll__('Оберіть місто'),
            'warehouse_placeholder' => pll__('Оберіть відділення'),
            'custom_address_placeholder' => pll__('Введіть адресу'),
            'text_search' => pll__('Введіть значення для пошуку'),
            'text_loading' => pll__('Завантаження...'),
            'text_more' => pll__('Завантажити ще'),
            'text_not_found' => pll__('Нічого не знайдено')
        ]
    ];

    return $i18n;
}, 10, 2);
add_filter('woocommerce_shipping_rate_label', function ($label, $rate) {
    if (function_exists('pll__') && strpos($rate->get_method_id(), 'meest') !== false) {
        return pll__('Meest Міжнародна доставка'); // Replace with translatable name
    }
    return $label;
}, 10, 2);

/* MEEST SHIPPING COST LABEL */
add_filter('woocommerce_cart_shipping_method_full_label', function ($label, $method) {
    if (function_exists('pll__')) {
        // Custom text to be added
        $custom_text = pll__('Вартість доставки в ваш регіон');
        // Get the shipping cost for the selected method
        $shipping_cost = $method->get_cost();
        
        if (!empty($shipping_cost)) {
            // Split the label into two parts: the method name and the price
            $parts = explode($method->get_label(), $label);
            
            // Reassemble the label with custom text between the method name and the price
            $label = $parts[0] . $method->get_label() . ' ' . '<br><span class="shipping-cost-text">' . $custom_text . '</span> ' . wc_price($shipping_cost);
        }
    }
    return $label;
}, 10, 2);

/* COUNTRIES FIELD SYNCHRONIZATION */
function synchronize_country() {
    if(is_checkout()) { ?>
        <script>
        jQuery(document).ready(function($) {
            $('#billing_meest_country_id').on('change', function() {
                /*const country = $(this).find('option:selected').text();
                if($('#billing_country').length) {
                    var shipping_country = $('#billing_country').find('option').filter(function() {
                        return $(this).text() === country
                    }).val();
                    console.log(shipping_country);
                    $('#billing_country').val(shipping_country).change();
                }*/
                setTimeout(function () {
                    const country = $('#billing_meest_country').val();
                    if ($('#billing_country').length) {
                        $('#billing_country').val(country).trigger('change');
                    }
                }, 0); // Delay to allow DOM update to finish
            });
        });
        </script>
    <?php }
}
add_action('wp_footer', 'synchronize_country');

/* ORDERBY SHORTCODE */
// Add this to your theme's functions.php or a custom plugin
function custom_price_sorting_radio_buttons() {
    // Get the current 'orderby' value from the URL
    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
    // Build the current URL without the 'orderby' parameter
    $current_url = remove_query_arg('orderby');
    // Output the radio buttons for sorting
    ob_start(); ?>
    <form class="orderby_custom" method="get" action="<?php echo esc_url($current_url); ?>">
        <label>
            <input type="radio" name="orderby" value="price" onchange="this.form.submit()" <?php echo ($orderby == 'price') ? 'checked' : ''; ?>> <?php pll_e('Price: Low to High'); ?>
        </label>
        <label>
            <input type="radio" name="orderby" value="price-desc" onchange="this.form.submit()" <?php echo ($orderby == 'price-desc') ? 'checked' : ''; ?>> <?php pll_e('Price: High to Low'); ?>
        </label>
        <?php // Preserve other query parameters (e.g., pagination, filters, etc.)
        if (isset($_GET['paged'])) {
            echo '<input type="hidden" name="paged" value="' . esc_attr($_GET['paged']) . '">';
        } ?>
    </form>
    <?php return ob_get_clean();
}
add_shortcode('price_sorting_radio_buttons', 'custom_price_sorting_radio_buttons');
// Ensure sorting works when "Price: Low to High" or "Price: High to Low" is selected
function custom_woocommerce_get_catalog_ordering_args( $args ) {
    if ( isset( $_GET['orderby'] ) ) {
        // If "Price: Low to High" is selected
        if ( $_GET['orderby'] === 'price' ) {
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'asc';  // Low to High
            $args['meta_key'] = '_price';  // The price meta key
        }
        // If "Price: High to Low" is selected
        elseif ( $_GET['orderby'] === 'price-desc' ) {
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'desc';  // High to Low
            $args['meta_key'] = '_price';  // The price meta key
        }
    }
    return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'custom_woocommerce_get_catalog_ordering_args' );

/* SINGLE PRODUCT GALLERY */
add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false' );
add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );
function customize_flexslider_options( $options ) {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return $options;
    }
    $product = wc_get_product( get_queried_object_id() );
    if ( ! $product || ! $product->is_type( 'simple' ) ) {
        return $options;
    }
    $options['controlNav'] = false; // Disable thumbnail navigation (simple products only)
    return $options;
}
add_filter( 'woocommerce_single_product_carousel_options', 'customize_flexslider_options' );
add_image_size('product-image', 995, 746, true);
//add_image_size('product-image-narrow', 490, 645, true);
add_image_size('product-image-large', 995, 1279, true);
add_image_size('post-carousel', 735, 570, true);
add_image_size('post-grid', 735, 429, true);
add_image_size('cart-thumbnail', 128, 164, true);
function custom_product_main_image_size( $size ) {
    global $product;
    if ( $product instanceof WC_Product && $product->is_type( 'variable' ) ) {
        return 'product-image-large';
    }
    if ( function_exists( 'is_product' ) && is_product() ) {
        $p = wc_get_product( get_queried_object_id() );
        if ( $p && $p->is_type( 'variable' ) ) {
            return 'product-image-large';
        }
    }
    return 'product-image';
}
add_filter( 'woocommerce_gallery_image_size', 'custom_product_main_image_size' );
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'custom_gallery_image_sizes_by_index', 10, 2 );
function custom_gallery_image_sizes_by_index( $html, $attachment_id ) {
    global $product;
    if ( ! $product instanceof WC_Product ) {
        return $html;
    }
    static $index = 0;
    static $context_product_id = null;
    if ( $context_product_id !== (int) $product->get_id() ) {
        $context_product_id = (int) $product->get_id();
        $index              = 0;
    }
    $index++;
    $total             = count( $product->get_gallery_image_ids() );
    $force_same_size   = function_exists( 'get_field' ) ? get_field( 'gallery_style', $product->get_id() ) : false;
    $is_variable       = $product->is_type( 'variable' );
    // Variable products: one size for every slide (and matches variation swap).
    if ( $is_variable ) {
        $image_size = 'product-image-large';
    } elseif ( $total === 1 ) {
        $image_size = 'product-image-large';
    } elseif ( $force_same_size ) {
        $image_size = 'woocommerce_thumbnail';
    } else {
        $image_size = ( $index % 3 === 0 ) ? 'product-image' : 'woocommerce_thumbnail';
    }
    // Build the new image tag
    $custom_img = wp_get_attachment_image( $attachment_id, $image_size, false, array(
        'class' => 'wp-post-image',
    ) );
    // Replace the original <img> tag in $html with our resized one
    if ( preg_match( '/<img[^>]+>/', $html, $matches ) ) {
        $html = str_replace( $matches[0], $custom_img, $html );
    }
    return $html;
}
add_filter( 'woocommerce_single_product_image_gallery_classes', 'add_custom_gallery_class_for_single_image' );
function add_custom_gallery_class_for_single_image( $classes ) {
    global $product;
    if ( ! $product ) {
        return $classes;
    }
    if ( $product->is_type( 'variable' ) ) {
        $classes[] = 'woocommerce-product-gallery_variable';
        return $classes;
    }
    $total_images = count( $product->get_gallery_image_ids() ); // +1 for main image
    if ( $total_images === 1 ) {
        $classes[] = 'woocommerce-product-gallery_single';
    } elseif ( get_field( 'gallery_style', $product->get_id() ) ) {
        $classes[] = 'woocommerce-product-gallery_grid';
    } elseif ( $total_images > 1 ) {
        $classes[] = 'woocommerce-product-gallery_simple';
    }
    return $classes;
}

/**
 * Variations without their own image inherit the parent's image_id in "view" context.
 * WooCommerce then runs wc_variations_image_update with single-size src, which clashes
 * with custom gallery markup and looks like a cropped main image. Omit image data only
 * when the variation has no dedicated thumbnail (get_image_id in edit context).
 */
function blankslate_strip_inherited_variation_image( $variation_data, $product, $variation ) {
    if ( ! $variation instanceof WC_Product_Variation ) {
        return $variation_data;
    }
    if ( ! $variation->get_image_id( 'edit' ) ) {
        $variation_data['image']    = false;
        $variation_data['image_id'] = '';
    }
    return $variation_data;
}
add_filter( 'woocommerce_available_variation', 'blankslate_strip_inherited_variation_image', 10, 3 );

/**
 * Variation JSON image.src uses woocommerce_gallery_image_size; on AJAX get_variation is_product() is false.
 * Rebuild attachment props so the swapped image matches gallery slides (product-image-large).
 */
function blankslate_variation_image_product_image_large( $data, $product, $variation ) {
    if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
        return $data;
    }
    if ( empty( $data['image'] ) || ! is_array( $data['image'] ) || empty( $data['image']['src'] ) ) {
        return $data;
    }
    $image_id = isset( $data['image_id'] ) ? (int) $data['image_id'] : 0;
    if ( ! $image_id ) {
        return $data;
    }
    add_filter( 'woocommerce_gallery_image_size', 'blankslate_filter_return_product_image_large', 999 );
    $data['image'] = wc_get_product_attachment_props( $image_id, $variation );
    remove_filter( 'woocommerce_gallery_image_size', 'blankslate_filter_return_product_image_large', 999 );
    return $data;
}
function blankslate_filter_return_product_image_large() {
    return 'product-image-large';
}
add_filter( 'woocommerce_available_variation', 'blankslate_variation_image_product_image_large', 25, 3 );

/* VARIATION HANDLING */
function variation_price_handle() {
    if(!is_singular('product')) return; ?>
    <script>
        ((typeof jQuery === "function") && !((function($,w){ $.fn.extend({ hideShow : function(callback) { this.checkForVisiblilityChange(callback); return this; }, checkForVisiblilityChange : function(callback) { if(!(this.length >>>0 )){ return undefined; } var elem,i=0; while ( ( elem = this[ i++ ] ) ) { var curValue = $(elem).is(":visible"); (elem.lastVisibility === undefined) && (elem.lastVisibility = curValue); (curValue !== elem.lastVisibility) && ( elem.lastVisibility = curValue, (typeof callback === "function") && ( callback.apply(this, [new jQuery.Event('visibilityChanged'), curValue ? "shown" : "hidden"]) ), (function(elem, curValue, w){ w.setTimeout(function(){ $(elem).trigger('visibilityChanged',[curValue ? "shown" : "hidden"]) },10) })(elem, curValue, w) ) } (function(that, a, w){ w.setTimeout(function(){ that.checkForVisiblilityChange.apply(that,a); },10) })(this, arguments, w) } }) })(jQuery, window))) || console.error("hideShow plugin requires jQuery");
        jQuery(document).ready(function($) {
            setTimeout(() => {
                $('#price-templ .price').before($('.woocommerce-variation.single_variation'));
            }, 50);
            if($('.woocommerce-variation.single_variation').is(':visible')) {
                if($('.woocommerce-variation.single_variation').find('.woocommerce-variation-price').length && !$('.woocommerce-variation.single_variation').find('.woocommerce-variation-price').is(':empty'))
                    $('.woocommerce-variation.single_variation').next('.price').hide();
            }
            $('.woocommerce-variation.single_variation').hideShow().on("visibilityChanged",function(event,visibility){
                if(visibility == 'shown') {
                    if(!$(this).find('.woocommerce-variation-price').is(':empty'))
                        $(this).next('.price').hide();
                } else $(this).next('.price').show();
            });
            $('.single-product .variations_form').on('change', function() {
                if($('.woocommerce-variation.single_variation').is(':visible')) {
                    if($('.woocommerce-variation.single_variation').find('.woocommerce-variation-price').length && !$('.woocommerce-variation.single_variation').find('.woocommerce-variation-price').is(':empty'))
                        $('.woocommerce-variation.single_variation').next('.price').hide();
                }
            });
        });
    </script>
<?php }
add_action('wp_footer', 'variation_price_handle');

/* CUSTOM ADD TO CART */
/*add_action( 'woocommerce_after_add_to_cart_quantity', function() {
    global $product;
    if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) return; ?>
    <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" 
        class="single_add_to_cart_button button alt custom-buy-now-btn">
        <?php esc_html_e( 'Checkout', 'woocommerce' ); ?>
    </button>
    <input type="hidden" name="custom_buy_now_redirect" value="no" id="custom-buy-now-redirect"> 
    <div class="add-to-cart-image">
        <img width="252" height="24" src="<?php echo get_stylesheet_directory_uri(); ?>/img/Payment_Method_product.svg" alt="<?php pll_e('Payments'); ?>">
    </div>
<?php }, 30 );

add_filter('woocommerce_add_to_cart_redirect', function($url) {
    if (!empty($_REQUEST['custom_buy_now_redirect']) && $_REQUEST['custom_buy_now_redirect'] === 'yes') {
        return wc_get_checkout_url(); // Redirect to Checkout
    }
    return $url; // Default behavior for regular Add to Cart
});*/

/* SINGLE PRODUCT GET CONSULTATION BUTTON */
add_action( 'woocommerce_after_add_to_cart_quantity', function() {
    //global $product; ?>
    <a class="consult-button">
        <?php pll_e( 'Get personal advice' ); ?>
    </a>
    <div class="add-to-cart-image">
        <img width="252" height="24" src="<?php echo get_stylesheet_directory_uri(); ?>/img/Payment_Method_product.svg" alt="<?php pll_e('Payments'); ?>">
    </div>
<?php }, 30 );

/* PRODUCT LOOP TITLE */
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_action('woocommerce_shop_loop_item_title', function() {
    echo '<div class="woocommerce-loop-product__title">' . get_the_title() . '</div>';
}, 10);

/* SINGLE PRODUCT ADDITIONAL INFO BLOCK */
function product_additional_info() {
    if(is_admin()) return;
    if (is_singular('product') && in_array(get_queried_object_id(), [pll_get_post(10099), pll_get_post(10101)])) { ?>
        <style>.product-additional-info{display:none}</style>
    <?php }
}
add_action('wp_footer', 'product_additional_info');

/* CERTIFICATE SWATCH WIDTH */
function certificate_swatch_width() {
    if(is_admin()) return;
    if (is_singular('product') && in_array(get_queried_object_id(), [pll_get_post(10101)])) { ?>
        <style>.swatchly-swatch{width:auto}.swatchly-swatch .swatchly-content{font-size:15px;padding:3px 15px}</style>
    <?php }
}
add_action('wp_footer', 'certificate_swatch_width');

/* PAGINATION */
add_filter('woocommerce_pagination_args', 'pagination_custom_arrows');
function pagination_custom_arrows($args) {
    $args['prev_text'] = '<svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.01297 1.70437L6.01287 1.70447L1.99446 5.99527L6.01297 10.2946C6.19568 10.4901 6.19568 10.8036 6.01297 10.9991C5.82485 11.2003 5.51492 11.2003 5.32681 10.9991L0.987033 6.35602C0.895251 6.25782 0.849999 6.13447 0.849999 6.00378C0.849999 5.88357 0.894063 5.75101 0.987033 5.65154L5.32539 1.01002C5.51339 0.798351 5.82465 0.798411 6.01297 0.999891C6.19568 1.19537 6.19568 1.50889 6.01297 1.70437Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.3"/></svg>';
	$args['next_text'] = '<svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.987034 1.70437L0.987134 1.70447L5.00554 5.99527L0.987035 10.2946C0.804323 10.4901 0.804323 10.8036 0.987035 10.9991C1.17515 11.2003 1.48508 11.2003 1.67319 10.9991L6.01297 6.35602C6.10475 6.25782 6.15 6.13447 6.15 6.00378C6.15 5.88357 6.10594 5.75101 6.01297 5.65154L1.67461 1.01002C1.48661 0.798351 1.17535 0.798411 0.987034 0.999891C0.804322 1.19537 0.804322 1.50889 0.987034 1.70437Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.3"/></svg>';
    return $args;
}

/**
 * AJAX: update a single cart line quantity (mini-cart / side cart) without full page reload.
 */
function blankslate_wc_ajax_update_cart_qty() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'woocommerce-cart' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
	$raw_qty       = isset( $_POST['quantity'] ) ? wp_unslash( $_POST['quantity'] ) : '';

	if ( '' === $cart_item_key || '' === $raw_qty ) {
		wp_send_json_error( array( 'message' => 'Missing data' ) );
	}

	$quantity = apply_filters( 'woocommerce_stock_amount_cart_item', wc_stock_amount( preg_replace( '/[^0-9\.]/', '', $raw_qty ) ), $cart_item_key );

	$cart_item = WC()->cart->get_cart_item( $cart_item_key );
	if ( ! $cart_item ) {
		wp_send_json_error( array( 'message' => 'Invalid cart item' ) );
	}

	$_product = $cart_item['data'];
	$passed   = apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $cart_item, $quantity );

	if ( $_product && $_product->is_sold_individually() && $quantity > 1 ) {
		$passed = false;
		wc_add_notice(
			sprintf(
				/* translators: %s: product name */
				__( 'You can only have 1 %s in your cart.', 'woocommerce' ),
				$_product->get_name()
			),
			'error'
		);
	}

	if ( ! $passed ) {
		if ( ! wc_notice_count( 'error' ) ) {
			wc_add_notice( __( 'Could not update the cart.', 'woocommerce' ), 'error' );
		}
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();
		$msg = '';
		if ( ! empty( $notices[0]['notice'] ) ) {
			$msg = wp_strip_all_tags( $notices[0]['notice'] );
		}
		wp_send_json_error( array( 'message' => $msg ) );
	}

	WC()->cart->set_quantity( $cart_item_key, $quantity, true );
	WC()->cart->calculate_totals();

	$line_subtotal_html = blankslate_get_side_cart_line_display_price_html( $cart_item_key );
	$line_prices        = blankslate_get_side_cart_all_line_display_prices();

	/*
	 * Do not call WC_AJAX::get_refreshed_fragments() here: Elementor Pro only registers
	 * cart template fragments when wc-ajax=get_refreshed_fragments, so a separate client
	 * request is required to refresh #custom-side-cart / Elementor widget markup.
	 */
	$gtm_payloads = function_exists( 'blankslate_gtm_blankslate_qty_pull' ) ? blankslate_gtm_blankslate_qty_pull() : array();

	wp_send_json_success(
		array(
			'cart_hash'       => WC()->cart->get_cart_hash(),
			'cart_item_key'   => $cart_item_key,
			'line_subtotal'   => $line_subtotal_html,
			'line_prices'     => $line_prices,
			'gtm_add_to_cart' => $gtm_payloads,
		)
	);
}

add_action( 'wc_ajax_blankslate_update_cart_qty', 'blankslate_wc_ajax_update_cart_qty' );

/**
 * Не додавати варіативний батьківський товар без обраної варіації (захист від подвійного AJAX).
 *
 * @param bool $passed     Validation result.
 * @param int  $product_id Product ID.
 * @param int  $quantity   Quantity.
 */
/**
 * wc-ajax add_to_cart ігнорує variation_id у POST; для variable треба product_id = ID варіації.
 * Підготовка запиту (пріоритет 1 — до WC_AJAX::add_to_cart).
 */
function blankslate_wc_ajax_prepare_variable_add_to_cart() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['product_id'] ) ) {
		return;
	}

	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
	if ( $variation_id <= 0 ) {
		return;
	}

	$product = wc_get_product( absint( $_POST['product_id'] ) );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	$_POST['product_id'] = $variation_id;
	unset( $_POST['variation_id'] );
}
add_action( 'wc_ajax_add_to_cart', 'blankslate_wc_ajax_prepare_variable_add_to_cart', 1 );
add_action( 'wp_ajax_woocommerce_add_to_cart', 'blankslate_wc_ajax_prepare_variable_add_to_cart', 1 );
add_action( 'wp_ajax_nopriv_woocommerce_add_to_cart', 'blankslate_wc_ajax_prepare_variable_add_to_cart', 1 );

function blankslate_block_variable_parent_without_variation( $passed, $product_id, $quantity ) {
	unset( $quantity );
	if ( ! $passed ) {
		return $passed;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return $passed;
	}
	$variation_id = isset( $_REQUEST['variation_id'] ) ? absint( $_REQUEST['variation_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $variation_id > 0 ) {
		return $passed;
	}

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'blankslate_block_variable_parent_without_variation', 10, 3 );

/**
 * Mini-cart (including Elementor "Menu Cart"): default markup is "2 × price" with no input, so AJAX qty cannot run.
 */
function blankslate_widget_cart_item_quantity_input( $html, $cart_item, $cart_item_key ) {
	if ( is_admin() || ! WC()->cart ) {
		return $html;
	}
	$_product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( ! $_product || ! $_product->exists() ) {
		return $html;
	}
	if ( ! $_product->is_purchasable() || $_product->is_sold_individually() ) {
		return $html;
	}
	$max_qty = $_product->get_max_purchase_quantity();
	if ( $max_qty < 0 ) {
		$max_qty = '';
	}
	$qty_html = woocommerce_quantity_input(
		array(
			'input_name'   => 'cart[' . $cart_item_key . '][qty]',
			'input_value'  => $cart_item['quantity'],
			'max_value'    => $max_qty,
			'min_value'    => 0,
			'product_name' => $_product->get_name(),
		),
		$_product,
		false
	);
	$product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );

	/* $qty_html already includes div.quantity; avoid nesting a second .quantity (breaks +/- helpers). */
	return $qty_html . ' <span class="mini-cart-line-price">' . $product_price . '</span>';
}
add_filter( 'woocommerce_widget_cart_item_quantity', 'blankslate_widget_cart_item_quantity_input', 5, 3 );

/**
 * Кастомна модалка #custom-side-cart: оновлення блоку підсумку (div.cart_totals з cart-totals.php).
 * Селектор збігається з вашою розміткою; окремий клас у Elementor не потрібен.
 *
 * Додаткові пари селектор → HTML: blankslate_side_cart_extra_fragments.
 */
function blankslate_get_side_cart_totals_fragment_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '<div class="blankslate-side-cart-totals"></div>';
	}

	ob_start();
	echo '<div class="blankslate-side-cart-totals">';
	if ( ! WC()->cart->is_empty() ) {
		wc_get_template( 'cart/cart-totals.php' );
		echo '<div class="mini-cart-cart"><a href="' . esc_url( wc_get_cart_url() ) . '" class="mini-cart-link">' . esc_html( pll__( 'View Cart' ) ) . '</a></div>';
	}
	echo '</div>';

	return ob_get_clean();
}

/**
 * Контекст рендеру #custom-side-cart (сирі назви з <br>, підсумок рядка за qty).
 */
function blankslate_side_cart_display_context_begin() {
	$GLOBALS['blankslate_side_cart_display_context'] = (int) ( $GLOBALS['blankslate_side_cart_display_context'] ?? 0 ) + 1;
}

function blankslate_side_cart_display_context_end() {
	if ( empty( $GLOBALS['blankslate_side_cart_display_context'] ) ) {
		return;
	}
	$GLOBALS['blankslate_side_cart_display_context']--;
	if ( $GLOBALS['blankslate_side_cart_display_context'] <= 0 ) {
		unset( $GLOBALS['blankslate_side_cart_display_context'] );
	}
}

function blankslate_is_side_cart_product_display_context() {
	return ! empty( $GLOBALS['blankslate_side_cart_display_context'] );
}

/**
 * Чи знаходиться віджет Elementor всередині секції з CSS ID custom-side-cart.
 *
 * @param Elementor\Widget_Base $widget Elementor widget.
 */
function blankslate_elementor_widget_in_custom_side_cart( $widget ) {
	if ( ! $widget instanceof Elementor\Widget_Base || ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}

	$document = \Elementor\Plugin::$instance->documents->get_current();
	if ( ! $document || ! method_exists( $document, 'get_elements_data' ) ) {
		return false;
	}

	$elements = $document->get_elements_data();
	if ( ! is_array( $elements ) || ! $elements ) {
		return false;
	}

	if ( blankslate_elementor_tree_has_widget_in_side_cart( $elements, (string) $widget->get_id() ) ) {
		return true;
	}

	/*
	 * Резерв: у документі є #custom-side-cart, і цей віджет належить тому ж шаблону
	 * (наприклад, якщо _element_id задано не на прямому предку).
	 */
	$json = wp_json_encode( $elements );
	if ( ! is_string( $json ) || strpos( $json, 'custom-side-cart' ) === false ) {
		return false;
	}

	return blankslate_elementor_document_contains_widget_id( $elements, (string) $widget->get_id() );
}

/**
 * @param array<int, array<string, mixed>> $elements Elementor elements tree.
 * @param string                           $target_widget_id Widget element id.
 */
function blankslate_elementor_document_contains_widget_id( $elements, $target_widget_id ) {
	foreach ( (array) $elements as $element ) {
		if ( ! is_array( $element ) ) {
			continue;
		}
		if ( ! empty( $element['id'] ) && (string) $element['id'] === $target_widget_id ) {
			return true;
		}
		if ( ! empty( $element['elements'] ) && blankslate_elementor_document_contains_widget_id( $element['elements'], $target_widget_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @param array<int, array<string, mixed>> $elements Elementor elements tree.
 * @param string                           $target_widget_id Widget element id.
 * @param bool                             $inside_side_cart Whether we are inside #custom-side-cart.
 */
function blankslate_elementor_tree_has_widget_in_side_cart( $elements, $target_widget_id, $inside_side_cart = false ) {
	foreach ( (array) $elements as $element ) {
		if ( ! is_array( $element ) ) {
			continue;
		}

		$inside = $inside_side_cart;
		if (
			! empty( $element['settings']['_element_id'] )
			&& 'custom-side-cart' === $element['settings']['_element_id']
		) {
			$inside = true;
		}

		if ( $inside && ! empty( $element['id'] ) && (string) $element['id'] === $target_widget_id ) {
			return true;
		}

		if ( ! empty( $element['elements'] ) && blankslate_elementor_tree_has_widget_in_side_cart( $element['elements'], $target_widget_id, $inside ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Зберегти налаштування Woolentor Cart Table з Elementor-шаблону (футер / #custom-side-cart).
 *
 * @param Elementor\Widget_Base $widget Elementor widget.
 */
function blankslate_capture_side_cart_woolentor_settings( $widget ) {
	if ( ! $widget instanceof Elementor\Widget_Base ) {
		return;
	}
	$name = $widget->get_name();
	if ( ! in_array( $name, array( 'wl-cart-table-list', 'wl-cart-table' ), true ) ) {
		return;
	}
	if ( ! blankslate_elementor_widget_in_custom_side_cart( $widget ) ) {
		return;
	}
	$settings = $widget->get_settings_for_display();
	if ( ! is_array( $settings ) || ! $settings ) {
		return;
	}
	set_transient( 'blankslate_side_cart_wl_widget', $name, DAY_IN_SECONDS );
	set_transient( 'blankslate_side_cart_wl_cfg', $settings, DAY_IN_SECONDS );
	$element_id = $widget->get_id();
	if ( $element_id ) {
		set_transient( 'blankslate_side_cart_wl_element_id', (string) $element_id, DAY_IN_SECONDS );
	}
}
add_action( 'elementor/frontend/widget/before_render', 'blankslate_capture_side_cart_woolentor_settings', 10, 1 );

/**
 * @param Elementor\Widget_Base $widget Elementor widget.
 */
function blankslate_side_cart_widget_render_begin( $widget ) {
	if ( ! $widget instanceof Elementor\Widget_Base ) {
		return;
	}
	if ( ! in_array( $widget->get_name(), array( 'wl-cart-table-list', 'wl-cart-table' ), true ) ) {
		return;
	}
	if ( ! blankslate_elementor_widget_in_custom_side_cart( $widget ) ) {
		return;
	}
	blankslate_side_cart_display_context_begin();
}
add_action( 'elementor/frontend/widget/before_render', 'blankslate_side_cart_widget_render_begin', 9, 1 );

/**
 * @param Elementor\Widget_Base $widget Elementor widget.
 */
function blankslate_side_cart_widget_render_end( $widget ) {
	if ( ! $widget instanceof Elementor\Widget_Base ) {
		return;
	}
	if ( ! in_array( $widget->get_name(), array( 'wl-cart-table-list', 'wl-cart-table' ), true ) ) {
		return;
	}
	if ( ! blankslate_elementor_widget_in_custom_side_cart( $widget ) ) {
		return;
	}
	blankslate_side_cart_display_context_end();
}
add_action( 'elementor/frontend/widget/after_render', 'blankslate_side_cart_widget_render_end', 10, 1 );

/**
 * HTML ціни рядка міні-кошика (підсумок за поточну qty).
 *
 * @param string $cart_item_key Cart item key.
 */
function blankslate_get_side_cart_line_display_price_html( $cart_item_key ) {
	if ( ! WC()->cart || ! is_string( $cart_item_key ) || $cart_item_key === '' ) {
		return '';
	}

	$cart_item = WC()->cart->get_cart_item( $cart_item_key );
	if ( ! $cart_item || empty( $cart_item['data'] ) ) {
		return '';
	}

	blankslate_side_cart_display_context_begin();
	$html = blankslate_format_side_cart_line_display_price( $cart_item, $cart_item_key );
	blankslate_side_cart_display_context_end();

	return $html;
}

/**
 * Усі рядки міні-кошика: cart_item_key => HTML ціни (для JS-патчу після AJAX).
 *
 * @return array<string, string>
 */
function blankslate_get_side_cart_all_line_display_prices() {
	$prices = array();
	if ( ! WC()->cart ) {
		return $prices;
	}

	blankslate_side_cart_display_context_begin();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( empty( $cart_item['data'] ) ) {
			continue;
		}
		$prices[ $cart_item_key ] = blankslate_format_side_cart_line_display_price( $cart_item, $cart_item_key );
	}
	blankslate_side_cart_display_context_end();

	return $prices;
}

/**
 * @param array<string, mixed> $cart_item Cart line.
 * @param string               $cart_item_key Cart item key.
 */
function blankslate_format_side_cart_line_display_price( $cart_item, $cart_item_key ) {
	$_product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	$qty      = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
	if ( ! $_product || ! $_product->exists() || $qty < 1 || ! WC()->cart ) {
		return '';
	}

	return (string) WC()->cart->get_product_subtotal( $_product, $qty );
}

/**
 * Woolentor: woocommerce_cart_item_price (за замовчуванням qty = 1).
 *
 * @param string               $price_html Formatted price HTML.
 * @param array<string, mixed> $cart_item Cart line.
 * @param string               $cart_item_key Cart item key.
 */
function blankslate_side_cart_line_item_price_html( $price_html, $cart_item, $cart_item_key ) {
	if ( ! blankslate_is_side_cart_product_display_context() ) {
		return $price_html;
	}

	return blankslate_format_side_cart_line_display_price( $cart_item, $cart_item_key );
}
add_filter( 'woocommerce_cart_item_price', 'blankslate_side_cart_line_item_price_html', 20, 3 );

/**
 * side-cart-form.php: woocommerce_cart_item_subtotal (вже з qty, але лишаємо єдину логіку).
 *
 * @param string               $subtotal_html Formatted subtotal HTML.
 * @param array<string, mixed> $cart_item Cart line.
 * @param string               $cart_item_key Cart item key.
 */
function blankslate_side_cart_line_item_subtotal_html( $subtotal_html, $cart_item, $cart_item_key ) {
	if ( ! blankslate_is_side_cart_product_display_context() ) {
		return $subtotal_html;
	}

	return blankslate_format_side_cart_line_display_price( $cart_item, $cart_item_key );
}
add_filter( 'woocommerce_cart_item_subtotal', 'blankslate_side_cart_line_item_subtotal_html', 20, 3 );

/**
 * @return array<string, mixed>
 */
function blankslate_woolentor_side_cart_default_config() {
	return array(
		'style'                        => '1',
		'qty_input_placement'          => 'after_title',
		'stock_availability_placement' => 'right',
		'discount_percent_placement'   => 'right',
		'show_thumbnail_remove_icon'   => 'yes',
		'show_meta_data'               => 'yes',
		'show_sku'                     => '',
		'show_product_stock'           => '',
		'show_discount_percent'        => '',
		'order_sku'                    => 10,
		'order_meta_data'              => 20,
		'order_qty'                    => 30,
		'order_stock_availability'     => 40,
		'order_remove_action'          => 10,
		'order_compare_action'         => 20,
		'order_wishlist_action'        => 30,
		'show_remove_action'           => '',
		'show_compare_action'          => '',
		'show_wishlist_action'         => '',
		'show_details_action'          => '',
		'disable_user_adj_qtn'         => '',
		'remove_product_link'          => '',
	);
}

/**
 * @param array<string, mixed> $settings Widget settings.
 * @return array<string, mixed>
 */
function blankslate_woolentor_build_cart_table_opt_list( $settings ) {
	$s = wp_parse_args( is_array( $settings ) ? $settings : array(), blankslate_woolentor_side_cart_default_config() );

	return array(
		'update_cart_button'   => array(
			'enable'     => isset( $s['show_update_button'] ) ? $s['show_update_button'] : '',
			'button_txt' => isset( $s['update_cart_button_txt'] ) ? $s['update_cart_button_txt'] : '',
		),
		'continue_shop_button' => array(
			'enable'     => isset( $s['show_continue_button'] ) ? $s['show_continue_button'] : '',
			'button_txt' => isset( $s['continue_button_txt'] ) ? $s['continue_button_txt'] : '',
		),
		'coupon_form'          => array(
			'enable'      => isset( $s['show_coupon_form'] ) ? $s['show_coupon_form'] : '',
			'button_txt'  => isset( $s['coupon_form_button_txt'] ) ? $s['coupon_form_button_txt'] : '',
			'placeholder' => isset( $s['coupon_form_pl_txt'] ) ? $s['coupon_form_pl_txt'] : '',
		),
		'extra_options'        => array(
			'disable_qtn' => isset( $s['disable_user_adj_qtn'] ) ? $s['disable_user_adj_qtn'] : '',
			'remove_link' => isset( $s['remove_product_link'] ) ? $s['remove_product_link'] : '',
			'show_stock'  => isset( $s['show_product_stock'] ) ? $s['show_product_stock'] : '',
		),
	);
}

/**
 * HTML блоку з товарами для #custom-side-cart (WooLentor Cart Table List / Table).
 */
function blankslate_get_side_cart_table_fragment_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	WC()->cart->calculate_totals();

	if ( WC()->cart->is_empty() ) {
		ob_start();
		echo '<div class="woocommerce">';
		wc_get_template( 'cart/cart-empty.php' );
		echo '</div>';
		return ob_get_clean();
	}

	$widget_type = get_transient( 'blankslate_side_cart_wl_widget' );
	$config      = get_transient( 'blankslate_side_cart_wl_cfg' );
	if ( ! is_array( $config ) || ! $config ) {
		$config = blankslate_woolentor_side_cart_default_config();
	}
	if ( ! is_string( $widget_type ) || ! $widget_type ) {
		$widget_type = 'wl-cart-table-list';
	}

	$config = apply_filters( 'blankslate_side_cart_woolentor_config', $config, $widget_type );
	// У AJAX-фрагменті завжди дозволяємо +/- (на фронті Woolentor інколи зберігає disable_user_adj_qtn у transient).
	$config['disable_user_adj_qtn'] = '';

	blankslate_side_cart_display_context_begin();
	ob_start();

	if ( 'wl-cart-table' === $widget_type && class_exists( 'WooLentor_Shortcode_Cart' ) ) {
		$cartopt = array(
			'cart_layout_sytle' => isset( $config['style'] ) ? $config['style'] : 'wl-cart-style-1',
			'extra_options'     => array(
				'disable_qtn' => '',
				'remove_link' => isset( $config['remove_product_link'] ) ? $config['remove_product_link'] : '',
			),
		);
		WooLentor_Shortcode_Cart::output( array(), array(), $cartopt );
	} elseif ( class_exists( 'WooLentor_Shortcode_Cart_List' ) ) {
		$cartopt = blankslate_woolentor_build_cart_table_opt_list( $config );
		if ( isset( $cartopt['extra_options'] ) && is_array( $cartopt['extra_options'] ) ) {
			$cartopt['extra_options']['disable_qtn'] = '';
		}
		WooLentor_Shortcode_Cart_List::output( $config, array(), array(), $cartopt );
	} else {
		echo '<div class="woocommerce">';
		wc_get_template( 'cart/side-cart-form.php' );
		echo '</div>';
	}

	$html = blankslate_clean_side_cart_table_html( ob_get_clean() );
	blankslate_side_cart_display_context_end();

	return $html;
}

if ( ! function_exists( 'blankslate_get_side_cart_table_fragment_selector' ) ) {
	/**
	 * Селектор фрагмента таблиці Woolentor (контейнер віджета в #custom-side-cart).
	 */
	function blankslate_get_side_cart_table_fragment_selector() {
		$selectors = array(
			'#custom-side-cart .elementor-widget-wl-cart-table-list .elementor-widget-container',
			'#custom-side-cart .elementor-widget-wl-cart-table .elementor-widget-container',
			'#custom-side-cart form.woocommerce-cart-form',
		);

		$el_id = get_transient( 'blankslate_side_cart_wl_element_id' );
		if ( is_string( $el_id ) && $el_id !== '' ) {
			array_unshift(
				$selectors,
				'#custom-side-cart .elementor-element-' . sanitize_html_class( $el_id ) . ' > .elementor-widget-container'
			);
		}

		$selectors = array_values( array_unique( array_filter( $selectors ) ) );

		return apply_filters(
			'blankslate_side_cart_table_fragment_selector',
			implode( ', ', $selectors )
		);
	}
}

if ( ! function_exists( 'blankslate_clean_side_cart_table_html' ) ) {
	/**
	 * Прибрати зайве з HTML таблиці міні-кошика (collaterals, заголовок h1).
	 *
	 * @param string $html Cart table markup.
	 */
	function blankslate_clean_side_cart_table_html( $html ) {
		$html = blankslate_strip_cart_collaterals_html( $html );
		return preg_replace( '/<h1\b[^>]*>[\s\S]*?<\/h1>/i', '', $html );
	}
}

if ( ! function_exists( 'blankslate_strip_cart_collaterals_html' ) ) {
	/**
	 * Прибрати cart-collaterals з HTML таблиці (підсумок лише через [custom_cart_totals]).
	 *
	 * @param string $html Cart table markup.
	 */
	function blankslate_strip_cart_collaterals_html( $html ) {
		if ( ! is_string( $html ) || $html === '' || false === stripos( $html, 'cart-collaterals' ) ) {
			return $html;
		}

		$prev = libxml_use_internal_errors( true );
		$doc  = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $doc );
		$nodes = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " cart-collaterals ")]' );
		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				if ( $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
		$out = $doc->saveHTML();
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		return is_string( $out ) ? $out : $html;
	}
}

function blankslate_fragments_custom_side_cart( $fragments ) {
	if ( ! WC()->cart ) {
		return $fragments;
	}

	$table_html = blankslate_get_side_cart_table_fragment_html();
	$table_sel  = blankslate_get_side_cart_table_fragment_selector();
	if ( is_string( $table_sel ) && $table_sel && $table_html ) {
		foreach ( array_map( 'trim', explode( ',', $table_sel ) ) as $one_sel ) {
			if ( $one_sel !== '' ) {
				$fragments[ $one_sel ] = $table_html;
			}
		}
	}

	$totals_html = blankslate_get_side_cart_totals_fragment_html();
	$totals_selectors = apply_filters(
		'blankslate_side_cart_totals_fragment_selectors',
		array(
			'#custom-side-cart .blankslate-side-cart-totals',
		)
	);
	if ( $totals_html && is_array( $totals_selectors ) ) {
		foreach ( $totals_selectors as $totals_sel ) {
			if ( is_string( $totals_sel ) && $totals_sel !== '' ) {
				$fragments[ $totals_sel ] = $totals_html;
			}
		}
	}

	$line_prices = blankslate_get_side_cart_all_line_display_prices();
	if ( $line_prices ) {
		$fragments['#blankslate-side-cart-line-prices-data'] = sprintf(
			'<span id="blankslate-side-cart-line-prices-data" hidden aria-hidden="true" data-line-prices="%s"></span>',
			esc_attr( wp_json_encode( $line_prices ) )
		);
	}

	$extra = apply_filters( 'blankslate_side_cart_extra_fragments', array() );
	if ( is_array( $extra ) && $extra ) {
		$fragments = array_merge( $fragments, $extra );
	}

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'blankslate_fragments_custom_side_cart', 30 );

/**
 * Якір для JSON з цінами рядків (оновлюється через cart fragments).
 */
function blankslate_side_cart_line_prices_anchor() {
	echo '<span id="blankslate-side-cart-line-prices-data" hidden aria-hidden="true" data-line-prices="{}"></span>';
}
add_action( 'wp_footer', 'blankslate_side_cart_line_prices_anchor', 3 );

/**
 * WooLentor cart table: remove link у шаблоні без data-cart_item_key — JS не може відновити cart_item_key для AJAX qty.
 */
function blankslate_wc_cart_item_remove_link_data_key( $link, $cart_item_key ) {
	if ( strpos( $link, 'data-cart_item_key=' ) !== false || strpos( $link, 'data-cart-item-key=' ) !== false ) {
		return $link;
	}
	$key = is_string( $cart_item_key ) ? $cart_item_key : '';
	if ( $key === '' ) {
		return $link;
	}
	return (string) preg_replace(
		'/^<a\s+/i',
		'<a data-cart_item_key="' . esc_attr( $key ) . '" ',
		$link,
		1
	);
}
add_filter( 'woocommerce_cart_item_remove_link', 'blankslate_wc_cart_item_remove_link_data_key', 15, 2 );

/**
 * Variable products: block submit until a variation is chosen; inline highlight (works with Elementor product templates too).
 */
function blankslate_variable_add_to_cart_validate_script() {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$msg = function_exists( 'pll__' )
		? pll__( 'Please select some product options before adding this product to your cart.' )
		: __( 'Please select some product options before adding this product to your cart.', 'woocommerce' );
	?>
	<style>
		form.variations_form .variations.woocommerce-invalid,
		form.variations_form table.variations.woocommerce-invalid {
			outline: 2px solid #b32d2e;
			outline-offset: 2px;
		}
		.blankslate-variation-error {
			display: none;
			margin: 0.75em 0;
			padding: 0.75em 1em;
			background: #f8d7da;
			color: #721c24;
			font-size: 14px;
			line-height: 1.4;
		}
	</style>
	<script>
	(function () {
		var msg = <?php echo wp_json_encode( $msg ); ?>;

		function getVariationBlock(form) {
			return form.querySelector('table.variations') || form.querySelector('.variations');
		}

		function setVariationInvalid(form, on) {
			var block = getVariationBlock(form);
			if (!block) return;
			block.classList.toggle('woocommerce-invalid', on);
			block.setAttribute('aria-invalid', on ? 'true' : 'false');
		}

		function showVariationError(form) {
			var el = form.querySelector('.blankslate-variation-error');
			if (!el) {
				el = document.createElement('div');
				el.className = 'blankslate-variation-error';
				el.setAttribute('role', 'alert');
				var hook = form.querySelector('.woocommerce-variation-add-to-cart') || form.querySelector('.single_variation_wrap') || form;
				hook.parentNode.insertBefore(el, hook);
			}
			el.textContent = msg;
			el.style.display = 'block';
		}

		function clearVariationError(form) {
			setVariationInvalid(form, false);
			var el = form.querySelector('.blankslate-variation-error');
			if (el) el.style.display = 'none';
		}

		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form || !form.classList.contains('variations_form')) return;

			var vidInput = form.querySelector('input[name="variation_id"]');
			var variationId = vidInput ? parseInt(vidInput.value, 10) : 0;
			if (variationId > 0) {
				clearVariationError(form);
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();

			setVariationInvalid(form, true);
			showVariationError(form);
			var block = getVariationBlock(form);
			if (block && typeof block.scrollIntoView === 'function') {
				block.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}, true);

		document.addEventListener('change', function (e) {
			var t = e.target;
			if (!t || !t.closest) return;
			var form = t.closest('form.variations_form');
			if (!form) return;
			clearVariationError(form);
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'blankslate_variable_add_to_cart_validate_script', 20 );

/* CART PRODUCT THUMBNAIL */
add_filter( 'woocommerce_cart_item_thumbnail', 'custom_cart_item_thumbnail_size', 10, 3 );
function custom_cart_item_thumbnail_size( $thumbnail, $cart_item, $cart_item_key ) {
    $product = $cart_item['data'];
    if ( is_callable( [ $product, 'get_image_id' ] ) ) {
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            // Change 'custom-thumbnail-size' to any registered image size or custom dimensions.
            $thumbnail = wp_get_attachment_image( $image_id, 'cart-thumbnail', false, [ 'loading' => 'lazy', 'decoding' => 'async' ] );
        }
    }
    return $thumbnail;
}

/* CART STRUCTURE */
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
//add_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 20 );
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
//add_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 20 );

/* CART UPDATER */
function cart_updater() {
    if(is_cart()) { ?>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('input', '.cart-page input.qty', function() {
                    console.log('qty changed');
                    setTimeout(() => {
                        $('.cart-page button[name="update_cart"]').prop('disabled', false).trigger('click');
                    }, 50); // Small delay to ensure value updates
                    //$('.cart-page .woocommerce-cart-form').trigger('submit');
                    /*setTimeout(function() {
                        window.location.reload();
                    }, 100);*/
                });
                $(document).on('click', '.cart-page .quantity button', function() {
                    console.log('qty changed');
                    setTimeout(() => {
                        $('.cart-page button[name="update_cart"]').prop('disabled', false).trigger('click');
                        //$('.cart-page .woocommerce-cart-form').trigger('submit');
                    }, 50); // Small delay to ensure value updates
                    /*setTimeout(function() {
                        window.location.reload();
                    }, 100);*/
                });
                $(document).on('updated_wc_div', function() {
                    console.log('Cart updated');
                    // Reload the page after the cart is updated
                    window.location.reload();
                });
            });
        </script>
    <?php }
}
add_action('wp_footer', 'cart_updater');

/* CHECKOUT HONEYPOT (anti-spam; WP Armor doesn't cover WooCommerce checkout) */
add_action( 'woocommerce_checkout_after_customer_details', 'blankslate_checkout_honeypot_field', 5 );
function blankslate_checkout_honeypot_field() {
	$name = 'checkout_hp_url';
	?>
	<div class="woocommerce-checkout-hp" aria-hidden="true">
		<label for="<?php echo esc_attr( $name ); ?>"><?php esc_html_e( 'Leave this field empty', 'blankslate' ); ?></label>
		<input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="" autocomplete="off" tabindex="-1">
	</div>
	<?php
}

add_action( 'woocommerce_checkout_process', 'blankslate_checkout_honeypot_validate' );
function blankslate_checkout_honeypot_validate() {
	$name = 'checkout_hp_url';
	if ( isset( $_POST[ $name ] ) && '' !== sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) ) {
		$msg = function_exists( 'pll__' ) ? pll__( 'We could not process your order. Please try again or contact us.' ) : __( 'We could not process your order. Please try again or contact us.', 'blankslate' );
		wc_add_notice( $msg, 'error' );
	}
}

add_action( 'woocommerce_after_checkout_validation', 'blankslate_validate_billing_phone_mask', 10, 2 );
function blankslate_validate_billing_phone_mask( $data, $errors ) {
	$phone = isset( $data['billing_phone'] ) ? trim( (string) $data['billing_phone'] ) : '';

	if ( '' === $phone ) {
		return;
	}

	if ( false !== strpos( $phone, '_' ) || ! preg_match( '/^\+\d{12}$/', $phone ) ) {
		$message = function_exists( 'pll__' )
			? pll__( 'Please enter your full phone number.' )
			: __( 'Please enter your full phone number.', 'blankslate' );
		$errors->add( 'billing_phone', $message );
	}
}

/* CHECKOUT FIELDS ORDER */
function custom_override_checkout_fields( $fields ) {
    $fields['billing']['billing_phone']['priority'] = 24;
    $fields['billing']['billing_email']['priority'] = 25;
    $fields['billing']['billing_email']['class'][0] = 'form-row-first';
	$fields['billing']['billing_phone']['class'][0] = 'form-row-last';
    $fields['billing']['billing_country']['class'][0] = 'form-row-first';
    return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

/* CHECKOUT RESTRUCTURE */
function checkout_restructure() {
    if(is_checkout()) { ?>
        <script>
            jQuery(function($) {
                // Create a condition that targets viewports
                const mediaQuery_mobile = window.matchMedia('(max-width: 1024px)');
                const mediaQuery_desktop = window.matchMedia('(min-width: 1025px)');

                //max-width: 1024px
                function handleMobileChange(e) {
                    if (e.matches) {
                        $('.order_review_trigger').removeClass('opened').next('#order_review').hide();
                        $('.order_review_trigger span').text('<?php pll_e('Show order summary'); ?>');
                    }
                }

                //min-width:1025px
                function handleDesktopChange(e) {
                    if (e.matches) {
                        $('.order_review_trigger').next('#order_review').show();
                    }
                }

                // Register event listener
                mediaQuery_mobile.addListener(handleMobileChange);
                mediaQuery_desktop.addListener(handleDesktopChange);

                // Initial check
                handleMobileChange(mediaQuery_mobile);
                handleDesktopChange(mediaQuery_desktop);

                jQuery(document).ready(function($) {
                    $('#customer_details').append($('.e-checkout__order_review-2'));
                    $('form.woocommerce-checkout').addClass('checkout-steps-first');

                    const $fieldWrapper = $('.woocommerce-billing-fields');
                    const $shippingGroup = $('<h3 class="checkout-steps-heading checkout-steps-heading-shipping checkout-steps-heading-shipping-delivery"><?php pll_e('Shipping Method'); ?></h3><ul class="custom-shipping-methods"></ul>');
                    function moveShippingMethodsToCustomBlock() {
                        const $originalShipping = $('#shipping_method');
                        const $customBlock = $('.custom-shipping-methods');

                        if (!$originalShipping.length || !$customBlock.length) return;

                        // Empty the custom container first
                        $customBlock.empty();

                        // Clone each shipping method <li> (including label and input)
                        $originalShipping.children('li').each(function () {
                            const $originalLi = $(this);
                            const $clone = $originalLi.clone();

                            // Remove ID from input to avoid duplicates
                            $clone.find('input').removeAttr('id').each(function() {
                                $(this).attr('name', $(this).attr('name') + '_custom');
                            });

                            $customBlock.append($clone);
                        });

                        // Sync selection: when user clicks a method in custom block, trigger the original input
                        $customBlock.find('input[type="radio"]').on('change', function () {
                            const selectedVal = $(this).val();
                            $originalShipping.find(`input[value="${selectedVal}"]`)
                                .prop('checked', true)
                                .trigger('click').trigger('change').next().find('.woocommerce-Price-amount').prevAll().hide();
                        });

                        let deliveryMethod = null;
                        if($('#shipping_method li').length > 1) {
                            deliveryMethod = $('#shipping_method input[type="radio"]').filter(function () {
                                return $(this).prop('checked');
                            });
                        } else {
                            deliveryMethod = $('#shipping_method .shipping_method');
                        }
                        var deliveryValue = deliveryMethod ? deliveryMethod.next('label').find('.amount').clone() : '';
                        if(!deliveryValue.length || deliveryValue.text() === '0') deliveryValue = '<?php pll_e('Free'); ?>';
                        $('.shipping_method_price_value').empty().append(deliveryValue);
                    }

                    // Run on page load and after WooCommerce updates checkout
                    moveShippingMethodsToCustomBlock();

                    // Empty and reinsert groups
                    $fieldWrapper.append($shippingGroup);

                    // Sync again whenever checkout updates
                    $('body').on('updated_checkout', moveShippingMethodsToCustomBlock);

                    $('.checkout-steps-user-contacts-data-name span').text($('#billing_first_name').val() + ' ' + $('#billing_last_name').val());
                    $('.checkout-steps-user-contacts-data-phone span').text($('#billing_phone').val());
                    $('.checkout-steps-user-contacts-data-email span').text($('#billing_email').val());

                    $('.checkout-steps-forward-contacts').on('click', function (e) {
                        e.preventDefault();

                        let isValid = true;

                        // Trigger change and blur events on each field to run WooCommerce's native validation
                        const requiredFields = $('#billing_first_name, #billing_last_name, #billing_phone, #billing_email');

                        requiredFields.each(function () {
                            const $field = $(this);
                            $field.trigger('blur');
                            $field.trigger('change');

                            if ($field.closest('.form-row').hasClass('woocommerce-invalid')) {
                                isValid = false;
                            }
                        });

                        if (isValid) {
                            $('.checkout-steps-user-contacts-data-name span').text($('#billing_first_name').val() + ' ' + $('#billing_last_name').val());
                            $('.checkout-steps-user-contacts-data-phone span').text($('#billing_phone').val());
                            $('.checkout-steps-user-contacts-data-email span').text($('#billing_email').val());
                            $('form.woocommerce-checkout').addClass('checkout-steps-second').removeClass('checkout-steps-first');
                        } else {
                            // Scroll to the first invalid field
                            const $firstInvalid = $('.woocommerce-billing-fields .woocommerce-invalid:visible').first();
                            if ($firstInvalid.length) {
                                $('html, body').animate({
                                    scrollTop: $firstInvalid.offset().top - 60
                                }, 300);
                            }
                        }
                    });

                    $('.checkout-steps-back-shipping').on('click', function (e) {
                        e.preventDefault();
                        $('form.woocommerce-checkout').addClass('checkout-steps-first').removeClass('checkout-steps-second');
                    });

                    $('.checkout-steps-forward-shipping').on('click', function (e) {
                        e.preventDefault();

                        let isValid = true;

                        // Trigger change and blur events on each field to run WooCommerce's native validation
                        let requiredFields = null;
                        let deliveryMethod = null;
                        if($('#shipping_method li').length > 1) {
                            deliveryMethod = $('#shipping_method input[type="radio"]').filter(function () {
                                return $(this).prop('checked');
                            });
                        } else {
                            deliveryMethod = $('#shipping_method .shipping_method');
                        }
                        if(deliveryMethod.val().indexOf("meest") >= 0) {
                            if($('#billing_meest_country').val() === 'UA') {
                                if($('[name="billing_delivery_type"]').filter(function () {
                                    return $(this).prop('checked');
                                }).val() === 'address') {
                                    requiredFields = $('#billing_meest_country_id, #billing_meest_city_id, #billing_meest_street_id, #billing_meest_building, #billing_meest_form #billing_postcode');
                                } else {
                                    requiredFields = $('#billing_meest_country_id, #billing_meest_city_id, #billing_meest_branch_id, #billing_meest_form #billing_postcode');
                                }
                            } else {
                                requiredFields = $('#billing_meest_country_id, #billing_meest_region_text, #billing_meest_city_text, #billing_meest_street_text, #billing_meest_building, #billing_meest_form #billing_postcode');
                            }
                        } else {
                            if($('input[name="shipping_type"]') === 'doors') {
                                requiredFields = $('[name="wcus_np_billing_city"], #wcus_np_billing_custom_address');
                            } else {
                                requiredFields = $('[name="wcus_np_billing_city"], [name="wcus_np_billing_warehouse"]');
                            }
                        }

                        requiredFields.each(function () {
                            const $field = $(this);
                            if ($field.is('select')) {
                                if($field.val() === '') $field.closest('.form-row').addClass('woocommerce-invalid');
                                else $field.closest('.form-row').removeClass('woocommerce-invalid');
                            } else {
                                if($field.is('[name="wcus_np_billing_city"]') || $field.is('[name="wcus_np_billing_warehouse"]') || $field.is('#wcus_np_billing_custom_address') || $field.is('[name="wcus_np_billing_city"]')) {
                                    if($field.val() === '') $field.closest('.form-row').addClass('woocommerce-invalid');
                                    else $field.closest('.form-row').removeClass('woocommerce-invalid');
                                } else {
                                    $field.trigger('blur');
                                    $field.trigger('change');
                                }
                            }

                            if ($field.closest('.form-row').hasClass('woocommerce-invalid')) {
                                isValid = false;
                            }
                        });

                        if (isValid) {
                            console.log('valid');
                            $('.checkout-steps-user-shipping-data-country span').text($('#billing_country option').filter(function () {
                                return $(this).prop('selected');
                            }).text());
                            $('.checkout-steps-user-shipping-data-city span').text($('#billing_meest_city_text').val());
                            $('.checkout-steps-user-shipping-data-zipcode span').text($('input[id="billing_postcode"]:visible').val());

                            if(deliveryMethod.val().indexOf("meest") >= 0 && $('#billing_meest_country').val() === 'UA' && $('[name="billing_delivery_type"]').filter(function () {return $(this).prop('checked');}).val() === 'branch') {
                                $('.checkout-steps-user-shipping-data-address span').text($('#billing_meest_branch_text').val());
                            } else if(deliveryMethod.val().indexOf("nova_poshta") >=0) {
                                $('.checkout-steps-user-shipping-data-city span').text($('[name="wcus_np_billing_city_name"]').val());
                                if($('[name="shipping_type"]').val() === 'doors') {
                                    $('.checkout-steps-user-shipping-data-address span').text($('#wcus_np_billing_custom_address').val());
                                } else {
                                    $('.checkout-steps-user-shipping-data-address span').text($('[name="wcus_np_billing_warehouse_name"]').val());
                                }
                            } else {
                                $('.checkout-steps-user-shipping-data-address span').text($('#billing_meest_street_text').val() + ' ' + $('#billing_meest_building').val());
                            }

                            if(deliveryMethod.val().indexOf("meest") >= 0) {
                                $('.checkout-steps-user-shipping-data-delivery span').text(deliveryMethod.next('label').contents().filter(function() {
                                    return this.nodeType === 3; // 3 = text node
                                }).text().trim());
                            } else {
                                $('.checkout-steps-user-shipping-data-delivery span').text(deliveryMethod.nextAll('input').val());
                            }
                            $('form.woocommerce-checkout').addClass('checkout-steps-third').removeClass('checkout-steps-second');
                        } else {
                            console.log('invalid');
                            // Scroll to the first invalid field
                            const $firstInvalid = $('.woocommerce-billing-fields .woocommerce-invalid:visible').first();
                            if ($firstInvalid.length) {
                                $('html, body').animate({
                                    scrollTop: $firstInvalid.offset().top - 60
                                }, 300);
                            }
                        }
                    });

                    $('.checkout-steps-back-payment').on('click', function (e) {
                        e.preventDefault();
                        $('form.woocommerce-checkout').addClass('checkout-steps-second').removeClass('checkout-steps-third');
                    });

                    $('.order_review_trigger').on('click', function() {
                        if($(this).hasClass('opened')) $(this).find('span').text('<?php pll_e('Show order summary'); ?>');
                        else $(this).find('span').text('<?php pll_e('Hide order summary'); ?>');
                        $(this).toggleClass('opened').next('#order_review').slideToggle();
                    });
                });
            });
        </script>
    <?php }
}
add_action('wp_footer', 'checkout_restructure');

/* PAY FOR ORDER BUTTON TEXT */
add_filter( 'woocommerce_order_button_text', function() {
    return function_exists( 'pll__' ) ? pll__( 'Pay now' ) : __( 'Pay now', 'woocommerce' );
} );
add_filter( 'woocommerce_pay_order_button_text', function() {
    return function_exists( 'pll__' ) ? pll__( 'Pay now' ) : __( 'Pay now', 'woocommerce' );
} );

/* EMPTY CART */
remove_all_filters( 'wc_empty_cart_message' );
add_filter( 'wc_empty_cart_message', function( $message ) {
	return '<div class="empty-cart-container">
		<div class="empty-cart-title">' . pll__('Your bag is still empty') . '</div>
		<a class="empty-cart-link" href="' .  get_permalink(pll_get_post(6751) ) . '">' . pll__('Start Shopping') . '</a>
	</div>';
});

/*
 * PRODUCT TITLE — <br> split
 * - the_title: safe to output HTML (loops use get_the_title(); Elementor parent title uses get_the_title()).
 * - WC product get_name: used inside esc_html() for notices, cart messages, emails — must stay plain text.
 */
/**
 * Заголовок товару без розмітки: <br> → пробіл між частинами, інші теги знімаються (GTM, plain get_name).
 */
function blankslate_product_title_plain_string( $name ) {
	$name = (string) $name;
	if ( $name === '' ) {
		return '';
	}
	if ( strpos( $name, '<br' ) !== false ) {
		$parts = preg_split( '/<br\s*\/?>/i', $name );
		$parts = array_map(
			static function ( $part ) {
				return trim( wp_strip_all_tags( $part ) );
			},
			$parts
		);
		$parts = array_filter( $parts );
		$name  = implode( ' ', $parts );
	} else {
		$name = wp_strip_all_tags( $name );
	}

	return trim( preg_replace( '/\s+/u', ' ', $name ) );
}

add_filter( 'the_title', 'wrap_title_after_br_globally', 10, 2 );
function wrap_title_after_br_globally( $title, $post_id ) {
	if ( get_post_type( $post_id ) !== 'product' || is_admin() ) return $title;
	if ( strpos( $title, '<br' ) !== false ) {
		$parts = preg_split( '/<br\s*\/?>/i', $title );
		if ( count( $parts ) > 1 ) {
			return $parts[0] . '<span class="product-title-desc"> ' . $parts[1] . '</span>';
		}
	}

	return $title;
}
/**
 * Сира назва товару з post_title (зберігає <br>).
 *
 * @param WC_Product $product Product.
 */
function blankslate_product_raw_title_for_display( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$raw = get_post_field( 'post_title', $product->get_id(), 'raw' );
	if ( ! is_string( $raw ) || $raw === '' ) {
		if ( $product->is_type( 'variation' ) ) {
			$raw = get_post_field( 'post_title', $product->get_parent_id(), 'raw' );
		}
	}

	return is_string( $raw ) ? $raw : '';
}

add_filter( 'woocommerce_product_get_name', 'wrap_br_in_product_name_plain_after_br', 10, 2 );
add_filter( 'woocommerce_product_variation_get_name', 'wrap_br_in_product_name_plain_after_br', 10, 2 );
function wrap_br_in_product_name_plain_after_br( $name, $product ) {
	if ( blankslate_is_side_cart_product_display_context() ) {
		$raw = blankslate_product_raw_title_for_display( $product );
		return $raw !== '' ? $raw : $name;
	}

	return blankslate_product_title_plain_string( $name );
}

/**
 * Ключ дедуплікації GTM-події (один push на запит / одне замовлення / один кошик).
 *
 * @param array<string, mixed> $payload dataLayer payload.
 */
function blankslate_gtm_build_payload_dedupe_key( array $payload ) {
	$event = isset( $payload['event'] ) ? (string) $payload['event'] : '';
	if ( $event === '' ) {
		return '';
	}

	if ( 'purchase' === $event && ! empty( $payload['ecommerce']['transaction_id'] ) ) {
		return 'purchase|' . (string) $payload['ecommerce']['transaction_id'];
	}

	if ( 'begin_checkout' === $event && WC()->cart ) {
		return 'begin_checkout|' . (string) WC()->cart->get_cart_hash();
	}

	if ( ! empty( $payload['ecommerce']['items'][0] ) && is_array( $payload['ecommerce']['items'][0] ) ) {
		$item = $payload['ecommerce']['items'][0];
		return implode(
			'|',
			array(
				$event,
				(string) ( $item['item_id'] ?? '' ),
				(string) ( $item['quantity'] ?? '' ),
				(string) ( $payload['ecommerce']['value'] ?? '' ),
			)
		);
	}

	return $event;
}

/**
 * Чи можна відправити payload у dataLayer (без дубля в межах одного HTTP-запиту).
 *
 * @param array<string, mixed> $payload dataLayer payload.
 */
function blankslate_gtm_should_emit_payload( array $payload ) {
	static $emitted = array();

	$key = blankslate_gtm_build_payload_dedupe_key( $payload );
	if ( $key !== '' && isset( $emitted[ $key ] ) ) {
		return false;
	}
	if ( $key !== '' ) {
		$emitted[ $key ] = true;
	}

	return true;
}

/**
 * Вивести один dataLayer.push у футері (з дедуплікацією).
 *
 * @param array<string, mixed> $payload dataLayer payload.
 */
function blankslate_gtm_emit_datalayer_payload( array $payload ) {
	if ( empty( $payload['event'] ) || empty( $payload['ecommerce'] ) ) {
		return false;
	}
	if ( ! blankslate_gtm_should_emit_payload( $payload ) ) {
		return false;
	}
	?>
<script>
window.dataLayer = window.dataLayer || [];
dataLayer.push({ ecommerce: null });
dataLayer.push(<?php echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>);
</script>
	<?php
	return true;
}

/**
 * begin_checkout: не дублювати для того самого вмісту кошика в межах сесії (напр. подвійний wp_footer).
 */
function blankslate_gtm_begin_checkout_already_sent_for_cart() {
	if ( ! WC()->session || ! WC()->cart || WC()->cart->is_empty() ) {
		return false;
	}
	$hash = (string) WC()->cart->get_cart_hash();
	$sent = (string) WC()->session->get( 'blankslate_gtm_begin_checkout_cart_hash', '' );
	return $hash !== '' && $hash === $sent;
}

function blankslate_gtm_mark_begin_checkout_sent_for_cart() {
	if ( ! WC()->session || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	WC()->session->set( 'blankslate_gtm_begin_checkout_cart_hash', (string) WC()->cart->get_cart_hash() );
}

/**
 * GTM / dataLayer: активна валюта магазину (з урахуванням WooCommerce Multi Currency, якщо є).
 */
function blankslate_gtm_store_currency() {
	if ( function_exists( 'wmc_get_woocommerce_currency' ) ) {
		return wmc_get_woocommerce_currency();
	}
	return get_woocommerce_currency();
}

/**
 * GA4 item_category: slug «найглибшої» призначеної категорії; fallback — передостанній сегмент шляху permalink.
 */
function blankslate_gtm_view_item_category_slug( WC_Product $product ) {
	$slug  = '';
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$best      = null;
		$max_depth = -1;
		foreach ( $terms as $term ) {
			$depth = count( get_ancestors( $term->term_id, 'product_cat' ) );
			if ( $depth > $max_depth ) {
				$max_depth = $depth;
				$best      = $term;
			}
		}
		$slug = $best ? $best->slug : $terms[0]->slug;
	}
	if ( ! $slug ) {
		$path = wp_parse_url( $product->get_permalink(), PHP_URL_PATH );
		if ( is_string( $path ) && $path !== '' ) {
			$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
			if ( count( $parts ) >= 2 ) {
				$slug = sanitize_title( $parts[ count( $parts ) - 2 ] );
			}
		}
	}

	return apply_filters( 'blankslate_gtm_view_item_item_category', $slug, $product );
}

/**
 * Ціна для GTM: фактична ціна покупки (зі знижкою), з урахуванням податків як у вітрині.
 */
function blankslate_gtm_view_item_price( WC_Product $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		if ( empty( $prices['price'] ) ) {
			return 0.0;
		}
		return (float) wc_format_decimal( current( $prices['price'] ) );
	}
	return (float) wc_get_price_to_display( $product );
}

/**
 * dataLayer: view_item на завантаженні картки товару (single product).
 */
function blankslate_gtm_data_layer_view_item() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	if ( ! apply_filters( 'blankslate_gtm_enable_view_item', true, $product ) ) {
		return;
	}

	$price    = round( blankslate_gtm_view_item_price( $product ), 2 );
	$currency = blankslate_gtm_store_currency();

	$payload = array(
		'event'     => 'view_item',
		'ecommerce' => array(
			'currency' => $currency,
			'value'    => $price,
			'items'    => array(
				array(
					'item_id'       => (string) $product->get_id(),
					'item_name'     => blankslate_product_title_plain_string(
						get_post_field( 'post_title', $product->get_id(), 'raw' )
					),
					'item_category' => blankslate_gtm_view_item_category_slug( $product ),
					'price'         => $price,
					'quantity'      => 1,
				),
			),
		),
	);

	$payload = apply_filters( 'blankslate_gtm_view_item_payload', $payload, $product );

	if ( empty( $payload['event'] ) || empty( $payload['ecommerce'] ) ) {
		return;
	}

	blankslate_gtm_emit_datalayer_payload( $payload );
}
add_action( 'wp_footer', 'blankslate_gtm_data_layer_view_item', 15 );

/**
 * GTM add_to_cart: якір для підміни фрагментом після WC AJAX add_to_cart (див. woocommerce_add_to_cart_fragments).
 */
function blankslate_gtm_fragment_anchor() {
	if ( is_admin() ) {
		return;
	}
	echo '<span id="blankslate-gtm-fragment-anchor" class="blankslate-gtm-fragment-anchor" aria-hidden="true"></span>';
}
add_action( 'wp_footer', 'blankslate_gtm_fragment_anchor', 4 );

/**
 * Чи це запит wc-ajax=add_to_cart (каталог, картка без перезавантаження).
 */
function blankslate_gtm_is_wc_ajax_add_to_cart_request() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['wc-ajax'] ) && 'add_to_cart' === sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) );
}

/**
 * Чи це кастомне оновлення кількості міні-кошика / сайд-кошика.
 */
function blankslate_gtm_is_wc_ajax_blankslate_qty_request() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['wc-ajax'] ) && 'blankslate_update_cart_qty' === sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) );
}

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function &blankslate_gtm_request_payload_buckets() {
	static $buckets = array(
		'wc_ajax_atc'   => array(),
		'blankslate_qty' => array(),
	);
	return $buckets;
}

/**
 * @param array<string, mixed> $payload
 */
function blankslate_gtm_wc_ajax_add_to_cart_push( array $payload ) {
	$buckets = &blankslate_gtm_request_payload_buckets();
	$buckets['wc_ajax_atc'][] = $payload;
}

/**
 * @return array<int, array<string, mixed>>
 */
function blankslate_gtm_wc_ajax_add_to_cart_pull() {
	$buckets = &blankslate_gtm_request_payload_buckets();
	$out     = $buckets['wc_ajax_atc'];
	$buckets['wc_ajax_atc'] = array();
	return $out;
}

/**
 * @param array<string, mixed> $payload
 */
function blankslate_gtm_blankslate_qty_push( array $payload ) {
	$buckets = &blankslate_gtm_request_payload_buckets();
	$buckets['blankslate_qty'][] = $payload;
}

/**
 * @return array<int, array<string, mixed>>
 */
function blankslate_gtm_blankslate_qty_pull() {
	$buckets = &blankslate_gtm_request_payload_buckets();
	$out     = $buckets['blankslate_qty'];
	$buckets['blankslate_qty'] = array();
	return $out;
}

/**
 * @param array<string, mixed> $payload
 */
function blankslate_gtm_session_append_add_to_cart( array $payload ) {
	if ( ! WC()->session ) {
		return;
	}
	$q = WC()->session->get( 'blankslate_gtm_atc', array() );
	if ( ! is_array( $q ) ) {
		$q = array();
	}
	$q[] = $payload;
	WC()->session->set( 'blankslate_gtm_atc', $q );
}

/**
 * @return array<int, array<string, mixed>>
 */
function blankslate_gtm_session_take_add_to_cart() {
	if ( ! WC()->session ) {
		return array();
	}
	$q = WC()->session->get( 'blankslate_gtm_atc', array() );
	WC()->session->set( 'blankslate_gtm_atc', array() );
	return is_array( $q ) ? $q : array();
}

/**
 * GTM add_to_cart: черга для наступного get_refreshed_fragments (окремий HTTP-запит після wc-ajax=add_to_cart).
 *
 * @param array<string, mixed> $payload
 */
function blankslate_gtm_stash_for_next_fragment_request( array $payload ) {
	if ( ! WC()->session ) {
		return;
	}
	$q = WC()->session->get( 'blankslate_gtm_next_frag', array() );
	if ( ! is_array( $q ) ) {
		$q = array();
	}
	$q[] = $payload;
	WC()->session->set( 'blankslate_gtm_next_frag', $q );
}

/**
 * @return array<int, array<string, mixed>>
 */
function blankslate_gtm_take_next_fragment_request_queue() {
	if ( ! WC()->session ) {
		return array();
	}
	$q = WC()->session->get( 'blankslate_gtm_next_frag', array() );
	WC()->session->set( 'blankslate_gtm_next_frag', array() );
	return is_array( $q ) ? $q : array();
}

/**
 * @param array<string, mixed> $payload
 */
function blankslate_gtm_route_add_to_cart_payload( array $payload ) {
	if ( blankslate_gtm_is_wc_ajax_add_to_cart_request() ) {
		/*
		 * Якщо увімкнено «перейти в кошик після додавання», WC add-to-cart.js робить
		 * window.location і не викликає added_to_cart — фрагмент з dataLayer не потрапляє в JS.
		 * Тоді віддаємо подію через сесію на наступному завантаженні (сторінка кошика).
		 */
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			blankslate_gtm_session_append_add_to_cart( $payload );
			return;
		}
		blankslate_gtm_wc_ajax_add_to_cart_push( $payload );
		blankslate_gtm_stash_for_next_fragment_request( $payload );
		return;
	}
	if ( blankslate_gtm_is_wc_ajax_blankslate_qty_request() ) {
		blankslate_gtm_blankslate_qty_push( $payload );
		return;
	}
	blankslate_gtm_session_append_add_to_cart( $payload );
}

/**
 * Повний об'єкт для dataLayer (add_to_cart), у форматі з ТЗ.
 *
 * @param string $context 'quantity_increase'|'add_new_line' — для фільтра.
 */
function blankslate_gtm_add_to_cart_event_payload( WC_Product $product, $qty_delta, $context = 'quantity_increase' ) {
	$qty_delta = (int) $qty_delta;
	if ( $qty_delta <= 0 ) {
		return null;
	}

	$price    = round( (float) wc_get_price_to_display( $product ), 2 );
	$value    = round( $price * $qty_delta, 2 );
	$currency = blankslate_gtm_store_currency();

	$cat_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
	if ( ! $cat_product instanceof WC_Product ) {
		$cat_product = $product;
	}
	$item_category = blankslate_gtm_view_item_category_slug( $cat_product );

	$item_name = blankslate_product_title_plain_string( get_post_field( 'post_title', $product->get_id(), 'raw' ) );
	if ( $item_name === '' ) {
		$item_name = blankslate_product_title_plain_string( $product->get_name() );
	}

	$payload = array(
		'event'     => 'add_to_cart',
		'ecommerce' => array(
			'currency' => $currency,
			'value'    => $value,
			'items'    => array(
				array(
					'item_id'       => (string) $product->get_id(),
					'item_name'     => $item_name,
					'item_category' => $item_category,
					'price'         => $price,
					'quantity'      => $qty_delta,
				),
			),
		),
	);

	$payload = apply_filters( 'blankslate_gtm_add_to_cart_payload', $payload, $product, $qty_delta, $context );
	if ( ! is_array( $payload ) || empty( $payload['event'] ) || empty( $payload['ecommerce'] ) ) {
		return null;
	}

	return $payload;
}

/**
 * Збільшення кількості в кошику (+, ручний ввід): дельта = нове − старе.
 */
function blankslate_gtm_on_cart_line_quantity_update( $cart_item_key, $quantity, $old_quantity, $cart ) {
	if ( ! $cart instanceof WC_Cart ) {
		return;
	}
	$delta = (int) $quantity - (int) $old_quantity;
	if ( $delta <= 0 ) {
		return;
	}
	$item = $cart->get_cart_item( $cart_item_key );
	if ( ! $item || empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
		return;
	}
	/** @var WC_Product $product */
	$product = $item['data'];
	if ( ! apply_filters( 'blankslate_gtm_enable_add_to_cart', true, $product, $delta, 'quantity_increase' ) ) {
		return;
	}
	$payload = blankslate_gtm_add_to_cart_event_payload( $product, $delta, 'quantity_increase' );
	if ( ! is_array( $payload ) ) {
		return;
	}
	blankslate_gtm_route_add_to_cart_payload( $payload );
}
add_action( 'woocommerce_after_cart_item_quantity_update', 'blankslate_gtm_on_cart_line_quantity_update', 10, 4 );

/**
 * Новий рядок кошика: без виклику set_quantity, тільки цей хук. Якщо товар уже був у кошику — спрацює лише quantity_update (щоб не дублювати).
 */
function blankslate_gtm_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	unset( $product_id, $variation_id, $variation, $cart_item_data );
	if ( ! WC()->cart ) {
		return;
	}
	$item = WC()->cart->get_cart_item( $cart_item_key );
	if ( ! $item || empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
		return;
	}
	$qty_added = (int) $quantity;
	if ( (int) $item['quantity'] !== $qty_added ) {
		return;
	}
	/** @var WC_Product $product */
	$product = $item['data'];
	if ( ! apply_filters( 'blankslate_gtm_enable_add_to_cart', true, $product, $qty_added, 'add_new_line' ) ) {
		return;
	}
	$payload = blankslate_gtm_add_to_cart_event_payload( $product, $qty_added, 'add_new_line' );
	if ( ! is_array( $payload ) ) {
		return;
	}
	blankslate_gtm_route_add_to_cart_payload( $payload );
}
add_action( 'woocommerce_add_to_cart', 'blankslate_gtm_on_add_to_cart', 10, 6 );

/**
 * Прокидуємо події add_to_cart у відповідь get_refreshed_fragments після WC AJAX add_to_cart.
 *
 * @param array<string, string> $fragments
 * @return array<string, string>
 */
function blankslate_gtm_inject_add_to_cart_fragment( $fragments ) {
	$queue = blankslate_gtm_wc_ajax_add_to_cart_pull();
	if ( empty( $queue ) ) {
		$queue = blankslate_gtm_take_next_fragment_request_queue();
	}
	if ( empty( $queue ) ) {
		return $fragments;
	}
	$json  = wp_json_encode( $queue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$html  = '<span id="blankslate-gtm-fragment-anchor" class="blankslate-gtm-fragment-anchor" aria-hidden="true" data-blankslate-gtm-add="' . esc_attr( $json ) . '"></span>';
	$fragments['#blankslate-gtm-fragment-anchor'] = $html;
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'blankslate_gtm_inject_add_to_cart_fragment', 50 );

/**
 * Події з сесії (оновлення кошика формою, додавання з редіректом тощо).
 */
function blankslate_gtm_footer_flush_session_add_to_cart() {
	if ( is_admin() ) {
		return;
	}
	$queue = blankslate_gtm_session_take_add_to_cart();
	if ( empty( $queue ) ) {
		return;
	}

	$to_emit = array();
	foreach ( $queue as $payload ) {
		if ( is_array( $payload ) && blankslate_gtm_should_emit_payload( $payload ) ) {
			$to_emit[] = $payload;
		}
	}
	if ( empty( $to_emit ) ) {
		return;
	}
	?>
<script>
window.dataLayer = window.dataLayer || [];
(function(blankslateGtmAtc){
	if (!blankslateGtmAtc || !blankslateGtmAtc.length) return;
	blankslateGtmAtc.forEach(function(p){
		if (!p || !p.event) return;
		dataLayer.push({ ecommerce: null });
		dataLayer.push(p);
	});
})(<?php echo wp_json_encode( $to_emit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>);
</script>
	<?php
}
add_action( 'wp_footer', 'blankslate_gtm_footer_flush_session_add_to_cart', 16 );

/**
 * Ціна одиниці товару в кошику для GTM begin_checkout.
 * Бере реальну line_total після знижок; податок додається лише якщо ціни у кошику показуються з податком.
 *
 * @param array<string, mixed> $cart_item
 */
function blankslate_gtm_cart_item_unit_price( array $cart_item ) {
	$quantity = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
	if ( $quantity <= 0 ) {
		return 0.0;
	}

	$line_total = isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0;
	$line_tax   = isset( $cart_item['line_tax'] ) ? (float) $cart_item['line_tax'] : 0.0;
	$line_value = $line_total;

	if ( WC()->cart && WC()->cart->display_prices_including_tax() ) {
		$line_value += $line_tax;
	}

	return round( $line_value / $quantity, 2 );
}

/**
 * Один item для GTM begin_checkout із поточного кошика.
 *
 * @param array<string, mixed> $cart_item
 * @return array<string, mixed>|null
 */
function blankslate_gtm_begin_checkout_cart_item_payload( array $cart_item ) {
	if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
		return null;
	}

	/** @var WC_Product $product */
	$product  = $cart_item['data'];
	$quantity = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
	if ( $quantity <= 0 ) {
		return null;
	}

	$cat_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
	if ( ! $cat_product instanceof WC_Product ) {
		$cat_product = $product;
	}

	$item_name = blankslate_product_title_plain_string( get_post_field( 'post_title', $product->get_id(), 'raw' ) );
	if ( '' === $item_name ) {
		$item_name = blankslate_product_title_plain_string( $product->get_name() );
	}

	return array(
		'item_id'       => (string) $product->get_id(),
		'item_name'     => $item_name,
		'item_category' => blankslate_gtm_view_item_category_slug( $cat_product ),
		'price'         => blankslate_gtm_cart_item_unit_price( $cart_item ),
		'quantity'      => $quantity,
	);
}

/**
 * Повний payload для begin_checkout на сторінці оформлення.
 *
 * @return array<string, mixed>|null
 */
function blankslate_gtm_begin_checkout_payload() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return null;
	}

	$items = array();
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$item_payload = blankslate_gtm_begin_checkout_cart_item_payload( $cart_item );
		if ( is_array( $item_payload ) ) {
			$items[] = $item_payload;
		}
	}

	if ( empty( $items ) ) {
		return null;
	}

	$payload = array(
		'event'     => 'begin_checkout',
		'ecommerce' => array(
			'currency' => blankslate_gtm_store_currency(),
			'value'    => round( (float) WC()->cart->get_total( 'edit' ), 2 ),
			'items'    => $items,
		),
	);

	$payload = apply_filters( 'blankslate_gtm_begin_checkout_payload', $payload, WC()->cart );
	if ( ! is_array( $payload ) || empty( $payload['event'] ) || empty( $payload['ecommerce'] ) ) {
		return null;
	}

	return $payload;
}

/**
 * dataLayer: begin_checkout на завантаженні checkout-сторінки.
 */
function blankslate_gtm_data_layer_begin_checkout() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_admin() ) {
		return;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	$payload = blankslate_gtm_begin_checkout_payload();
	if ( ! is_array( $payload ) ) {
		return;
	}
	if ( ! apply_filters( 'blankslate_gtm_enable_begin_checkout', true, $payload ) ) {
		return;
	}
	if ( blankslate_gtm_begin_checkout_already_sent_for_cart() ) {
		return;
	}
	if ( ! blankslate_gtm_emit_datalayer_payload( $payload ) ) {
		return;
	}
	blankslate_gtm_mark_begin_checkout_sent_for_cart();
}
add_action( 'wp_footer', 'blankslate_gtm_data_layer_begin_checkout', 17 );

/**
 * Поточне замовлення на thank-you page.
 *
 * @return WC_Order|null
 */
function blankslate_gtm_order_received_page_order() {
	if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
		return null;
	}

	$order_id = absint( get_query_var( 'order-received' ) );
	if ( ! $order_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		if ( $order_key ) {
			$order_id = wc_get_order_id_by_order_key( $order_key );
		}
	}
	if ( ! $order_id ) {
		return null;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return null;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( $order_key && $order->get_order_key() !== $order_key ) {
		return null;
	}

	return $order;
}

/**
 * Чи purchase уже був відправлений для цього замовлення.
 */
function blankslate_gtm_purchase_already_sent( WC_Order $order ) {
	return 'yes' === $order->get_meta( '_blankslate_gtm_purchase_sent', true );
}

/**
 * Помічає purchase як уже відправлений.
 */
function blankslate_gtm_mark_purchase_sent( WC_Order $order ) {
	$order->update_meta_data( '_blankslate_gtm_purchase_sent', 'yes' );
	$order->update_meta_data( '_blankslate_gtm_purchase_sent_at', time() );
	$order->save();
}

/**
 * Один item для GTM purchase із WC_Order_Item_Product.
 *
 * @return array<string, mixed>|null
 */
function blankslate_gtm_purchase_order_item_payload( WC_Order $order, WC_Order_Item_Product $item ) {
	$quantity = (int) $item->get_quantity();
	if ( $quantity <= 0 ) {
		return null;
	}

	$product = $item->get_product();

	$item_id = (string) ( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() );
	$item_name = blankslate_product_title_plain_string( $item->get_name() );

	$item_category = '';
	if ( $product instanceof WC_Product ) {
		$cat_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
		if ( ! $cat_product instanceof WC_Product ) {
			$cat_product = $product;
		}
		$item_category = blankslate_gtm_view_item_category_slug( $cat_product );

		$product_title = blankslate_product_title_plain_string( get_post_field( 'post_title', $product->get_id(), 'raw' ) );
		if ( '' !== $product_title ) {
			$item_name = $product_title;
		}
	}

	$price = round( (float) $order->get_item_total( $item, $order->get_prices_include_tax(), false ), 2 );

	return array(
		'item_id'       => $item_id,
		'item_name'     => $item_name,
		'item_category' => $item_category,
		'price'         => $price,
		'quantity'      => $quantity,
	);
}

/**
 * Повний payload для purchase на thank-you page.
 *
 * @return array<string, mixed>|null
 */
function blankslate_gtm_purchase_payload( WC_Order $order ) {
	$items = array();

	foreach ( $order->get_items( 'line_item' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$item_payload = blankslate_gtm_purchase_order_item_payload( $order, $item );
		if ( is_array( $item_payload ) ) {
			$items[] = $item_payload;
		}
	}

	if ( empty( $items ) ) {
		return null;
	}

	$payload = array(
		'event'     => 'purchase',
		'ecommerce' => array(
			'transaction_id' => (string) $order->get_order_number(),
			'value'          => round( (float) $order->get_total(), 2 ),
			'currency'       => $order->get_currency() ? $order->get_currency() : blankslate_gtm_store_currency(),
			'items'          => $items,
		),
	);

	$payload = apply_filters( 'blankslate_gtm_purchase_payload', $payload, $order );
	if ( ! is_array( $payload ) || empty( $payload['event'] ) || empty( $payload['ecommerce'] ) ) {
		return null;
	}

	return $payload;
}

/**
 * dataLayer: purchase на thank-you page, тільки один раз на замовлення.
 */
function blankslate_gtm_data_layer_purchase() {
	if ( is_admin() ) {
		return;
	}

	$order = blankslate_gtm_order_received_page_order();
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! $order->is_paid() || $order->has_status( 'failed' ) ) {
		return;
	}
	if ( blankslate_gtm_purchase_already_sent( $order ) ) {
		return;
	}

	$payload = blankslate_gtm_purchase_payload( $order );
	if ( ! is_array( $payload ) ) {
		return;
	}
	if ( ! apply_filters( 'blankslate_gtm_enable_purchase', true, $order, $payload ) ) {
		return;
	}

	if ( ! blankslate_gtm_emit_datalayer_payload( $payload ) ) {
		return;
	}

	blankslate_gtm_mark_purchase_sent( $order );

	if ( WC()->session ) {
		WC()->session->set( 'blankslate_gtm_begin_checkout_cart_hash', '' );
	}
}
add_action( 'wp_footer', 'blankslate_gtm_data_layer_purchase', 18 );

/*
 * Elementor form GTM (send_form_personal_advice / send_form_send_message):
 * includes/gtm-elementor-forms.php — do not duplicate blankslate_gtm_elementor_form_* here.
 */

/* STRIPE BUTTONS POSITION */
add_action('init', function () {
    remove_action('woocommerce_checkout_before_customer_details', [ \WC_Stripe_Express_Checkout_Element::instance(), 'display_express_checkout_button_html' ], 1);
});
add_action('woocommerce_review_order_before_payment', function () {
    if (class_exists('\WC_Stripe_Express_Checkout_Element')) {
        // Ensure button is visible by removing the inline style
        \WC_Stripe_Express_Checkout_Element::instance()->display_express_checkout_button_html();
    }
}, 10); // Adjust priority as needed