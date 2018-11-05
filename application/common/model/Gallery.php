<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/25
 * Time: 16:09
 */

namespace app\common\model;

use think\Model;

class Gallery extends Model
{

    /**
     * 获取所有图片列表
     * @param int $type 1-头   2-尾   3-中
     */
    public function gallery($type,$page,$limit)
    {
        return $this->field('id,pic_url')
            ->where('type',$type)
            ->page($page)
            ->limit($limit)
            ->order('id desc')
            ->select()->toArray();
    }

}