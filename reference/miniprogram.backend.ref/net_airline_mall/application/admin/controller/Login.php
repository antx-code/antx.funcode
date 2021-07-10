<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://uu235.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

namespace app\admin\controller;

use Firebase\JWT\JWT;
use think\Controller;
use think\Db;
use think\exception\PDOException;
use think\facade\Request;

class Login extends Controller
{
    public function login()
    {
        if (Request::isGet()) {
            //登录状态检测
            if (session('admin_user')) {
                $this->redirect('index/index');
            } else if (cookie('admin')) {
                try {
                    $str  = explode(',', selfDeCrypt(cookie('admin')));
                    $user = Db::name('user')->where(['user_name' => $str[0], 'lastlogintime' => $str[1]])->find();
                    if ($user) {
                        session('admin_user', $user);
                        $this->redirect('index/index');
                    } else {
                        cookie('admin', null);
                    }
                } catch (\Exception $e) {
                    trace('cookie非法尝试登录:' . $e->getMessage() . PHP_EOL . 'cookie:' . cookie('admin') . PHP_EOL . '用户IP:' . $this->request->ip());
                }
            }
            $this->redirect(config('ucenter') . 'index/login?module=yinong');
            //return view();
        } else {
            $username        = input("request.username", '');
            $password        = input("request.password", '');
            $map['username'] = ['eq', $username];
            $admin_user      = Db::name("admin_user")->where($map)->find();
            if (!$admin_user) {
                return ['status' => 1, 'msg' => '用户不存在或被禁用'];
            }
            if (password_verify($password, $admin_user['password'])) {
                session('admin_user', $admin_user);
                $this->adminlogAdd($admin_user, input('remember'));
                return ['status' => 0, 'msg' => '登录成功'];
            } else {
                return ['status' => 1, 'msg' => '密码错误'];
            }
        }
    }

    public function mutlogin()
    {
        $auth = input('auth');
        if (!$auth) {
            $this->redirect(config('ucenter') . 'index/login?module=yinong');
        }
        try {
            $data = (array)JWT::decode($auth, config('auth_key'), ['HS256'])->data;
            $user = Db::name('admin_user')->where('username', $data['username'])->find();
            if (!$user) {
                throw new \Exception('用户不存在');
            }
            session('admin_user', $user);
            $this->adminlogAdd($user, input('remember'));
        } catch (\Exception $e) {
            trace('自动登录失败：' . $e->getMessage());
            $this->redirect(config('ucenter') . 'index/login?module=yinong&msg=' . $e->getMessage());
        };

        $this->redirect('index/index');

        //$siteInfo = Db::name('shop_config')->where('shop_group', 'site_config')->column('value', 'code');
        //$this->assign('site', $siteInfo);
        //return view();
    }

    public function loginout()
    {
        session('admin_user_auth', null);
        session('admin_user', null);
        cookie('admin', null);
        return json(['status' => 0, 'msg' => '退出成功', 'url' => config('ucenter') . 'index/login?module=yinong']);
    }

    #添加日志
    public function adminlogAdd($admin_user, $remember)
    {
        $ip                         = Request::ip();
        $adminData                  = [];
        $date                       = date('Y-m-d H:i:s');
        $adminData['lastloginip']   = $ip;
        $adminData['lastlogintime'] = $date;
        $adminData['logintimes']    = $admin_user['logintimes'] + 1;
        Db::name('admin_user')->where("id={$admin_user['id']}")->update($adminData);
        if ($remember) {
            cookie('admin', selfEnCrypt($admin_user['username'] . ',' . $date), 864000);
        }

        $admin_logData               = [];
        $admin_logData['user_id']    = $admin_user['id'];
        $admin_logData['log_time']   = time();
        $admin_logData['log_info']   = '登录操作';
        $admin_logData['ip_address'] = $ip;
        Db::name('admin_log')->insertGetId($admin_logData);
    }

    public function createAccount()
    {
        $jwt = input('post.jwt');
        try {
            $data = (array)JWT::decode($jwt, config('auth_key'), ['HS256'])->data;
            $user = Db::name('admin_user')->where('username', $data['username'])->find();
            if ($user) {
                exit('已存在');
            }
            $data['name'] = $data['realname'];
            unset($data['realname']);
            $data['logintimes']    = 0;
            $data['admin_avatar']  = '';
            $data['lastlogintime'] = 0;
            $data['create_time']   = time();
            Db::name('admin_user')->insert($data);
        } catch (\Exception $e) {
            trace('创建用户失败: ' . $e->getMessage());
            return json(['msg' => $e->getMessage()]);
        }
    }
}
