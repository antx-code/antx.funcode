<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/17 14:06
 */

namespace app\home\controller;

use think\Controller;
use think\Db;
use Payment\Pay;
use Payment\Notify;

class Payment extends Controller
{
    protected $pay_code;
    protected $pay_method;
    protected $pay_name;
    protected $pay;
    protected $conf;

    public function initialize()
    {
        //$data = [
        //    'appid'      => 'wxd61e5ed1a5c7b8b5',
        //    'mchid'      => '1488194562',
        //    'key'        => '0598e9a56c45cd5243d27394cb21be3b',
        //    'secret'     => 'cdcb1b56897754fb0459ba962bd01bba',
        //    'return_url' => 'https://www.enongmall.com/home/payment/return/pay_code/weixin.html',
        //    'notify_url' => 'https://www.enongmall.com/home/payment/notify/pay_code/weixin.html',
        //];

        //$data = [
        //    'appid'  => 'wxd61e5ed1a5c7b8b5',
        //    'mchid'  => '1488194562',
        //    'key'    => '0598e9a56c45cd5243d27394cb21be3b',
        //    'secret' => 'cdcb1b56897754fb0459ba962bd01bba',
        //];
        //
        //Db::name('payment')->where('pay_code="weixin"')->update(['pay_config' => json_encode($data, JSON_UNESCAPED_SLASHES)]);
        //die;

        //FIXME 存入pay_config时注意json_encode不能转义,加参数 JSON_UNESCAPED_SLASHES
        //Db::name('payment')->where('pay_code', 'alipay')->update(['pay_config' => json_encode($conf, JSON_UNESCAPED_SLASHES)]);
        $code             = ['weixin' => 'WxQrpay', 'alipay' => 'AliWeb'];
        $name             = ['weixin' => '微信支付', 'alipay' => '支付宝'];
        $this->pay_code   = input('pay_code');
        $this->pay_method = $code[$this->pay_code];
        $this->pay_name   = $name[$this->pay_code];

        $config = json_decode(Db::name('payment')->where('pay_code', $this->pay_code)->value('pay_config'), true);
        if (!$config) $this->error('支付参数错误');
        $this->conf = $config;
    }

    #PC支付
    public function pay()
    {
        $data           = input('post.');
        $orderModel     = new \app\home\model\Order;
        $data['amount'] = $orderModel->getAmountByOrderSn($data['sn']);
        if ($data['user_money'] > 0) {
            $user = Db::name('user')->where('user_id', session('user.user_id'))->find();
            if (!$user || $user['user_money'] < $data['user_money']) {
                if ($this->request->isAjax()) {
                    return json('余额不足', 403);
                } else {
                    $this->error('余额不足');
                }
            }
            $use_money      = min($data['amount'], $data['user_money']);
            $data['amount'] -= $use_money;
        } else {
            $use_money = 0;
        }

        $data['amount'] = $orderModel->useMoney($data['sn'], $use_money);
        error_reporting(0);
        if (strpos($this->pay_code, 'ali') === false) {
            if ($data['amount'] == 0) {
                return json('支付成功', 206);
            }
            //微信扫码支付
            $payData = [
                'subject'      => '商品订单',
                'out_trade_no' => $data['sn'] . mt_rand(1000, 9999),
                'amount'       => $data['amount'],
                'notify_url'   => $this->conf['notify_url'],
            ];

            $res = Pay::run($this->pay_method, $this->conf, $payData);
            if ($res['ret'] == 0) {
                return json(['result' => $res['data']]);
            } else {
                return json($res['msg'], 403);
            }
        } else {
            if ($data['amount'] == 0) {
                $data = [
                    'order_no'     => $data['sn'],
                    'total_amount' => $data['amount'],
                    'pay_name'     => '余额支付'
                ];
                $this->redirect('cart/payResult', $data);
            }
            $payData = [
                'subject'      => '商品订单',
                'out_trade_no' => $data['sn'] . mt_rand(1000, 9999),
                'amount'       => $data['amount'],
                'return_url'   => $this->conf['return_url'],
                'notify_url'   => $this->conf['notify_url'],
            ];
            return Pay::run($this->pay_method, $this->conf, $payData);
        }
    }

    public function notify()
    {
        if (strpos($this->pay_code, 'ali') === false) {
            trace(date('Y-m-d H:i:s') . '==支付回调==' . var_export($this->request->put(), true), 'pay_notify');
            Notify::run('NotifyWx', $this->conf, 'updatePayStatus');
        } else {
            trace(date('Y-m-d H:i:s') . '==支付回调==' . var_export($_POST, true), 'pay_notify');
            Notify::run('NotifyAli', $this->conf, 'updatePayStatus');
        }
    }

    public function return()
    {
        $param = input('get.');
        trace(date('Y-m-d H:i:s') . '==支付跳转==' . var_export($param, true), 'pay_return');
        //if (true !== $this->pay->checkSign($param)) {
        //    $this->redirect('index/index');//验签失败,非法请求
        //}

        $data = [
            'order_no'     => substr($param['out_trade_no'], 0, 18),
            'total_amount' => $param['total_amount'],
            'pay_name'     => $this->pay_name
        ];
        $this->redirect('cart/payResult', $data);
    }

    public function ajaxQuery()
    {
        $order_sn = input('post.order_sn');
        $order    = Db::name('order')->where('order_sn', $order_sn)->find();
        if (!$order) {
            $order = Db::name('order')->where('master_order_sn', $order_sn)->find();
        }
        return json(['pay_status' => $order['pay_status']]);
    }

    public function query($order_sn = null)
    {
        if (is_null($order_sn)) $order_sn = input('order_sn');

        $res = $this->pay->query($order_sn);
        if ($res['ret'] == 0) {
            return json($res['trade_state_desc']);
        } else {
            return json($res['msg'], 403);
        }
    }

    public function queryGiftCard()
    {
        $order_sn = input('post.order_sn');
        $order    = Db::name('user_buycard')->where('order_sn', $order_sn)->find();
        return json(['pay_status' => $order['status']]);
    }
}
