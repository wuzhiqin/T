<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/11/3/003
 * Time: 上午 10:26
 */

namespace app\admin\controller;


use think\Db;
use think\facade\Request;

/**
 * 微信公众号流量主统计
 * Class MpFlow
 * @package app\admin\controller
 */
class MpFlow
{

    /**
     * 单账号流量主数据
     * @return \think\response\Json
     * @throws
     */
    public function single()
    {
        $account_id = Request::post("id", 0);
        $account = Db::name("wechat_account")->where("id", $account_id)->field('id,gh_id,nick_name,verify_type_info,scan_account,last_login_time,last_send_time')->find();
        if (!$account) {
            return response_json(null, 500, "公众号id不存在！");
        }
        $month = date("Y-m-d", time() - (30 * 24 * 60 * 60));  //默认查询最近30天的记录
        $list = Db::name("wechat_account_flow")->where("account_id", $account_id)->where("date", ">", $month)->order("date", "desc")->select();
        return response_json(['account' => $account, 'list' => $list], 200, "success");
    }


    /**
     * 账号流量主聚合统计
     * @return \think\response\Json
     * @throws
     */
    public function multi()
    {
        $date = Request::post("date", "");
        $yesterday = date("Y-m-d", time() - (24 * 60 * 60));
        $date = $yesterday;  //默认统计昨天的
        $list = Db::name("wechat_account_flow")->where("date", $date)->select();
        $click_count = Db::name("wechat_account_flow")->where("date", $date)->sum("click_count");
        $exposure_count = Db::name("wechat_account_flow")->where("date", $date)->sum("exposure_count");
        $income = Db::name("wechat_account_flow")->where("date", $date)->sum("income");
        $click_rate = Db::name("wechat_account_flow")->where("date", $date)->avg("click_rate");
        $avg_income = Db::name("wechat_account_flow")->where("date", $date)->avg("avg_income");
        return response_json(['list' => $list, 'count' => [
            'click_count' => $click_count,
            'income' => $income,
            'exposure_count' => $exposure_count,
            'click_rate' => $click_rate,
            'avg_income' => $avg_income
        ]]);
    }

}