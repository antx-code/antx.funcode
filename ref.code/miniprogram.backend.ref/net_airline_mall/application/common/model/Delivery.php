<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/5/5 11:30
 */

namespace app\common\model;

use think\Model;
use think\Db;

class Delivery extends Model
{
    protected $name = 'delivery_order';
    protected $pk = 'delivery_id';

    public function getDeliveryGoods($delivery_id)
    {
        return Db::name('delivery_goods')->where('delivery_id', $delivery_id)->select();
    }

    public function waitSend($order_id)
    {
        $delivery = $this->where('order_id', $order_id)->select();
        $waitSend = false;
        if ($delivery) {
            $ids       = $delivery->column('delivery_id');
            $goods_num = Db::name('order_goods')->where('order_id', $order_id)->sum('goods_num');
            $num       = Db::name('delivery_goods')->whereIn('delivery_id', $ids)->sum('send_num');
            trace('goods_num:' . $goods_num . '===num:' . $num);
            $status  = $delivery->column('status');
            $counter = array_count_values($status);
            //商品数量已全部生成发货单,并且还有发货单在出库中
            if ($goods_num == $num && $counter[0] > 0) {
                $waitSend = true;
            }
        }

        return $waitSend;
    }

    public function send($order_id, $delivery_id, $shipping_id, $shipping_no)
    {
        $orderGoodsNum  = Db::name('order_goods')->where('order_id', $order_id)->sum('goods_num');
        $delivery_ids   = $this->where(['order_id' => $order_id, 'status' => 1])->column('delivery_id');
        $deliveryNum    = Db::name('delivery_goods')->whereIn('delivery_id', $delivery_ids)->sum('send_num');//已发货数量
        $delivery_goods = $this->getDeliveryGoods($delivery_id);

        $deliveryNum   += array_sum(array_column($delivery_goods, 'send_num'));//加已发货数量,本次发货数量
        $shipping_name = Db::name('shipping')->where('shipping_id', $shipping_id)->value('shipping_name');
        $update        = [
            'shipping_name' => $shipping_name,
            'shipping_id'   => $shipping_id,
            'shipping_time' => time(),
        ];
        if ($orderGoodsNum == $deliveryNum) {
            $update['shipping_status'] = 2;//已发货
        } else {
            $update  ['shipping_status'] = 3;//部分发货
        }
        Db::name('order')->where('order_id', $order_id)->update($update);

        Db::name('order_goods')->whereIn('rec_id', array_column($delivery_goods, 'order_goods_recid'))->update(['is_send' => 1]);
        return $this->where('delivery_id', $delivery_id)->update(['shipping_id' => $shipping_id, 'shipping_no' => $shipping_no, 'status' => 1, 'update_time' => time()]);
    }

    public function del($id)
    {
        $delivery = $this->get($id);
        if ($delivery['status'] != 0) {
            return '发货单状态错误,无法删除';
        }
        $count = $this->where('order_id', $delivery['order_id'])->where('delivery_id', '<>', $id)->count();
        if ($count == 0) {
            Db::name('order')->where('order_id', $delivery['order_id'])->update(['shipping_status' => 0]);
        }

        $delivery_goods = $this->getDeliveryGoods($id);
        foreach ($delivery_goods as $index => $goods) {
            $rec  = Db::name('order_goods')->where('rec_id', $goods['order_goods_recid'])->field('goods_num,send_number,is_send')->find();
            $data = ['send_number' => Db::raw('send_number-' . $goods['send_num'])];
            if ($rec['goods_num'] == $rec['send_number'] && $rec['is_send'] == 1) {
                $data['is_send'] = 0;
            }
            Db::name('order_goods')->where('rec_id', $goods['order_goods_recid'])->update($data);
        }
        Db::name('delivery_goods')->where('delivery_id', $id)->delete();
        $this->where('delivery_id', $id)->delete();
        return $delivery['order_id'];
    }
}
