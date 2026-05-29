<?php
/* CUSTOM PRODUCTS TAXONOMIES */
function cptui_register_my_taxes() {
	/**
	 * Taxonomy: Категорії.
	 */
	$labels = [
		"name" => "Категорії",
		"singular_name" => "Категорія",
		"menu_name" => "Категорії",
		"all_items" => "Всі категорії",
		"edit_item" => "Редагувати категорію",
		"view_item" => "Переглянути категорію",
		"update_item" => "Оновити категорію",
		"add_new_item" => "Додати нову категорію",
		"new_item_name" => "Назва нової категорії",
		"parent_item" => "Батьківська категорія",
		"parent_item_colon" => "Батьківська категорія:",
		"search_items" => "Шукати категорії",
		"popular_items" => "Популярні категорії",
		"separate_items_with_commas" => "Окремі категорії через кому",
		"add_or_remove_items" => "Додати або видалити категорії",
		"choose_from_most_used" => "Обрати з найбільш вживаних",
		"not_found" => "Категорії не знайдені",
		"no_terms" => "Категорій немає",
		"items_list_navigation" => "Навігація списком категорій",
		"items_list" => "Список категорій",
	];
	$args = [
		"label" => "Категорії",
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => true,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => [ 'slug' => 'type', 'with_front' => true, ],
		"show_admin_column" => false,
		"show_in_rest" => true,
		"rest_base" => "type",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => false,
		];
	register_taxonomy( "type", [ "product" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes' );

add_filter( 'woocommerce_taxonomy_args_product_cat', 'rename_product_category_taxonomy' );
function rename_product_category_taxonomy( $args ) {
    $args['labels']['name'] = 'Колекції';
    $args['labels']['singular_name'] = 'Колекція';
    $args['labels']['menu_name'] = 'Колекції';
	$args['labels']["all_items"] = "Всі колекції";
	$args['labels']["edit_item"] = "Редагувати колекцію";
	$args['labels']["view_item"] = "Переглянути колекцію";
	$args['labels']["update_item"] = "Оновити колекцію";
	$args['labels']["add_new_item"] = "Додати нову колекцію";
	$args['labels']["new_item_name"] = "Назва нової колекції";
	$args['labels']["parent_item"] = "Батьківська колекція";
	$args['labels']["parent_item_colon"] = "Батьківська колекція:";
	$args['labels']["search_items"] = "Шукати колекції";
	$args['labels']["popular_items"] = "Популярні колекції";
	$args['labels']["separate_items_with_commas"] = "Окремі колекції через кому";
	$args['labels']["add_or_remove_items"] = "Додати або видалити колекції";
	$args['labels']["choose_from_most_used"] = "Обрати з найбільш вживаних";
	$args['labels']["not_found"] = "Колекції не знайдені";
	$args['labels']["no_terms"] = "Колекцій немає";
	$args['labels']["items_list_navigation"] = "Навігація списком колекцій";
	$args['labels']["items_list"] = "Список колекцій";
    return $args;
}

add_filter( 'woo_feed_category_mapping_taxonomy', 'dyvoshyv_ctx_feed_category_mapping_taxonomy' );
function dyvoshyv_ctx_feed_category_mapping_taxonomy( $taxonomy ) {
	if ( taxonomy_exists( 'type' ) ) {
		return 'type';
	}

	return $taxonomy;
}

add_action( 'admin_head', 'dyvoshyv_ctx_feed_reset_category_mapping_cache' );
function dyvoshyv_ctx_feed_reset_category_mapping_cache() {
	if ( ! is_admin() ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'webappick-manage-category-mapping' !== $page ) {
		return;
	}
	?>
	<script>
	(function () {
		var reloadFlag = 'dyvoshyv_ctxfeed_category_mapping_cache_reset';
		var storageKeys = [
			'options',
			'dropdownOptions',
			'woo_feed_category_mapping',
			'woo_feed_attributes',
			'singleCategoryMapping',
			'categoryMappingAddEdit',
			'singleAttributeMapping',
			'attributeMappingAddEdit'
		];
		try {
			storageKeys.forEach(function (key) {
				localStorage.removeItem(key);
				sessionStorage.removeItem(key);
			});

			var cachedData = localStorage.getItem('ctxFeedData');
			if (cachedData) {
				var parsedData = JSON.parse(cachedData);
				if (parsedData && typeof parsedData === 'object') {
					delete parsedData.options;
					delete parsedData.dropdownOptions;
					delete parsedData.woo_feed_category_mapping;
					delete parsedData.woo_feed_attributes;
					delete parsedData.singleCategoryMapping;
					delete parsedData.categoryMappingAddEdit;
					localStorage.setItem('ctxFeedData', JSON.stringify(parsedData));
				}
			}

			var sessionData = sessionStorage.getItem('ctxFeedData');
			if (sessionData) {
				var parsedSessionData = JSON.parse(sessionData);
				if (parsedSessionData && typeof parsedSessionData === 'object') {
					delete parsedSessionData.options;
					delete parsedSessionData.dropdownOptions;
					delete parsedSessionData.woo_feed_category_mapping;
					delete parsedSessionData.woo_feed_attributes;
					delete parsedSessionData.singleCategoryMapping;
					delete parsedSessionData.categoryMappingAddEdit;
					sessionStorage.setItem('ctxFeedData', JSON.stringify(parsedSessionData));
				}
			}

			if (!sessionStorage.getItem(reloadFlag)) {
				sessionStorage.setItem(reloadFlag, '1');
				window.location.reload();
				return;
			}

			sessionStorage.removeItem(reloadFlag);
		} catch (error) {
			console.warn('CTX Feed cache reset failed', error);
		}
	})();
	</script>
	<?php
}
?>