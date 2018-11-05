<?php
/**
 * 使用素材设置（是否使用素材）  验证位置：admin/MaterialSetting/useMaterialSetting
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/27
 * Time: 15:50
 */

namespace app\admin\validate;

use think\Validate;
use app\common\model\Relation;
use app\common\model\Type;

class UseMaterialSetting extends Validate
{
    protected $rule = [
        'use_hf'            =>          'require|in:head,foot,middle,href',
        'use_head'          =>          'requireIf:use_hf,head|in:0,1',
        'use_foot'          =>          'requireIf:use_hf,foot|in:0,1',
        'use_middle'        =>          'requireIf:use_hf,middle|in:0,1',
        'use_href'          =>          'requireIf:use_hf,href|in:0,1',
        'href_value'        =>          'requireIf:use_href,1|url',
        'mode'              =>          'require|in:type,relation,custom',
        'type_id'           =>          'requireIf:mode,type|number|isType',
        'relation_id'       =>          'requireIf:mode,relation|number|isRelation',
        'custom'            =>          'requireIf:mode,custom',
    ];

    protected $message = [
        'type_id.isType'             =>        '类型不存在，操作终止',
        'relation_id.isRelation'    =>        '分组不存在，操作终止',
    ];

    //验证类型是否存在
    protected function isType($value)
    {
        $typeM = new Type();
        return (bool)($typeM->where('id', $value)->find());
    }

    //验证分组是否存在
    protected function isRelation($value)
    {
        $relationM = new Relation();
        return (bool)($relationM->where('id', $value)->find());
    }
}