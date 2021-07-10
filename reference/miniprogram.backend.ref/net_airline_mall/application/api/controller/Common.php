<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/4 14:17
 */

namespace app\api\controller;

use app\home\model\Order;
use think\Controller;
use think\facade\Env;
use think\Db;
use think\facade\Log;
use EasyWeChat\Factory;
use app\home\model\Goods;
use app\common\model\Supplier;
use app\home\model\Store;

use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;

class Common extends Controller
{
    /**
     * 生成二维码
     * @param string content
     */
    public function qrcode()
    {
        $str     = input('param.str', '');
        $no_logo = input('no_logo');
        header('Content-Type: image/png');
        if (!trim($str)) exit(file_get_contents(Env::get('root_path') . '/public/static/images/qrcode_no_content.png'));
        $qrcode = new BaconQrCodeGenerator;
        if ($no_logo) {
            exit($qrcode->format('png')->size(400)->backgroundColor(240, 255, 255)->margin(2)->generate($str));
        }

        $logo = 'public/static/images/qrcode_bg.png';
        exit($qrcode->format('png')->size(400)->backgroundColor(240, 255, 255)->margin(2)->merge($logo)->generate($str));
    }

    public function qrcodePay()
    {
        $str = input('param.str', '');
        header('Content-Type: image/png');
        $qrcode = new BaconQrCodeGenerator;
        exit($qrcode->format('png')->size(400)->backgroundColor(240, 255, 255)->margin(2)->generate($str));
    }

    public function shopTask()
    {
        $token = input('token');
        if ($token != config('auth_key')) {
            return json('', 404);
        }
        set_time_limit(0);
        $receiveTask = $this->autoReceive();
        echo $receiveTask['msg'];
        $settlementTask = $this->settlement();
        echo $settlementTask['msg'];
    }

    /**
     * 定时任务 自动确认收货
     */
    protected function autoReceive()
    {
        $open_delivery_time = Db::name('shop_config')->where('code', 'open_delivery_time')->cache(true)->value('value');
        $time               = time();
        $date               = date('Y-m-d H:i:s');
        $start              = strtotime('-' . $open_delivery_time . 'day', strtotime(date('Y-m-d', $time)));
        $map                = [
            ['is_delete', '=', 0],
            ['order_status', '=', 1],
            ['shipping_status', '=', 2],
            ['shipping_time', '<', $start]
        ];
        $model              = new \app\home\model\Order;
        try {
            Db::startTrans();
            $orders = $model->where($map)->select();
            $ids    = [];
            foreach ($orders as $index => $order) {
                array_push($ids, $order['order_id']);
                orderLog($order, '系统自动确认收货');
            }

            $model->whereIn('order_id', $ids)->update(['order_status' => 2, 'confirm_time' => $time]);
            Db::name("delivery_order")->whereIn('order_id', $ids)->update(['status' => 3]);

            Db::commit();
            return ['res' => 0, 'msg' => $date . '自动确认收货完成：' . count($ids) . '条记录' . implode(',', $ids) . PHP_EOL];
        } catch (\Exception $e) {
            Db::rollback();
            return ['res' => 1, 'msg' => $date . '自动收货::错误' . $e->getMessage() . ' File:' . $e->getFile() . ' Line:' . $e->getLine() . PHP_EOL];
        }
    }

    /**
     * 定时任务 结算
     */
    protected function settlement()
    {
        $withdraw_day = Db::name('shop_config')->where('code', 'withdraw_day')->cache(true)->value('value');
        $date         = date('Y-m-d H:i:s') . ' ';
        $time         = time();
        $start        = strtotime('-' . $withdraw_day . 'day', strtotime(date('Y-m-d', $time)));

        $map = [
            ['pay_status', '=', 1],
            ['is_delete', '=', 0],
            ['shipping_status', '=', 2],
            ['order_status', 'IN', [2, 4]],
            ['order_settle_id', '=', 0],
            ['confirm_time', 'BETWEEN', [$start, $time]]
        ];

        $orderModel        = new \app\home\model\Order;
        $settleModel       = new \app\common\model\OrderSettle;
        $accountStoreModel = new \app\common\model\AccountLogStore;

        $orders = $orderModel->fixOrderGoods($orderModel->where($map)->select()->toArray());
        if (!$orders) {
            return ['res' => 0, 'msg' => $date . '结算 0 条记录' . PHP_EOL];
        }
        try {
            $supplier_count = $store_count = 0;
            $integral_scale = Db::name('shop_config')->where('code', 'integral_scale')->value('value') * 0.01;

            Db::startTrans();
            foreach ($orders as $index => $order) {
                /**
                 * $supplier_settlement 供应商商品结算金额
                 * $give_integral_amount 赠送积分金额
                 * $settlement订单应结金额 = 应付金额 + 余额抵扣 + 运费 + 积分抵扣 - 赠送积分 - 供应商应结金额
                 * $commission平台抽成 = 订单应结金额 * 店铺结算比率
                 * $total订单实际结算金额
                 */
                $give_integral        = array_sum(array_column($order['order_goods'], 'give_integral'));
                $give_integral_amount = round($give_integral * $integral_scale, 2);

                if ($order['store_id'] == 0) {
                    //自营店铺，只需给供应商结算
                    if (array_sum(array_column($order['order_goods'], 'sr_id')) > 0) {
                        foreach ($order['order_goods'] as $idx => $rec) {
                            if ($rec['sr_id'] == 0) continue;//非供应商商品跳过结算
                            $this->supplierSettlement($settleModel, $accountStoreModel, $order, $rec, $give_integral_amount);
                            $supplier_count++;
                        }
                    }
                    continue;
                }
                $store               = Store::where('store_id', $order['store_id'])->field('seller_money,commission_rate')->find();
                $commission_rate     = $store['commission_rate'];
                $supplier_settlement = 0;

                if (array_sum(array_column($order['order_goods'], 'sr_id'))) {
                    foreach ($order['order_goods'] as $idx => $goods) {
                        if ($goods['sr_id'] == 0) continue;
                        $supplier_settlement += $this->supplierSettlement($settleModel, $accountStoreModel, $order, $goods, $give_integral_amount);
                        $supplier_count++;
                    }
                }

                $settlement = round($order['order_amount'] + $order['user_money'] + $order['shipping_price'] + $order['integral_money'] - $give_integral_amount - $supplier_settlement, 2);
                if ($settlement < 0) {
                    throw new \Exception('应结金额异常order_id:' . $order['order_id']);
                }
                $commission = round($settlement * $commission_rate, 2);
                $total      = $settlement - $commission;

                $row = [
                    'store_id'          => $order['store_id'],
                    'sr_id'             => 0,
                    'order_id'          => $order['order_id'],
                    'order_sn'          => $order['order_sn'],
                    'order_totals'      => $order['total_amount'],
                    'shipping_totals'   => $order['shipping_price'],
                    'return_totals'     => 0,
                    'give_integral'     => $give_integral_amount,
                    'discount'          => $order['discount'],
                    'integral'          => $order['integral_money'],
                    'order_prom_amount' => $order['order_prom_amount'],
                    'coupon_price'      => $order['coupon_price'],
                    'distribut'         => 0,//分享人利润
                    'commis_rate'       => $commission_rate,
                    'commis_totals'     => $commission,
                    'result_totals'     => $total,
                    'add_time'          => $time,
                    'type'              => 0
                ];
                //结算记录
                $id = $settleModel->insertGetId($row);
                //订单状态修改为已结算
                Order::where('order_id', $order['order_id'])->update(['order_settle_id' => $id]);
                //资金实际变动日志表
                $res = $accountStoreModel->settlement($total, 0, $order['store_id'], $order['order_sn'], '商家结算');
                if ($res !== true) {
                    throw new \Exception($res);
                };
                $store_count++;
            }

            Db::commit();
            return ['res' => 0, 'msg' => $date . ' 结算完成，供应商结算: ' . $supplier_count . ' 条记录，订单结算: ' . $store_count . ' 条记录' . PHP_EOL];
        } catch (\Exception $e) {
            Db::rollback();
            return ['res' => 1, 'msg' => $date . '结算::错误 ' . $e->getMessage() . ' File:' . $e->getFile() . ' Line:' . $e->getLine() . PHP_EOL];
        }
    }

    protected function supplierSettlement($settleModel, $accountStoreModel, $order, $rec, $give_integral_amount)
    {
        $supplier_rate = Supplier::where('id', $rec['sr_id'])->value('commission_rate');
        /**
         * $cach    应结金额
         * $commission  平台抽成
         * $amount  实际结算金额
         */
        $cach = round($rec['goods_num'] * $rec['cost_price'], 2);
        if ($cach == 0) {
            return $cach;//兼容 cost_price = 0的状况
        }
        $commission = $cach * $supplier_rate;
        $amount     = $cach - $commission;
        $row        = [
            'store_id'          => $order['store_id'],
            'sr_id'             => $rec['sr_id'],
            'order_id'          => $order['order_id'],
            'order_sn'          => $order['order_sn'],
            'order_totals'      => $order['total_amount'],
            'shipping_totals'   => $order['shipping_price'],
            'give_integral'     => $give_integral_amount,
            'discount'          => $order['discount'],
            'integral'          => $order['integral_money'],
            'order_prom_amount' => $order['order_prom_amount'],
            'coupon_price'      => $order['coupon_price'],
            'commis_rate'       => $supplier_rate,
            'commis_totals'     => $commission,
            'result_totals'     => $amount,
            'add_time'          => time(),
            'type'              => 1
        ];
        //结算记录
        $id = $settleModel->insertGetId($row);
        //订单状态修改为已结算
        Order::where('order_id', $order['order_id'])->update(['order_settle_id' => $id]);
        //资金实际变动日志表
        $res = $accountStoreModel->settlement($amount, 1, $rec['sr_id'], $order['order_sn'], '供应商结算');
        if ($res !== true) {
            throw new \Exception($res);
        };
        return $cach;
    }

    public function miniprogramQrcode($goods_id = [])
    {
        $config   = [
            'app_id'        => config('miniwxpayConfig')['appid'],
            'secret'        => config('miniwxpayConfig')['secret'],
            'response_type' => 'array',
        ];
        $path     = input('path');
        $name     = input('name');
        $app      = Factory::miniProgram($config);
        $savePath = Env::get('root_path') . 'uploads';
        //$path     = '/pages/venue/index?venue_id=5';//临时页面生成小程序二维码
        $response = $app->app_code->get($path, $optional = ['width' => 400]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $name = $response->saveAs($savePath, $name);
            exit($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/uploads/' . $name);
        }
        exit;

        $map = 'miniprograme_qrcode IS NOT NULL';
        if ($goods_id) {
            $map = [['goods_id', 'IN', $goods_id]];
        }
        $goodsModel = new Goods;
        $ids        = $goodsModel->where($map)->column('goods_name', 'goods_id');
        set_time_limit(0);
        $name  = '';
        $count = 0;

        foreach ($ids as $goods_id => $goods_name) {
            $path     = 'pages/goods/index?goods_id=' . $goods_id;
            $response = $app->app_code->get($path, $optional = ['width' => 400]);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $goods_name = str_replace(['/', '*', ':', '?', '='], ['', '-', 'x', '-', '|', '-'], $goods_name);
                $name       = $response->saveAs($savePath, $goods_name);
                $goodsModel->where('goods_id', $goods_id)->update(['miniprograme_qrcode' => '/uploads/' . $name]);
                $count++;
            }
        }
        if ($goods_id) {
            return '/uploads/' . $name;
        } else {
            return json(['count' => $count]);
        }
    }

    public function tempApi()
    {
        exit();
        $goods_ids = Db::name('products')->group('goods_id')->column('goods_id');
        $count     = 0;
        foreach ($goods_ids as $index => $goods_id) {
            $ids = Db::name('products')->where('goods_id', $goods_id)->column('product_id');
            Db::name('goods')->where('goods_id', $goods_id)->update(['product_id' => implode(',', $ids)]);
            $count++;
        }
        dump(count($goods_ids));
        dump($count);
    }

    public function exportOrder()
    {
        exit();
        $title = Db::name('order')->getTableFields();
        $list  = Db::name('order')->order('order_id DESC')->select();
        foreach ($list as $index => &$item) {
            $item['add_time'] > 0 && $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['pay_time'] > 0 && $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
            $item['shipping_time'] > 0 && $item['shipping_time'] = date('Y-m-d H:i:s', $item['shipping_time']);
            $item['write_off'] > 0 && $item['write_off'] = date('Y-m-d H:i:s', $item['write_off']);
        }
        toExcel($title, $list, 'order-' . date('Y-m-d'));
    }
}
