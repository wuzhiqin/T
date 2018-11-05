<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/30
 * Time: 9:48
 */

namespace app\thirdparty\controller;


class CustomMsg {
	public $access_token;
	public $openid;

	public function __construct($appid,$openid) {
		$this->access_token = (new Authorize())->getAuthorizerToken($appid);
		$this->openid = $openid;

	}


}