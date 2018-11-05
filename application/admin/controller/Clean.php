<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/31
 * Time: 16:41
 */

namespace app\admin\controller;


use app\common\model\MaterialContent;
use app\common\model\MaterialNews;
use think\Exception;

class Clean
{
    //清除已使用素材
    public function cleanNews($material_type = -1)
    {
        $where[] = ['use_type','=',1];                  //1-已使用
        if($material_type>0){
            $where[] = ['material_type','=',$material_type];
        }
        $materialNewsM = new MaterialNews();
        try{
            $res = $materialNewsM
                ->where($where)
                ->chunk(1000,function ($newsList){
                    $hundred = [];
                    $uc = [];
                //素材来源归类
                foreach($newsList as $k=>$v){
                    switch ($v['referer_id']){
                        case 1:
                            $hundred[] = $v;
                            break;
                        case 2:
                            $uc[] = $v;
                            break;
                    }
                }

                //刪除百家号素材
                    $hundredId = array_column($hundred, 'id');
                    MaterialNews::destroy($hundredId);
                //删除UC素材
                    $ucId = array_column($uc,'id');
                    $this->ucMaterial($ucId);
            });
        }catch (Exception $e){
            return response_json(false,500,$e->getMessage());
        }

        if($res){
            return response_json(true,200,'清除完毕');
        }else{
            return response_json(false,500,'无素材可清除');
        }

    }

    //清除UC素材
    protected function ucMaterial($idArr)
    {
        $materialContentM = new MaterialContent();
        MaterialNews::destroy($idArr);
        $materialContentM->where('content_id','in',$idArr)->delete();
    }


}