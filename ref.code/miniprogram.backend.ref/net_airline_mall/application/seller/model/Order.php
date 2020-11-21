<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/29 15:58
 */

namespace app\seller\model;

use think\Model;
use think\Db;

class Order extends Model
{
    protected $pk = 'order_id';

    protected function base($query)
    {
        $query->where(['store_id' => session('seller')['store_id']]);
    }

    public function getOrderGoods($order_id)
    {
        $goods = Db::name('order_goods a')->where('order_id', $order_id)->select();
        foreach ($goods as $index => $good) {
            if ($good['product_id']) {
                $goods[$index]['goods_number'] = Db::name('products')->where(['goods_id' => $good['product_id']])->value('product_number');
            } else {
                $goods[$index]['goods_number'] = Db::name('goods')->where('goods_id', $good['goods_id'])->value('goods_number');
            }
        }
        return $goods;
    }

    public function getOrderLog($order_id)
    {
        return Db::name('order_log')->where('order_id', $order_id)->order('action_id DESC')->select();
    }

    public function getOrderBtn($order)
    {
        $os  = $order['order_status'];//订单状态
        $ps  = $order['pay_status'];//支付状态
        $ss  = $order['shipping_status'];//发货状态
        $btn = [];
        //dump($order);die;
        if ($order['pay_code'] == 'cod') {
            if ($os == 0 && $ss == 0) {
                $btn['confirm'] = '确认';
            } elseif ($os == 1 && $ss == 0) {
                //$btn['cancel']   = '取消确认';
                $btn['delivery'] = '生成发货单';
            } elseif ($os == 1 && $ss == 1) {
                $btn['send'] = '去发货';
            }
        } else {

            if ($os == 0) {
                $btn['confirm'] = '确认';
            } elseif ($os == 1 && $ss == 0) {
                //$btn['cancel'] = '取消确认';
            }

            if ($order['self_pickup_time']) {
                $btn['self_pickup'] = '到店自提订单';
            } elseif ($os == 1 && $ps == 1 && $ss != 2) {
                if ((new Delivery)->waitSend($order['order_id'])) {
                    $btn['send'] = '去发货';
                } else {
                    $btn['delivery'] = '生成发货单';
                }
            } elseif ($os == 1 && $ps == 1 && $ss == 2) {
                //$btn['after_service'] = '售后';
            }
            //if ($os < 2 && $ps == 0) {
            //    $btn['cancel_order'] = '取消订单(退款)';
            //}
        }
        return $btn;
    }
}
