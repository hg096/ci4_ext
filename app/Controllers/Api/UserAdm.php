<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Api;

use App\Controllers\Top\ApiTopController;

use App\Models\UserAdminModel;


class UserAdm extends ApiTopController
{

    private $userAdmModel;

    public function __construct()
    {
        // 부모 클래스의 생성자 호출
        parent::__construct();

        // 모델 인스턴스 생성
        $this->userAdmModel = new UserAdminModel();
    }

    public function index()
    {

        return;
        // return view('welcome_message');
    }

    public function join()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['post']);

        // 요청 데이터 가져오기
        $data = [
            'ma_id' => $this->request->getPost('ma_id'),
            'ma_pass' => $this->request->getPost('ma_pass'), // 비밀번호는 모델 콜백에서 해시 처리됨
            'ma_nickname' => $this->request->getPost('ma_nickname'),
            'ma_token' => '', // 필요 시 토큰 생성 로직 추가
            'ma_is_use' => 'Y', // 활성화 상태 기본값
        ];

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 데이터 추가
        $userIdx = $this->userAdmModel->insert_DBV($data, "UserAdm/join 추가 ");

        // 추가 성공했는지 확인
        if (empty($userIdx)) {
            $this->utilPack->sendResponse(400, 'N', '회원 가입에 실패했습니다.');
        }

        // 사용자 데이터를 가져오기 위해 DB에서 다시 조회
        $user = $this->userAdmModel->find($userIdx);

        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userAdmModel->update_DBV($userIdx, ['ma_token' => $refreshToken], "UserAdm/join 리프레시 토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();

        // 성공 응답 반환
        $this->utilPack->sendResponse(
            200,
            'Y',
            '회원가입이 성공적으로 완료되었습니다.',
            null,
            [
                getenv('ACCESS_TOKEN_NAME') => $accessToken,
                getenv('REFRESH_TOKEN_NAME') => $refreshToken
            ]
        );
    }


    public function login()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['post']);

        // 요청 데이터 가져오기
        $ma_id = $this->request->getPost('ma_id');
        $ma_pass = $this->request->getPost('ma_pass');

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member_admin where ma_id = ? AND ma_is_use = 'Y' limit 1";
        $user = $this->userAdmModel->select_DBV($selectUser, [$ma_id], "UserAdm/login 1")[0];

        // 사용자 존재 여부 및 비밀번호 검증
        if (!$user || !password_verify($ma_pass, $user['ma_pass'])) {
            $this->utilPack->sendResponse(400, 'N', '아이디 또는 비밀번호가 올바르지 않습니다.');
        }

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userAdmModel->update_DBV($user['ma_idx'], ['ma_token' => $refreshToken], "UserAdm/login 리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();

        // 성공 응답 반환
        $this->utilPack->sendResponse(
            200,
            'Y',
            '로그인이 성공적으로 완료되었습니다.',
            null,
            [
                getenv('ACCESS_TOKEN_NAME') => $accessToken,
                getenv('REFRESH_TOKEN_NAME') => $refreshToken
            ]
        );
    }


    public function edits()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['post']);
        $this->utilPack->checkAuthLevel($this->JWTData->ulv, ['group' => 'a']);

        // 요청 데이터 가져오기
        $ma_nickname = $this->request->getPost('ma_nickname');
        $ma_pass = $this->request->getPost('ma_pass');

        // 토큰의 사용자 ID 추출
        $userId = $this->JWTData->uid;

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member_admin where ma_id = ? AND ma_is_use = 'Y' limit 1";
        $user = $this->userAdmModel->select_DBV($selectUser, [$userId], "UserAdm/edits 1")[0];


        if (empty($user['ma_idx'])) {
            $this->utilPack->sendResponse(400, 'N', '회원 조회에 실패했습니다.');
        }

        // // 사용자 존재 여부 및 비밀번호 검증
        // if (!password_verify($m_pass, $user['m_pass'])) {
        //     $this->utilPack->sendResponse(400, 'N', '비밀번호가 올바르지 않습니다.');
        // }
        $editUser = [];

        if (!empty($ma_pass)) {
            $editUser["ma_pass"] = $ma_pass;
        }

        $editUser["ma_nickname"] = $ma_nickname;

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userAdmModel->update_DBV(
            $user['ma_idx'],
            $editUser,
            "UserAdm/edits 회원 정보 업데이트"
        );

        // 토근에 담긴 회원 아이디를 수정했기 때문에 토큰 재발급
        // $user["ma_id"] = $ma_id;

        // 엑세스 토큰 생성 (유효기간 1시간)
        // $accessToken = $this->utilPack->generateJWT([$user, 0, 1);
        // $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        // $refreshToken = $this->utilPack->generateJWT($user, 15);
        // $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        // $this->userAdmModel->update_DBV($user['m_idx'], ['m_token' => $refreshToken], "UserAdm/edit 리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();

        // 성공 응답 반환
        $this->utilPack->sendResponse(200, 'Y', '회원수정이 성공적으로 완료되었습니다.');
    }


    public function logout()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['post']);
        $this->utilPack->checkAuthLevel($this->JWTData->ulv, ['group' => 'a']);

        // 토큰의 사용자 ID 추출
        $userId = $this->JWTData->uid;

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member_admin where ma_id = ? AND ma_is_use = 'Y' limit 1";
        $user = $this->userAdmModel->select_DBV($selectUser, [$userId], "UserAdm/logout 1")[0];

        if (empty($user['ma_idx'])) {
            $this->utilPack->sendResponse(400, 'N', '회원 조회에 실패했습니다.');
        }

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 엑세스 토큰 만료
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), "", -1);

        // 리프레시 토큰 만료
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), "", -1);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userAdmModel->update_DBV($user['ma_idx'], ['ma_token' => ""], "UserAdm/logout 로그아웃  리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();

        // 성공 응답 반환
        $this->utilPack->sendResponse(200, 'Y', '로그아웃이 성공적으로 완료되었습니다.');
    }




}