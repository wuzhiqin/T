<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/25
 * Time: 17:59
 */

namespace app\common\model;


use think\Exception;
use think\Model;

class Attach extends Model
{

    protected $append = ["type_name"];
    //归类获取器
    public function getTypeNameAttr($value,$data)
    {
        $status = [1=>'图片',2=>'超链接',3=>'小程序卡片',4=>'小程序图片',5=>'小程序超链'];
        return $status[$data['m_type']];
    }

    //获取内容预设列表
    public function content($hf_type,$page,$limit)
    {
        return $this->field('id,m_type,value,remark')
            ->where('hf_type',$hf_type)
            ->page($page)
            ->limit($limit)
            ->select()->toArray();
    }
    
}