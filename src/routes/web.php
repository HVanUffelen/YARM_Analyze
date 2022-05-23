<?php
Route::group(['namespace'=>'Yarm\Analyze\Http\Controllers','prefix'=>'dlbt','middleware'=>['web']], function (){

    //Route for Voyant tests
    Route::get('/tools/voyantTest', 'Tools\VoyantController@showCirrus')
        ->name('showCirrus');

    //Tools
    Route::get('/tools/toolsForm', 'Tools\VoyantController@toolsForm')
        ->name('toolsForm');

    //Test Frame
    Route::get('/test/testFrame', 'Tools\VoyantController@showTestFrame')
        ->name('testFrame');


});


