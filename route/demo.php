<?php

use think\Route;
use domain\base\behavior\UserAuthBehavior;
use domain\base\behavior\AdminAuthBehavior;
use domain\base\behavior\SystemAuthBehavior;

use domain\demo\port\controller\SampleController;
use domain\demo\port\controller\EsSampleController;

use think\Config;

//领域模块路由-普通路由不要放这里

//开放权限
Route::group('demo', function () {

    //只对测试开放-正式接口不要放这里
    if (Config::get('app_debug')) {

        //-新增
        Route::post('/sample/save', SampleController::class . '@sampleSave');
        //-新增-异步
        Route::post('/sample/job-save', SampleController::class . '@sampleJobSave');
        //-获取-列表
        Route::get('/sample/index', SampleController::class . '@sampleIndex');
        //-获取-详情
        Route::get('/sample/read/:id', SampleController::class . '@sampleRead', [], ['id' => '\d+']);
        //-更新-详情
        Route::put('/sample/update/:id', SampleController::class . '@sampleUpdate', [], ['id' => '\d+']);
        //-删除-详情
        Route::delete('/sample/delete/:id', SampleController::class . '@sampleDelete', [], ['id' => '\d+']);
        //-批量新增
        Route::post('/sample/batch-save', SampleController::class . '@sampleBatchSave');
        //-批量更新
        Route::put('/sample/batch-update', SampleController::class . '@sampleBatchUpdate');


        //-ES新增索引库
        Route::post('/es_sample/table/save', EsSampleController::class . '@esSampleTableSave');
        //-ES新增
        Route::post('/es_sample/save', EsSampleController::class . '@esSampleSave');
        //-ES获取-列表
        Route::get('/es_sample/index', EsSampleController::class . '@esSampleIndex');
        //-ES获取-详情
        Route::get('/es_sample/read/:id', EsSampleController::class . '@esSampleRead', [], ['id' => '\d+']);
        //-ES更新-详情
        Route::put('/es_sample/update/:id', EsSampleController::class . '@esSampleUpdate', [], ['id' => '\d+']);
        //-ES删除-详情
        Route::delete('/es_sample/delete/:id', EsSampleController::class . '@esSampleDelete', [], ['id' => '\d+']);
        //-ES批量新增
        Route::post('/es_sample/batch-save', EsSampleController::class . '@esSampleBatchSave');
        //-ES批量更新
        Route::put('/es_sample/batch-update', EsSampleController::class . '@esSampleBatchUpdate');
    }

});


//#旧接口 鉴权在 BaseController
//用户以上权限
Route::group('demo', function () {

});
//#


//用户以上权限
Route::group('demo', function () {

}, ['after_behavior' => UserAuthBehavior::class]);


//管理以上权限
Route::group('demo', function () {

}, ['after_behavior' => AdminAuthBehavior::class]);


//系统以上权限
Route::group('demo', function () {

}, ['after_behavior' => SystemAuthBehavior::class]);