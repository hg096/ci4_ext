<?php
namespace App\Models\Top;

use CodeIgniter\Model;
use App\Libraries\ModelDb; // 공통 DB 처리 라이브러리

class TOPModel extends Model
{
    protected $modelDb;

    public function __construct()
    {
        parent::__construct();
        $this->modelDb = new ModelDb();
    }

    public function insert_DBV(array $data, string $message)
    {
        return $this->modelDb->insert_MDB($this, $data, $message);
    }

    public function update_DBV(int $id, array $data, string $message, array $where = [])
    {
        return $this->modelDb->update_MDB($this, $id, $data, $message, $where);
    }

    public function updateDel_DBV(int $id, array $data, string $message, array $where = [])
    {
        return $this->modelDb->updateDel_MDB($this, $id, $data, $message, $where);
    }

    public function delete_DBV(int $id, array $data, string $message, array $where = [])
    {
        return $this->modelDb->delete_MDB($this, $id, $message, $where);
    }

    public function select_DBV(string $sql, array $params = [], $message = "")
    {
        return $this->modelDb->select_MDB($sql, $params, $message);
    }

    public function paging_DBV(string $sql, array $params, string $message, int $requestedPage, int $perPage, int $pageMakeCnt)
    {
        return $this->modelDb->paging_MDB($sql, $params, $message, $requestedPage, $perPage, $pageMakeCnt);
    }


}
