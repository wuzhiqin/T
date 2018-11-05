<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/24
 * Time: 14:01
 */

namespace app\admin\controller;

use app\common\model\Type as TypeM;
use think\Db;

class Type
{
    //创建分类
    public function createType()
    {
        $param = get_param('type_name',1);
        $param['type_name'] = trim($param['type_name']);
        if (!(TypeM::where('type_name','=',$param['type_name'])->find())){
            if (TypeM::insert($param)){
                return response_json(true,200,'成功');
            }else{
                return response_json(false,400,'失败');
            }
        }else{
            return response_json(false,400,'该分类已存在');
        }

    }

    //修改
    public function updateType()
    {
        $param = get_param('id,type_name',1);
        $param['id'] = intval(trim($param['id']));
        $param['type_name'] = trim($param['type_name']);
        $is = TypeM::where('id','<>',$param['id'])->where('type_name','=',$param['type_name'])->find();
        if($is){
            return response_json(false,400,'该分类已存在');
        }else{
            if (TypeM::where('id',$param['id'])->update(['type_name'=>$param['type_name']])){
                return response_json(true,200,'成功');
            }else{
                return response_json(true,200,'数据未改动');
            }
        }

    }

    //查询单个类型
    public function type()
    {
        $param = get_param('id',1);
        $id = intval(trim($param['id']));
        $typeM = new TypeM();
        $res = $typeM->where('id',$id)->find();
        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 查询所有类型，默认分页 TODO 待完善
     */
    public function types($page=1,$limit=10,$all=false)
    {
        if ($all){
            $res = Db::name('type')->select();
        }else{
            $type_list = Db::name('type')
                ->page($page)
                ->limit($limit)
                ->select();
            $not_used = Db::name('material_news')
                ->where('use_type','=',0)
                ->group('material_type')
                ->column('material_type,count(*) as count');
            $used = Db::name('material_news')
                ->where('use_type','=',1)
                ->group('material_type')
                ->column('material_type,count(*) as count');
            $wechat_account_num = Db::name('wechat_account')
                ->where('verify_type_info','0')
                ->group('material_type')
                ->column('material_type,count(*) as count');
            $w_a_no_num = Db::name('wechat_account')
                ->where('verify_type_info','-1')
                ->group('material_type')
                ->column('material_type,count(*) as count');
            $material_account_num = Db::name('material_account')
                ->group('material_type')
                ->column('material_type,count(*) as count');
            $res = array();
            foreach ($type_list as $key=>$value){
                $res['type_list'][$key]['id'] = $value['id'];
                $res['type_list'][$key]['type_name'] = $value['type_name'];
                isset($used[$value['id']])?$res['type_list'][$key]['used'] = $used[$value['id']]:$res['type_list'][$key]['used'] = 0;
                isset($not_used[$value['id']])?$res['type_list'][$key]['not_used'] = $not_used[$value['id']]:$res['type_list'][$key]['not_used'] = 0;
                isset($wechat_account_num[$value['id']])?$res['type_list'][$key]['wechat_account_num'] = $wechat_account_num[$value['id']]:$res['type_list'][$key]['wechat_account_num'] = 0;
                isset($w_a_no_num[$value['id']])?$res['type_list'][$key]['w_a_no_num'] = $w_a_no_num[$value['id']]:$res['type_list'][$key]['w_a_no_num'] = 0;
                isset($material_account_num[$value['id']])?$res['type_list'][$key]['material_account_num'] = $material_account_num[$value['id']]:$res['type_list'][$key]['material_account_num'] = 0;
            }
            $res['total_limit'] = Db::name('type')->count();
            $res['total_page'] = ceil($res['total_limit']/$limit);
            $res['current_page'] = $page;
        }

        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //类型id对应素材账号 TODO 待定
    public function typeMaterial()
    {
        $param = get_param(['type_id']);
        $id = intval(trim($param['type_id']));
        $res = Db::name('material_account')
            ->where('material_type',$id)
            ->field('id,account_name')
            ->select();

        if ($res){
            return json(['data'=>$res,'message'=>'请求成功','code'=>200]);
        }else{
            return json(['data'=>false,'message'=>'请求失败','code'=>400]);
        }
    }

}