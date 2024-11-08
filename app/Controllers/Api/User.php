<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Api;

use App\Controllers\Top\ApiTopController;

use App\Models\UserModel;


class User extends ApiTopController
{

    private $userModel;

    public function __construct()
    {
        // 부모 클래스의 생성자 호출
        parent::__construct();

        // 모델 인스턴스 생성
        $this->userModel = new UserModel();

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
            'm_id' => $this->request->getPost('m_id'),
            'm_pass' => $this->request->getPost('m_pass'), // 비밀번호는 모델 콜백에서 해시 처리됨
            'm_email' => $this->request->getPost('m_email'),
            'm_hp' => $this->request->getPost('m_hp'),
            'm_token' => '', // 필요 시 토큰 생성 로직 추가
            'm_is_use' => 'Y', // 활성화 상태 기본값
        ];

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 데이터 추가
        $userIdx = $this->userModel->insert_DBV($data, " join 추가 ");

        // 추가 성공했는지 확인
        if (empty($userIdx)) {
            $this->utilPack->sendResponse(400, 'N', '회원 가입에 실패했습니다.');
        }

        // 사용자 데이터를 가져오기 위해 DB에서 다시 조회
        $user = $this->userModel->find($userIdx);

        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userModel->update_DBV($userIdx, ['m_token' => $refreshToken], "join 리프레시 토큰 업데이트");

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
        $m_id = $this->request->getPost('m_id');
        $m_pass = $this->request->getPost('m_pass');

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member where m_id = ? AND m_is_use = 'Y' limit 1";
        $user = $this->userModel->select_DBV($selectUser, [$m_id], "User/login 1")[0];

        // 사용자 존재 여부 및 비밀번호 검증
        if (!$user || !password_verify($m_pass, $user['m_pass'])) {
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
        $this->userModel->update_DBV($user['m_idx'], ['m_token' => $refreshToken], "login 리프레시토큰 업데이트");

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
        $this->utilPack->checkAuthLevel($this->JWTData->ulv, ['group' => 'u']);

        // 요청 데이터 가져오기
        $m_id = $this->request->getPost('m_id');
        $m_email = $this->request->getPost('m_email');
        $m_pass = $this->request->getPost('m_pass');
        $m_pass_new = $this->request->getPost('m_pass_new');

        // 토큰의 사용자 ID 추출
        $userId = $this->JWTData->uid;

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member where m_id = ? AND m_is_use = 'Y' limit 1";
        $user = $this->userModel->select_DBV($selectUser, [$userId], "User/edits 1")[0];

        if (empty($user['m_idx'])) {
            $this->utilPack->sendResponse(400, 'N', '회원 조회에 실패했습니다.');
        }

        $editUser = [];

        // 사용자 존재 여부 및 비밀번호 검증
        if (!empty($m_pass) || !empty($m_pass_new)) {
            if (!password_verify($m_pass, $user['m_pass'])) {
                $this->utilPack->sendResponse(400, 'N', '비밀번호가 올바르지 않습니다.');
            }

            $editUser["m_pass"] = $m_pass_new;
        }

        $editUser["m_id"] = $m_id;
        $editUser["m_email"] = $m_email;

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 업데이트
        $this->userModel->update_DBV(
            $user['m_idx'],
            $editUser,
            "edits 회원 정보 업데이트"
        );

        // 토근에 담긴 회원 아이디를 수정했기 때문에 토큰 재발급
        $user["m_id"] = $m_id;

        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userModel->update_DBV($user['m_idx'], ['m_token' => $refreshToken], "edit 리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();

        // 성공 응답 반환
        $this->utilPack->sendResponse(200, 'Y', '회원수정이 성공적으로 완료되었습니다.');
    }


    public function logout()
    {
        // 요청만 허용
        $this->requestHelper->onlyAllowedMethods(['post']);
        $this->utilPack->checkAuthLevel($this->JWTData->ulv, ['group' => 'u']);

        // 토큰의 사용자 ID 추출
        $userId = $this->JWTData->uid;

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member where m_id = ? AND m_is_use = 'Y' limit 1";
        $user = $this->userModel->select_DBV($selectUser, [$userId], "User/logout 1")[0];

        if (empty($user['m_idx'])) {
            $this->utilPack->sendResponse(400, 'N', '회원 조회에 실패했습니다.');
        }

        // 트랜잭션 시작
        $this->utilPack->startTransaction();

        // 엑세스 토큰 만료
        $this->utilPack->makeCookie(getenv('ACCESS_TOKEN_NAME'), "", -1);

        // 리프레시 토큰 만료
        $this->utilPack->makeCookie(getenv('REFRESH_TOKEN_NAME'), "", -1);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userModel->update_DBV($user['m_idx'], ['m_token' => ""], "logout 로그아웃  리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->endTransaction();


        // 성공 응답 반환
        $this->utilPack->sendResponse(200, 'Y', '로그아웃이 성공적으로 완료되었습니다.');
    }




}