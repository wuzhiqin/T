<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/12/012
 * Time: 下午 3:08
 */

namespace app\admin\controller;

use app\common\Tools;
use think\Db;
use app\common\service\Mp as MpService;

/**
 * 未认证公众号
 **/
class Mp
{

    public $qrcode = "https://mp.weixin.qq.com/cgi-bin/loginqrcode?action=getqrcode&param=4300";
    public $vcode = "https://mp.weixin.qq.com/cgi-bin/verifycode?username=username&r=";
    public $checkLogin = "https://mp.weixin.qq.com/cgi-bin/loginqrcode?action=ask&token=&lang=zh_CN&f=json&ajax=1";
    public $login_start_url = "https://mp.weixin.qq.com/cgi-bin/bizlogin?action=startlogin";
    public $login_success = "https://mp.weixin.qq.com/cgi-bin/bizlogin?action=login";

    /**
     * 登陆第一步
     * @throws
     **/
    public function login($id = 0, $vcode = '')
    {
        MpService::checkDir();  //检查并创建cookie和二维码所需要的文件夹
        $account = Db::name("wechat_account")->where('id', $id)->find();
        if (!$account) return response_json(null, 500, '账号id不存在！');
        $username = $account['gh_id'];
        $password = $account['login_password'];

        //尝试登陆：如果有验证码，说明已经请求过本接口保存了cookie，所以要使用保存的cookie
        if ($vcode == '') {
            $cookie = false;
        } else {
            $cookie = MpService::makeCookieJarDir($username);
        }
        $tempData = http_build_query(["username" => $username, "pwd" => md5($password), "imgcode" => $vcode, "f" => "json", "userlang" => "zh_CN", "token" => "", "lang" => "zh_CN", "ajax" => 1]);
        $result1 = Tools::curl($this->login_start_url, $tempData, "https://mp.weixin.qq.com", $cookie, MpService::makeCookieJarDir($username), 2, 1);
        $result1 = json_decode($result1, true);
        $ret = $result1['base_resp']['ret'];
        if ($ret != 0) {
            if ($ret == 200023) {
                return response_json(null, 500, "您输入的帐号或者密码不正确，请重新输入！");
            } else if ($ret == 200027 || $ret == 200008) {
                //验证码处理
                $message = '验证码处理错误！';
                if ($ret == 200027) $message = '验证码错误！';
                if ($ret == 200008) $message = '需要验证码！';
                $vcode = Tools::curl($this->vcode, '', '', MpService::makeCookieJarDir($username), MpService::makeCookieJarDir($username), 2, 0);
                file_put_contents("wechatMpHack/vcode/{$username}.png", $vcode);
                $image_file = $_SERVER['DOCUMENT_ROOT'] . "/wechatMpHack/vcode/{$username}.png";
                $base64_img = Tools::imgToBase64($image_file);
                return response_json(['vcode' => $base64_img, 'result' => $result1], 300, $message);
            } else {
                return response_json($result1, 200, "未知错误！");
            }
        }

        //图片二维码并且保存到本地
        $img = Tools::curl($this->qrcode, '', '', MpService::makeCookieJarDir($username), '', 2, 0);
        file_put_contents("wechatMpHack/qrcode/{$username}.png", $img);
        $image_file = $_SERVER['DOCUMENT_ROOT'] . "/wechatMpHack/qrcode/{$username}.png";
        $base64_img = Tools::imgToBase64($image_file);
        return response_json(['qrcode' => $base64_img], 200, "请扫码！");
    }

    /**
     * 登陆第二步：检查登陆状态，登陆成功存储cookie&&token
     * @throws
     **/
    public function checkLogin($id = 0)
    {
        $account = Db::name("wechat_account")->where('id', $id)->find();
        if (!$account) return response_json(null, 500, "账号id不存在！");
        $username = $account['gh_id'];
        $result = Tools::curl($this->checkLogin, '', '', MpService::makeCookieJarDir($username), MpService::makeCookieJarDir($username), 2, 0);
        $result = json_decode($result, true);
        if (!isset($result['status'])) return response_json(null, 500, "未查询到待登录状态！");
        if ($result['status'] == 0) {
            return response_json(null, 500, "还没有扫码！");
        } else if ($result['status'] == 4) {
            return response_json(null, 500, "已扫码，未确认登陆！");
        } else if ($result['status'] == 1) {
            //扫码已经确认登陆
            $tempData = http_build_query(['f' => 'json', 'ajax' => 1, 'random' => 0.48824483303562394]);
            $result = Tools::curl($this->login_success, $tempData, "https://mp.weixin.qq.com/cgi-bin/bizlogin?action=validate&lang=zh_CN&account={$username}", MpService::makeCookieJarDir($username), MpService::makeCookieJarDir($username), 2, 1);
            $result = json_decode($result, true);
            if ($result['base_resp']['ret'] == 0) {
                $redirect_url = $result['redirect_url'];
                $preg = "/token=(.*)/";
                preg_match($preg, $redirect_url, $token);
                $token = $token[1];  //匹配到token
                $cookie = file_get_contents("wechatMpHack/cookie/{$username}.cookie");  //获取登录成功的cookie
                $date = date("Y-m-d", time() - 24 * 60 * 60);  //昨天日期
                MpService::updateFlowMain($account['id'], $token, MpService::makeCookieJarDir($username), $date, $date);  //更新流量主
                unlink("wechatMpHack/cookie/{$username}.cookie");  //删除cookie文件，cookie存数据库
                Db::name("wechat_account")->where('id', $id)->update(['login_token' => $token, 'last_login_time' => time(), 'login_cookie' => $cookie]);  //更新到token
                return response_json(null, 200, "登陆成功！");
            }
        }
    }


}