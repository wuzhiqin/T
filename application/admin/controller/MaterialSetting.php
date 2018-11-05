<?php
/**
 * 素材设置模块
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/25
 * Time: 15:07
 */

namespace app\admin\controller;

use app\admin\validate\AddGallery;
use app\admin\validate\SettingHeadTail;
use app\admin\validate\UseMaterialSetting;
use think\Db;
use think\facade\Request;
use think\facade\Validate;
use app\common\model\Attach as AttachM;
use app\common\model\Gallery as GalleryM;
use app\common\model\WechatAccount as WechatAccountM;

class MaterialSetting
{
    //添加图片
    public function addGallery($suffix = 'jpg')
    {
        $param = get_param('type,img',1);
        //验证器验证
        $validate = new AddGallery();
        if(!$validate->check($param)){
            return response_json(false,400,$validate->getError());
        }
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $param['img'], $result)){
            $suffix = $result[2];
        }
        list($msec, $sec) = explode(' ', microtime());
        $msecTime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);      //得出毫秒级时间戳
        $imgName = $msecTime . random_keys(10) . '.'.$suffix;                                      //图片名
        base64ToImg($imgName,$param['img']);                                                               //上传图片
        $param['pic_url'] = 'http://thirdparty-1257007004.cos.ap-guangzhou.myqcloud.com/'.$imgName;  //图片地址
        unset($param['img']);
        $galleryM = new GalleryM();
        $res = $galleryM->insert($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //获取图片列表
    public function gallery($page=1,$limit=10)
    {
        $param = get_param(['type']);
        $type = trim($param['type']);
        $galleryM = new GalleryM();
        $res['total_limit'] = $galleryM->where('type',$type)->count();      //总条数
        $res['total_page'] = ceil($res['total_limit']/$limit);             //可分页
        $res['current_page'] = $page;                                              //当前页
        $res['list'] = $galleryM->gallery($type,$page,$limit);                      //当前页数据
        if ($res['list']){
            return response_json($res,200,'成功');
        }else{
            return response_json(null,200,'暂无数据');
        }
    }

    //删除图片 TODO 待优化 - 删除对象存储文件
    public function delGallery()
    {
        $param = get_param(['id']);
        $id = intval(trim($param['id']));
        $galleryM = new GalleryM();
        $galleryM->get($id);
        $res = $galleryM->where('id',$id)->delete();
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 设置预设内容(上中下)
     * @param int $hf_type 1-文章头部 2-尾部 3-中部
     * @param string $m_type 1-图片，2-超链接 3小程序卡片- 4-小程序图片 5-小程序超链接
     * @param string $remake 备注
     * @return json
     */
    public function settingContent($remark='')
    {
        $param = Request::post();
        $data['remark'] = $remark ? $remark : '无';
        /**************** 数据验证区 *************/
        $rule = [
            'hf_type'   => 'require|number|in:1,2,3',
            'm_type'    => 'require|number|between:1,5',
            'pic_url'   => 'requireIf:m_type,1',
            'content'   => 'requireIf:m_type,2',
            'appid'     => 'requireIf:m_type,3|requireIf:m_type,4|requireIf:m_type,5',
            'title'     => 'requireIf:m_type,3|requireIf:m_type,4|requireIf:m_type,5',
            'image_url' => 'requireIf:m_type,3|requireIf:m_type,4',
        ];
        $msg = [

        ];
        $validate = Validate::make($rule,$msg);
        if(!$validate->check($param)){
            return response_json(false,400,$validate->getError());
        }

        $data['hf_type'] = trim($param['hf_type']);
        $data['m_type'] = trim($param['m_type']);
        //m_type 1-图片，2-超链接 3小程序卡片- 4-小程序图片 5-小程序超链接
        switch ($data['m_type']){
            case 1:
                $data['value'] = trim($param['pic_url']);
                break;
            case 2:
                $data['value'] = trim($param['content']);
                break;
            case 3:
                $temporary['appid'] = trim($param['appid']);
                $temporary['path'] = trim($param['path']);
                $temporary['title'] = trim($param['title']);
                $temporary['imageurl'] = trim($param['image_url']);
                $data['value'] = json_encode($temporary,JSON_UNESCAPED_UNICODE);
                break;
            case 4:
                $temporary['appid'] = trim($param['appid']);
                $temporary['path'] = trim($param['path']);
                $temporary['title'] = trim($param['title']);
                $temporary['imageurl'] = trim($param['image_url']);
                $data['value'] = json_encode($temporary,JSON_UNESCAPED_UNICODE);
                break;
            case 5:
                $temporary['appid'] = trim($param['appid']);
                $temporary['path'] = trim($param['path']);
                $temporary['title'] = trim($param['title']);
                $data['value'] = json_encode($temporary,JSON_UNESCAPED_UNICODE);
                break;
        }

        unset($param);
        $attachM = new AttachM();
        $res['id'] = $attachM->insertGetId($data);
        if ($res){
            return json(['data'=>$res,'message'=>'成功','code'=>200]);
        }else{
            return json(['data'=>false,'message'=>'失败','code'=>400]);
        }
    }

    /*
     * 删除预设内容设置
     * @param int $id  预设内容id
     * @return json
     */
    public function delContent()
    {
        $param = get_param('id',1);
        $id = intval(trim($param['id']));
        $attachM = new AttachM();
        $res = $attachM->where('id',$id)->delete();
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /**
     * 内容预设列表
     * @param int $hf_type 1-顶部预设 2-底部预设 3-中部预设
     * @return json
     */
    public function contentList($page=1,$limit=10,$hf_type=1)
    {
        $attachM = new AttachM();
        $res['total_limit'] = $attachM->where('hf_type',$hf_type)->count();      //总条数
        $res['total_page'] = ceil($res['total_limit']/$limit);                  //页数
        $res['current_page'] = $page;                                                   //当前页
        $res['list'] = $attachM->content($hf_type,$page,$limit);                        //数据列表

        if ($res['list']){
            return response_json($res,200,'成功');
        }else{
            return response_json(null,200,'暂无数据');
        }
    }

    /*
     * 公众号头尾中内容设置（设置账号）
     * @param int $attach_id 预设内容id
     * @param string $mode type - 根据类型 ；relation - 根据分组 ; custom - 自定义
     * @param string $type 为空设置认证公众号 noAuth则设置未认证
     */
    public function settingHeadTail($type='auth')
    {
        $param = Request::post();
        $where = [];
        $attachM = new AttachM();
        $weChatAccountM = new WechatAccountM();
        //判定认证号操作还是未认证
        if($type=='auth'){
            $where[] = array(['verify_type_info','=',0]);
        }else if($type=='noAuth'){
            $where[] = array(['verify_type_info','=',-1]);
        }else{
            return response_json(false,400,'type参数有误');
        }

        /**************** 数据验证区 *************/
       $validate = new SettingHeadTail();
        if(!$validate->check($param)){
            return response_json(false,400,$validate->getError());
        }

        //获取对应预设内容
        $id = intval(trim($param['attach_id']));
        try{
            $attach_data = $attachM->where('id',$id)->find();
        }catch (Exception $e){
            die(json_encode(['data'=>false,'message'=>$e->getMessage(),'code'=>500]));
        }catch (\Error $e){
            die(json_encode(['data'=>false,'message'=>$e->getMessage(),'code'=>500]));
        }
        
        $data = [];
        //hf_type 1-文章头部 2-尾部 3-中部
        if (!empty($attach_data)){
            switch ($attach_data['hf_type']){
                case 1:
                    $data['w_at.head_type'] = $attach_data['m_type'];
                    $data['w_at.head_value'] = $attach_data['value'];
                    break;
                case 2:
                    $data['w_at.foot_type'] = $attach_data['m_type'];
                    $data['w_at.foot_value'] = $attach_data['value'];
                    break;
                case 3:
                    $data['w_at.middle_type'] = $attach_data['m_type'];
                    $data['w_at.middle_value'] = $attach_data['value'];
                    break;
            }
        }

        //type - 根据类型 ；relation - 根据分组 ; custom - 自定义
        switch ($param['mode']){
            case 'type':
                $where[] = array(['w_a.material_type','=',trim($param['type_id'])]);
                break;
            case 'relation':
                $where[] = array(['w_a.relation_id','=',trim($param['relation_id'])]);
                break;
            case 'custom':
                $where[] = array(['w_a.gh_id','in',trim($param['custom'])]);
                break;
        }

        $res = $weChatAccountM->evenTable($where,$data);
        if ($res >= 0){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }

    }

    /*
     * 使用素材设置（是否使用素材）TODO 待对接测试
     * @param string $type auth-认证公众号 noAuth则设置未认证
     */
    public function useMaterialSetting($type='auth')
    {
        $param = Request::post();
        $weChatAccountM = new WechatAccountM();
        $where = [];
        //判定认证号操作还是未认证
        if($type=='auth'){
            $where[] = array(['verify_type_info','=',0]);
        }else if($type=='noAuth'){
            $where[] = array(['verify_type_info','=',-1]);
        }else{
            return response_json(false,400,'type参数有误');
        }

        //验证器验证
        $validate = new UseMaterialSetting();
        if(!$validate->check($param)){
            return response_json(false,400,$validate->getError());
        }
        $data = [];
        //head - 头部设置 foot - 底部设置  middle - 中部设置  href - 原文设置
        switch ($param['use_hf']){
            case 'head':
                $data['w_at.use_head'] = trim($param['use_head']);
                break;
            case 'foot':
                $data['w_at.use_foot'] = trim($param['use_foot']);
                break;
            case 'middle':
                $data['w_at.use_middle'] = trim($param['use_middle']);
                break;
            case 'href':
                $data['w_at.use_href'] = trim($param['use_href']);
                $data['w_at.href_value'] = trim($param['href_value']);
                break;
        }

        //type - 根据类型 ；relation - 根据分组 ; custom - 自定义
        switch ($param['mode']){
            case 'type':
                $where[] = array(['w_a.material_type','=',trim($param['type_id'])]);
                break;
            case 'relation':
                $where[] = array(['w_a.relation_id','=',trim($param['relation_id'])]);
                break;
            case 'custom':
                $where[] = array(['w_a.gh_id','in',trim($param['custom'])]);
                break;
        }

        $res = $weChatAccountM->evenTable($where,$data);
        if ($res >= 0){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }

    }

}