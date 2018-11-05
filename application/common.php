<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 必传参数检查（若有部分参数需要做接口验证签名，另一部分不需要，将不需要验证的参数放入decryptdata数组）
 * @param  string|array $map 传入参数支持数组和以英文逗号','分割的字符串
 * @param int $notnull 是否不能为空
 * @return array 返回结果
 */
function get_param($map, $notnull = 0)
{
    if (is_string($map)) {
        $map = explode(',', $map);
    }
    $param = Request::param();
    if (isset($param['decryptdata']) && !empty($param['decryptdata'])) {
        foreach ($param['decryptdata'] as $key => $value) {
            $param[$key] = $value;
        }
    }
    $res = array();
    foreach ($map as $value) {
        if ($notnull) {
            if (isset($param[$value]) && (!empty($param[$value]) || $param[$value] === '0' || $param[$value] === 0)) {
                $res[$value] = $param[$value];
            } else {
                die(json_encode(['data' => false, 'code' => 300, 'message' => '缺少参数或参数为空' . $value], 320));
            }
        } else {
            if (isset($param[$value])) {
                $res[$value] = $param[$value];
            } else {
                die(json_encode(['data' => false, 'code' => 300, 'message' => '缺少参数' . $value], 320));
            }
        }

    }
    return $res;
}

/**
 * @param string $url 请求地址
 * @param string $post post请求数据
 * @param string $referer_url 模拟来路
 * @param string $cookie 加入cookie
 * @return mixed
 */
function ext_curl($url, $post = '', $referer_url = '', $cookie = '')
{
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
    return $response;
}
/**
 * 并发curl
 * @param array $array array('key_name'=>array('url'=>URL,'post'=>POST),'key_name2'=>array('url'=>URL,'post'=>POST))
 * @param array $result 返回值为array('key_name'=>$result,'key_name2'=>$result2)
 * @param string $referer_url
 * @param string $cookie
 * @return bool
 */
function curl_multi(array $array,  &$result,$referer_url = '', $cookie = '') {
//		$save_path ='upload/';
	$queue = curl_multi_init();
	if(count($array)==0)return false;
	$array_list = array();
	$result = array();
	foreach ($array as $key_name=>$value){
		$array_list[$key_name] = curl_init();
		curl_setopt($array_list[$key_name], CURLOPT_URL, $value['url']);
		curl_setopt($array_list[$key_name], CURLOPT_HEADER, 0);
		curl_setopt($array_list[$key_name], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($array_list[$key_name], CURLOPT_SSL_VERIFYPEER, false);    //跳过证书验证
		curl_setopt($array_list[$key_name], CURLOPT_SSL_VERIFYHOST, false);    // 从证书中检查SSL加密算法是否存在
		if ($referer_url) {
			curl_setopt($array_list[$key_name], CURLOPT_REFERER, $referer_url);      //模拟来路
		}
		if ($cookie) {
			curl_setopt($array_list[$key_name], CURLOPT_COOKIE, $cookie);     //使用上面获取的cookies
		}
		if (isset($value['post'])) {
			curl_setopt($array_list[$key_name], CURLOPT_POST, 1);
			curl_setopt($array_list[$key_name], CURLOPT_POSTFIELDS, $value['post']);
		}
		curl_multi_add_handle($queue, $array_list[$key_name]);
	}

	do {
		while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

		if ($code != CURLM_OK) {
			break;
		}

		// a request was just completed -- find out which one
		while ($done = curl_multi_info_read($queue)) {

			// get the info and content returned on the request
			// $info = curl_getinfo($done['handle']);

//				 $error = curl_error($done['handle']);
			$results = curl_multi_getcontent($done['handle']);
			// $arr = compact('info', 'error', 'results');
			if ($results) {
				$search_key = array_search($done['handle'], $array_list);
				$result[$search_key] =$results;
			}
			// remove the curl handle that just completed
			curl_multi_remove_handle($queue, $done['handle']);
			curl_close($done['handle']);
		}

		// Block for data in / output; error handling is done by curl_multi_exec
		if ($active > 0) {
			curl_multi_select($queue, 1);
		}

	} while ($active);
	curl_multi_close($queue);
}

/**
 *  获取当天24点的时间戳
 * @return false|int
 */
function get_24_time()
{
    return (strtotime('today') + 86400) - time();
}


/**
 * 统一返回 json 数据
 * @param null $data
 * @param int $code
 * @param string $message
 * @return \think\response\Json
 */
function response_json($data = null, $code = 200, $message = 'success')
{
    return json(['code' => $code, 'message' => $message, 'data' => $data]);
}

/**
 * 生成随机字符串
 */
function random_keys($length)
{
    $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
    $key = '';
    $len = strlen($pattern) - 1;
    for ($i = 0; $i < $length; $i++) {
        $key .= $pattern{mt_rand(0, $len)};     //生成php随机数
    }
    return $key;
}

/**将错误信息记录至error表（适用于catch方法）
 * @param $err_type int 错误类型(1函数错误|2微信api错误)
 * @param $err_msg string
 */
function error_record($err_type, $err_msg)
{
    $is = Db::name('error')->insert(['err_type' => $err_type, 'err_msg' => $err_msg, 'time' => date('Y-m-d H:i:s', time())]);
    if (!$is) {
        die(json_encode('错误信息存入数据库失败,错误信息:type[' . $err_type . '],msg[' . $err_msg . ']', 320));
    }

}

/**
 * 停止并输出信息
 * @param string $msg
 */
function dec_count_die($msg=''){
	Db::name('config')->where('id','=',2)->setDec('config_value');
	die($msg);
}

/**
 * base64图片上传对象存储
 * @param string $img_name 图片名
 * @param string $image base64图片
 * @return string
 */
function base64ToImg($img_name, $image)
{
    require Env::get('extend_path') . '/cos/cos-autoloader.php';
    $bucket = 'thirdparty-1257007004';
    if (strstr($image, ",")) {
        $image = explode(',', $image);
        $image = $image[1];
    }
    $img_file = base64_decode($image);
    $cosClient = new \Qcloud\Cos\Client(array('region' => 'ap-guangzhou',
        'credentials' => array(
            'secretId' => 'AKIDT97rS6U8YvQEyqK7JZkBPYWYRHwUY3CA',
            'secretKey' => 'EICJseLF1e6d1EFMNEAHYKCvTq25QbEx')));
    try {
        $cosClient->putObject(array(
            'Bucket' => $bucket,
            'Key' => $img_name,
            'Body' => $img_file,
        ));
    } catch (\Exception $e) {
        die(json_encode(['data'=>false,'code'=>500,'message'=>'图片上传对象存储出错']));
    }
    return $img_name;
}