<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/22
 * Time: 13:44
 */

namespace app\thirdparty\controller;

/**
 * 微信基础接口
 * Class WXInterface
 * @package app\thirdparty\controller
 */
class WXInterface extends Tools {
	private $access_token;
	public function __construct($access_token) {
		$this->access_token = $access_token;
	}

	/**
	 * 好像没啥用，图片地址不能用
	 * 获取素材列表（图片/视频/音频/图文）
	 * @param string $type 素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
	 * @param int $page 页数
	 * @param int $limit 返回素材的数量，取值在1到20之间
	 * @return mixed|string
	 */
	public function getMaterial($type,$page =1,$limit = 20) {
		$this->check_param($type,['image','video','voice','news']);
		if($limit>20||$limit<1){
			$limit = 20;
		}
		$offset = ($page-1)*$limit;
		$url ='https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$this->access_token;
		$post = '{"type":"'.$type.'","offset":'.$offset.',"count":'.$limit.'}';
		$res = $this->ext_curl($url,$post,'','',1);
		isset($res['item'])?$res = $res['item']:null;
		return $res;
	}

	/**
	 * 删除素材
	 * @param string $media_id
	 * @return bool|mixed|string
	 */
	public function delMaterial($media_id){
		$url ='https://api.weixin.qq.com/cgi-bin/material/del_material?access_token='.$this->access_token;
		$post='{"media_id":"'.$media_id.'"}';
		$res = $this->ext_curl($url,$post,'','',1);
		if(isset($res['errcode'])&&$res['errcode']==0){
			return false;
		}elseif(isset($res['errcode'])&&$res['errcode']==40007){
			return '无效的media_id';
		}else{
			return $res;
		}

	}
	public function uploadMaterial($access_token, $media, $type = 'image', $up_type = 1){
		$url = $this->uploadMaterialCheck($access_token, $media, $type, $up_type);
		$post = new \CURLFile($media,1,1);
	}
}