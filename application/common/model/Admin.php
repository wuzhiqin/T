<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/19
 * Time: 10:56
 */

namespace app\common\model;

use think\Exception;
use think\facade\Request;
use think\Model;

class Admin extends Model
{
    protected $autoWriteTimestamp = true;

    //获取器role_auth
    public function getRoleAuthAttr($value)
    {
        if (empty($value)){
            return $value;
        }
        $value = explode(',',$value);
        return $value;
    }

    //连表查询管理员的权限id集
    public static function accessRights($id)
    {
        try{
            return self::where('admin.id',$id)->leftJoin('admin_role a_r','admin.role_id = a_r.id')->find();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }
    }

    //记录最后一次登录数据
    public static function lastLogin($id)
    {
        return self::where('id',$id)->update(['last_login_time' => time(), 'last_login_ip' => Request::ip()]);
    }

    //查询所有数据
    public static function allUser($page,$limit)
    {
        return self::field('id,name,role_id,last_login_time,update_time,last_login_ip')
            ->page($page)
            ->limit($limit)
            ->order('id','desc')
            ->select();
    }

    //检测用户名是否已存在
    public static function isUser($user_name)
    {
        return self::where('name',$user_name)->find();
    }

}

