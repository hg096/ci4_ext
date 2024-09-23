<?php

namespace App\Models;

use App\Models\Top\TOPModel;

class UserAdminModel extends TOPModel
{


    public function __construct()
    {
        parent::__construct();
    }

    protected $table = '_member_admin'; // 테이블 이름
    protected $primaryKey = 'ma_idx'; // 기본 키
    protected $useAutoIncrement = true; // 자동 증가 사용
    protected $returnType = 'array'; // 반환 형식
    protected $useSoftDeletes = false; // 소프트 삭제 사용 여부
    protected $allowedFields = ['ma_id', 'ma_pass', 'ma_level', 'ma_nickname', 'ma_token', 'ma_is_use']; // 수정 가능한 필드

    // 유효성 중복검사중 현재 데이터 제외 is_unique 사용하는 필드들
    protected $is_unique_arr = ["ma_id", "ma_nickname"];

    // 날짜 설정
    protected $useTimestamps = true;
    protected $createdField = 'ma_regidate';
    protected $updatedField = 'ma_editdate'; // 업데이트 필드
    protected $deletedField = 'ma_deldate'; // 삭제 필드

    // 유효성 검사 규칙
    // 데이터 추가 시 사용될 규칙
    protected $validationRules = [
        'ma_id' => 'required|max_length[50]|is_unique[_member_admin.ma_id]',
        'ma_nickname' => 'required|max_length[50]|is_unique[_member_admin.ma_nickname]',
        'ma_pass' => 'required|min_length[8]|is_unique[_member_admin.ma_pass]',
        'ma_is_use' => 'max_length[2]',
        'ma_level' => 'max_length[5]',
    ];

    // 유효성 검사 메시지
    protected $validationMessages = [
        'ma_id' => [
            'is_unique' => '아이디가 이미 사용 중입니다.',
            'required' => '아이디는 필수 입력 항목입니다.',
        ],
        'ma_nickname' => [
            'is_unique' => '닉네임이 이미 사용 중입니다.',
            'required' => '닉네임은 필수 입력 항목입니다.',
        ],
        'm_pass' => [
            'required' => '비밀번호는 필수 입력 항목입니다.',
            'min_length' => '비밀번호는 최소 8자 이상이어야 합니다.',
        ],
    ];

    // 콜백 설정

    // 데이터 추가 전에 비밀번호를 해시하거나, 입력 데이터를 추가로 처리할 수 있습니다.
    protected $beforeInsert = ['hashPassword'];
    // 데이터 추가 후 로그 기록이나 알림 발송 등의 작업을 처리할 수 있습니다.
    protected $afterInsert = [];
    // 업데이트 전 데이터를 검증하거나 변경 사항을 기록하는 등의 작업에 사용할 수 있습니다.
    protected $beforeUpdate = ['hashPassword'];
    // 업데이트 후 추가 처리나 로그 기록이 필요할 때 사용됩니다.
    protected $afterUpdate = [];
    // 데이터 삭제 전 확인 작업을 하거나, 참조 무결성을 유지하기 위한 추가 작업을 수행할 수 있습니다.
    protected $beforeDelete = [];
    // 삭제 후의 후처리 작업이나 관련된 데이터를 정리하는 작업 등을 수행할 수 있습니다.
    protected $afterDelete = [];

    protected function hashPassword(array $data)
    {
        if (!empty($data['data']['ma_pass'])) {
            $data['data']['ma_pass'] = password_hash($data['data']['ma_pass'], PASSWORD_DEFAULT);
        }
        return $data;
    }
}
