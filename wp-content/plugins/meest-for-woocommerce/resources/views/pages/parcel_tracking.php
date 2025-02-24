<?php
use MeestShipping\Core\View;

$lang = get_locale();
$lang = $lang === 'uk' ? 'UA' : strtoupper($lang);
?>
<div class="container">
    <div class="row">
        <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL.'public\img\icon_big.png' ?>">
        <h1><?php _e('Track parcel #', MEEST_PLUGIN_DOMAIN) ?><?php echo esc_attr($parcel->barcode) ?></h1>
        <hr class="wp-header">
        <div class="parcel-grid">
            <div class="w75">
                <div class="content">
                    <div class="table-container">
                        <table class="wp-list-table widefat fixed striped table-view-list parcels">
                            <thead>
                            <tr>
                                <th scope="col" class="manage-column column-primary"><?php _e('Date', MEEST_PLUGIN_DOMAIN) ?></th>
                                <th scope="col" class="manage-column column-primary"><?php _e('Code', MEEST_PLUGIN_DOMAIN) ?></th>
                                <th scope="col" class="manage-column column-primary"><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?></th>
                                <th scope="col" class="manage-column column-primary"><?php _e('City', MEEST_PLUGIN_DOMAIN) ?></th>
                                <th scope="col" class="manage-column column-primary"><?php _e('Event', MEEST_PLUGIN_DOMAIN) ?></th>
                                <th scope="col" class="manage-column column-primary"><?php _e('In detail', MEEST_PLUGIN_DOMAIN) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($tracking) > 0): ?>
                                <?php foreach ($tracking as $track) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_attr($track['eventDateTime']) ?></th>
                                    <th scope="row"><?php echo esc_attr($track['eventCodeUPU']) ?></th>
                                    <th scope="row"><?php echo esc_attr($track['eventCountryDescr']["descr$lang"]) ?></th>
                                    <th scope="row"><?php echo esc_attr($track['eventCityDescr']["descr$lang"]) ?></th>
                                    <th scope="row"><?php echo esc_attr($track['eventDescr']["descr$lang"]) ?></th>
                                    <th scope="row"><?php echo esc_attr($track['eventDetailDescr']["descr$lang"]) ?></th>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <th colspan="6" scope="row"><?php _e('Tracking information is missing', MEEST_PLUGIN_DOMAIN) ?></th>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="submit">
                        <a class="button button-error button-large" href="?page=meest_parcel"><?php _e('Back', MEEST_PLUGIN_DOMAIN) ?></a>
                    </p>
                </div>
            </div>
            <div class="w25">
                <?php echo View::part('views/parts/support') ?>
            </div>
        </div>
    </div>
</div>
