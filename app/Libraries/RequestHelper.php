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
     * 노출되면 안되는 메서드일때는 빈 []로 보내기
     */
    public function onlyAllowedMethods(array $methods)
    {
        if (empty($methods)) {
            $this->utilPack->sendResponse(404, 'ERROR', '잘못된 요청입니다.');
        }

        $request = service('request');
        $allowedMethods = array_map('strtoupper', $methods); // 메서드 대문자 변환

        if (!in_array($request->getMethod(), $allowedMethods)) {
            // 404 에러 반환
            $this->utilPack->sendResponse(404, 'ERROR', '잘못된 요청입니다.');
        }

        return true;
    }
}
