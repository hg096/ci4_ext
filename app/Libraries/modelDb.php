<?php

namespace App\Libraries;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseConnection;

class modelDb
{
    /**
     * 데이터 업데이트 처리 메서드
     *
     * @param Model $model
     * @param int $id
     * @param array $data
     * @param array $where
     * @return mixed
     */
    public function update_MDB(Model $model, int $id, array $data, array $where = [])
    {
        // 현재 레코드를 제외한 유일성 검사 설정
        $updateValidationRules = $model->validationRules;
        foreach ($model->is_unique_arr as $field) {
            if (isset($updateValidationRules[$field])) {
                // 비어있는 값도 허용하도록 설정
                $updateValstr = str_replace("required", "permit_empty", $updateValidationRules[$field]);
                $updateValstr = str_replace("]", "", $updateValstr);

                // 동적으로 is_unique 규칙을 추가하여 현재 레코드 제외
                $updateValidationRules[$field] = $updateValstr . ",{$model->primaryKey},{$id}]";
            }
        }

        // 유효성 검사 수행
        if (!$model->validate($updateValidationRules, $data)) {
            return $model->errors(); // 유효성 검사 실패 시 에러 반환
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

        // 남은 일반 필드 값들을 설정
        if (!empty($data)) {
            $builder->set($data);
        }

        // 조건이 있는 경우와 없는 경우의 처리
        if (!empty($where)) {
            return $builder->where($where)->update();
        } else {
            return $builder->where($model->primaryKey, $id)->update();
        }
    }


    /**
     * 복잡한 쿼리를 실행하여 결과를 반환하는 함수
     *
     * @param string $sql 실행할 SQL 쿼리
     * @param array $params 바인딩할 파라미터 (옵션)
     * @param BaseConnection|null $db 데이터베이스 연결 인스턴스 (옵션)
     * @return array 쿼리 결과 배열
     */
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
