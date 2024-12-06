<?php

namespace App\Models;

use App\Models\Top\TOPModel;

class ExModel extends TOPModel
{


    public function __construct()
    {
        parent::__construct();
    }

    protected $table = '_my_table'; // 테이블 이름
    protected $primaryKey = '_idx'; // 기본 키
    protected $useAutoIncrement = true; // 자동 증가 사용
    protected $returnType = 'array'; // 반환 형식
    protected $useSoftDeletes = false; // 소프트 삭제 사용 여부
    protected $allowedFields = [
        '_name',
        '_state',
        '_url',
        '_auth',
        '_is_use',
        '_email',
        '_phone',
        '_age',
        '_password',
        '_confirm_password',
        '_ip',
        '_file',
        '_datetime',
        '_cc',
        '_description',
        '_json_data'
    ]; // 수정 가능한 필드

    // 유효성 중복검사중 현재 데이터 제외 is_unique 사용하는 필드들
    // !!!!! is_unique 규칙은 가장 마지막으로 배치하기 update시 현재 행을 제외하고 검사하는 로직이 기본
    protected $is_unique_arr = ['_email'];

    // 날짜 설정
    protected $useTimestamps = true;
    protected $createdField = '_regidate'; // 추가 날짜필드
    protected $updatedField = '_editdate'; // 업데이트 날짜필드
    protected $deletedField = '_deldate'; // 삭제 날짜필드

    // 유효성 검사 규칙
    // 데이터 추가 시 사용될 규칙
    protected $validationRules = [
        '_name' => 'required|regex_match[/^[가-힣]+$/]|max_length[5]', // 한글만 허용
        '_state' => 'max_length[10]|alpha_space', // 알파벳 및 공백만 허용
        '_email' => 'required|valid_email|is_unique[_my_table._email]', // 이메일 형식 및 중복 확인
        '_url' => 'required|valid_url', // URL 형식 확인
        '_auth' => 'required|in_list[admin,user,guest]', // 특정 값만 허용
        '_is_use' => 'permit_empty|in_list[Y,N]', // Y, N 값만 허용, 비어 있어도 괜찮음
        '_phone' => 'required|regex_match[/^[0-9]{10,11}$/]', // 10~11자리 숫자만 허용
        '_age' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[150]', // 숫자, 1~150 범위
        '_password' => 'required|min_length[8]|max_length[20]', // 비밀번호 최소 8자, 최대 20자
        '_confirm_password' => 'matches[_password]', // 비밀번호 확인
        '_ip' => 'permit_empty|valid_ip', // 유효한 IP 형식만 허용
        '_file' => 'uploaded[_file]|max_size[_file,1024]|ext_in[_file,jpg,png,pdf]|mime_in[_file,image/jpeg,image/png]', // 파일 검증
        '_datetime' => 'required|valid_date[Y-m-d H:i:s]', // 유효한 날짜 형식 확인
        '_cc' => 'valid_credit_card', // 신용카드 번호 확인
        '_description' => 'permit_empty|alpha_numeric_punct|max_length[255]', // 설명
        '_json_data' => 'permit_empty|valid_json', // JSON 데이터 확인
    ];

    // 유효성 검사 메시지
    protected $validationMessages = [
        '_email' => [
            'is_unique' => '이미 사용 중인 이메일 주소입니다.',
        ],
        '_name' => [
            'required' => '이름은 필수 입력 항목입니다.',
            'max_length' => '이름은 최대 50자까지 입력할 수 있습니다.',
            'alpha_space' => '이름은 알파벳과 공백만 입력할 수 있습니다.',
        ],
        '_password' => [
            'differs' => '새 비밀번호는 기존 비밀번호와 달라야 합니다.',
        ],
        '_url' => [
            'required' => 'URL은 필수 입력 항목입니다.',
            'valid_url' => '유효한 URL 형식이 아닙니다.',
        ],
        '_auth' => [
            'required' => '권한은 필수 입력 항목입니다.',
            'in_list' => '권한 값은 admin, user, guest 중 하나여야 합니다.',
        ],
        '_phone' => [
            'required' => '전화번호는 필수 입력 항목입니다.',
            'regex_match' => '전화번호는 10~11자리 숫자여야 합니다.',
        ],
        '_confirm_password' => [
            'matches' => '비밀번호 확인이 일치하지 않습니다.',
        ],
        '_file' => [
            'uploaded' => '파일이 업로드되지 않았습니다.',
            'max_size' => '파일 크기는 최대 1MB를 초과할 수 없습니다.',
            'ext_in' => '파일 형식은 jpg, png, pdf만 가능합니다.',
            'mime_in' => '파일 형식은 JPEG 또는 PNG여야 합니다.',
        ],
        '_ip' => [
            'valid_ip' => '유효한 IP 형식이 아닙니다.',
        ],
        '_datetime' => [
            'valid_date' => '날짜 형식은 Y-m-d H:i:s 형식이어야 합니다.',
        ],
        '_cc' => [
            'valid_credit_card' => '유효한 신용카드 번호를 입력하세요.',
        ],
        '_json_data' => [
            'valid_json' => 'JSON 형식이 올바르지 않습니다.',
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
        if (!empty($data['data']['_password'])) {
            $data['data']['_password'] = password_hash($data['data']['_password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
}
