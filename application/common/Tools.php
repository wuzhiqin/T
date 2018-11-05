<?php
/**
 * Created by Panco.
 * User: Administrator
 * Date: 2018/10/23/023
 * Time: 上午 9:36
 */

namespace app\common;


class Tools
{

    /**
     * @param $img_file
     * @return string
     * 本地图片path 转为base64
     */
    public static function imgToBase64($img_file)
    {
        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
            $fp = fopen($app_img_file, "r"); // 图片是否可读权限
            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {           //判读图片类型
                    case 1:
                        $img_type = "gif";
                        break;
                    case 2:
                        $img_type = "jpg";
                        break;
                    case 3:
                        $img_type = "png";
                        break;
                }
                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码
            }
            fclose($fp);
        }
        return $img_base64; //返回图片的base64
    }


    /**
     * 生成随机IP地址
     * @return string
     */
    public static function rand_ip()
    {
        $ip2id = round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
        $ip3id = round(rand(600000, 2550000) / 10000);
        $ip4id = round(rand(600000, 2550000) / 10000);
        //下面是第二种方法，在以下数据中随机抽取
        $arr_1 = array("218", "218", "66", "66", "218", "218", "60", "60", "202", "204", "66", "66", "66", "59", "61", "60", "222", "221", "66", "59", "60", "60", "66", "218", "218", "62", "63", "64", "66", "66", "122", "211");
        $randarr = mt_rand(0, count($arr_1) - 1);
        $ip1id = $arr_1[$randarr];
        return $ip1id . "." . $ip2id . "." . $ip3id . "." . $ip4id;
    }


    /**
     * 搜寻二维数组中是否存在特定值，仅限二维数组
     * @param $needle
     * @param $array
     * @return array|bool
     */
    public static function ext_in_array($needle, $array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $search_key = array_search($needle, $value);
                    if ($search_key !== false) {
                        return array($key, $search_key);
                    }
                }
            }
        }
        return false;
    }


    /**
     * CURL请求封装
     * @param $url
     * @param $data
     * @param bool $referer_url
     * @param bool $cookie
     * @param bool $cookieJar
     * @param int $cookieType
     * @param int $isPost
     * @param int $time
     * @return mixed
     */
    public static function curl($url, $data = [], $referer_url = false, $cookie = false, $cookieJar = false, $cookieType = 1, $isPost = 1, $time = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); //请求地址
        curl_setopt($ch, CURLOPT_POST, $isPost); //是否post请求
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);
        curl_setopt($ch, CURLOPT_HEADER, 0); //是否获取头部信息
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . self::rand_ip(), 'CLIENT-IP:' . self::rand_ip()));  //伪造ip
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36"); //伪造浏览器
        if ($referer_url) curl_setopt($ch, CURLOPT_REFERER, $referer_url); //模拟来路地址
        if ($cookieJar) curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar); //存储cookie
        if ($isPost == 1) curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //post数据
        if ($cookie) {
            if ($cookieType == 1) {
                curl_setopt($ch, CURLOPT_COOKIE, $cookie); //使用cookie字符串
            } else if ($cookieType == 2) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //使用cookieJar文件
            }
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            die(json_encode(['data' => false, 'message' => curl_error($ch), 'code' => 500])); //若错误打印错误信息
        }
        curl_close($ch);
        return $response;
    }


}