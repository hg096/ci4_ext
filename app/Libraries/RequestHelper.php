<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Response;

class RequestHelper
{
    /**
     * 요청 메서드가 지정한 메서드 목록에 포함되지 않는 경우 405 Method Not Allowed 반환
     *
     * @param array $methods 허용할 메서드 목록 (예: ['post', 'get'])
     */
    public static function onlyAllowedMethods(array $methods)
    {
        $request = service('request');
        $allowedMethods = array_map('strtolower', $methods); // 메서드 소문자 변환

        if (!in_array($request->getMethod(), $allowedMethods)) {
            // 응답 반환 및 스크립트 종료
            $response = service('response');
            $response->setStatusCode(405);
            $response->setJSON([
                'status' => 'error',
                'message' => 'Method Not Allowed'
            ]);
            $response->send(); // 응답을 보내고
            exit(); // 스크립트를 종료합니다.
        }
    }
}
