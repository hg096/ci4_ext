<?php

namespace App\Libraries;

use Config\Services;
use CodeIgniter\Model;

use CodeIgniter\HTTP\Response;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;
use CodeIgniter\I18n\Time;
use Exception;
use App\Models\UserModel;


class UtilPack
{
    private $secretKey;
    private $encryptionKey;
    private $ivKey;

    private $db;

    public function __construct()
    {
        // 환경 변수에서 비밀 키 가져오기
        $this->secretKey = getenv('JWT_SECRET_KEY');
        // AES-256 암호화 키 (32 바이트)
        $this->encryptionKey = getenv('JWT_ENCRYPTION_KEY');
        // 초기화 벡터 (16 바이트, 안전한 난수 사용 권장)
        $this->ivKey = getenv('JWT_IV_KEY');

        $this->db = \Config\Database::connect(); // DB 인스턴스 초기화
    }

    // jwt 생성
    public function generateJWT($user, $days = 0, $hours = 0)
    {
        $issuedAt = Time::now()->getTimestamp();

        if (!empty((int)$days)) {
            $expiryInSeconds = (int)$days * 86400; // 일(day) 단위를 초(second) 단위로 변환
        } else if (!empty((int)$hours)) {
            $expiryInSeconds = (int)$hours * 3600; // 시간(hours) 단위를 초(second) 단위로 변환
        } else {
            $expiryInSeconds = 7 * 86400; // 기본 7일
        }

        $expiration = (int)$issuedAt + (int)$expiryInSeconds;

        $domain = getenv('JWT_DOMAIN');

        if (empty($domain)) {
            // 현재 요청된 도메인 가져오기
            $request = service('request');
            $domain = $request->getServer('HTTP_HOST'); // 요청 도메인
        }

        $payload = [
            'iss' => $domain,           // 토큰 발급자
            'aud' => $domain,           // 토큰 대상자
            'iat' => $issuedAt,         // 발급 시간
            'exp' => $expiration,       // 만료 시간
            'uid' => !empty($user['uid']) ? $user['uid'] : $user['m_id'],        // 사용자 ID
            'ulv' => !empty($user['ulv']) ?  $user['ulv'] : $user['m_level'],    // 사용자 레벨
        ];

        // JSON으로 페이로드 변환
        $payloadJson = json_encode($payload);

        // AES-256-CBC로 페이로드 암호화
        $encryptedPayload = openssl_encrypt($payloadJson, 'AES-256-CBC', $this->encryptionKey, 0, $this->ivKey);

        return JWT::encode(['data' => $encryptedPayload], $this->secretKey, 'HS256');
    }

    // jwt 검증
    public function validateJWT($token)
    {
        try {

            $JWT_result = JWT::decode($token, new Key($this->secretKey, 'HS256'));

            // AES-256-CBC로 복호화
            $encryptedPayload = $JWT_result->data ?? null;

            if (!$encryptedPayload) {
                return ['status' => 'invalid', 'message' => '토큰이 유효하지 않습니다.'];
            }

            // 암호화된 페이로드 복호화
            $payloadJson = openssl_decrypt($encryptedPayload, 'AES-256-CBC', $this->encryptionKey, 0, $this->ivKey);

            if ($payloadJson === false) {
                return ['status' => 'invalid', 'message' => '토큰이 유효하지 않습니다.'];
            }

            return ['status' => 'success', 'data' => json_decode($payloadJson, true)];
        } catch (ExpiredException $e) {
            return ['status' => 'expired', 'message' => '토큰이 만료되었습니다.'];
        } catch (SignatureInvalidException $e) {
            return ['status' => 'invalid_signature', 'message' => '서명이 유효하지 않습니다.'];
        } catch (BeforeValidException $e) {
            return ['status' => 'invalid_before', 'message' => '토큰이 아직 유효하지 않습니다.'];
        } catch (Exception $e) {
            return ['status' => 'invalid', 'message' => '토큰이 유효하지 않습니다.'];
        }
    }

    // 엑세스 토큰 만료시 엑세스토큰, 리프레시 토큰 재발급
    // - 엑세스, 리프레시 쿠키 생성, 리프레시 토큰 디비 저장까지
    public function refreshAccessToken($accessToken, $refreshToken)
    {

        // 1. 엑세스 토큰 검증
        $accessValidation = $this->validateJWT($accessToken);

        // 엑세스 토큰이 만료되지 않은 경우
        if ($accessValidation['status'] === 'success') {
            return;
        }

        // 엑세스 토큰이 만료된 경우에만 리프레시 토큰 사용
        if ($accessValidation['status'] !== 'expired') {
            $this->sendResponse(401, 'OUT', '다시 로그인하세요.');
        }

        // 2. 리프레시 토큰 검증
        $refreshValidation = $this->validateJWT($refreshToken);

        // 리프레시 토큰 유효성 검사 실패
        if ($refreshValidation['status'] !== 'success') {
            $this->sendResponse(401, 'OUT', '다시 로그인하세요.');
        }

        // 리프레시 토큰의 사용자 ID 추출
        $userId = $refreshValidation["data"]->uid;

        // 3. 데이터베이스에서 사용자 조회
        $userModel = new UserModel();
        // $user = $userModel->find($userId);

        // 사용자를 ID로 조회
        $selectUser = "SELECT * from _member where m_id = ? AND m_is_use = 'Y' limit 1";
        $user = $userModel->select_DBV($selectUser, [$userId], "fn refreshAccessToken")[0];

        if (!$user) {
            $this->sendResponse(401, 'OUT', '다시 로그인하세요.');
        }

        // 데이터베이스에 저장된 리프레시 토큰과 요청된 토큰을 비교
        if ((string)$refreshToken !== (string)$user['m_token']) {
            $this->sendResponse(401, 'OUT', '다시 로그인하세요.');
        }

        // 4. 엑세스 토큰 재발급 (유효기간 1시간)
        $newAccessToken = $this->generateJWT(["uid" => $user["m_id"], "ulv" => $user["m_level"]], 0, 1); // 1시간 유효
        $this->makeCookie(getenv('ACCESS_TOKEN_NAME'), $newAccessToken, 0, 1);

        // 5. 새로운 리프레시 토큰 발급 및 저장
        $newRefreshToken = $this->generateJWT(["uid" => $user["m_id"], "ulv" => $user["m_level"]], 15); // 15일 유효
        $this->makeCookie(getenv('REFRESH_TOKEN_NAME'), $newRefreshToken, 15);
        $userModel->update($user['m_idx'], ['m_token' => $newRefreshToken]);

        return;
    }


    // 쿠키에 담긴 jwt 검증, 디코드 된 jwt 내용 리턴
    public function checkJWT()
    {
        $accessToken = $_COOKIE[getenv('ACCESS_TOKEN_NAME')] ?? null;

        $accessValidation = [];

        if (!empty($accessToken)) {
            $accessValidation = $this->validateJWT($accessToken);
            if (empty($accessValidation["data"]->uid)) {
                // 응답 반환 및 스크립트 종료
                $this->sendResponse(401, 'ATokenEnd', '다시 시도해주세요.');
            }
        }

        return $accessValidation;
    }

    // 쿠키 생성
    public function makeCookie($name, $value, $days = 0, $hours = 0, $isHttp = true)
    {

        $issuedAt = Time::now()->getTimestamp();

        // 쿠키 만료 시간 계산
        if ((int)$days < 0 || (int)$hours < 0) {
            // days 또는 hours가 0보다 작으면 쿠키를 즉시 만료시키도록 현재 시간보다 과거로 설정
            $expiration = $issuedAt - 3600; // 현재 시간보다 1시간 전으로 설정하여 즉시 만료
        } else {
            if (!empty((int)$days)) {
                $expiryInSeconds = (int)$days * 86400; // 일(day) 단위를 초(second) 단위로 변환
            } else if (!empty((int)$hours)) {
                $expiryInSeconds = (int)$hours * 3600; // 시간(hours) 단위를 초(second) 단위로 변환
            } else {
                $expiryInSeconds = 7 * 86400; // 기본 만료 기간: 7일
            }

            $expiration = (int)$issuedAt + (int)$expiryInSeconds;
        }

        setcookie($name, $value, [
            'expires' => $expiration, // 만료 시간
            'path' => '/',
            'domain' => '', // 필요시 도메인 설정
            // 'domain' => '.localhost', // 필요시 도메인 설정
            // 'domain' => '.ci4.localhost', // 필요시 도메인 설정
            'secure' => true, // HTTPS 사용 시에만 전송
            'httponly' => $isHttp, // JavaScript 접근 불가
            // 'samesite' => 'Lax', // CSRF 방지
            'samesite' => 'None', // 크로스 도메인 요청 허용, 앱api 사용시
        ]);
    }

    // 트랜젝션 시작
    public function startTransaction()
    {
        // 데이터베이스 연결 인스턴스 가져오기
        $this->db->transBegin();
    }

    // 트랜젝션 종료
    public function endTransaction(string $errorMessage = "처리에 실패했습니다.")
    {
        if ($this->db->transStatus() === false) {
            // 트랜잭션 롤백
            $this->db->transRollback();
            log_message('error', "!트랜잭션 에러! - | " . json_encode($this->db->error(), JSON_UNESCAPED_UNICODE));
            $this->sendResponse(401, 'N', (string)$errorMessage);
        } else {
            // 트랜잭션 커밋
            $this->db->transCommit();
        }
    }

    // 현재 트랜잭션에 사용 중인 DB 인스턴스를 반환
    public function getDb()
    {
        return $this->db;
    }

    // 프로세스 종료시
    public function sendResponse(int $statusCode, string $status, string $message, array $data = null, array $headers = null): void
    {
        $response = Services::response();

        $responseArray = [
            'status' => $status,
            'message' => $message,
        ];

        // 추가 데이터가 있을 경우, 응답 배열에 추가
        if (!empty($data)) {
            $responseArray['data'] = $data;
        }

        // 응답 헤더 설정 (필요한 경우)
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                $response->setHeader($name, $value);
            }
        }

        // 응답 설정
        $response
            ->setStatusCode($statusCode)
            ->setJSON($responseArray)
            ->send(); // 즉시 응답 반환 및 종료

        exit(); // 스크립트 종료

    }

    // 접근 권한 체크 && 토큰이 있어야만 접근가능한 페이지 체크
    public function checkAuthLevel(string $authData, array $conditions)
    {

        if (empty($authData)) {
            $this->sendResponse(404, 'N', "잘못된 요청입니다.");
        }

        // $conditions = [
        //     ['group' => 'u', 'level' => 2], // u_2 이상 허용
        //     ['group' => 'a', 'level' => 3], // a_3 이상 허용
        // ];

        // authData를 그룹과 레벨로 분리
        $authData_arr = explode("_", $authData);
        $authGroup = !empty($authData_arr[0]) ? $authData_arr[0] : "";
        $authLevel = !empty((int)$authData_arr[1]) ? (int)$authData_arr[1] : 0;

        // 조건 중 하나라도 만족하면 통과
        foreach ($conditions as $condition) {
            $group = $condition['group'];
            $level = (int)$condition['level'] ?? 0; // 레벨이 지정되지 않으면 0으로 설정

            if ($authGroup === $group && $authLevel >= $level) {
                return; // 조건 만족 시 함수 종료
            }
        }

        // 조건을 모두 만족하지 못한 경우
        $this->sendResponse(404, 'N', "잘못된 요청입니다.");
    }


}
