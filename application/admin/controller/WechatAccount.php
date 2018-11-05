<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/22
 * Time: 10:12
 */

namespace app\admin\controller;

use app\common\model\AuthorizeParam;
use app\common\service\Common;
use app\thirdparty\controller\Authorize;
use think\Controller;
use app\common\model\WechatAttach as WechatAttachM;
use app\common\model\WechatAccount as WechatAccountM;

class WechatAccount extends Controller
{
    //新建微信账号和修改
    public function updateWechat($scan_account = '')
    {
        $param = get_param(['id', 'material_type', 'relation_id', 'login_password']);
        foreach ($param as $k => $v) {
            $param[$k] = trim($param[$k]);
        }
        $param['scan_account'] = $scan_account ? trim($scan_account) : '未知';
        $validate = new \app\admin\validate\WechatAccount();
        if (!$validate->check($param)) return response_json(false, 400, $validate->getError());

        $res = WechatAccountM::update($param);
        if ($res) {
            return json(['data' => true, 'message' => '成功', 'code' => 200]);
        } else {
            return json(['data' => false, 'message' => '失败', 'code' => 400]);
        }
    }

    //删除微信账号 TODO 待优化
    public function delWechat()
    {
        $param = get_param('id', 1);
        $id = trim($param['id']);
        $weChatAccountM = new WechatAccountM();
        $weChatAttachM = new WechatAttachM();
        $res = $weChatAccountM->where('id', $id)->delete();
        $res_attach = $weChatAttachM->where('id', $id)->delete();
        if ($res || $res_attach) {
            return json(['data' => true, 'message' => '成功', 'code' => 200]);
        } else {
            return json(['data' => false, 'message' => '失败', 'code' => 400]);
        }
    }

    //根据id查询微信账号
    public function weChat($id = '')
    {
        $res = WechatAccountM::field('id,gh_id,nick_name,scan_account,appid,material_type,relation_id,login_password')->get($id);
        if ($res) {
            return json(['data' => $res, 'message' => '成功', 'code' => 200]);
        } else {
            return json(['data' => false, 'message' => '失败', 'code' => 400]);
        }
    }

    /**
     * 查询所有有效微信账号 TODO 待测试 数据接收待验证
     * @param string $type auth-认证号 -noAuth-未认证
     * @param int $material_type 分类id
     * @param int $relation_id 分组id
     * @param string $gh_id 原始id
     * @param string $scan_account 扫码号
     * @param string $nick_name 微信名
     * @param int $page 分页
     * @param int $limit 显示条数
     * @return json
     */
    public function weChats($type = 'auth', $material_type = 0, $relation_id = -1, $gh_id = '', $scan_account = '', $nick_name = '', $page = 1, $limit = 10)
    {
        $where_gh_id = $this->determine($gh_id, 'gh_id');
        $where_material_type = $this->determine($material_type, 'material_type');
        $where_scan_account = $this->determine($scan_account, 'scan_account');
        $where_nick_name = $this->determine($nick_name, 'nick_name', 'like');

        if ($relation_id > -1) {
            $where_relation_id = array(['relation_id', '=', intval(trim($relation_id))]);
        } else {
            $where_relation_id = array();
        }

        //判定认证号查询还是未认证
        if ($type == 'auth') {
            $verify_type_info = 0;
        } else if ($type == 'noAuth') {
            $verify_type_info = -1;
        } else {
            return response_json(false, 400, 'type参数有误');
        }

        $whereAppend = array();
        //判定无用数据
        switch ($verify_type_info) {
            case -1:                //未认证号
                $whereAppend = array(['appid', '<>', ''], ['login_password', '<>', ''], ['material_type', '<>', '']);     //追加条件，用于判断数据的可用性
                break;
            case 0:                 //认证号
                $whereAppend = array(['appid', '<>', ''], ['material_type', '<>', '']);                                   //追加条件，用于判断数据的可用性
                break;
        }

        //合并数组
        $whereArr = $this->batchArr(array($where_gh_id, $where_material_type, $where_relation_id, $where_scan_account, $where_nick_name, $whereAppend));
        $weChatAccountM = new WechatAccountM();
        $res['total_limit'] = $weChatAccountM->effectiveCount($whereArr, $verify_type_info);       //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                           //可分页数
        $res['current_page'] = $page;                                                              //当前页
        $res['list'] = $weChatAccountM->effective($whereArr, $verify_type_info, $page, $limit);     //查询数据

        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '无数据');
        }

    }

    //批量合并数组（限固定类型）
    protected function batchArr($array)
    {
        $arr = array();
        foreach ($array as $k => $v) {
            $arr = array_merge($arr, $v);
        }
        return $arr;
    }

    /**
     * 条件判断返回
     * @param string $value 判断值
     * @param string $name 字段名
     * @param string $symbol 比较符
     * @param bool $identifying 是否是负数对比
     * @return array
     */
    protected function determine($value, $name, $symbol = '=', $identifying = false)
    {
        if ($identifying) {
            if ($value > -1) {
                return array([$name, '=', intval(trim($value))]);
            } else {
                return array();
            }

        }

        if ($value) {
            if ($symbol == 'like') {
                return array([$name, $symbol, '%' . trim($value) . '%']);
            } else {
                return array([$name, $symbol, trim($value)]);
            }

        } else {
            return array();
        }
    }

    /*
     * (有效)扫码号列表    TODO 待测试
     * @return json
     */
    public function scanAccount()
    {
        $res = WechatAccountM::field('scan_account')
            ->where('appid', '<>', '')
            ->where('material_type', '<>', 0)
            ->where('login_password', '<>', '')
            ->group('scan_account')
            ->select();
        if ($res) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '暂无数据');
        }
    }


    /*
     * 查询无效账号列表 TODO 待完善  1-数据待补全  2-需要重新授权
     */
    public function invalidAccount($page = 1, $limit = 10)
    {
        $weChatAccountM = new WechatAccountM();
        $res['total_limit'] = $weChatAccountM->invalidCount();                                  //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                        //可分页数
        $res['current_page'] = $page;                                                           //当前页
        $res['list'] = $weChatAccountM->invalid($page, $limit);                                  //查询数据
        //状态判定
        foreach ($res['list'] as $k => $v) {
            if ($v['login_password'] == '' || $v['material_type'] == 0 || $v['gh_id'] == '') {
                $res['list'][$k]['status'] = 1;
            } elseif ($v['appid'] == '') {
                $res['list'][$k]['status'] = 2;
            }
        }

        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '无数据');
        }
    }


    //批量所需字段gh_id，material_type,relation_id,login_password,scan_account TODO 待测试
    public function batch()
    {
        if (empty($_FILES['excel'])) {
            die(json_encode(['data' => false, 'message' => '接收不到文件，请重新上传', 'code' => 500]));
        }
        $data = $_FILES['excel']['tmp_name'];
        $weChatAccountM = new WechatAccountM();
        $weChatAttachM = new WechatAttachM();
        $common = new Common();
        $excel_data = $common->importExcel($data);
        array_shift($excel_data);         //删除第一个数组(标题);
        $data = [];
        //删除空白行数组
        foreach ($excel_data as $k => $v) {
            $t = '';
            foreach ($excel_data[$k] as $ks => $vs) {
                $t .= $excel_data[$k][$ks];
            }
            //如果$t为null 说明整条数据都为空，删除
            if ($t == null) {
                unset($excel_data[$k]);
            }
        }
        $excel_count = count($excel_data);        //表格数据总条数
        //对应字段赋值
        $lack_gh_id = '';       //记录缺少数据的行数
        foreach ($excel_data as $k => $v) {
            if (isset($excel_data[$k]['A']) && isset($excel_data[$k]['B']) && $excel_data[$k]['B'] == 0 && isset($excel_data[$k]['D'])) {
                $data[$k]['gh_id'] = $excel_data[$k]['A'];
                $data[$k]['material_type'] = $excel_data[$k]['B'];
                $data[$k]['relation_id'] = $excel_data[$k]['C'];
                $data[$k]['login_password'] = $excel_data[$k]['D'];
                $data[$k]['scan_account'] = $excel_data[$k]['E'] ? $excel_data[$k]['E'] : '未知';
            } else {
                $lack_gh_id .= intval($k + 2) . ',';
            }
        }
        $lack_gh_id = '缺少数据的行数：' . substr($lack_gh_id, 0, -1);
        $repeat_data = '';      //记录数据库已存在的数据对应excel表的行数
        $success_num = 0;       //记录成功录入条数
        foreach ($data as $k => $v) {
            $is = $weChatAccountM->where('gh_id', $data[$k]['gh_id'])->find();
            if ($is) {
                $repeat_data .= intval($k + 2) . ',';
            } else {
                $res = $weChatAccountM->insertGetId($data[$k]);
                $res_attach = $weChatAttachM->insert(['id' => $res]);
                $res || $res_attach ? $success_num += 1 : '';
            }
        }
        $repeat_data = '已存在账号的行数：' . substr($repeat_data, 0, -1);
        //失败条数
        $fail_num = $excel_count - $success_num;

        return response_json(true, 200, '总条数:' . $excel_count . '条,成功:' . $success_num . '条，失败:' . $fail_num . '条,' . $repeat_data . ',' . $lack_gh_id);
    }


    /******************************** 附表-上中下内容和超链 *****************************/
    //固定条件判断返回
    public function regular($value, $name)
    {
        if ($value == -1) {
            return array();
        } elseif ($value == 0) {
            return array([$name, '=', '']);
        } elseif ($value == 1) {
            return array([$name, '<>', '']);
        }
    }

    //查询头中尾设置状态数据  -1-查询所有   0-否   1-是
    public function settingState($use_head = -1, $use_foot = -1, $use_middle = -1, $use_href = -1, $head_value = -1, $foot_value = -1, $middle_value = -1, $href_value = -1, $page = 1, $limit = 10)
    {
        //条件判断
        $use_head = $this->determine($use_head, 'w_at.use_head', '=', true);
        $use_foot = $this->determine($use_foot, 'w_at.use_foot', '=', true);
        $use_middle = $this->determine($use_middle, 'w_at.use_middle', '=', true);
        $use_href = $this->determine($use_href, 'w_at.use_href', '=', true);
        $head_value = $this->regular($head_value,'w_at.head_value');
        $foot_value = $this->regular($foot_value,'w_at.foot_value');
        $middle_value = $this->regular($middle_value,'w_at.middle_value');
        $href_value = $this->regular($href_value,'w_at.href_value');

        //合并数组
        $whereArr = $this->batchArr(array($use_head, $use_foot, $use_middle, $use_href,$head_value,$foot_value,$middle_value,$href_value));
        if (empty($whereArr)) $whereArr = 1;

        $weChatAccountM = new WechatAccountM();
        $res['total_limit'] = $weChatAccountM->settingStateCount($whereArr);                //统计总条数
        $res['total_page'] = ceil($res['total_limit'] / $limit);                   //可分页数
        $res['current_page'] = $page;                                                      //当前页
        $res['list'] = $weChatAccountM->settingState($whereArr, $page, $limit);             //查询数据

        /*************************数据处理*************************/
        function judge($value)
        {
            $status = '是';
            if (empty($value)) {
                $status = '否';
            }
            return $status;
        }

        foreach ($res['list'] as $k => $v) {
            $res['list'][$k]['use_head'] = judge($v['use_head']);
            $res['list'][$k]['use_foot'] = judge($v['use_foot']);
            $res['list'][$k]['use_middle'] = judge($v['use_middle']);
            $res['list'][$k]['use_href'] = judge($v['use_href']);
            $res['list'][$k]['head_value'] = judge($v['head_value']);
            $res['list'][$k]['foot_value'] = judge($v['foot_value']);
            $res['list'][$k]['middle_value'] = judge($v['middle_value']);
            $res['list'][$k]['href_value'] = judge($v['href_value']);
        }

        if ($res['list']) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(null, 200, '无数据');
        }
    }


    //根据id查询微信账号(附表-上中下设置部分)
    public function weAttach($id = '')
    {
        $res = WechatAttachM::get($id);
        if (!$res) return response_json(false, '400', '附表数据异常或所传参数有误');
        if ($res) {
            return response_json($res, 200, '成功');
        } else {
            return response_json(false, 400, '失败');
        }
    }

    //修改微信账号(上中下内容使用选择)(附表)
    public function updateAttach()
    {
        $param = request()->post();
        foreach ($param as $k => $v) {
            $param[$k] = trim($v);
        }
        $validate = new \app\admin\validate\WechatAttach();
        if (!$validate->check($param)) return response_json(false, 400, $validate->getError());        //数据验证
        $id = $param['id'];
        unset($param['id']);
        try {
            $res = WechatAttachM::where('id', $id)->update($param);
        } catch (\Exception $e) {
            return response_json(false, 400, $e->getMessage());
        }
        if ($res) {
            return response_json(true, 200, '成功');
        } else {
            return response_json(true, 200, '数据未改动');
        }
    }

    //添加账号授权回调  base64回调地址
    public function authCallback()
    {
        $param = get_param('url', 1);
        $url = (new Authorize())->getAuth($param['url']);
        echo '<script>window.location.href = "' . $url . '"</script>';
    }

    //重置token
    public function tokenReset()
    {
        $param = get_param('id', 1);
        $weChatAccountM = new WechatAccountM();
        $weChat = $weChatAccountM->get($param['id']);
        if (!$weChat) return response_json(false, 400, '该账号不存在啊');
        $appid = $weChat['appid'];
        (new AuthorizeParam())->save(['update_time' => 0], ['name' => 'AuthorizerAccessToken_' . $appid]);
        (new Authorize())->getAuthorizerToken($appid, 1);
        return response_json(true, 200, '成功');
    }

}