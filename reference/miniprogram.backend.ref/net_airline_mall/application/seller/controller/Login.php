<?php

/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/4 15:51
 */

namespace app\seller\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;

class Login extends Controller
{
    //GET登录页面，POST登录验证
    public function index()
    {
        if ($this->request->isPost()) {
            //生产环境密码错误10次锁定3分钟
            if (config('debug') !== true) {
                $cache = Cache::get('seller_login_' . session_id());
                if ($cache === false) {
                    Cache::set('seller_login_' . session_id(), 1, 180);
                } else if ($cache > 11) {
                    return json(['msg' => '您已连续输入错误10次,请3分钟后再试', 'token' => $this->request->token('token')]);
                } else {
                    Cache::inc('seller_login_' . session_id());
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
            if (true !== $result) return json(['msg' => $result, 'token' => $this->request->token('token')], 401);

            $seller = Db::name('seller')->where('username', $data['username'])->find();
            if (!$seller) {
                return json(['msg' => '用户名或密码错误', 'token' => $this->request->token('token')], 402);
            }

            $now = time();
            if ($seller['lock_until'] > $now) {
                return json(['msg' => '您已输入错误次数过多，请10分钟之后再试'], 401);
            }

            if (!password_verify($data['password'], $seller['password'])) {
                //密码错误10次锁定账号10分钟
                $count = session($data['username']);
                $count = $count ? $count + 1 : 1;
                session($data['username'], $count);
                if ($count >= 10) {
                    Db::name('seller')->where('username', $data['username'])->update(['lock_until' => $now + 600]);
                }
                return json(['msg' => '用户名或密码错误', 'token' => $this->request->token('token')], 402);
            }

            $store = Db::name('store')->where(['store_id' => $seller['store_id'], 'status' => 1])->find();
            if (!$store) {
                return json(['msg' => '店铺状态异常，请联系客服'], 402);
            }

            Db::name('seller')->where('id', $seller['id'])->update(['last_login_time' => time()]);
            session($data['username'], null);//登录成功，清除错误提示
            $seller['seller_quicklink'] = $seller['seller_quicklink'] ? json_decode($seller['seller_quicklink'], true) : [];
            if ($seller['controller'] != 'all') {
                $seller['controller'] = explode(',', $seller['controller']);
            }
            if ($seller['action'] != 'all') {
                $seller['action'] = json_decode($seller['action'], true);
            }

            session('seller', $seller);

            if (isset($data['remeber']) && $data['remeber'] > 0) {
                cookie('seller', $seller['username']);
                cookie('seller_sign', hash('sha256', $seller['password']));
            }
            return json();
        }

        if (true === check_login_state()) {
            $store = Db::name('store')->where(['store_id' => session('seller.store_id'), 'status' => 1])->find();
            if (!$store) {
                $this->redirect('home/merchant/index');
            }
            $this->redirect('index/index');
        }

        //前端登录用户,直接跳转商户后台
        if (session('user')) {
            $seller = Db::name('seller')->where('user_id', session('user.user_id'))->find();
            if ($seller) {
                $store = Db::name('store')->where(['store_id' => $seller['store_id'], 'status' => 1])->find();
                if (!$store) {
                    $this->redirect('home/merchant/index');
                }

                $seller['seller_quicklink'] = $seller['seller_quicklink'] ? json_decode($seller['seller_quicklink'], true) : [];
                if ($seller['controller'] != 'all') {
                    $seller['controller'] = explode(',', $seller['controller']);
                }
                if ($seller['action'] != 'all') {
                    $seller['action'] = json_decode($seller['action'], true);
                }

                session('seller', $seller);
                $this->redirect('index/index');
            }
        }

        $this->assign('logo', Db::name('shop_config')->where('code', 'seller_login_logo')->cache(true)->value('value'));
        return $this->fetch();
    }

    /**
     * 找回密码
     */
    public function forget()
    {
        //todo
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('seller', null);
        session('user', null);
        cookie('seller', null);
        cookie('uid', null);
        $this->redirect('login/index');
    }
}
