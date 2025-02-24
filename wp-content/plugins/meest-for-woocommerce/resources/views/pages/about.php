<?php
use MeestShipping\Core\View;
?>
<div class="container">
    <div class="row">
        <div class="about-grid">
            <div class="w75">
                <div class="content">
                    <div class="table-container">
                        <h3 class="table-container-title"><?php _e('About', MEEST_PLUGIN_DOMAIN) ?></h3>
                        <div style="padding: 0 10px;">
                            <div>
                                <img src="<?php echo MEEST_PLUGIN_URL.'public\img\logo.png' ?>"/>
                                <p><?php _e('Meest - International Postal and Logistics Group, for detailed information - visit our website <a href="https://meest.com">https://meest.com</a>.', MEEST_PLUGIN_DOMAIN) ?></a></p>
                                <p><?php _e('The “Meest for WooCommerce” plugin connects delivery from Meest to your online store, allows you to create invoices, courier calls and manage them.', MEEST_PLUGIN_DOMAIN) ?></p>
                            </div>
                            <hr>
                            <div>
                                <h2><?php _e('Settings', MEEST_PLUGIN_DOMAIN) ?></h2>
                                <ol>
                                    <li><?php _e('Go to the <a href="admin.php?page=meest_setting">”Settings page”</a>.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Enter the data to connect to the API and save the settings.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('If the settings are saved successfully, fill in other data.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Fill in the data about the Agent, phone in accordance with the international format.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Fill in the data on the address of the parcel collection.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('For Ukraine - available types of delivery: address and branch. City and street or branch - selected from the list.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('For other countries, only address delivery is available. Region, city, street - entered manually.', MEEST_PLUGIN_DOMAIN) ?></li>
                                </ol>
                            </div>
                            <hr>
                            <div>
                                <h2><?php _e('Creating an empty parcel', MEEST_PLUGIN_DOMAIN) ?></h2>
                                <ol>
                                    <li><?php _e('Go to the page <a href="admin.php?page=meest_parcel">“Parcels”</a> and click the button “Create”.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('To quickly create a parcel - select “Parcel“ from the top menu “Add“.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Fill in the parcel parameters.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Types of packaging can be selected from the list or specify special sizes.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Filling in the address for the sender and recipient is the same as for the page <a href="admin.php?page=meest_setting">”Settings”</a>.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('After creating a parcel, you can edit, delete, track, add to the ”Pickups”, cancel the ”Pickup” and print the declaration and sticker.', MEEST_PLUGIN_DOMAIN) ?></li>
                                </ol>
                            </div>
                            <hr>
                            <div>
                                <h2><?php _e('Creating a parcel by the order', MEEST_PLUGIN_DOMAIN) ?></h2>
                                <ol>
                                    <li><?php _e('Go to the page <a href="edit.php?post_type=shop_order">“Order”</a>.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('For orders for which “Bridge” delivery is selected, the “Create parcel” button is displayed.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Click the button ”Create Parcel” or open the order and click on the button ”Create Parcel” in the right pane.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Data is filled in automatically.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('You can change or add information about the parcel, sender and recipient.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('After creating the parcel, the invoice number will be displayed in the orders or when you open the order, you can edit the parcel, track and print the declaration and the sticker.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('If you set the status to ”Shipped” for the order, the user will be automatically sent a letter with the invoice number.', MEEST_PLUGIN_DOMAIN) ?></li>
                                </ol>
                            </div>
                            <hr>
                            <div>
                                <h2><?php _e('Creating a pickup', MEEST_PLUGIN_DOMAIN) ?></h2>
                                <ol>
                                    <li><?php _e('Go to the page <a href="admin.php?page=meest_parcel">“Parcels”</a> and click the button “Create pickup for parcels”</a>.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('You can also create a pickup on the page <a href="admin.php?page=meest_pickup&action=create">Pickups</a>.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('Filling in the address is the same as for the parcel, specify the desired time of arrival of the courier.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('On the right is a list of parcels that will be included in the call.', MEEST_PLUGIN_DOMAIN) ?></li>
                                    <li><?php _e('After creating the ”Pickup”, it can be edited and deleted.', MEEST_PLUGIN_DOMAIN) ?></li>
                                </ol>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w25">
                <?php echo View::part('views/parts/support') ?>
            </div>
        </div>
    </div>
</div>