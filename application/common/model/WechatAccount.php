<?php
/**
 * 微信公众账号Model
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/19
 * Time: 15:28
 */

namespace app\common\model;
use think\Exception;
use think\Model;

class WechatAccount extends Model {

	protected $pk = 'id';
	protected static function init()
	{
		//TODO:初始化内容
	}


    /**
     * 查询有效微信公众账号
     * @param array $whereArr 带字段的数组  例：[['name','=','thinkphp'],['status','=',1]]
     * @param int $verify_type_info 是否为认证号 -1:未认证  0：认证
     * @param int $page 分页
     * @param int $limit 显示条数
     * @return array
     */
    public function effective($whereArr,$verify_type_info,$page,$limit)
    {
        try{
            return $this->alias('w_a')
                ->leftJoin('type', 'w_a.material_type = type.id')
                ->leftJoin('relation r_t', 'w_a.relation_id = r_t.id')
                ->field('w_a.id,w_a.gh_id,w_a.nick_name,w_a.scan_account,w_a.last_login_time,w_a.authorization_time,w_a.last_send_time,type.type_name,r_t.relation_name')
                ->where($whereArr)
                ->where('verify_type_info',$verify_type_info)
                ->where('material_type','<>',0)
                ->order('w_a.id','desc')
                ->page($page)
                ->limit($limit)
                ->select()
                ->toArray();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }
    }

    /**
     * 查询有效微信公众账号统计
     * @param array $whereArr 带字段的数组  例：[['name','=','thinkphp'],['status','=',1]]
     * @param int $verify_type_info 是否为认证号 -1:未认证  0：认证
     * @return int
     */
    public function effectiveCount($whereArr,$verify_type_info)
    {
        return $this->where($whereArr)
            ->where('verify_type_info',$verify_type_info)
            ->where('material_type','<>',0)
            ->count();
    }

    /*
     * 查询无效微信公众账号(信息不全)
     */
    public function invalid($page,$limit)
    {
        try{
            return $this->alias('w_a')
                ->field('w_a.id,w_a.gh_id,w_a.appid,w_a.scan_account,w_a.nick_name,w_a.material_type,t.type_name,w_a.login_password')
                ->leftJoin('type t','w_a.material_type = t.id')
                ->whereOr('w_a.appid','')
                ->whereOr('w_a.appid',null)
                ->whereOr('w_a.login_password','')
                ->whereOr('w_a.material_type',0)
                ->order('w_a.scan_account','desc')
                ->page($page)
                ->limit($limit)
                ->select()
                ->toArray();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }

    }

    /*
     * 无效微信公众账号统计
     */
    public function invalidCount()
    {
        return $this->whereOr('appid','')
            ->whereOr('appid',null)
            ->whereOr('material_type',0)
            ->whereOr('login_password','')
            ->count();
    }

    /**
     * 根据分组id获取关联公众号
     * @param int $relation_id  分组id
     * @param int $verify_type_info    0-认证号   -1未认证
     * @return array
     */
    public function relationWc($relation_id,$verify_type_info,$page,$limit)
    {
        try{
            return $this->field('id,gh_id,nick_name,last_send_time')
                ->where('relation_id',$relation_id)
                ->where('verify_type_info',$verify_type_info)
                ->page($page)
                ->limit($limit)
                ->select()->toArray();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }

    }

    /**
     * 连表更新数据 需要特定数据
     * @param array $where 更新条件
     * @param array $updateData 更新数据
     * @return mixed
     */
    public function evenTable($where,$updateData)
    {
        try{
            return $this->alias('w_a')
                ->leftJoin("wechat_attach w_at",'w_a.id = w_at.id')
                ->where($where)
                ->update($updateData);
        }catch(\Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }
    }


    //查询头中尾内容设置状态数据
    public function settingState($where,$page,$limit)
    {
        try{
            return $this->alias('w_a')
                ->leftJoin('wechat_attach w_at','w_a.id = w_at.id')
                ->leftJoin('type t','w_a.material_type = t.id')
                ->leftJoin('relation r','w_a.relation_id = r.id')
                ->field('w_a.id,w_a.gh_id,w_a.nick_name,w_a.scan_account,t.type_name,r.relation_name,
            w_at.use_head,w_at.use_foot,w_at.use_middle,w_at.use_href,w_at.head_value,w_at.foot_value,w_at.middle_value,w_at.href_value')
                ->where($where)
//                ->whereOr('w_at.use_head',0)
//                ->whereOr('w_at.use_foot',0)
//                ->whereOr('w_at.use_middle',0)
//                ->whereOr('w_at.use_href',0)
//                ->whereOr('w_at.head_value','')
//                ->whereOr('w_at.foot_value','')
//                ->whereOr('w_at.middle_value','')
//                ->whereOr('w_at.href_value','')
                ->order('id','desc')
                ->page($page)
                ->limit($limit)
                ->select()->toArray();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }
    }

    //头中尾内容设置状态公众号统计
    public function settingStateCount($where)
    {
        try{
            return $this->alias('w_a')
                ->leftJoin('wechat_attach w_at','w_a.id = w_at.id')
                ->leftJoin('type t','w_a.material_type = t.id')
                ->leftJoin('relation r','w_a.relation_id = r.id')
                ->where($where)
//                ->whereOr('w_at.use_head',0)
//                ->whereOr('w_at.use_foot',0)
//                ->whereOr('w_at.use_middle',0)
//                ->whereOr('w_at.use_href',0)
//                ->whereOr('w_at.head_value','')
//                ->whereOr('w_at.foot_value','')
//                ->whereOr('w_at.middle_value','')
//                ->whereOr('w_at.href_value','')
                ->count();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>500,'message'=>$e->getMessage()]));
        }
    }


}