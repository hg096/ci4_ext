<?php

namespace App\Libraries;

// 싱글턴 클래스
class JWTHolder
{
    private static $instance;
    private $jwtData;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new JWTHolder();
        }
        return self::$instance;
    }

    public function setJWTData($data)
    {
        $this->jwtData = $data;
    }

    public function getJWTData()
    {
        return $this->jwtData;
    }
}