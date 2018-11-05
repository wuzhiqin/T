<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/23
 * Time: 15:33
 */

namespace app\common\model;

use think\Exception;
use think\Model;

class Send extends Model
{
    protected $pk = 'id';

    protected static function init()
    {
        //TODO:初始化内容
    }

    protected $append = ['status_msg'];

    //状态获取器
    public function getStatusMsgAttr($value, $data)
    {
        $status = [0 => '待发送', 1 => '正在发送', 2 => '待检查', 200 => '发送完成', 500 => '发送失败'];
        return $status[$data['status']];
    }


    //根据sendId查看一键群发详情  $verify_type -1 未认证   0 - 认证
    public function taskDetails($sendId, $page, $limit,$verify_type = '')
    {
        $where = $verify_type ? array(['verify_type','=',$verify_type]) : 1;
        try {
            return $this->alias('s')
                ->where('s.send_id', '=', $sendId)
                ->where('s.status', '<>', 200)
                ->leftjoin('wechat_account wa', 's.wechat_id = wa.id')
                ->field('s.id,s.wechat_id,wa.nick_name as wechat_name,wa.scan_account,wa.gh_id,s.msg_id,s.status,s.return_msg')
                ->where($where)
                ->order('s.create_time', 'desc')
                ->page($page)
                ->limit($limit)
                ->select()->toArray();
        } catch (Exception $e) {
            die(json_encode(['data' => false, 'code' => 500, 'message' => $e->getMessage()]));
        }
    }

    //根据sendId统计群发详情条数 $verify_type -1 未认证   0 - 认证
    public function taskDetailsCount($sendId, $verify_type = '')
    {
        $where = $verify_type ? array(['verify_type','=',$verify_type]) : 1;
        try {
            return $this->where('send_id', '=', $sendId)
                ->where('status', '<>', 200)
                ->where('verify_type', $verify_type)
                ->count();
        } catch (Exception $e) {
            die(json_encode(['data' => false, 'code' => 500, 'message' => $e->getMessage()]));
        }
    }

}