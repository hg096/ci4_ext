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
        $accessToken = $_COOKIE[getenv('ACCESS_TOKEN_NAME')] ?? null;
        $refreshToken = $_COOKIE[getenv('REFRESH_TOKEN_NAME')] ?? null;

        if (empty($accessToken)) {
            $this->utilPack->sendResponse(404, 'N', '인증방식이 잘못되었습니다.');
        }

        $this->utilPack->refreshAccessToken($accessToken, $refreshToken);

        if (strtoupper($request->getMethod()) !== 'GET') {
            // 트랜잭션 시작
            $this->utilPack->startTransaction();
        }
    }


    // 응답이 사용자에게 반환되기 전에 실행
    // !!!!! 컨트롤러에서 exit() 을 사용시에 after가 동작하지 않아서 유의
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 후처리 로직 필요 시 추가

        if (strtoupper($request->getMethod()) !== 'GET') {
            // 트랜잭션 종료 및 결과 처리
            $this->utilPack->endTransaction();
        }
    }
}
