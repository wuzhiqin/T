<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/25
 * Time: 17:55
 */

namespace app\thirdparty\controller;


use app\common\model\Send;
use app\common\model\WechatAccount;

class MassSendJobFinish extends Tools {
	private $mass_info;
	private $send_msg_info;
	private $wechat_info;

	public function __construct($mass_info) {
		$this->mass_info = $mass_info;
	}
	public function index() {
		file_put_contents('mass_info2.log',json_encode($this->mass_info,320));
		/*$json = '{"ToUserName":"gh_bb7f72066f16","FromUserName":"oHK9L0-IBK4qgeVjjJhFhWFlKeV8","CreateTime":"1540628607","MsgType":"event","Event":"MASSSENDJOBFINISH","MsgID":"1000000005","Status":"err(30003)","TotalCount":"0","FilterCount":"0","SentCount":"0","ErrorCount":"0","CopyrightCheckResult":{"Count":"2","ResultList":{"item":[{"ArticleIdx":"4","UserDeclareState":"0","AuditState":"2","OriginalArticleUrl":"http://mp.weixin.qq.com/s?__biz=MzU1OTYzNDA0Mw==&mid=2247483773&idx=1&sn=10b075385ec0551dac3e7ae067df1764&chksm=fc150269cb628b7f78896e414e1430b8581038e8d5c92703008acf35fefe5df7b3fb4ceb042c#rd","OriginalArticleType":"1","CanReprint":"1","NeedReplaceContent":"1","NeedShowReprintSource":"1"},{"ArticleIdx":"5","UserDeclareState":"0","AuditState":"2","OriginalArticleUrl":"http://mp.weixin.qq.com/s?__biz=MzA3NzAzNDI1Mg==&mid=2650156555&idx=5&sn=d2eb09ac316298398f0d911074d29cd4&chksm=875aa868b02d217e9d512c1623a52c08b39c3cbf7d2002f144f5b58df169c1031c6baeec97e2#rd","OriginalArticleType":"1","CanReprint":"1","NeedReplaceContent":"1","NeedShowReprintSource":"1"}]},"CheckState":"2"}}';
		dump(json_decode($json,1));
		$this->mass_info = json_decode($json, 1);
		dump($this->mass_info);*/
		/*
		 * 	群发的结构，为“send success”或“send fail”或“err(num)”。但send success时，也有可能因用户拒收公众号的消息、系统错误等原因造成少量用户接收失败。
		 * send fail
		 * send success
		 * err(num)是审核失败的具体原因，可能的情况如下：
		 * err(10001), //涉嫌广告
		 * err(20001), //涉嫌政治
		 * err(20004), //涉嫌社会
		 * err(20002), //涉嫌色情
		 * err(20006), //涉嫌违法犯罪
		 * err(20008), //涉嫌欺诈
		 * err(20013), //涉嫌版权
		 * err(22000), //涉嫌互推(互相宣传)
		 * err(21000), //涉嫌其他
		 * err(30001) // 原创校验出现系统错误且用户选择了被判为转载就不群发
		 * err(30002) // 原创校验被判定为不能群发
		 * err(30003) // 原创校验被判定为转载文且用户选择了被判为转载就不群发
		 */


		$this->wechat_info = (new WechatAccount())->where('gh_id', '=', $this->mass_info['ToUserName'])->find();
		if(!$this->wechat_info)die('未找到公众号');
//		dump($this->wechat_info);
		$this->send_msg_info = (new Send())->where('wechat_id', '=', $this->wechat_info['id'])->where('msg_id', '=', $this->mass_info['MsgID'])->find();
		if(!$this->send_msg_info)die('未找到发送消息');
//		dump($this->send_msg_info);
		switch ($this->mass_info['Status']) {
			case 'send success':
				$this->send_msg_info->isUpdate(true)->save(['status' => 200, 'return_msg' => '发送成功']);
				break;
			case 'send fail':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '发送失败，未知原因']);
				break;
			case 'err(10001)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌广告']);
				break;
			case 'err(20001)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌政治']);
				break;
			case 'err(20004)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌社会']);
				break;
			case 'err(20002)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌色情']);
				break;
			case 'err(20006)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌违法犯罪']);
				break;
			case 'err(20008)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌欺诈']);
				break;
			case 'err(20013)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌版权']);
				break;
			case 'err(22000)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌互推(互相宣传)']);
				break;
			case 'err(21000)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '涉嫌其他']);
				break;
			case 'err(30001)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '原创校验出现系统错误且用户选择了被判为转载就不群发']);
				break;
			case 'err(30002)':
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '原创校验被判定为不能群发']);
				break;
			case 'err(30003)':
//				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '有原创文章，正在处理']);
				//处理原创文章
				$this->originalHandle();
				break;


		}
	}

	/**
	 * 原创文章处理
	 */
	private function originalHandle() {
		/*
		 * 取出原创文章序号
		 * 根据media_id查询文章，根据序号删除对应文章
		 * 统计文章剩余数量，若少于四篇则补对应篇数
		 * 篇数足够，按照正常走
		 * 篇数不足则停止发送
		 *
		 */
		//判断原创是否超过4，超过4篇进入重发流程
		if ($this->mass_info['CopyrightCheckResult']['Count'] > 4) {
			//判断是否已经重发过一次，重发过则不在群发，防止进入死循环
			if($this->send_msg_info['json_log']=='original'){
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '已尝试过重新发送，依然失败']);
				echo '';
				exit;
			}
			//修改此公众号及发送消息的状态，重新进入发送流程
			$this->send_msg_info->isUpdate(true)->save(['status' => 0, 'return_msg' => '原创文章过多，尝试重新发送','json_log'=>'original']);
			$this->wechat_info->isUpdate(true)->save(['last_send_time' => 0]);
			//app.php中设置的发送消息域名
			$url = config('send_msg_server_url') . "send/sendAuth/send_id/" . $this->send_msg_info['id'];
			ext_curl($url);
			exit;
		}
		//若原创数量较少，则取出原创文章id，素材，进行删除，得到无原创的文章数组
		$del_index_array = array();
		if (isset($this->mass_info['CopyrightCheckResult']['ResultList']['item']['ArticleIdx'])) {
			$del_index_array[] = $this->mass_info['CopyrightCheckResult']['ResultList']['item']['ArticleIdx'] - 1;//-1是因为校验结果从1开始，获取图文消息索引从0开始
		} else {
			foreach ($this->mass_info['CopyrightCheckResult']['ResultList']['item'] as $value) {
				$del_index_array[] = $value['ArticleIdx'] - 1;//-1是因为校验结果从1开始，获取图文消息索引从0开始
			}
		}
		$authorize = new Authorize();
		$access_token = $authorize->getAuthorizerToken($this->wechat_info['appid']);
		$content_array = $this->getMaterial($access_token);
		//检查是否正确获取到对应文章
		if (!is_array($content_array)) {
			$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '含有原创，且删除文章失败，请手动删除']);
			echo'';
			die();
		}
		//删除原创文章
		foreach ($del_index_array as $value) {
			if (isset($content_array[$value])) {
				unset($content_array[$value]);
			}
		}
		//检查剩余文章是否小于4，小于4则进入重发
		if(count($content_array)<4){
			//判断是否已经重发过一次，重发过则不在群发，防止进入死循环
			if($this->send_msg_info['json_log']=='original'){
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '已尝试过重新发送，依然失败']);
				echo '';
				exit;
			}
			//剩余文章不够，修改此公众号及发送消息的状态，重新进入发送流程
			$this->send_msg_info->isUpdate(true)->save(['status' => 0, 'return_msg' => '原创文章过多，尝试重新发送','json_log'=>'original']);
			$this->wechat_info->isUpdate(true)->save(['last_send_time' => 0]);
			//app.php中设置的发送消息域名
			$url = config('send_msg_server_url') . "send/sendAuth/send_id/" . $this->send_msg_info['id'];
			ext_curl($url);
			exit;
		}
		$this->uploadNews($content_array,$access_token);


	}

	/**
	 * 获取素材
	 * @param $access_token
	 * @return array|bool
	 */
	private function getMaterial($access_token) {
		$url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $access_token;
		$post = '{"media_id":"' . $this->send_msg_info['media_id'] . '"}';
		$res = $this->ext_curl($url, $post, '', '', 1);
		if (isset($res['news_item'])) {
			return $res['news_item'];
		} else {
			return false;
		}
	}

	/**
	 * 上传文章并发送
	 * @param array $content_array
	 * @param string $access_token
	 */
	private function uploadNews(array $content_array,$access_token){
		$num =0;
		foreach ($content_array as $value) {
			$data['articles'][$num]['title'] = $value['title'];
			$data['articles'][$num]['thumb_media_id'] = $value['thumb_media_id'];
			$data['articles'][$num]['show_cover_pic'] = 0;//是否正文内显示封面图片 0否 1是
			$data['articles'][$num]['content'] = $value['content'];
			if(isset($value['content_source_url'])&&!empty($value['content_source_url'])){
				$data['articles'][$num]['content_source_url'] = $value['content_source_url'];
			}
			$num++;
		}
		if(!isset($data)){
			$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '未知错误，重发上传部分丢失文章']);
			die();
		}
		//上传素材
		$url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=" . $access_token;
		$res = ext_curl($url, json_encode($data, 320));
		$tmp_res = json_decode($res, 1);
		if (isset($tmp_res['media_id'])) {
			$this->send_msg_info->isUpdate(true)->save(['media_id' => $tmp_res['media_id']]);
			$url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' . $access_token;
			$msg = '{"filter":{"is_to_all":true,"tag_id":1},"mpnews":{"media_id":"' . $tmp_res['media_id'] . '"},"msgtype":"mpnews","send_ignore_reprint":0}';
			$res = ext_curl($url, $msg);
			$tmp_res = json_decode($res, 1);
			if (isset($tmp_res['errcode'])) {
				switch ($tmp_res['errcode']) {
					case 0:
						$this->send_msg_info->isUpdate(true)->save(['status' => 2, 'msg_id' => $tmp_res['msg_id'], 'msg_data_id' => $tmp_res['msg_data_id'], 'return_msg' => '发送成功待检查']);
						$this->wechat_info->isUpdate(true)->save(['last_send_time' => time()]);
						die();
						break;
					case 45028:
						$this->send_msg_info->isUpdate(true)->save(['status' => 200, 'return_msg' => '没有发送次数了']);
						$this->wechat_info->isUpdate(true)->save(['last_send_time' => time()]);
						die();
						break;
					default:
						$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
						die();
						break;
				}
			} else {
				$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
				die();
			}
		} else {
			$this->send_msg_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
			die();
		}
	}

}