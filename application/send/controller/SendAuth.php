<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/23
 * Time: 15:17
 */

namespace app\send\controller;


use app\common\model as model;
use app\thirdparty\controller\Authorize;
use app\thirdparty\controller\Tools;
use think\Controller;

class SendAuth extends Controller {
	protected $middleware = ['SendCount'];

	public function index($repeat = 1) {

		//请求立即返回，不等待结果
		fastcgi_finish_request();

		/*
		 * send_info 发送信息
		 * wechat_info 公众号信息
		 * news_list 素材数组
		 * wechat_attach_info 附加信息
		 */
		/*
		 * 接收id查询记录
		 * 信息错误过滤
		 * 获取token
		 * 取素材
		 * 下载素材
		 *
		 *
		 */
		$send_id = request()->param('send_id');
		$id = intval($send_id);
		$id?null:dec_count_die('id错误');

		$send_info = model\Send::get($id);
		if (!$send_info) dec_count_die('无效消息id');
		if ($send_info['status'] != 0) dec_count_die('非待发送');//发送状态非未发送则停止
		//更改为正在发送状态
		$send_info->isUpdate(true)->save(['status' => 1, 'return_msg' => '发送时间' . date('Y-m-d H:i:s', time())]);
		if ($send_info['send_num'] == 0) {
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '无效的发送数量']);
			dec_count_die('无效发送数量');
		}
		$wechat_info = (new model\WechatAccount())->get($send_info['wechat_id']);
		/*["id"] => int(2)
  ["gh_id"] => string(15) "gh_8862958e1db1"
  ["appid"] => string(18) "wx03148b9b4d17e60f"
  ["material_type"] => int(2)
  ["last_send_time"] => int(0)*/
		if (!isset($wechat_info) || empty($wechat_info['appid']) || $wechat_info['material_type'] == 0) {
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '公众号信息不正确']);
			dec_count_die('公众号信息不正确');
		}
		if (date('Ymd', $wechat_info['last_send_time']) == date('Ymd', time())) {
			$send_info->isUpdate(true)->save(['status' => 200, 'return_msg' => '今天已经发送过了']);
			dec_count_die('已经发送过');
		}
		$wechat_attach_info = (new model\WechatAttach())->get($send_info['wechat_id']);
		if (!$wechat_attach_info) {
			//若查不出附加信息则生成默认信息，以免影响程序运行
			error_record(1, $send_info['wechat_id'] . '此公众号无附加消息字段，需要增加');
			$wechat_attach_info = array(
				"id" => $send_info['wechat_id'],
				"use_head" => 0,
				"use_foot" => 0,
				"use_href" => 0,
				"use_middle" => 0,
				"head_type" => 0,
				"foot_type" => 0,
				"middle_type" => 0,
				"head_value" => null,
				"foot_value" => null,
				"middle_value" => null,
				"href_value" => null,
			);

		}
		$access_token = (new Authorize())->getAuthorizerToken($wechat_info['appid']);
		if(!$access_token){
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '无效token']);
			dec_count_die('无效token');
		}
		//3.条件判定
		$where[] = array('material_type', '=', $wechat_info['material_type']);
		if ($repeat == 1) {
			$where[] = ['use_type', '=', 0];
		}
		//4.随机获取指定条件和数量的文章
		$news_list = $this->randList($where, $send_info['send_num']);
		if (!$news_list) {
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => '素材数量不足,素材id:' . $wechat_info['material_type']]);
			dec_count_die('素材数量不足');
		}
		/*
		 * 检查素材是否符合，符合则检出图片，不符则弃用
		 * 头部中部尾部附加模板信息检查，检出图片
		 *
		 */
		$news_pic = array();
		$news_origin_list = array();

		//处理新闻图片
		foreach ($news_list as $news_key => $news) {
			$this->getContent($news['referer_id'], $news_list[$news_key], $content);
			if ($content) {
				//文章内容
				$news_origin_list[$news_key]['content'] = $content;
				$news_origin_list[$news_key]['title'] = $news['title'];
				//文章封面图
				$news_pic[$news_key . '_' . 'cover'] = $news['cover_pic_url'];
				//文章内图片
				$tmp_pib_arr = json_decode($news['content_pic_url'], 1);
				$news_origin_list[$news_key]['pic_count'] = count($tmp_pib_arr);
				foreach ($tmp_pib_arr as $k => $v) {
					$news_pic[$news_key . '_newsimg_' . $k] = $v;
				}
			} else {
				continue;
			}
		}
		//处理附加模板
		$head_value = '';
		$foot_value = '';
		$middle_value = '';

		//头部信息判定
		if ($wechat_attach_info['use_head'] && !empty($wechat_attach_info['head_value'])) {
			switch ($wechat_attach_info['head_type']) {
				//头部类型（0不存在1图片）
				case 1:
					$news_pic['head_attach'] = $wechat_attach_info['head_value'];
					break;
				default:
					break;
			}

		}

		//尾部信息判定
		if ($wechat_attach_info['use_foot'] && !empty($wechat_attach_info['foot_value'])) {
			switch ($wechat_attach_info['foot_type']) {
				//尾部类型（0不存在1图片2超链接）
				case 1:
					$news_pic['foot_attach'] = $wechat_attach_info['foot_value'];
					break;
				case 2:
					$foot_value = $wechat_attach_info['foot_value'];
					break;
				default:
					break;
			}

		}
		if ($wechat_attach_info['use_foot'] && !empty($wechat_attach_info['foot_value'])) {
			switch ($wechat_attach_info['foot_type']) {
				//1图片 2超链接 3小程序卡片 4小程序图片 5小程序超链接
				case 1:
					$news_pic['foot_attach'] = $wechat_attach_info['foot_value'];
					break;
				case 2:
					$foot_value = $wechat_attach_info['foot_value'];
					break;
				case 3:
				case 4:
					$wechat_attach_info['foot_value'] = json_decode($wechat_attach_info['foot_value'], 1);
					if (is_null($wechat_attach_info['foot_value'])) break;
					$news_pic['foot_attach'] = $wechat_attach_info['foot_value']['imageurl'];
					break;
				case 5:
					$wechat_attach_info['foot_value'] = json_decode($wechat_attach_info['foot_value'], 1);
					if (is_null($wechat_attach_info['foot_value'])) break;
					$foot_value = '<p style="margin: 55px 0 30px 0;"><span style="display: block;width: 100%;border: 1px solid #ccc;padding: 20px;box-sizing:border-box;"><span style="display: block;margin: -45px auto 30px;width: 50%;height: 50px;font-size: 18px;text-align: center;line-height: 50px;"><span style="display: block;width: 100%;height: 100%;background-color:#ccc;transform:translate(5px, 5px);"></span><span style="display: block;margin: 0;width: 100%;height: 100%;box-sizing:border-box;border:1px solid #999;transform:translate(0px,-50px);background-color:#fff;"></span><span style="display: block;color: #8b3a39;transform: translate(0, -100px)">精 彩 推 荐</span></span><a data-miniprogram-appid="' . $wechat_attach_info['foot_value']['appid'] . '" data-miniprogram-path="' . $wechat_attach_info['foot_value']['path'] . '" href="">' . $wechat_attach_info['foot_value']['title'] . '</a></span></p>';
					break;
				default:
					break;
			}

		}

		//中部信息判定
		if ($wechat_attach_info['use_middle'] && !empty($wechat_attach_info['middle_value'])) {
			switch ($wechat_attach_info['middle_type']) {
				//1图片 2超链接 3小程序卡片 4小程序图片 5小程序超链接
				case 1:
					$news_pic['middle_attach'] = $wechat_attach_info['middle_value'];
					break;
				case 2:
					$middle_value = $wechat_attach_info['middle_value'];
					break;
				case 3:
				case 4:
					$wechat_attach_info['middle_value'] = json_decode($wechat_attach_info['middle_value'], 1);
					if (is_null($wechat_attach_info['middle_value'])) break;
					$news_pic['middle_attach'] = $wechat_attach_info['middle_value']['imageurl'];
					break;
				case 5:
					$wechat_attach_info['middle_value'] = json_decode($wechat_attach_info['middle_value'], 1);
					if (is_null($wechat_attach_info['middle_value'])) break;
					$middle_value = '<p style="margin: 55px 0 30px 0;"><span style="display: block;width: 100%;border: 1px solid #ccc;padding: 20px;box-sizing:border-box;"><span style="display: block;margin: -45px auto 30px;width: 50%;height: 50px;font-size: 18px;text-align: center;line-height: 50px;"><span style="display: block;width: 100%;height: 100%;background-color:#ccc;transform:translate(5px, 5px);"></span><span style="display: block;margin: 0;width: 100%;height: 100%;box-sizing:border-box;border:1px solid #999;transform:translate(0px,-50px);background-color:#fff;"></span><span style="display: block;color: #8b3a39;transform: translate(0, -100px)">精 彩 推 荐</span></span><a data-miniprogram-appid="' . $wechat_attach_info['middle_value']['appid'] . '" data-miniprogram-path="' . $wechat_attach_info['middle_value']['path'] . '" href="">' . $wechat_attach_info['middle_value']['title'] . '</a></span></p>';
					break;
				default:
					break;
			}

		}

		/*
		 * 下载图片
		 * 上传图片
		 * 处理图片至正确位置
		 * 上传素材√
		 * 分配素材---
		 * 确定文章
		 * 发送
		 * 处理结果
		 *
		 * 事件接收发送结果，根据结果修改原创内容并
		 */
		$wx_pic_list = $this->handleImage($news_pic, $access_token, 'http://mbd.baidu.com');
		if (is_string($wx_pic_list)) {
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $wx_pic_list]);
			dec_count_die($wx_pic_list);
		}
		//处理头尾中部图片类型附加消息
		if ($wechat_attach_info['use_head'] && $wechat_attach_info['head_type'] == 1 && isset($wx_pic_list['head_attach.jpg'])) {
			$head_value = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $wx_pic_list['head_attach.jpg'] . '"/></section></section></section></section>';
		}
		if (isset($wx_pic_list['middle_attach.jpg'])) {
			switch ($wechat_attach_info['middle_type']) {
				//1图片 2超链接 3小程序卡片 4小程序图片 5小程序超链接
				case 1:
					$middle_value = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $wx_pic_list['middle_attach.jpg'] . '"/></section></section></section></section>';
					break;
				case 3:
					$middle_value = '<mp-miniprogram data-miniprogram-appid="' . $wechat_attach_info['middle_value']['appid'] . '" data-miniprogram-path="' . $wechat_attach_info['middle_value']['path'] . '" data-miniprogram-title="' . $wechat_attach_info['middle_value']['title'] . '" data-miniprogram-imageurl="' . $wx_pic_list['middle_attach.jpg'] . '"></mp-miniprogram>';
					break;
				case 4:
					$middle_value = '<p style="font-size:19px;line-height:30px;margin-top:11px;overflow:visible;letter-spacing:.2px;padding-left: 17px;padding-right:17px;"><a data-miniprogram-appid="' . $wechat_attach_info['middle_value']['appid'] . '" data-miniprogram-path="' . $wechat_attach_info['middle_value']['path'] . '" href=""><img src="' . $wx_pic_list['middle_attach.jpg'] . '" alt="" data-width="null" data-ratio="NaN"></a></p>';
					break;
				default:
					break;
			}
		}
		if ($wechat_attach_info['use_foot'] && $wechat_attach_info['foot_type'] == 1 && isset($wx_pic_list['foot_attach.jpg'])) {
			$foot_value = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $wx_pic_list['foot_attach.jpg'] . '"/></section></section></section></section>';
		}
		$use_source_url = ($wechat_attach_info['use_href'] && !empty($wechat_attach_info['href_value'])) ? true : false;
		//处理文内图片生成素材
		//6.组合data数据
		$data = array();
		$num = 0;

		foreach ($news_origin_list as $key => $value) {
			//无封面图的文章直接过滤
			if (!isset($wx_pic_list[$key . '_' . 'cover.jpg'])) continue;
			$tmp_news_pic_list = array();
			for ($i = 0; $i < $value['pic_count']; $i++) {
				$tmp_news_pic_list[] = isset($wx_pic_list[$key . '_newsimg_' . $i . '.jpg'])?$wx_pic_list[$key . '_newsimg_' . $i . '.jpg']:'';
			}
			$data['articles'][$num]['title'] = $value['title'];
			$data['articles'][$num]['thumb_media_id'] = $wx_pic_list[$key . '_' . 'cover.jpg'];
			$data['articles'][$num]['show_cover_pic'] = 0;//是否正文内显示封面图片 0否 1是
			$data['articles'][$num]['content'] = $this->replace_img_url($value['content'], $tmp_news_pic_list, $head_value, $foot_value, $middle_value,$news_list[$key]['referer_id']);
			$use_source_url ? $data['articles'][$num]['content_source_url'] = $wechat_attach_info['href_value'] : null;
			$num++;
		}
		//上传素材
		$url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=" . $access_token;
		$res = ext_curl($url, json_encode($data, 320));
		$tmp_res = json_decode($res, 1);
		if (isset($tmp_res['media_id'])) {
			$send_info->isUpdate(true)->save(['media_id' => $tmp_res['media_id']]);
			$url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' . $access_token;
			$msg = '{"filter":{"is_to_all":true,"tag_id":1},"mpnews":{"media_id":"' . $tmp_res['media_id'] . '"},"msgtype":"mpnews","send_ignore_reprint":0}';
			$res = ext_curl($url, $msg);
			$tmp_res = json_decode($res, 1);
			if (isset($tmp_res['errcode'])) {
				switch ($tmp_res['errcode']) {
					case 0:
						$send_info->isUpdate(true)->save(['status' => 2, 'msg_id' => $tmp_res['msg_id'], 'msg_data_id' => $tmp_res['msg_data_id'], 'return_msg' => '发送成功待检查']);
						$wechat_info->isUpdate(true)->save(['last_send_time' => time()]);
						dec_count_die('发送完成');
						break;
					case 45028:
						$send_info->isUpdate(true)->save(['status' => 200, 'return_msg' => '没有发送次数了']);
						$wechat_info->isUpdate(true)->save(['last_send_time' => time()]);
						dec_count_die('发送完成,没有次数');
						break;
					default:
						$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
						dec_count_die('发送失败，错误:' . $res);
						break;
				}
			} else {
				$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
				dec_count_die('发送信息错误');
			}
		} else {
			$send_info->isUpdate(true)->save(['status' => 500, 'return_msg' => $res]);
			dec_count_die('上传素材错误');
		}


	}

	public function getContent($referer_id, $news, &$content) {
		switch ($referer_id) {
			case 1://百家号
				//百家号文章采集
				$this->getContent_bjh($news['content_source_url'], $content);
				break;
			case 2://uc
			case 3://趣头条

				$content = (new model\MaterialContent())->where("content_id", $news['id'])->value('content');
				break;
			default:
				$content = false;
				break;
		}
	}

	/**
	 * 百家号文章内容采集
	 * @param $content_url
	 * @param $content
	 */
	public function getContent_bjh($content_url, &$content) {
		$class_list = array(
			'class="mainContent"',
			'class="contentText contentSize contentPadding"',
			'class="bjh-p"',
			'class="contentMedia contentPadding"',
			'class="contentImg linexRBS"',
			'class="bjh-blockquote"',
			'class="bjh-strong"',
			'class="bjh-h3">',
		);
		$style_list = array(
			'style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"',
			'style="font-size:18px;line-height:34px;margin-top:11px;overflow:visible;letter-spacing:.2px;"',
			'style="display:block;"',
			'style="margin-top:14px;"',
			'style="position:relative;max-width:100%;background-color:#eaeaea;overflow:hidden;margin:0 auto;box-shadow:0 0 0 0.01rem rgba(0,0,0,.06);"',
			'style="display: block;line-height: 30px;margin: 20px 0;padding-left:30px;color: #999;position: relative;border: 0;"',
			'style="font-weight: 700;color: #333;"',
			'style="padding-left:11px;font-weight: 700;"><span style="width: 4px;height:17px;display:inline-block;margin-left:-11px;margin-right:7px;background: #3c76ff;"></span>',
		);
		$content = file_get_contents($content_url);//获取url内容
		$content = str_replace(array('百家号', "\r\n", "\r", "\n", "\t", "</body></html>"), array('微信号', ''), $content);//替换文字，删除换行
		$content = preg_replace('/(<script[^>]*>(.*?)<\/script>)|(<style[^>]*>(.*?)<\/style>)|(style="padding-top:[^"]*")/si', "", $content);//删除js,style,图片padding
		preg_match('/<div[^>]*class="mainContent[^>]*>(.*)/si', $content, $match);//未正确匹配对称div标签，下方为对称匹配正则方式，后续升级
		//<div[^>]*>[^<>]*(((?'Open'<div[^>]*>).**)+((?'-Open'</div>)[^<>]*)+)*(?(Open)(?!))</div>
//		if(!isset($match[0]))return false;
		$content = str_replace(array('<div', '</div>'), array('<section', '</section>'), $match[0]);//替换文字，删除换行section
		$content = str_replace($class_list, $style_list, $content);//替换class样式
	}


	/*************************************工具类方法**************/
	/**
	 * 下载网络图片
	 * @param array $file_array array('要生成的文件名称'=>'网络地址')
	 * @param string $token 微信号token
	 * @param string $referer_url 来源地址模拟
	 * @param string $cookie cookie模拟
	 * @return array|bool
	 */
	public function handleImage(array $file_array, $token, $referer_url = '', $cookie = '') {
		//下载图片
		$path = 'uploads/' . md5(microtime() . mt_rand(1000, 9999));
		foreach ($file_array as $key => $value) {
			$list[$key . '.jpg']['url'] = $value;
		}
		if (!isset($list)) return false;
		curl_multi($list, $res, $referer_url, $cookie);
		if (empty($res)) return '下载图片失败';
		is_dir($path) ?: mkdir($path, 0777, true);
		foreach ($res as $k => $v) {
			if (stripos($k, 'cover')) {
				$file_list[$k]['url'] = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . $token . '&type=image';

			} else {
				$file_list[$k]['url'] = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' . $token;
			}
			file_put_contents($path . '/' . $k, $v);
			$file_list[$k]['post']['media'] = NEW \CURLFile($path . '/' . $k);
		}
		//上传图片
		curl_multi($file_list, $return_list);
		if (empty($return_list)) return '上传图片失败';
		foreach ($return_list as $key => $value) {
			//{"url":"http:\/\/mmbiz.qpic.cn\/mmbiz_jpg\/RGFvZKMxUhwDZLIYRekhxtRdmA7AgJLL9nxmPpt1bU0AuU6DCVbGkrD89CibzsyrdWLsbEyLD9dLJozQzk5OuyQ\/0"}
			$tmp_res = json_decode($value, 1);
			if (stripos($key, 'cover')) {
				$wx_pic[$key] = $tmp_res['media_id'] ? $tmp_res['media_id'] : null;

			} else {
				$wx_pic[$key] = $tmp_res['url'] ? $tmp_res['url'] : null;
			}
		}
		if (empty($wx_pic)) return '上传图片失败';
		$tools = new Tools();
		$tools->delDirAndFile($path);
		return $wx_pic;
	}

	/**
	 * 随机取指定条件和数量的文章素材
	 * @param array $where 查询条件
	 * @param int $num 取记录数量
	 * @return array|bool
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function randList($where, $num) {
		$news = new model\MaterialNews();
		$id_list = $news->where($where)->column('id');
		$count = count($id_list);
		if ($num > $count) {
			return false;
		}
		$id_key_list = array_rand($id_list, $num);
		if ($num == 1) {
			$id_key_list = array($id_key_list);
		}
		foreach ($id_key_list as $id_key) {
			$where_id[] = $id_list[$id_key];
		}
		$list = $news->where($where)->where('id', 'in', $where_id)->select()->toArray();
		//修改使用状态
		$news->where('id','in',$where_id)->update(['use_type'=>1]);
		return $list;
	}

	/**
	 * 将文章中的图片地址替换为微信地址
	 * @param string $content
	 * @param array $url
	 * @param string $head_value
	 * @param string $foot_value
	 * @param string $middle_value
	 * @param int $referer_id
	 * @return mixed|string
	 */
	public function replace_img_url($content, $url, $head_value, $foot_value, $middle_value,$referer_id =1) {
		switch ($referer_id) {
			case 1://百家号
				preg_match_all('/<img[^>]*src="(.*?)"/si', $content, $match);//未正确匹配对称div标签，下方为对称匹配正则方式，后续升级
				foreach ($match[1] as $key => $value) {
					if (isset($url[$key])) {
						$content = str_replace($value, $url[$key], $content);
					}
				}
				break;
			case 2://uc
			case 3://趣头条
				foreach ($url as $k => $v) {
					$content = str_replace("<!--{img:$k}-->", "<img src='{$v}' style=\"position:relative;max-width:100%;background-color:#eaeaea;overflow:hidden;margin:0 auto;box-shadow:0 0 0 0.01rem rgba(0,0,0,.06);\">", $content);
				}
			$class_list = array(
				'<p>',
			);
			$style_list = array(
				'<p style="max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;font-size:18px;line-height:34px;margin-top:11px;overflow:visible;letter-spacing:.2px;">',
		);
			$content = str_replace($class_list, $style_list, $content);//替换class样式
				break;
		}
		if (!$head_value) $head_value = '';
		if (!$foot_value) $foot_value = '';
		if (!$middle_value) $middle_value = '';
		$middle_str_len = strpos($content, '</p>', ceil(strlen($content) / 2));
		$str_before = substr($content, 0, $middle_str_len + 4);
		$str_after = substr($content, $middle_str_len + 4);
		$content = $head_value . $str_before . $middle_value . $str_after . $foot_value;
		return $content;
	}


}