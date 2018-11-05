<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/23
 * Time: 11:32
 */

namespace app\common\service;

use think\Db;

class Material
{
    /**
     * 获取文章内容
     * @param $news_id
     * @param $referer_id 1百家号，2UC头条
     * @return bool|mixed|null|string|string[]
     */
    public static function get_article_bjh($news_id, $referer_id = 1, $msgid = 1)
    {
        switch ($referer_id) {
            case 1:  //百家号
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
                $url = 'https://mbd.baidu.com/newspage/data/landingshare?context={"nid":"' . $news_id . '","sourceFrom":"bjh"}';
                $content = file_get_contents($url);//获取url内容
                $content = str_replace(array('百家号', "\r\n", "\r", "\n", "\t"), array('微信号', ''), $content);//替换文字，删除换行
                $content = preg_replace('/(<script[^>]*>(.*?)<\/script>)|(<style[^>]*>(.*?)<\/style>)|(style="padding-top:[^"]*")/si', "", $content);//删除js,style,图片padding
                preg_match('/<div[^>]*class="mainContent[^>]*>(.*)/si', $content, $match);//未正确匹配对称div标签，下方为对称匹配正则方式，后续升级
                //<div[^>]*>[^<>]*(((?'Open'<div[^>]*>).**)+((?'-Open'</div>)[^<>]*)+)*(?(Open)(?!))</div>
                //if(!isset($match[0]))return false;
                $content = str_replace(array('<div', '</div>'), array('<section', '</section>'), $match[0]);//替换文字，删除换行section
                $content = str_replace($class_list, $style_list, $content);//替换class样式
                return $content;
                break;
            case 2:  //UC头条
                $content = Db::name("material_content")->where("content_id", $msgid)->find();
                $content = $content['content'];
                return $content;
                break;
            case 3:  //趣头条
                $content = Db::name("material_content")->where("content_id", $msgid)->find();
                $content = $content['content'];
                return $content;
                break;
        }
    }


    /**
     * 替换文章图片url
     * @param $content
     * @param $url
     * @param $head_value
     * @param $foot_value
     * @param $middle_value
     * @return mixed|string
     */
    public static function replace_img_url($content, $url, $head_value = null, $foot_value = null, $middle_value = null, $referer_id = 1)
    {
        switch ($referer_id) {
            case 1:  //百家号
                preg_match_all('/<img[^>]*src="(.*?)"/si', $content, $match);//未正确匹配对称div标签，下方为对称匹配正则方式，后续升级
                foreach ($match[1] as $key => $value) {
                    if (isset($url[$key])) {
                        $content = str_replace($value, $url[$key], $content);
                    }
                }
                break;
            case 2:  //UC头条
                foreach ($url as $k => $v) {
                    $content = str_replace("<!--{img:$k}-->", "<img src='{$v}' style=\"position:relative;max-width:100%;background-color:#eaeaea;overflow:hidden;margin:0 auto;box-shadow:0 0 0 0.01rem rgba(0,0,0,.06);\">", $content);
                }
                break;
            case 3:  //趣头条
                foreach ($url as $k => $v) {
                    $content = str_replace("<!--{img:$k}-->", "<img src='{$v}' style=\"position:relative;max-width:100%;background-color:#eaeaea;overflow:hidden;margin:0 auto;box-shadow:0 0 0 0.01rem rgba(0,0,0,.06);\">", $content);
                }
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

    /**
     * 通过类型筛选素材
     * @param str $material_type
     * @param int $num
     * @return mixed
     * @throws
     */
    public static function select_material($material_type, $num = 8)
    {
        $id_list = Db::name('material_news')->where("material_type", $material_type)->where('use_type', 0)->column('id');
        $count = count($id_list);
        if ($num > $count) return [];//die(json_encode(['code' => 500, 'message' => "素材库图文数量不够，当前素材库有{$count}条,请核实后在确认生成数量！", 'data' => false]));
        $id_key_list = array_rand($id_list, $num);
        if ($num == 1) $id_key_list = array($id_key_list);
        foreach ($id_key_list as $id_key) {
            $where_id[] = $id_list[$id_key];
        }
        $list = Db::name('material_news')->where('id', 'in', $where_id)->select();
        Db::name('material_news')->where('id', 'in', $where_id)->update(['use_type' => 1]);
        return $list;
    }

}