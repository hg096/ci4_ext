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
    $routes->get('user/(:any)', 'User::$1');
    $routes->get('user/(:any)/(:any)', 'User::$1/$2');
    $routes->post('user/(:any)', 'User::$1');
    $routes->put('user/(:any)/(:any)', 'User::$1/$2');
    $routes->delete('user/(:any)/(:any)', 'User::$1/$2');


});

