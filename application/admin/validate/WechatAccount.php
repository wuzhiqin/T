<?php
/**
 * 新建微信账号  验证位置：admin/WechatAccount/updateWechat
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/22
 * Time: 14:02
 */

namespace app\admin\validate;

use think\Validate;
use app\common\model\Type as TypeM;
use app\common\model\Relation as RelationM;
use app\common\model\WechatAccount as WechatAccountM;

class WechatAccount extends Validate
{
    protected $rule = [
        'id'                 => 'require|number|isWechat',
        'material_type'     => 'require|number|isMaterial',
        'relation_id'       => 'require|number|isRelation',//relation_id为0时验证器报错，所以暂时注释 TODO
        'login_password'    => 'require',
    ];

    protected $message = [
        'id.isWechat'                    =>           '账号不存在，修改操作终止',
        'material_type.isMaterial'     =>            '该分类不存在',
        'relation_id.isRelation'       =>            '该分组不存在'
    ];

    //是否存在该账号
    protected function isWechat($value)
    {
        return (bool)(WechatAccountM::get($value));
    }

    //是否有该分类
    protected function isMaterial($value)
    {
        return (bool)(TypeM::get($value));
    }

    //是否有该分组
    protected function isRelation($value)
    {
        if ($value == 0) return true;
        return (bool)(RelationM::get($value));
    }

}