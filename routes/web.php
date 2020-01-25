<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//cargando clases

use App\Http\Middleware\ApiAuthMiddleware;

//rutas de prueba

Route::get('/', function () {
    return view('welcome');
});

Route::get('/prueba/{nombre?}', function ($nombre=null) {  
    $texto="<h2>Hola ".$nombre. "</h2>";
    return view('pruebas',array(
        'texto'=>$texto
    ));
});

Route::get('/animales','PruebasController@index');
Route::get('/orm','PruebasController@testOrm');

//rutas del api
/*
    get: conseguir datos o recursos
    post: guardar datos o recursos
    put: actualizar datos o recursos
    delete: eliminar datos o recursos
*/

//rutas de prueba
/*Route::get('/user','UserController@pruebas');
Route::get('/post','PostController@pruebas');
Route::get('/category','CategoryController@pruebas');*/

//rutas de controlador user
Route::post('/api/register','UserController@register');
Route::post('/api/login','UserController@login');
Route::put('/api/user/update','UserController@update')->middleware(ApiAuthMiddleware::class);
Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class); //usa un middleware para verificar si un usuario esta autenticado
Route::get('/api/user/avatar/{filename}','UserController@getImage');
Route::get('/api/user/detail/{id}','UserController@detail');

//rutas de controlador category
Route::resource('/api/category','CategoryController');

//rutas del controlador Post
Route::resource('/api/post','PostController');
Route::post('/api/post/upload','PostController@upload');
Route::get('/api/post/image/{filename}','PostController@getImage');
Route::get('/api/post/category/{id}','PostController@getPostsByCategory');
Route::get('/api/post/user/{id}','PostController@getPostsByUser');
