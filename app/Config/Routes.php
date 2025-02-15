<?php

use CodeIgniter\Router\RouteCollection;



use App\Controllers\Home;
use App\Controllers\Homeaa;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');


// $routes->get('api/user', 'Api\User::index');

$routes->get('/', [Home::class, 'index']);


// api 통신 url 노출되면 안되는 펑션은 private 처리
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {

    // $routes->get('user', 'User::index'); // index 를 사용 할 경우 이렇게 명시하기
    $routes->get('user/(:any)', 'User::$1');
    $routes->get('user/(:any)/(:any)', 'User::$1/$2');
    $routes->post('user/(:any)', 'User::$1');
    $routes->put('user/(:any)/(:any)', 'User::$1/$2');
    $routes->delete('user/(:any)/(:any)', 'User::$1/$2');


    $routes->get('adm/user/(:any)', 'UserAdm::$1');
    $routes->get('adm/user/(:any)/(:any)', 'UserAdm::$1/$2');
    $routes->post('adm/user/(:any)', 'UserAdm::$1');
    $routes->put('adm/user/(:any)/(:any)', 'UserAdm::$1/$2');
    $routes->delete('adm/user/(:any)/(:any)', 'UserAdm::$1/$2');

    $routes->get('adm/menu/(:any)', 'Menu::$1');
    $routes->get('adm/menu/(:any)/(:any)', 'Menu::$1/$2');
    $routes->post('adm/menu/(:any)', 'Menu::$1');
    $routes->put('adm/menu/(:any)/(:any)', 'Menu::$1/$2');
    $routes->delete('adm/menu/(:any)/(:any)', 'Menu::$1/$2');



});

