<?php

namespace app\common\service;

use Firebase\JWT\JWT;
use think\facade\Config;

class Token
{

    //加密id
    static public function encode($user_id)
    {
        if (empty($user_id)) {
            return '';
        }

        try {
            $jwt = JWT::encode(["user_id" => $user_id], Config::get('config.jwt.key'), 'HS256');
            return (string)$jwt;
        } catch (\Exception $exception) {
            return '';
        }
    }

    //解密id
    static public function decode($token)
    {
        if (empty($token)) {
            return null;
        }

        try {
            $jwt = JWT::decode($token, Config::get('config.jwt.key'), ['HS256']);
            if ($jwt && intval($jwt->user_id) > 0) {
                return $jwt;
            }
            return null;
        } catch (\Exception $exception) {
            return null;
        }
    }

    //获取当前请求用户id，0表示未登录
    static public function getCurrentUserId()
    {
        $token = request()->header('X-App-Token');
        $jwt = self::decode($token);
        if (empty($jwt) || empty($jwt->user_id)) {
            return 0;
        }
        return intval($jwt->user_id);
    }

}