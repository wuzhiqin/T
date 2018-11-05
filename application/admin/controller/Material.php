<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/22/022
 * Time: 上午 9:21
 */

namespace app\admin\controller;

use think\Db;

class Material
{

    /**
     * 批量获取百家号文章
     * $throws
     */
    public function updateAllArticle()
    {
        $param = get_param('material_type',1);
        $where[] = ['last_update_time', '<', time() - 43200];
        if ($param['material_type'] > 0) {
            $where = [['material_type', '=', $param['material_type']]];
            $id_list = Db::name('material_account')->where($where)->column('id');
        } elseif ($param['material_type'] == -1) {
            $id_list = Db::name('material_account')->column('id');
        } else {
            return json(['data' => false, 'code' => 500, 'message' => '错误的类型']);
        }
        foreach ($id_list as $id) {
            $this->updateArticle($id);
        }
        Db::name("material_account")->where("id", $param['id'])->update(['last_update_time' => time()]);
        return response_json(null, 200, '执行结束！');
    }


    /** 更新某个百家号的文章素材
     * @param int $id
     * @return mixed
     * @throws
     **/
    public function updateArticle($id = 0)
    {
        if ($id > 0) {
            $param['id'] = $id;
        } else {
            $param = get_param(['id']);
        }
        $res = Db::name('material_account')->where('id', '=', $param['id'])->find();
        switch ($res['type']) {
            case 1:
                $res = $this->get_news_bjh($res);  //百家号
                break;
            case 2:
                $res = $this->get_news_uctt($res);  //UC头条
                break;
            case 3:
                $res = $this->get_news_qtt($res);  //趣头条
                break;
            default:
                return json(['data' => false, 'code' => 500, 'message' => '未知的账号来源']);
                break;
        }

        return response_json($res, 200);
    }


    /**
     * 抓取百家号文章
     * @throws
     */
    public function get_news_bjh($account_info)
    {
        $material_update_type = Db::name('config')->where('config_name', '=', 'material_update_type')->value('config_value');
        if ($material_update_type && ((time() - $material_update_type) < 300)) die(json_encode(['data' => false, 'code' => 500, 'message' => '正在执行上一次更新，请耐心等待'], 320));
        Db::name('config')->where('config_name', '=', 'material_update_type')->update(['config_value' => time()]);//变更采集状态
        $result = $this->get_article_list_bjh($account_info['account_id']);
        if (!$result) return '抓取素材列表失败'; //百家号文章列表抓取不到，返回失败
        foreach ($result['items'] as $value) {
            $data = [];
            if ($account_info['last_update_time'] != 0 && $value['updated_at'] <= $account_info['last_update_time']) {
                break;//采集至上次采集位置，停止采集
            }
            $value['content'] = json_decode($value['content'], 1);
            if (!isset($value['content']['items'])) continue;//非图文素材，跳过
            if (Db::name('material_news')->where('news_id', '=', $value['id'])->where('account_local_id', '=', $account_info['id'])->find()) break;
            $data['news_id'] = $value['id'];
            $data['title'] = $value['title'];
            $data['account_local_id'] = $account_info['id'];
            $data['material_account_id'] = $account_info['account_id'];
            $data['material_type'] = $account_info['material_type'];
            $data['content_source_url'] = 'https://mbd.baidu.com/newspage/data/landingshare?context={"nid":"' . $value['id'] . '","sourceFrom":"bjh"}';
            $data['content_pic_url'] = [];  //文章内图片原文地址
            //封面,存网络地址
            if (isset($value['cover_images'][0])) {
                if (!isset($value['cover_images'][0]['src'])) continue;
                $data['cover_pic_url'] = $value['cover_images'][0]['src'];
            }

            foreach ($value['content']['items'] as $v) {
                if ($v['type'] == 'image') {
                    $data["content_pic_url"][] = $v['data']['original']['src'];
                }
            }
            $data['content_pic_url'] = json_encode($data['content_pic_url']);

            try {
                $data['referer_id'] = $account_info['type'];  //存储图文消息的时候存储来源类型，比如百家号1
                Db::name('material_news')->insert($data);
            } catch (\Exception $e) {
                die(json_encode(['data' => true, 'code' => 500, 'message' => '失败'], 320));
            }
        }
        //更新
        Db::name('material_account')->where('id', '=', $account_info['id'])->update(['last_update_time' => time()]);
        Db::name('config')->where('config_name', '=', 'material_update_type')->update(['config_value' => 0]);
        return 'success';
    }


    /**
     * 抓取某个百家号文章列表
     **/
    public function get_article_list_bjh($appid, $page = 1, $limit = 25, $time = 0)
    {
        if ($limit > 25 || $limit < 1 || $page < 1) {
            return false;
        }
        if ($time == 0) {
            $time = time();
        }
        $offset = ($page - 1) * $limit;
        $url = 'https://author.baidu.com/list?type=article&context={"offset":"1_' . $offset . '","app_id":"' . $appid . '","last_time":"' . $time . '","pageSize":' . $limit . '}';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIE, "BAIDUID=" . md5(microtime()));
        curl_setopt($ch, CURLOPT_REFERER, 'https://baijiahao.baidu.com');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = json_decode(curl_exec($ch), 1);
        if (isset($result['errno']) && $result['errno'] == 0) {
            return $result['data'];
        } else {
            return false;
        }
    }


    /**
     * 抓取UC头条文章
     * @param $account_info
     * @return mixed
     * @throws
     */
    public function get_news_uctt($account_info)
    {
        $material_update_type = Db::name('config')->where('config_name', '=', 'material_update_type')->value('config_value');
        if ($material_update_type && ((time() - $material_update_type) < 300)) die(json_encode(['data' => false, 'code' => 500, 'message' => '正在执行上一次更新，请耐心等待'], 320));
        Db::name('config')->where('config_name', '=', 'material_update_type')->update(['config_value' => time()]);//变更采集状态
        $result = $this->get_article_list_uctt($account_info['account_id']);
        if (count($result) == 0) {
            return '抓取素材列表失败';
        }
        foreach ($result as $k => $v) {
            $data = [];
            $v['created_at'] = explode(",", $v['created_at']);
            $v['created_at'] = $v['created_at'][0];
            $v['created_at'] = strtotime($v['created_at']);
            if ($account_info['last_update_time'] != 0 && $v['created_at'] <= $account_info['last_update_time']) {
                break;  //采集至上次采集位置，停止采集
            }
            if (Db::name('material_news')->where('news_id', '=', $v['content_id'])->where('account_local_id', '=', $account_info['id'])->find()) break;
            $data['news_id'] = $v['content_id'];
            $data['title'] = $v['title'];
            $data['account_local_id'] = $account_info['id'];
            $data['material_account_id'] = $account_info['account_id'];
            $data['material_type'] = $account_info['material_type'];
            $data['content_source_url'] = "http://a.mp.uc.cn/article.html?uc_param_str=frdnsnpfvecpntnwprdssskt&from=media#!wm_cid={$v['content_id']}!!wm_aid={$v['origin_id']}!!wm_id={$v['shard_id']}";
            $data['cover_pic_url'] = $v['cover_url'];
            $data['content_pic_url'] = [];
            foreach ($v['body']['inner_imgs'] as $k2 => $v2) {
                $data['content_pic_url'][] = $v2['url'];
            }
            $data['content_pic_url'] = json_encode($data['content_pic_url']);
            $data['referer_id'] = $account_info['type'];  //存储图文消息的时候存储来源类型，比如百家号1
            $id = Db::name('material_news')->insertGetId($data);
            if ($id) {
                Db::name("material_content")->insert(['content_id' => $id, 'content' => $v['body']['text']]);
            }
        }
        return 'success';
    }

    /**
     * 抓取某个UC头条文章列表
     * @param $account_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function get_article_list_uctt($account_id, $page = 1, $limit = 100)
    {
        $url = "http://ff.dayu.com/contents/author/{$account_id}?biz_id=1002&_size={$limit}&_page={$page}&_order_type=published_at&status=1&_fetch=1&uc_param_str=frdnsnpfvecpntnwprdssskt&_=1540967183452";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = json_decode(curl_exec($ch), 1);
        return $result['data'];
    }


    /** 抓取趣头条文章
     * @param $account_info
     * @return string
     * @throws
     */
    public function get_news_qtt($account_info)
    {
        $material_update_type = Db::name('config')->where('config_name', '=', 'material_update_type')->value('config_value');
        if ($material_update_type && ((time() - $material_update_type) < 300)) die(json_encode(['data' => false, 'code' => 500, 'message' => '正在执行上一次更新，请耐心等待'], 320));
        Db::name('config')->where('config_name', '=', 'material_update_type')->update(['config_value' => time()]);//变更采集状态

        //分页采集素材
        $result = [];
        for ($page = 1; $page < 20; $page++) {
            $temp = $this->get_article_list_qtt($account_info['account_id'], $page);
            if (count($temp) == 0) {
                break;
            } else {
                $result = array_merge($temp, $result);
            }
        }
        if (count($result) == 0) {
            return '抓取素材列表失败';
        }

        $data = [];
        foreach ($result as $k => $v) {
            $v['publish_time'] = substr($v['publish_time'], 0, -3);  //文章发表时间
            if ($account_info['last_update_time'] != 0 && $v['publish_time'] <= $account_info['last_update_time']) {
                break;  //采集至上次采集位置，停止采集
            }
            if (Db::name('material_news')->where('news_id', '=', $v['id'])->where('account_local_id', '=', $account_info['id'])->find()) break;
            $data['news_id'] = $v['id'];
            $data['title'] = $v['title'];
            $data['account_local_id'] = $account_info['id'];
            $data['material_account_id'] = $account_info['account_id'];
            $data['material_type'] = $account_info['material_type'];
            $data['content_source_url'] = $v['url'];
            $data['cover_pic_url'] = $v['cover'][0];
            $data['referer_id'] = $account_info['type'];  //存储图文消息的时候存储来源类型，比如百家号1

            //获取文章content内容与图片数组，转换文章内容图片格式，最终格式跟uc头条类似
            $ch = curl_init($v['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $preg = '/<div class="content">(.*?)<\/div>/s';
            preg_match($preg, $result, $arr);
            $content = $arr[1];  //内容
            $preg2 = '/<img data-src="(.*?)" data-size=".*?" alt=".*?">/s';
            preg_match_all($preg2, $content, $img);
            foreach ($img[0] as $k2 => $v2) {
                $content = str_replace($v2, "<!--{img:$k2}-->", $content);
            }
            $content = preg_replace("/\s(?=\s)/", "\\1", $content);  //去除多余空格
            $data['content_pic_url'] = json_encode($img[1]);
            $id = Db::name('material_news')->insertGetId($data);
            if ($id) {
                Db::name("material_content")->insert(['content_id' => $id, 'content' => $content]);
            }

        }
        return 'success';
    }

    /**
     * 抓取某个趣头条账号文章列表
     * @param $account_id
     * @param int $page
     * @return array
     */
    public function get_article_list_qtt($account_id, $page = 1)
    {
        $url = "http://api.1sapp.com/wemedia/content/articleList?token=&dtu=200&version=0&os=android&id={$account_id}&page={$page}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = json_decode(curl_exec($ch), 1);
        if ($result['code'] == 0) {
            return $result['data']['list'];
        } else {
            return [];
        }
    }

}