<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/3 8:52
 */

namespace app\home\controller;

use app\home\model\Giftcard;
use think\Db;
use think\facade\Request;
use app\home\model\Cart as CartModel;
use app\home\model\Order as OrderModel;

use app\home\validate\Invoince;
use Payment\Pay;
use think\response\View;

class Cart extends Common
{
    protected $user;
    protected $user_id;
    protected $session_id;
    protected $beforeActionList = [
        'checkLogin' => ['only' => 'gift_card'],
    ];

    protected function checkLogin()
    {
        if (!$this->user_id) {
            if ($this->request->isAjax()) return json('请登录后操作', 401);
            else $this->redirect('login/index');
        }
    }

    public function initialize()
    {
        parent::initialize();
        $user             = getUserInfo();
        $this->user       = $user;
        $this->user_id    = $user['user_id'];
        $this->session_id = session_id();
    }

    public function addCart()
    {
        $goods_id  = input('post.goods_id/d');
        $num       = input('post.num/d');
        $attr_keys = input('post.attr_keys');
        $user_id   = session('user.user_id') ?? 0;
        $result    = CartModel::addCart($goods_id, $num, $user_id, session_id(), $attr_keys);
        return json($result);
    }

    public function changeNum()
    {
        $type = input('type');
        $id   = input('id/d');
        if ($this->user_id) {
            $where = [['user_id', '=', $this->user_id]];
            //如果用户已经登录则按照用户id查询
        } else {
            $where = [['session_id', '=', $this->session_id]];
        }

        $row = Db::name('cart')->where('id', $id)->where($where)->find();

        if (!$row) {
            return json('购物车商品信息异常，请登录后操作', 401);
        }
        if ($type == 'minus') {
            if ($row <= 1) return json('最少购买数量为1', 403);
            Db::name('cart')->where('id', $id)->setDec('goods_num');
            $res = ['num' => $row['goods_num'] - 1];
        } else {
            Db::name('cart')->where('id', $id)->setInc('goods_num');
            $res = ['num' => $row['goods_num'] + 1];
        }
        $res['total_num'] = Db::name('cart')->where($where)->sum('goods_num');
        session('cart_num', $res['total_num'] ?? 0);
        return json($res);
    }

    #购物车模块开始
    public function index()
    {
        if (Request::isAjax()) {
            $map = [];
            if ($this->user_id > 0) {
                $map[] = ['a.user_id', '=', $this->user_id];
            } else {
                $map[] = ['a.session_id', '=', $this->session_id];
            }

            //刷新购物车选中状态与数量
            $select_arr = input('post.select/a');
            $num_arr    = input('post.num/a');
            if ($num_arr) {
                $cartList = Db::name('cart a')->where($map)
                    ->join('goods b', 'a.goods_id=b.goods_id')
                    ->join('products c', 'a.product_id=c.product_id', 'LEFT')
                    ->field('a.id,a.goods_id,a.goods_num,a.product_id,a.selected,b.goods_number,c.product_number')
                    ->select();
                if ($cartList) {
                    $cartList = array_combine(array_column($cartList, 'id'), $cartList);
                }

                foreach ($num_arr as $id => $num) {
                    $data = [
                        'goods_num' => $num < 1 ? 1 : $num,
                        'selected'  => ($select_arr[$id] == 'false' || $select_arr[$id] == '0') ? 0 : 1
                    ];
                    if ($cartList[$id]['goods_num'] == $data['goods_num'] && $cartList[$id]['selected'] == $data['selected'])
                        continue;

                    //库存检查
                    if (is_numeric($cartList[$id]['product_number'])) {
                        if ($data['goods_num'] > $cartList[$id]['product_number']) {
                            return json(['msg' => '库存仅剩余：' . $cartList[$id]['product_number'], 'num' => $cartList[$id]['product_number'], 'id' => $id], 403);
                        }
                    } elseif ($data['goods_num'] > $cartList[$id]['goods_number']) {
                        return json(['msg' => '库存仅剩余：' . $cartList[$id]['goods_number'], 'num' => $cartList[$id]['goods_number'], 'id' => $id], 403);
                    }

                    Db::name('cart')->where("id", $id)->update($data);
                    unset($data);
                }
            }

            $storeList  = Db::name("cart")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_name,b.store_id")
                ->where($map)
                ->group("a.store_id")
                ->select();
            $goodsList  = Db::name("cart")->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
                ->join("products c", "a.product_id=c.product_id", "LEFT")
                ->field("a.*,b.goods_name,b.cat_id,b.goods_sn,b.brand_id,b.shop_price,b.market_price,original_img,
                c.tempvalue,c.product_sn,c.product_number,c.product_price,(a.goods_price*a.goods_num) as goodsxiaoji")
                ->where($map)
                ->select();
            $select_num = $sum_price = $sum_save_price = 0;
            foreach ($goodsList as $k => $v) {
                if ($v['selected'] == 1) {
                    $sum_price      += $v['goods_num'] * $v['goods_price'];
                    $sum_save_price += $v['goods_num'] * $v['market_price'];
                }
            }
            foreach ($storeList as $index => $item) {
                $ids      = array_column($goodsList, 'store_id');
                $valCount = array_count_values($ids);
                if ($item['store_id']) {
                    $storeList[$index]['goodsList'] = array_slice($goodsList, array_search($item['store_id'], $ids), $valCount[$item['store_id']]);
                } else {
                    $storeList[$index]['goodsList'] = array_slice($goodsList, array_search($item['store_id'], $ids), $valCount[0]);
                }
            }

            if ($goodsList) {
                $select_num = array_sum(array_column($goodsList, 'selected'));
            }

            $data['storeList']        = $storeList;
            $data['selectGoodsNum']   = $select_num;
            $data['selectGoodsTotal'] = $sum_price;
            $data['marketGoodsTotal'] = round($sum_save_price - $sum_price, 2);
            ajaxmsg('true', 1, $data);
        } else {
            return view();
        }
    }

    public function cartDelete()
    {
        $id  = input("id", 0);
        $map = [];
        if ($this->user_id > 0) {
            $map[] = ['user_id', '=', $this->user_id];
        } else {
            $map[] = ['session_id', '=', $this->session_id];
        }
        $map[] = ['id', '=', $id];
        $res   = Db::name("cart")->where($map)->delete();
        CartModel::cart_goods_calc($this->user_id, $this->session_id);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function topBarDelete()
    {
        $id  = input("id", 0);
        $map = [];
        if ($this->user_id > 0) {
            $map[] = ['user_id', '=', $this->user_id];
        } else {
            $map[] = ['session_id', '=', $this->session_id];
        }
        $map[] = ['id', '=', $id];
        Db::name("cart")->where($map)->delete();

        $cartList = CartModel::cartList($this->user_id, session_id());
        $this->assign('cartList', $cartList);
        return json(['cart_num' => $cartList['cart_num'], 'content' => $this->fetch('ajax/top_cart')]);
    }

    public function cartDeleteAll()
    {
        $map = [];
        if ($this->user_id > 0) {
            $map[] = ['user_id', '=', $this->user_id];
        } else {
            $map[] = ['session_id', '=', $this->session_id];
        }
        $map[] = ['selected', '=', 1];
        $res   = Db::name("cart")->where($map)->delete();
        CartModel::cart_goods_calc($this->user_id, $this->session_id);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function transferCollect()
    {
        $map = [];
        if ($this->user_id > 0) {
            $map[] = ['user_id', '=', $this->user_id];
        } else {
            $map[] = ['session_id', '=', $this->session_id];
        }
        $map[]               = ['selected', '=', 1];
        $list                = Db::name("cart")->where($map)->column("goods_id");
        $goods_collect_map   = [];
        $goods_collect_map[] = ['user_id', '=', $this->user_id];
        $goods_collect_map[] = ['goods_id', 'in', implode(',', $list)];
        Db::name("goods_collect")->where($goods_collect_map)->delete();
        foreach ($list as $k => $v) {
            Db::name("goods_collect")->insertGetId([
                'user_id'  => $this->user_id,
                'goods_id' => $v,
                'add_time' => time(),
            ]);
        }
        $res = Db::name("cart")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function goodsCollectAdd()
    {
        $id       = input("id", 0);
        $goods_id = input("goods_id", 0);
        $count    = Db::name("goods_collect")
            ->where("goods_id={$goods_id} and user_id={$this->user_id}")
            ->count("rec_id");
        if (!$count) {
            Db::name("goods_collect")->insertGetId([
                'user_id'  => $this->user_id,
                'goods_id' => $goods_id,
                'add_time' => time(),
            ]);
        }
        $map = [];
        if ($this->user_id > 0) {
            $map[] = ['user_id', '=', $this->user_id];
        } else {
            $map[] = ['session_id', '=', $this->session_id];
        }
        $map[] = ['id', '=', $id];
        $res   = Db::name("cart")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function guessYouLike()
    {
        $list = goods_random_data(24);
        ajaxmsg('true', 1, $list);
    }

    #购物车模块结束

    public function confirmOrder()
    {
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'cart/pay') !== false) {
            $this->redirect('member/order');
        }
        if (!$this->user_id) {
            $this->error('请登录后操作');
        }
        $coupon      = input('coupon/a', 0);
        $giftCardId  = input('gift_card/d', 0);
        $buyNow      = input('post.buyNow');
        $buyNowParam = '';
        $buyNowGoods = [];

        //立即购买参数验证,价格计算
        if ($buyNow) {
            if ($buyNow == __FUNCTION__) {
                $post = json_decode(input('post.buyNowParam'), true);
            } else {
                $post        = input('post.');
                $buyNowParam = json_encode($post);
            }
            $check = $this->validate($post, [
                'goods_id' => 'require|integer|gt:0',
                'number'   => 'require|integer|gt:0'
            ], [
                'goods_id' => '商品ID错误',
                'number'   => '购买数量错误'
            ]);
            if (true !== $check) {
                $this->error($check);
            }

            $virtualCart = CartModel::addCart($post['goods_id'], $post['number'], $this->user_id, $this->session_id, $post['attr_key'] ?? null, 'buyNow');
            if ($virtualCart['status'] < 0) {
                if ($this->request->isAjax()) {
                    //立即购买,选择优惠券后模拟购物车数据失败时,需返回json
                    return json($virtualCart['msg'], 403);
                } else {
                    //立即购买跳转错误页面
                    $this->error($virtualCart['msg']);
                }
            }
            $buyNowGoods[] = $virtualCart['result'];
        }
        $result = calculatePrice($this->user_id, $coupon, $buyNowGoods, $giftCardId);
        if ($result['status'] < 0) {
            if ($this->request->isAjax()) {
                return json($result['msg'], 403);
            } else {
                $this->redirect('member/order');
            }
        }

        if ($this->request->isPost()) {
            if (input('sub') == 'submit') {
                $req = input('post.');
                if (!isset($req['address_id'])) {
                    return json('请选择收货地址~', 403);
                }
                $req['ua'] = 'PC';
                $res       = (new OrderModel())->addOrder($this->user_id, $result['result'], $req, $buyNow);
                if ($res['status'] < 0) return json($res['msg'], 403);

                //选择货到付款,下单成功后跳转至订单详情,否则在线支付
                if (isset($req['pay_type']) && $req['pay_type'] == 'cod') {
                    $url = url('member/orderdetail', ['order_id' => $res['id']]);
                } else {
                    $url = url('cart/pay', ['sn' => $res['sn']]);
                }
                return json(['url' => $url]);
            } else {
                if ($this->request->isAjax()) return json($result['result']);
            }
        }

        $address = Db::name('user_address')->where(['user_id' => $this->user_id])->order('is_default DESC')->select();
        $region  = [];
        if ($address) {
            $region_ids = array_merge(array_column($address, 'province'), array_column($address, 'city'), array_column($address, 'district'));
            $region     = Db::name('region')->whereIn('region_id', $region_ids)->column('region_name', 'region_id');
        }

        $time       = time();
        $coupon     = [];
        $couponList = Db::name('coupon_list a')
            ->join('coupon b', 'a.coupon_id=b.coupon_id')
            ->where(['a.user_id' => $this->user_id, 'a.is_delete' => 0, 'a.order_id' => 0])
            ->whereIn('b.store_id', $result['result']['store_ids'])
            ->where('b.use_start_time < ' . $time)
            ->where('b.use_end_time > ' . $time)
            ->field('a.*,b.name,b.store_id,b.money,b.min_goods_amount,b.store_id,FROM_UNIXTIME(b.use_end_time,"%Y-%m-%d") AS use_end_time')
            ->select();

        foreach ($couponList as $index => $item) {
            if ($item['min_goods_amount'] <= $result['result']['data'][$item['store_id']]['goods_price']) {
                $coupon['valid'][$item['store_id']][] = $item;
            } else {
                $coupon['invalid'][] = $item;
            }
        }
        $card = $this->getGiftCard($this->user_id, 1, 100);

        $this->assign('card', $card);
        $this->assign('buyNow', $buyNow);
        $this->assign('buyNowParam', $buyNowParam);
        $this->assign('region', $region);
        $this->assign('address', $address);
        $this->assign('coupon', $coupon);
        $this->assign('orderInfo', $result['result']);

        return view();
    }

    public function getGiftCard($user_id, $page = 1, $pageSize = 3)
    {
        if (!$user_id) {
            $page     = input('page/d', 1);
            $pageSize = input('page/d', 3);
        }
        $card = (new Giftcard())->get_list($user_id, $page, $pageSize);
        if ($user_id) {
            return $card;
        }
        return json($card);
    }

    /**
     * 确认支付
     */
    public function pay()
    {
        $sn    = input('sn');
        $order = Db::name('order')->where('order_sn', $sn)->select();
        if (!$order) {
            $order = Db::name('order')->where('master_order_sn', $sn)->select();
        }

        $amount = array_sum(array_column($order, 'order_amount'));

        //已付款订单直接跳转到支付结果页面
        if ($order[0]['pay_status'] == 1) {
            $data = [
                'order_no'     => $sn,
                'total_amount' => $amount,
                'pay_name'     => $order[0]['pay_name']
            ];
            $this->redirect('cart/payResult', $data);
        }

        $order      = getRegionText($order[0]);
        $payWay     = Db::name('payment')->where('enable', 1)->whereRaw('find_in_set(3,`type`)')->order('sort DESC')->column('pay_id,pc_logo,pay_name', 'pay_code');
        $user_money = Db::name('user')->where('user_id', $this->user_id)->value('user_money');

        $this->assign('payWay', $payWay);
        $this->assign('order', $order);
        $this->assign('user_money', $user_money);
        $this->assign('amount', $amount);
        return view();
    }

    /**
     * 支付成功页面
     * @return View
     */
    public function payResult()
    {
        $sn               = input('order_no');
        $order            = Db::name('order')->where('order_sn', $sn)->find();
        $child_order_info = [];
        if (!$order) {
            $child_order_info = Db::name('order')->where('master_order_sn', $sn)->select();
            if (!$child_order_info) $this->redirect('index/index');//非法请求,跳转至首页
            $child_order_info[0] = getRegionText($child_order_info[0]);
        } else {
            $order = getRegionText($order);
        }

        $this->assign('order', $order);
        $this->assign('child_order', count($child_order_info));
        $this->assign('child_order_info', $child_order_info);
        return view();
    }

    /**
     * 确认订单,选择发票
     * @return \think\response\Json|View
     */
    public function ajaxInvoince()
    {
        if ($this->request->isPost()) {
            $result['type'] = input('invoice_type/d');
            if ($result['type'] == 0) {
                $invoice_id             = input('invoice_id/d');
                $result['inv_content']  = input('inv_content');
                $result['invoice_type'] = '普通发票（纸质）';
                $result['tax_id']       = input('tax_id');
                if ($invoice_id > 0) {
                    if (strlen($result['tax_id']) == 0) return json('请填写纳税人识别号', 403);
                    $row = Db::name('user_invoice')->where(['invoice_id' => $invoice_id, 'user_id' => $this->user_id])->find();
                    if (!$row) return json('您选择的发票信息不存在~', 403);
                    $result['inv_payee'] = $row['inv_payee'];
                    Db::name('user_invoice')->where('invoice_id', $invoice_id)->update(['tax_id' => $result['tax_id']]);
                } else {
                    $result['inv_payee'] = '个人';
                }
            } else {
                $info = Db::name('user_vat_invoice')->where('user_id', $this->user_id)->find();
                if ($info) {
                    $info   = getRegionText($info);
                    $result = [
                        'type' => 1,
                        'data' => $info,
                    ];
                } else {
                    return json('请先添加增值税发票信息', 403);
                }
            }
            return json($result);
        }

        $inv_content_list = Db::name('shop_config')->where('code', 'fapiaocon')->value('value');
        if ($inv_content_list) {
            $inv_content_list = explode(',', $inv_content_list);
        }
        $invoice     = Db::name('user_invoice')->where('user_id', $this->user_id)->select();
        $vat_invoice = Db::name('user_vat_invoice')->where('user_id', $this->user_id)->find();

        $this->assign('inv_content_list', $inv_content_list);
        $this->assign('invoice', $invoice);
        $this->assign('user_id', $this->user_id);
        $this->assign('vat_invoice', $vat_invoice);
        return view('ajax/invoice');
    }

    /**
     * 确认订单,编辑/新增发票信息
     * @return \think\response\Json
     */
    public function ajaxUpdateInvoince()
    {
        $validate = new Invoince();
        $act      = input('act');
        $data     = input('post.');

        if (!$validate->scene($act)->check($data)) {
            return json($validate->getError(), 403);
        }

        if ($act == 'update_name') {
            //普通发票
            $count = Db::name('user_invoice')->where('user_id', $this->user_id)->count();
            if ($data['invoice_id'] && $count >= 3) {
                return json('您最多可以添加3个公司发票!', 403);
            }
            if ($data['invoice_id'] > 0) {
                Db::name('user_invoice')->where(['user_id' => $this->user_id, 'invoice_id' => $data['invoice_id']])->update($data);
            } else {
                $data['user_id']    = $this->user_id;
                $data['invoice_id'] = Db::name('user_invoice')->insertGetId($data);
            }

            return json($data);
        } elseif ($act == 'update_vat') {
            //增值税发票
            $data['user_id']      = $this->user_id;
            $data['add_time']     = time();
            $data['audit_status'] = 1;//0=>1 无需审核 by alex

            $data['id'] = Db::name('user_vat_invoice')->insertGetId($data);

            if ($data['id']) {
                $this->assign('vat_invoice', $data);
                return view('ajax/add_vat_invoice');
            }
            return json('操作失败,请在用户中心添加发票信息', 404);
        } elseif ($act == 'del') {
            Db::name('user_invoice')->where(['user_id' => $this->user_id, 'invoice_id' => $data['tax_id']])->delete();
            return json();
        }
        return json('非法操作', 401);
    }

    public function gift_card()
    {
        $referer = $this->request->header('referer');
        if (is_null($referer) || strpos($referer, 'index/gift_card') === false) {
            $this->redirect('index/gift_card');
        }
        $data   = input('param.');
        $result = $this->validate($data, [
            'amount'   => 'require|integer|between:10,5000',
            'num'      => 'require|integer|egt:1',
            'pay_code' => 'require|integer',
        ], [
            'amount'   => '金额必须在10~5000元之间',
            'num'      => '至少购买一张',
            'pay_code' => '请选择支付方式',
        ]);
        if (true !== $result) {
            return json($result, 403);
        }
        $pay_code = $data['pay_code'] == 1 ? 'alipay' : 'weixin';
        $config   = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
        if (!$config) {
            ajaxmsg('支付参数错误', 0);
        }
        if (config('app_debug') === true) {
            $data['amount'] = 0.01;
        }
        $row = [
            'order_sn'     => get_order_sn('user_buycard'),
            'money'        => $data['amount'],
            'order_amount' => $data['amount'] * $data['num'],
            'user_id'      => $this->user_id,
            'order_type'   => $data['pay_code'],
            'num'          => $data['num'],
            'create_time'  => time(),
        ];
        $id  = Db::name("user_buycard")->insertGetId($row);
        if (!$id) {
            return json('支付订单生成失败', 405);
        }
        $payData = [
            'subject'      => '购买卡片支付订单',
            'out_trade_no' => $row['order_sn'],
            'amount'       => $row['order_amount'],
            'notify_url'   => Request::domain() . '/api/apicloud/buycardcheck',
        ];
        try {
            if ($pay_code == 'alipay') {
                $payData['return_url'] = url('home/member/giftcard', '', true, true);
                return Pay::run('AliWeb', $config, $payData);
            } else {
                error_reporting(0);
                //微信扫码
                $res = Pay::run('WxQrpay', $config, $payData);
                if ($res['ret'] == 0) {
                    return json(['result' => $res['data'], 'order_sn' => $row['order_sn']]);
                } else {
                    return json($res['msg'], 403);
                }
            }
        } catch (\Exception $e) {
            trace('充值失败：' . $e->getMessage());
            return json($e->getMessage(), 403);
        }
    }
}
