<style>
.wp-list-table .column-order_id { width: 7%; }
.wp-list-table .column-cost_services { width: 7%; }
.wp-list-table .column-delivery_date { width: 7%; }
.wp-list-table .column-created_at { width: 7%; }
.wp-list-table .column-updated_at { width: 7%; }
</style>
<div class="container">
    <div class="row">
        <form method="get">
            <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL.'public\img\icon_big.png' ?>">
            <h1 class="wp-heading-inline"><?php _e('Pickups', MEEST_PLUGIN_DOMAIN) ?></h1>
            <?php if ($totalPickup > 0): ?>
                <a href="admin.php?page=meest_pickup&action=create" class="page-title-action button-primary button-large"><?php _e('Create') ?></a>
            <?php endif ?>
            <?php settings_errors() ?>
            <?php \MeestShipping\Core\Error::show(); ?>

            <hr class="wp-header-end">

            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
            <?php $pickupTable->display() ?>
        </form>
    </div>
</div>