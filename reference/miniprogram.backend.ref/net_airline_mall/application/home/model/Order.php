<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/16 17:14
 */

namespace app\home\model;

use app\common\model\Supplier;
use think\Exception;
use think\Model;
use think\Db;
use think\facade\Request;

class Order extends Model
{
    protected $pk = 'order_id';

    public function addOrder($user_id, $cart_price, $req, $buyNow = null)
    {
        //$req['address_id'], $req['pay_type'], $req['invoice_type'], $req['user_note'], $req['invoice_title'], $req['invoice_no'], $req['invoice_content'], $req['gift_card']
        $self_pickup_time = null;
        if ((isset($req['mode']) && (int)$req['mode'] == 1)) {
            $address          = [
                'consignee' => $req['pickup']['consignee'],
                'mobile'    => $req['pickup']['mobile'],
                'address'   => $req['pickup']['self_pickup_address'],
            ];
            $self_pickup_time = $req['pickup']['pickup_time'];
        } else {
            $address = Db::name('user_address')->where('address_id', $req['address_id'])->find();
            if (!$address) return ['status' => -1, 'msg' => '收货地址不存在~'];
        }

        $now = time();
        // 防制灌水 1天只能下 50 单
        $order_count = $this->where("user_id= $user_id and order_sn REGEXP '^" . date('Ymd') . "'")->count(); // 查找购物车商品总数量
        if ($order_count >= 50)
            return ['status' => -2, 'msg' => '一天只能下50个订单'];
        $giftcard_list = [];
        $card_ids      = '';
        if (isset($req['gift_card']) && $req['gift_card'] > 0) {
            $giftcard_list = Db::name('giftcard')->where('id', $req['gift_card'])->where('status', 1)->select();
        }
        if (isset($req['giftcardList'])) {
            $req_list      = json_decode($req['giftcardList'], true);
            $giftcard_list = Db::name('giftcard')->whereIn('id', array_column($req_list, 'id'))->where('status', 1)->select();
        }
        if (count($giftcard_list)) {
            $card_ids = implode(',', array_column($giftcard_list, 'id'));
        }
        $ua = $req['ua'] ?? '小程序';
        // 循环添加订单 多少个商家添加多少个订单
        Db::startTrans();
        try {
            $user            = Db::name('user')->where('user_id', $user_id)->find();
            $master_order_sn = get_order_sn(); // 主订单号
            $invoice_no      = '';
            $invoice_title   = $req['invoice_title'] ?? '';
            if (isset($req['invoice_type']) && $req['invoice_type'] > 0) {
                $vat_invoice   = Db::name('user_vat_invoice')->where('user_id', $user_id)->find();
                $invoice_no    = $vat_invoice['id'];
                $invoice_title = $vat_invoice['company_name'];
            }

            foreach ($cart_price['data'] as $store_id => $v) {
                //$stock_type 0下单减库存,1付款减库存
                if ($store_id == 0) {
                    $stock_type = Db::name('shop_config')->where('code', 'stock_dec_time')->value('value');
                } else {
                    if ($cart_price > 0) {
                        throw new \Exception('礼品卡仅可购买自营商品');
                    }
                    $stock_type = Db::name('store')->where('store_id', $store_id)->value('stock_type');
                }
                $order_sn = get_order_sn(); // 获取生成订单号

                // 1. 插入订单
                //cod货到付款标识,若选择在线支付此处留空,待实际支付成功后回调写入
                $pay_code      = (isset($req['pay_type']) && $req['pay_type'] == 'cod') ? 'cod' : '';
                $pay_name      = $pay_code == 'cod' ? '货到付款' : '';
                $shipping_id   = $v['shipping_id'] ?? 0;
                $shipping_name = Db::name('shipping')->where('shipping_id', $shipping_id)->value('shipping_name');
                $data          = [
                    'order_sn'          => $order_sn, // 订单编号
                    'master_order_sn'   => $master_order_sn, // 主订单号
                    'user_id'           => $user_id, // 用户id
                    'consignee'         => $address['consignee'], // 收货人
                    'country'           => $address['country'] ?? 0,//城市id,
                    'province'          => $address['province'] ?? 0,//省份id,
                    'city'              => $address['city'] ?? 0,//城市id,
                    'district'          => $address['district'] ?? 0,//县,
                    'address'           => $address['address'] ?? 0,//详细地址,
                    'mobile'            => $address['mobile'],//手机,
                    'zipcode'           => $address['zipcode'] ?? '',//邮编
                    'regionname'        => $address['regionname'] ?? '',//小程序省市区地址
                    'email'             => $req['invoice_email'] ?? ($address['email'] ?? ''),//邮箱,
                    'pay_code'          => $pay_code,
                    'pay_name'          => $pay_name,
                    '_source'           => $ua, // 订单来源 PC，APP
                    'invoice_type'      => $req['invoice_type'] ?? 0, //发票类型 0普通发票，1增值税发票,
                    'invoice_title'     => $invoice_title, //发票抬头,
                    'invoice_no'        => $invoice_no,
                    'invoice_content'   => $req['invoice_content'] ?? 0,
                    'user_note'         => $req['user_note'] ?? '', //买家留言,
                    'goods_price'       => $v['goods_price'],//每个店铺的商品价格,
                    'shipping_price'    => $v['shipping_price'],//物流价格,
                    'shipping_id'       => $shipping_id,//物流ID,
                    'shipping_name'     => $shipping_name,//物流名称,
                    'best_time'         => $address['best_time'] ?? '',//物流名称,
                    'user_money'        => 0, //余额支付
                    'coupon_price'      => $v['coupon_price'],//'使用优惠券',
                    'card_price'        => 0,//'使用礼品卡',商城自营，不存在多单合并
                    'integral'          => 0, // FIXME 暂时不开放 积分抵扣
                    'integral_money'    => 0,// FIXME 暂时不开放 积分抵扣',
                    'total_amount'      => ($v['goods_price'] + $v['shipping_price']),// 订单总额 = 商品总价 + 物流费
                    'order_amount'      => ($v['goods_price'] + $v['shipping_price'] - $v['coupon_price'] - $v['order_prom_amount']),// 应付金额 = 商品价格 + 物流费 - 优惠券 - 订单优惠金额 - 余额抵扣 - 礼品卡金额
                    'add_time'          => $now, // 下单时间
                    'order_prom_id'     => $v['order_prom_id'],//订单优惠活动id,
                    'order_prom_amount' => $v['order_prom_amount'],//'订单优惠活动优惠了多少钱',
                    'store_id'          => $store_id,  // 店铺id
                    'use_coupon'        => $v['coupon_id'],//使用优惠券id
                    'use_card'          => '',//使用礼品卡id，同一订单可以使用多张礼品卡
                    'self_pickup_time'  => $self_pickup_time,
                ];
                if (isset($req['share_man']) && $req['share_man'] != $user_id) {
                    $data['share_man'] = $req['share_man']; //分享人
                } else {
                    $data['share_man'] = 0;
                }

                if ($data['order_amount'] <= 0) {
                    $data['pay_status'] = 1;
                }
                $order_id = Db::name("order")->insertGetId($data);
                // 记录订单操作日志
                $action_info = [
                    'order_id'         => $order_id,
                    'user_type'        => 2,
                    'action_user_id'   => $user_id,
                    'action_user_name' => $user['nick_name'] ? $user['nick_name'] : $user['user_name'],
                    'action_remark'    => '您提交了订单，请等待系统确认',
                    'status_desc'      => '提交订单',
                    'log_time'         => $now,
                ];
                Db::name('order_log')->insert($action_info);

                // 2. 插入order_goods 表
                $goods = Db::name('goods')->whereIn('goods_id', array_column($v['goods'], 'goods_id'))->column('cost_price,give_integral,goods_number,warn_number,original_img,cat_id,sr_id', 'goods_id');
                $data2 = [];
                foreach ($v['goods'] as $key => $val) {
                    $sr_name = '';
                    $sr_id   = $goods[$val['goods_id']]['sr_id'];
                    if ($sr_id > 0) {
                        $sr_name = Supplier::where('id', $sr_id)->value('name');
                    }
                    $data2[$key]['order_id']           = $order_id; // 订单id
                    $data2[$key]['goods_id']           = $val['goods_id']; // 商品id
                    $data2[$key]['goods_name']         = $val['goods_name']; // 商品名称
                    $data2[$key]['goods_img']          = $goods[$val['goods_id']]['original_img']; // 商品主图
                    $data2[$key]['goods_sn']           = $val['goods_sn']; // 商品货号
                    $data2[$key]['goods_num']          = $val['goods_num']; // 购买数量
                    $data2[$key]['market_price']       = $val['market_price']; // 市场价
                    $data2[$key]['goods_price']        = $val['goods_price']; // 商品价
                    $data2[$key]['product_id']         = $val['product_id']; // 商品规格ID
                    $data2[$key]['tempvalue']          = $val['tempvalue']; // 规格中文名称
                    $data2[$key]['sku']                = $val['sku']; // 商品条码
                    $data2[$key]['member_goods_price'] = $val['member_goods_price']; // 会员折扣价
                    $data2[$key]['cost_price']         = $goods[$val['goods_id']]['cost_price']; // 成本价
                    $data2[$key]['give_integral']      = ($goods[$val['goods_id']]['give_integral'] * $val['goods_num']); // 购买商品赠送积分
                    $data2[$key]['prom_type']          = $val['prom_type']; // 商品活动类型
                    $data2[$key]['prom_id']            = $val['prom_id']; // 活动id
                    $data2[$key]['store_id']           = $val['store_id']; // 店铺id
                    $data2[$key]['cat_id']             = $goods[$val['goods_id']]['cat_id']; // 商品分类id
                    $data2[$key]['commission']         = 0; //FIXME 商品抽成比例,暂时固定比例
                    $data2[$key]['sr_id']              = $sr_id;
                    $data2[$key]['sr_name']            = $sr_name;

                    if (isset($val['share_man']) && $val['share_man'] != $user_id) {
                        $data2[$key]['share_man'] = $val['share_man']; //分享人
                    } else {
                        $data2[$key]['share_man'] = 0;
                    }

                    // 3. 检查库存
                    if ($val['product_id'] > 0) {
                        $spec      = Db::name('products')->where('product_id', $val['product_id'])->field('product_number,product_warn_number,tempvalue')->find();
                        $remaining = $spec['product_number'] - $val['goods_num'];
                        //超卖通知商家，事务回滚
                        if ($remaining < 0) {
                            addStoreMsg($store_id, '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存不足,用户[' . $action_info['action_user_name'] . '](' . $user['mobile'] . ')下单' . $val['goods_num'] . '失败');
                            throw new \Exception($val['goods_name'] . '库存仅剩余：' . $spec['product_number']);
                        } elseif ($remaining <= $spec['product_warn_number']) {
                            //库存预警
                            addStoreMsg($store_id, '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存预警 : ' . $remaining);
                        }

                        //根据店铺设置时机减库存
                        if ($stock_type == 0) {
                            Db::name('products')->where('product_id', $val['product_id'])->setDec('product_number', $val['goods_num']);
                            goods_stock_log($val['goods_id'], -1 * $val['goods_num'], $val['goods_name'], $store_id, $user_id, $spec['tempvalue'], $order_sn);//出库日志
                            Db::name('goods')->where('goods_id', $val['goods_id'])->inc('total_sale_num', $val['goods_num'])->update();//增加商品累计销售数量
                            refresh_stock($val['goods_id']);
                        }
                    } else {
                        $remaining = $goods[$val['goods_id']]['goods_number'] - $val['goods_num'];

                        if ($remaining < 0) {
                            addStoreMsg($store_id, '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存不足,用户[' . $action_info['action_user_name'] . '](' . $user['mobile'] . ')下单' . $val['goods_num'] . '失败');
                            throw new \Exception($val['goods_name'] . '库存仅剩余：' . $goods[$val['goods_id']]['goods_number']);
                        } elseif ($remaining < $goods[$val['goods_id']]['warn_number']) {
                            addStoreMsg($store_id, '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存预警 : ' . $remaining);
                        }

                        //根据店铺设置时机减库存
                        if ($stock_type == 0) {
                            goods_stock_log($val['goods_id'], -1 * $val['goods_num'], $val['goods_name'], $store_id, $user_id, '', $order_sn);
                            //出库日志,增加商品累计销售数量
                            Db::name('goods')->where('goods_id', $val['goods_id'])->dec('goods_number', $val['goods_num'])->inc('total_sale_num', $val['goods_num'])->update();
                        }
                    }

                    // 查询该商品活动,是否有买N件赠送指定商品，多规格商品只计算一次
                    if ($val['prom_type'] == 3) {
                        //TODO
                    }
                }
                Db::name("order_goods")->insertAll($data2);

                // 4. 修改优惠券状态
                if ($v['coupon_id'] > 0) {
                    $data3 = [
                        'order_id'  => $order_id,
                        'used_time' => $now,
                    ];
                    $succ  = Db::name('coupon_list')->where(['list_id' => $v['coupon_id'], 'user_id' => $user_id])->update($data3);
                    if (!$succ) {
                        throw new \Exception($v['coupon_price'] . '元优惠券使用错误');
                    }
                    $cid = Db::name('coupon_list')->where('list_id', $v['coupon_id'])->value('coupon_id');
                    Db::name('coupon')->where('coupon_id', $cid)->setInc('use_num'); // 优惠券的使用数量加一
                }

                // 5. 清空购物车
                if (!$buyNow) {
                    Db::name('cart')->where('user_id', $user_id)->whereIn('id', array_column($v['goods'], 'id'))->delete();
                    Cart::cart_goods_calc($user_id);
                }

                // 6. 扣除礼品卡金额，礼品卡抵扣自营店铺不与其他店铺合并提交订单，foreach只循环一次
                if (count($giftcard_list) > 0) {
                    $cardModel    = new Giftcard;
                    $order_amount = $data['order_amount'];
                    $logData      = [];
                    foreach ($giftcard_list as $index => $card) {
                        if ($order_amount == 0) {
                            break;
                        }

                        $logData[$index] = [
                            'gid'      => $card['id'],
                            'order_id' => $order_id,
                            'user_id'  => $user_id,
                            'use_time' => $now,
                            'status'   => 0,
                        ];
                        $where           = ['id' => $card['id']];
                        if ($order_amount > $card['cash']) {
                            $res                     = $cardModel->isUpdate(true)->save(['cash' => 0], $where);
                            $logData[$index]['cash'] = $card['cash'];
                            $order_amount            -= $card['cash'];
                        } else {
                            $res                     = $cardModel->isUpdate(true)->save(['cash' => Db::raw('cash-' . $order_amount)], $where);
                            $logData[$index]['cash'] = $order_amount;
                            $order_amount            = 0;
                        }
                        if (!$res) {
                            throw new \Exception('礼品卡抵扣错误');
                        }
                    }

                    Db::name("giftcard_log")->insertAll($logData);
                    $gift_price   = $data['order_amount'] - $order_amount;//礼品卡抵扣金额
                    $order_amount = $data['order_amount'] - $gift_price;
                    $update       = [
                        'card_price'   => $gift_price,
                        'order_amount' => $order_amount,
                        'use_card'     => $card_ids
                    ];
                    if ($order_amount == 0) {
                        $update['pay_status'] = 1;
                        $update['pay_time']   = $now;
                        $update['pay_code']   = 'gift_card';
                        $update['pay_name']   = '礼品卡抵扣';
                    }
                    $success = $this->where('order_id', $order_id)->update($update);
                    if (!$success) {
                        throw new \Exception($gift_price . '元 礼品卡使用错误');
                    }
                }
                // 7. 推送消息给商家

            }

            //多店铺合并支付使用marster_order_sn,否则单店铺支付使用order_sn
            $sn         = $master_order_sn;
            $pay_status = $this->where('master_order_sn', $sn)->value('pay_status');
            if (count($cart_price['data']) == 1) {
                $sn         = $order_sn;
                $pay_status = $this->where('order_sn', $sn)->value('pay_status');
            }
            $cart_goods_calc = Cart::cart_goods_calc($user_id);
            session('cart_num', $cart_goods_calc['goods_num']);// 更新购物车数量
            Db::commit();
            return ['status' => 1, 'msg' => '提交订单成功', 'sn' => $sn, 'id' => $order_id, 'pay_status' => $pay_status]; // 返回新增的订单id
        } catch (\Exception $e) {
            Db::rollback();
            trace('【插入订单失败：】' . $e->getMessage());
            return ['status' => -9, 'msg' => $e->getMessage()];
        }
    }

    public function getUserOrderList($user_id, $limit = 5)
    {
        $order_list = $this->where(['user_id' => $user_id, 'is_delete' => 0])
            ->order('order_id DESC')->limit($limit)
            ->column('add_time,total_amount,pay_name,consignee', 'order_id');
        return $this->fixOrderGoods($order_list);
    }

    public function fixOrderGoods($order_list)
    {
        if (count($order_list) == 0) {
            return [];
        }
        $goods    = Db::name('order_goods')
            ->whereIn('order_id', array_column($order_list, 'order_id'))
            ->order('order_id DESC')
            ->column('goods_id,order_id,goods_img as original_img,goods_num,sr_id,cost_price', 'rec_id');
        $ids      = array_column($goods, 'order_id');
        $valCount = array_count_values($ids);
        foreach ($order_list as $index => $item) {
            if (!isset($valCount[$item['order_id']])) continue;
            $order_list[$index]['order_goods'] = array_slice($goods, array_search($item['order_id'], $ids), $valCount[$item['order_id']]);
            $order_list[$index]['jiesuanNum']  = array_sum(array_column($order_list[$index]['order_goods'], 'goods_num'));
        }
        return $order_list;
    }

    #积分订单
    public function exorder($user_id, $goods_id)
    {
        $res            = false;
        $user           = Db::name("user")->where("user_id={$user_id}")->find();
        $gmap           = [];
        $gmap[]         = ['goods_id', '=', $goods_id];
        $gmap[]         = ['is_on_sale', '=', 1];
        $gmap[]         = ['is_show', '=', 1];
        $gmap[]         = ['review_status', '=', 2];
        $goods          = Db::name("goods")->where($gmap)->find();
        $exchange_goods = Db::name("exchange_goods")->where("goods_id={$goods_id} and is_exchange=1")->find();
        $address        = Db::name('user_address')->where("user_id={$user_id} and is_default=1")->find();
        if ($goods['store_id'] > 0) {
            $store = Db::name("store")->where("store_id={$goods['store_id']}")->find();
        } else {
            $store             = [];
            $goods['store_id'] = 0;
        }
        $remaining = $goods['goods_number'] - 1;
        if ($remaining < 1) {
            addStoreMsg($goods['store_id'], '商品[' . $goods['goods_id'] . $goods['goods_name'] . ']库存不足,用户[' . $user['user_name'] . '](' . $user['mobile'] . ')积分兑换' . $goods['goods_num'] . '失败');
            return $res;
        }
        Db::startTrans();
        try {
            $master_order_sn = get_order_sn(); // 主订单号
            $order_sn        = get_order_sn();
            $now             = time();
            $orderData       = [
                'order_sn'          => $order_sn, // 订单编号
                'master_order_sn'   => $master_order_sn, // 主订单号
                'user_id'           => $user['user_id'], // 用户id
                'consignee'         => $address['consignee'], // 收货人
                'country'           => $address['country'],//城市id,
                'province'          => $address['province'],//省份id,
                'city'              => $address['city'],//城市id,
                'district'          => $address['district'],//县,
                'address'           => $address['address'],//详细地址,
                'mobile'            => $address['mobile'],//手机,
                'zipcode'           => $address['zipcode'],//邮编
                'email'             => $address['email'],//邮箱,
                'invoice_title'     => '', //发票抬头,
                'user_note'         => '', //买家留言,
                'goods_price'       => 0,//每个店铺的商品价格,
                'shipping_price'    => 0,//物流价格,
                'user_money'        => 0, // FIXME 暂时不开放余额支付
                'coupon_price'      => 0,//'使用优惠券',
                'integral'          => $exchange_goods['exchange_integral'], //使用积分
                'integral_money'    => 0,//积分抵扣,
                'total_amount'      => 0,// 订单总额 = 商品总价 + 物流费
                'order_amount'      => 0,// 应付金额 = 商品价格 + 物流费 - 优惠券 - 订单优惠金额
                'add_time'          => $now, // 下单时间
                'order_prom_id'     => $exchange_goods['eid'],//积分商城对应的产品ID,
                'order_prom_type'   => 6,//订单类型：0：默认，1：抢购，2：团购，3：优惠，4：预售，5：虚拟，6：积分商城
                'order_prom_amount' => 0,//'订单优惠活动优惠了多少钱',
                'store_id'          => $goods['store_id'],  // 店铺id
                'pay_status'        => 1,//积分兑换，默认已支付
                'pay_time'          => $now,
            ];
            $order_id        = Db::name("order")->insertGetId($orderData);

            #记录订单操作日志
            $action_info = [
                'order_id'         => $order_id,
                'user_type'        => 2,
                'action_user_id'   => $user_id,
                'action_user_name' => $user['nick_name'] ? $user['nick_name'] : $user['user_name'],
                'action_remark'    => '您提交了订单，请等待系统确认',
                'status_desc'      => '提交订单',
                'log_time'         => $now,
            ];
            Db::name('order_log')->insert($action_info);
            $orderGoodsData                  = [];
            $orderGoodsData['order_id']      = $order_id; // 订单id
            $orderGoodsData['goods_id']      = $goods['goods_id']; // 商品id
            $orderGoodsData['goods_name']    = $goods['goods_name']; // 商品名称
            $orderGoodsData['goods_sn']      = $goods['goods_sn']; // 商品货号
            $orderGoodsData['goods_num']     = 1; // 购买数量
            $orderGoodsData['market_price']  = $goods['market_price']; // 市场价
            $orderGoodsData['goods_price']   = $goods['shop_price']; // 商品价
            $orderGoodsData['product_id']    = $goods['product_id']; // 商品规格ID
            $orderGoodsData['tempvalue']     = ''; // 规格中文名称
            $orderGoodsData['sku']           = $goods['goods_sn']; // 商品条码
            $orderGoodsData['goods_img']     = $goods['original_img']; // 图片
            $orderGoodsData['cost_price']    = $goods['cost_price']; // 成本价
            $orderGoodsData['give_integral'] = 0; // 购买商品赠送积分
            $orderGoodsData['prom_type']     = 5; // 商品活动类型
            $orderGoodsData['prom_id']       = $exchange_goods['eid']; //积分产品ID
            $orderGoodsData['store_id']      = $goods['store_id']; // 店铺id
            $rec_id                          = Db::name("order_goods")->insertGetId($orderGoodsData);
            $rows                            = Db::name("user")
                ->where("user_id={$user_id}")
                ->setDec('points', $exchange_goods['exchange_integral']);
            $eids                            = Db::name("exchange_goods")->where("eid={$exchange_goods['eid']}")
                ->setInc("sale_num", 1);
            Db::commit();
            if ($order_id > 0 && $rec_id > 0 && $rec_id > 0 && $eids > 0) {
                $res = true;
            }
        } catch (\Exception $e) {
            Db::rollback();
        }
        return $res;
    }

    public function addOrderForAuction($user_id, $goods_id, $price, $number, $house_id)
    {
        try {
            $goods           = Db::name('goods')->where('goods_id', $goods_id)->find();
            $now             = time();
            $master_order_sn = get_order_sn();
            $order_sn        = get_order_sn();
            $data            = [
                'order_sn'          => $order_sn, // 订单编号
                'master_order_sn'   => $master_order_sn, // 主订单号
                'user_id'           => $user_id, // 用户id
                'consignee'         => '', // 收货人
                'country'           => '',//城市id,
                'province'          => '',//省份id,
                'city'              => '',//城市id,
                'district'          => '',//县,
                'address'           => '',//详细地址,
                'mobile'            => '',//手机,
                'zipcode'           => '',//邮编
                'email'             => '',//邮箱,
                'pay_code'          => '',
                'pay_name'          => '',
                '_source'           => 'APP', // 订单来源 PC，WAP，APP
                'invoice_title'     => '', //发票抬头,
                'user_note'         => '', //买家留言,
                'goods_price'       => $price,//每个店铺的商品价格,
                'shipping_price'    => 0,//物流价格,
                'shipping_id'       => 0,//物流ID,
                'shipping_name'     => '',//物流名称,
                'best_time'         => '',//物流名称,
                'user_money'        => 0, // FIXME 暂时不开放余额支付
                'coupon_price'      => 0,//'使用优惠券',
                'integral'          => 0, // FIXME 暂时不开放 积分抵扣
                'integral_money'    => 0,// FIXME 暂时不开放 积分抵扣',
                'total_amount'      => round($price * $number, 2),// 订单总额
                'order_amount'      => round($price * $number, 2),// 应付金额
                'add_time'          => $now, // 下单时间
                'order_prom_id'     => $house_id,//订单优惠活动id,
                'order_prom_type'   => 8,//订单优惠活动类型,
                'order_prom_amount' => 0,//'订单优惠活动优惠了多少钱',
                'store_id'          => 0,  // 店铺id
            ];
            $order_id        = Db::name("order")->insertGetId($data);

            // 记录订单操作日志
            $action_info = [
                'order_id'         => $order_id,
                'user_type'        => 2,
                'action_user_id'   => 0,
                'action_user_name' => '系统拍卖订单生成',
                'action_remark'    => '您提交了订单，请等待系统确认',
                'status_desc'      => '提交订单',
                'log_time'         => $now,
            ];
            Db::name('order_log')->insert($action_info);

            //写入订单商品
            $data2 = [
                'order_id'           => $order_id, // 订单id
                'goods_id'           => $goods['goods_id'], // 商品id
                'goods_name'         => $goods['goods_name'], // 商品名称
                'goods_img'          => $goods['original_img'], // 商品主图
                'goods_sn'           => $goods['goods_sn'], // 商品货号
                'goods_num'          => $number, // 购买数量
                'market_price'       => $goods['market_price'], // 市场价
                'goods_price'        => $price, // 商品价
                'product_id'         => $goods['product_id'], // 商品规格ID
                'tempvalue'          => '',
                'sku'                => '', // 商品条码
                'member_goods_price' => 0, // 会员折扣价
                'cost_price'         => 0, // 成本价
                'give_integral'      => 0, // 购买商品赠送积分
                'prom_type'          => 0, // 商品活动类型
                'prom_id'            => 0, // 活动id
                'store_id'           => 0, // 店铺id
                'cat_id'             => $goods['cat_id'], // 商品分类id
                'commission'         => 0 //FIXME 商品抽成比例,暂时固定比例
            ];
            Db::name("order_goods")->insert($data2);
            return $order_id;
        } catch (Exception $e) {
            trace($e->getMessage());
            return false;
        }
    }

    public function useMoney($order_sn, $money)
    {
        $orders = $this->where('order_sn', $order_sn)->whereOr('master_order_sn', $order_sn)->select()->toArray();
        if ($money == 0) {
            $this->whereIn('order_id', array_column($orders, 'order_id'))->update(['user_money' => 0]);
            return array_sum(array_column($orders, 'order_amount'));
        }

        $totalMinus = min($money, array_sum(array_column($orders, 'order_amount')));
        foreach ($orders as $index => $order) {
            if ($money == 0) {
                break;
            }
            //$use = $order['order_amount'] > $money ? $money : $order['order_amount'];
            if ($order['order_amount'] >= $money) {
                $row   = [
                    'order_amount' => $order['order_amount'] - $money,
                    'user_money'   => $money
                ];
                $money = 0;
            } else {
                $row   = [
                    'order_amount' => 0,
                    'user_money'   => $money - $order['order_amount'],
                ];
                $money -= $order['order_amount'];
            }
            if ($row['order_amount'] == 0) {
                //扣除余额
                $row['pay_status'] = 1;
                $row['pay_time']   = time();
                $row['pay_code']   = 'usemoney';
                $row['pay_name']   = '余额支付';
            }
            $this->where('order_id', $order['order_id'])->update($row);
        }
        $res = (new AccountLog)->accountLog($order['user_id'], -1 * $totalMinus, '用户订单支付', 0, 0, $orders[0]['order_id'], $order_sn);
        return $this->getAmountByOrderSn($order_sn);
    }

    public function getAmountByOrderSn($order_sn)
    {
        return $this->where('order_sn', $order_sn)->whereOr('master_order_sn', $order_sn)->sum('order_amount');
    }

    /**
     * 组装 我的订单查询条件
     * @param $order_status
     * @return array
     */
    public function orderStatusWhere($order_status)
    {
        $map = [];
        switch ($order_status) {
            case 'wait_pay':
                $map[] = ['a.order_status', 'IN', '(0,1)'];
                $map[] = ['a.pay_status', '=', 0];
                break;
            case 'wait_send':
                $map[] = ['a.pay_status', '=', 1];
                $map[] = ['a.shipping_status', '<>', 2];
                $map[] = ['a.order_status', 'IN', '(0,1)'];
                break;
            case 'wait_recive':
                $map[] = ['a.order_status', '<', 2];
                $map[] = ['a.shipping_status', '=', 2];
                break;
            case 'comment':
                $map[] = ['a.order_status', '=', 2];
                break;
            case 'completed':
                $map[] = ['a.order_status', '=', 4];
                break;
        }
        return $map;
    }

    /**
     * 统计订单数
     */
    public function statistics($user_id)
    {
        $orders  = $this->where('user_id', $user_id)->where('is_delete=0 AND order_status NOT IN (3,5,6)')->field('order_status,pay_status,shipping_status')->select();
        $counter = [
            'total'       => 0,
            'wait_pay'    => 0,
            'wait_send'   => 0,
            'wait_recive' => 0,
            'comment'     => 0,
            'completed'   => 0,
        ];
        if ($orders) {
            foreach ($orders as $index => $order) {
                $counter['total']++;
                if ($order['order_status'] == 4) {
                    $counter['completed']++;
                } elseif ($order['order_status'] == 2) {
                    $counter['comment']++;
                } elseif ($order['pay_status'] == 0) {
                    $counter['wait_pay']++;
                } elseif ($order['pay_status'] == 1 && $order['shipping_status'] != 2) {
                    $counter['wait_send']++;
                } elseif ($order['order_status'] < 2 && $order['shipping_status'] == 2) {
                    $counter['wait_recive']++;
                }
            }
        }
        return $counter;
    }

    public function cancel($order_id, $user_id)
    {
        $map   = [
            ['order_id', '=', $order_id],
            ['user_id', '=', $user_id],
            ['pay_status', '=', 0],
            ['shipping_status', '=', 0]
        ];
        $order = $this->where($map)->find();
        if (!$order) {
            return (['status' => 0, 'msg' => '订单不存在']);
        }
        try {
            Db::startTrans();
            //退还优惠券
            if ($order->coupon_price > 0 && $order->use_coupon > 0) {
                Db::name('coupon_list')->where(['list_id' => $order->use_coupon])->update(['order_id' => 0, 'used_time' => 0]);
                $cid = Db::name('coupon_list')->where('list_id', $order->use_coupon)->value('coupon_id');
                Db::name('coupon')->where('coupon_id', $cid)->setDec('use_num'); // 优惠券的使用数量减一
            }

            //退还礼品卡
            if ($order->card_price > 0 && $order->use_card) {
                //$cardModel = new Giftcard;
                //$list      = $cardModel->whereIn('id', $order->use_card)->select();
                //$amount    = $order->card_price;
                //$logData   = [];
                //foreach ($list as $index => $item) {
                //    if ($amount == 0) {
                //        break;
                //    }
                //    $logData[$index] = [
                //        'gid'      => $item->id,
                //        'order_id' => $order_id,
                //        'user_id'  => $user_id,
                //        'use_time' => $time,
                //        'status'   => 2,
                //    ];
                //    $where           = ['id' => $item->id];
                //    $gap             = $item->price - $item->cash;
                //    if ($amount > $gap) {
                //        $logData[$index]['cash'] = $gap;
                //        $amount                  -= $gap;
                //        $res                     = $cardModel->isUpdate(true)->save(['cash' => Db::raw('price')], $where);
                //    } else {
                //        $res                     = $cardModel->isUpdate(true)->save(['cash' => Db::raw('cash+' . $amount)], $where);
                //        $logData[$index]['cash'] = $amount;
                //        $amount                  = 0;
                //    }
                //    if (!$res) {
                //        throw new \Exception('退还礼品卡错误');
                //    }
                //}
                //
                //if (count($logData)) {
                //    Db::name('giftcard_log')->insertAll($logData);
                //}
                (new Giftcard)->refund($order->use_card, $order->card_price, $order_id, $user_id);
            }

            $status = $this->isUpdate(true)->save(['order_status' => 3], ['order_id' => $order->order_id]);
            Db::commit();
            return ['status' => $status];
        } catch (\Exception $e) {
            Db::rollback();
            trace($e->getTraceAsString());
            return ['status' => 0, 'msg' => $e->getMessage()];
        }
    }
}
