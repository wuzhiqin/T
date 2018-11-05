<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/19
 * Time: 14:54
 */

namespace app\thirdparty\controller;
use think\Exception;

/**
 *
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * @author widyhu
 *
 */
class Tools
{
	/**输出xml字符
	 * @param $array
	 * @return string
	 */
	public function ToXml($array)
	{

		$xml = "<xml>";
		foreach ($array as $key=>$val)
		{
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}

	/**
	 * 将xml转为array
	 * @param string $xml
	 * @return string
	 */
	public function FromXml($xml)
	{
		if(!$xml){
			return false;
		}
		//将XML转为array
		//禁止引用外部xml实体
		$disableLibxmlEntityLoader = libxml_disable_entity_loader(true);
		$array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		libxml_disable_entity_loader($disableLibxmlEntityLoader);
		return $array;
	}

	/**格式化参数格式化成url参数
	 * @param array $array
	 * @return string
	 */
	public function ToUrlParams(array $array)
	{
		$buff = "";
		foreach ($array as $k => $v)
		{
			if($v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}

		$buff = trim($buff, "&");
		return $buff;
	}

	/**
	 * @param string $dirName 文件夹路径及名称（后面不带/）
	 * @return bool
	 */
	function delDirAndFile($dirName) {
		if(!is_dir($dirName))return false;
		if ($handle = opendir($dirName)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != '..') {
					if (is_dir("$dirName/$item")) {
						$this->delDirAndFile("$dirName/$item");
					} else {
						unlink("$dirName/$item") ;
					}
				}
			}
			closedir($handle);
			rmdir($dirName) ;
		}
	}
	/**
	 * 下载网络图片
	 * @param array $file_array array('要生成的文件名称'=>'网络地址')
	 * @param string $referer_url 来源地址模拟
	 * @param string $cookie cookie模拟
	 * @return array|bool
	 */
	public function downloadFile(array $file_array,  $referer_url = '', $cookie = '') {
		$file_list['path'] = 'uploads/'.md5(microtime().mt_rand(1000,9999));
		foreach ($file_array as $key => $value) {
			$list[$key . '.jpg']['url'] = $value;
		}
		if(!isset($list))return false;
		curl_multi($list, $res, $referer_url,$cookie);

		is_dir($file_list['path']) ?: mkdir($file_list['path'], 0777, true);
		foreach ($res as $k => $v) {
			$file_list['file'][] = $k;
			file_put_contents($file_list['path'] .'/'. $k, $v);
		}
		if(!isset($file_list))return array();
		return $file_list;
	}

	/**
	 * 上传文件检查并生成curl
	 * @param $access_token
	 * @param $media
	 * @param string $type
	 * @param int $up_type 1永久素材 2图文内图片 3临时素材
	 * @return string
	 */
	public function uploadMaterialCheck($access_token, $media, $type = 'image', $up_type = 1) {
		/*
		 * 临时素材 图片（2M） 语音（2M） 视频（10M） 缩略图（64K）
		 * image voice video thumb
		 */
		$this->check_param($type,array('image','video','thumb','image'));
		//图片类型确定和文件大小检查
		switch ($type) {
			case 'voice':
				$type = 'voice';
				filesize($media) < 1024 * 2048 ?: die(json_encode('音频文件大小超出2M上传限制',320));
				break;
			case 'video':
				$type = 'video';
				filesize($media) < 1024 * 10240 ?:die(json_encode('视频文件大小超出10M上传限制',320));
				break;
			case 'thumb':
				$type = 'thumb';
				filesize($media) < 1024 * 64 ?: die(json_encode('缩略图文件大小超出64K上传限制',320));
				break;
			default:
				$type = 'image';
				filesize($media) < 1024 * 2048 ?: die(json_encode('图片文件大小超出2M上传限制',320));
				break;
		}
		if ($up_type == 1) {
			//永久素材
			$url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . $access_token . '&type=' . $type;

		} elseif ($up_type == 2) {
			//图文内图片
			$url = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' . $access_token;
		} else {
			//临时素材
			$url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $access_token . '&type=' . $type;
		}
		return $url;
	}
	function ext_curl($url, $post = '', $referer_url = '', $cookie = '',$json_decode = 0) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //跳过证书验证
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    // 从证书中检查SSL加密算法是否存在
		if ($referer_url) {
			curl_setopt($ch, CURLOPT_REFERER, $referer_url);      //模拟来路
		}
		if ($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);     //使用上面获取的cookies
		}
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			die(json_encode(['data' => false, 'message' => curl_error($ch), 'code' => 500])); //若错误打印错误信息
		}
		curl_close($ch);    //关闭curl
		if($json_decode){
			return json_decode($response,1);
		}
		return $response;
	}
	public function check_param($param,array $enum_array){
		if (!in_array($param,$enum_array)){
			die(json_encode(['data' => false, 'code' => 300, 'message' => $param.',该参数不在枚举条件中['.implode(',',$enum_array).']'], 320));
		}

	}



}