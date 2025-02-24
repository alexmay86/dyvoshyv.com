<?php

namespace MeestShipping\Modules;

class PluginMenu
{
    public function init()
    {
        add_filter('plugin_action_links_'.MEEST_PLUGIN_BASENAME, [$this, 'pluginActionLinks']);
    }

    public function pluginActionLinks($links)
    {
        $link = '<a href="' . admin_url('admin.php?page=meest_setting') . '">'.__('Settings', MEEST_PLUGIN_DOMAIN).'</a>';
        array_unshift($links, $link);

        return $links;
    }
}
