<?php
/**
 * 根据分组id添加账号 (验证数据)    验证位置:admin/Relation/addWxAccount
 * Created by PhpStorm.
 * User: w1248
 * Date: 2018/10/24
 * Time: 22:29
 */

namespace app\admin\validate;

use think\Validate;
use app\common\model\Relation as RelationM;
use app\common\model\WechatAccount as weChatAccountM;

class RelationWx extends Validate
{

    protected $rule = [
        'id'                   =>   'require|isRelation',
        'mode'                 =>   'require|in:type_id,gh_id',
        'type_id'              =>   'requireIf:mode,type_id|isType|relationType',
        'gh_id'                =>   'requireIf:mode,gh_id|relationGhId',
    ];

    protected $message = [
        'id.isRelation'                     =>           '该分组不存在，终止操作',
        'type_id.isType'                    =>           '该类型无账号可分组',
    ];

    //验证分组id可用性
    protected function isRelation($value)
    {
        $relationM = new RelationM();
        return (bool) ($relationM->where('id',$value)->find());
    }

    //查询该类型是否有账号可分组
    protected function isType($value)
    {
        $weChatAccountM = new weChatAccountM();
        global $verify_type_info;
        return boolval($weChatAccountM->where('material_type',$value)->where('verify_type_info',$verify_type_info)->count());
    }

    //通过分类id找出已有关联分组的账号
    protected function relationType($value)
    {
        $weChatAccountM = new weChatAccountM();
        global $verify_type_info;
        $res = $weChatAccountM->where('material_type',$value)->where('relation_id','>',0)->where('verify_type_info',$verify_type_info)->value('GROUP_CONCAT(gh_id)');
        return $res ? die(json_encode(['data'=>false,'message'=>'不予许添加,已有关联分组的账号：'.$res,'code'=>400])) : true;
    }

    //通过原始账号id找出已关联分组账号
    protected function relationGhId($value)
    {
        $weChatAccountM = new weChatAccountM();
        global $verify_type_info;
        $res = $weChatAccountM->where('gh_id','in',$value)->where('relation_id','>',0)->where('verify_type_info',$verify_type_info)->value('GROUP_CONCAT(gh_id)');
        return $res ? die(json_encode(['data'=>false,'message'=>'不予许添加,已有关联分组的账号：'.$res,'code'=>400])) : true;
    }
}