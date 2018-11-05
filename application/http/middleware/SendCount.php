<?php
/**
 * Created by PhpStorm.
 * User: Panco
 * Date: 2018/10/24/024
 * Time: 上午 10:52
 */

namespace app\http\middleware;

use think\Db;

class SendCount
{

    public function handle($request, \Closure $next)
    {
        //这里写前置操作
//		检查正在发送的数量
		//$count_num = Db::name('config')->where('id','=',2)->value('config_value');
		Db::name('config')->where('id','=',2)->setInc('config_value');

        $response = $next($request);

        //这里写后置操作
		Db::name('config')->where('id','=',2)->setDec('config_value');
        return $response;
    }

}