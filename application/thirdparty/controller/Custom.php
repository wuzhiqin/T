<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/20
 * Time: 11:15
 */

namespace app\thirdparty\controller;


use app\common\model\WechatAccount;

class Custom extends Tools {
	/**
	 * 接收微信推送的ComponentVerifyTicket
	 * @return string
	 */
	protected $appid;
	protected $signature;
	protected $timestamp;
	protected $nonce;
	protected $openid;
	protected $encrypt_type;
	protected $msg_signature;
	protected $wechat_info;

	protected $msgArr = array('MsgType' => 'Miss');

	protected $crypt;

	public function __construct() {
		$this->crypt = new WXBizMsgCrypt(AuthorizeConfig::TOKEN, AuthorizeConfig::ENCRYPTKEY, AuthorizeConfig::APPID);
	}


	public function index($appid) {
		//get参数
		$param = get_param('msg_signature,timestamp,nonce,openid,signature,encrypt_type', 1);
		$this->appid = $appid;
		$this->msg_signature = $param['msg_signature'];
		$this->timestamp = $param['timestamp'];
		$this->nonce = $param['nonce'];
		$this->openid = $param['openid'];
		$this->signature = $param['signature'];
		$this->encrypt_type = $param['encrypt_type'];
		//接收
		$xml = file_get_contents("php://input");
		file_put_contents('QUERY_AUTH_CODE.log',time().'------------------------'.PHP_EOL,8);
		file_put_contents('QUERY_AUTH_CODE.log',json_encode($param,320).PHP_EOL,8);
		file_put_contents('QUERY_AUTH_CODE.log',$xml.PHP_EOL,8);
		//检查是否正常解密
		$errmsg = $this->crypt->decryptMsg($this->msg_signature, $this->timestamp, $this->nonce, $xml, $msg);
		if ($errmsg) {
			error_record(2, '开发者模式消息解密错误：' . $errmsg);
			return 'success';
		}
		$this->wechat_info = (new WechatAccount())->where('appid', '=', $this->appid)->find();
		if (!$this->wechat_info) {
			error_record(2, '开发者模式消息公众号错误，未找到公众号：' . $this->appid);
			return 'success';
		}
		//转为数组 将用户消息存入公共信息;
		$this->msgArr = $this->FromXml($msg);
		file_put_contents('QUERY_AUTH_CODE.log',$msg.PHP_EOL,8);
		switch ($this->msgArr['MsgType']) {
			case 'text':
				$this->responseText();
				break;
			case 'image':
				$this->responseImage();
				break;
			case 'voice':
				$this->responseVoice();
				break;
			case 'video':
				$this->responseVideo();
				break;
			case 'shortvideo':
				$this->responseShortVideo();
				break;
			case 'location':
				$this->responseLocation();
				break;
			case 'link':
				$this->responseLink();
				break;
			case 'event':
				$this->responseEvent();
				break;
			default:
				$this->responseDefault();
				break;

		}

	}

	/*************************根据用户发来的消息类型进行处理****************************/
	//处理用户发来的文本消息
	public function responseText() {
		//处理消息
		//全网发布检查
		$this->thirdPartyCheck();
		//选择合适的回复
		$this->transmitText($this->msgArr['Content']);
	}

	//处理用户发来的图片消息
	public function responseImage() {
//		file_put_contents('img_msg.log', json_encode($this->msgArr,320));
		$content = '图片地址: ' . $this->msgArr['PicUrl'] . PHP_EOL . '图片media_id: ' . $this->msgArr['MediaId'];
		//处理流程
		$this->transmitText($content);
//		$this->transmitImage($this->msgArr['MediaId']);
	}

	//处理用户发来的音频消息
	public function responseVoice() {
		$this->transmitText('voice');
	}

	//处理用户发来的视频消息
	public function responseVideo() {
		$this->transmitText('video');
	}//处理用户发来的短视频消息

	public function responseShortVideo() {
		$this->transmitText('shortvideo');
	}

	//处理用户发来的地址位置消息
	public function responseLocation() {
		$this->transmitText('location');
	}

	//处理用户发来的链接消息
	public function responseLink() {
		$this->transmitText('link消息');
	}

	//处理用户发来的事件消息
	public function responseEvent() {
		switch ($this->msgArr['Event']) {
			case 'subscribe':
				$this->transmitText('关注事件');
				break;
			case 'unsubscribe':
				echo '';
				break;
			case 'CLICK':
				echo '';
				break;
			case 'MASSSENDJOBFINISH'://群发消息原创校验
				(new MassSendJobFinish($this->msgArr))->index();
				echo '';
				break;

			case 'SCAN':
				$this->transmitText('扫码但是已关注');
				break;
			case 'LOCATION':
				$this->transmitText('上报地理事件');
				break;

		}

	}

	//处理用户发来的其他消息
	public function responseDefault() {
		$this->transmitText('暂不支持此类型消息，敬请期待');
	}



	/*********回复用户各种类型的消息****************/

	/**
	 * 发送文本消息
	 * @param string $content 消息文本
	 */
	public function transmitText($content) {
		$array['MsgType'] = 'text';
		$array['Content'] = $content;
		$this->sendMsg($array);
	}

	/**
	 * 发送图片消息
	 * @param string $media_id 从微信获取的media_id
	 */
	public function transmitImage($media_id) {
		$array['MsgType'] = 'image';
		$array['MediaId'] = $media_id;//从微信获取
		$this->sendMsg($array);
	}

	/**
	 * 发送音频消息
	 * @param string $media_id 从微信获取的media_id
	 */
	public function transmitVoice($media_id) {
		$array['MsgType'] = 'voice';
		$array['MediaId'] = $media_id;//从微信获取
		$this->sendMsg($array);
	}

	/**
	 * 发送视频消息
	 * @param string $media_id 从微信获取的media_id
	 * @param string $title 视频标题
	 * @param string $description 视频简介
	 */
	public function transmitVideo($media_id, $title = '', $description = '') {
		$array['MsgType'] = 'video';
		$array['MediaId'] = $media_id;//从微信获取
		$title ? $array['Title'] = $title : null;
		$description ? $array['Description'] = $description : null;
		$this->sendMsg($array);
	}

	/**
	 * 发送音乐消息
	 * @param string $music_url 音乐连接
	 * @param string $thumb_media_id 缩略图的media_id
	 * @param string $hq_music_url 高品质音乐链接
	 * @param string $title 音乐标题
	 * @param string $description 音乐简介
	 */
	public function transmitMusic($music_url, $thumb_media_id, $hq_music_url = '', $title = '', $description = '') {
		$array['MsgType'] = 'music';
		$array['MusicURL'] = $music_url;
		$array['ThumbMediaId'] = $thumb_media_id;//从微信获取
		$title ? $array['Title'] = $title : null;
		$description ? $array['Description'] = $description : null;
		$hq_music_url ? $array['HQMusicUrl'] = $hq_music_url : null;

		$this->sendMsg($array);
	}

	/**
	 * 发送图文消息
	 * @param array $news_array 图文数组 array(array('Title'=>'测试标题1','Description'=>'测试简介1','PicUrl'=>'图片url1','Url'=>'跳转链接1',),array('Title'=>'测试标题2','Description'=>'测试简介2','PicUrl'=>'图片url2','Url'=>'跳转链接2',))
	 */
	public function transmitNews($news_array) {
		$array['MsgType'] = 'news';
		$array['ArticleCount'] = count($news_array);
		$array['Articles'] = $news_array;
		$this->sendMsg($array);
	}

	/**
	 * 拼接必要参数并加密发送
	 * @param array $array
	 */
	public function sendMsg(array $array) {
		$array['ToUserName'] = $this->msgArr['FromUserName'];
		$array['FromUserName'] = $this->msgArr['ToUserName'];
		$array['CreateTime'] = time();
		$xml = $this->ToXml($array);
//		file_put_contents('custom_decode.log', $xml, 8);
		$code = $this->crypt->encryptMsg($xml, $this->timestamp, $this->nonce, $encryptMsg);
		if ($code) {
			error_record(2, '加密消息出错:' . $code);
		}
//		file_put_contents('custom_decode.log', $encryptMsg, 8);
		echo $encryptMsg;
	}


	/*********发送客服消息****************/

	/**
	 * 发送文本消息
	 * @param string $content 消息文本
	 */
	public function transmitCustomText($content) {
		$array['msgtype'] = 'text';
		$array['text']['content'] = $content;
		$this->sendCustomMsg($array);
		//<a href="http://www.qq.com" data-miniprogram-appid="appid" data-miniprogram-path="pages/index/index">点击跳小程序</a>
	}

	/**
	 * 发送图片消息
	 * @param string $media_id 从微信获取的media_id
	 */
	public function transmitCustomImage($media_id) {
		$array['msgtype'] = 'image';
		$array['image']['media_id'] = $media_id;//从微信获取

		$this->sendCustomMsg($array);
	}

	/**
	 * 发送音频消息
	 * @param string $media_id 从微信获取的media_id
	 */
	public function transmitCustomVoice($media_id) {
		$array['msgtype'] = 'voice';
		$array['voice']['media_id'] = $media_id;//从微信获取
		$this->sendCustomMsg($array);
	}

	/**
	 * 发送视频消息
	 * @param string $media_id 从微信获取的media_id
	 * @param string $thumb_media_id 从微信获取的thumb_media_id
	 * @param string $title 视频标题
	 * @param string $description 视频简介
	 */
	public function transmitCustomVideo($media_id, $thumb_media_id, $title = '', $description = '') {
		$array['msgtype'] = 'video';
		$array['video']['media_id'] = $media_id;//从微信获取
		$array['video']['thumb_media_id'] = $thumb_media_id;
		$array['video']['title'] = $title;
		$array['video']['description'] = $description;
		$this->sendCustomMsg($array);
	}

	/**
	 * 发送音乐消息
	 * @param string $music_url 音乐连接
	 * @param string $thumb_media_id 缩略图的media_id
	 * @param string $hq_music_url 高品质音乐链接
	 * @param string $title 音乐标题
	 * @param string $description 音乐简介
	 */
	public function transmitCustomMusic($music_url, $thumb_media_id, $hq_music_url = '', $title = '', $description = '') {
		$array['msgtype'] = 'music';
		$array['music']['title'] = $title;
		$array['music']['description'] = $description;
		$array['music']['musicurl'] = $music_url;
		$array['music']['hqmusicurl'] = $hq_music_url;
		$array['music']['thumb_media_id'] = $thumb_media_id;
		$this->sendCustomMsg($array);
	}

	/**
	 * 发送图文消息('外链')
	 * @param $title
	 * @param $url
	 * @param $picurl
	 * @param string $description
	 */
	public function transmitCustomNews($title, $url, $picurl, $description = '') {
		$array['msgtype'] = 'news';
		$array['news']['articles'][] = array(
			'title' => $title,
			'description' => $description,
			'url' => $url,
			'picurl' => $picurl,
		);
		$this->sendCustomMsg($array);
	}

	/**
	 * 发送图文消息('图文消息')
	 * @param $media_id
	 */
	public function transmitCustommpNews($media_id) {
		$array['msgtype'] = 'mpnews';
		$array['mpnews']['media_id'] = $media_id;
		$this->sendCustomMsg($array);
	}

	/**
	 * 拼接必要参数并加密发送
	 * @param array $array
	 */
	public function sendCustomMsg(array $array) {
		$array['touser'] = $this->openid;
		$post = json_encode($array, 320);
		file_put_contents('send_custom_msg.log',json_encode($post,320));
		$access_token = (new Authorize())->getAuthorizerToken($this->wechat_info['appid']);
		file_put_contents('send_custom_msg.log',PHP_EOL.$access_token.PHP_EOL,8);
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
		file_put_contents('send_custom_msg.log',PHP_EOL.$url.PHP_EOL,8);
		$res = ext_curl($url, $post);
		file_put_contents('send_custom_msg.log',$res,8);
	}


	/*******************第三方平台接入检测**********************/

	private function thirdPartyCheck() {
		if ($this->msgArr['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
			$this->transmitText('TESTCOMPONENT_MSG_TYPE_TEXT_callback');
			exit;
		}
		if (stristr($this->msgArr['Content'], 'QUERY_AUTH_CODE:')) {

			$auth_code = substr($this->msgArr['Content'], 16);

			//使用auth_code获取二维码
			$authorizer_auth_url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . (new Authorize())->getComponentAccessToken();
			file_put_contents('QUERY_AUTH_CODE.log',$auth_code.PHP_EOL,8);
			$authorizer_auth_post = '{"component_appid":"' . AuthorizeConfig::APPID . '" ,"authorization_code": "' . $auth_code . '"}';
			file_put_contents('QUERY_AUTH_CODE.log',$authorizer_auth_post.PHP_EOL,8);

			$res = $this->ext_curl($authorizer_auth_url, $authorizer_auth_post,'','',1);
			file_put_contents('QUERY_AUTH_CODE.log',json_encode($res,320).PHP_EOL,8);

			if(isset($res['authorization_info']['authorizer_access_token'])){
				$array['msgtype'] = 'text';
				$array['text']['content'] = $auth_code.'_from_api';
				$array['touser'] = $this->openid;
				$post = json_encode($array, 320);
				$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $res['authorization_info']['authorizer_access_token'];
				file_put_contents('QUERY_AUTH_CODE.log',json_encode($post,320).PHP_EOL,8);
				file_put_contents('QUERY_AUTH_CODE.log',$url.PHP_EOL,8);
				$res =ext_curl($url, $post);
				file_put_contents('QUERY_AUTH_CODE.log',$res.PHP_EOL,8);
				echo'';
				exit;
			}else{
				$this->transmitText('非接入检测:'.$this->msgArr['Content']);
				exit;
			}

		}

	}

}