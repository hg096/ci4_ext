<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }



    // public function getTiest()
    // {

    //     // 예시 데이터
    //     $data = [
    //         'id' => 1,
    //         'name' => 'John Doe',
    //         'email' => 'johndoe@example.com'
    //     ];

    //     // 데이터를 응답으로 반환
    //     return $this->respond($data);
    // }


}
