/* Shared cookie helpers (popups + preferences form). Must load before any code that calls them. */
function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        '(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'
    ));
    return matches ? decodeURIComponent(matches[1]) : '';
}
function setCookie(name, value, options) {
    options = options || {};
    var expires = options.expires;
    if (typeof expires === 'number' && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires * 24 * 60 * 60 * 1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }
    value = encodeURIComponent(value);
    var updatedCookie = name + '=' + value;
    for (var propName in options) {
        updatedCookie += '; ' + propName;
        var propValue = options[propName];
        if (propValue !== true) {
            updatedCookie += '=' + propValue;
        }
    }
    document.cookie = updatedCookie;
}

function blankslateIsMiniCartQuantityContext(el) {
    return !!(
        el &&
        el.closest &&
        (el.closest('#custom-side-cart') ||
            el.closest('.elementor-menu-cart__main') ||
            el.closest('.elementor-widget-woocommerce-menu-cart'))
    );
}

function blankslateRefreshSideCartQtyButtons() {
    blankslateEnsurePlusMinusButtons(document);
    var sideCart = document.getElementById('custom-side-cart');
    if (sideCart) {
        blankslateEnsurePlusMinusButtons(sideCart);
    }
}

function blankslateInitSideCartQtyWatchers() {
    var cart = document.getElementById('custom-side-cart');
    if (!cart || cart.dataset.blankslateQtyWatch === '1') {
        return;
    }
    cart.dataset.blankslateQtyWatch = '1';

    var debounceTimer;
    var scheduleRefresh = function () {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(blankslateRefreshSideCartQtyButtons, 50);
    };

    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(scheduleRefresh).observe(cart, {
            childList: true,
            subtree: true
        });
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.type === 'attributes' && m.attributeName === 'class' && cart.classList.contains('open')) {
                    blankslateRefreshSideCartQtyButtons();
                    window.setTimeout(blankslateRefreshSideCartQtyButtons, 0);
                }
            });
        }).observe(cart, { attributes: true, attributeFilter: ['class'] });
    }
}

function blankslateOpenSideCart() {
    var cart = document.getElementById('custom-side-cart');
    var overlay = document.getElementById('overlay');
    if (cart) {
        cart.classList.add('open');
    }
    if (overlay) {
        overlay.classList.add('visible');
    }
    blankslateRefreshSideCartQtyButtons();
    window.setTimeout(blankslateRefreshSideCartQtyButtons, 0);
}

var blankslateGtmRecentEventKeys = {};

function blankslateGtmBuildEventKey(payload) {
    if (!payload || !payload.event) {
        return '';
    }
    if (payload.event === 'purchase' && payload.ecommerce && payload.ecommerce.transaction_id) {
        return 'purchase|' + payload.ecommerce.transaction_id;
    }
    if (payload.event === 'begin_checkout' && payload.ecommerce) {
        var items = payload.ecommerce.items || [];
        var itemSig = items
            .map(function (item) {
                return (item.item_id || '') + 'x' + (item.quantity || '');
            })
            .join(',');
        return 'begin_checkout|' + itemSig + '|' + (payload.ecommerce.value || '');
    }
    var item = payload.ecommerce && payload.ecommerce.items && payload.ecommerce.items[0];
    if (!item) {
        return payload.event;
    }
    return [
        payload.event,
        item.item_id || '',
        item.quantity || '',
        payload.ecommerce.value || '',
        item.item_name || ''
    ].join('|');
}

function blankslateGtmPushAddToCartList(list) {
    if (!list) {
        return;
    }
    var items = Array.isArray(list) ? list : [list];
    if (!items.length) {
        return;
    }
    var now = Date.now();
    var ttl = 3000;
    window.dataLayer = window.dataLayer || [];
    items.forEach(function (p) {
        if (!p || !p.event) {
            return;
        }
        var key = blankslateGtmBuildEventKey(p);
        if (key && blankslateGtmRecentEventKeys[key] && now - blankslateGtmRecentEventKeys[key] < ttl) {
            return;
        }
        if (key) {
            blankslateGtmRecentEventKeys[key] = now;
        }
        dataLayer.push({ ecommerce: null });
        dataLayer.push(p);
    });
    Object.keys(blankslateGtmRecentEventKeys).forEach(function (k) {
        if (now - blankslateGtmRecentEventKeys[k] > ttl) {
            delete blankslateGtmRecentEventKeys[k];
        }
    });
}

function blankslateGtmPushFormEvent(eventName) {
    if (!eventName) {
        return;
    }
    var now = Date.now();
    var ttl = 3000;
    var key = 'form|' + eventName;
    window.dataLayer = window.dataLayer || [];
    if (blankslateGtmRecentEventKeys[key] && now - blankslateGtmRecentEventKeys[key] < ttl) {
        return;
    }
    blankslateGtmRecentEventKeys[key] = now;
    dataLayer.push({ event: eventName });
}

var blankslateFormGtmDefaultMap = {
    'consultation-form': 'send_form_personal_advice',
    'contact-form': 'send_form_send_message'
};

function blankslateFormGtmIdMap() {
    if (typeof blankslateFormGtm !== 'undefined' && blankslateFormGtm.byCssId) {
        return blankslateFormGtm.byCssId;
    }
    return blankslateFormGtmDefaultMap;
}

/** GTM event from wrapper id (#consultation-form on Form widget block, not on <form>). */
function blankslateFormGtmEventFromElement(el) {
    var map = blankslateFormGtmIdMap();
    var node = el;
    while (node) {
        if (node.id && map[node.id]) {
            return map[node.id];
        }
        node = node.parentElement;
    }
    return '';
}

function blankslateFormGtmEventFromElementorAjax(resp) {
    if (!resp || !resp.success || !resp.data) {
        return '';
    }
    if (resp.data.data && resp.data.data.blankslate_gtm_event) {
        return resp.data.data.blankslate_gtm_event;
    }
    return '';
}

function blankslateFormGtmEventFromSubmitPayload(responseData) {
    if (!responseData || typeof responseData !== 'object') {
        return '';
    }
    if (responseData.blankslate_gtm_event) {
        return responseData.blankslate_gtm_event;
    }
    if (responseData.data && responseData.data.blankslate_gtm_event) {
        return responseData.data.blankslate_gtm_event;
    }
    return '';
}

function blankslateFindElementorFormByFormId(formId) {
    if (!formId) {
        return null;
    }
    var inputs = document.querySelectorAll('.elementor-form input[name="form_id"]');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].value === String(formId)) {
            return inputs[i].closest('.elementor-form');
        }
    }
    return null;
}

var blankslateLastElementorFormEl = null;

function blankslateIsElementorFormAjax(settings) {
    if (!settings || !settings.data) {
        return false;
    }
    if (typeof FormData !== 'undefined' && settings.data instanceof FormData) {
        return settings.data.get('action') === 'elementor_pro_forms_send_form';
    }
    return typeof settings.data === 'string' && settings.data.indexOf('action=elementor_pro_forms_send_form') !== -1;
}

/**
 * POST body for wc-ajax add_to_cart.
 * Variable: WC AJAX expects product_id = variation post ID (not parent + variation_id).
 *
 * @param {HTMLFormElement} form
 * @return {Array<{name: string, value: string}>}
 */
function blankslateBuildAddToCartPostData(form) {
    var $form = jQuery(form);
    var data = $form.serializeArray();

    if (!form.classList.contains('variations_form')) {
        var hasProductId = false;
        jQuery.each(data, function (_i, field) {
            if (field.name === 'product_id') {
                hasProductId = true;
            }
        });
        if (!hasProductId) {
            var atcVal = $form.find('[name="add-to-cart"]').val();
            if (atcVal) {
                data.push({ name: 'product_id', value: String(atcVal) });
            }
        }
        return data;
    }

    var variationId = parseInt($form.find('input[name="variation_id"]').val(), 10) || 0;
    if (!variationId) {
        return data;
    }

    data = jQuery.grep(data, function (field) {
        return (
            field.name !== 'add-to-cart' &&
            field.name !== 'product_id' &&
            field.name !== 'variation_id'
        );
    });
    data.push({ name: 'product_id', value: String(variationId) });

    return data;
}

function blankslateElementorFormGtmPushFromForm(formEl, responseData, ajaxResp) {
    var gtmEvent = blankslateFormGtmEventFromElementorAjax(ajaxResp);
    if (!gtmEvent) {
        gtmEvent = blankslateFormGtmEventFromSubmitPayload(responseData);
    }
    if (!gtmEvent && formEl) {
        gtmEvent = blankslateFormGtmEventFromElement(formEl);
    }
    if (gtmEvent) {
        blankslateGtmPushFormEvent(gtmEvent);
    }
}

/** Direct bind on #consultation-form / #contact-form wrappers (id on Form widget, not on <form>). */
function blankslateInitElementorFormGtm() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;
    var map = blankslateFormGtmIdMap();

    Object.keys(map).forEach(function (domId) {
        $('#' + domId).each(function () {
            var $root = $(this);
            var $forms = $root.is('.elementor-form') ? $root : $root.find('.elementor-form');
            var gtmEvent = map[domId];

            $forms.each(function () {
                $(this)
                    .off('submit_success.blankslateFormGtm')
                    .on('submit_success.blankslateFormGtm', function (_e, responseData) {
                        blankslateElementorFormGtmPushFromForm(this, responseData, null);
                    });
            });
        });
    });
}

function blankslateGtmConsumeWcFragmentAnchor(fragments) {
    if (!fragments || !fragments['#blankslate-gtm-fragment-anchor']) {
        return;
    }
    var html = fragments['#blankslate-gtm-fragment-anchor'];
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var el = wrap.querySelector('[data-blankslate-gtm-add]');
    if (!el) {
        return;
    }
    var raw = el.getAttribute('data-blankslate-gtm-add');
    if (!raw) {
        return;
    }
    try {
        blankslateGtmPushAddToCartList(JSON.parse(raw));
    } catch (err) {
        if (window.console && console.warn) {
            console.warn('Blankslate GTM add_to_cart:', err);
        }
    }
}

function blankslatePersistWcFragmentStorage(data) {
    if (!data || !data.fragments || typeof wc_cart_fragments_params === 'undefined') {
        return;
    }
    try {
        sessionStorage.setItem(
            wc_cart_fragments_params.fragment_name,
            JSON.stringify(data.fragments)
        );
        if (data.cart_hash) {
            sessionStorage.setItem(wc_cart_fragments_params.cart_hash_key, data.cart_hash);
        }
    } catch (err) {
        // sessionStorage unavailable
    }
}

function blankslateQtyWrapperHasVisibleButtons(wrapper) {
    if (!wrapper) {
        return false;
    }
    var minus = wrapper.querySelector(
        'button.minus, .woolentor-quantity-decrease, .woolentor-cart-product-quantity-btn.minus'
    );
    var plus = wrapper.querySelector(
        'button.plus, .woolentor-quantity-increase, .woolentor-cart-product-quantity-btn.plus'
    );
    if (!minus || !plus) {
        return false;
    }
    var r1 = minus.getBoundingClientRect();
    var r2 = plus.getBoundingClientRect();
    return r1.width > 0 && r1.height > 0 && r2.width > 0 && r2.height > 0;
}

function blankslateInsertPlusMinusIntoQtyContainer(quantityContainer) {
    if (!quantityContainer) {
        return;
    }
    if (quantityContainer.querySelector('.minus') && quantityContainer.querySelector('.plus')) {
        return;
    }

    var minusButton = document.createElement('button');
    minusButton.type = 'button';
    minusButton.classList.add('minus');
    minusButton.textContent = '-';

    var plusButton = document.createElement('button');
    plusButton.type = 'button';
    plusButton.classList.add('plus');
    plusButton.textContent = '+';

    quantityContainer.insertBefore(minusButton, quantityContainer.firstChild);
    quantityContainer.appendChild(plusButton);
}

function blankslateEnsureWoolentorQtyBlock(wlQty) {
    if (!wlQty || blankslateQtyWrapperHasVisibleButtons(wlQty)) {
        return;
    }

    var qtyDiv = wlQty.querySelector('.quantity');
    if (!qtyDiv) {
        var input = wlQty.querySelector('input.qty, input[name*="cart["][name*="][qty]"]');
        if (!input || input.type === 'hidden') {
            return;
        }
        qtyDiv = document.createElement('div');
        qtyDiv.className = 'quantity';
        input.parentNode.insertBefore(qtyDiv, input);
        qtyDiv.appendChild(input);
    }

    blankslateInsertPlusMinusIntoQtyContainer(qtyDiv);
}

function blankslateEnsurePlusMinusButtons(scope) {
    var root = scope || document;

    root.querySelectorAll('.quantity').forEach(function (quantityContainer) {
        var wrap = quantityContainer.closest(
            '.woolentor-product-quantity, .woolentor-cart-product-quantity'
        );
        if (wrap && blankslateQtyWrapperHasVisibleButtons(wrap)) {
            return;
        }
        blankslateInsertPlusMinusIntoQtyContainer(quantityContainer);
    });

    root.querySelectorAll('.woolentor-product-quantity, .woolentor-cart-product-quantity').forEach(
        function (wlQty) {
            if (blankslateQtyWrapperHasVisibleButtons(wlQty) || wlQty.querySelector('.quantity')) {
                return;
            }
            blankslateEnsureWoolentorQtyBlock(wlQty);
        }
    );
}

document.addEventListener('DOMContentLoaded', function () {
    if (sessionStorage.getItem('addedToCart')) {
        sessionStorage.removeItem('addedToCart');
        blankslateOpenSideCart();
    }

    blankslateInitSideCartQtyWatchers();
    blankslateRefreshSideCartQtyButtons();
    window.addEventListener('load', function () {
        blankslateInitSideCartQtyWatchers();
        blankslateRefreshSideCartQtyButtons();
    });

    document.querySelectorAll('.quantity button').forEach(function (button) {
        if (blankslateIsMiniCartQuantityContext(button)) {
            return;
        }
        button.addEventListener('click', function () {
            var input = button.parentElement.querySelector('input[type="number"]');
            if (!input) return;
            var currentValue = parseInt(input.value, 10);
            if (isNaN(currentValue)) currentValue = 1;
            if (button.classList.contains('plus')) {
                input.value = currentValue + 1;
            } else if (button.classList.contains('minus')) {
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            }
        });
    });
});

jQuery(function($) {

    $(document.body).on('added_to_cart', function (e, fragments, cart_hash, $button) {
        if ($button && $button.length) {
            blankslateOpenSideCart();
        }
        if (e.blankslateGtmHandled) {
            return;
        }
        blankslateGtmConsumeWcFragmentAnchor(fragments);
    });

    /* Elementor forms: success only — #consultation-form / #contact-form (CSS ID on Form widget). */
    blankslateInitElementorFormGtm();
    $(window).on('elementor/frontend/init', blankslateInitElementorFormGtm);

    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        elementorFrontend.hooks.addAction('frontend/element_ready/form.default', function ($scope) {
            var $widget = $scope.hasClass('elementor-widget-form') ? $scope : $scope.find('.elementor-widget-form').first();
            if (
                !$widget.length ||
                (!$widget.is('#consultation-form, #contact-form') &&
                    !$widget.closest('#consultation-form, #contact-form').length)
            ) {
                return;
            }
            blankslateInitElementorFormGtm();
        });
    }

    /*
     * Elementor: $form.trigger('submit_success', response.data) after successful AJAX.
     * response.data = { message, data: { blankslate_gtm_event } } — same as your Network JSON.
     */
    $(document).on('submit_success', function (_e, responseData) {
        var $form = $(_e.target);
        if (!$form.hasClass('elementor-form')) {
            $form = $form.closest('.elementor-form');
        }
        if (!$form.length) {
            return;
        }
        blankslateElementorFormGtmPushFromForm($form[0], responseData, null);
    });

    $(document).ajaxSend(function (_e, _xhr, settings) {
        blankslateLastElementorFormEl = null;
        if (!blankslateIsElementorFormAjax(settings) || !(settings.data instanceof FormData)) {
            return;
        }
        blankslateLastElementorFormEl = blankslateFindElementorFormByFormId(settings.data.get('form_id'));
    });

    $(document).ajaxSuccess(function (_event, xhr, settings) {
        if (!blankslateIsElementorFormAjax(settings)) {
            return;
        }
        var resp;
        try {
            resp = JSON.parse(xhr.responseText);
        } catch (parseErr) {
            return;
        }
        if (!resp || !resp.success) {
            return;
        }
        var formEl = blankslateLastElementorFormEl;
        if (!formEl && settings.data instanceof FormData) {
            formEl = blankslateFindElementorFormByFormId(settings.data.get('form_id'));
        }
        blankslateElementorFormGtmPushFromForm(formEl, null, resp);
    });

    /*
     * Mini-cart AJAX qty — must register on DOM ready (not window.onload), because the side
     * cart can open immediately after add-to-cart while the page is still loading handlers.
     */
    if (typeof blankslateCartAjax !== 'undefined' && blankslateCartAjax.wcAjaxUrl) {
        var miniCartRoot =
            '#custom-side-cart, .elementor-widget-woocommerce-menu-cart, .elementor-menu-cart__main';
        var miniCartQtySelector =
            '#custom-side-cart .quantity .plus, #custom-side-cart .quantity .minus, ' +
            '#custom-side-cart .woolentor-quantity-increase, #custom-side-cart .woolentor-quantity-decrease, ' +
            '#custom-side-cart .woolentor-cart-product-quantity-btn.plus, #custom-side-cart .woolentor-cart-product-quantity-btn.minus, ' +
            '.elementor-widget-woocommerce-menu-cart .quantity .plus, .elementor-widget-woocommerce-menu-cart .quantity .minus, ' +
            '.elementor-menu-cart__main .quantity .plus, .elementor-menu-cart__main .quantity .minus';
        var miniCartInputSelector =
            '#custom-side-cart input.qty, #custom-side-cart input[name*="cart["][name*="][qty]"], ' +
            '.elementor-widget-woocommerce-menu-cart input.qty, .elementor-widget-woocommerce-menu-cart input[name*="cart["][name*="][qty]"], ' +
            '.elementor-menu-cart__main input.qty, .elementor-menu-cart__main input[name*="cart["][name*="][qty]"]';
        var qtyAjaxTimer = null;

        function blankslateParseCartItemKey(qtyInput) {
            if (!qtyInput) return '';
            if (qtyInput.name) {
                var m = qtyInput.name.match(/cart\[([^\]]+)\]\[qty\]/);
                if (m) return m[1];
            }
            var row = qtyInput.closest(
                '.cart_item, tr.cart_item, .woocommerce-cart-form__cart-item, .elementor-menu-cart__product, .woocommerce-mini-cart-item, tr.product'
            );
            if (row) {
                var rm = row.querySelector(
                    'a.remove_from_cart_button[data-cart_item_key], a.remove[data-cart_item_key], a[data-cart_item_key], a[data-cart-item-key], a.remove, a.woolentor-cart-product-remove'
                );
                if (rm) {
                    var dk =
                        rm.getAttribute('data-cart_item_key') ||
                        rm.getAttribute('data-cart-item-key') ||
                        '';
                    if (dk) return dk;
                    if (rm.getAttribute('href')) {
                        var href = rm.getAttribute('href');
                        var ma = href.match(/[?&]remove_item=([^&]+)/);
                        if (ma) return decodeURIComponent(ma[1]);
                    }
                }
            }
            var wrap = qtyInput.closest('[data-cart-item-key]');
            if (wrap && wrap.getAttribute('data-cart-item-key')) {
                return wrap.getAttribute('data-cart-item-key');
            }
            return '';
        }

        function blankslateReplaceFragment(sel, html) {
            var $targets = $(sel);
            if (!$targets.length) return false;
            if (sel.indexOf('elementor-widget-container') !== -1) {
                $targets.html(html);
            } else if ($targets.is('form.woocommerce-cart-form')) {
                var $wrap = $targets.closest('.woocommerce');
                if ($wrap.length) {
                    $wrap.replaceWith(html);
                } else {
                    $targets.replaceWith(html);
                }
            } else {
                $targets.replaceWith(html);
            }
            return true;
        }

        function blankslateFindSideCartRow($root, cartItemKey) {
            if (!$root.length || !cartItemKey) {
                return $();
            }
            var nameAttr = 'cart[' + cartItemKey + '][qty]';
            var $row = $root
                .find('input.qty, input[name*="[qty]"]')
                .filter(function () {
                    return this.name === nameAttr;
                })
                .closest('tr');
            if ($row.length) {
                return $row;
            }
            var $remove = $root.find('a').filter(function () {
                var key =
                    this.getAttribute('data-cart_item_key') ||
                    this.getAttribute('data-cart-item-key') ||
                    '';
                if (key === cartItemKey) {
                    return true;
                }
                var href = this.getAttribute('href') || '';
                if (!href) {
                    return false;
                }
                var match = href.match(/[?&]remove_item=([^&]+)/);
                return match && decodeURIComponent(match[1]) === cartItemKey;
            });
            if ($remove.length) {
                return $remove.first().closest('tr');
            }
            return $();
        }

        function blankslateSetSideCartRowPriceHtml($row, html) {
            if (!$row.length || !html) {
                return false;
            }
            var $wlPrice = $row.find('.woolentor-product-price-new').first();
            if ($wlPrice.length) {
                $wlPrice.html(html);
                return true;
            }
            var $subtotalRow = $row.find('td.product-subtotal .product-subtotal__row').last();
            if ($subtotalRow.length) {
                var $amount = $subtotalRow.find('.amount').first();
                if ($amount.length) {
                    $amount.replaceWith(html);
                } else {
                    $subtotalRow.html(html);
                }
                return true;
            }
            var $fallback = $row.find('.woolentor-product-price .amount, .product-price .amount').first();
            if ($fallback.length) {
                $fallback.replaceWith(html);
                return true;
            }
            return false;
        }

        function blankslatePatchSideCartLinePrice(cartItemKey, html) {
            var $root = $('#custom-side-cart');
            if (!cartItemKey || !html || !$root.length) {
                return;
            }
            blankslateSetSideCartRowPriceHtml(blankslateFindSideCartRow($root, cartItemKey), html);
        }

        function blankslatePatchSideCartLinePrices(linePrices) {
            if (!linePrices || typeof linePrices !== 'object') {
                return;
            }
            var $root = $('#custom-side-cart');
            if (!$root.length) {
                return;
            }
            $.each(linePrices, function (cartItemKey, html) {
                if (!html) {
                    return;
                }
                blankslateSetSideCartRowPriceHtml(blankslateFindSideCartRow($root, cartItemKey), html);
            });
        }

        function blankslateSideCartFragmentHasLineItems(html) {
            if (!html || typeof html !== 'string') {
                return false;
            }
            return (
                html.indexOf('woocommerce-cart-form__cart-item') !== -1 ||
                html.indexOf('woolentor-cart-list') !== -1 ||
                html.indexOf('woolentor-cart ') !== -1 ||
                html.indexOf('woolentor-cart-table') !== -1
            );
        }

        function blankslateFindSideCartTableFragmentHtml(fragments) {
            if (!fragments) {
                return '';
            }
            var found = '';
            var tableSelList = blankslateCartAjax.sideCartTableSelector || '';
            if (tableSelList) {
                var tableSels = tableSelList.split(',');
                for (var i = 0; i < tableSels.length; i++) {
                    var sel = $.trim(tableSels[i]);
                    if (sel && fragments[sel] && blankslateSideCartFragmentHasLineItems(fragments[sel])) {
                        return fragments[sel];
                    }
                }
            }
            $.each(fragments, function (_sel, html) {
                if (blankslateSideCartFragmentHasLineItems(html)) {
                    found = html;
                    return false;
                }
            });
            return found;
        }

        function blankslateCleanupSideCartEmptyState(fragments) {
            var $root = $('#custom-side-cart');
            if (!$root.length) {
                return;
            }
            var hasItems =
                $root.find(
                    '.woocommerce-cart-form__cart-item, .woolentor-cart-list .woolentor-cart, .woolentor-cart-table'
                ).length > 0;
            if (!hasItems && fragments) {
                $.each(fragments, function (_sel, html) {
                    if (blankslateSideCartFragmentHasLineItems(html)) {
                        hasItems = true;
                        return false;
                    }
                });
            }
            if (!hasItems) {
                return;
            }
            $root.find('.cart-empty, .woocommerce-info.cart-empty, .mini-cart-empty').remove();
            $root.find('.woocommerce').each(function () {
                var $wrap = $(this);
                if (
                    $wrap.find(
                        '.woocommerce-cart-form__cart-item, .woolentor-cart-list, .woolentor-cart-table'
                    ).length === 0 &&
                    ($wrap.find('.cart-empty').length || $wrap.find('.woocommerce-info').length)
                ) {
                    $wrap.remove();
                }
            });
        }

        function blankslateReadSideCartLinePricesFromFragment(fragments) {
            if (!fragments || !fragments['#blankslate-side-cart-line-prices-data']) {
                return null;
            }
            var wrap = document.createElement('div');
            wrap.innerHTML = fragments['#blankslate-side-cart-line-prices-data'];
            var node = wrap.querySelector('#blankslate-side-cart-line-prices-data');
            if (!node) {
                return null;
            }
            var raw = node.getAttribute('data-line-prices') || '{}';
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function blankslateApplySideCartFragments(fragments) {
            if (!fragments || typeof blankslateCartAjax === 'undefined') {
                return {};
            }
            var handled = {};
            var totalsSel = blankslateCartAjax.sideCartTotalsSelector;
            var tableSelList = blankslateCartAjax.sideCartTableSelector || '';
            var totalsKeys = [totalsSel];
            var totalsHtml = '';
            var tIdx;
            for (tIdx = 0; tIdx < totalsKeys.length; tIdx++) {
                if (totalsKeys[tIdx] && fragments[totalsKeys[tIdx]]) {
                    totalsHtml = fragments[totalsKeys[tIdx]];
                    break;
                }
            }

            var totalsApplied = false;
            for (tIdx = 0; tIdx < totalsKeys.length && totalsHtml; tIdx++) {
                var tSel = totalsKeys[tIdx];
                if (!tSel || !fragments[tSel] || !$(tSel).length) {
                    continue;
                }
                var html = fragments[tSel];
                if (tSel.indexOf('elementor-widget-container') !== -1) {
                    $(tSel).html(html);
                } else {
                    blankslateReplaceFragment(tSel, html);
                }
                handled[tSel] = true;
                totalsApplied = true;
                break;
            }

            if (totalsHtml && !totalsApplied) {
                var $legacyTotals = $('#custom-side-cart .cart_totals').filter(function () {
                    return $(this).closest('.cart-collaterals').length === 0;
                }).first();
                if ($legacyTotals.length) {
                    $legacyTotals.replaceWith(totalsHtml);
                    handled[totalsSel] = true;
                }
            }

            var tableApplied = false;
            var tableHtml = blankslateFindSideCartTableFragmentHtml(fragments);

            if (tableSelList) {
                var tableSels = tableSelList.split(',');
                for (var i = 0; i < tableSels.length; i++) {
                    var tableSel = $.trim(tableSels[i]);
                    if (!tableSel || !$(tableSel).length) {
                        continue;
                    }
                    var htmlForSel = fragments[tableSel] || tableHtml;
                    if (!htmlForSel || !blankslateSideCartFragmentHasLineItems(htmlForSel)) {
                        continue;
                    }
                    blankslateReplaceFragment(tableSel, htmlForSel);
                    handled[tableSel] = true;
                    tableApplied = true;
                }
            }

            if (!tableApplied && tableHtml) {
                var $wlContainers = $(
                    '#custom-side-cart .elementor-widget-wl-cart-table-list .elementor-widget-container, ' +
                        '#custom-side-cart .elementor-widget-wl-cart-table .elementor-widget-container'
                );
                if ($wlContainers.length) {
                    $wlContainers.each(function () {
                        $(this).html(tableHtml);
                    });
                    tableApplied = true;
                }
            }

            if (!tableApplied) {
                $.each(fragments, function (sel, html) {
                    if (handled[sel] || !sel || !html) {
                        return;
                    }
                    if (sel.indexOf('custom-side-cart') === -1 || sel.indexOf('elementor-widget-container') === -1) {
                        return;
                    }
                    if (sel.indexOf('elementor-widget-shortcode') !== -1) {
                        return;
                    }
                    if (!blankslateSideCartFragmentHasLineItems(html)) {
                        return;
                    }
                    if (!$(sel).length) {
                        return;
                    }
                    blankslateReplaceFragment(sel, html);
                    handled[sel] = true;
                    tableApplied = true;
                });
            }

            if (tableApplied) {
                blankslateCleanupSideCartEmptyState(fragments);
            }

            return handled;
        }

        function blankslateApplyCartFragments(data) {
            if (!data || !data.fragments) return;
            var cartWasOpen = document.getElementById('custom-side-cart') &&
                document.getElementById('custom-side-cart').classList.contains('open');

            blankslateGtmConsumeWcFragmentAnchor(data.fragments);

            var handled = blankslateApplySideCartFragments(data.fragments);
            var skipLegacySideCart = [
                '#custom-side-cart .woocommerce',
                '#custom-side-cart form.woocommerce-cart-form',
                '#custom-side-cart .cart_totals'
            ];

            var tableHtmlForLegacy = blankslateFindSideCartTableFragmentHtml(data.fragments);
            if (tableHtmlForLegacy) {
                $('#custom-side-cart .woocommerce').each(function () {
                    var $wrap = $(this);
                    if (
                        $wrap.closest('.elementor-widget-wl-cart-table-list, .elementor-widget-wl-cart-table')
                            .length &&
                        $wrap.find('.cart-empty, .woocommerce-info').length &&
                        !$wrap.find('.woocommerce-cart-form__cart-item, .woolentor-cart-list').length
                    ) {
                        $wrap.replaceWith(tableHtmlForLegacy);
                    }
                });
            }

            $.each(data.fragments, function (sel, html) {
                if (handled[sel]) {
                    return;
                }
                if (skipLegacySideCart.indexOf(sel) !== -1) {
                    return;
                }
                blankslateReplaceFragment(sel, html);
            });

            blankslateCleanupSideCartEmptyState(data.fragments);

            var linePricesFromFragments = blankslateReadSideCartLinePricesFromFragment(data.fragments);
            if (linePricesFromFragments) {
                blankslatePatchSideCartLinePrices(linePricesFromFragments);
            }

            blankslatePersistWcFragmentStorage(data);
            blankslateRefreshSideCartQtyButtons();
            window.setTimeout(blankslateRefreshSideCartQtyButtons, 0);

            $(document.body).trigger('wc_fragments_refreshed');

            if (cartWasOpen) {
                blankslateOpenSideCart();
            }
        }

        function blankslateRefreshWcFragments() {
            if (!blankslateCartAjax.fragmentsUrl) return $.Deferred().reject().promise();
            return $.post(blankslateCartAjax.fragmentsUrl, { time: new Date().getTime() }, null, 'json');
        }

        function blankslateRefreshElementorMenuCartFragments() {
            var dfd = $.Deferred();
            if (
                typeof elementorFrontend === 'undefined' ||
                !blankslateCartAjax.adminAjaxUrl ||
                !blankslateCartAjax.elementorMcNonce
            ) {
                dfd.resolve();
                return dfd.promise();
            }
            var templates = [];
            if (elementorFrontend.documentsManager && elementorFrontend.documentsManager.documents) {
                $.each(elementorFrontend.documentsManager.documents, function (index) {
                    templates.push(index);
                });
            }
            if (!templates.length) {
                dfd.resolve();
                return dfd.promise();
            }
            $.post(
                blankslateCartAjax.adminAjaxUrl,
                {
                    action: 'elementor_menu_cart_fragments',
                    templates: templates,
                    _nonce: blankslateCartAjax.elementorMcNonce,
                    is_editor:
                        typeof elementorFrontend.isEditMode === 'function'
                            ? elementorFrontend.isEditMode()
                            : false
                },
                null,
                'json'
            )
                .done(function (successData) {
                    if (successData && successData.fragments) {
                        $.each(successData.fragments, function (key, value) {
                            if (key.indexOf('custom-side-cart') !== -1) {
                                return;
                            }
                            $(key).replaceWith(value);
                        });
                    }
                })
                .always(function () {
                    dfd.resolve();
                });
            return dfd.promise();
        }

        function blankslateSyncMiniCartLine($input) {
            var el = $input[0];
            var key = blankslateParseCartItemKey(el);
            if (!key) {
                if (window.console && console.warn) {
                    console.warn(
                        'Blankslate mini-cart: cart_item_key not found (check quantity input name or remove link data attribute).'
                    );
                }
                return;
            }
            var qty = parseFloat(el.value);
            if (isNaN(qty)) return;

            var cartWasOpen = document.getElementById('custom-side-cart') &&
                document.getElementById('custom-side-cart').classList.contains('open');

            $.post(
                blankslateCartAjax.wcAjaxUrl,
                {
                    security: blankslateCartAjax.nonce,
                    cart_item_key: key,
                    quantity: qty
                },
                null,
                'json'
            )
                .done(function (resp) {
                    if (!resp || resp.success === false) return;

                    if (resp.data && resp.data.gtm_add_to_cart) {
                        blankslateGtmPushAddToCartList(resp.data.gtm_add_to_cart);
                    }

                    var pendingLinePrices =
                        resp.data && resp.data.line_prices ? resp.data.line_prices : null;

                    blankslateRefreshWcFragments().done(function (fragResp) {
                        if (fragResp && fragResp.fragments) {
                            blankslateApplyCartFragments(fragResp);
                            var fragPrices = blankslateReadSideCartLinePricesFromFragment(fragResp.fragments);
                            if (fragPrices) {
                                pendingLinePrices = fragPrices;
                            }
                        } else if (cartWasOpen) {
                            blankslateOpenSideCart();
                        }

                        function blankslateApplyPendingLinePrices() {
                            if (pendingLinePrices) {
                                blankslatePatchSideCartLinePrices(pendingLinePrices);
                            } else if (resp.data && resp.data.line_subtotal && resp.data.cart_item_key) {
                                blankslatePatchSideCartLinePrice(resp.data.cart_item_key, resp.data.line_subtotal);
                            }
                        }

                        blankslateApplyPendingLinePrices();
                        window.setTimeout(blankslateApplyPendingLinePrices, 0);
                        window.setTimeout(blankslateApplyPendingLinePrices, 120);

                        $(miniCartRoot).each(function () {
                            blankslateEnsurePlusMinusButtons(this);
                        });
                        if (cartWasOpen) {
                            blankslateOpenSideCart();
                        }
                        blankslateRefreshElementorMenuCartFragments();
                    });
                })
                .fail(function (xhr) {
                    var msg = '';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        if (typeof xhr.responseJSON.data === 'string') msg = xhr.responseJSON.data;
                        else if (xhr.responseJSON.data.message) msg = xhr.responseJSON.data.message;
                    }
                    if (msg) window.alert(msg);
                });
        }

        $(document).on('click', miniCartQtySelector, function (e) {
            var $btn = $(this);
            var isPlus =
                $btn.hasClass('plus') ||
                $btn.hasClass('woolentor-quantity-increase') ||
                ($btn.hasClass('woolentor-cart-product-quantity-btn') && $btn.hasClass('plus'));
            var isMinus =
                $btn.hasClass('minus') ||
                $btn.hasClass('woolentor-quantity-decrease') ||
                ($btn.hasClass('woolentor-cart-product-quantity-btn') && $btn.hasClass('minus'));

            if (!isPlus && !isMinus) {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            var $wrap = $btn.closest('.quantity, .woolentor-product-quantity, .woolentor-cart-product-quantity');
            var $inp = $wrap.find('input.qty, input[name*="cart["][name*="][qty]"]').not('[type="hidden"]').first();
            if (!$inp.length) {
                return false;
            }

            var v = parseInt($inp.val(), 10);
            if (isNaN(v)) {
                v = 1;
            }
            if (isPlus) {
                $inp.val(v + 1);
            } else if (v > 1) {
                $inp.val(v - 1);
            }
            blankslateSyncMiniCartLine($inp);
            return false;
        });

        $(document).on('input change', miniCartInputSelector, function (e) {
            var $inp = $(this);
            if (e.type === 'change') {
                blankslateSyncMiniCartLine($inp);
                return;
            }
            clearTimeout(qtyAjaxTimer);
            qtyAjaxTimer = setTimeout(function () {
                blankslateSyncMiniCartLine($inp);
            }, 450);
        });

        $(document.body).on('wc_fragments_refreshed added_to_cart wc_fragments_loaded', function () {
            blankslateRefreshSideCartQtyButtons();
            window.setTimeout(blankslateRefreshSideCartQtyButtons, 0);
        });

        function blankslateRegisterElementorCartQtyHooks() {
            if (window.blankslateElementorCartQtyHooksRegistered) {
                return;
            }
            if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
                return;
            }
            window.blankslateElementorCartQtyHooksRegistered = true;
            var onWlCartReady = function ($scope) {
                if ($scope.closest('#custom-side-cart').length) {
                    blankslateRefreshSideCartQtyButtons();
                    window.setTimeout(blankslateRefreshSideCartQtyButtons, 0);
                }
            };
            elementorFrontend.hooks.addAction('frontend/element_ready/wl-cart-table-list.default', onWlCartReady);
            elementorFrontend.hooks.addAction('frontend/element_ready/wl-cart-table.default', onWlCartReady);
        }

        blankslateRegisterElementorCartQtyHooks();
        $(window).on('elementor/frontend/init', blankslateRegisterElementorCartQtyHooks);

        /**
         * Single product: AJAX add-to-cart (без перезавантаження → без повторного view_item).
         */
        if (blankslateCartAjax.singleProductAtc && blankslateCartAjax.addToCartUrl) {
            $(document).on('submit', 'form.cart:not(.grouped_form)', function (e) {
                if (!document.body.classList.contains('single-product')) {
                    return;
                }
                var form = this;
                var submitter = e.originalEvent && e.originalEvent.submitter;
                if (submitter && submitter.classList.contains('custom-buy-now-btn')) {
                    return;
                }
                if (form.classList.contains('variations_form')) {
                    var variationId = parseInt($(form).find('input[name="variation_id"]').val(), 10);
                    if (!variationId) {
                        return;
                    }
                }

                if (form.getAttribute('data-blankslate-atc-pending') === '1') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();
                form.setAttribute('data-blankslate-atc-pending', '1');

                var $btn = submitter
                    ? $(submitter)
                    : $(form).find('.single_add_to_cart_button').first();
                if (!$btn.length) {
                    $btn = $(form).find('[type="submit"]').first();
                }

                $btn.addClass('loading').prop('disabled', true);

                var data = blankslateBuildAddToCartPostData(form);

                $.post(blankslateCartAjax.addToCartUrl, $.param(data), null, 'json')
                    .done(function (response) {
                        form.removeAttribute('data-blankslate-atc-pending');
                        $btn.removeClass('loading').prop('disabled', false);

                        if (!response) {
                            return;
                        }
                        if (response.error && response.product_url) {
                            window.location = response.product_url;
                            return;
                        }

                        var finishAfterFragments = function (payload) {
                            if (!payload || !payload.fragments) {
                                blankslateOpenSideCart();
                                return;
                            }
                            blankslateApplyCartFragments(payload);
                            $(miniCartRoot).each(function () {
                                blankslateEnsurePlusMinusButtons(this);
                            });
                            blankslateOpenSideCart();
                            var addedEv = $.Event('added_to_cart');
                            addedEv.blankslateGtmHandled = true;
                            $(document.body).trigger(addedEv, [
                                payload.fragments,
                                payload.cart_hash,
                                $btn
                            ]);
                        };

                        blankslateRefreshWcFragments()
                            .done(function (fragResp) {
                                finishAfterFragments(
                                    fragResp && fragResp.fragments ? fragResp : response
                                );
                                blankslateRefreshElementorMenuCartFragments();
                            })
                            .fail(function () {
                                finishAfterFragments(response);
                                blankslateRefreshElementorMenuCartFragments();
                            });
                    })
                    .fail(function () {
                        form.removeAttribute('data-blankslate-atc-pending');
                        $btn.removeClass('loading').prop('disabled', false);
                    });
            });
        }
    }

    /* DESKTOP MENU */
    // Create a condition that targets viewports
    const mediaQuery_desktop_menu = window.matchMedia('(min-width: 1025px)');
    //min-width:1025px
    function handleDesktopChangeMenu(e) {
        if (e.matches) {
            $('.elementor-nav-menu--main .elementor-nav-menu > li > ul').hide().css('opacity', '1');
        }
    }
    // Register event listener
    mediaQuery_desktop_menu.addListener(handleDesktopChangeMenu);
    // Initial check
    handleDesktopChangeMenu(mediaQuery_desktop_menu);
    /* desktop menu */

    /* POPUPS LOGIC */
    // Wait until the page is fully loaded
    window.onload = function () {

        function showPopup(popupId) {
            $('#' + popupId).css("display", "flex").hide().fadeIn();
            
            // Only add the 'popup-fixed' class if the popup is not the cookie popup
            if (popupId !== 'cookie-popup') {
                $('html').addClass('popup-fixed');
            }

            // Set cookie as soon as the popup is shown
            setCookie(popupId, "true", {expires:365, path: '/'});
        }

        function hidePopup(popupId) {
            $('#' + popupId).fadeOut();
            $('html').removeClass('popup-fixed');
            showNextPopup();
        }

        // Function to check if a popup has already been shown
        function hasPopupBeenShown(popupId) {
            return getCookie(popupId) === "true";
        }

        // Handle closing the popups
        $(".popup-close, .close-popup").click(function() {
            const popupId = $(this).closest('.popup').attr('id');
            hidePopup(popupId);
        });

        // Close the popup when clicking on the overlay
        $(".popup-overlay").click(function() {
            const popupId = $(this).closest('.popup').attr('id');
            hidePopup(popupId);
        });

        // Function to show the next popup after a delay
        function showNextPopup() {
            const popups = [
                { id: 'preferences-popup', delay: 0 },      // First popup, show immediately
                { id: 'cookie-popup', delay: 2000 },   // Second popup, show after 2s
                { id: 'subscription-popup', delay: 120000 }   // Third popup, show after 30s
            ];

            // Go through each popup and show the next one if it hasn't been shown
            for (let i = 0; i < popups.length; i++) {
                const popup = popups[i];
                
                if (!hasPopupBeenShown(popup.id) && (i === 0 || i > 0 && hasPopupBeenShown(popups[i - 1].id))) {
                    // Show the popup with the corresponding delay
                    setTimeout(function() {
                        showPopup(popup.id);
                    }, popup.delay);
                }
            }
        }

        showNextPopup();
    };
    /* popups logic */

    jQuery(document).ready(function($) {
        /* CONSULT MODAL */
        $('.consult-button').on('click', function(e){
            e.preventDefault();
            $('#consult-popup').css("display", "flex").hide().fadeIn();
            $('html').addClass('popup-fixed');
            window.setTimeout(blankslateInitElementorFormGtm, 400);
        });

        /* DEVICE AGENT */
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
        /* device agent */

        /* SCROLL TO TOP */
        $('#scroll-to-top').on('click', function() {
            $("html, body").animate({ scrollTop: 0 }, 800);
        });
        /* scroll to top */

        /* MOBILE MENU */
        $('#menu-mob .sub-arrow').off('click');
        // Handle menu item click
        $('#menu-mob .menu-item-has-children > a').off('click').on('click', function (e) {
            e.stopPropagation();
            if($(this).hasClass('opened')) {
                $(this).removeClass('opened').next().hide().parent().removeClass('opened').siblings().show();
            } else {
                $(this).addClass('opened').next().show().parent().addClass('opened').siblings().hide();
            }
        });
        /* mobile menu */

        /* CATALOG FILTER */
        $('.woof_block_html_items').hide(); // Hide all filter sections by default
        $('.woof_container h4').click(function () {
            $(this).toggleClass('opened').next('.woof_block_html_items').slideToggle();
        });
        /* catalog filter */

        /* CUSTOM ADD TO CART */
        $('.custom-buy-now-btn').on('click', function() {
            // Set custom_buy_now_redirect to 'yes' when Buy Now is clicked
            $('#custom-buy-now-redirect').val('yes');
        });
        /* custom add to cart */

        /* RELATED PRODUCTS DISPLAY */
        $('.related').each(function() {
            if($(this).find('.e-loop-nothing-found-message').length) $(this).hide();
        });
        /* related products display */
    });

});