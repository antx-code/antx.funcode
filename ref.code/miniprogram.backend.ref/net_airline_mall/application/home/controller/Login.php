<?php

/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://heimishop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 * @controller: 登录权限核心控制器
 */

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Config;
use think\facade\Request;
use think\captcha\Captcha;
use think\Facade\Cache;
use think\facade\Validate;

class Login extends Common
{
    public function isLogin()
    {
        if (session('user')) {
            if ($this->request->isAjax()) {
                return json(['user_id' => session('user.user_id')], 402);
            }
            $this->redirect('/home/member/index');
        }
    }

    public function is_registered()
    {
        $act      = input("act");
        $userList = [];
        switch ($act) {
            case 'checkusername':
                $user_name = input('username');
                $userList  = Db::name("user")
                    ->where("user_name='{$user_name}'")
                    ->select();
                break;
            case 'check_phone':
                $mobile   = input('mobile_phone');
                $userList = Db::name("user")
                    ->where("mobile='{$mobile}'")
                    ->select();
                break;
            case 'check_email':
                $email    = input("email");
                $userList = Db::name("user")
                    ->where("email='{$email}'")
                    ->select();
                break;
            case 'phone_captcha':
                $captcha = new Captcha();
                if (!$captcha->check(input('captcha'))) {
                    return json(false);
                }
                break;
            case 'sendsms':
                $mobile = input("mobile");

                if (Validate::isMobile($mobile)) {
                    $count = Db::name("user")->where("mobile='{$mobile}'")->count('user_id');
                    if ($count > 0) {
                        ajaxmsg('手机号被占用', 0);
                    } else {
                        $smscode = mt_rand(1111, 9999);
                        sendsms($mobile, $smscode);
                    }
                } else {
                    ajaxmsg('手机号格式不对', 0);
                }
                break;
            case 'sendsmsLogin':
                $mobile = input("mobile");

                if (Validate::isMobile($mobile)) {
                    $count = Db::name("user")->where("mobile='{$mobile}'")->count('user_id');
                    if ($count == 0) {
                        ajaxmsg('手机号未注册', 0);
                    } else {
                        $smscode = mt_rand(1111, 9999);
                        sendsms($mobile, $smscode);
                    }
                } else {
                    ajaxmsg('手机号格式不对', 0);
                }
                break;
        }
        if (count($userList) > 0) {
            return json(false);
        } else {
            return json(true);
        }
    }

    public function index()
    {
        $this->isLogin();
        $captcha = new Captcha();

        if (Request::isAjax()) {
            $pageparm = input("post.");

            #开启验证码
            $login_captcha = Db::name("shop_config")->where("code='login_captcha'")->value('value');
            if ($login_captcha == 1) {
                if (!$captcha->check($pageparm['captcha'])) {
                    ajaxmsg('验证码不正确', 0);
                }
            }
            #邮箱登录
            if (filter_var($pageparm['username'], FILTER_VALIDATE_EMAIL)) {
                $user = Db::name("user")
                    ->where("email='{$pageparm['username']}'")
                    ->find();
                if (!$user) {
                    ajaxmsg('邮箱错误', 0);
                }
            }
            #手机号登录

            if (Validate::isMobile($pageparm['username'])) {
                $user = Db::name("user")
                    ->where("mobile='{$pageparm['username']}'")
                    ->find();
                if (!$user) {
                    ajaxmsg('手机号错误', 0);
                }
            } else {
                $user = Db::name("user")
                    ->where("user_name='{$pageparm['username']}'")
                    ->find();
                if (!$user) {
                    ajaxmsg('用户名错误', 0);
                }
            }
            if (password_verify($pageparm['password'], $user['password'])) {
                session('user', $user);
                //if (session('seller') && session('seller.user_id') != $user['user_id']) {
                //    session('seller', null);
                //}
                mergeCart();
                ajaxmsg('登录成功', 1);
            } else {
                ajaxmsg('密码错误', 0);
            }
        } else {
            return view();
        }
    }

    public function register()
    {
        $this->isLogin();
        $userData = [];
        if (Request::isPost()) {
            $pageparm = input("post.");

            #图片验证码验证
            $captcha     = new Captcha();
            $reg_captcha = Db::name("shop_config")->where("code='reg_captcha'")->value('value');
            if ($reg_captcha == 1) {
                if (!$captcha->check(input('captcha'))) {
                    $this->error("验证码不正确");
                }
            }
            $user = Db::name("user")
                ->where("user_name='{$pageparm['username']}'")
                ->select();
            if ($user) {
                $this->error("用户名已经存在,请重新输入");
            }
            $user = Db::name("user")
                ->where("mobile='{$pageparm['mobile_phone']}'")
                ->select();
            if ($user) {
                $this->error("手机已经存在,请重新输入");
            }

            #短信验证码验证
            $sms_signin = Db::name("shop_config")->where("code='sms_signin'")->value('value');
            if ($sms_signin == 1) {
                if ($pageparm['mobile_code']) {
                    $sid = Db::name("smscode")
                        ->where('mobile', '=', $pageparm['mobile_phone'])
                        ->where('smscode', '=', $pageparm['mobile_code'])
                        ->value("id");
                    if ($sid == 0) {
                        $this->error("短信验证码不正确,请重新输入");
                    } else {
                        Db::name("smscode")->where("id={$sid}")->delete();
                    }
                } else {
                    $this->error("短信验证码不正确,请重新输入");
                }
            }


            $email = input('email');
            if ($email) {
                $user = Db::name("user")
                    ->where("email='{$pageparm['email']}'")
                    ->select();
                if ($user) {
                    $this->error("邮箱已存在,请重新输入");
                }
                $userData['email'] = $pageparm['email'];
            }
            $userData['user_name']  = $pageparm['username'];
            $userData['mobile']     = $pageparm['mobile_phone'];
            $userData['password']   = password_hash($pageparm['password'], PASSWORD_BCRYPT);
            $userData['reg_time']   = time();
            $userData['last_login'] = time();
            $userData['last_ip']    = Request::ip();

            $user_id = Db::name("user")->insertGetId($userData);

            if ($user_id > 0) {
                session('user', Db::name("user")->where("user_id={$user_id}")->find());
                $this->redirect("/home/member/index");
            } else {
                $this->error("数据异常");
            }
        } else {
            $shop_reg_closed = Db::name("shop_config")->where("code='shop_reg_closed'")->value('value');
            if ($shop_reg_closed == 1) {
                $this->error("数据维护中，暂停注册！！！");
            } else {
                return view();
            }
        }
    }

    /**
     * 弹窗登录
     * @return \think\response\Json|\think\response\View
     */
    public function ajaxLogin()
    {

        if ($this->request->isPost()) {
            //生产环境密码错误10次锁定3分钟
            if (config('debug') !== true) {
                $cache = Cache::get('login_' . session_id());
                if ($cache === false) {
                    Cache::set('login_' . session_id(), 1, 180);
                } else if ($cache > 11) {
                    return json(['msg' => '您已连续输入错误10次,请3分钟后再试', 'token' => $this->request->token('token')]);
                } else {
                    Cache::inc('login_' . session_id());
                }
            }

            $data   = input('post.');
            $result = $this->validate($data, [
                'token'    => 'require|max:32|token:token',
                'username' => 'require',
                'password' => 'require',
            ], [
                'token'    => 'token失效,请刷新页面',
                'username' => '请输入用户名',
                'password' => '请输入密码',
            ]);
            if (true !== $result) {
                return json(['msg' => $result, 'token' => $this->request->token('token')], 403);
            }
            $user = Db::name('user')->where('email|mobile|user_name', '=', $data['username'])->find();
            if (!$user || !password_verify($data['password'], $user['password'])) return json(['msg' => '用户名或密码错误', 'token' => $this->request->token('token')], 403);
            session('user', $user);
            mergeCart();
            if (isset($data['remember'])) cookie('uid', $user['user_id']);
            return json();
        }

        $this->assign('refer', input('refer'));
        return view('ajax/login');
    }

    public function agreement()
    {
        return view();
    }

    public function smslogin()
    {
        if (Request::isAjax()) {
            $this->isLogin();
            $captcha  = new Captcha();
            $pageparm = input("post.");

            #开启验证码
            $login_captcha = Db::name("shop_config")->where("code='login_captcha'")->value('value');
            if ($login_captcha == 1) {
                if (!$captcha->check($pageparm['captcha'])) {
                    ajaxmsg('图片验证码不正确', 0);
                }
            }
            #手机号登录
            if (Validate::isMobile($pageparm['mobile'])) {
                $user = Db::name("user")
                    ->where("mobile='{$pageparm['mobile']}'")
                    ->find();
                if (!$user) {
                    ajaxmsg('手机号未注册', 0);
                }
            } else {
                ajaxmsg('手机号格式不对', 0);
            }
            #短信验证码验证
            if ($pageparm['mobile_code']) {
                $sid = Db::name("smscode")
                    ->where('mobile', '=', $pageparm['mobile'])
                    ->where('smscode', '=', $pageparm['mobile_code'])
                    ->value("id");
                if ($sid == 0) {
                    ajaxmsg('短信验证码不正确,请重新输入', 0);
                } else {
                    Db::name("smscode")->where("id={$sid}")->delete();
                    session('user', $user);
                    mergeCart();
                    ajaxmsg('登录成功', 1);
                }
            } else {
                ajaxmsg('短信验证码不正确,请重新输入', 0);
            }
        } else {
            return view();
        }
    }
}
