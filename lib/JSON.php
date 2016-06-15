<?php

class JSON
{
    /*
     * from json to mixed
     */
    public static function decode($json)
    {
        $json = str_replace(array("\t","\n","\r"), '', $json);
        //$json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $json);
        //$json = str_replace('\\\'', "'", $json);
        //$json = preg_replace('/(,)\s*}$/','}',$json);
        //$json = preg_replace('/,\s*([\]}])/m', '$1', $json);
        $result = json_decode($json, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        }
        $json = str_replace('\\\'', "'", $json);
        $result = json_decode($json, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        }
        $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $json);
        $result = json_decode($json, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $json = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
             '|[\x00-\x7F][\x80-\xBF]+'.
             '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
             '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
             '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
             '', $json);
            $result = json_decode($json, true);
        }
        return $result;
    }

    /*
     * from mixed to json
     */
    public static function encode($data)
    {
        $result = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($result) {
            return $result;
        } else {
            return JSON::encode2($data);
        }
    }

    public static function encode2($data)
    {
        if (is_null($data)) return 'null';
        if ($data === false) return 'false';
        if ($data === true) return 'true';
        if (is_scalar($data)) {
            $data = addslashes($data);
            $data = str_replace("\n", '\n', $data);
            $data = str_replace("\r", '\r', $data);
            $data = preg_replace('{(</)(script)}i', "$1'+'$2", $data);
            return '"' . $data . '"';
        }
        $isList = true;
        for ($i = 0, reset($data); $i < count($data); $i++, next($data))
            if (key($data) !== $i) {
                $isList = false;
                break;
            }
        $result = array();
        if ($isList) {
            foreach ($data as $v) $result[] = JSON::encode2($v);
            return '[' . join(',', $result) . ']';
        } else {
            foreach ($data as $k => $v)
                $result[] = JSON::encode2($k) . ':' . JSON::encode2($v);
            return '{' . join(',', $result) . '}';
        }
    }
}