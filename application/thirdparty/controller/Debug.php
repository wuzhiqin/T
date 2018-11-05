<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/17
 * Time: 18:17
 */

namespace app\thirdparty\controller;

use app\common\model\AuthorizeParam;
use think\Request;
use think\db;

class Debug extends Tools {
	public $msgArr;
	public function index() {
		$arr = json_decode('{
    "signature": "30e85e744e07bf5fea107149a0833c2e10f434b9",
    "timestamp": "1540180175",
    "nonce": "960750756",
    "openid": "oofoVt352hUk0V6QyI5ZamhwLabI",
    "encrypt_type": "aes",
    "msg_signature": "fd1d3f778246f9407779cbb89d72a38969aa6a5a",
    "Action": "Custom",
    "func": "index",
    "appid": "wx35c492ba099b5f64"
}', 1);
		echo $this->ToUrlParams($arr);
	}
	public function t1(){
		$url = (new Authorize())->getAuth(base64_encode('http://admin.wxmanage.yueji1314.com/#/Home/OfficialMessage'));
		echo '<script>window.location.href = "'.$url.'"</script>';
	}
	public function t2(){
		$appid = '';
		(new AuthorizeParam())->save(['update_time'=>0],['name'=>'AuthorizerAccessToken_' . $appid]);
		$token = (new Authorize())->getAuthorizerToken($appid);
		return $token;
	}
	public function t3(){
		$pre_auth_code_url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . (new Authorize())->getComponentAccessToken();
		$pre_auth_code_result = json_decode($this->ext_curl($pre_auth_code_url, '{"component_appid":"' . AuthorizeConfig::APPID . '"}'), 1);
		if (!isset($pre_auth_code_result['pre_auth_code'])) die(json_encode(['data' => $pre_auth_code_result, 'message' => '预授权码获取失败', 'code' => 500])); //若错误打印错误信息
		$redirect_uri = app('request')->domain() . '/thirdparty/Debug/t4';

			$url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=' . AuthorizeConfig::APPID . '&pre_auth_code=' . $pre_auth_code_result['pre_auth_code'] . '&redirect_uri=' . $redirect_uri;
		echo '<script>window.location.href = "'.$url.'"</script>';
	}
	public function t4(){
		$access_token = (new Authorize())->getAuthorizerToken('wx03148b9b4d17e60f',1);
		$res = (new WXInterface($access_token))->delMaterial('e_IJlmjh3K47oURMiGRXvRoguQ0NKqDeGqO7OE363Wo1');
		return json($res);
	}

	public function t5($page=1,$limit=20){
		$data = Db::connect('mysql://root:yueji0791@gz-cdb-ebqjvubu.sql.tencentcdb.com:62608/wechat_manage_test#utf8')
			->name('wechat_account')
			->field('id,gh_id,wx_name as nick_name,scan_account,material_type,relation_id')
			->chunk(100, function($data) {
				$id_list = array();
				foreach ($data as $value){
					$id_list[]['id'] = $value['id'];

				}
				Db::connect('mysql://root:yueji0791@gz-cdb-ebqjvubu.sql.tencentcdb.com:62608/wechat_third_party#utf8')
					->name('wechat_account')
					->data($data)
					->insertAll();
				Db::connect('mysql://root:yueji0791@gz-cdb-ebqjvubu.sql.tencentcdb.com:62608/wechat_third_party#utf8')
					->name('wechat_attach')
					->data($id_list)
					->insertAll();

			});
		echo 'success'.time();
/*		dump($data);
		$res = Db::connect('mysql://root:yueji0791@gz-cdb-ebqjvubu.sql.tencentcdb.com:62608/wechat_third_party#utf8')
			->name('material_account')
			->data($data)
			->insertAll();
		dump($res);*/
	}



}