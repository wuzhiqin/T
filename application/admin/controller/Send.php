<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/24/024
 * Time: 下午 1:39
 */

namespace app\admin\controller;


use app\common\Tools;
use think\Controller;
use think\Db;
use think\facade\Request;
use app\common\model\Send as SendM;
use app\common\model\SendList as SendListM;

class Send extends Controller
{

    /**
     * 单个账号群发
     * @return \think\response\Json
     * @throws
     */
    public function send()
    {
        $param = get_param('id,num', 1);
        $account = Db::name("wechat_account")->where('id', $param['id'])->find();
        if (!$account) {
            return response_json(null, 500, "公众号id不存在！");
        }

        if ($param['num'] < 1 || $param['num'] > 8) {
            return response_json(null, 500, "群发素材数量不能为0！");
        }

        $send_id = Db::name("send")->insertGetId(['send_id' => 0, 'send_num' => $param['num'], 'wechat_id' => $param['id']]);

        switch ($account['verify_type_info']) {
            case -1:  //未认证公众号群发
                $url = config('send_msg_server_url') . "/send/sendNoAuth/send";
                Tools::curl($url, ['id' => $send_id]);
                break;
            case 0:  ////已认证公众号群发
                $url = config('send_msg_server_url') . "/send/sendAuth/index";
                Tools::curl($url, ['send_id' => $send_id]);
                break;
        }
        return response_json(null, 200, "群发任务已下发，请查看群发记录结果！");
    }

    /**
     * 多账号群发
     * @param string $remark 备注
     * @return \think\response\Json
     */
    public function batchSend($remark = '无备注',$verify_type=0)
    {

        $param = get_param('type,data,num,send_time', 1);
        if ($verify_type != -1){
            $verify_type = 0;
        }
        $where[] = ['verify_type_info', '=', $verify_type];
        //1 按分类 2按分组 3自定义，筛选出符合要求的公众号
        switch ($param['type']) {
            case 1:
                $where[] = ['material_type', '=', $param['data']];
                break;
            case 2:
                $where[] = ['relation_id', '=', $param['data']];
                break;
            case 3:
                $where[] = ['gh_id', 'in', $param['data']];
                break;
            case 4:
                $where = 1;
                break;
            default:
                return response_json(null, 500, '错误的类型！');
                break;
        }

        $account_list = Db::name('wechat_account')->where($where)->column('id,material_type,scan_account,verify_type_info');//符合条件的公众号
        if (count($account_list) < 1) {
            return response_json(null, 500, '无符合要求的公众号！');
        }
        if ($param['send_time'] > 0) {//定时群发
            if (!(strlen($param['send_time']) == 10 && $param['send_time'] > time())) {
                return response_json(null, 500, "请设置有效且大于当前时间的时间戳!传入时间:" . date('Y-m-d H:i:s', $param['send_time']));
            }
            //若为定时群发，则不检查素材数量，直接插入记录
            $send_list_id = Db::name("send_list")->insertGetId(['send_count' => count($account_list), 'send_time' => $param['send_time'], 'create_time' => time(), 'remark' => $remark,'verify_type' => $verify_type]);
            if ($send_list_id) {
                $send_data = array();
                foreach ($account_list as $k => $v) {
                    $send_data[] = ['send_id' => $send_list_id, 'send_num' => $param['num'], 'wechat_id' => $v['id'], 'verify_type' => $v['verify_type_info']];
                }
                if (empty($send_data)) return response_json(null, 500, "创建定时任务失败，删除后重试。-空消息列表");
                $send_ok = Db::name("send")->insertAll($send_data);
                if ($send_ok) {
                    return response_json(null, 200, "创建定时任务成功");
                } else {
                    return response_json(null, 500, "创建定时任务失败，请删除后重新创建");
                }
            } else {
                return response_json(null, 500, "创建定时任务失败");
            }
        } elseif ($param['send_time'] == -1) {//立即群发
            //检查素材数量，不够则退出并输出结果
            $type_count = array();
            $fail_type_list = array();
            foreach ($account_list as $value) {
                isset($type_count[$value['material_type']]) ? $type_count[$value['material_type']]++ : $type_count[$value['material_type']] = 1;
            }
            if (empty($type_count)) die(json_encode(['data' => false, 'message' => '无符合条件的公众号', 'code' => 500], 320));
            foreach ($type_count as $key => $value) {
                $count_num = Db::name('material_news')->where('use_type', '=', 0)->where('material_type', '=', $key)->count();
                if (($value * $param['num'] - $count_num) > 0) $fail_type_list[$key] = $value * $param['num'] - $count_num;
            }
            if (!empty($fail_type_list)) {
                $type_list = Db::name('type')->column('id,type_name');
                $res = '缺少文章!';
                foreach ($fail_type_list as $key => $value) {
                    $res .= $type_list[$key] . '共' . $type_count[$key] . '个公众号，需要' . $type_count[$key] * $param['num'] . '篇文章，还缺少' . $value . '篇素材,';
                }
                die(json_encode(['data' => false, 'message' => $res, 'code' => 500], 320));
            }
            unset($type_count, $count_num, $fail_type_list, $res);

            //开始批量群发
            $send_list_id = Db::name("send_list")->insertGetId(['send_count' => count($account_list), 'create_time' => time(), 'remark' => $remark, 'verify_type' => $verify_type]);
            foreach ($account_list as $k => $v) {
                $send_id = Db::name("send")->insertGetId(['send_id' => $send_list_id, 'send_num' => $param['num'], 'wechat_id' => $v['id'], 'verify_type' => $v['verify_type_info']]);
                switch ($v['verify_type_info']) {
                    case -1:  //未认证公众号群发
                        $url = config('send_msg_server_url') . "/send/sendNoAuth/send";
                        Tools::curl($url, ['id' => $send_id]);
                        break;
                    case 0:  ////已认证公众号群发
                        $url = config('send_msg_server_url') . "/send/sendAuth/index";
                        Tools::curl($url, ['send_id' => $send_id]);
                        break;
                }
            }
            return response_json(null, 200, "群发任务已下发，请查看群发记录结果！");
        } else {
            return response_json(null, 500, "错误的定时时间,-1为立即群发,有效时间戳为定时群发");
        }

    }

    //一键群发任务列表  -1 未认证   0 认证
    public function sendTask($verify_type = 0, $page = 1, $limit = 30)
    {
        $sendListM = new SendListM();
        $res['total_limit'] = $sendListM->where('verify_type',$verify_type)->count();    //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                        //可分页数
        $res['current_page'] = $page;                                                           //当前页
        $res['list'] = $sendListM->sendList($verify_type, $page, $limit);                        //数据列
        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '暂无数据');
        }
    }

    //删除一键群发任务
    public function sendTaskDel()
    {
        $param = get_param('send_id',1);
        $sendId = trim($param['send_id']);
        $sendListM = new SendListM();
        $sendListM->sendTaskDel($sendId);
        return response_json(true, 200, '成功');
    }

    //一键群发详情列
    public function sendDetails($page = 1, $limit = 50)
    {
        $param = get_param('id', 1);
        $sendId = trim($param['id']);
        $sendM = new SendM();
        $res['total_limit'] = $sendM->taskDetailsCount($sendId);                                //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                        //可分页数
        $res['current_page'] = $page;                                                           //当前页
        $res['list'] = $sendM->taskDetails($sendId, $page, $limit);                              //数据列
        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '暂无数据');
        }
    }

    //单次群发详情列   $verify_type -1 未认证   0 认证
    public function oneSendTask($verify_type = 0, $page = 1, $limit = 50)
    {
        $sendM = new SendM();
        $res['total_limit'] = $sendM->taskDetailsCount(0,$verify_type);                              //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                        //可分页数
        $res['current_page'] = $page;                                                           //当前页
        $res['list'] = $sendM->taskDetails(0, $page, $limit, $verify_type);                            //数据列
        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '暂无数据');
        }
    }

    //重新发送
    public function Resend()
    {
        $param = get_param('send_id',1);
        //重置数据
        $sendId = trim($param['send_id']);
        $send = SendM::get($sendId);
        $send->status = 0;
        $send->media_id = '';
        $send->msg_id = '';
        $send->msg_data_id = '';
        $send->save();
//        $update = ['status'=>0,'media_id'=>'','msg_id'=>'','msg_data_id'=>''];
        switch ($send['verify_type']) {
            case -1:  //未认证公众号群发
                $url = config('send_msg_server_url') . "/send/sendNoAuth/send";
                Tools::curl($url, ['id' => $sendId]);
                break;
            case 0:  ////已认证公众号群发
                $url = config('send_msg_server_url') . "/send/sendAuth/index";
                Tools::curl($url, ['send_id' => $sendId]);
                break;
        }
        return response_json(null, 200, "已重新发送，请待会查看发送结果！");
    }

}