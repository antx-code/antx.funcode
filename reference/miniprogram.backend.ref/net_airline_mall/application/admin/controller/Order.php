<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://uu235.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

namespace app\admin\controller;

use app\common\model\OrderReturn;
use app\common\model\ReturnLog;
use app\common\model\Supplier;
use think\Db;
use think\facade\Request;
use think\facade\Config;
use app\common\model\Delivery as DeliveryModel;

class Order extends Common
{
    /**
     * table order
     * order_status：订单状态  0：待确认 1：已确认 2：待评价 3：已取消 4：已完成 5：退款 6：作废
     * pay_status：0：待支付，1：已付款
     * pay_code：  到付：cod，微信支付：weixin，支付宝：alipay
     * shipping_status: 发货状态   0：未发货 1：发货中 2：已发货 3：部分发货，4：已签收
     **/

    public function index()
    {
        if (Request::isGet()) {
            return view();
        }
        $map             = [];
        $keywords        = input('keywords');
        $keywords_goods  = input('keywords_goods');
        $keywords_sr     = input('keywords_sr');
        $consignee       = input('consignee');
        $store_id        = input('store_id/d', 0);
        $start_time      = input('start_time');
        $end_time        = input('end_time');
        $share_man       = input('share_man');
        $download        = input('download');
        $order_prom_type = input('order_prom_type', 0);
        if ($store_id > 0) {
            $map[] = ['a.store_id', 'gt', $store_id];
        } else {
            $map[] = ['a.store_id', 'eq', $store_id];
        }
        if ($keywords) {
            $map[] = ['a.order_sn|a.master_order_sn', "like", "%$keywords%"];
        }
        if ($consignee) {
            $map[] = ['a.consignee|a.mobile', "like", "%$consignee%"];
        }
        if ($share_man) {
            $user = Db::name('user')->where('user_name|nick_name|mobile', 'LIKE', '%' . $share_man . '%')->field('user_id,user_name,nick_name,mobile')->find();
            if (!$user) {
                ajaxmsg('未搜索到分享人，请确认用户名或手机号', 0);
            }
            $map[] = ['a.share_man', '=', $user['user_id']];
        }
        if ($order_prom_type > 0) {
            $map[] = ['a.order_prom_type', '=', $order_prom_type];
        }

        if ($end_time) {
            $end_time = strtotime($end_time) + 60;
            if ($end_time === false) ajaxmsg('查询结束时间错误', 0);
            $map[] = ['a.pay_time', '<=', $end_time];
        }
        if ($start_time) {
            $start_time = strtotime($start_time);
            if ($start_time === false) ajaxmsg('查询开始时间错误', 0);
            $map[] = ['a.pay_time', '>', $start_time];
        }

        $order_status    = input("order_status/d", 100);
        $pay_status      = input("pay_status/d", 100);
        $shipping_status = input("shipping_status/d", 100);
        if ($order_status < 100) {
            $map[] = ['a.order_status', 'eq', $order_status];
        }
        if ($pay_status < 100) {
            $map[] = ['a.pay_status', 'eq', $pay_status];
        }
        if ($shipping_status < 100) {
            if ($shipping_status == 0) {
                $map[] = ['a.shipping_status', '=', 0];
            } else {
                $map[] = ['a.shipping_status', '>', 1];
            }
        }
        //按商品名称、编号搜索
        if ($keywords_goods) {
            $ids   = Db::name('order_goods')->where('goods_name|goods_sn', 'LIKE', '%' . $keywords_goods . '%')->column('order_id');
            $map[] = ['order_id', 'IN', array_unique($ids)];
        }
        //按供应商搜索
        if ($keywords_sr) {
            $sr_id = Supplier::where('name', 'LIKE', '%' . $keywords_sr . '%')->value('id');
            if (!$sr_id) ajaxmsg('未匹配到供应商【' . $keywords_sr . '】', 0);
            $ids   = Db::name('order_goods')->where('sr_id', $sr_id)->column('order_id');
            $map[] = ['order_id', 'IN', array_unique($ids)];
        }
        if ($download) {
            $this->exportExcel($map);
        }

        $orderList = Db::name("order")->alias("a")
            ->join("store b", "a.store_id=b.store_id", "LEFT")
            ->join("user c", "c.user_id=a.user_id", "LEFT")
            ->field("a.*,_source as from_source,b.seller_id,b.store_name,c.user_name")
            ->where($map)
            ->order("order_id desc")
            ->page($this->page, $this->rows)
            ->select();
        if ($orderList) {
            $goodsList = Db::name('order_goods')->alias("a")
                ->join("brand b", "a.brand_id=b.brand_id", "LEFT")
                ->field("a.*,b.brand_name")
                ->where('a.order_id', 'IN', array_column($orderList, 'order_id'))
                ->order('a.order_id desc')
                ->select();
            foreach ($orderList as $k => $v) {
                if (is_numeric($v['province']) && $v['province'] > 0) {
                    $orderList[$k] = getRegionText($v);
                }
                $ids                         = array_column($goodsList, 'order_id');
                $valCount                    = array_count_values($ids);
                $orderList[$k]['goods_list'] = array_slice(
                    $goodsList,
                    array_search($v['order_id'], $ids),
                    $valCount[$v['order_id']]
                );
            }
        }
        $total = Db::name("order")->alias("a")
            ->join("store b", "a.store_id=b.store_id", "LEFT")
            ->join("user c", "c.user_id=a.user_id", "LEFT")
            ->where($map)
            ->count();
        if ($share_man) {
            ajaxMsg('账号:' . $user['user_name'] . '<br>昵称:' . $user['nick_name'] . '<br>手机号:' . $user['mobile'], 2, ['list' => $orderList, 'total' => $total]);
        }
        ajaxmsg('true', 1, ['list' => $orderList, 'total' => $total]);
    }

    public function orderUpdate()
    {
        $map          = [];
        $order_status = input('order_status');
        $ids          = input('ids');
        $pk           = Db::name("order")->getPk();#获取主键
        $map[]        = [$pk, 'in', $ids];
        $res          = Db::name("order")->where($map)->update(['order_status' => $order_status]);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function orderDelete()
    {
        $pageparm = $this->getPageparm();
        $pk       = Db::name("order")->getPk();#获取主键
        $res      = Db::name("order")->where("$pk={$pageparm[$pk]}")->delete();
        Db::name("order_goods")->where("order_id={$pageparm['order_id']}")->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function orderAllDelete()
    {
        $map   = [];
        $ids   = input('ids');
        $pk    = Db::name("order")->getPk();#获取主键
        $map[] = [$pk, 'in', $ids];
        $res   = Db::name("order")->where($map)->delete();
        Db::name("order_goods")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function orderdetail()
    {
        $order_id = input("order_id", 0);
        $data     = [];
        $order    = Db::name("order")->field("*,order_amount as origin_order_amount")->where('order_id', $order_id)->find();
        if ($order && $order['invoice_type'] > 0 && $order['invoice_no'] > 0) {
            $data['vat_invoice'] = Db::name('user_vat_invoice')->where('id', $order['invoice_no'])->find();
        }
        $order['addressTemp'] = getAddressWithOder($order);
        $data['order']        = $order;
        $data['goods_list']   = Db::name("order_goods")
            ->field("*,(goods_num-send_number) as send_num")
            ->where('order_id', $order_id)->select();
        foreach ($data['goods_list'] as $index => $good) {
            if ($good['product_id']) {
                $data['goods_list'][$index]['goods_number'] = Db::name('products')->where(['product_id' => $good['product_id']])->value('product_number');
            } else {
                $data['goods_list'][$index]['goods_number'] = Db::name('goods')->where('goods_id', $good['goods_id'])->value('goods_number');
            }
        }
        $data['user']               = Db::name("user")->where('user_id', $order['user_id'])->find();
        $data['order_log']          = Db::name("order_log")
            ->where('order_id', $order_id)
            ->order("action_id desc")
            ->select();
        $data['delivery_wait_send'] = false;
        if ($order['shipping_status'] == 1) {
            $model                      = new DeliveryModel;
            $data['delivery_wait_send'] = $model->waitSend($order_id);
        }

        if (Request::isAjax()) {
            ajaxmsg('true', 1, $data);
        } else {
            $shipping           = Db::name("shipping")->where('enabled=1')->cache(true)->select();
            $open_delivery_time = Db::name("shop_config")->where('code', 'open_delivery_time')->cache(true)->value('value');
            $this->assign('order_id', $order_id);
            $this->assign('info', $data);
            $this->assign('shipping', $shipping);
            $this->assign('open_delivery_time', $open_delivery_time);
            return view();
        }
    }

    public function exportExcel($map)
    {
        set_time_limit(0);
        $orders         = Db::name('order')->alias('a')
            ->join('user b', 'a.share_man=b.user_id', 'LEFT')
            ->field('a.*,b.user_name,b.nick_name,b.mobile')
            ->where($map)->select();
        $title          = ['序号', '订单编号', '订单状态', '余额抵扣', '支付金额', '订单总额', '收件人', '收件人电话1', '收件人电话2', '收件地址', '商品信息', '下单时间', '支付时间', '支付方式', '是否开票', '分享人', '用户备注', '商家备注'];
        $data           = [];
        $orderStatus    = config('order_status');
        $payStatus      = config('pay_status');
        $shippingStatus = config('shipping_status');
        foreach ($orders as $index => $order) {
            $mobile    = Db::name('user')->where('user_id', $order['user_id'])->value('mobile');
            $goodsList = Db::name('order_goods')->where('order_id', $order['order_id'])->field('goods_name,goods_num,sr_id,sr_name')->select();
            $goods     = '';
            foreach ($goodsList as $idx => $item) {
                $goods .= $item['goods_name'] . '  x  ' . $item['goods_num'];
                if ($item['sr_id'] > 0) {
                    $goods .= '  供应商:【' . $item['sr_name'] . '】';
                }
                $goods .= PHP_EOL;
            }
            $address    = getAddressWithOder($order);
            $is_invoice = '';
            if ($order['invoice_type'] == 1) {
                $is_invoice = '增值税专用发票';
            } else if ($order['invoice_title']) {
                $is_invoice = '普通发票';
            }
            $share_name = ($order['user_name'] ?? '' . ' ' . $order['nick_name'] ?? $order['nick_name'] . ' ' . $order['mobile'] ?? '');
            if ($order['abnormal']) {
                $status = '订单异常：' . $order['abnormal'];
            } else if (in_array($order['order_status'], [3, 5, 6])) {
                $status = '订单异常：' . $orderStatus[$order['order_status']];
            } else {
                $status = $payStatus[$order['pay_status']] . ',' . $shippingStatus[$order['shipping_status']];
            }
            $data[] = [
                'order_id'     => $order['order_id'],
                'order_sn'     => $order['order_sn'],
                'status'       => $status,
                'user_money'   => $order['user_money'],
                'order_amount' => $order['order_amount'],
                'total_amount' => $order['total_amount'],
                'consignee'    => $order['consignee'],
                'mobile'       => $order['mobile'],
                'mobile2'      => $mobile,
                'address'      => $address,
                'goods_detail' => $goods,
                'add_time'     => date('Y-m-d H:i:s', $order['add_time']),
                'pay_time'     => $order['pay_time'] > 0 ? date('Y-m-d H:i:s', $order['pay_time']) : '',
                'pay_name'     => $order['pay_name'],
                'is_invoice'   => $is_invoice,
                'share_name'   => $share_name,
                'user_note'    => $order['user_note'],
                'admin_note'   => $order['admin_note']
            ];
        }
        $res = toExcel($title, $data, date('Y-m-d_H-i-s'));
        if ($res !== true) {
            dump($res);
        };
        exit;

        //FIXME 修复地址不带区域的订单
        //$count  = 0;
        //$ids    = [];
        //foreach ($orders as $index => $order) {
        //    if ($order['_source'] == '小程序' && !$order['regionname']) {
        //        $address = Db::name('user_address')->where('user_id', $order['user_id'])->find();
        //        if (!$address['province'] && $address['regionname']) {
        //            Db::name('order')->where('order_id', $order['order_id'])->update(['regionname' => $address['regionname']]);
        //            $count++;
        //        }
        //    }
        //    //if ($order['province'] == 0 && !$order['regionname']) {
        //    //    array_push($ids, $order['order_id']);
        //    //    $count++;
        //    //}
        //}
        //return json($count);
    }

    public function orderUpstatus()
    {
        $order_id        = input('order_id');
        $order_status    = input('order_status');
        $shipping_status = input('shipping_status');
        $pay_status      = input('pay_status');
        $remark          = input('remark');
        $map             = [];
        $map[]           = ['order_id', '=', $order_id];
        $res             = Db::name("order")->where($map)->update([
            'order_status'    => $order_status,
            'shipping_status' => $shipping_status,
            'pay_status'      => $pay_status,
        ]);
        $order           = Db::name("order")->where("order_id={$order_id}")->find();
        $msg             = '更新订单状态值：' . $order_status . '，更新发货状态值：' . $shipping_status . '，更新支付状态值：' . $pay_status;
        if ($remark) {
            $msg = $remark . '<br>' . $msg;
        }
        $admin_user = $this->get_admin_user();
        orderLog($order, '更改订单状态', $msg, $admin_user['id'], $admin_user['username'], 1);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function abnormal()
    {
        $order_id = input('order_id/d');
        $status   = input('status');
        $remark   = input('remark');
        if (!$remark) {
            ajaxmsg('异常订单，请填写操作备注', 0);
        }
        if (is_numeric($status)) {
            //废除订单
            $res = Db::name("order")->where('order_id', $order_id)->update(['order_status' => $status, 'abnormal' => '']);
            $msg = '订单废除：' . $remark;
        } else {
            //恢复正常
            $res = Db::name("order")->where('order_id', $order_id)->update(['abnormal' => '']);
            $msg = $status . '<br>恢复正常：' . $remark;
        }
        if ($res) {
            $order      = Db::name("order")->where('order_id', $order_id)->find();
            $admin_user = $this->get_admin_user();
            orderLog($order, '异常订单处理', $msg, $admin_user['id'], $admin_user['username'], 1);
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #快递查询
    public function kuaidiQuery()
    {
        $data       = [];
        $JuHeConfig = Config::get('JuHeConfig');
        $queryData  = [
            #'com' => $order_supplier['shipping_com'],
            #'no' => $order_supplier['shipping_no'],
            'com' => 'sf',
            'no'  => '764227550954',
            'key' => $JuHeConfig['AppKey'],
        ];
        $arr        = json_decode(curl_post($JuHeConfig['cURL'], $queryData), 320);
        if ($arr['resultcode'] == 200) {
            $data['code']   = $arr['resultcode'];
            $data['reason'] = $arr['reason'];
            $data['result'] = $arr['result'];
        } else {
            $data['code']   = 500;
            $data['reason'] = '失败的返回';
            $data['result'] = [];

        }
        return $data;
    }

    #缺货登记
    public function booking()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['goods_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("booking_goods")
                ->where($map)
                ->page($page, $rows)
                ->order("bk_id desc")
                ->select();
            $total = Db::name("booking_goods")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    /**
     * 退款列表
     * @return \think\response\Json|\think\response\View
     */
    public function refund_list()
    {
        if (Request::isPost()) {
            $keywords = input('keywords');
            $store_id = input('store_id');
            $page     = input('page/d', 1);
            $pageSize = input('rows/d', 10);

            $res = (new OrderReturn)->getListByStore($store_id, $page, $pageSize, $keywords);
            return json($res);
        }
        $self_shop_title = Db::name('shop_config')->where('code', 'shop_name')->cache(true)->value('value');
        $this->assign('self_shop_title', $self_shop_title);
        return view();
    }

    /**
     * 退款详情
     * @return \think\response\Json|\think\response\View
     */
    public function refund_detail()
    {
        $rid = input('return_id/d');
        if (Request::isAjax()) {
            $model  = new OrderReturn;
            $detail = $model->getDetail($rid);
            if (Request::isPost()) {
                $status       = input('status/d');
                $refund_way   = input('refund_way/d');
                $gap          = input('gap/f');
                $admin_remark = input('admin_remark');
                try {
                    Db::startTrans();
                    $res = $model->refund($detail, $status, 2, $admin_remark, $refund_way, $gap);
                    if ($res !== true) {
                        throw new \Exception($res);
                    }
                    Db::commit();
                    return json();
                } catch (\Exception $e) {
                    Db::rollback();
                    trace($e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString(), 'error');
                    $res = $e->getMessage();
                    return json($res, 403);
                }
            }

            return json($detail);
        }

        return view();
    }

    /***
     * 投诉
     * @return \think\response\View
     */
    public function appeal()
    {
        if (Request::isAjax()) {
            $page     = input('page', 1);
            $rows     = input('rows', 10);
            $keywords = input('keywords');
            $model    = new ReturnLog;
            $map      = [
                ['a.is_appeal', '=', 1],
                ['a.user_type', '=', 0],
            ];
            $count    = $model->alias('a')->where($map)->count();
            if ($keywords) {
                $map[] = ['b.order_sn', 'LIKE', '%' . $keywords . '%'];
            }
            $list  = $model->alias('a')
                ->join('return b', 'a.rid=b.return_id')
                ->where($map)
                ->order('a.id DESC')
                ->page($page, $rows)->select();
            $store = [];
            if ($list) {
                $sid = array_unique($list->column('store_id'));
                if (array_sum($sid)) {
                    $store = Db::name('store')->whereIn('store_id', $sid)->column('store_name', 'store_id');
                }
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $count, 'store' => $store]);
        }
        return view();
    }

    #投诉管理
    public function complaint()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input('keywords'));
            if ($keywords) {
                $map[] = ['store_name', 'like', "%{$keywords}%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("complaint")
                ->where($map)
                ->page($page, $rows)
                ->order("complaint_id desc")
                ->select();
            $total = Db::name("complaint")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        }

        return view();
    }

    public function complaintadd()
    {
        $complaint_id = input("complaint_id", 0);
        $infoColumn   = gettableColumn('complaint');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($complaint_id > 0) {
                $info = Db::name("complaint")->where("complaint_id={$complaint_id}")->find();
            } else {
                $info = gettableColumn('complaint');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['complaint_id'] > 0) {
                $res = Db::name('complaint')->update($pageparm);
            } else {
                $res = Db::name('complaint')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #发货单管理
    public function delivery()
    {
        if (Request::isAjax()) {
            $map       = [];
            $keywords  = input("keywords");
            $mobile    = input("mobile");
            $consignee = input("consignee");
            $store_id  = input('store_id', 0);
            if ($store_id > 0) {
                $map[] = ['a.store_id', '>', 0];
            } else {
                $map[] = ['a.store_id', '=', 0];
            }
            if ($keywords) {
                $map[] = ['a.order_sn', '=', $keywords];
            }
            if ($mobile) {
                $map[] = ['a.mobile', '=', $mobile];
            }
            if ($consignee) {
                $map[] = ['a.consignee', 'LIKE', '%' . $consignee . '%'];
            }
            $page  = input('page/d', 1);
            $rows  = input('rows/d', 10);
            $list  = Db::name("delivery_order")->alias("a")
                ->join("order b", "a.order_id=b.order_id", "LEFT")
                ->join("store c", "a.store_id=c.store_id", "LEFT")
                ->field("a.*,b.add_time as order_time,c.store_name")
                ->where($map)
                ->page($page, $rows)
                ->order("a.delivery_id desc")
                ->select();
            $total = Db::name("delivery_order")->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function makedelivery()
    {
        $order_id      = input("order_id", 0);
        $action_remark = input("action_remark", '');
        if (Request::isAjax()) {
            $admin_user = $this->get_admin_user();
            $order      = Db::name("order")->where('order_id', $order_id)->find();
            if (!$order) {
                ajaxmsg('数据异常', 0);
            }

            $data          = input('post.');
            $data['goods'] = json_decode($data['goods'], true);
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
                'action_user'   => $admin_user['username'],
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
                'status'        => 0,
            ];
            try {
                Db::startTrans();
                $delivery_id      = Db::name('delivery_order')->insertGetId($delivery_row);
                $msg              = '发货单流水号：【' . $delivery_id . '】';
                $delivery_goods   = [];
                $order_goods_Data = [];

                $casestr  = '';
                $wherestr = '';
                foreach ($data['goods'] as $index => $goods) {
                    if ($goods['send_num'] == 0) {
                        continue;
                    }
                    //预防多人操作发货错误
                    $sended = Db::name('order_goods')->where('rec_id', $goods['rec_id'])->value('send_number');
                    if ($goods['send_number'] != $sended) {
                        throw new \Exception($goods['goods_name'] . ' 已出库数量变化，请确认~');
                    }
                    if ($goods['send_num'] > $goods['goods_num'] - $goods['send_number']) {
                        throw new \Exception($goods['goods_name'] . '已发货:' . $goods['send_number'] . '<br>可再发货:' . ($goods['goods_num'] - $goods['send_number']));
                    }
                    $send_number_Temp       = $goods['send_number'] + $goods['send_num'];
                    $casestr                .= " WHEN '{$goods['rec_id']}' THEN '{$send_number_Temp}' ";
                    $wherestr               .= "'" . $goods['rec_id'] . "'" . ',';
                    $delivery_goods[$index] = [
                        'delivery_id'       => $delivery_id,
                        'goods_id'          => $goods['goods_id'],
                        'order_goods_recid' => $goods['rec_id'],
                        'product_id'        => $goods['product_id'],
                        'tempvalue'         => $goods['tempvalue'],
                        'goods_name'        => $goods['goods_name'],
                        'goods_sn'          => $goods['goods_sn'],
                        'send_num'          => $goods['send_num'],
                    ];
                    $msg                    .= '<br>【商品货号：' . $goods['goods_sn'] . '，发货：' . $goods['send_num'] . '件】';
                }
                $wherestr = trim($wherestr, ',');
                $prefix   = Config::get('database.prefix');
                $sql      = "update {$prefix}order_goods 
                        set send_number = 
                        case rec_id 
                        {$casestr} 
                        end 
                        where rec_id in ({$wherestr})";
                if (count($delivery_goods)) {
                    Db::name('delivery_goods')->insertAll($delivery_goods);
                    Db::execute($sql);
                } else {
                    Db::name("delivery_order")->delete($delivery_id);
                    throw new \Exception('请填写发货商品数量');
                }
                if ($action_remark) {
                    $msg .= '<br>' . $action_remark;
                }
                Db::name('order')->where('order_id', $order['order_id'])->update(['shipping_status' => 1]);
                orderLog($order, '生成发货单', $msg, $admin_user['id'], $admin_user['username'], 0);
                Db::commit();
                ajaxmsg('生成成功', 1);
            } catch (\Exception $e) {
                Db::rollback();
                ajaxmsg($e->getMessage(), 0);
            }


        } else {
            $this->assign('order_id', $order_id);
            return view();
        }
    }

    public function delivery_del()
    {
        $id       = input('post.delivery_id/d');
        $model    = new DeliveryModel;
        $order_id = $model->del($id);
        if (is_string($order_id)) {
            ajaxmsg($order_id, 0);
        } else {
            $admin_user = $this->get_admin_user();
            $order      = Db::name('order')->where('order_id', $order_id)->find();
            orderLog($order, '删除发货单', '', $admin_user['id'], $admin_user['username'], 0);
            ajaxmsg('删除完成', 1);
        }
    }

    /**
     * 一键发货
     */
    public function makeonekey()
    {
        $order_id      = input("order_id", 0);
        $action_remark = input("action_remark", '');
        $shipping_no   = input("shipping_no");
        $admin_user    = $this->get_admin_user();
        $order         = Db::name("order")->where('order_id', $order_id)->find();
        if (!$order) {
            ajaxmsg('数据异常', 0);
        }

        $delivery_id = Db::name('delivery_order')->where('order_id', $order_id)->value('delivery_id');
        if ($delivery_id > 0) {
            ajaxmsg('已经发货或部分发货，不支持一键发货操作', 0);
        }

        $order_goods = Db::name("order_goods")->where('order_id', $order_id)->select();

        //物流顺序原则: 1.选购商品中使用运费模板的物流id; 2.最近一次发货的物流id; 3.留空!
        $shipping_id = $order['shipping_id'];
        if ($shipping_id == 0) {
            $shipping_id = input("shipping_id/d");
        }
        $shipping = Db::name("shipping")->where('shipping_id', $shipping_id)->find();
        if ($shipping) {
            $shipping_name = $shipping['shipping_name'];
        } else {
            ajaxmsg('请选择物流公司', 0);
        }

        $delivery_row   = [
            'delivery_sn'   => get_delivery_sn(),
            'order_sn'      => $order['order_sn'],
            'order_id'      => $order['order_id'],
            'store_id'      => $order['store_id'],
            'shipping_no'   => $shipping_no,
            'shipping_id'   => $shipping_id,
            'shipping_name' => $shipping_name,
            'shipping_fee'  => $order['shipping_price'],
            'add_time'      => time(),
            'user_id'       => $order['user_id'],
            'action_user'   => $admin_user['username'],
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
            'status'        => 1,
        ];
        $delivery_id    = Db::name('delivery_order')->insertGetId($delivery_row);
        $msg            = '一键发货单流水号：【' . $delivery_id . '】';
        $delivery_goods = [];

        foreach ($order_goods as $index => $goods) {
            $delivery_goods[] = [
                'delivery_id' => $delivery_id,
                'goods_id'    => $goods['goods_id'],
                'product_id'  => $goods['product_id'],
                'tempvalue'   => $goods['tempvalue'],
                'goods_name'  => $goods['goods_name'],
                'goods_sn'    => $goods['goods_sn'],
                'send_num'    => $goods['goods_num'],
            ];
            $msg              .= '<br>【商品货号：' . $goods['goods_sn'] . '，发货：' . $goods['goods_num'] . '件】';
        }
        Db::name('order_goods')->where('order_id', $order['order_id'])->update(['is_send' => 1, 'send_number' => Db::raw('goods_num')]);
        Db::name('delivery_goods')->insertAll($delivery_goods);

        if ($action_remark) {
            $msg .= '<br>' . $action_remark;
        }

        //更新订单发货状态
        $orderData = ['shipping_status' => 2, 'shipping_time' => time()];
        if ($shipping_id != $order['shipping_id'] || $shipping_name != $order['shipping_name']) {
            $orderData['shipping_id']   = $shipping_id;
            $orderData['shipping_name'] = $shipping_name;
        }
        Db::name('order')->where('order_id', $order['order_id'])->update($orderData);

        $order['shipping_status'] = 2;
        orderLog($order, '一键生成发货单', $msg, $admin_user['id'], $admin_user['username'], 1);
        ajaxmsg('发货成功', 1);
    }

    /**
     * 发货单详情页,发货操作
     */
    public function deliveryUpdate()
    {
        $act = input('act');
        $res = 0;
        switch ($act) {
            case 'updateStatusAll':#批量发货
                $map   = [];
                $ids   = input('ids');
                $pk    = Db::name("delivery_order")->getPk();#获取主键
                $map[] = [$pk, 'in', $ids];
                $res   = Db::name("delivery_order")->where($map)->update(['status' => 1]);
                $rows  = spUpdate($ids);
                break;
            case 'updatestatus':#单个发货单发货
                $order_id    = input("order_id/d", 0);
                $delivery_id = input("delivery_id", 0);
                $shipping_id = input("shipping_id/d", 0);
                $shipping_no = input('shipping_no', '');
                if (!$shipping_id) {
                    ajaxmsg('请选择物流公司', 0);
                }
                if (strlen($shipping_no) < 3) {
                    ajaxmsg('物流快递单号错误', 0);
                }
                $deliveryModel = new DeliveryModel();
                $res           = $deliveryModel->send($order_id, $delivery_id, $shipping_id, $shipping_no);
                if ($res) {
                    $admin_user = $this->get_admin_user();
                    $order      = Db::name('order')->where('order_id', $order_id)->find();
                    $action     = '订单ID：' . $order_id . '发货单ID：' . $delivery_id . ' 发货';
                    $res        = orderLog($order, $action, input('action_remark'), $admin_user['id'], $admin_user['username']);
                }
                //$rows       = spUpdate($delivery_id);
                break;
            case 'shippingno':#更新发货单物流单号
                $order_id                          = input("order_id/d", 0);
                $delivery_id                       = input("delivery_id/d", 0);
                $shipping_no                       = input("shipping_no");
                $res                               = Db::name("delivery_order")->where('delivery_id', $delivery_id)->update(['shipping_no' => $shipping_no]);
                $admin_user                        = $this->get_admin_user();
                $order_logData                     = [];
                $order_logData['action_remark']    = '更新物流单号';
                $order_logData['order_id']         = $order_id;
                $order_logData['user_type']        = 0;
                $order_logData['action_user_id']   = $admin_user['id'];
                $order_logData['action_user_name'] = $admin_user['username'];
                $order_logData['status_desc']      = '订单ID：' . $order_id . '发货单ID：' . $delivery_id . ' 更新物流单号：' . $shipping_no;
                $order_logData['log_time']         = time();
                $res                               = Db::name("order_log")->insertGetId($order_logData);
                break;
        }
        if ($res > 0 || $rows > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function deliverydetail()
    {
        $order_id    = input("order_id", 0);
        $delivery_id = input("delivery_id", 0);
        if (Request::isAjax()) {
            $data                   = [];
            $order                  = Db::name("order")->field("*,order_amount as origin_order_amount")->where("order_id={$order_id}")->find();
            $data['delivery_order'] = Db::name("delivery_order")->where("delivery_id={$delivery_id}")->find();
            $order['addressTemp']   = getAddressWithOder($order);
            $data['order']          = $order;

            $data['goods_list'] = Db::name("delivery_goods")->alias("a")
                ->join("order_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->where("a.delivery_id={$delivery_id} and b.order_id={$order_id}")
                ->group(' a.rec_id')
                ->select();
            $data['user']       = Db::name("user")->where("user_id={$order['user_id']}")->find();
            $data['order_log']  = Db::name("order_log")
                ->where("order_id={$order_id}")
                ->order("action_id desc")
                ->select();
            ajaxmsg('true', 1, $data);
        } else {
            $shipping = Db::name("shipping")->where('enabled=1')->cache(true)->column('shipping_name,shipping_code', 'shipping_id');

            $this->assign('order_id', $order_id);
            $this->assign('shipping', $shipping);
            $this->assign('delivery_id', $delivery_id);
            return view();
        }
    }
}
