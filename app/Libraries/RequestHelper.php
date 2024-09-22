<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Response;
use App\Libraries\UtilPack;

class RequestHelper
{
    private $utilPack;

    public function __construct()
    {
        $this->utilPack = new UtilPack();
    }

    /**
     * @param array $methods 허용할 메서드 목록 (예: ['post', 'get'])
     */
    public function onlyAllowedMethods(array $methods)
    {
        $request = service('request');
        $allowedMethods = array_map('strtoupper', $methods); // 메서드 소문자 변환

        if (!in_array($request->getMethod(), $allowedMethods)) {
            // 401 에러 반환
            $this->utilPack->sendResponse(405, 'ERROR', '잘못된 요청입니다.');
        }

        return true;
    }
}
