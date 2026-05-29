/**
 * A11Y: ensure interactive elements have accessible names (Elementor / Woo / Swiper fallbacks).
 */
(function () {
	function ensureMainLandmark() {
		var hasMain = document.querySelector('main, [role="main"]');
		if (hasMain) return;

		var candidates = [
			'[data-elementor-type="wp-page"]'
		];

		for (var i = 0; i < candidates.length; i++) {
			var el = document.querySelector(candidates[i]);
			if (!el) continue;
			el.setAttribute('role', 'main');
			if (!el.id) {
				el.id = 'main-content';
			}
			return;
		}
	}

	function hasAccessibleName(el) {
		if (!el) return true;
		if ((el.getAttribute('aria-label') || '').trim() !== '') return true;
		if ((el.getAttribute('aria-labelledby') || '').trim() !== '') return true;
		var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
		return text !== '';
	}

	function guessLabel(el) {
		if (el.id === 'scroll-to-top') {
			return 'Scroll to top';
		}

		var productCard = el.closest('li.product, .eael-product-slider, .swiper-slide');
		if (productCard) {
			var titleNode = productCard.querySelector('.eael-product-title, .woocommerce-loop-product__title, h1, h2, h3, h4, h5, h6');
			var productTitle = titleNode ? (titleNode.textContent || '').replace(/\s+/g, ' ').trim() : '';

			if (el.classList.contains('open-popup-link') || el.closest('.eael-product-quick-view')) {
				return productTitle ? ('Quick view: ' + productTitle) : 'Quick view product';
			}

			if (el.closest('.view-details') || el.querySelector('.fa-link')) {
				return productTitle ? ('View details: ' + productTitle) : 'View product details';
			}

			if (el.classList.contains('woocommerce-loop-product__link') || el.classList.contains('woocommerce-LoopProduct-link')) {
				return productTitle ? ('Open product: ' + productTitle) : 'Open product';
			}
		}

		var attrCandidates = ['title', 'data-title', 'aria-description'];
		for (var i = 0; i < attrCandidates.length; i++) {
			var value = (el.getAttribute(attrCandidates[i]) || '').trim();
			if (value) return value;
		}

		var icon = el.querySelector('i[class], svg, img[alt]');
		if (icon && icon.tagName === 'IMG') {
			var alt = (icon.getAttribute('alt') || '').trim();
			if (alt) return alt;
		}

		var nearHeading = el.closest('.elementor-widget, .elementor-button-wrapper, .elementor-icon-box-wrapper');
		if (nearHeading) {
			var h = nearHeading.querySelector('h1, h2, h3, h4, h5, h6, .elementor-heading-title');
			if (h) {
				var headingText = (h.textContent || '').replace(/\s+/g, ' ').trim();
				if (headingText) return headingText;
			}
		}

		return '';
	}

	function patchNames(scope) {
		var root = scope || document;
		var selector = 'a, button, [role="button"], [role="menuitem"], [role="link"]';
		var nodes = root.querySelectorAll(selector);

		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			if (hasAccessibleName(el)) continue;
			if (el.getAttribute('aria-hidden') === 'true') continue;
			var label = guessLabel(el);
			if (label) {
				el.setAttribute('aria-label', label);
			}
		}
	}

	/**
	 * Elementor + SmartMenus: <a href> is implicit role "link"; axe rejects aria-haspopup /
	 * aria-controls / aria-expanded on links. SmartMenus sets these on .elementor-item and
	 * often on .elementor-sub-item; desktop init can run after DOMContentLoaded.
	 */
	function stripElementorNavLinkAria(a) {
		if (!a || !a.removeAttribute || !a.matches) {
			return;
		}
		if (!a.matches('a.elementor-item') && !a.matches('a.elementor-sub-item')) {
			return;
		}
		a.removeAttribute('aria-haspopup');
		a.removeAttribute('aria-controls');
		a.removeAttribute('aria-expanded');
	}

	function fixElementorNavSubmenuLinkAria(root) {
		var scope = root || document;
		stripElementorNavLinkAria(scope.nodeType === 1 ? scope : null);
		if (!scope.querySelectorAll) {
			return;
		}
		var links = scope.querySelectorAll('a.elementor-item, a.elementor-sub-item');
		for (var i = 0; i < links.length; i++) {
			stripElementorNavLinkAria(links[i]);
		}
	}

	/**
	 * Swiper a11y module sets role="group" + slide counter aria-label on slides.
	 * On <li class="swiper-slide"> that conflicts with implicit listitem (Lighthouse/axe).
	 */
	function fixSwiperListItemSlideRole(root) {
		var scope = root || document;
		function patchLi(li) {
			if (!li.matches || !li.matches('li.swiper-slide')) {
				return;
			}
			var r = (li.getAttribute('role') || '').toLowerCase();
			if (r === 'group') {
				li.removeAttribute('role');
			}
			var al = (li.getAttribute('aria-label') || '').trim();
			if (/^\d+\s*\/\s*\d+$/.test(al)) {
				li.removeAttribute('aria-label');
			}
			li.removeAttribute('aria-roledescription');
		}
		if (scope.nodeType === 1) {
			patchLi(scope);
		}
		if (!scope.querySelectorAll) {
			return;
		}
		var slides = scope.querySelectorAll('li.swiper-slide[role], li.swiper-slide[aria-label]');
		for (var s = 0; s < slides.length; s++) {
			patchLi(slides[s]);
		}
	}

	function runA11yPatches(root) {
		ensureMainLandmark();
		fixElementorNavSubmenuLinkAria(root || document);
		fixSwiperListItemSlideRole(root || document);
		patchNames(root || document);
	}

	function runDelayedA11yFixes() {
		fixElementorNavSubmenuLinkAria(document);
		fixSwiperListItemSlideRole(document);
	}

	document.addEventListener('DOMContentLoaded', function () {
		runA11yPatches(document);
		setTimeout(runDelayedA11yFixes, 200);
		setTimeout(runDelayedA11yFixes, 1000);
		setTimeout(runDelayedA11yFixes, 2500);
		requestAnimationFrame(function () {
			requestAnimationFrame(runDelayedA11yFixes);
		});
		window.addEventListener('load', runDelayedA11yFixes);
		var observer = new MutationObserver(function (mutations) {
			for (var i = 0; i < mutations.length; i++) {
				var mutation = mutations[i];
				if (mutation.type === 'attributes' && mutation.target.matches) {
					var t = mutation.target;
					if (t.matches('a.elementor-item') || t.matches('a.elementor-sub-item')) {
						stripElementorNavLinkAria(t);
					}
					if (t.matches('li.swiper-slide')) {
						fixSwiperListItemSlideRole(t);
					}
				}
				if (mutation.type !== 'childList') {
					continue;
				}
				for (var j = 0; j < mutation.addedNodes.length; j++) {
					var added = mutation.addedNodes[j];
					if (added && added.nodeType === 1) {
						runA11yPatches(added);
					}
				}
			}
		});
		observer.observe(document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['aria-expanded', 'aria-controls', 'aria-haspopup', 'role', 'aria-label', 'aria-roledescription']
		});
	});
})();
