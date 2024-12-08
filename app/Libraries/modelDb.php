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

        // 데이터 추가 시도
        if (!$model->insert($data, true)) {

            // DB 연결 인스턴스 가져오기
            $db = $this->utilPack->getDb();

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 유효성 검사 오류 가져오기
            $validationErrors = $model->errors();

            // 유효성 검사 오류 값들만 추출
            $errorMessages = $validationErrors ? array_values($validationErrors) : ["추가에 실패했습니다."];

            $error = $db->error()['message'] ?? "!!UNKNOWN ERROR!!"; // ['code' => SQL 상태 코드, 'message' => 에러 메시지]

            // 트랜잭션 롤백
            $db->transRollback();

            // 오류 로그 남기기
            log_message('critical', "\n\n !!!!! insert_MDB Error - | $message \n| LOG | " . json_encode($validationErrors, JSON_UNESCAPED_UNICODE) . "\n $error \n| SQL | $lastQuery \n\n");


            // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
            $uri = current_url();
            if (strpos($uri, '/api/') === 0) {
                $this->utilPack->sendResponse(404, 'N', implode(",", $errorMessages));
            } else {
                return false;
            }
        }

        // 성공 시 추가된 레코드의 ID 반환
        return $model->insertID();
    }


    // (!ex!) $where = ['column_name' => $userInput, ];
    // (!ex!) $where = ['( sam_idx = ? OR sam_type = ? ) and ( sam2_idx = ? OR sam2_type = ? )' => [1, 'ba', 13, 'qa'], ]
    public function update_MDB(Model $model, int $PKId, array $data, $message = "수정 에러", array $where = [])
    {
        $updateValidationRules = [];
        $db = $this->utilPack->getDb(); // DB 연결 인스턴스 가져오기

        // 수정 요청이 온것만 체크
        foreach ($data as $data_key => $data_value) {
            if (!empty($model->validationRules[$data_key])) {
                $updateValidationRules[$data_key] = $model->validationRules[$data_key];
            }
        }

        foreach ($model->is_unique_arr as $field) {
            if (isset($updateValidationRules[$field])) {
                $updateValStr = rtrim($updateValidationRules[$field], ']'); // 마지막에 있는 ']' 제거
                // 동적으로 is_unique 규칙을 추가하여 현재 레코드 제외
                $updateValidationRules[$field] = $updateValStr . ",{$model->primaryKey},{$PKId}]";
            }
        }

        // 쿼리 빌더 생성
        $builder = $model->builder();
        $validationData = [];
        $isPlusMinus = false;

        // 사칙 연산 처리
        // 'points' => '+=10',  // points 필드에 10을 더함
        foreach ($data as $key => $value) {
            // 값이 사칙 연산을 포함한 문자열일 때 처리
            if (is_string($value) && preg_match('/^(\+|\-|\*|\/)=/', $value, $matches)) {
                // 기존 필드 값을 연산
                $builder->set($key, "{$key} {$matches[1]} " . $matches[2], false);
                if (!empty($updateValidationRules[$key])) {
                    $isPlusMinus = true;
                }
                $validationData[$key] = [$matches[1], $matches[2]];

                unset($data[$key]);
            }
        }

        if ($isPlusMinus === true) {
            // 현재 데이터 조회 (사칙 연산 처리를 위한 기존 값 확인)
            $currentValues = $builder->select(array_keys($validationData))
                ->where($model->primaryKey, $PKId)
                ->get()
                ->getRowArray();

            if (!$currentValues) {
                $this->utilPack->sendResponse(404, 'N', '데이터가 존재하지 않습니다.');
            }

            foreach ($validationData as $key => $value) {
                // 값이 사칙 연산을 포함한 문자열일 때 처리
                if (is_array($value) === true) {

                    switch ($value[0]) {
                        case '+':
                            $newValue = (float)$currentValues[$key] + (float)$value[1];
                            break;
                        case '-':
                            $newValue = (float)$currentValues[$key] - (float)$value[1];
                            break;
                        case '*':
                            $newValue = (float)$currentValues[$key] * (float)$value[1];
                            break;
                        case '/':
                            $newValue = (float)$currentValues[$key] / (float)$value[1];
                            break;
                        default:
                            $newValue = (float)$currentValues[$key];
                            break;
                    }

                    if (is_float($newValue) && (int)$newValue === $newValue) {
                        $newValue = (int)$newValue;
                    }

                    // 연산 결과를 validationData 설정
                    $validationData[$key] = $newValue;
                }
            }
        }

        // 왼쪽의 값이 더 우선순위
        $validationData = $validationData + $data;

        if (!empty($updateValidationRules)) {
            // 유효성 검사 인스턴스 생성 및 규칙 설정
            $validation = Services::validation();
            $validation->setRules($updateValidationRules, $model->validationMessages);

            // 유효성 검사 수행
            if (!$validation->run($validationData)) {
                // $validationErrors = $validation->getErrors();

                // 유효성 검사 실패 시 로그를 남기고 트랜잭션 롤백
                log_message('critical', "\n\n !!!!! update_MDB validation - | $message | LOG | " . json_encode($validation->getErrors(), JSON_UNESCAPED_UNICODE) . "\n\n");

                // 트랜잭션 롤백
                $db->transRollback();

                // 유효성 검사 오류 가져오기
                $validationErrors = $model->errors();
                // 유효성 검사 오류 값들만 추출
                $errorMessages = $validationErrors ? array_values($validationErrors) : ["입력을 다시 확인해주세요."];

                // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
                $uri = current_url();
                if (strpos($uri, '/api/') === 0) {
                    $this->utilPack->sendResponse(404, 'N', implode(",", $errorMessages));
                } else {
                    return false;
                }
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

            $firstValue = array_values($where)[0];

            if (is_array($firstValue)) {
                foreach ($where as $condition => $bindings) {
                    $builder->where($condition, $bindings);
                }
                $result = $builder->update();
            } else {
                $result = $builder->where($where)->update();
            }
        } else {
            $result = $builder->where($model->primaryKey, $PKId)->update();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 오류 로그 남기기
            log_message('critical', "\n\n !!!!! update_MDB SQL - | $message | LOG | " . json_encode($model->errors(), JSON_UNESCAPED_UNICODE) . " | SQL | $lastQuery \n\n");

            // 트랜잭션 롤백
            $db->transRollback();

            // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
            $uri = current_url();
            if (strpos($uri, '/api/') === 0) {
                $this->utilPack->sendResponse(404, 'N', '수정에 실패했습니다.');
            } else {
                return false;
            }
        }

        // 쿼리 성공 시 결과 반환
        return $result;
    }


    // 개념 삭제에서 사용
    public function updateDel_MDB(Model $model, int $PKId, array $data, $message = "삭제 에러", array $where = [])
    {

        // 쿼리 빌더 생성
        $builder = $model->builder();
        $db = $this->utilPack->getDb(); // DB 연결 인스턴스 가져오기

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

            $firstValue = array_values($where)[0];

            if (is_array($firstValue)) {
                foreach ($where as $condition => $bindings) {
                    $builder->where($condition, $bindings);
                }
                $result = $builder->update();
            } else {
                $result = $builder->where($where)->update();
            }
        } else {
            $result = $builder->where($model->primaryKey, $PKId)->update();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 오류 로그 남기기
            log_message('critical', "\n\n !!!!! updateDel_MDB SQL - | $message | LOG | " . json_encode($model->errors(), JSON_UNESCAPED_UNICODE) . " | SQL | $lastQuery \n\n");

            // 트랜잭션 롤백
            $db->transRollback();

            // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
            $uri = current_url();
            if (strpos($uri, '/api/') === 0) {
                $this->utilPack->sendResponse(404, 'N', '삭제에 실패했습니다.');
            } else {
                return false;
            }
        }

        // 쿼리 성공 시 결과 반환
        return $result;
    }


    public function delete_MDB(Model $model, int $PKId, $message = "삭제 에러", array $where = [])
    {
        // 쿼리 빌더 생성
        $builder = $model->builder();
        $db = $this->utilPack->getDb(); // DB 연결 인스턴스 가져오기

        // 삭제 실행
        $result = null;
        if (!empty($where)) {

            $firstValue = array_values($where)[0];

            if (is_array($firstValue)) {
                foreach ($where as $condition => $bindings) {
                    $builder->where($condition, $bindings);
                }
                $result = $builder->delete();
            } else {
                $result = $builder->where($where)->delete();
            }
        } else {
            $result = $builder->where($model->primaryKey, $PKId)->delete();
        }

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$result) {

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 오류 로그 남기기
            log_message('critical', "\n\n !!!!! delete_MDB SQL - | $message | LOG | " . json_encode($model->errors(), JSON_UNESCAPED_UNICODE) . " | SQL | $lastQuery \n\n");

            // 트랜잭션 롤백
            $db->transRollback();

            // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
            $uri = current_url();
            if (strpos($uri, '/api/') === 0) {
                $this->utilPack->sendResponse(404, 'N', '삭제에 실패했습니다.');
            } else {
                return false;
            }
        }

        // 성공 시 결과 반환
        return $result;
    }


    public function select_MDB(string $sql, array $params = [], $message = "조회 에러"): array
    {
        // 데이터베이스 연결 인스턴스 가져오기 (기본: CodeIgniter의 기본 DB)
        $db = $this->utilPack->getDb(); // DB 연결 인스턴스 가져오기

        $query = $db->query($sql, $params);

        // 쿼리 실행 결과 확인 및 오류 로그 출력
        if (!$query) {

            // 마지막 실행된 쿼리 가져오기
            $lastQuery = (string)$db->getLastQuery(); // 실행된 마지막 쿼리 가져오기

            // 오류 로그 남기기
            log_message('critical', "\n\n !!!!! select_MDB SQL - | $message | LOG | " . json_encode($db->error(), JSON_UNESCAPED_UNICODE) . " | SQL | $lastQuery \n\n");

            // 트랜잭션 롤백
            $db->transRollback();

            // 요청이 /api/로 시작하는 경우와 그렇지 않은 경우 분기 처리
            $uri = current_url();
            if (strpos($uri, '/api/') === 0) {
                $this->utilPack->sendResponse(404, 'N', '조회에 실패했습니다.');
            } else {
                return [];
            }
        }

        if (empty($query->getResultArray())) {
            return [];
        }

        return $query->getResultArray();
    }


    public function paging_MDB(
        string $sql,
        array $params = [],
        string $message = "",
        int $requestedPage = 1,
        int $perPage = 20,
        int $pageMakeCnt = 5
    ): array {
        // 기본 설정: 페이지와 페이지당 데이터 수
        $limit = (int)$perPage * (int)$pageMakeCnt; // 조회할 데이터의 양은 $perPage의 $pageMakeCnt배

        if ((int)$requestedPage === 1) {
            $offset = 0; // 실제 가져올 데이터의 시작 지점은 $perPage를 기준으로
        } else {
            $offset = ((int)$requestedPage - 1) * (int)$perPage; // 실제 가져올 데이터의 시작 지점은 $perPage를 기준으로
        }

        // SQL 쿼리 작성 (예시로 데이터가 있는 테이블명과 칼럼명을 설정)
        $sql = $sql . " LIMIT :limit OFFSET :offset";

        // 쿼리 파라미터 설정
        $params['limit'] = (int)$limit;
        $params['offset'] = (int)$offset;

        // 데이터 조회
        $fullData = $this->select_MDB($sql, $params, $message);

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
