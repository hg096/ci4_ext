<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Api;

use App\Controllers\Top\ApiTopController;

use App\Models\AdminMenuModel;
use App\Libraries\RequestHelper;


class Menu extends ApiTopController
{

    private $menuModel;
    private $requestHelper;

    public function __construct()
    {
        // 부모 클래스의 생성자 호출
        parent::__construct();

        // 모델 인스턴스 생성
        $this->menuModel = new AdminMenuModel();
        $this->requestHelper = new RequestHelper();
    }

    public function index()
    {
        return;
        // return view('welcome_message');
    }


    public function myMenu()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['get']);
        // $this->utilPack->checkAuthLevel($this->JWTData->ulv, ['group' => 'a']);

        // 토큰의 사용자 ID 추출
        // $userId = $this->JWTData->uid;

        // 메뉴조회
        $selectMenu = "SELECT am1.*
        from _admin_menu am1
        where am1.am_is_use = 'Y'
        order by am1.am_order, am1.am_idx desc
        ";
        $menuData = $this->menuModel->select_DBV($selectMenu, [], "Menu/myMenu 1");



        $this->utilPack->makeCookie('TTT', "TT", 0, 3);


        // 성공 응답 반환
        $this->utilPack->sendResponse(200, 'Y', '조회되었습니다.', $menuData);
    }






}