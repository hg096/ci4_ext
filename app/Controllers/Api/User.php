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

    public function __construct()
    {
        $this->utilPack = new UtilPack();
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


        // 모델 인스턴스 생성
        $userModel = new UserModel();

        // 요청 데이터 가져오기
        $data = [
            'm_id' => $this->request->getPost('m_id'),
            'm_pass' => $this->request->getPost('m_pass'), // 비밀번호는 모델 콜백에서 해시 처리됨
            'm_email' => $this->request->getPost('m_email'),
            'm_hp' => $this->request->getPost('m_hp'),
            'm_token' => '', // 필요 시 토큰 생성 로직 추가
            'm_is_use' => 'Y', // 활성화 상태 기본값
        ];


        // 모델의 save() 메서드로 데이터 저장
        if ($userModel->insert_DBV($data)) {
            // 방금 삽입된 레코드의 기본 키 값 가져오기
            $userIdx = $userModel->insertID();

            // 사용자 데이터를 가져오기 위해 DB에서 다시 조회
            $user = $userModel->find($userIdx);

            // 엑세스 토큰 생성 (유효기간 1시간)
            $accessToken = $this->utilPack->generateJWT($user, 0, 1);

            // 리프레시 토큰 생성 (유효기간 15일)
            $refreshToken = $this->utilPack->generateJWT($user, 15);

            // 리프레시 토큰을 m_token 필드에 업데이트
            $userModel->update_DBV($userIdx, ['m_token' => $refreshToken]);

            // 성공 응답 반환
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Access-Token', $accessToken)
                ->setHeader('Refresh-Token', $refreshToken)
                ->setJSON([
                    'status' => 'Y',
                    'message' => '회원가입이 성공적으로 완료되었습니다.'
                ]);

        } else {
            // 유효성 검사 실패 시 모델에서 에러 메시지 반환
            return $this->failValidationErrors($userModel->errors());
        }
    }

    public function login()
    {
        // POST 요청만 허용
        RequestHelper::onlyAllowedMethods(['post']);

        // 모델 인스턴스 생성
        $userModel = new UserModel();

        // 요청 데이터 가져오기
        $m_id = $this->request->getPost('m_id');
        $m_pass = $this->request->getPost('m_pass');

        // 사용자를 ID로 조회
        $user = $userModel->where('m_id', $m_id)->first();

        // 사용자 존재 여부 및 비밀번호 검증
        if (!$user || !password_verify($m_pass, $user['m_pass'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'N',
                'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
            ]);
        }

        // 엑세스 토큰 생성 (유효기간 1시간)
        $accessToken = $this->utilPack->generateJWT($user, 0, 1);

        // 리프레시 토큰 생성 (유효기간 15일)
        $refreshToken = $this->utilPack->generateJWT($user, 15);

        // 리프레시 토큰을 m_token 필드에 업데이트
        $userModel->update_DBV($user['m_idx'], ['m_token' => $refreshToken]);

        // 성공 응답 반환
        return $this->response
            ->setStatusCode(200)
            ->setHeader('Access-Token', $accessToken)
            ->setHeader('Refresh-Token', $refreshToken)
            ->setJSON([
                'status' => 'Y',
                'message' => '로그인이 성공적으로 완료되었습니다.'
            ]);
    }


}
