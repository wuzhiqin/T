<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/8/28
 * Time: 13:36
 */

namespace app\admin\controller\v1;

use think\facade\Env;
use think\facade\Request;
use Db;

class Test {
	private $token = '14_h1mkcbSlvO_DPyycF0Y1K0GkuKV7HtbVWH29eZwp7b8YEnxoK-KA3tP_1Gu4zWf_-74_GGRUvcA3MUBhXR375fIxC1jBdP4we3WVbSO0XviUsgYx9rEvx-3j1w5AHNP07O9AfiWekiND--D1JUZjADARCH';
	public function get_token_pastor(){
		$a='wx5c787c18c8da0fa9';
		$b = 'd4624c36b6795d1d99dcf0547af5443d';
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$a."&secret=".$b;
		$ch = curl_init();//初始化curl
		curl_setopt($ch,CURLOPT_URL,$url);                          //要访问的地址
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);    //跳过证书验证
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);    // 从证书中检查SSL加密算法是否存在
		$data = json_decode(curl_exec($ch),1);
		dump($data);

	}
	public function get_token_wang(){
		$a='wx0097cacba77b73a0';
		$b = 'c29b15b7bdef7479c995f0f10d6cc05b';
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$a."&secret=".$b;
		$ch = curl_init();//初始化curl
		curl_setopt($ch,CURLOPT_URL,$url);                          //要访问的地址
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);    //跳过证书验证
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);    // 从证书中检查SSL加密算法是否存在
		$data = json_decode(curl_exec($ch),1);
		dump($data);

	}
	public function get_token_er(){
		//14_bYTj73xLzt_7mYK44WSw4o2gv7rRNyl3KCnP-Q6WAXsQyRcFBemiEywMAdui__Ii3gTwV3sRaKi0g7s8bnw6CVudeX1pb829QDk_P0M2ECXcXAN4VyE5_uSu_wyk-Jf2wetNuN5Gzh1YEldSNVEbAGAJRA
		$a='wx5b6d1faac01571bf';
		$b = '83f06b59f7047708c9c30b2d4fd0316d';
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$a."&secret=".$b;
		$ch = curl_init();//初始化curl
		curl_setopt($ch,CURLOPT_URL,$url);                          //要访问的地址
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);    //跳过证书验证
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);    // 从证书中检查SSL加密算法是否存在
		$data = json_decode(curl_exec($ch),1);
		dump($data);

	}
	public function get_token_yi(){
		$a='wx03148b9b4d17e60f';
		$b = '4f713323e561ae57eed0f827a4236b9a';
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$a."&secret=".$b;
		$ch = curl_init();//初始化curl
		curl_setopt($ch,CURLOPT_URL,$url);                          //要访问的地址
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);    //跳过证书验证
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);    // 从证书中检查SSL加密算法是否存在
		$data = json_decode(curl_exec($ch),1);
		dump($data);

	}
	public function get_material(){
		$url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$this->token;
//		$del_url = 'https://api.weixin.qq.com/cgi-bin/material/del_material?access_token='.$this->token;
		$data = json_encode(['type'=>'news','offset'=>0,'count'=>20]);
		$res = curlPost($url,$data);
		$res = json_decode($res,1);
		dump($res);

	}

	/********************************上面为测试用常备方法*******************************************/
	public function test_third_party_encrypt_fun() {
// 第三方发送消息给公众平台
		$encodingAesKey = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG";
		$token = "pamtest";
		$timeStamp = "1409304348";
		$nonce = "xxxxxx";
		$appId = "wxb11529c136998cb6";
		$text = "<xml><ToUserName><![CDATA[oia2Tj我是中文jewbmiOUlr6X-1crbLOvLw]]></ToUserName><FromUserName><![CDATA[gh_7f083739789a]]></FromUserName><CreateTime>1407743423</CreateTime><MsgType><![CDATA[video]]></MsgType><Video><MediaId><![CDATA[eYJ1MbwPRJtOvIEabaxHs7TX2D-HV71s79GUxqdUkjm6Gs2Ed1KF3ulAOA9H1xG0]]></MediaId><Title><![CDATA[testCallBackReplyVideo]]></Title><Description><![CDATA[testCallBackReplyVideo]]></Description></Video></xml>";

		include_once "../extend/wxcrypt/wxBizMsgCrypt.php";
		$pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);
		$encryptMsg = '';
		$errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
		if ($errCode == 0) {
			print("加密后: " . $encryptMsg . "\n");
		} else {
			print($errCode . "\n");
		}

		$xml_tree = new \DOMDocument();
		$xml_tree->loadXML($encryptMsg);
		$array_e = $xml_tree->getElementsByTagName('Encrypt');
		$array_s = $xml_tree->getElementsByTagName('MsgSignature');
		$encrypt = $array_e->item(0)->nodeValue;
		$msg_sign = $array_s->item(0)->nodeValue;

		$format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);

// 第三方收到公众号平台发送的消息
		$msg = '';
		$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
		if ($errCode == 0) {
			print("解密后: " . $msg . "\n");
		} else {
			print($errCode . "\n");
		}
	}
	public function t1() {
		$str = "Hello World!";
		echo $str . "<br>";
		echo trim($str,"HWrd!");
		exit;
		$res = file_get_contents('data/authsrize.json');
//		$res = json_decode()
		echo $res;
	}
	public function t2(){
		$obj = new Authorize();
		$authorizer_info_url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' . $obj->getComponentAccessToken();
		$authorizer_info_post = '{"component_appid":"wxb940042f41e2928a" , "authorizer_appid": "wx35c492ba099b5f64"}';
//		$authorizer_info_post = '{"component_appid":"wxb940042f41e2928a" , "authorizer_appid": "wx03148b9b4d17e60f"}';
		$authorizer_info_res = $obj->ext_curl($authorizer_info_url, $authorizer_info_post);
//		dump(json_decode($authorizer_info_res,1));
		echo($authorizer_info_res);
		exit;
	}
	public function moveAccount(){
		Db::name('wechat_account')->field('id,appid,material_type,relation_id,scan_account,wx_name')->chunk(100, function($infos) {
			foreach ($infos as $info) {
				$data['id'] = $info['id'];
				$data['appid'] = $info['appid'];
				$data['nick_name'] = $info['wx_name'];
				$data['service_type_info'] =3;
				$data['verify_type_info'] =0;
				$data['refresh_token'] =0;
				$data['func_info'] = 0;
				$data['authorization_time'] = time();
				$data['material_type'] = $info['material_type'];
				$data['relation_id'] = $info['relation_id'];
				$data['scan_account'] = $info['scan_account'];
				$list[] = $data;
			}
			Db::name('wechat_account_third_party')->insertAll($list);
		});
	}

	/**
	 * 授权事件接收URL
	 * @access public
	 *
	 */
	public function sysMessage()
	{
		$wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();

		$encodingAesKey = $this->encodingAesKey;
		$token = $this->token;
		$appId = $this->appId;
		$timeStamp  = empty($_GET['timestamp'])     ? ""    : trim($_GET['timestamp']) ;
		$nonce      = empty($_GET['nonce'])     ? ""    : trim($_GET['nonce']) ;
		$msg_sign   = empty($_GET['msg_signature']) ? ""    : trim($_GET['msg_signature']) ;
		$encryptMsg = file_get_contents('php://input', 'r');

		libxml_disable_entity_loader(true);
		$result = json_decode(json_encode(simplexml_load_string($encryptMsg, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

		if(config('redis_set')['use_status']){
			$redis = $wxComponentTicketModel->initializeRedis();
			$redis->set('wx_component_verify_ticket_xml',$encryptMsg);
		}

		$pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);

		$xml_tree = new \DOMDocument();
		$xml_tree->loadXML($encryptMsg);
		$array_e = $xml_tree->getElementsByTagName('Encrypt');
		$encrypt = $array_e->item(0)->nodeValue;


		$format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);

		// 第三方收到公众号平台发送的消息
		$msg = '';
		$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
		if ($errCode == 0) {
			//print("解密后: " . $msg . "\n");
			$xml = new \DOMDocument();
			$xml->loadXML($msg);
			$array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
			$component_verify_ticket = $array_e->item(0)->nodeValue;

			//logResult('解密后的component_verify_ticket是：'.$component_verify_ticket);

			$dateline = time();

			$data = array(
				'app_id' => $result['AppId'],
				'encrypt' => $result['Encrypt'],
				'create_time' => $dateline + 600,
				//'info_type' => $result['AppId'],
				'component_verify_ticket' => $component_verify_ticket,
				'time' => date('Y-m-d H:i:s')
			);

			if(config('redis_set')['use_status']){
				$redis = $wxComponentTicketModel->initializeRedis();
				$redis->set('wx_component_verify_ticket',json_encode($data));
			}

			$existComponentTicke = $wxComponentTicketModel->get(array('component_verify_ticket'=>$component_verify_ticket));
			if(!$existComponentTicke){
				$wx = $wxComponentTicketModel->create($data);
				if($wx){
					echo 'success';
					exit;
				}else{
					echo 'fail';
					exit;
				}
			}else{
				echo 'success';
				exit;
			}
		}else{
			echo 'fail';
			exit;
		}
	}

	/**
	 * 公众号消息与事件接收URL
	 * @access public
	 *
	 */
	public function callback()
	{
		$wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();
		$wxCallbackModel = new \app\diuber\model\WxCallback();
		$wxAccessTokenModel = new \app\diuber\model\WxAccessToken();

		$encodingAesKey = $this->encodingAesKey;
		$token = $this->token;
		$appId = $this->appId;
		$timeStamp  = empty($_GET['timestamp'])     ? ""    : trim($_GET['timestamp']) ;
		$nonce      = empty($_GET['nonce'])     ? ""    : trim($_GET['nonce']) ;
		$msg_sign   = empty($_GET['msg_signature']) ? ""    : trim($_GET['msg_signature']) ;

		$encryptMsg = file_get_contents('php://input');
		$pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);

		$xml_tree = new \DOMDocument();
		$xml_tree->loadXML($encryptMsg);
		$array_e = $xml_tree->getElementsByTagName('Encrypt');
		$encrypt = $array_e->item(0)->nodeValue;


		$format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);

		// 第三方收到公众号平台发送的消息
		$msg = '';
		$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
		if ($errCode == 0) {
			$xml = new \DOMDocument();
			$xml->loadXML($msg);

			$array_e2 = $xml->getElementsByTagName('ToUserName');
			$ToUserName = $array_e2->item(0)->nodeValue;
			$array_e3 = $xml->getElementsByTagName('FromUserName');
			$FromUserName = $array_e3->item(0)->nodeValue;
			$array_e5 = $xml->getElementsByTagName('MsgType');
			$MsgType = $array_e5->item(0)->nodeValue;
			$nowTime = date('Y-m-d H:i:s');
			$contentx = '';


			if($MsgType=="text") {
				$array_e = $xml->getElementsByTagName('Content');
				$content = $array_e->item(0)->nodeValue;
				$needle ='QUERY_AUTH_CODE:';
				$tmparray = explode($needle,$content);
				if(count($tmparray) > 1){
					//3、模拟粉丝发送文本消息给专用测试公众号，第三方平台方需在5秒内返回空串
					//表明暂时不回复，然后再立即使用客服消息接口发送消息回复粉丝
					$contentx = str_replace ($needle,'',$content);
					$info = $wxAccessTokenModel->getMiniAppInfo($contentx);
					$test_token = $info['info']['authorizer_access_token'];
					$content_re = $contentx."_from_api";
					echo '';
					$data = '{
                            "touser":"'.$FromUserName.'",
                            "msgtype":"text",
                            "text":
                            {
                                 "content":"'.$content_re.'"
                            }
                        }';
					$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$test_token;
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_exec($ch);
					curl_close($ch);
				}else{
					//2、模拟粉丝发送文本消息给专用测试公众号
					$contentx = "TESTCOMPONENT_MSG_TYPE_TEXT_callback1";
				}
			}elseif($MsgType == "event"){ //1、模拟粉丝触发专用测试公众号的事件
				$array_e4 = $xml->getElementsByTagName('Event');
				$event = $array_e4->item(0)->nodeValue;
				$contentx = $event.'from_callback';
			}

			$text = "<xml>
            <ToUserName><![CDATA[$FromUserName]]></ToUserName>
            <FromUserName><![CDATA[$ToUserName]]></FromUserName>
            <CreateTime>$nowTime</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[$contentx]]></Content>
                    </xml>";

			//加密消息
			$encryptMsg = '';
			$errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);

			$wxCallbackModel->create(array('from_user_name'=>$FromUserName,'to_user_name'=>$ToUserName,'msg_type'=>$MsgType,'content'=>$contentx,'create_time'=>$timeStamp));
			echo $encryptMsg;
			exit();
		} else {
			if(config('redis_set')['use_status']){
				$redis = $wxComponentTicketModel->initializeRedis();
				$redis->set('wx_call_back_err',$errCode);
			}
			exit();
		}
	}



}