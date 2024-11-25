<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Top;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\UtilPack;
use App\Libraries\RequestHelper;
use App\Libraries\JWTHolder;

class ApiTopController extends ResourceController
{

    protected $utilPack;
    protected $requestHelper;
    protected $JWTData;

    public function __construct()
    {
        // UtilPack 인스턴스 생성
        $this->utilPack = new UtilPack();

        $this->requestHelper = new RequestHelper();

        // JWTHolder 싱글턴 인스턴스를 가져옴
        $jwtHolder = JWTHolder::getInstance();

        // JWT 데이터를 가져와서 사용
        $this->JWTData = $jwtHolder->getJWTData();

    }

}
