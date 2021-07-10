<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/10 10:34
 */

namespace app\sr\controller;

use app\common\model\Delivery as DeliveryModel;
use app\home\model\Order as OrderModel;
use think\Controller;
use think\Db;

class Order extends Controller
{
    public function index()
    {
        $flow = config('order_flow');
        if ($this->request->isAjax()) {
            $field      = input('field', 'order_id');
            $sort       = input('sort', 'desc');
            $start_date = input('start_date/d');
            $end_date   = input('end_date/d');
            $keywords   = input('keywords');
            $flow_index = input('flow_index/d');
            $consignee  = input('consignee');
            $order_sn   = input('order_sn');
            $page       = input('page/d', 1);
            $pageSize   = input('pageSize/d', 15);

            $goodsmap = [
                ['sr_id', '=', session('sr.id')]
            ];

            if ($keywords) {
                $goodsmap[] = ['goods_name|goods_sn', 'LIKE', '%' . $keywords . '%'];
            }

            $map = [];
            if ($flow_index > 0) {
                $map = $this->fixorderwhere($flow_index);
            }

            $order_ids = Db::name('order_goods')->where($goodsmap)->column('order_id');
            $map[]     = ['order_id', 'IN', $order_ids];

            if ($consignee) {
                $map[] = ['consignee', 'LIKE', '%' . $consignee . '%'];
            }
            if ($order_sn) {
                $map[] = ['order_sn', '=', $order_sn];
            }
            if ($start_date) {
                $map[] = ['add_time', '>', $start_date];
            }
            if ($end_date) {
                $map[] = ['add_time', '<', $end_date];
            }

            $count = Db::name('order')->where($map)->count();
            $order = Db::name('order')
                ->where($map)
                ->field('order_id,order_sn,add_time,total_amount,order_amount,order_status,pay_status,pay_name,shipping_status,_source,consignee,user_id,mobile,address,self_pickup_time,write_off')
                ->page($page, $pageSize)
                ->order($field, $sort)
                ->select();

            if ($order) {
                $goods = Db::name('order_goods')
                    ->where('order_id', 'IN', array_column($order, 'order_id'))
                    ->where($goodsmap)
                    ->field('rec_id,order_id,goods_id,goods_name,goods_img,goods_price,goods_num,tempvalue')
                    ->order('order_id desc')
                    ->select();

                $ids      = array_column($goods, 'order_id');
                $valCount = array_count_values($ids);
                foreach ($order as $index => $item) {
                    if (isset($valCount[$item['order_id']]))
                        $order[$index]['goods_list'] = array_slice($goods, array_search($item['order_id'], $ids), $valCount[$item['order_id']]);
                    else $order[$index]['goods_list'] = [];
                }
            }
            //header('x-powered-by: ZendServer 8.5.0,ASP.NET');
            return json(['count' => $count, 'page' => $page, 'list' => $order]);
        }

        $pageconf = [
            'flow'            => $flow,
            'order_status'    => config('order_status'),
            'pay_status'      => config('pay_status'),
            'shipping_status' => config('shipping_status'),
        ];
        return view('', $pageconf);
    }

    public function order_detail()
    {
        $model = new OrderModel();
        //订单状态order_status  0:待确认 1:已确认 2:待评价 3:已取消 4:已完成 5:退款 6:作废
        if ($this->request->isPost()) {
            $order_id = input('order_id/d');
            $code     = input('post.code');
            $remark   = input('post.action_note');
            $order    = $model->find($order_id)->toArray();
            if (!$order) return json('订单不存在', 403);
            $url = $_SERVER["HTTP_REFERER"] ?? '/sr/order/index';
            //delivery=>生成发货单,send=>去发货
            switch ($code) {
                case 'delivery':
                    $url = url('order/make_delivery', ['order_id' => $order_id]);
                    break;
                case 'send':
                    $url = url('order/delivery_list', ['order_sn' => input('order_sn')]);
                    break;
            }

            return json($url);
        }

        if (input('?order_id')) $map[] = ['order_id', '=', input('order_id')];
        else $map[] = ['order_sn', '=', input('order_sn')];

        $order = $model->where($map)->find();
        if (!$order) {
            if ($this->request->isAjax()) return json('订单不存在', 403);
            $this->error('订单不存在');
        }
        $order            = getRegionText($order);
        $order->user_name = Db::name('user')->where('user_id', $order->user_id)->value('user_name');

        $goods = $this->getOrderGoods($order->order_id);
        if (!$goods) {
            $this->error('订单号不正确');
        }

        $order->sr_goods_price = 0;
        foreach ($goods as $index => $good) {
            $order->sr_goods_price += $good['goods_price'] * $good['goods_num'];
            if ($good['product_id']) {
                $goods[$index]['goods_number'] = Db::name('products')->where(['product_id' => $good['product_id']])->value('product_number');
            } else {
                $goods[$index]['goods_number'] = Db::name('goods')->where('goods_id', $good['goods_id'])->value('goods_number');
            }
        }
        $action_log      = Db::name('order_log')->where('order_id', $order->order_id)->order('action_id DESC')->select();
        $autoConfirmTime = Db::name('shop_config')->where('code', 'open_delivery_time')->value('value');
        $data            = [
            'order'           => $order,
            'goods'           => $goods,
            'action_log'      => $action_log,
            'autoConfirmTime' => $autoConfirmTime,
            'btn'             => $this->getOrderBtn($order, $goods),
            'order_status'    => config('order_status'),
            'pay_status'      => config('pay_status'),
            'shipping_status' => config('shipping_status')
        ];
        if ($this->request->isAjax()) return json($data);

        return $this->fetch('', $data);
    }

    protected function fixorderwhere($flow_index)
    {
        //[0 => '请选择', 1 => '待确认', 2 => '待付款', 3 => '待发货', 4 => '已完成', 5 => '取消', 6 => '无效', 7 => '退货', 8 => '待收货']
        $flow  = config('flow_index');
        $where = [];
        switch ($flow_index) {
            case 1:
                $where[] = ['order_status', '=', 0];
                break;
            case 2:
                $where[] = ['order_status', '<', 2];
                $where[] = ['pay_status', '=', 0];
                break;
            case 3:
                $where[] = ['order_status', '<', 2];
                $where[] = ['pay_status', '=', 1];
                $where[] = ['shipping_status', '=', 0];
                break;
            case 4:
                $where[] = ['order_status', '=', 4];
                break;
            case 5:
                $where[] = ['order_status', '=', 3];
                break;
            case 6:
                $where[] = ['order_status', '=', 6];
                break;
            case 7:
                $where[] = ['order_status', '=', 5];
                break;
            case 8:
                $where[] = ['order_status', '<', 2];
                $where[] = ['pay_status', '=', 1];
                $where[] = ['shipping_status', '=', 2];
                break;
        }
        return $where;
    }

    protected function getOrderBtn($order, $goods)
    {
        $os  = $order->order_status;//订单状态
        $ps  = $order->pay_status;//支付状态
        $ss  = $order->shipping_status;//发货状态
        $btn = [];
        if ($order->pay_code == 'cod') {
            //TODO
        } else {
            if ($order->self_pickup_time) {
                $btn['self_pickup'] = '到店自提订单';
            } elseif ($os == 1 && $ps == 1 && $ss != 2) {
                $wait_send    = 0;
                $sended       = 0;
                $stock_outing = 0;
                foreach ($goods as $index => $good) {
                    if ($good['is_send'] == 0) {
                        $wait_send++;
                    } else if ($good['send_number'] == $good['goods_num']) {
                        $sended++;
                    } else {
                        $stock_outing++;
                    }
                }
                if ($wait_send) $btn['delivery'] = '生成发货单';
                if ($stock_outing) {
                    $btn['send'] = '去发货';
                }
            }
        }
        return $btn;
    }

    protected function getOrderGoods($order_id)
    {
        $goods = Db::name('order_goods')
            ->where(['order_id' => ($order_id), 'sr_id' => session('sr.id')])
            ->select();
        return $goods;
    }

    public function make_delivery()
    {
        $order = OrderModel::get(input('order_id/d'));
        if (!$order) {
            if ($this->request->isAjax()) return json('订单不存在', 403);
            $this->error('订单不存在');
        }
        if ($order->shipping_status == 2) {
            if ($this->request->isAjax()) return json('当前订单已发货', 403);
            $this->error('当前订单已发货');
        }

        if ($this->request->isPost()) {
            $data = input('post.');
            //物流顺序原则: 1.选购商品中使用运费模板的物流id; 2.最近一次发货的物流id; 3.留空!
            $shipping_id = $order['shipping_id'];
            if ($shipping_id > 0) {
                $shipping_name = Db::name("shipping")->where('shipping_id', $shipping_id)->value('shipping_name');
            }
            $delivery_row = [
                'delivery_sn'   => get_delivery_sn(),
                'order_sn'      => $order['order_sn'],
                'order_id'      => $order['order_id'],
                'store_id'      => $order['store_id'],
                'sr_id'         => session('sr.id'),
                'shipping_no'   => '',
                'shipping_id'   => $shipping_id,
                'shipping_name' => $shipping_name ?? '',
                'shipping_fee'  => $order['shipping_price'],
                'add_time'      => time(),
                'user_id'       => $order['user_id'],
                'action_user'   => session('sr.name'),
                'consignee'     => $order['consignee'],
                'country'       => $order['country'],
                'province'      => $order['province'],
                'city'          => $order['city'],
                'district'      => $order['district'],
                'street'        => '',//暂未启用地区五级联动
                'address'       => $order['address'],
                'email'         => $order['email'],
                'zipcode'       => $order['zipcode'],
                'mobile'        => $order['mobile'],
                'best_time'     => $order['best_time'],
                'status'        => 0
            ];
            try {
                Db::startTrans();
                $delivery_id    = Db::name('delivery_order')->insertGetId($delivery_row);
                $msg            = '发货单流水号：【' . $delivery_id . '】';
                $delivery_goods = [];
                $sql            = "UPDATE " . config('database.prefix') . "order_goods set send_number = CASE rec_id ";
                $rec_ids        = [];
                foreach ($data['goods'] as $index => $goods) {
                    $goods['send_num'] = (int)$goods['send_num'];
                    if ($goods['send_num'] == 0) {
                        continue;
                    }

                    if ($goods['order_id'] != $order['order_id']) {
                        throw new \Exception('订单与发货商品不符~');
                    }

                    //预防多人操作发货错误
                    $sended = Db::name('order_goods')->where('rec_id', $goods['rec_id'])->value('send_number');
                    if ($goods['send_number'] != $sended) {
                        throw new \Exception($goods['goods_name'] . ' 已出库数量变化，请确认~');
                    }

                    if ($goods['send_num'] < 1 || $goods['send_num'] > (int)$goods['goods_num'] - (int)$goods['send_number']) {
                        throw new \Exception('发货数量有误,请确认~');
                    }
                    $num                    = (int)$goods['send_number'] + $goods['send_num'];
                    $sql                    .= "WHEN {$goods['rec_id']} THEN {$num} ";//FIXME 多次发货 数量相加
                    $delivery_goods[$index] = [
                        'delivery_id'       => $delivery_id,
                        'goods_id'          => $goods['goods_id'],
                        'product_id'        => $goods['product_id'],
                        'order_goods_recid' => $goods['rec_id'],
                        'tempvalue'         => $goods['tempvalue'],
                        'goods_name'        => $goods['goods_name'],
                        'goods_sn'          => $goods['goods_sn'],
                        'send_num'          => $goods['send_num'],
                    ];
                    array_push($rec_ids, $goods['rec_id']);
                    $msg .= '<br>【商品货号：' . $goods['goods_sn'] . '，发货：' . $goods['send_num'] . '件】';
                }

                if (count($delivery_goods)) {
                    Db::name('delivery_goods')->insertAll($delivery_goods);
                    $sql .= 'END WHERE rec_id IN (' . implode(',', $rec_ids) . ')';
                    Db::execute($sql);
                } else {
                    throw new \Exception('请填写发货商品数量');
                }

                if ($data['action_note']) {
                    $msg .= '<br>' . $data['action_note'];
                }
                Db::name('order')->where('order_id', $order['order_id'])->update(['shipping_status' => 1, 'shipping_time' => time()]);
                $user = session('sr');
                orderLog($order, '生成发货单', $msg, $user['id'], $user['username'], 3);
                Db::commit();
                return json(url('order/delivery', ['delivery_id' => $delivery_id]));//TODO 发货url
            } catch (\Exception $e) {
                Db::rollback();
                return json($e->getMessage(), 403);
            }
        }

        $order              = getRegionText($order);
        $order['user_name'] = Db::name('user')->where('user_id', $order['user_id'])->value('user_name');

        $goods           = $goods = $this->getOrderGoods($order->order_id);
        $action_log      = Db::name('order_log')->where('order_id', $order->order_id)->order('action_id DESC')->select();
        $autoConfirmTime = Db::name('shop_config')->where('code', 'open_delivery_time')->value('value');
        $data            = [
            'order'           => $order,
            'goods'           => $goods,
            'action_log'      => $action_log,
            'autoConfirmTime' => $autoConfirmTime,
            'order_status'    => config('order_status'),
            'pay_status'      => config('pay_status'),
            'shipping_status' => config('shipping_status')
        ];
        if ($this->request->isAjax()) return json($data);

        return $this->fetch('', $data);
    }

    public function delivery()
    {
        $delivery_id = input('delivery_id/d');
        $deliModel   = new DeliveryModel();
        if ($this->request->isPost()) {
            $action_note = input('action_note');
            $shipping_no = input('shipping_no');
            $shipping_id = input('shipping_id/d', 0);
            if (!$shipping_no) {
                return json('请输入发货单号~', 403);
            }
            if ($shipping_id == 0) {
                return json('请选择物流公司~', 403);
            }
            $delivery = $deliModel->get($delivery_id);
            $res      = $deliModel->send($delivery->order_id, $delivery_id, $shipping_id, $shipping_no);
            if ($res) {
                $order = OrderModel::get($delivery->order_id);
                orderLog($order, '供应商发货', $action_note, session('sr.id'), session('sr.username'), 3);
                return json(url('order/delivery_list'));
            } else {
                return json('操作失败', 403);
            }
        }

        $delivery = $deliModel->where('sr_id', session('sr.id'))->get($delivery_id);
        if (!$delivery) $this->error('发货单信息不存在~');
        $delivery   = getRegionText($delivery);
        $order      = OrderModel::get($delivery['order_id']);
        $goods      = $deliModel->getDeliveryGoods($delivery_id);
        $action_log = Db::name('order_log')->where('order_id', $order->order_id)->order('action_id DESC')->select();
        $shipping   = Db::name('shipping')->where('enabled=1')->cache(true)->column('shipping_name,shipping_code', 'shipping_id');

        $data = [
            'delivery'        => $delivery,
            'order'           => $order,
            'goods'           => $goods,
            'action_log'      => $action_log,
            'shipping'        => $shipping,
            'order_status'    => config('order_status'),
            'shipping_status' => config('shipping_status'),
            'pay_status'      => config('pay_status'),
        ];

        return $this->fetch('', $data);
    }

    public function delivery_list()
    {
        if ($this->request->isAjax()) {
            $delivery_sn = input('delivery_sn');
            $order_sn    = input('order_sn');
            $status      = input('status');
            $consignee   = input('consignee');
            $page        = input('page/d', 1);
            $pageSize    = input('pageSize/d', 15);
            $field       = input('field', 'delivery_id');
            $sort        = input('sort', 'desc');

            $map = [['sr_id', '=', session('sr.id')]];

            if ($delivery_sn) {
                $map[] = ['delivery_sn', '=', $delivery_sn];
            }
            if ($delivery_sn) {
                $map[] = ['delivery_sn', '=', $delivery_sn];
            }
            if ($order_sn) {
                $map[] = ['order_sn', '=', $order_sn];
            }
            if ($status) {
                $map[] = ['status', '=', $status];
            }
            if ($consignee) {
                $map[] = ['consignee', 'LIKE', '%' . $consignee . '%'];
            }

            $count = Db::name('delivery_order')->where($map)->count();
            $list  = Db::name('delivery_order')->where($map)->order($field, $sort)->page($page, $pageSize)->select();

            $res = ['list' => $list, 'page' => $page, 'count' => $count];
            return json($res);
        }

        return $this->fetch();
    }

    public function delivery_del()
    {
        $id       = input('post.delivery_id/d');
        $model    = new DeliveryModel;
        $order_id = $model->del($id);
        if (is_numeric($order_id)) {
            $user  = session('sr');
            $order = OrderModel::get($order_id);
            orderLog($order, '删除发货单', '', $user['id'], $user['username'], 3);
            return json();
        }
        return json($order_id, 403);
    }

    public function showInvoice()
    {
        $order_id    = input('order_id/d');
        $order       = OrderModel::get($order_id);
        $vat_invoice = [];
        if ($order->invoice_type > 0 && $order->invoice_no > 0) {
            $vat_invoice = Db::name('user_vat_invoice')->where('id', $order->invoice_no)->find();
        }
        $this->assign('order', $order);
        $this->assign('vat_invoice', $vat_invoice);
        return view();
    }
}