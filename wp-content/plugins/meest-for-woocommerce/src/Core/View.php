<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

class View
{
    public static $path = 'resources';

    public static function render($file, $data = [])
    {
        $options = meest_init('Option')->all();
        extract($data);

        ob_start();

        echo '<div class="wrap">';
        include MEEST_PLUGIN_PATH . self::$path . "/$file.php";
        echo '</div>';

        echo ob_get_clean();

        return true;
    }

    public static function part($file, $data = [])
    {
        $options = meest_init('Option')->all();
        extract($data);

        ob_start();

        include MEEST_PLUGIN_PATH . self::$path . "/$file.php";

        return ob_get_clean();
    }
}
