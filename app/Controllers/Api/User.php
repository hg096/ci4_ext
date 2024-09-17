<?php

// *** route 시에 폴더까지 잡아주기
namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Libraries\RequestHelper;
use App\Libraries\UtilPack;


class User extends ResourceController
{

    private $utilPack;
    private $userModel;

    public function __construct()
    {
        $this->utilPack = new UtilPack();
        // 모델 인스턴스 생성
        $this->userModel = new UserModel();
    }

    public function index(): string
    {

        return "AAA";
        // return view('welcome_message');
    }

    public function join()
    {
        // POST 요청만 허용
        RequestHelper::onlyAllowedMethods(['post']);

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
        $this->utilPack->handleTransactionStart($this->userModel);

        // 데이터 삽입
        $this->userModel->insert_DBV($data, " join 추가 ");

        // 방금 삽입된 레코드의 기본 키 값 가져오기
        $userIdx = $this->userModel->insertID();

        // 삽입이 성공했는지 확인
        if (!empty($userIdx)) {
            // 사용자 데이터를 가져오기 위해 DB에서 다시 조회
            $user = $this->userModel->find($userIdx);

            // 엑세스 토큰 생성 (유효기간 1시간)
            $accessToken = $this->utilPack->generateJWT($user, 0, 1);
            $this->utilPack->makeCookie('A-Token', $accessToken, 0, 1);

            // 리프레시 토큰 생성 (유효기간 15일)
            $refreshToken = $this->utilPack->generateJWT($user, 15);
            $this->utilPack->makeCookie('R-Token', $refreshToken, 15);

            // 리프레시 토큰을 m_token 필드에 업데이트
            $this->userModel->update_DBV($userIdx, ['m_token' => $refreshToken], "join 리프레시 토큰 업데이트");
        }

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->handleTransactionEnd($this->userModel);

        // 성공 응답 반환
        return $this->response
            ->setStatusCode(200)
            ->setHeader('A-Token', $accessToken)
            ->setHeader('R-Token', $refreshToken)
            ->setJSON([
                'status' => 'Y',
                'message' => "회원가입이 성공적으로 완료되었습니다."
            ]);
    }


    public function login()
    {
        // POST 요청만 허용
        RequestHelper::onlyAllowedMethods(['post']);

        // 요청 데이터 가져오기
        $m_id = $this->request->getPost('m_id');
        $m_pass = $this->request->getPost('m_pass');

        // 사용자를 ID로 조회
        $user = $this->userModel
            ->where([
                'm_id' => $m_id,
                'm_is_use' => 'Y',
            ])
            ->first();

        // 사용자 존재 여부 및 비밀번호 검증
        if (!$user || !password_verify($m_pass, $user['m_pass'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'N',
                'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
            ]);
        }

        // 트랜잭션 시작
        $this->utilPack->handleTransactionStart($this->userModel);


        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);
        $this->utilPack->makeCookie('A-Token', $accessToken, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);
        $this->utilPack->makeCookie('R-Token', $refreshToken, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $this->userModel->update_DBV($user['m_idx'], ['m_token' => $refreshToken], "login 리프레시토큰 업데이트");

        // 트랜잭션 종료 및 결과 처리
        $this->utilPack->handleTransactionEnd($this->userModel);

        // 성공 응답 반환
        return $this->response
            ->setStatusCode(200)
            ->setHeader('A-Token', $accessToken)
            ->setHeader('R-Token', $refreshToken)
            ->setJSON([
                'status' => 'Y',
                'message' => "로그인이 성공적으로 완료되었습니다."
            ]);
    }


    public function edits()
    {
        // POST 요청만 허용
        RequestHelper::onlyAllowedMethods(['post']);

        $resultJWT = $this->utilPack->checkJWT();

        // 요청 데이터 가져오기
        $m_id = $this->request->getPost('m_id');
        $m_email = $this->request->getPost('m_email');

        // 토큰의 사용자 ID 추출
        $userId = $resultJWT["data"]->uid;

        // 사용자를 ID로 조회
        $user = $this->userModel
            ->where([
                'm_id' => $userId,
                'm_is_use' => 'Y',
            ])
            ->first();


        if (!empty($user['m_idx'])) {

            // 트랜잭션 시작
            $this->utilPack->handleTransactionStart($this->userModel);

            // 리프레시 토큰을 m_token 필드에 업데이트
            $this->userModel->update_DBV(
                $user['m_idx'],
                [
                    'm_id' => $m_id,
                    'm_email' => $m_email
                ],
                "edits 회원 정보 업데이트"
            );
            // 트랜잭션 종료 및 결과 처리
            $this->utilPack->handleTransactionEnd($this->userModel);


            // 토근에 담긴 회원 아이디를 수정했기 때문에 토큰 재발급
            $user["m_id"] = $m_id;


            // 엑세스 토큰 생성 (유효기간 1시간)
            $accessToken = $this->utilPack->generateJWT($user, 0, 1);
            $this->utilPack->makeCookie('A-Token', $accessToken, 0, 1);

            // 리프레시 토큰 생성 (유효기간 15일)
            $refreshToken = $this->utilPack->generateJWT($user, 15);
            $this->utilPack->makeCookie('R-Token', $refreshToken, 15);


        } else {
            // 성공 응답 반환
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => 'N',
                    'message' => "회원 수정에 실패했습니다."
                ]);
        }



        // 성공 응답 반환
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status' => 'Y',
                'message' => "회원수정이 성공적으로 완료되었습니다."
            ]);
    }
}
