<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Libraries\UtilPack;
use Config\Services; // Services 클래스를 명시적으로 불러와야 할 수 있습니다.

class AuthFilter implements FilterInterface
{
    private $utilPack;

    public function __construct()
    {
        $this->utilPack = new UtilPack();
    }

    // 요청이 컨트롤러에 도달하기 전에 실행
    public function before(RequestInterface $request, $arguments = null)
    {
        // 토큰 가져오기

        $accessToken = $_COOKIE['A-Token'] ?? null;
        $refreshToken = $_COOKIE['R-Token'] ?? null;

        if (!$accessToken) {
            $this->utilPack->sendResponse(401, 'N', '엑세스 토큰이 제공되지 않았습니다.');
        }

        $result = $this->utilPack->refreshAccessToken($accessToken, $refreshToken);

        // 엑세스 토큰이 만료되었으나, 리프레시 토큰으로 재발급 성공
        if ($result['status'] === 'Y') {

            $this->utilPack->makeCookie('A-Token', $result['access_token'], 0, 1);
            $this->utilPack->makeCookie('R-Token', $result['refresh_token'], 15);

        } elseif ($result['status'] === 'OUT') {
            // 실패 응답 반환
            $this->utilPack->sendResponse(401, $result['status'], $result['message']);
        }
    }


    // 응답이 사용자에게 반환되기 전에 실행
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 후처리 로직 필요 시 추가
    }
}
