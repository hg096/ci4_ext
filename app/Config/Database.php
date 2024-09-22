<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */

    public array $default = []; // 명시적으로 빈 배열로 초기화
    public array $tests = []; // 명시적으로 빈 배열로 초기화

    public function __construct()
    {
        parent::__construct();

        // 환경 변수는 생성자에서 불러옵니다.
        $this->default = [
            'DSN'          => '',
            'hostname'     => env('database.default.hostname', 'localhost'),
            'username'     => env('database.default.username', ''),
            'password'     => env('database.default.password', ''),
            'database'     => env('database.default.database', ''),
            'DBDriver'     => env('database.default.DBDriver', 'MySQLi'),
            'DBPrefix'     => env('database.default.DBPrefix', ''),
            'pConnect'     => false,
            // 'DBDebug'      => (ENVIRONMENT !== 'production'),
            'DBDebug'      => false,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_general_ci',
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'port'         => (int)env('database.default.port', 3306),
            'numberNative' => false,
            'dateFormat'   => [
                'date'     => 'Y-m-d',
                'datetime' => 'Y-m-d H:i:s',
                'time'     => 'H:i:s',
            ],
        ];

        /**
         * This database connection is used when running PHPUnit database tests.
         *
         * @var array<string, mixed>
         */
        $this->tests = [
            'DSN'         => '',
            'hostname'    => '127.0.0.1',
            'username'    => '',
            'password'    => '',
            'database'    => ':memory:',
            'DBDriver'    => 'SQLite3',
            'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
            'pConnect'    => false,
            'DBDebug'     => true,
            'charset'     => 'utf8',
            'DBCollat'    => '',
            'swapPre'     => '',
            'encrypt'     => false,
            'compress'    => false,
            'strictOn'    => false,
            'failover'    => [],
            'port'        => 3306,
            'foreignKeys' => true,
            'busyTimeout' => 1000,
            'dateFormat'  => [
                'date'     => 'Y-m-d',
                'datetime' => 'Y-m-d H:i:s',
                'time'     => 'H:i:s',
            ],
        ];

        // 테스트 환경에서는 기본 그룹을 'tests'로 설정
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }


    }
}
