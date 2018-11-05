<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/19
 * Time: 17:43
 */

namespace app\common\model;


use think\Model;

class AdminRole extends Model
{
    //获取器role_auth
    public function getRoleAuthAttr($value)
    {
        $value = explode(',',$value);
        return $value;
    }

}