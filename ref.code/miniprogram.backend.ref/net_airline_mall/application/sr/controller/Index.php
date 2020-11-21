<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/9 15:42
 */

namespace app\sr\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use app\common\model\Supplier;

class Index extends Controller
{
    public function signin()
    {
        $model = new Supplier;
        if ($this->request->isPost()) {
            if (config('debug') !== true) {
                $cache = Cache::get('sr_login_' . session_id());
                if ($cache === false) {
                    Cache::set('sr_login_' . session_id(), 1, 180);
                } else if ($cache > 11) {
                    return json(['msg' => '您已连续输入错误10次,请3分钟后再试', 'token' => $this->request->token('token')]);
                } else {
                    Cache::inc('sr_login_' . session_id());
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

            $supplier = $model->where('username', $data['username'])->find();
            if (!$supplier) {
                return json(['msg' => '用户名或密码错误', 'token' => $this->request->token('token')], 402);
            }

            $now = time();
            if ($supplier->lock_until > $now) {
                return json(['msg' => '您已输入错误次数过多，请10分钟之后再试'], 401);
            }

            if (!password_verify($data['password'], $supplier->password)) {
                //密码错误10次锁定账号10分钟
                $count = session($data['username']);
                $count = $count ? $count + 1 : 1;
                session($data['username'], $count);
                if ($count >= 10) {
                    $model->where('username', $data['username'])->update(['lock_until' => $now + 600]);
                }
                return json(['msg' => '用户名或密码错误', 'token' => $this->request->token('token')], 402);
            }

            if ($supplier->enabled > 0) {
                return json(['msg' => '您的账号暂时无法登录，请联系客服'], 402);
            }

            $model->where('id', $supplier->id)->update(['last_login_time' => time()]);
            session($data['username'], null);//登录成功，清除错误提示

            session('sr', $supplier);

            if (isset($data['remeber']) && $data['remeber'] > 0) {
                cookie('sr_user', $supplier['username']);
                cookie('sr_sign', hash('sha256', $supplier->password));
            }
            return json();
        }

        if (session('?sr') || cookie('?sr_user')) {
            $username = session('?sr') ? session('sr.username') : cookie('sr_user');
            $supplier = $model->where(['username' => $username, 'enabled' => 0])->find();
            if ($supplier && hash('sha256', $supplier->password) == cookie('sr_sign')) {
                session('sr', $supplier);
                $this->redirect('index/index');
            } else {
                session('sr', null);
                cookie('sr_user', null);
                cookie('sr_sign', null);
            }
        }

        $this->assign('logo', Db::name('shop_config')->where('code', 'seller_login_logo')->cache(true)->value('value'));
        return view();
    }

    public function signout()
    {
        session('sr', null);
        cookie('sr_user', null);
        cookie('sr_sign', null);
        $this->redirect('signin');
    }

    public function index()
    {
        $goods_count = \app\sr\model\Goods::where('sr_id', session('sr.id'))->where('is_on_sale=1 AND review_status=2')->count();
        $wait_send       = Db::name('order_goods a')
            ->join('order b', 'a.order_id=b.order_id')
            ->where('a.sr_id', session('id'))
            ->where('b.pay_status=1 AND b.order_status=1 AND b.shipping_status<>2')
            ->count();

        $this->assign('goods_count', $goods_count);
        $this->assign('wait_send', $wait_send);
        $this->assign('supplier', session('sr'));
        return view();
    }

    public function _empty()
    {
        if ($this->request->isAjax()) {
            return json('您请求的资源不存在~', 403);
        } else {
            $msg = input('?msg') ? input('msg') : '此页面待开发~~~';
            $this->error($msg);
        }
    }
}