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
Auth::routes();

Route::get('/', function () { return view('home'); });
Route::get('home', 'HomeController@index');
Route::get('logout', 'Auth\LoginController@logout');

Route::group(['middleware' => ['auth']], function() {
	//	USER ROUTES
	Route::get('phoneplan', 'PhoneplanController@index');
	Route::get('phoneplan/outstanding', 'PhoneplanController@outstandingindex');
	Route::get('phoneplan/details/{id}', 'PhoneplanController@detailsindex');
	Route::get('phoneplan/download/{date}/{local}', 'PhoneplanController@download');
	Route::get('phoneplan/downloadsearch/{search}', 'PhoneplanController@downloadsearch');

	Route::get('test', 'PhoneplanController@testing');

	Route::get('clients', 'ClientsController@index');
	Route::get('clients/create', 'ClientsController@createindex');
	Route::get('clients/edit/{id}', 'ClientsController@editindex');
	Route::get('clients/delete/{id}', 'ClientsController@delete');

	Route::get('members', 'MembersController@index');
	Route::get('members/create', 'MembersController@createindex');
	Route::get('members/create/{phone}', 'MembersController@createphoneindex');
	Route::get('members/edit/{id}', 'MembersController@editindex');
	Route::get('members/delete/{id}', 'MembersController@delete');

	// AJAX CALLS
	Route::post('phoneplan/get', 'PhoneplanController@get');
	Route::post('phoneplan/search', 'PhoneplanController@search');
	Route::post('phoneplan/upload', 'PhoneplanController@upload');
	Route::post('phoneplan/search/{query}', 'PhoneplanController@search');

	Route::post('clients/get', 'ClientsController@get');
	Route::post('clients/search', 'ClientsController@search');
	Route::post('clients/create', 'ClientsController@create');
	Route::post('clients/edit/{id}', 'ClientsController@edit');

	Route::post('members/get', 'MembersController@get');
	Route::post('members/getall', 'MembersController@getAll');
	Route::post('members/search', 'MembersController@search');
	Route::post('members/create', 'MembersController@create');
	Route::post('members/edit/{id}', 'MembersController@edit');
	
});
