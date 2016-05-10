<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
  Route::get('images{all}', 'AuthController@getImages' )->where('all', '.*');
  Route::post('login', 'AuthController@login');
  Route::post('customers','AuthController@postCustomers');
  Route::get('products/{id?}','AuthController@getProducts');
  Route::get('product_options/{id?}','AuthController@getProduct_options');
  Route::get('order_states/{id?}','AuthController@getOrder_states');
  Route::get('product_option_values/{id?}','AuthController@getProduct_option_values');
  Route::get('product_features/{id?}','AuthController@getProduct_features');
  Route::get('product_feature_values/{id?}','AuthController@getProduct_feature_values');
  Route::get('combinations/{id?}','AuthController@getCombinations');
  Route::group(['middleware' => ['jwt.authdiff']], function() {
  	 Route::get('customers','AuthController@getCustomers');
   	 });
  Route::group(['middleware' => ['jwt.auth']], function() {
  		Route::get('addresses/{id?}','AuthController@getAddresses');
  		Route::post('addresses/edit/{id}','AuthController@editAddresses');
  		Route::post('addresses','AuthController@postAddresses');
  		Route::post('addresses/delete/{id}','AuthController@deleteAddresses');
  		Route::get('carts/{id?}','AuthController@getCarts');
  		Route::post('carts/edit/{id}','AuthController@editCarts');
  		Route::post('carts','AuthController@postCarts');
  		Route::post('carts/delete/{id}','AuthController@deleteCarts');
        Route::get('logout', 'AuthController@logout');
        Route::get('orders/{id?}','AuthController@getOrders');
        Route::post('orders/edit/{id}','AuthController@editOrders');
        Route::post('orders','AuthController@postOrders');
  		Route::post('orders/delete/{id}','AuthController@deleteOrders');
        Route::post('customers/edit','AuthController@editCustomers');
        Route::post('customers/delete','AuthController@deleteCustomers');
     });
