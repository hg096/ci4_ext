<?php

namespace App\Libraries;

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

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET_KEY'); // 환경 변수에서 비밀 키 가져오기
    }

    public function generateJWT($user, $days = 0, $hours = 0)
    {
        $issuedAt = Time::now()->getTimestamp();

        if (!empty((int)$days)) {
            $expiryInSeconds = (int)$days * 86400; // 일(day) 단위를 초(second) 단위로 변환
        } else if (!empty((int)$hours)) {
            $expiryInSeconds = (int)$hours * 3600; // 시간(hours) 단위를 초(second) 단위로 변환
        } else {
            $expiryInSeconds = 7 * 86400;
        }

        $expiration = (int)$issuedAt + (int)$expiryInSeconds;

        $domain = getenv('JWT_DOMAIN');

        if (empty($domain)) {
            // 현재 요청된 도메인 가져오기
            $request = service('request');
            $domain = $request->getServer('HTTP_HOST'); // 요청 도메인
        }

        $payload = [
            'iss' => $domain,  // 토큰 발급자
            'aud' => $domain,  // 토큰 대상자
            'iat' => $issuedAt,          // 발급 시간
            'exp' => $expiration,        // 만료 시간
            'uid' => $user['m_id'],        // 사용자 ID
            'ulv' => $user['m_level'],        // 사용자 레벨
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    private function validateJWT($token)
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
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

    public function refreshAccessToken($accessToken, $refreshToken)
    {

        // 1. 엑세스 토큰 검증
        $accessValidation = $this->validateJWT($accessToken);

        // 엑세스 토큰이 만료되지 않은 경우
        if ($accessValidation['status'] === 'success') {
            return [
                'status' => 'Y',
                'message' => '엑세스 토큰이 아직 유효합니다. 리프레시 토큰을 사용할 필요가 없습니다.'
            ];
        }

        // 엑세스 토큰이 만료된 경우에만 리프레시 토큰 사용
        if ($accessValidation['status'] !== 'expired') {
            return [
                'status' => 'N',
                'message' => '엑세스 토큰이 유효하지 않습니다. 다시 로그인하세요.'
            ];
        }

        // 2. 리프레시 토큰 검증
        $refreshValidation = $this->validateJWT($refreshToken);

        // 리프레시 토큰 유효성 검사 실패
        if ($refreshValidation['status'] !== 'success') {
            return [
                'status' => 'N',
                'message' => '리프레시 토큰이 유효하지 않거나 만료되었습니다.'
            ];
        }

        // 리프레시 토큰의 사용자 ID 추출
        $userId = $refreshValidation->uid;

        // 3. 데이터베이스에서 사용자 조회
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return [
                'status' => 'N',
                'message' => '사용자를 찾을 수 없습니다.'
            ];
        }

        // 데이터베이스에 저장된 리프레시 토큰과 요청된 토큰을 비교
        if ((string)$refreshToken !== (string)$user['m_token']) {
            return [
                'status' => 'N',
                'message' => '리프레시 토큰이 일치하지 않습니다.'
            ];
        }

        // 4. 엑세스 토큰 재발급 (유효기간 1시간)
        $newAccessToken = $this->generateJWT($user, 0, 1); // 1시간 유효

        // 5. 새로운 리프레시 토큰 발급 및 저장
        $newRefreshToken = $this->generateJWT($user, 15); // 15일 유효
        $userModel->update($user['m_idx'], ['m_token' => $newRefreshToken]);

        return [
            'status' => 'Y',
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken
        ];
    }


}
