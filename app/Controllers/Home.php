<?php

namespace App\Controllers;
use App\Controllers\Top\PageTopController;

class Home extends PageTopController
{
    public function index()
    {



        return view('welcome_message2');
    }



    // public function getTest()
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
