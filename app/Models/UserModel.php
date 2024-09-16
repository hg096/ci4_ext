<?php

namespace App\Models;


use CodeIgniter\Model;
use App\Libraries\modelDb; // 공통 디비 펑션

class UserModel extends Model
{
    protected $modelDb;

    public function __construct()
    {
        parent::__construct();
        $this->modelDb = new modelDb();
    }


    protected $table = '_member'; // 테이블 이름
    protected $primaryKey = 'm_idx'; // 기본 키
    protected $useAutoIncrement = true; // 자동 증가 사용
    protected $returnType = 'array'; // 반환 형식
    protected $useSoftDeletes = false; // 소프트 삭제 사용 여부
    protected $allowedFields = ['m_id', 'm_pass', 'm_hp', 'm_email', 'm_token', 'm_is_use', 'm_level']; // 수정 가능한 필드

    // 유효성 중복검사중 현재 데이터 제외 is_unique 사용하는 필드들
    protected $is_unique_arr = ["m_id", "m_email", "m_hp"];

    // 날짜 설정
    protected $useTimestamps = true;
    protected $createdField = 'm_regidate';
    protected $updatedField = ''; // 업데이트 필드 미사용
    protected $deletedField = ''; // 삭제 필드 미사용

    // 유효성 검사 규칙
    // 데이터 추가 시 사용될 규칙
    protected $validationRules = [
        'm_id' => 'required|max_length[50]|is_unique[_member.m_id]',
        'm_email' => 'required|valid_email|is_unique[_member.m_email]',
        'm_hp' => 'required|max_length[20]|is_unique[_member.m_hp]',
        'm_pass' => 'required|min_length[8]',
        'm_level' => 'max_length[5]',
    ];

    // 유효성 검사 메시지
    protected $validationMessages = [
        'm_id' => [
            'is_unique' => '아이디가 이미 사용 중입니다.',
            'required' => '아이디는 필수 입력 항목입니다.',
        ],
        'm_email' => [
            'is_unique' => '이메일이 이미 사용 중입니다.',
            'required' => '이메일은 필수 입력 항목입니다.',
            'valid_email' => '유효한 이메일 주소를 입력해 주세요.',
        ],
        'm_hp' => [
            'is_unique' => '전화번호가 이미 사용 중입니다.',
            'required' => '전화번호는 필수 입력 항목입니다.',
        ],
        'm_pass' => [
            'required' => '비밀번호는 필수 입력 항목입니다.',
            'min_length' => '비밀번호는 최소 8자 이상이어야 합니다.',
        ],
    ];

    // 콜백 설정

    // 데이터 삽입 전에 비밀번호를 해시하거나, 입력 데이터를 추가로 처리할 수 있습니다.
    protected $beforeInsert = ['hashPassword'];
    // 데이터 삽입 후 로그 기록이나 알림 발송 등의 작업을 처리할 수 있습니다.
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
        if (!empty($data['data']['m_pass'])) {
            $data['data']['m_pass'] = password_hash($data['data']['m_pass'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function insert_DBV(array $data, string $message)
    {
        return $this->modelDb->insert_MDB($this, $data, $message);
    }

    public function update_DBV(int $id, array $data, string $message, array $where = [])
    {
        return $this->modelDb->update_MDB($this, $id, $data, $message, $where);
    }
}
