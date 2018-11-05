<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/11/3
 * Time: 11:28
 */

namespace app\admin\controller;

use app\common\model\Error as ErrorM;

class Error
{
    //错误列表
    public function errorList()
    {
        $res = ErrorM::all();
        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(null,200,'暂无数据');
        }
    }

    //删除单条
    public function errorDel(){
        $param = get_param(['id']);
        $where[] = ['id','=',$param['id']];
        $res = ErrorM::where($where)->delete();
        if($res){
            return json(['data' => true, 'code' => 200, 'message' => '成功删除']);
        }else{
            return json(['data' => false, 'code' => 400, 'message' => '无记录']);
        }
    }

    //主键区间删除错误信息
    public function sectionDel()
    {
        $param = get_param('start,stop',1);
        $start = trim($param['start']);
        $stop = trim($param['stop']);
        $res = ErrorM::whereBetween('id',"$start,$stop")->delete();
        return response_json(true,200,'删除了'.$res.'条数据');
    }

}