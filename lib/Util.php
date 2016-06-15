<?php

class Util
{
    private static $_config = null;

    public static function config()
    {
        if (Util::$_config) return Util::$_config;
        Util::$_config = require(__DIR__ . '/../../../../../www/protected/config/main.php');
        return Util::$_config;
    }

    public static function _t($string, $params = array())
    {
        global $lang;

        global $strings;
        if (!$strings) {
            $strings = array();
            if (file_exists(__DIR__ . '/../../../../../www/protected/messages/'.$lang.'/install.php')) {
                $strings = include_once(__DIR__ . '/../../../../../www/protected/messages/'.$lang.'/install.php');
            //} else {
              //  return $string;
            }
        }
        $string = isset($strings[$string])? $strings[$string] : $string;

        if ($params) {
            foreach ($params as $key => $value) {
                $string = str_replace($key, $value, $string);
            }
        }

        return $string;
    }

    public static function getRS($max, $strconst = 0)
    {
        if (!$strconst) {
            $strconst = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));
        }

        $str = "";
        while (strlen($str) < $max) {
            $str .= strval($strconst[rand(0, sizeof($strconst) - 1)]);
        }

        return $str;
    }

    public static function get_avatar_path($file, $create_if_not_exits = false)
    {
        $config = Util::config();
        $first = strtolower(substr($file, 0, 1));
        $path = $config['params']['avatar_dir'] . DIRECTORY_SEPARATOR . $first;
        if ($create_if_not_exits && !file_exists($path)) {
            mkdir($path);
            chmod($path, $config['params']['avatar_dir_rights']);
        }
        return $path;
    }

    public static function get_root_url($client_id)
    {
        $config = Util::config();
        $first = substr($client_id, 0, 1);
        $second = substr($client_id, 1, 1);
        return $config['params']['settings_url'] . '/' . $first . '/' . $second;
    }

    public static function allowed_avatars()
    {
        return ['m', 'f', 'boy-001', 'boy-002', 'boy-003', 'boy-004', 'boy-005', 'boy-006', 'boy-007', 'boy-008', 'girl-001', 'girl-002', 'girl-003', 'girl-004', 'girl-005', 'girl-006', 'girl-007', 'girl-008'];
    }
    public static function allowed_os()
    {
        return ['macos', 'windows', 'android', 'ios'];
    }
}