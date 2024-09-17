<?php
namespace App\Filters;

use CodeIgniter\HTTP\Cors as CorsService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    private ?CorsService $cors = null;

    public function __construct(array $config = [])
    {
        if ($config !== []) {
            $this->cors = new CorsService($config);
        }
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $this->createCorsService($arguments);

        if (!$this->cors->isPreflightRequest($request)) {
            return;
        }

        $response = service('response');

        // 프리플라이트 요청인지 확인
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request, $response);

            // Always adds `Vary: Access-Control-Request-Method` header for cacheability.
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
            return $response;
        }

        // 일반 요청에 대해 CORS 헤더 추가
        $this->addCorsHeaders($response);

        return $response;
    }

    private function createCorsService(?array $arguments): void
    {
        $this->cors ??= ($arguments === null) ? CorsService::factory()
            : CorsService::factory($arguments[0]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $this->createCorsService($arguments);

        if ($request->is('OPTIONS')) {
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
        }

        return $this->cors->addResponseHeaders($request, $response);
    }

    private function addCorsHeaders(ResponseInterface $response): void
    {
        // CORS 관련 헤더 추가
        // $response->setHeader('Access-Control-Allow-Origin', 'https://sample.com'); // 프론트엔드 도메인
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Allow-Credentials', 'true'); // 쿠키 포함 허용
    }
}
