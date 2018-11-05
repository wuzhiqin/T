<?php

namespace app\http\middleware;

use app\common\model\Admin;
use app\common\model\AdminAuth;
use app\common\service\Token;
use think\facade\Config;

/**
 * 验证token中间件
 * Class CheckToken
 * @package app\http\middleware
 */
class CheckToken
{
    public function handle($request, \Closure $next)
    {
        // 公开访问的路由直接返回
        $path = trim(trim($request->path()), '/');
        if ($this->isPublicRoute($path)) {
            return $next($request);
        }
        return $next($request);     //关闭权限验证
        // 非公开的路由需要验证登录
        global $userId;
        $userId = Token::getCurrentUserId();
        if ($userId <= 0) {
            // 提示登录
            return response_json(null, 401, '未登录');
        }

        //todo：验证权限
        $routesId = $this->accessRight($userId);                                           //获取用户权限id集
        $pathId = $this->getPathId($path);                                                 //获取当前访问路由id
        if (is_array($routesId)) if ($routesId['0'] == 007) return $next($request);      //通过则说明是超级管理员

        if (empty($routesId) || !in_array($pathId,$routesId)){
            //提示权限不够
            return response_json(false,403,'没有访问权限');
        }

        return $next($request);
    }

    // 检测是否公开路由
    public function isPublicRoute($path)
    {
        $publicRoute = Config::get('config.api_public_route');
        return is_array($publicRoute) && in_array($path, $publicRoute);
    }

    // 获取访问权限
    private function accessRight($user_id)
    {
        $adminM = new Admin();
        $accessRights = $adminM->accessRights($user_id);
        return $accessRights ? $accessRights['role_auth'] : die(json_encode(['data'=>false,'code'=>400,'message'=>'权限获取失败，终止操作']));
    }

    //获取当前路由id
    private function getPathId($path)
    {
        $adminAuthM = new AdminAuth();
        $adminAuth = $adminAuthM->field('id')->where('auth_url',$path)->find();
        return $adminAuth ? $adminAuth['id'] : $adminAuth;
    }
}
