<?php

class MockData {

    public static $oauth = true;
    public static $host = true;

    public static function load($resource) {
        if (!MockData::$host) return '';
        if (!MockData::$oauth) $resource = 'error';
        $dir = dirname(__FILE__);
        return json_decode(file_get_contents($dir."/data/{$resource}.json"), true);
    }
}
