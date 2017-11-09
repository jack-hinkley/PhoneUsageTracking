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
	Route::get('phonedata', 'PhonedataController@index');
	Route::get('phonedata/outstanding', 'PhonedataController@outstandingindex');
	Route::get('phonedata/details/{id}', 'PhonedataController@detailsindex');
	Route::get('phonedata/download/{date}/{local}', 'PhonedataController@download');
	Route::get('phonedata/downloadsearch/{search}', 'PhonedataController@downloadsearch');
	Route::get('phonedata/generate/{date}/{local}', 'PhonedataController@generate');

	Route::get('clients', 'ClientsController@index');
	Route::get('clients/create', 'ClientsController@createindex');
	Route::get('clients/edit/{id}', 'ClientsController@editindex');
	Route::get('clients/delete/{id}', 'ClientsController@delete');
	Route::get('clients/autocomplete',array('as'=>'autocomplete','uses'=>'ClientsController@autocomplete'));

	Route::get('members', 'MembersController@index');
	Route::get('members/create', 'MembersController@createindex');
	Route::get('members/create/{phone}', 'MembersController@createphoneindex');
	Route::get('members/edit/{id}', 'MembersController@editindex');
	Route::get('members/delete/{id}', 'MembersController@delete');
	Route::post('phonedata/search/{query}', 'PhonedataController@search');

	// AJAX CALLS
	Route::post('phonedata/get', 'PhonedataController@get');
	Route::post('phonedata/search', 'PhonedataController@search');
	Route::post('phonedata/upload', 'PhonedataController@upload');
	Route::post('phonedata/generate', 'PhonedataController@generatereport');

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
