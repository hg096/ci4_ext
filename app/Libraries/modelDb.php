<?php

namespace App\Libraries;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseConnection;
use Config\Services;
use App\Libraries\UtilPack;

// 데이터 관련 통합 함수
class modelDb
{

    private $utilPack;

    public function __construct()
    {
        $this->utilPack = new UtilPack();
    }


    public function insert_MDB(Model $model, array $data, string $message = "추가 에러")
    {
        // 데이터베이스 연결 인스턴스 가져오기
        $db = \Config\Database::connect();

        // 데이터 삽입 시도
        if (!$model->insert($data, true)) {

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 유효성 검사 실패 시 로그를 남기고 트랜잭션 롤백
            log_message('error', "insert_MDB SQL - $message: " . json_encode($model->errors() . " SQL : $lastQuery ", JSON_UNESCAPED_UNICODE));

            // 트랜잭션 롤백
            $db->transRollback();

            // 401 에러 반환
            $this->utilPack->sendResponse(401, 'N', '추가에 실패했습니다.');
        }

        // 성공 시 삽입된 레코드의 ID 반환
        return $model->insertID();
    }


    public function update_MDB(Model $model, int $id, array $data, $message = "수정 에러", array $where = [])
    {

        $updateValidationRules_org = $model->validationRules;

        $updateValidationRules = [];
        foreach ($data as $data_key => $data_value) {
            if (!empty($updateValidationRules_org[$data_key])) {
                $updateValidationRules[$data_key] = $updateValidationRules_org[$data_key];
            }
        }

        foreach ($model->is_unique_arr as $field) {
            if (isset($updateValidationRules[$field])) {
                $updateValStr = rtrim($updateValidationRules[$field], ']'); // 마지막에 있는 ']' 제거
                // 동적으로 is_unique 규칙을 추가하여 현재 레코드 제외
                $updateValidationRules[$field] = $updateValStr . ",{$model->primaryKey},{$id}]";
            }
        }

        if (!empty($updateValidationRules)) {
            // 유효성 검사 인스턴스 생성 및 규칙 설정
            $validation = Services::validation();
            $validation->setRules($updateValidationRules, $model->validationMessages);

            // 유효성 검사 수행
            if (!$validation->run($data)) {

                // 유효성 검사 실패 시 로그를 남기고 트랜잭션 롤백
                // log_message('error', "update_MDB validation - $message: " . json_encode($validation->getErrors(), JSON_UNESCAPED_UNICODE));

                // 직접 DB 연결을 가져와 오류 확인
                $db = \Config\Database::connect(); // DB 연결 인스턴스 가져오기
                // 트랜잭션 롤백
                if ($db->transStatus()) { // 트랜잭션이 이미 시작된 경우만 롤백
                    $db->transRollback();
                }

                // 401 에러 반환
                $this->utilPack->sendResponse(401, 'N', '이미 사용중인 정보입니다.');
            }
        }


        // 쿼리 빌더 생성
        $builder = $model->builder();

        // 사칙 연산 처리
        // 'points' => '+=10',  // points 필드에 10을 더함
        foreach ($data as $key => $value) {
            // 값이 사칙 연산을 포함한 문자열일 때 처리
            if (is_string($value) && preg_match('/^(\+|\-|\*|\/)=/', $value, $matches)) {
                // 기존 필드 값을 연산
                $builder->set($key, "{$key} {$matches[1]} " . $matches[2], false);
                unset($data[$key]);
            }
        }

        // 수동으로 updatedField를 현재 시간으로 설정
        if (!empty($model->updatedField)) {
            $data[$model->updatedField] = date('Y-m-d H:i:s');
        }


        // 남은 일반 필드 값들을 설정
        if (!empty($data)) {
            $builder->set($data);
        }

        // 쿼리 실행
        $result = null;
        if (!empty($where)) {
            $result = $builder->where($where)->update();
        } else {
            $result = $builder->where($model->primaryKey, $id)->update();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {
            // 직접 DB 연결을 가져와 오류 확인
            $db = \Config\Database::connect(); // DB 연결 인스턴스 가져오기

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 유효성 검사 실패 시 로그를 남기고 트랜잭션 롤백
            log_message('error', "update_MDB SQL - $message: " . json_encode($model->errors() . " SQL : $lastQuery ", JSON_UNESCAPED_UNICODE));

            // 트랜잭션 롤백
            if ($db->transStatus()) { // 트랜잭션이 이미 시작된 경우만 롤백
                $db->transRollback();
            }

            // 401 에러 반환
            $this->utilPack->sendResponse(401, 'N', '수정에 실패했습니다.');
        }

        // 쿼리 성공 시 결과 반환
        return $result;
    }


    // 개념 삭제에서 사용
    public function updateDel_MDB(Model $model, int $id, array $data, $message = "삭제 에러", array $where = [])
    {

        // 쿼리 빌더 생성
        $builder = $model->builder();

        // 사칙 연산 처리
        // 'points' => '+=10',  // points 필드에 10을 더함
        foreach ($data as $key => $value) {
            // 값이 사칙 연산을 포함한 문자열일 때 처리
            if (is_string($value) && preg_match('/^(\+|\-|\*|\/)=/', $value, $matches)) {
                // 기존 필드 값을 연산
                $builder->set($key, "{$key} {$matches[1]} " . $matches[2], false);
                unset($data[$key]);
            }
        }

        // 수동으로 deletedField를 현재 시간으로 설정
        if (!empty($model->deletedField)) {
            $data[$model->deletedField] = date('Y-m-d H:i:s');
        }

        // 남은 일반 필드 값들을 설정
        if (!empty($data)) {
            $builder->set($data);
        }

        // 쿼리 실행
        $result = null;
        if (!empty($where)) {
            $result = $builder->where($where)->update();
        } else {
            $result = $builder->where($model->primaryKey, $id)->update();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {
            // 직접 DB 연결을 가져와 오류 확인
            $db = \Config\Database::connect(); // DB 연결 인스턴스 가져오기

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 유효성 검사 실패 시 로그를 남기고 트랜잭션 롤백
            log_message('error', "updateDel_MDB SQL - $message: " . json_encode($model->errors() . " SQL : $lastQuery ", JSON_UNESCAPED_UNICODE));

            // 트랜잭션 롤백
            if ($db->transStatus()) { // 트랜잭션이 이미 시작된 경우만 롤백
                $db->transRollback();
            }

            // 401 에러 반환
            $this->utilPack->sendResponse(401, 'N', '삭제에 실패했습니다.');
        }

        // 쿼리 성공 시 결과 반환
        return $result;
    }


    public function delete_MDB(Model $model, int $id, $message = "삭제 에러", array $where = [])
    {
        // 쿼리 빌더 생성
        $builder = $model->builder();


        // 삭제 실행
        $result = null;
        if (!empty($where)) {
            $result = $builder->where($where)->delete();
        } else {
            $result = $builder->where($model->primaryKey, $id)->delete();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {
            // 직접 DB 연결을 가져와 오류 확인
            $db = \Config\Database::connect(); // DB 연결 인스턴스 가져오기

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 오류 로그 남기기
            log_message('error', "delete_MDB SQL - $message: " . json_encode($model->errors() . " SQL : $lastQuery ", JSON_UNESCAPED_UNICODE));

            // 트랜잭션 롤백
            if ($db->transStatus()) { // 트랜잭션이 이미 시작된 경우만 롤백
                $db->transRollback();
            }

            // 401 에러 반환
            $this->utilPack->sendResponse(401, 'N', '삭제에 실패했습니다.');
        }

        // 성공 시 결과 반환
        return $result;
    }


    public function select_MDB(string $sql, array $params = [], BaseConnection $db = null): array
    {
        // 데이터베이스 연결 인스턴스 가져오기 (기본: CodeIgniter의 기본 DB)
        $db = $db ?? \Config\Database::connect();
        $query = $db->query($sql, $params);

        return $query->getResultArray();
    }


    public function paging_MDB(
        string $sql,
        array $params = [],
        int $requestedPage = 1,
        int $perPage = 20,
        int $pageMakeCnt = 5,
        BaseConnection $db = null
    ): array {
        // 기본 설정: 페이지와 페이지당 데이터 수
        $limit = $perPage * $pageMakeCnt; // 조회할 데이터의 양은 $perPage의 $pageMakeCnt배

        if ($requestedPage === 1) {
            $offset = 0; // 실제 가져올 데이터의 시작 지점은 $perPage를 기준으로
        } else {
            $offset = ($requestedPage - 1) * $perPage; // 실제 가져올 데이터의 시작 지점은 $perPage를 기준으로
        }


        // SQL 쿼리 작성 (예시로 데이터가 있는 테이블명과 칼럼명을 설정)
        $sql = $sql . " LIMIT :limit OFFSET :offset";

        // 쿼리 파라미터 설정
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        // 데이터 조회
        $fullData = $this->select_MDB($sql, $params, $db);

        // 조회된 데이터를 $pageMakeCnt등분하기
        $segmentedData = array_chunk($fullData, $perPage); // $pageMakeCnt개의 세그먼트로 나누기

        // 필요한 세그먼트 데이터 가져오기 (빈 데이터가 있을 수 있음)
        $resultData = $segmentedData[0] ?? [];

        // $segmentedData의 0번째 요소를 제외하고 값이 있는 세그먼트의 개수 카운트
        $nonEmptySegmentsCount = count(array_filter(array_slice($segmentedData, 1), 'count'));

        // 결과 데이터와 값이 있는 세그먼트의 개수 반환
        return [
            'data' => $resultData,
            'nextPagingCnt' => $nonEmptySegmentsCount
        ];
    }
}
