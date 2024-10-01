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



        // if (!$this->cors->isPreflightRequest($request)) {
        //     return;
        // }

        // $response = service('response');

        // // 프리플라이트 요청인지 확인
        // if ($this->cors->isPreflightRequest($request)) {
        //     $response = $this->cors->handlePreflightRequest($request, $response);

        //     // Always adds `Vary: Access-Control-Request-Method` header for cacheability.
        //     $response->appendHeader('Vary', 'Access-Control-Request-Method');
        //     return $response;
        // }

        // // 일반 요청에 대해 CORS 헤더 추가
        // $this->addCorsHeaders($response);
        // return $response;


        // Preflight 요청 처리 (OPTIONS 요청)
        if ($this->cors->isPreflightRequest($request)) {
            $response = service('response');
            $response = $this->cors->handlePreflightRequest($request, $response);
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
            return $response;
        }

        // 일반 요청에 대해 CORS 헤더 추가
        $this->addCorsHeaders(service('response'));

        return;
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

        // 일반 요청에도 CORS 헤더 추가
        $this->addCorsHeaders($response);

        if ($request->is('OPTIONS')) {
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
        }

        return $response;
        // return $this->cors->addResponseHeaders($request, $response);
    }

    private function addCorsHeaders(ResponseInterface $response): void
    {

        $origin = $_SERVER['HTTP_ORIGIN'] ?? 'https://localhost:3000'; // 요청된 도메인 동적으로 허용

        // CORS 관련 헤더 추가
        $response->setHeader('Access-Control-Allow-Origin', $origin); // 프론트엔드 도메인
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Allow-Credentials', 'true'); // 쿠키 포함 허용

        // 항상 Vary 헤더 추가 (캐시 관련 문제 방지)
        $response->appendHeader('Vary', 'Origin');
    }
}
