<?php

use Illuminate\Support\Facades\Storage;
if (!function_exists('site_image')) {
    function site_image($file, $default = '') {
        if (!empty($file)) {
            return str_replace('\\', '/', Storage::disk(config('_constant.storage.disk'))->url($file));
        }
        return $default;
    }
}

if (!function_exists('escape_str')) {
    function escape_str($str, $like = TRUE) {
        if (is_array($str)) {
            foreach ($str as $key => $val)
            {
                $str[$key] = escape_str($val, $like);
            }
            return $str;
        }
        $str = addslashes($str);
        // escape LIKE condition wildcards
        if ($like === TRUE) {
            $str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
        }
        return $str;
    }
}
?>