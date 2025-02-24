<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

class Error
{
    private static $name = 'meest_errors';
    private static $types = ['error', 'success', 'warning', 'info'];

    public static function add($id, $message, $type = 'error')
    {
        $_SESSION[self::$name][$id] = [$message, $type];
    }

    public static function show()
    {
        if (empty($_SESSION[self::$name]) || !is_array($_SESSION[self::$name])) {
            return;
        }

        $errors = $_SESSION[self::$name];

        $output = '';
        foreach ($errors as $key => $value) {
            if (in_array($value[1], self::$types, true)) {
                $type = 'notice-' . $value[1];
            }

            $output .= "<div id=\"".self::$name.'-'.$key."\" class=\"notice $type is-dismissible\">";
            $output .= "<p><strong>{$value[0]}</strong></p>";
            $output .= "</div>";
        }

        echo $output;

        unset($_SESSION[self::$name]);
    }
}
