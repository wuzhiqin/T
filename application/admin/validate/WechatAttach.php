<?php
/**
 * 修改微信账号(上中下内容使用选择)(附表) 验证位置:admin/WechatAccount/updateAttach
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/23
 * Time: 14:36
 */

namespace app\admin\validate;


use think\Validate;
use app\common\model\WechatAttach as WechatAttachM;

class WechatAttach extends Validate
{
    protected $rule = [
        'id'                =>          'require|number|isWechatAttach',
        'use_head'         =>          'require|in:0,1',
        'use_foot'         =>          'require|in:0,1',
        'use_middle'       =>          'require|in:0,1',
        'use_href'         =>          'require|in:0,1',
        'head_type'        =>          'require|in:0,1',
        'foot_type'        =>          'require|in:0,1,2,3,4,5',
        'middle_type'      =>          'require|in:0,1,2,3,4,5',
        'head_value'       =>          'requireIf:href_type,1',
        'foot_value'       =>          'requireIf:foot_type,1|requireIf:foot_type,2',
        'middle_value'     =>          'requireIf:middle_type,1|requireIf:middle_type,2|requireIf:middle_type,3|requireIf:middle_type,4|requireIf:middle_type,5',
        'href_value'       =>          'requireIf:use_href,1'
    ];

    protected $message = [
        'id.isWechatAttach'            =>          '附表数据异常',
    ];

    //判断该附表是否存在该数据
    protected function isWechatAttach($value)
    {
        return (bool)(WechatAttachM::get($value));
    }

}