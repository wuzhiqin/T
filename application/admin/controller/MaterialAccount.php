<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/9/5
 * Time: 14:06
 */

namespace app\admin\controller;

use app\common\model\Type as TypeM;
use app\common\model\MaterialAccount as MaterialAccountM;
use app\common\service\Common;
use Db;

class MaterialAccount {

    //创建账号
    public function createMaterial($remark='')
    {
        $param = get_param('account_name,type,account_id,account_url,material_type',1);
        $data = [];
        foreach ($param as $k=>$v){
            $data[$k] = trim($param[$k]);
        }
        $data['remark'] = $remark ? $remark : '无';
        $materialAccountM = new MaterialAccountM();
        $where['account_id'] = $data['account_id'];
        $where['material_type'] = $data['material_type'];
        if(!TypeM::get($param['material_type'])) return response_json(false,400,'该分类不存在');
        $is = $materialAccountM->where($where)->find();

        //不允许重复添加账号
        if(!$is){
            $res = $materialAccountM->insert($data);
            if ($res){
                return response_json(true,200,'成功');
            }else{
                return response_json(false,400,'失败');
            }
        }else{
            return response_json(false,400,'该账号已存在');
        }

    }

    //删除账号
    public function delMaterial()
    {
        $param = get_param(['id']);
        $id = intval(trim($param['id']));
        $materialAccountM = new MaterialAccountM();
        $res = $materialAccountM->where('id',$id)->delete();
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //修改账号
    public function updateMaterial($remark='')
    {
        $param = get_param(['id','account_name','type','account_id','account_url','material_type']);
        $data = [];
        $materialAccountM = new MaterialAccountM();
        foreach ($param as $k=>$v){
            $data[$k] = trim($param[$k]);
        }
        $data['remark'] = $remark ? $remark : '无';
        $id = intval($data['id']);
        unset($param,$data['id']);
        $where['account_id'] = $data['account_id'];
        $where['material_type'] = $data['material_type'];
        $is = $materialAccountM->where('id','<>',$id)->where($where)->find();

        if($is){
            return response_json(false,400,'该账号已存在');
        }else{
            $res = $materialAccountM->where('id',$id)->update($data);
            if ($res){
                return response_json(true,200,'成功');
            }else{
                return response_json(true,200,'数据未改动');
            }
        }

    }

    //获取单个账号 TODO 待修改
    public function one()
    {
        $param = get_param(['id']);
        $id = trim($param['id']);
        $materialAccountM = new MaterialAccountM();
        $res = $materialAccountM->one($id);
        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }


    //账号搜索和获取所有账号 TODO 待修改
    public function selectAllMaterial($account_id=0,$material_type=0,$account_name='',$remark='',$page=1,$limit=10)
    {
        $where_account_id = $this->determine($account_id,'account_id');
        $where_material_type = $this->determine($material_type,'material_type');
        $where_account_name = $this->determine($account_name,'account_name','like');
        $where_remark = $this->determine($remark,'remark','like');
        //合并数组
        $whereArr = $this->batchArr(array($where_account_id,$where_material_type,$where_account_name,$where_remark));

        $materialAccountM = new MaterialAccountM();
        $res['total_limit'] = $materialAccountM->totalLimit($whereArr);
        $res['total_page'] = ceil($res['total_limit']/$limit);
        $res['current_page'] = $page;
        $res['list'] = $materialAccountM->conditional($whereArr,$page,$limit);

        if ($res['list']){
            return json(['data'=>$res,'message'=>'请求成功','code'=>200]);
        }else{
            return json(['data'=>false,'message'=>'无数据','code'=>400]);
        }
    }

    //批量合并数组（限固定类型）
    protected function batchArr($array)
    {
        $arr = array();
        foreach($array as $k => $v){
            $arr = array_merge($arr,$v);
        }
        return $arr;
    }

    //条件判断返回
    protected function determine($data,$name,$symbol='=')
    {
        if ($data){
            if ($symbol == 'like'){
                return array([$name,$symbol,'%'.trim($data).'%']);
            }else{
                return array([$name,$symbol,trim($data)]);
            }

        }else{
            return array();
        }
    }


    //批量导入Excel素材账号
    public function batch()
    {
        if (empty($_FILES['excel'])){
            die(json_encode(['data'=>false,'message'=>'接收不到文件，请重新上传','code'=>500]));
        }

        $data = $_FILES['excel']['tmp_name'];
        $common = new Common();
        $excel_data = $common->importExcel($data);
        array_shift($excel_data);         //删除第一个数组(标题);
        $data = [];
        //删除空白行数组
        foreach ($excel_data as $k => $v){
            $t = '';
            foreach ($excel_data[$k] as $ks=>$vs){
                $t .= $excel_data[$k][$ks];
            }
            //如果$t为null 说明整条数据都为空，删除
            if ($t == null){
                unset($excel_data[$k]);
            }
        }
        $excel_count = count($excel_data);        //表格数据总条数
        //对应字段赋值
        $lack_gh_id = '';       //记录缺少数据的行数
        foreach ($excel_data as $k => $v){
            if (isset($excel_data[$k]['A']) && isset($excel_data[$k]['C'])){
                $data[$k]['type'] = $excel_data[$k]['A'];
                $data[$k]['account_name'] = $excel_data[$k]['B'];
                $data[$k]['account_id'] = $excel_data[$k]['C'];
                $data[$k]['account_url'] = $excel_data[$k]['D'];
                $data[$k]['material_type'] = $excel_data[$k]['E'];
                $data[$k]['remark'] = $excel_data[$k]['F'];
            }else{
                $lack_gh_id .= intval($k+2).',';
            }
        }
        $lack_gh_id = '缺少数据的行数：'.substr($lack_gh_id, 0, -1);
        $repeat_data = '';      //记录数据库已存在的数据对应excel表的行数
        $success_num = 0;       //记录成功录入条数
        foreach ($data as $k=>$v){
            $is = Db::name('material_account')->where('type',$data[$k]['type'])->where('account_id',$data[$k]['account_id'])->find();
            if ($is){
                $repeat_data .= intval($k+2).',';
            }else{
                $res = Db::name('material_account')->insert($data[$k]);
                $res ? $success_num += 1 : '';
            }
        }
        $repeat_data = '已存在账号的行数：'.substr($repeat_data, 0, -1);
        //失败条数
        $fail_num = $excel_count - $success_num;
        return json(['data'=>true,'message'=>'总条数:'.$excel_count.'条,成功:'.$success_num.'条，失败:'.$fail_num.'条,'.$repeat_data.','.$lack_gh_id,'code'=>200]);
    }

}