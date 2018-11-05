<?php
/**
 * 公众号头尾中内容设置（设置账号） 验证位置：admin/MaterialSetting/settingHeadTail
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/27
 * Time: 13:59
 */

namespace app\admin\validate;

use app\common\model\Attach;
use app\common\model\Relation;
use app\common\model\Type;
use think\Validate;

class SettingHeadTail extends Validate
{
    protected $rule = [
        'attach_id'       =>    'require|number|isAttach',
        'mode'             =>   'require|in:type,relation,custom',
        'type_id'          =>   'requireIf:mode,type|number|isType',
        'relation_id'     =>    'requireIf:mode,relation|number|isRelation',
        'custom'           =>   'requireIf:mode,custom',
    ];

    protected $message = [
        'attach_id.isAttach'           =>             '预设内容不存在，操作终止',
        'type_id.isType'                =>            '类型不存在，操作终止',
        'relation_id.isRelation'       =>            '分组不存在，操作终止',
    ];

    //验证预设内容是否存在
    protected function isAttach($value)
    {
        $attachM = new Attach();
        return (bool)($attachM->where('id',$value)->find());
    }

    //验证类型是否存在
    protected function isType($value)
    {
        $typeM = new Type();
        return (bool)($typeM->where('id',$value)->find());
    }

    //验证分组是否存在
    protected function isRelation($value)
    {
        $relationM = new Relation();
        return (bool)($relationM->where('id',$value)->find());
    }
}