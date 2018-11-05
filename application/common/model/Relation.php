<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/23
 * Time: 11:41
 */

namespace app\common\model;


use think\Model;

class Relation extends Model
{

    //根据微信原始id和分组id获取对应微信公众
    public function searchWxGhId($where)
    {
        return $this->alias('r_t')
            ->field('w_c.id,w_c.gh_id,w_c.nick_name,w_c.last_send_time,r_t.relation_name')
            ->leftJoin("wechat_account w_c",'r_t.id = w_c.relation_id')
            ->where($where)
            ->find();
    }
}