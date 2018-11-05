<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/7/17
 * Time: 17:45
 */

namespace app\thirdparty\controller;
use app\common\model\AuthorizeParam;
use app\common\model\WechatAccount;
use app\common\model\WechatAttach;
use Db;
use think\exception\DbException;
use think\facade\Cache;

class Authorize extends Tools {

	/**
	 * 接收微信推送的ComponentVerifyTicket
	 * @return string
	 */
	public function index() {
		$xml = file_get_contents("php://input");

		//检查接收参数
		/*file_put_contents('param.log', json_encode(request()->param(true),320));
		file_put_contents('authsrize.log', $xml);*/
		if ($xml) {
			$value = $this->FromXml($xml);
			if (isset($value['AppId']) && isset($value['Encrypt'])) {
				if ($value["AppId"] == AuthorizeConfig::APPID) {
					$t = new Prpcrypt(AuthorizeConfig::ENCRYPTKEY);
					$info = $t->decrypt($value["Encrypt"], $value["AppId"]);
					if (isset($info[0]) && $info[0] === 0) {
						$decryptinfo = $this->FromXml($info[1]);
						if (isset($decryptinfo['ComponentVerifyTicket'])) {
							(new AuthorizeParam())->save(['value'=>$decryptinfo['ComponentVerifyTicket'],'update_time'=>time()],['name'=>'ComponentVerifyTicket']);
						}
					}
				}
			} else {
				error_record(2, $xml);
			}
		}
		return 'success';
	}

	/**
	 * 获取ComponentVerifyTicket（第三方平台获取token的验证密钥）
	 * @return null|static
	 * @throws \think\Exception\DbException
	 */
	public function getComponentVerifyTicket() {
		$res = (new AuthorizeParam)->get('ComponentVerifyTicket');
		return $res['value'];
	}
	//获取ComponentAccessToken


	/**
	 * 获取ComponentAccessToken（第三方平台的token）
	 * @return string
	 * @throws \think\Exception\DbException
	 */
	public function getComponentAccessToken() {
		$res = (new AuthorizeParam)->get('ComponentAccessToken');
		if (isset($res['update_time']) &&$res['update_time']<time()-5400) {
			$url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
			$post['component_appid'] = AuthorizeConfig::APPID;
			$post['component_appsecret'] = AuthorizeConfig::APPSERCET;
			$post['component_verify_ticket'] = $this->getComponentVerifyTicket();
			$result = $this->ext_curl($url, json_encode($post, 320));
			$res = json_decode($result, 1);
			if (isset($res['component_access_token'])) {
				(new AuthorizeParam())->save(['value'=>$res['component_access_token'],'update_time'=>time()],['name'=>'ComponentAccessToken']);
				return $res['component_access_token'];
			} else {
				die(json_encode(['data' => false, 'message' => $result, 'code' => 500])); //若错误打印错误信息
			}
		}
		return $res['value'];

	}

	public function getAuthorizerToken($appid,$isdie =0) {
		try {
			$res = (new AuthorizeParam)->get('AuthorizerAccessToken_' . $appid);
		}catch (\Exception $e){
			if($isdie){
				die(json_encode($e->getMessage()));
			}else{
				return false;
			}
		}
		if (!isset($res['update_time']) ||$res['update_time']<time()-5400) {
			//查询数据库获取
			try{
				$auth_info = (new WechatAccount())->where('appid','=',$appid)->field('appid,refresh_token')->find();
			}catch (\Exception $e){
				error_record(1,'appid:'.$appid.'获取token时未获取到公众号信息');
				if($isdie){
					die(json_encode($e->getMessage()));
				}else{
					return false;
				}
			}
			if (!$auth_info) die(json_encode(['data' => $res, 'message' => '公众号token获取失败,无该数据:' . $appid, 'code' => 500]));
			try{
				$token = $this->getComponentAccessToken();
			}catch (\Exception $e){
				error_record(1,'appid:'.$appid.'获取token时未获取到第三方平台token');
				if($isdie){
					die(json_encode($e->getMessage()));
				}else{
					return false;
				}
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=' . $token;
			$post = '{"component_appid":"' . AuthorizeConfig::APPID . '","authorizer_appid":"' . $auth_info['appid'] . '","authorizer_refresh_token":"' . $auth_info['refresh_token'] . '",}';
			$res = json_decode($this->ext_curl($url, $post), 1);

			if (isset($res['authorizer_access_token'])) {
				(new AuthorizeParam())->save(['value'=>$res['authorizer_access_token'],'update_time'=>time()],['name'=>'AuthorizerAccessToken_' . $appid]);
				$res['value'] = $res['authorizer_access_token'];
			} else {
				die(json_encode(['data' => $res, 'message' => '公众号token获取失败:' . $appid, 'code' => 500]));
			}
		}
		return $res['value'];
	}

	/**
	 * 获取第三方平台授权url(type 0 为扫码，1为点击【只可在微信浏览器内使用】)
	 * @param string $return_url
	 * @param int $type
	 * @throws DbException
	 * @return string
	 */
	public function getAuth($return_url ='',$type = 0) {
		$pre_auth_code_url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . $this->getComponentAccessToken();
		$pre_auth_code_result = json_decode($this->ext_curl($pre_auth_code_url, '{"component_appid":"' . AuthorizeConfig::APPID . '"}'), 1);
		if (!isset($pre_auth_code_result['pre_auth_code'])) die(json_encode(['data' => $pre_auth_code_result, 'message' => '预授权码获取失败', 'code' => 500])); //若错误打印错误信息
		$redirect_uri = app('request')->domain() . '/thirdparty/Authorize/authCallback/return_url/'.$return_url;
		if (!$type) {
			$url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=' . AuthorizeConfig::APPID . '&pre_auth_code=' . $pre_auth_code_result['pre_auth_code'] . '&redirect_uri=' . $redirect_uri;
		} else {
			$url = 'https://mp.weixin.qq.com/safe/bindcomponent?action=bindcomponent&auth_type=3&no_scan=1&component_appid=' . AuthorizeConfig::APPID . '&pre_auth_code=' . $pre_auth_code_result['pre_auth_code'] . '&redirect_uri=' . $redirect_uri . '#wechat_redirect';
		}
//		dump($url);exit;
		return $url;

	}

	/**
	 * 授权回调(未处理小程序部分)
	 * @param string $auth_code 授权码
	 * @param $return_url
	 * @param int $isdie
	 * @throws DbException
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function authCallback($auth_code, $return_url,$isdie = 0) {
		//获取授权用户信息
		$authorizer_auth_url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . $this->getComponentAccessToken();
		$authorizer_auth_post = '{"component_appid":"' . AuthorizeConfig::APPID . '" ,"authorization_code": "' . $auth_code . '"}';
		$authorizer_auth_res = json_decode($this->ext_curl($authorizer_auth_url, $authorizer_auth_post), 1);
		if (!isset($authorizer_auth_res['authorization_info']['authorizer_appid'])) die(json_encode(['data' => false, 'message' => $authorizer_auth_res, 'code' => 500]));
		//存token
		try{
			$token = (new AuthorizeParam())->find('AuthorizerAccessToken_' . $authorizer_auth_res['authorization_info']['authorizer_appid']);
			if($token){
				$token->isUpdate(true)->save(['name'=>'AuthorizerAccessToken_'.$authorizer_auth_res['authorization_info']['authorizer_appid'],'value'=>$authorizer_auth_res['authorization_info']['authorizer_access_token'],'update_time'=>time()]);
			}else{
				(new AuthorizeParam())->save(['name'=>'AuthorizerAccessToken_'.$authorizer_auth_res['authorization_info']['authorizer_appid'],'value'=>$authorizer_auth_res['authorization_info']['authorizer_access_token'],'update_time'=>time()]);
			}

		}catch (\Exception $e){
			if($isdie){
				die(json_encode($e->getMessage()));
			}else{
				return false;
			}
		}
		//请求公众号详细信息
		$authorizer_info_url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' . $this->getComponentAccessToken();
		$authorizer_info_post = '{"component_appid":"'.AuthorizeConfig::APPID.'" , "authorizer_appid": "'.$authorizer_auth_res['authorization_info']['authorizer_appid'].'"}';
		$authorizer_info_res = json_decode($this->ext_curl($authorizer_info_url, $authorizer_info_post), 1);
		if (!isset($authorizer_info_res['authorization_info']['authorizer_appid'])) die(json_encode(['data' => false, 'message' => $authorizer_info_res, 'code' => 500]));

		$data['appid'] = $authorizer_info_res['authorization_info']['authorizer_appid'];//appid
		$data['refresh_token'] = $authorizer_info_res['authorization_info']['authorizer_refresh_token'];//刷新token
		$data['func_info'] = json_encode($authorizer_info_res['authorization_info']['func_info']);//权限集（json）
		$data['authorization_time'] = time();//授权时间

		$data['nick_name'] = $authorizer_info_res['authorizer_info']['nick_name'];//公众号名称
		$data['service_type_info'] = $authorizer_info_res['authorizer_info']['service_type_info']['id'];//公众号类型，0代表订阅号，1代表由历史老帐号升级后的订阅号，2代表服务号
		//未辨别各种认证权限，此处设置为非未认证的皆为认证
		$authorizer_info_res['authorizer_info']['verify_type_info']['id']>-1?$data['verify_type_info'] = 0:$data['verify_type_info'] = -1;//授权方认证类型，-1代表未认证，0代表微信认证，1代表新浪微博认证，2代表腾讯微博认证，3代表已资质认证通过但还未通过名称认证，4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证，5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证
		$data['gh_id'] = $authorizer_info_res['authorizer_info']['user_name'];//公众号的原始ID

		$wechatAccount = new WechatAccount();
		$is = $wechatAccount->where('gh_id', '=', $authorizer_info_res['authorizer_info']['user_name'])->find();
		if ($is) {
			$is->save($data);
			$account_id = 0;

		} else {
			$account_id = $wechatAccount->insertGetId($data);
			//向附加表增加信息
			(new WechatAttach())->insert(['id'=>$account_id]);

		}

		$uri = base64_decode($return_url).'?account_id='.$account_id;
		header('Location:'.$uri);
		//跳转至指定页面
//		dump($return_url);exit;
//		return $uri;
	}

	//清除缓存
	public function clearCache($type = 'all', $reget = 0,$isdie = 0) {
		$res = true;
		switch ($type) {
			case 'all':
				try {
					Cache::clear();
				} catch (\Exception $e) {
					if($isdie){
						die(json_encode($e->getMessage()));
					}else{
						return false;
					}
				}
				break;
			case 'component_access_token':
				(new AuthorizeParam())->save(['update_time'=>0],['name'=>'ComponentAccessToken']);
				if ($reget) {
					try {
						$res = $this->getComponentAccessToken();
					}catch(\Exception $e){
						if($isdie){
							die(json_encode($e->getMessage()));
						}else{
							return false;
						}
					}
				}
				break;
			default:
				$authorizeParam =  new AuthorizeParam();
				try{
					$is = $authorizeParam->where(['name','=','AuthorizerAccessToken_' . $type])->find();
				}catch (\Exception $e){
					if($isdie){
						die(json_encode($e->getMessage()));
					}else{
						return false;
					}
				}
				if($is){
					$authorizeParam->save(['update_time'=>0],['name'=>'AuthorizerAccessToken_' . $type]);
					if ($reget) {
						$res = $this->getAuthorizerToken($type);
					}
				}else{
					return false;
				}


				break;
		}
		return json(['data' => $res, 'message' => '清理成功', 'code' => 200]);
	}

	/**
	 * 清除接口调用次数
	 * @return \think\response\Json
	 */
	public function resetInterFaceCount($isdie = 0){
		try {
			$token = $this->getComponentAccessToken();
		}catch(\Exception $e){
			if($isdie) die(json_encode($e->getMessage()));
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/component/clear_quota?component_access_token=' . $token;
		$post = '{"component_appid":"'.AuthorizeConfig::APPID.'"}';
		$res = json_decode($this->ext_curl($url, $post), 1);
		if($res['errcode']){
			return response_json($res,500,'错误');
		}else{
			return response_json(true);
		}
	}



}