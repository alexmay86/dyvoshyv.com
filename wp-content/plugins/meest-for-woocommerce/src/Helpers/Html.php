<?php

namespace MeestShipping\Helpers;

class Html
{
    public static function hiddenTextInput($id, $name, $value = null): string
    {
        return '<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.$value.'">';
    }

    public static function textInput($id, $name, $value = null, $attr = []): string
    {
        $attr = self::arrayToAttr($attr);

        return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$attr.'>';
    }

    public static function select($id, $name, $value, $options): string
    {
        $str = '<select id="'.$id.'" name="'.$name.'">';
        foreach ($options as $key => $text) {
            $str .= '<option value="'.$key.'" '.($key == $value ? ' selected' : '').'>'.$text.'</option>';
        }

        return $str . '</select>';
    }

    public static function radioInput($id, $name, $text, $value = null, $checked = null): string
    {
        return '<label for="'.$id.'">'
            .'<input type="radio" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$checked.'>'
            .$text
            .'</label>';
    }

    public static function checkbox($id, $name, $checked, $value = 1): string
    {
        return '<input type="checkbox" id="'.$id.'" name="'.$name.'" value="'.$value.'"'.($checked == 1 ? ' checked' : '').'>';
    }

    public static function link($title, $page, $action = null, $id = null): string
    {
        $attr = '';
        if ($action !== null) {
            $attr .= '&action='.$action;
        }
        if ($id !== null) {
            $attr .= '&id='.$id;
        }

        return '<a href="?page='.$page.$attr.'">'.$title.'</a>';
    }

    public static function pageLink($title, $page, $action = null, $id = null): string
    {
        return '<form hidden name="'.$action.'_'.$id.'" method="post" action="?page='.$page.'&action='.$action.'&id='.$id.'"></form>'
            .'<a href="#" onclick="document.forms[\''.$action.'_'.$id.'\'].submit();">'.$title.'</a>';
    }

    public static function postLink($title, $action, $name = null): string
    {
        return '<form hidden name="'.$name.'" method="post" action="'.$action.'"></form>'
            .'<a href="#" onclick="document.forms[\''.$name.'\'].submit();">'.$title.'</a>';
    }

    private static function arrayToAttr($arr)
    {
        array_walk($arr, function (&$value, $key) {
            $value = $key.'="'.$value.'"';
        });

        return implode(' ', $arr);
    }
}
