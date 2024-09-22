<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Top;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\UtilPack;


class ApiTopController extends ResourceController
{

    protected $utilPack;
    protected $JWTData;


    public function __construct()
    {
        // UtilPack 인스턴스 생성
        $this->utilPack = new UtilPack();

        // JWT 검증 및 데이터 저장
        $accessValidation = $this->utilPack->checkJWT();
        // checkJWT()에서 실패 시 이미 응답을 반환하고 종료했기 때문에, 추가적인 검증은 불필요
        if (!empty($accessValidation["data"])) {
            $this->JWTData = $accessValidation["data"];
        }

    }

}
