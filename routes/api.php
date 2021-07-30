<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return ['success' => true];
});

/* 인증 */
Route::group(['prefix' => 'auth'], function () {
    /* 중복 체크 */
    Route::group(['prefix' => 'exists'], function () {
        Route::get('/email/{email}', 'AuthController@exists_email');
        Route::get('/nickname/{nickname}', 'AuthController@exists_nickname');
    });

    /* 회원가입 */
    Route::post('/signup', 'AuthController@signup');
    Route::post('/signup/sns', 'AuthController@signup_sns');

    /* 로그인 */
    Route::post('/login', 'AuthController@login');
    Route::post('/login/sns', 'AuthController@login_sns');

    /* 초기데이터 구성 */
    Route::get('/check/init/{need}', 'AuthController@check_init')
        ->where(['need' => '(nickname|area|category|follow)']);
});

Route::group(['prefix' => 'user'], function () {
    Route::patch('/profile', 'UserController@update_profile');
    Route::post('/favorite_category', 'UserController@add_favorite_category');
    Route::delete('/favorite_category', 'UserController@remove_favorite_category');
    Route::post('/follow', 'UserController@follow');
    Route::delete('/follow', 'UserController@unfollow');
});

Route::get('/area', 'BaseController@area');
Route::get('/category', 'BaseController@categorgy');

Route::group(['prefix' => 'feed'], function () {
    //
});
