<?php
/**
 * 添加图片验证
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/27
 * Time: 17:29
 */

namespace app\admin\validate;

use think\Validate;

class AddGallery extends Validate
{
    protected $rule = [
        'type'      =>      'require|number|in:1,2,3',
        'img'       =>      'require|isBase64',
    ];

    protected $message = [
      'img.isBase64'      =>      '请上传正确的base64图片',
    ];

    //判定是否为base64图片
    protected function isBase64($value)
    {
        preg_match('/^data:\s*image\/\w+;base64,/', $value, $result);
        return (bool)(!empty($result));
    }
}