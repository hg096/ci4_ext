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
        // 헤더에서 토큰 가져오기
        $accessToken = $request->getHeaderLine('A-Token');
        $refreshToken = $request->getHeaderLine('R-Token');

        if (!$accessToken) {
            return Services::response()->setJSON([
                'status' => 'N',
                'message' => '엑세스 토큰이 제공되지 않았습니다.'
            ])->setStatusCode(401);
        }

        // 엑세스 토큰 유효성 검증
        $accessToken = str_replace('Bearer ', '', $accessToken);
        $result = $this->utilPack->refreshAccessToken($accessToken, $refreshToken);

        // 엑세스 토큰이 만료되었으나, 리프레시 토큰으로 재발급 성공
        if ($result['status'] === 'Y') {
            // 새로운 엑세스 토큰을 헤더로 추가
            header('A-Token: ' . $result['access_token']);
            header('R-Token: ' . $result['refresh_token']);
        } elseif ($result['status'] === 'N') {
            // 실패 응답 반환
            return Services::response()->setJSON($result)->setStatusCode(401);
        }
    }


    // 응답이 사용자에게 반환되기 전에 실행
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 후처리 로직 필요 시 추가
    }
}
