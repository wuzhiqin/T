<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/29
 * Time: 11:34
 */

namespace app\common\model;


use think\Exception;
use think\Model;

class MaterialAccount extends Model
{

    //根据素材账号id连表查询
    public function one($id)
    {
        try{
            return $this->alias('m_a')
                ->leftJoin('type','m_a.material_type = type.id')
                ->field('m_a.*,type.type_name')
                ->where('m_a.id','=',$id)
                ->find();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>400,'message'=>$e->getMessage()]));
        }
    }

    /**
     * 查询素材账号
     * @param array $whereArr 带字段的数组  例：[['name','=','thinkphp'],['status','=',1]]
     * @param int $page 分页
     * @param int $limit 显示条数
     * @return array
     */
    public function conditional($whereArr,$page,$limit)
    {
        try{
            return $this->alias('m_a')
                ->leftJoin('type', 'm_a.material_type = type.id')
                ->field('m_a.id,m_a.account_name,m_a.type,type.type_name,m_a.remark,m_a.news_num,m_a.last_update_time')
                ->where($whereArr)
                ->page($page)
                ->limit($limit)
                ->select()
                ->toArray();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'code'=>400,'message'=>$e->getMessage()]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'message'=>$e->getMessage(),'code'=>500]));
        }
    }

    /**
     * 查询素材账号账号统计
     * @param array $whereArr 带字段的数组  例：[['name','=','thinkphp'],['status','=',1]]
     * @return int
     */
    public function totalLimit($whereArr)
    {
        return $this->alias('m_a')
            ->leftJoin('type', 'm_a.material_type = type.id')
            ->where($whereArr)
            ->count();
    }
}