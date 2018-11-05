<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

Route::group('admin', function () {

    //管理员相关接口
    Route::group('user', function () {
        Route::post('login', 'login');                  //管理员登陆
        Route::post('create', 'createUser');          //创建管理员
        Route::post('update', 'updateUser');          //修改管理员
        Route::post('pwd', 'password');                //修改密码
        Route::post('del', 'delUser');                 //删除管理员
        Route::get('one', 'user');                     //获取单个管理员
        Route::get('list', 'users');                   //获取所有管理员列表
    })->prefix('admin/admin/');

    //角色相关接口
    Route::group('role', function () {
        Route::post('create', 'createRole');            //创建角色
        Route::post('update', 'updateRole');            //修改角色
        Route::post('del', 'delRole');                  //删除角色
        Route::get('one', 'role');                      //获取单个角色
        Route::get('list', 'roles');                    //获取所有角色
    })->prefix('admin/admin/');

    //权限相关接口
    Route::group('auth', function () {
        Route::post('create', 'createAuth');           //创建权限
        Route::post('update', 'updateAuth');           //修改权限
        Route::post('del', 'delAuth');                 //删除权限
        Route::get('one', 'auth');                     //获取单个权限
        Route::get('list', 'auths');                   //获取所有权限
    })->prefix('admin/admin/');

    //微信账号管理接口
    Route::group('account', function () {
        Route::get('wechat', 'weChat');                             //根据id获取微信公众
        Route::get('wechats', 'weChats');                           //获取所有微信公众
        Route::post('update', 'updateWechat');                      //新增或修改微信公众
        Route::post('del', 'delWechat');                             //删除微信公众号
        Route::get('scan', 'scanAccount');                           //扫码号列表
        Route::get('invalid', 'invalidAccount');                    //查询所有无效账号
        Route::post('batch', 'batch');                               //批量添加微信账号
        Route::get('auth/callback', 'authCallback');                //添加账号授权回调
        Route::post('attach/update', 'updateAttach');               //修改微信公众上中下数据          （附表操作）
        Route::get('attach', 'weAttach');                            //根据id获取微信公众头尾中设置数据（附表操作）
        Route::post('token/reset', 'tokenReset');                    //重置token
        Route::get('setting/state', 'settingState');                //查询微信公众头中尾状态数据
    })->prefix('admin/WechatAccount/');

    //分类管理接口
    Route::group('type', function () {
        Route::post('create', 'createType');                        //创建分类
        Route::post('update', 'updateType');                        //修改分类
        Route::get('type', 'type');                                  //根据id获取类型
        Route::get('types', 'types');                                //获取所有分类
        Route::get('material', 'typeMaterial');                     //类型id对应素材账号 todo 待定
    })->prefix('admin/Type/');

    //分组管理接口
    Route::group('relation', function () {
        Route::post('create', 'createRelation');                        //创建
        Route::post('update', 'updateRelation');                        //修改
        Route::post('del', 'delRelation');                               //删除
        Route::get('relations', 'relations');                           //获取所有
        Route::post('remove/wechat', 'removeWxAccount');               //移除微信账号
        Route::get('gh/id', 'ghId');                                     //根据微信原始id和分组id获取对应微信公众
        Route::post('wechat/add', 'addWxAccount');                      //根据分组id添加账号
        Route::post('wechat/update', 'updateWechatRelation');          //微信公众账号变更分组
        Route::get('wechat', 'relationWc');                             //根据分组获取所有关联的微信公众号
    })->prefix('admin/Relation/');

    //素材账号管理
    Route::group('material/account', function () {
        Route::post('create', 'createMaterial');                  //创建
        Route::post('update', 'updateMaterial');                  //修改
        Route::post('del', 'delMaterial');                        //删除
        Route::get('one', 'one');                                 //获取单个（修改调用）
        Route::get('all', 'selectAllMaterial');                 //账号搜索和获取所有账号
        Route::post('batch', 'batch');                           //批量添加素材账号
    })->prefix('admin/MaterialAccount/');

    //素材设置接口
    Route::group('material/setting', function () {
        Route::post('gallery/add', 'addGallery');                 //添加图片
        Route::post('gallery/del', 'delGallery');                //删除图片
        Route::get('gallery', 'gallery');                         //获取图片列表
        Route::post('content/setup', 'settingContent');         //设置预设内容(上中下)
        Route::get('content/list', 'contentList');              //内容预设列表
        Route::post('content/del', 'delContent');               //删除预设内容设置
        Route::post('head/tail', 'settingHeadTail');            //公众号头尾中内容设置（设置账号）
        Route::post('use', 'useMaterialSetting');               //使用素材设置（是否使用素材）
    })->prefix('admin/MaterialSetting/');

    //发送信息
    Route::group('send', function () {
        Route::rule('send', 'send');                                            //单条
        Route::rule('batchSend', 'batchSend');                                //多条群发
        Route::get('task/list', 'sendTask');                                  //一键群发任务列表
        Route::post('task/del', 'sendTaskDel');                               //删除一键群发任务
        Route::get('details/list', 'sendDetails');                           //一键群发详情列
        Route::get('details/one', 'oneSendTask');                            //单次群发详情列
        Route::post('resend', 'Resend');                                      //重新发送
    })->prefix('admin/Send/');

    //未认证公众号登陆
    Route::group('mp', function () {
        Route::post('login/login', 'Mp/login');        //公众号模拟登陆
        Route::post('login/check', 'Mp/checkLogin');  //公众号模拟登陆后续，检测登陆状态
        Route::post('flow/single', "MpFlow/single");   //单个公众号流量主查询
        Route::post('flow/multi', "MpFlow/multi");    //公众号流量主聚合统计
    })->prefix('admin/');

    //清除数据相关接口
    Route::group('clean', function () {
        Route::post('news', 'cleanNews');           //清除无用素材
    })->prefix('admin/Clean/');

    //错误信息
    Route::group('error', function () {
        Route::get('list', 'errorList');                     //错误信息列
        Route::post('section/del', 'sectionDel');           //主键区间删除错误信息
        Route::post('del', 'errorDel');                      //删除错误信息
    })->prefix('admin/Error/');

    //素材采集管理
    Route::group('material',function (){
        Route::post('article/update', 'updateArticle');              //根据账号id更新素材
        Route::post('article/all/update', 'updateAllArticle');      //一键更新素材
    })->prefix('admin/Material/');

});


return [
//    'admin/:Action/:func' => 'admin/:Action/:func',
    'send/:Action/:func' => 'send/:Action/:func',
    'thirdparty/:Action/:func' => 'thirdparty/:Action/:func',
];