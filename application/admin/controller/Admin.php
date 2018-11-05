<?php
/**
 * Created by PhpStorm.
 * User: len0v0
 * Date: 2018/10/18
 * Time: 10:40
 */

namespace app\admin\controller;


use app\common\service\Token;
use app\common\model\AdminAuth;
use app\common\model\Admin as AdminUser;
use app\common\model\AdminRole as AdminRoleM;
use app\common\model\AdminAuth as AdminAuthM;

class Admin
{
    /*
     * 用户登录
     */
    public function login()
    {
        $param = get_param('name,password',1);
        $user = AdminUser::isUser($param['name']);
        if (!$user) return response_json(false,400,'账号不存在！');
        $password = sha1($param['password'] . $user['pwd_salt']);
        if ($password !== $user['password']) {
            return response_json(false,400,'密码错误！');
        }
        AdminUser::lastLogin($user['id']);
        $res = AdminUser::where('name',$param['name'])->where('password',$password)->find();
        if ($res){
            return response_json(['token' => Token::encode($user['id']),'name'=>$user['name'],'role_id'=>$user['role_id']],200,'登陆成功！');
        }else{
            return response_json(false,400,'登陆失败！');
        }

    }

    /*
     * 创建用户 （最高管理员可用）
     */
    public function createUser()
    {
        $param = get_param('name,password,role_id',1);
        if(AdminUser::isUser($param['name'])) return response_json(false,400,'该用户名已存在！');
        if ($param['role_id'] != 0) $this->getRole($param['role_id']);              //检测角色是否存在
        $param['pwd_salt'] = random_keys(10);
        $param['password'] = sha1(md5($param['password']).$param['pwd_salt']);
        $res = AdminUser::create($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 删除用户 (最高管理员可操作)
     */
    public function delUser()
    {
        $param = get_param('id');
        if(AdminUser::where('id',$param['id'])->delete()){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    /*
     * 修改用户基本信息 （修改登陆用户本身数据）
     */
    public function updateUser()
    {
        $param = get_param('name,role_id',1);
        global $userId;
        $param['id'] = $userId;
        if (!AdminUser::get($userId)) return response_json(false,400,'该用户不存在！');
        $user = AdminUser::where('id','<>',$userId)->where('name',$param['name'])->find();
        if($user) return response_json(false,400,'该用户名已存在！');
        if ($param['role_id'] != 0) $this->getRole($param['role_id']);          //检测角色是否存在
        if (AdminUser::update($param)){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //修改密码  （修改登陆用户本身数据）
    public function password()
    {
        $param = get_param('now_pwd,password',1);
        global $userId;
        $param['id'] = $userId;
        $adminUserM = new AdminUser();
        $user = $adminUserM->where('id',$param['id'])->find();
        if (!$user) return response_json(false,400,'账号不存在！');
        $password = sha1($param['now_pwd'] . $user['pwd_salt']);
        if ($password !== $user['password']) {
            return response_json(false,400,'密码错误！');
        }
        $data['pwd_salt'] = random_keys(10);
        $data['password'] = sha1($param['password'].$data['pwd_salt']);
        if ($adminUserM->where('id',$param['id'])->update($data)){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //获取单个用户数据(修改用户基本信息可用)
    public function user($id)
    {
        $res = AdminUser::field('id,name,role_id')->get($id);
        if ($res){
            return response_json($res,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //获取所有用户 （最高管理员可用）
    public function users($page=1,$limit=10)
    {
        $res['total_limit'] = AdminUser::count();
        $res['total_page'] = ceil($res['total_limit']/$limit);
        $res['current_page'] = $page;
        $res['list'] = AdminUser::allUser($page,$limit);
        if ($res['list']){
            return response_json($res,200,'请求成功');
        }else{
            return response_json(null,200,'无数据');
        }
    }


    /******************************角色管理******************************/

    //创建角色
    public function createRole($description='')
    {
        $param = get_param('role_name,role_auth',1);
        $param['description'] = $description;
        if (AdminRoleM::where('role_name',$param['role_name'])->find()) return response_json(false,400,'该角色已存在');
        $res = AdminRoleM::create($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //修改角色
    public function updateRole()
    {
        $param = get_param('id,role_name,role_auth,description',1);
        $this->getRole($param['id']);     //检测该角色是否存在
        $user = AdminRoleM::where('id','<>',$param['id'])->where('role_name',$param['role_name'])->find();
        if($user) return response_json(false,400,'该角色已存在！');
        $res = AdminRoleM::update($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }

    }

    //删除角色
    public function delRole($id)
    {
        $role = $this->getRole($id);
        $role->isDelete = 2;
        $res = $role->save();
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //获取单个角色数据
    public function role($id='')
    {
        $res = $this->getRole($id);
        return response_json($res,200,'请求成功');
    }

    //获取所有角色,默认分页
    public function roles($page=1,$limit=10,$all=false)
    {
        if ($all){
            $res['list'] = AdminRoleM::where('isDelete',1)->select();
        }else{
            $res['total_limit'] = AdminRoleM::where('isDelete',1)->count();
            $res['total_page'] = ceil($res['total_limit']/$limit);
            $res['current_page'] = $page;
            $res['list'] = AdminRoleM::where('isDelete',1)->page($page)->limit($limit)->select();
        }

        if ($res['list']){
            return response_json($res,200,'请求成功');
        }else{
            return response_json(null,200,'无数据');
        }
    }

    //根据id获取角色
    protected function getRole($id)
    {
        $role = AdminRoleM::get($id);
        return $role ? $role : die(json_encode(['data'=>false,'code'=>400,'message'=>'该角色不存在']));
    }


    /******************************权限管理******************************/

    //创建权限路径
    public function createAuth()
    {
        $param = get_param('auth_name,auth_url,auth_parent',1);
        if (AdminAuthM::where('auth_url',$param['auth_url'])->find()) return response_json(false,400,'该路由已存在');
        $res = AdminAuthM::create($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //修改权限
    public function updateAuth()
    {
        $param = get_param('id,auth_name,auth_url,auth_parent',1);
        $this->getAuth($param['id']);     //检测该权限是否存在
        $auth = AdminAuthM::where('id','<>',$param['id'])->where('auth_url',$param['auth_url'])->find();
        if($auth) return response_json(false,400,'该路由已存在！');
        $res = AdminAuthM::update($param);
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //删除权限
    public function delAuth($id='')
    {
        $auth = $this->getAuth($id);
        $res = $auth->delete();
        if ($res){
            return response_json(true,200,'成功');
        }else{
            return response_json(false,400,'失败');
        }
    }

    //获取单个权限数据
    public function auth($id='')
    {
        $res = $this->getAuth($id);
        return response_json($res,200,'请求成功');
    }

    //获取所有权限
    public function auths($page=1,$limit=10,$all=false)
    {
        if ($all){
            $res['list'] = AdminAuthM::select();
        }else{
            $res['total_limit'] = AdminAuthM::count();
            $res['total_page'] = ceil($res['total_limit']/$limit);
            $res['current_page'] = $page;
            $res['list'] = AdminAuthM::page($page)->limit($limit)->select()->toArray();
        }

        /**

         * 将数据格式化成树形结构

         * @author Xuefen.Tong

         * @param array $items

         * @return array

         */

        function genTree9($items) {

            $tree = array(); //格式化好的树

            foreach ($items as $item)

                if (isset($items[$item['auth_parent']]))

                    $items[$item['auth_parent']]['son'][] = &$items[$item['id']];

                else

                    $tree[] = &$items[$item['id']];

            return $tree;
        }

        //无限极分类，实现具有父子关系的数据分类
        function category($arr,$pid=0,$level=0){
            //定义一个静态变量，存储一个空数组，用静态变量，是因为静态变量不会被销毁，会保存之前保留的值，普通变量在函数结束时，会死亡，生长周期函数开始到函数结束，再次调用重新开始生长
            //保存一个空数组
            static $list=array();
            //通过遍历查找是否属于顶级父类，pid=0为顶级父类，
            foreach($arr as $value){
                //进行判断如果pid=0，那么为顶级父类，放入定义的空数组里
                if($value['auth_parent']==$pid){
                    //添加空格进行分层
                    $value['level']=$level;
                    $list[]=$value;
                    //递归点，调用自身，把顶级父类的主键id作为父类进行再调用循环，空格+1
                    category($arr,$value['id'],$level+1);
                }
            }
            return $list;//递归出口
        }

        $shu = category($res['list']);
        foreach($shu as $k => $v) {
            echo str_repeat('--' , $v['level']) . $v['auth_name'] . '<br/>';
        }

        dump($shu);exit;
//        dump(genTree9($res['list']));

        if ($res['list']){
            return response_json($res,200,'请求成功');
        }else{
            return response_json(null,200,'无数据');
        }
    }

    //根据id获取权限数据
    protected function getAuth($id)
    {
        $auth = AdminAuthM::get($id);
        return $auth ? $auth : die(json_encode(['data'=>false,'code'=>400,'message'=>'该权限不存在']));
    }


}