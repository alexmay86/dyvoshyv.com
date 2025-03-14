<?php
add_action( 'after_setup_theme', 'blankslate_setup' );
function blankslate_setup() {
load_theme_textdomain( 'blankslate', get_template_directory() . '/languages' );
add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'responsive-embeds' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'html5', array( 'search-form', 'navigation-widgets' ) );
add_theme_support( 'appearance-tools' );
add_theme_support( 'woocommerce' );
global $content_width;
if ( !isset( $content_width ) ) { $content_width = 1920; }
register_nav_menus( array( 'main-menu' => esc_html__( 'Main Menu', 'blankslate' ) ) );
}
add_action( 'admin_notices', 'blankslate_notice' );
function blankslate_notice() {
$user_id = get_current_user_id();
$admin_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$param = ( count( $_GET ) ) ? '&' : '?';
if ( !get_user_meta( $user_id, 'blankslate_notice_dismissed_11' ) && current_user_can( 'manage_options' ) )
echo '<div class="notice notice-info"><p><a href="' . esc_url( $admin_url ), esc_html( $param ) . 'dismiss" class="alignright" style="text-decoration:none"><big>' . esc_html__( 'Ⓧ', 'blankslate' ) . '</big></a>' . wp_kses_post( __( '<big><strong>🏆 Thank you for using BlankSlate!</strong></big>', 'blankslate' ) ) . '<p>' . esc_html__( 'Powering over 10k websites! Buy me a sandwich! 🥪', 'blankslate' ) . '</p><a href="https://github.com/bhadaway/blankslate/issues/57" class="button-primary" target="_blank"><strong>' . esc_html__( 'How do you use BlankSlate?', 'blankslate' ) . '</strong></a> <a href="https://opencollective.com/blankslate" class="button-primary" style="background-color:green;border-color:green" target="_blank"><strong>' . esc_html__( 'Donate', 'blankslate' ) . '</strong></a> <a href="https://wordpress.org/support/theme/blankslate/reviews/#new-post" class="button-primary" style="background-color:purple;border-color:purple" target="_blank"><strong>' . esc_html__( 'Review', 'blankslate' ) . '</strong></a> <a href="https://github.com/bhadaway/blankslate/issues" class="button-primary" style="background-color:orange;border-color:orange" target="_blank"><strong>' . esc_html__( 'Support', 'blankslate' ) . '</strong></a></p></div>';
}
add_action( 'admin_init', 'blankslate_notice_dismissed' );
function blankslate_notice_dismissed() {
$user_id = get_current_user_id();
if ( isset( $_GET['dismiss'] ) )
add_user_meta( $user_id, 'blankslate_notice_dismissed_11', 'true', true );
}
add_action( 'wp_enqueue_scripts', 'blankslate_enqueue' );
function blankslate_enqueue() {
wp_enqueue_style( 'blankslate-style', get_stylesheet_uri(), array(), filemtime(get_stylesheet_directory() . '/style.css'), 'all' );
wp_enqueue_script( 'jquery' );
}
add_action( 'wp_footer', 'blankslate_footer' );
function blankslate_footer() {
?>
<script>
jQuery(document).ready(function($) {
var deviceAgent = navigator.userAgent.toLowerCase();
if (deviceAgent.match(/(iphone|ipod|ipad)/)) {
$("html").addClass("ios");
$("html").addClass("mobile");
}
if (deviceAgent.match(/(Android)/)) {
$("html").addClass("android");
$("html").addClass("mobile");
}
if (navigator.userAgent.search("MSIE") >= 0) {
$("html").addClass("ie");
}
else if (navigator.userAgent.search("Chrome") >= 0) {
$("html").addClass("chrome");
}
else if (navigator.userAgent.search("Firefox") >= 0) {
$("html").addClass("firefox");
}
else if (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) {
$("html").addClass("safari");
}
else if (navigator.userAgent.search("Opera") >= 0) {
$("html").addClass("opera");
}
});
</script>
<?php
}
add_filter( 'document_title_separator', 'blankslate_document_title_separator' );
function blankslate_document_title_separator( $sep ) {
$sep = esc_html( '|' );
return $sep;
}
add_filter( 'the_title', 'blankslate_title' );
function blankslate_title( $title ) {
if ( $title == '' ) {
return esc_html( '...' );
} else {
return wp_kses_post( $title );
}
}
function blankslate_schema_type() {
$schema = 'https://schema.org/';
if ( is_single() ) {
$type = "Article";
} elseif ( is_author() ) {
$type = 'ProfilePage';
} elseif ( is_search() ) {
$type = 'SearchResultsPage';
} else {
$type = 'WebPage';
}
echo 'itemscope itemtype="' . esc_url( $schema ) . esc_attr( $type ) . '"';
}
add_filter( 'nav_menu_link_attributes', 'blankslate_schema_url', 10 );
function blankslate_schema_url( $atts ) {
$atts['itemprop'] = 'url';
return $atts;
}
if ( !function_exists( 'blankslate_wp_body_open' ) ) {
function blankslate_wp_body_open() {
do_action( 'wp_body_open' );
}
}
//add_action( 'wp_body_open', 'blankslate_skip_link', 5 );
function blankslate_skip_link() {
echo '<a href="#content" class="skip-link screen-reader-text">' . esc_html__( 'Skip to the content', 'blankslate' ) . '</a>';
}
add_filter( 'the_content_more_link', 'blankslate_read_more_link' );
function blankslate_read_more_link() {
if ( !is_admin() ) {
return ' <a href="' . esc_url( get_permalink() ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
}
}
add_filter( 'excerpt_more', 'blankslate_excerpt_read_more_link' );
function blankslate_excerpt_read_more_link( $more ) {
if ( !is_admin() ) {
global $post;
return ' <a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
}
}
add_filter( 'big_image_size_threshold', '__return_false' );
add_filter( 'intermediate_image_sizes_advanced', 'blankslate_image_insert_override' );
function blankslate_image_insert_override( $sizes ) {
unset( $sizes['medium_large'] );
unset( $sizes['1536x1536'] );
unset( $sizes['2048x2048'] );
return $sizes;
}
add_action( 'widgets_init', 'blankslate_widgets_init' );
function blankslate_widgets_init() {
register_sidebar( array(
'name' => esc_html__( 'Sidebar Widget Area', 'blankslate' ),
'id' => 'primary-widget-area',
'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
'after_widget' => '</li>',
'before_title' => '<h3 class="widget-title">',
'after_title' => '</h3>',
) );
}
add_action( 'wp_head', 'blankslate_pingback_header' );
function blankslate_pingback_header() {
if ( is_singular() && pings_open() ) {
printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
}
}
add_action( 'comment_form_before', 'blankslate_enqueue_comment_reply_script' );
function blankslate_enqueue_comment_reply_script() {
if ( get_option( 'thread_comments' ) ) {
wp_enqueue_script( 'comment-reply' );
}
}
function blankslate_custom_pings( $comment ) {
?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>"><?php echo esc_url( comment_author_link() ); ?></li>
<?php
}
add_filter( 'get_comments_number', 'blankslate_comment_count', 0 );
function blankslate_comment_count( $count ) {
if ( !is_admin() ) {
global $id;
$get_comments = get_comments( 'status=approve&post_id=' . $id );
$comments_by_type = separate_comments( $get_comments );
return count( $comments_by_type['comment'] );
} else {
return $count;
}
}
add_filter('woocommerce_breadcrumb_defaults', 'wcc_change_breadcrumb_delimiter', 20);
function wcc_change_breadcrumb_delimiter($defaults) {
    // Змініть роздільник breadcrumbs з '/' на '>'
    $defaults['delimiter'] = ' > ';
    return $defaults;
}

function form_checkboxes_validation(){ ?> 
    <script type="text/javascript"> 
        (function($){ $("#send-request").click(function() { 
            if(! $('input[name="form_fields[suite][]"]').is(':checked')) { 
                alert("Please select at least one suite!"); return false; 
                } 
            }); 
        })(jQuery); 
    </script>
<?php } 
add_action('wp_footer', 'form_checkboxes_validation');

function custom_woocommerce_cart_totals() {
    ob_start();

    // Показувати підсумок (і кнопку) лише якщо кошик НЕ порожній
    if ( ! WC()->cart->is_empty() ) {
        // Умова, як у вас було: тільки на сторінці checkout або cart
        if ( is_checkout() || is_cart() ) {
            woocommerce_cart_totals();
        }
    }

    return ob_get_clean();
}
add_shortcode('custom_cart_totals', 'custom_woocommerce_cart_totals');


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

function add_noindex_for_elementor_library() {
    if ( isset($_GET['elementor_library']) ) {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }
}
add_action('wp_head', 'add_noindex_for_elementor_library', 1);

add_filter('woocommerce_get_breadcrumb', 'my_multilang_breadcrumb_replace', 9999, 2);
function my_multilang_breadcrumb_replace($crumbs, $breadcrumb_obj){
    foreach( $crumbs as $index => $crumb ) {
        // $crumb[0] = Текст у крихті
        // $crumb[1] = Посилання (URL) у крихті
        $title = $crumb[0];
        $link  = $crumb[1];

        // Перевіряємо, чи це крихта з "product-category"
        // тобто посилання на архів WooCommerce-категорії
        if ( str_contains($link, 'product-category') ) {

            // Витягаємо <cat-slug> за допомогою регулярки
            // знаходимо все, що після ".../product-category/" до наступного слеша
            if ( preg_match('~product-category/([^/]+)/?$~', $link, $matches) ) {
                $cat_slug = $matches[1];  // Наприклад: "velvet-roses" або "oksamytovi-troiandy"
            } else {
                // Якщо чомусь не змогли знайти slug, пропускаємо
                continue;
            }

            // Перевіряємо, чи є в URL "/eng/" — вважаємо, що це англійська
            if ( str_contains($link, '/eng/') ) {
                // АНГЛІЙСЬКА: ведемо на /eng/collections/<cat-slug>
                $new_url = home_url( "/eng/collections/$cat_slug/" );
            } else {
                // УКРАЇНСЬКА (або інша) → /kolektsii/<cat-slug>
                $new_url = home_url( "/kolektsii/$cat_slug/" );
            }

            // Підміняємо сам лінк у крихті
            $crumbs[$index][1] = $new_url;

            // Якщо хочете підмінити й текст (наприклад, на "TEST Cat Link"), зробіть так:
            // $crumbs[$index][0] = 'TEST Cat Link';
        }
    }

    return $crumbs;
}

remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

/* REMOVE  META TAGS */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head','adjacent_posts_rel_link_wp_head');
remove_action('wp_head','feed_links_extra', 3);
remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');

// REMOVE EMOJI ICONS
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

function disable_dashicons_frontend() {
    // Only dequeue on the frontend, not the admin panel
    if ( ! is_admin() ) {
        if(! is_admin_bar_showing()) wp_deregister_style( 'dashicons' );
        wp_deregister_style( 'wp-block-library' );
    }
}
add_action( 'wp_enqueue_scripts', 'disable_dashicons_frontend' );

/* DISABLE LIQPAY FOR EURO CURRENCY */
add_filter('woocommerce_available_payment_gateways', function ($available_gateways) {
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
});

/*  PAYMENT METHODS ICONS */
add_filter('woocommerce_gateway_icon', 'custom_woocommerce_payment_gateway_icons', 10, 2);
function custom_woocommerce_payment_gateway_icons($icon, $gateway_id) {
    if ($gateway_id === 'ppcp-gateway') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/paypal.svg" alt="PayPal" width="65" height="40">';
    }

    if ($gateway_id === 'liqpay-webplus') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/liqpay.svg" alt="LiqPay" width="65" height="42" style="">';
        $icon .= '<span style="display:inline-block;height:42px;border-left:1px solid #D5D8DC;margin-left:16px;margin-right:16px;vertical-align:top"></span>';
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

/* DISABLE MEEST UPDATE */
add_filter('site_transient_update_plugins', 'remove_update_notification');
function remove_update_notification($value) {
    unset($value->response[ "meest-for-woocommerce/meest_shipping.php" ]);
    unset($value->response[ "wc-ukr-shipping/wc-ukr-shipping.php" ]);
    return $value;
}

/* COUNTRIES FIELD SYNCHRONIZATION */
function synchronize_country() {
    if(is_checkout()) { ?>
        <script>
        jQuery(document).ready(function($) {
            $('#billing_meest_country_id').on('change', function() {
                const country = $(this).find('option:selected').text();
                if($('#billing_country').length) {
                    var shipping_country = $('#billing_country').find('option').filter(function() {
                        return $(this).text() === country
                    }).val();
                    console.log(shipping_country);
                    $('#billing_country').val(shipping_country).change();
                }
            });
        });
        </script>
    <?php }
}
add_action('wp_footer', 'synchronize_country');
