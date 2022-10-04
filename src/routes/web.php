<?php
Route::group(['namespace'=>'Yarm\Analyze\Http\Controllers','prefix'=> strtolower(config('yarm.sys_name')),'middleware'=>['web']], function (){

    //Route for Voyant tests
    Route::get('/tools/voyantTest', 'VoyantController@showCirrus')
        ->name('showCirrus');

    //Tools
    Route::get('/tools/toolsForm', 'VoyantController@toolsForm')
        ->name('toolsForm');

    //Test Frame
    Route::get('/test/testFrame', 'VoyantController@showTestFrame')
        ->name('testFrame');


});


