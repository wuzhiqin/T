<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/11/1
 * Time: 10:17
 */

namespace app\common\model;

use think\Db;
use think\Exception;
use think\Model;

class SendList extends Model
{
    //查询群发任务列表
    public function sendList($verify_type, $page, $limit)
    {
        try {
            return $this->field('send_md5,verify_type', true)
                ->where('verify_type', $verify_type)
                ->order('id', 'desc')
                ->page($page)
                ->limit($limit)
                ->select()->toArray();
        } catch (Exception $e) {
            die(json_encode(['data' => false, 'code' => 500, 'message' => $e->getMessage()]));
        }
    }

    //删除群发任务
    public function sendTaskDel($id)
    {
        try {
            $sendM = new Send();
            $sendM->where('send_id', $id)->delete();
            $this->where('id', $id)->delete();
            Db::commit();        //提交事物
        } catch (Exception $e) {
            Db::rollback();      //事物回滚
            die(json_encode(['data' => false, 'code' => 500, 'message' => $e->getMessage()]));
        }
    }

}