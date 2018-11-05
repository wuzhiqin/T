<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/24
 * Time: 15:10
 */

namespace app\admin\controller;

use think\Controller;
use app\admin\validate\RelationWx;
use app\common\model\Relation as RelationM;
use app\common\model\WechatAccount as WechatAccountM;

class Relation extends Controller
{
    /*
    * 创建分组
    */
    public function createRelation()
    {
        $param = get_param('relation_name',1);
        $param['relation_name'] = trim($param['relation_name']);
        if (!(RelationM::where('relation_name',$param['relation_name'])->find())){
            if (RelationM::insert($param)){
                return response_json(true,200,'成功');
            }else{
                return response_json(false,400,'失败');
            }
        }else{
            return response_json(false,400,'该分组已存在');
        }
    }

    /*
     * 删除分组
     */
    public function delRelation()
    {
        $param = get_param('id',1);
        $id = intval(trim($param['id']));
        $res = RelationM::destroy($id);
        $weChatAccountM = new WechatAccountM();
        if ($res){
            $weChatAccountM->where('relation_id',$id)->update(['relation_id'=>0]);
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 修改分组
     */
    public function updateRelation()
    {
        $param = get_param('id,relation_name',1);
        $param['id'] = intval(trim($param['id']));
        $param['relation_name'] = trim($param['relation_name']);
        $is = RelationM::where('id','<>',$param['id'])->where('relation_name',$param['relation_name'])->find();
        if($is){
            return response_json(false,400,'该分组已存在');
        }else{
            if (RelationM::where('id',$param['id'])->update(['relation_name'=>$param['relation_name']])){
                return response_json(true,200,'成功');
            }else{
                return response_json(true,200,'数据未改动');
            }
        }
    }

    /*
     * 获取所有分组 TODO 待完善
     * @param
     */
    public function relations($page=1,$limit=10,$all=false)
    {
        $relationM = new RelationM();
        $weChatAccountM = new WechatAccountM();
        if ($all){
            $res['list'] = $relationM->field('id,relation_name')->select();
        }else{
            $res['total_limit'] = $relationM->count();
            $res['total_page'] = ceil($res['total_limit']/$limit);
            $res['current_page'] = $page;
            $res['list'] = $relationM->page($page)->limit($limit)->select()->toArray();
            //认证公众统计
            $auth = $weChatAccountM->where('verify_type_info','0')->group('relation_id')->column('relation_id,count(*) as count');
            //未认证公众统计
            $no_auth = $weChatAccountM->where('verify_type_info','-1')->group('relation_id')->column('relation_id,count(*) as count');
            unset($relationM,$weChatAccountM);
            foreach ($res['list'] as $k=>$v){
                $res['list'][$k]['wx_auth_count'] = isset($auth[$v['id']]) ? $auth[$v['id']] : 0;
                $res['list'][$k]['wx_noauth_count'] = isset($no_auth[$v['id']]) ? $no_auth[$v['id']] : 0;
            }
        }

        if ($res['list']){
            return response_json($res,200,'成功');
        }else{
            return response_json(true,200,'暂无数据');
        }

    }

    /*
     * 根据分组获取所有关联的微信公众号 TODO 待完善
     * @param string $type 默认查询对应的认证公众号 noAuth则查询未认证
     */
    public function relationWc($page=1,$limit=10,$type='auth')
    {
        $param = get_param('id',1);
        $id = intval(trim($param['id']));
        //判断对认证号还是未认证号的操作
        $verify_type_info = $this->appraisal($type);

        $weChatAccountM = new WechatAccountM();
        $res['total_limit'] = $weChatAccountM->where('relation_id',$id)->where('verify_type_info',$verify_type_info)->count();
        $res['total_page'] = ceil($res['total_limit']/$limit);
        $res['current_page'] = $page;
        $res['list'] = $weChatAccountM->relationWc($id,$verify_type_info,$page,$limit);
        unset($weChatAccountM);
        if (!empty($res['list'])){
            return response_json($res,200,'成功');
        }else{
            return response_json(null,200,'该分组无关联账号');
        }
    }

    /*
     * 移除微信账号 TODO 待完善
     * @param string $type 默认查询认证公众号 noAuth则查询未认证
     */
    public function removeWxAccount($type='auth')
    {
        $param = get_param('id',1);
        $id = intval(trim($param['id']));
        //判断对认证号还是未认证号的操作
        $verify_type_info = $this->appraisal($type);

        $weChatAccountM = new WechatAccountM();
        $res = $weChatAccountM->where('id',$id)->where('verify_type_info',$verify_type_info)->update(['relation_id'=>0]);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 根据微信原始id和分组id获取对应微信公众 (搜索) TODO 待优化
     * @param string $type 为空查询认证公众号 noAuth则查询未认证
     */
    public function ghId($type='auth')
    {
        $param = get_param('relation_id,gh_id',1);
        $where['relation_id'] = intval(trim($param['relation_id']));
        $where['gh_id'] = trim($param['gh_id']);
        //判断对认证号还是未认证号的操作
        $where['verify_type_info'] = $this->appraisal($type);
        $relationM = new RelationM();
        $res = $relationM->searchWxGhId($where);
        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(false,403,'该分组下不存在该微信公众号');
        }
    }

    /*
     * 根据分组id添加账号 TODO 待优化-验证器
     * @param string $type 为空添加认证公众号 noauth则添加未认证
     */
    public function addWxAccount($type='auth')
    {
		$param = request()->post();
		//判断对认证号还是未认证号的操作
        global $verify_type_info;
		$verify_type_info = $this->appraisal($type);

		//验证器验证
        $validate = new RelationWx();
        if(!$validate->check($param)){
            return response_json(false,400,$validate->getError());
        }

        $where[] = ['verify_type_info','=',$verify_type_info];                                //认证或未认证条件
        if ($param['mode'] == 'type_id'){
            $where[] = ['material_type','=',intval(trim($param['type_id']))];                //类型条件
        }else if($param['mode'] == 'gh_id'){
            $where[] = ['gh_id','in',trim($param['gh_id'])];                                  //原始id条件
        }
        $res = '';
        $relation_id = intval(trim($param['id']));                                              //分组id
        $weChatAccountM = new WechatAccountM();                                                 //实例化公众账号模型
        try{
            $res = $weChatAccountM->where($where)->update(['relation_id'=>$relation_id]);     //符合条件则进行分组更新
        }catch (Exception $e){
            return response_json(false,500,$e->getMessage());
        }

        if ($res > 0){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 变更分组 TODO 待优化
     * @param string $type 默认添加认证公众号 noAuth则添加未认证
     */
    public function updateWechatRelation($type='auth'){
        $param = get_param('wechat_id,relation_id',1);
        $weChatAccountM = new WechatAccountM();
        //判断对认证号还是未认证号的操作
        $param['verify_type_info'] = $this->appraisal($type);

        $relation_id = $weChatAccountM->where('id',$param['wechat_id'])->where('verify_type_info',$param['verify_type_info'])->value('relation_id');
        if($relation_id==$param['relation_id'])return json(['data'=>false,'message'=>'原分组与目标分组相同','code'=>500]);
        $res = $weChatAccountM->where('id',$param['wechat_id'])->where('verify_type_info',$param['verify_type_info'])->update(['relation_id'=>$param['relation_id']]);
        if($res){
            return json(['data'=>true,'message'=>'变更分组成功','code'=>200]);
        }else{
            return json(['data'=>false,'message'=>'变更分组失败','code'=>500]);
        }
    }


    /**
     * 判定对认证号或未认证号的操作
     * @param string 认证标识 自己定
     * @return int  0->认证  or  -1->未认证
     */
    protected function appraisal($type)
    {
        if ($type == 'auth'){
            return 0;
        }elseif ($type == 'noAuth'){
            return -1;
        }else{
            die(json_encode(['data'=>false,'code'=>400,'message'=>'type参数有误']));
        }
    }

}