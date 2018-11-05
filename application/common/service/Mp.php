<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/23/023
 * Time: 上午 9:35
 */

namespace app\common\service;

use app\common\Tools;
use think\Db;


class Mp
{

    /**
     * 检查并创建cookie和二维码、登陆验证码所需要的文件夹
     **/
    public static function checkDir()
    {
        if (!is_dir('wechatMpHack')) mkdir('wechatMpHack', 0777);
        if (!is_dir('wechatMpHack/qrcode')) mkdir('wechatMpHack/qrcode', 0777);
        if (!is_dir('wechatMpHack/vcode')) mkdir('wechatMpHack/vcode', 0777);
        if (!is_dir('wechatMpHack/cookie')) mkdir('wechatMpHack/cookie', 0777);
    }

    /**
     * 生成存储cookieJar路径
     * @param $account
     * @return string
     */
    public static function makeCookieJarDir($account)
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/wechatMpHack/cookie/' . $account . '.cookie';
    }


    /**
     * 更新公众号流量主数据
     * @param $account_id
     * @param $token
     * @param $cookie
     * @param $start_date
     * @param $end_date
     * @throws
     */
    public static function updateFlowMain($account_id, $token, $cookie, $start_date, $end_date)
    {
        $url = "https://mp.weixin.qq.com/promotion/publisher/publisher_stat?action=biz_ads_stat&page=1&page_size=10&start_date=$start_date&end_date=$end_date&slot=1&token=$token&appid=&spid=";
        $refer = "https://mp.weixin.qq.com/cgi-bin/frame?t=ad_system/common_frame&t1=publisher/publisher_report&pos_type=bottom&token={$token}&lang=zh_CN";
        $result = Tools::curl($url, null, $refer, $cookie, null, 2, 0);
        $result = json_decode($result, true);
        if (count($result) > 0) {
            foreach ($result['list'] as $k => $v) {
                $today_log = Db::name("wechat_account_flow")->where('account_id', $account_id)->where("date", $v['date'])->find();
                if ($today_log) {
                    Db::name('wechat_account_flow')->where('id', $today_log['id'])->update(['click_count' => $v['click_count'], 'exposure_count' => $v['exposure_count'], 'income' => $v['income'] / 100, 'click_rate' => $v['click_count'] * 100 / $v['exposure_count'], 'avg_income' => ($v['income'] / 100) / $v['click_count']]);
                } else {
                    Db::name("wechat_account_flow")->insert(['account_id' => $account_id, 'date' => $v['date'], 'click_count' => $v['click_count'], 'exposure_count' => $v['exposure_count'], 'income' => $v['income'] / 100, 'click_rate' => $v['click_count'] * 100 / $v['exposure_count'], 'avg_income' => ($v['income'] / 100) / $v['click_count']]);
                }
            }
        }
    }


    /**
     * 新建图文
     * @param $data_arr
     * @param $token
     * @param $cookie
     * @return mixed
     */
    public static function build($data_arr, $token, $cookie)
    {
        $data = ['token' => $token, 'lang' => 'zh_CN', 'f' => 'json', 'ajax' => 1, 'count' => count($data_arr),];
        foreach ($data_arr as $k => $v) {
            $content = ['title' . $k => $v['title'], 'author' . $k => '', 'writerid' . $k => 0, 'digest' . $k => '', 'auto_gen_digest' . $k => 1, 'content' . $k => $v['content'], 'sourceurl' . $k => $v['content_source_url'], 'need_open_comment' . $k => 0, 'only_fans_can_comment' . $k => 0, 'cdn_url' . $k => $v['thumb_url'], 'cdn_235_1_url' . $k => $v['thumb_url'], 'cdn_1_1_url' . $k => $v['thumb_url'], 'cdn_url_back' . $k => $v['thumb_url'], 'show_cover_pic' . $k => 0, 'sections' . $k => [["section_index" => 0, "text_content" => $v['content'], "section_type" => 9, "extra_content" => "", "ad_available" => false]]];
            $data = array_merge($data, $content);
        }
        $referer_url = "https://mp.weixin.qq.com/cgi-bin/appmsg?t=media/appmsg_edit_v2&action=edit&isNew=1&type=10&token=$token&lang=zh_CN";
        $url = "https://mp.weixin.qq.com/cgi-bin/operate_appmsg?t=ajax-response&sub=create&type=10&token=$token&lang=zh_CN";
        $result = Tools::curl($url, http_build_query($data), $referer_url, $cookie, false, 2, 1);
        $result = json_decode($result, true);
        return $result;
    }


    /**
     * 群发图文
     * @param $appMsgId
     * @param $data
     * @param $token
     * @param $cookie
     * @return array
     */
    public static function send($appMsgId, $data, $token, $cookie)
    {
        for ($i = 0; $i < 10; $i++) {
            //第一次验证
            $url = "https://mp.weixin.qq.com/cgi-bin/masssend?action=check_same_material&token={$token}&lang=zh_CN";
            $refer = "https://mp.weixin.qq.com/cgi-bin/masssendpage?t=mass/send&type=10&appmsgid={$appMsgId}&token={$token}&lang=zh_CN";
            $tempData = array('token' => $token, 'lang' => 'zh_CN', 'f' => 'json', 'ajax' => 1, 'random' => 0.5126601617899094, 'appmsgid' => $appMsgId,);
            $result1 = Tools::curl($url, http_build_query($tempData), $refer, $cookie, false, 2, 1);

            $url = "https://mp.weixin.qq.com/cgi-bin/masssend?action=get_appmsg_copyright_stat&token={$token}&lang=zh_CN";
            $refer = "https://mp.weixin.qq.com/cgi-bin/masssendpage?t=mass/send&type=10&appmsgid={$appMsgId}&token={$token}&lang=zh_CN";
            $tempData = array('token' => $token, 'lang' => 'zh_CN', 'f' => 'json', 'ajax' => 1, 'random' => 0.5126601617899094, 'first_check' => 1, 'type' => 10, 'appmsgid' => $appMsgId,);
            //第二次验证
            $result2 = Tools::curl($url, http_build_query($tempData), $refer, $cookie, false, 2, 1);
            //第三次验证，检测是否原创内容
            $result3 = Tools::curl($url, http_build_query($tempData), $refer, $cookie, false, 2, 1);
            $temp = json_decode($result3, true);

            if ($temp['base_resp']['ret'] == 154008) {
                /*$url = "https://mp.weixin.qq.com/cgi-bin/operate_appmsg?sub=del&t=ajax-response";
                $refer = "https://mp.weixin.qq.com/cgi-bin/appmsg?begin=0&count=10&t=media/appmsg_list&type=10&action=list_card&lang=zh_CN&token=187191910";
                $tempData = ["AppMsgId" => $appMsgId, "token" => $token, "lang" => "zh_CN", "f" => "json", "ajax" => 1];
                $delete = Tools::curl($url, http_build_query($tempData), $refer, $cookie, false, 2, 1);*/
                return ['data' => ['check_same_material' => $result1, 'copy_right_1' => $result2, 'copy_right_2' => $result3, /*'delete_msg' => $delete*/], 'message' => "含有原创内容，已取消群发！", 'code' => 500];
            }

            //获取operation_seq：群发需要的参数
            $url = "https://mp.weixin.qq.com/misc/safeassistant?1=1&token={$token}";
            $result4 = Tools::curl($url, http_build_query(['token' => $token, 'f' => 'json', 'ajax' => 1, 'random' => 0.123213213123213, 'action' => 'get_ticket']), "https://mp.weixin.qq.com/cgi-bin/masssendpage?t=mass/send&token={$token}&lang=zh_CN", $cookie, false, 2, 1);
            $result4 = json_decode($result4, true);
            $operation_seq = $result4['operation_seq'];   //$ticket = $result4['ticket'];

            //开始群发
            $tempData = ['token' => $token, 'lang' => 'zh_CN', 'f' => 'json', 'ajax' => 1, 'random' => 0.48824483303562394, 'smart_product' => 0, 'type' => 10, 'appmsgid' => $appMsgId, 'share_page' => 1, 'send_time' => 0, 'cardlimit' => 1, 'sex' => 0, 'groupid' => -1, 'synctxweibo' => 0, 'country' => '', 'province' => '', 'city' => '', 'imgcode' => '', 'operation_seq' => $operation_seq, 'req_id' => random_keys(32), 'req_time' => microtime(), 'direct_send' => 1];
            foreach ($data as $k => $v) {
                $temp = ["multi_item[$k][author]" => '作者', "multi_item[$k][auto_gen_digest]" => 1, "multi_item[$k][can_reward]" => 0, "multi_item[$k][cdn_1_1_url]" => $v['thumb_url'], "multi_item[$k][cdn_235_1_url]" => $v['thumb_url'], "multi_item[$k][cdn_url]" => $v['thumb_url'], "multi_item[$k][cdn_url_back]" => $v['thumb_url'], "multi_item[$k][copyright_type]" => 0, "multi_item[$k][cover]" => $v['thumb_url'], "multi_item[$k][digest]" => '', "multi_item[$k][file_id]" => 0, "multi_item[$k][free_content]" => '', "multi_item[$k][is_mp_video]" => 0, "multi_item[$k][is_new_video]" => 0, "multi_item[$k][need_open_comment]" => 0, "multi_item[$k][only_fans_can_comment]" => false, "multi_item[$k][ori_white_list]" => '', "multi_item[$k][reward_money]" => 0, "multi_item[$k][reward_wording]" => '', "multi_item[$k][seq]" => $k, "multi_item[$k][share_page_type]" => 0, "multi_item[$k][show_cover_pic]" => 0, "multi_item[$k][smart_product]" => 0, "multi_item[$k][source_url]" => $v['content_source_url'], "multi_item[$k][title]" => $v['title'], "multi_item[$k][video_desc]" => '', "multi_item[$k][completed]" => true];
                $tempData = array_merge($tempData, $temp);
            }
            $referer_url = "https://mp.weixin.qq.com/cgi-bin/masssendpage?t=mass/send&token={$token}&lang=zh_CN";
            $url = "https://mp.weixin.qq.com/cgi-bin/masssend?t=ajax-response&token={$token}&lang=zh_CN";
            $result5 = Tools::curl($url, http_build_query($tempData), $referer_url, $cookie, false, 2, 1);
            $result5 = json_decode($result5, true);
            if ($result5['base_resp']['ret'] == 0) {
                $message = "群发成功";
                $code = 200;
            } else {
                $message = "群发失败";
                $code = 500;
                if ($i < 9) {
                    continue;
                }
            }
            $data = ['check_same_material' => $result1, 'copy_right_1' => $result2, 'copy_right_2' => $result3, 'operation_seq' => $result4, 'send' => $result5];
            return ['data' => $data, 'message' => "生成图文成功，{$message}！", 'code' => $code];
        }
    }


    /**
     * 上传素材所用图片接口
     * @param $img_array
     * @param $token
     * @param $cookie
     * @return array
     */
    public
    static function uploadImg($img_array, $token, $cookie)
    {
        $upload_url = "https://mp.weixin.qq.com/cgi-bin/uploadimg2cdn?lang=zh_CN&token={$token}&";
        $url_list = array();
        $queue = curl_multi_init();
        foreach ($img_array as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $url_list[$k][$k2] = '';
                if ($v2 != '') {  //判断地址长度
                    $refer = "https://mp.weixin.qq.com/cgi-bin/appmsg?t=media/appmsg_edit_v2&action=edit&isNew=1&type=10&token={$token}&lang=zh_CN";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $upload_url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['imgurl' => $v2, 't' => 'ajax-editor-upload-img']));
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36"); //模拟浏览器
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
                    curl_setopt($ch, CURLOPT_REFERER, $refer);
                    curl_setopt($ch, CURLOPT_NOSIGNAL, true);
                    curl_multi_add_handle($queue, $ch);
                    $img_list[$k][$k2] = $ch;
                }
            }
        }
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($code != CURLM_OK) {
                break;
            }
            while ($done = curl_multi_info_read($queue)) {
                $results = curl_multi_getcontent($done['handle']);
                if ($results) {
                    $key = Tools::ext_in_array($done['handle'], $img_list);
                    $response = json_decode($results, 1);
                    if ($response['errcode'] != 0) {
                        $url_list[$key[0]][$key[1]] = '';
                    } else {
                        if ($key[1] == 'cover_img') {
                            $url_list[$key[0]][$key[1]] = $response['url'];
                        } else {
                            $url_list[$key[0]][$key[1]] = $response['url'];
                        }
                    }
                }
                curl_multi_remove_handle($queue, $done['handle']);
                curl_close($done['handle']);
            }
            if ($active > 0) {
                curl_multi_select($queue, 1);
            }
        } while ($active);
        curl_multi_close($queue);
        return $url_list;
    }


    /**
     * 顶部中部底部素材和原文链接
     * @param $id
     * @return array
     * @throws
     */
    public static function head_middle_foot($id)
    {
        $condition = Db::name('wechat_account')->join('wechat_attach ', 'wechat_attach.id = wechat_account.id')->where('wechat_account.id', '=', $id)->find();
        $head_value = '';
        $foot_value = '';
        $middle_value = '';
        $href_value = '';
        //头部信息判定
        if ($condition['use_head'] && !empty($condition['head_value'])) {
            switch ($condition['head_type']) {
                case 1:
                    //TODO：修改为真实地址
                    $head_value = 'http://api12.wxmanage.ittun.com/upload/a.png';
                    break;
                default:
                    break;
            }
        }
        //尾部信息判定
        if ($condition['use_foot'] && !empty($condition['foot_value'])) {
            switch ($condition['foot_type']) {
                case 1:
                    //TODO：修改为真实地址
                    $foot_value = 'http://api12.wxmanage.ittun.com/upload/a.png';
                    break;
                default:
                    break;
            }
        }
        //中部信息判定
        if ($condition['use_middle'] && !empty($condition['middle_value'])) {
            switch ($condition['middle_type']) {
                case 1:
                    //TODO：修改为真实地址
                    $middle_value = 'http://api12.wxmanage.ittun.com/upload/a.png';
                    break;
                default:
                    break;
            }
        }
        //原文链接判定
        if ($condition['use_href'] && !empty($condition['href_value'])) {
            $href_value = $condition['href_value'];
        }
        return ['head_value' => $head_value, 'middle_value' => $middle_value, 'foot_value' => $foot_value, 'content_source_url' => $href_value];
    }


    /**
     * 顶部中部底部素材标签样式转换
     * @param $id
     * @param $value
     * @return array
     * @throws
     */
    public static function head_middle_foot_convert($id, $value)
    {
        $condition = Db::name('wechat_account')->join('wechat_attach ', 'wechat_attach.id = wechat_account.id')->where('wechat_account.id', '=', $id)->find();
        //处理头尾中部图片类型附加消息
        if ($condition['use_head'] && $condition['head_type'] == 1) {
            $value['head_value'] = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $value['head_value'] . '"/></section></section></section></section>';
        }
        if ($condition['use_middle']) {
            switch ($condition['middle_type']) {
                //1图片 2超链接 3小程序卡片 4小程序图片 5小程序超链接
                case 1:
                    $value['middle_value'] = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $value['middle_value'] . '"/></section></section></section></section>';
                    break;
                default:
                    break;
            }
        }
        if ($condition['use_foot'] && $condition['foot_type'] == 1) {
            $value['foot_value'] = '<section style="margin:0 auto;max-width:768px;letter-spacing:.7px;color:#000;text-align:justify;word-break:break-word;overflow:hidden;"><section style="margin-top:14px;"><section class="contentImg" style="max-width:640px"><section><img src="' . $value['foot_value'] . '"/></section></section></section></section>';
        }
        return $value;
    }

}