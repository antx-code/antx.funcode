<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/8 10:32
 */

namespace app\seller\controller;

use think\Db;

class Merchants extends Base
{
    /**
     * 结算记录
     * @return mixed|\think\response\Json
     */
    public function settlement()
    {
        $download = input('download', 0);

        if ($download || $this->request->isAjax()) {
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $page       = input('page/d', 1);
            $pageSize   = input('pageSize/d', 15);
            if (!$end_time) {
                $end_time = time();
            }
            if (!$start_time) {
                $start_time = $end_time - 2592000;
            }

            $start_time = is_int($start_time) ? $start_time : strtotime($start_time);
            $end_time   = is_int($end_time) ? $end_time : strtotime($end_time);

            $where = [
                ['store_id', '=', $this->store_id],
                ['type', '=', 0],
                ['add_time', 'BETWEEN', [$start_time, $end_time]]
            ];

            if ($download) {
                $list  = Db::name('order_settle')->where($where)->field('order_sn,FROM_UNIXTIME(add_time),order_totals,integral,return_totals,give_integral,order_prom_amount,coupon_price,commis_rate,commis_totals,result_totals')->order('id DESC')->select();
                $title = ['订单编号', '结算日期', '订单金额', '收入积分', '退单金额', '送出积分', '订单优惠', '优惠券', '佣金比率 %', '平台抽成', '结算金额'];
                if (!$list) {
                    $list = [['', '', '', '', '', '', '', '', '', '', '',]];
                }
                $result = toExcel($title, $list, '结算记录' . date('Y-m-d'));
                if ($result !== true)
                    return json($result, 403);
            } else {
                $count = Db::name('order_settle')->where($where)->count();
                $list  = Db::name('order_settle')->where($where)->order('id DESC')->page($page, $pageSize)->select();
            }

            $data = [
                'list'       => $list,
                'start_time' => date('Y-m-d H:i:s', $start_time),
                'end_time'   => date('Y-m-d H:i:s', $end_time),
                'page'       => $page,
                'pageSize'   => $pageSize,
                'count'      => $count,
            ];
            return json($data);
        }

        return $this->fetch();
    }

    /**
     * 待结算订单
     * @return mixed|\think\response\Json
     */
    public function pendding()
    {
        $download = input('download', 0);
        if ($download || $this->request->isAjax()) {
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $page       = input('page/d', 1);
            $pageSize   = input('pageSize/d', 2);
            if (!$end_time) {
                $end_time = time();
            }
            if (!$start_time) {
                $start_time = $end_time - 2592000;
            }

            $start_time = is_int($start_time) ? $start_time : strtotime($start_time);
            $end_time   = is_int($end_time) ? $end_time : strtotime($end_time);

            $where = [
                ['store_id', '=', $this->store_id],
                ['pay_status', '=', 1,],
                ['is_delete', '=', 0,],
                ['pay_code', '<>', 'cod'],
                ['order_settle_id', '=', 0],
                ['add_time', 'BETWEEN', [$start_time, $end_time]]
            ];

            if ($download) {
                $order = Db::name('order')->where($where)->field('order_sn,FROM_UNIXTIME(add_time),FROM_UNIXTIME(shipping_time),goods_price,total_amount,integral_money,user_money,order_prom_amount,order_amount')->order('order_id DESC')->select();
                $title = ['订单编号', '下单时间', '发货时间', '商品总价', '订单总价', '积分抵扣', '余额抵扣', '优惠金额', '付款金额'];
                if (!$order) {
                    $order = [['', '', '', '', '', '', '', '', '']];
                }
                $result = toExcel($title, $order, '未结算订单' . date('Y-m-d'));
                if ($result !== true)
                    return json($result, 403);
            } else {
                $count = Db::name('order')->where($where)->count();
                $order = Db::name('order')->where($where)->order('order_id DESC')->page($page, $pageSize)->select();
            }

            $data = [
                'order'      => $order,
                'start_time' => date('Y-m-d H:i:s', $start_time),
                'end_time'   => date('Y-m-d H:i:s', $end_time),
                'page'       => $page,
                'pageSize'   => $pageSize,
                'count'      => $count,
            ];
            return json($data);
        }

        $commission_model = Db::name('shop_config')->where('code', 'commission_model')->value('value');

        return $this->fetch('', ['commission_model' => $commission_model]);
    }

    /**
     * 店铺账户
     * @return mixed
     */
    public function account()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'account_name' => 'require',
                'bank_name'    => 'require',
                'bank_card'    => 'require',
                'bank_mobile'  => 'require|mobile',
            ], [
                'account_name' => '户名必填',
                'bank_name'    => '开户银行必填',
                'bank_card'    => '银行卡号必填',
                'bank_mobile'  => '银行预留手机号码必填',
            ]);
            if (true !== $result) {
                $this->error($result);
            }

            Db::name('store')->where('store_id', $this->store_id)->field('account_name,bank_name,bank_card,bank_mobile')->update($data);
        }
        $store = Db::name('store')->where('store_id', $this->store_id)->find();
        return $this->fetch('', ['store' => $store]);
    }

    /**
     * 提现记录
     * @return mixed|\think\response\Json
     */
    public function withdrawals()
    {
        if ($this->request->isAjax()) {
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $count    = Db::name('account_log_store')->where(['account_type' => 0, 'store_id' => $this->store_id, 'type' => 1])->count();
            $list     = Db::name('account_log_store')->where(['account_type' => 0, 'store_id' => $this->store_id, 'type' => 1])->order('log_id DESC')->page($page, $pageSize)->select();
            $data     = [
                'list'     => $list,
                'page'     => $page,
                'pageSize' => $pageSize,
                'count'    => $count,
            ];
            return json($data);
        }

        return $this->fetch();
    }

    /**
     * 提现申请
     * @return mixed
     */
    public function withdraw_apply()
    {
        if ($this->request->isPost()) {
            $amount = input('post.amount');
            $remark = input('post.remark');
            if (!is_numeric($amount) || $amount <= 1) {
                $this->error('提现金额最少0.01元~');
            }
            $before_amount = Db::name('store')->where('store_id', $this->store_id)->value('seller_money');
            if ($amount > $before_amount) {
                $this->error('提现金额超出账户余额~');
            }
            $row = [
                'store_id'      => $this->store_id,
                'before_amount' => $before_amount,
                'amount'        => -1 * $amount,
                'after_amount'  => $before_amount - $amount,
                'add_time'      => time(),
                'desc'          => '提现',
                'type'          => 1,
                'remark'        => $remark
            ];
            Db::name('store')->where('store_id', $this->store_id)->update([
                'seller_money' => Db::raw('seller_money-' . $amount),
                'frozen_money' => Db::raw('frozen_money+' . $amount)
            ]);
            Db::name('account_log_store')->insert($row);
            $this->redirect('merchants/withdrawals');
        }

        $store = Db::name('store')->where('store_id', $this->store_id)->find();
        if (!$store['bank_card'] && !$store['bank_name']) {
            $this->error('请先设置提现账户', 'merchants/account');
        }
        return $this->fetch('', ['store' => $store]);
    }

    /**
     * 收支明细
     * @return mixed|\think\response\Json
     */
    public function account_log()
    {
        $download = input('download', 0);
        if ($download || $this->request->isAjax()) {
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $map      = ['store_id' => $this->store_id, 'is_paid' => 1];
            if ($download) {
                $list   = Db::name('account_log_store')->where($map)->field('FROM_UNIXTIME(change_time),CASE `type` WHEN 0 THEN "平台结算" WHEN 1 THEN "商家提现" WHEN 2 THEN "充值" END,before_amount,amount,after_amount')->order('log_id DESC')->select();
                $title  = ['账户变动时间', '账户变动原因', '当前余额', '变动金额', '变动国后余额'];
                $result = toExcel($title, $list, '结算记录' . date('Y-m-d'));
                if ($result !== true)
                    return json($result, 403);
            } else {
                $count = Db::name('account_log_store')->where($map)->count();
                $list  = Db::name('account_log_store')->where($map)->order('log_id DESC')->page($page, $pageSize)->select();
            }

            $data = [
                'list'     => $list,
                'page'     => $page,
                'pageSize' => $pageSize,
                'count'    => $count,
            ];
            return json($data);
        }
        return $this->fetch();
    }
}
