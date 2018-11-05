<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/23/023
 * Time: 下午 3:21
 */

namespace app\send\controller;

use think\Controller;
use think\Db;
use app\common\service\Mp as MpService;
use app\common\Tools;
use app\common\service\Material as MaterialService;
use think\facade\Request;

/**
 * 未认证公众号群发
 * Class SendNoAuth
 * @package app\send\controller
 */
class SendNoAuth extends Controller
{

    protected $middleware = ['SendCount'];

    /**
     * 立即群发
     * @throws
     **/
    public function send()
    {
        //请求立即返回，不等待结果
        if (PHP_OS == 'LINUX') {
            fastcgi_finish_request();
        } else {
            ignore_user_abort();
            header('HTTP/1.1 200 OK');
            header('Content-Length:0');
            header('Connection:Close');
            flush();
        }

        //验证群发id
        $id = Request::param('id', 0);
        $send = Db::name("send")->where('id', $id)->find();
        if (!$send) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "群发id不存在！", "status" => 500]);
            dec_count_die();
        }
        $num = $send['send_num'];
        if ($num < 1) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "群发素材数量为0，无法群发！", "status" => 500]);
            dec_count_die();
        }
        $account_id = $send['wechat_id'];
        $account = Db::name('wechat_account')->where('id', $account_id)->find();
        if (!$account) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "账号id不存在！", "status" => 500]);
            dec_count_die();
        }

        //处理cookie和token
        $token = $account['login_token'];
        $cookie = $account['login_cookie'];
        if (strlen($cookie) < 2) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "此账号登录状态无效！", "status" => 500]);
            dec_count_die();
        }
        file_put_contents("wechatMpHack/cookie/{$account['gh_id']}.cookie", $cookie);  //将cookie保存为cookieJar文件
        $cookie = MpService::makeCookieJarDir($account['gh_id']);
        if (date("Ymd", time()) == date("Ymd", $account['last_send_time'])) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "此账号今日已经群发过了！", "status" => 500]);
            dec_count_die();
        }

        //用来检测账号登陆状态是否可用，不可用就不上传，节省上传限制
        $url = "https://mp.weixin.qq.com/misc/safeassistant?1=1&token={$token}";
        $temp = Tools::curl($url, http_build_query(['token' => $token, 'f' => 'json', 'ajax' => 1, 'random' => 0.123213213123213, 'action' => 'get_ticket']), "https://mp.weixin.qq.com/cgi-bin/masssendpage?t=mass/send&token={$token}&lang=zh_CN", $cookie, false, 2, 1);
        $temp = json_decode($temp, true);
        if ($temp['base_resp']['ret'] != 0) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "此账号登陆状态失效，请重新登陆！", "status" => 500]);
            dec_count_die();
        }

        $data = MaterialService::select_material($account['material_type'], $num);  //筛选出图文素材(没内容)
        if (count($data) == 0) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "素材库图文数量不够，当前素材库有0条,请核实后在确认生成数量！", "status" => 500]);
            dec_count_die();
        }
        foreach ($data as $k => $v) {  //处理素材
            $content = MaterialService::get_article_bjh($v['news_id'], $v['referer_id'], $v['id']);  //获取素材原始内容
            $content_pic_url_old = json_decode($v['content_pic_url'], true);  //获取文章内部图片
            $head_middle_foot = MpService::head_middle_foot($account_id);  //头部中部底部素材转换
            $content_source_url = $head_middle_foot['content_source_url'];   //原文链接
            unset($head_middle_foot['content_source_url']);

            $img_arr = ['cover' => [$v['cover_pic_url']], 'content' => $content_pic_url_old, 'head_middle_foot' => $head_middle_foot];  //所有要上传的图片
            $img_url_new = MpService::uploadImg($img_arr, $token, $cookie);  //上传图片获取到微信端新地址
            $cover_pic_url_new = $img_url_new['cover'][0];  //封面图
            $content_pic_url_new = $img_url_new['content'];  //内容图数组

            $head_middle_foot_new = MpService::head_middle_foot_convert($account_id, $img_url_new['head_middle_foot']);  //头部中部底部标签转换
            $content = MaterialService::replace_img_url($content, $content_pic_url_new, $head_middle_foot_new['head_value'], $head_middle_foot_new['middle_value'], $head_middle_foot_new['foot_value'], $v['referer_id']); //转换文章图片内容以及顶部中部底部素材

            $temp = ['title' => $v['title'], 'content' => $content, 'content_source_url' => $content_source_url, 'thumb_url' => $cover_pic_url_new];
            $data[$k] = $temp;
        }

        $build = MpService::build($data, $token, $cookie);  //新建图文
        if ($build['base_resp']['ret'] != 0 || !isset($build['base_resp'])) {
            Db::name("send")->where('id', $id)->update(['return_msg' => "生成图文失败，未进行群发！", "status" => 500, 'json_log' => json_encode($build, 320)]);
            dec_count_die();
        }

        $send = MpService::send($build['appMsgId'], $data, $token, $cookie);  //群发图文

        //存储群发记录
        Db::name("send")->where('id', $id)->update(['status' => $send['code'], 'return_msg' => $send['message'], 'json_log' => json_encode($send['data'], 320), 'verify_type' => -1]);
        return response_json();
    }

}