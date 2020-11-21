<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 10:59
 */

namespace app\seller\controller;

use app\seller\model\Delivery as DeliveryModel;
use think\Db;
use app\seller\model\Order as OrderModel;
use app\seller\model\Delivery;

class Order extends Base
{
    public function order_list()
    {
        $flow = config('order_flow');
        if ($this->request->isAjax()) {
            $field      = input('field', 'order_id');
            $sort       = input('sort', 'desc');
            $start_date = input('start_date/d');
            $end_date   = input('end_date/d');
            $keywords   = input('keywords');
            $flow_index = input('flow_index');
            $consignee  = input('consignee');
            $order_sn   = input('order_sn');
            $page       = input('page/d', 1);
            $pageSize   = input('pageSize/d', 15);


            if ($keywords) {
                $order_ids = Db::name('order_goods')->where('goods_name LIKE "%' . $keywords . '%" AND store_id = ' . $this->store_id)->column('order_id');
                if ($order_ids) $map[] = ['order_id', 'IN', $order_ids];
            }
            if ($flow_index > 0) {
                $map = fixorderwhere($flow_index);
            }
            $map[] = ['store_id', '=', $this->store_id];
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
        return $this->fetch('', $pageconf);
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
            $url = $_SERVER["HTTP_REFERER"];
            //confirm=>确认,cancel=>取消确认,delivery=>生成发货单,send=>去发货,after_service=>售后,cancel_order=>取消订单(退款)
            switch ($code) {
                case 'confirm':
                    if ($order['order_status'] <> 0) return json('非法操作', 403);
                    $model->where('order_id', $order_id)->update(['order_status' => 1]);
                    orderLog($order, '确认订单', $remark, $this->seller['id'], $this->seller['username'], 1);
                    break;
                case 'cancel':
                    if ($order['order_status'] != 1) return json('当前订单状态不支持取消', 403);
                    $model->where('order_id', $order_id)->update(['order_status' => 0]);
                    orderLog($order, '取消确认订单', $remark, $this->seller['id'], $this->seller['username'], 1);
                    break;
                case 'delivery':
                    $url = url('order/make_delivery', ['order_id' => $order_id]);
                    break;
                case 'send':
                    $url = url('order/delivery_list', ['order_sn' => input('order_sn')]);
                    break;
                case 'cancel_order':
                    if ($order['pay_status'] > 0) return json('无法取消已付款订单', 403);
                    if (!$remark) return json('取消订单需填写操作备注', 403);
                    Db::name('order')->where('order_id', $order_id)->update(['order_status' => 3]);
                    orderLog($order, '商家取消订单', $remark, $this->seller['id'], $this->seller['username'], 1);
                    break;
                case 'after_service':
                    return json('售后功能暂未对商家开放', 302);
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
        $order              = getRegionText($order);
        $order['user_name'] = Db::name('user')->where('user_id', $order['user_id'])->value('user_name');

        $goods      = $model->getOrderGoods($order['order_id']);
        $action_log = $model->getOrderLog($order['order_id']);
        $data       = [
            'order'           => $order,
            'goods'           => $goods,
            'action_log'      => $action_log,
            'btn'             => $model->getOrderBtn($order),
            'order_status'    => config('order_status'),
            'pay_status'      => config('pay_status'),
            'shipping_status' => config('shipping_status')
        ];
        if ($this->request->isAjax()) return json($data);

        return $this->fetch('', $data);
    }

    public function order_edit()
    {
        $model = new OrderModel();
        $awllo = ['user', 'goods', 'consignee', 'invoice', 'money'];
        if ($this->request->isPost()) {
            $step = input('step/d');
            $act  = input('act');
            if ($act) {
                $data = input('post.');
                //step==1，修改订单商品;
                switch ($act) {
                    case 'delGoods':
                        $order_goods = Db::name('order_goods')->where('rec_id', $data['rec_id'])->find();
                        if (!$order_goods) {
                            $result = '订单商品不存在~';
                            break;
                        }
                        $order = $model::get($order_goods['order_id']);
                        if (!$order) {
                            $result = '订单不存在~';
                            break;
                        }
                        $result = Db::name('order_goods')->where('rec_id', $data['rec_id'])->delete();//update(['is_delete' => time()]);
                        break;
                    case 'updateGoods':
                        $order = $model::get($data['order_id']);
                        if (!$order) {
                            $result = '订单不存在~';
                            break;
                        }
                        $update = [
                            'goods_num'   => $data['goods_num'],
                            'goods_price' => $data['goods_price'],
                        ];
                        $result = Db::name('order_goods')->where('rec_id', $data['rec_id'])->update($update);
                        break;
                    case 'appendGoods':
                        $result = $this->validate($data, [
                            'order_id'     => 'require|gt:0',
                            'goods_id'     => 'require|gt:0',
                            'goods_num'    => 'require|gt:0',
                            'goods_price'  => 'require|gt:0',
                            'market_price' => 'require|gt:0',
                            'product_id'   => 'require',
                        ], [
                            'order_id'     => '订单号错误',
                            'goods_id'     => '请选择商品',
                            'goods_num'    => '商品数量错误',
                            'goods_price'  => '商品价格错误',
                            'market_price' => '参数错误-市场价',
                            'product_id'   => '参数错误-规格',
                        ]);
                        if (true !== $result) {
                            break;
                        }
                        $order = $model::get($data['order_id']);
                        $goods = Db::name('goods')->where(['store_id' => $this->store_id, 'goods_id' => $data['goods_id']])->field('goods_desc', true)->find();
                        if (!$order && $goods) {
                            $result = '订单号或所选商品错误~';
                            break;
                        }
                        //FIXME 编辑商品不再赠送积分
                        $data['store_id']           = $this->store_id;
                        $data['goods_name']         = $goods['goods_name'];
                        $data['goods_img']          = $goods['original_img'];
                        $data['goods_sn']           = $goods['goods_sn'];
                        $data['member_goods_price'] = $data['goods_price'];
                        $data['brand_id']           = $goods['brand_id'];
                        $result                     = Db::name('order_goods')->insert($data);
                        if (!$result) break;
                        $result = $model->getOrderGoods($data['order_id']);
                        break;
                    default:
                        $result = '非法操作~';
                }

                if (is_numeric($result) && !$result > 0) {
                    return json('操作失败,数据未保存~', 403);
                } elseif (is_string($result)) {
                    return json($result, 403);
                }
                $msg = ['delGoods' => '删除商品', 'updateGoods' => '修改商品数量/价格', 'appendGoods' => '追加商品'];
                orderLog($order, '修改订单', $msg[$act], $this->seller['id'], $this->seller['username'], 1);
                return json($result);
            } else {
                $data  = input('post.order');
                $order = $model::get($data['order_id']);
                if (!$order) {
                    return json('订单不存在~', 403);
                }
                $order = $order->toArray();
                switch ($step) {
                    case 0:
                        $order['user_id'] = $data['user_id'];
                        break;
                    case 1:
                        $result = true;
                        break;
                    case 2:
                        $order['consignee'] = $data['consignee'];
                        $order['mobile']    = $data['mobile'];
                        $order['country']   = $data['country'];
                        $order['province']  = $data['province'];
                        $order['city']      = $data['city'];
                        $order['district']  = $data['district'];
                        $order['address']   = $data['address'];
                        $order['email']     = $data['email'];
                        $order['zipcode']   = $data['zipcode'];
                        break;
                    case 3:
                        $order['invoice_type']    = $data['invoice_type'];
                        $order['invoice_title']   = $data['invoice_title'];
                        $order['invoice_content'] = $data['invoice_content'];
                        $order['invoice_no']      = $data['invoice_no'];
                        $order['user_note']       = $data['user_note'];
                        $order['admin_note']      = $data['admin_note'];
                        break;
                    case 4:
                        if ($order['pay_status'] == 1) return json('已付款订单无法修改费用信息~', 403);
                        $order['shipping_price'] = $data['shipping_price'];
                        $order['discount']       = $data['discount'];
                        $order['order_amount']   = $data['order_amount'];
                        break;
                    default:
                        return json('非法操作', 403);
                }
                if ($step != 1)
                    $result = $model::where('order_id', $order['order_id'])->update($order);
                if (!$result) return json('操作失败,数据未保存~');
                $msg = [0 => '修改下单用户', 2 => '修改收货信息', 3 => '修改发票信息', 4 => '修改订单价格'];
                if ($step != 1)
                    orderLog($order, '修改订单', $msg[$step], $this->seller['id'], $this->seller['username'], 1);
                return json(url('order/order_detail', ['order_id' => $order['order_id']]));
            }
        }

        //GET展示页面
        $order_id   = input('order_id/d');
        $step_index = array_search(input('step'), $awllo);
        if (false === $step_index) {
            $this->error('参数错误~');
        }

        $order = $model::get($order_id);
        if (!$order) {
            $this->error('订单不存在~');
        }
        $order              = getRegionText($order);
        $order['goods']     = $model->getOrderGoods($order_id);
        $order['user_name'] = Db::name('user')->where('user_id', $order['user_id'])->value('user_name');
        $region             = getRegionList($order);
        return $this->fetch('', ['order' => $order, 'step_index' => $step_index, 'region' => $region]);
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

            $map = [['store_id', '=', $this->store_id]];

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

    /**
     * 生成发货单
     * @return mixed|\think\response\Json
     */
    public function make_delivery()
    {
        $model = new OrderModel();
        $order = $model->where('order_id', input('order_id/d'))->find();
        if (!$order) {
            if ($this->request->isAjax()) return json('订单不存在', 403);
            $this->error('订单不存在');
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
                'shipping_no'   => '',
                'shipping_id'   => $shipping_id,
                'shipping_name' => $shipping_name ?? '',
                'shipping_fee'  => $order['shipping_price'],
                'add_time'      => time(),
                'user_id'       => $order['user_id'],
                'action_user'   => $this->seller['username'],
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
                //'how_oos'     => $order[''],//缺货处理
                //'insure_fee'     => $order[''],//保价费用
                'status'        => 0
            ];
            try {
                Db::startTrans();
                $delivery_id    = Db::name('delivery_order')->insertGetId($delivery_row);
                $msg            = '发货单流水号：【' . $delivery_id . '】';
                $delivery_goods = [];
                $sql            = "UPDATE " . config('database.prefix') . "order_goods set send_number = CASE rec_id ";

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
                    $sql                    .= "WHEN {$goods['rec_id']} THEN {$num} ";
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
                    $msg                    .= '<br>【商品货号：' . $goods['goods_sn'] . '，发货：' . $goods['send_num'] . '件】';
                }

                if (count($delivery_goods)) {
                    Db::name('delivery_goods')->insertAll($delivery_goods);
                    $sql .= "END WHERE order_id = " . $order['order_id'];
                    Db::execute($sql);
                } else {
                    throw new \Exception('请填写发货商品数量');
                }

                if ($data['action_note']) {
                    $msg .= '<br>' . $data['action_note'];
                }
                Db::name('order')->where('order_id', $order['order_id'])->update(['shipping_status' => 1, 'shipping_time' => time()]);
                orderLog($order, '生成发货单', $msg, $this->seller['id'], $this->seller['username'], 1);
                Db::commit();
                return json(url('order/delivery', ['delivery_id' => $delivery_id]));//TODO 发货url
            } catch (\Exception $e) {
                Db::rollback();
                return json($e->getMessage(), 403);
            }
        }

        $order              = getRegionText($order);
        $order['user_name'] = Db::name('user')->where('user_id', $order['user_id'])->value('user_name');

        $goods      = $model->getOrderGoods($order['order_id']);
        $action_log = $model->getOrderLog($order['order_id']);
        $data       = [
            'order'           => $order,
            'goods'           => $goods,
            'action_log'      => $action_log,
            'order_status'    => config('order_status'),
            'pay_status'      => config('pay_status'),
            'shipping_status' => config('shipping_status')
        ];
        if ($this->request->isAjax()) return json($data);

        return $this->fetch('', $data);
    }

    /**
     * 发货单详情
     * @return mixed
     */
    public function delivery()
    {
        $delivery_id = input('delivery_id/d');
        $model       = new OrderModel();
        $deliModel   = new Delivery();
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
                $order = Db::name('order')->where('order_id', $delivery->order_id)->find();
                orderLog($order, '商家发货', $action_note, $this->seller['id'], $this->seller['username'], 1);
                return json(url('order/delivery_list'));
            } else {
                return json('操作失败', 403);
            }
        }

        $delivery = $deliModel->get($delivery_id);
        if (!$delivery) $this->error('发货单信息不存在~');
        $delivery   = getRegionText($delivery);
        $order      = $model->get($delivery['order_id']);
        $goods      = $deliModel->getDeliveryGoods($delivery_id);
        $action_log = $model->getOrderLog($order['order_id']);
        $shipping   = Db::name("shipping")->where('enabled=1')->cache(true)->column('shipping_name,shipping_code', 'shipping_id');

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

    public function delivery_del()
    {
        $id       = input('post.delivery_id/d');
        $model    = new DeliveryModel;
        $order_id = $model->del($id);
        if (is_string($order_id)) {
            ajaxmsg($order_id, 0);
        } else {
            $user  = session('seller');
            $order = Db::name('order')->where('order_id', $order_id)->find();
            orderLog($order, '删除发货单', '', $user['id'], $user['username'], 1);
            ajaxmsg('删除完成', 1);
        }
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
        return view('public/show_invoice');
    }
}
