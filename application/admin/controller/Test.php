<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/11/2
 * Time: 17:43
 */

namespace app\admin\controller;


use Firebase\JWT\JWT;
use think\Exception;

class Test
{
    //签发Token
    public function test()
    {
        $key = '344'; //key
        $time = time(); //当前时间
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time,   //签发时间
            'nbf' => $time + 60 , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time + 60, //过期时间,这里设置2个小时
            'data' => [     //自定义信息，不要定义敏感信息
                'userId' => 1,
                'username' => '李小龙'
            ]
        ];
        echo JWT::encode($token, $key,'HS256'); //输出Token
    }

    public function verification($token)
    {
        $key = '344'; //key要和签发的时候一样
        try {
            JWT::$leeway = 20;                                  //当前时间减去60，把时间留点余地
            $jwt = JWT::decode($token,$key, ['HS256']);
            dump($jwt);
        } catch(\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
//            echo $e->getMessage();
            echo '无效token';
        }catch(\Firebase\JWT\BeforeValidException $e) {         // 签名在某个时间点之后才能用
//            echo $e->getMessage();
            echo 'token还未生效';
        }catch(\Firebase\JWT\ExpiredException $e) {             // token过期
//            echo $e->getMessage();
            echo 'token已过期';
        }catch(Exception $e) {  //其他错误
            echo $e->getMessage();
        }
        //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
    }

    public function authorizations()
    {
        $key = '344'; //key
        $time = time(); //当前时间

        //公用信息
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'iat' => $time, //签发时间
            'data' => [ //自定义信息，不要定义敏感信息
                'userid' => 1,
            ]
        ];

        $access_token = $token;
        $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
        $access_token['exp'] = $time+7200; //access_token过期时间,这里设置2个小时

        $refresh_token = $token;
        $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
        $refresh_token['exp'] = $time+(86400 * 30); //access_token过期时间,这里设置30天

        $jsonList = [
            'access_token'=>JWT::encode($access_token,$key),
            'refresh_token'=>JWT::encode($refresh_token,$key),
            'token_type'=>'bearer' //token_type：表示令牌类型，该值大小写不敏感，这里用bearer
        ];
        Header("HTTP/1.1 201 Created");
        echo json_encode($jsonList); //返回给客户端token信息
    }




    public function test1($token)
    {
        $json = base64_decode($token);
        dump($json);
        $json = '{"iss":"http:\/\/www.helloweba.net","aud":"http:\/\/www.helloweba.net","iat":1541209514,"nbf":1541209514,"exp":1541209574,"data":{"userid":1,"username":"\u674e\u5c0f\u9f99"}}';
        dump(json_decode($json));

        $time = time();
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time,   //签发时间
            'nbf' => $time , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time + 60, //过期时间,这里设置2个小时
            'data' => [     //自定义信息，不要定义敏感信息
                'userid' => 1,
                'username' => '李小龙'
            ]
        ];
        echo '<br>';
        $s = json_encode($token);
        $s = base64_encode($s);
        dump(base64_decode($s));
    }


    public function ce()
    {
        $time = 1541213129 + 120;
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time,   //签发时间
            'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time + 30, //过期时间,这里设置2个小时
            'data' => [     //自定义信息，不要定义敏感信息
                'userid' => 1,
                'username' => '李小龙'
            ]
        ];

        dump(base64_encode(json_encode($token)));
    }

    public function tests()
    {

    }

}