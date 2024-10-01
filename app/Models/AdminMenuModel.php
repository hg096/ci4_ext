<?php

namespace App\Models;

use App\Models\Top\TOPModel;

class AdminMenuModel extends TOPModel
{


    public function __construct()
    {
        parent::__construct();
    }

    protected $table = '_admin_menu'; // 테이블 이름
    protected $primaryKey = 'am_idx'; // 기본 키
    protected $useAutoIncrement = true; // 자동 증가 사용
    protected $returnType = 'array'; // 반환 형식
    protected $useSoftDeletes = false; // 소프트 삭제 사용 여부
    protected $allowedFields = ['am_am_idx', 'am_name', 'am_url', 'am_icon', 'am_auth', 'am_is_use', 'am_order']; // 수정 가능한 필드

    // 유효성 중복검사중 현재 데이터 제외 is_unique 사용하는 필드들
    protected $is_unique_arr = [];

    // 날짜 설정
    protected $useTimestamps = true;
    protected $createdField = 'am_regidate';
    protected $updatedField = 'am_editdate'; // 업데이트 필드
    protected $deletedField = 'am_deldate'; // 삭제 필드

    // 유효성 검사 규칙
    // 데이터 추가 시 사용될 규칙
    protected $validationRules = [
        'am_name' => 'required|max_length[50]',
        'am_url' => 'required',
        'am_icon' => 'required',
        'am_auth' => 'required',
        'am_is_use' => 'max_length[5]',
    ];

    // 유효성 검사 메시지
    protected $validationMessages = [
        'am_name' => [
            'required' => '메뉴명은 필수 입력 항목입니다.',
            'max_length' => '메뉴명은 50자까지 입력할 수 있습니다.',
        ],
        'am_url' => [
            'required' => '주소는 필수 입력 항목입니다.',
        ],
        'am_icon' => [
            'required' => '아이콘명은 필수 입력 항목입니다.',
        ],
        'am_auth' => [
            'required' => '권한은 필수 입력 항목입니다.',
        ],
    ];

    // 콜백 설정

    // 데이터 추가 전에 비밀번호를 해시하거나, 입력 데이터를 추가로 처리할 수 있습니다.
    protected $beforeInsert = [];
    // 데이터 추가 후 로그 기록이나 알림 발송 등의 작업을 처리할 수 있습니다.
    protected $afterInsert = [];
    // 업데이트 전 데이터를 검증하거나 변경 사항을 기록하는 등의 작업에 사용할 수 있습니다.
    protected $beforeUpdate = [];
    // 업데이트 후 추가 처리나 로그 기록이 필요할 때 사용됩니다.
    protected $afterUpdate = [];
    // 데이터 삭제 전 확인 작업을 하거나, 참조 무결성을 유지하기 위한 추가 작업을 수행할 수 있습니다.
    protected $beforeDelete = [];
    // 삭제 후의 후처리 작업이나 관련된 데이터를 정리하는 작업 등을 수행할 수 있습니다.
    protected $afterDelete = [];


}
