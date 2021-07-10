<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/19 13:29
 */

namespace app\common\model;

use app\home\model\Giftcard;
use think\facade\Env;
use think\Model;
use think\Db;

class OrderReturn extends Model
{
    protected $name = 'return';
    protected $pk = 'return_id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'addtime';
    protected $append = ['show_status', 'show_time', 'addtime'];

    public function getAddtimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getShowStatusAttr($value, $data)
    {
        $text = ['-2' => '用户取消', '-1' => '商家不同意', '0' => '待审核', '1' => '商家同意', '2' => '已发货', '3' => '已收货', '4' => '换货完成', '5' => '退款完成', '6' => '申诉仲裁'];
        return $text[$data['status']];
    }

    public function getShowTimeAttr($value, $data)
    {
        switch ($data['status']) {
            case -2:
                $time = $data['canceltime'];//取消时间
                break;
            case 1:
            case -1:
                $time = $data['checktime'];//审核时间
                break;
            case 5:
                $time = $data['refundtime'];//退款时间
                break;
            default:
                $time = $data['addtime'];
        }
        return date('Y-m-d H:i:s', $time);
    }

    public function makeRefundNo()
    {
        while (true) {
            $no    = 'R' . date('YmdHis') . mt_rand(1000, 9999);
            $count = $this->where('refund_no', $no)->count();
            if ($count == 0) break;
        }
        return $no;
    }

    public function getListByUser($uid, $page, $pageSize)
    {
        $map = [['a.user_id', '=', $uid]];
        return $this->getList($map, $page, $pageSize);
    }

    public function getListByStore($store_id, $page, $pageSize, $keywords = '')
    {
        $map = [];
        if (!is_numeric($store_id)) {
            $map[] = ['a.store_id', '>', 0];
        } else {
            $map[] = ['a.store_id', '=', $store_id];
        }
        return $this->getList($map, $page, $pageSize, $keywords);
    }

    protected function getList($map, $page, $pageSize, $keywords = '')
    {
        $count = $this->alias('a')->where($map)->count();
        if ($keywords) {
            $map[] = ['a.order_sn', 'LIKE', '%' . $keywords . '%'];
        }
        $list = $this->alias('a')
            ->where($map)
            ->leftJoin('store b', 'a.store_id=b.store_id')
            ->order('a.return_id DESC')
            ->field('a.*,b.store_name')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        if ($list) {
            $goods = Db::name('return_goods a')
                ->join('order_goods b', 'a.rec_id=b.rec_id')
                ->whereIn('a.return_id', array_column($list, 'return_id'))
                ->field('a.*,b.goods_img,b.goods_id,b.goods_name,b.tempvalue')
                ->order('a.return_id DESC')
                ->select();
            $list  = fixTree($list, $goods, 'return_goods', 'return_id');
        }
        return ['count' => $count, 'list' => $list, 'page' => $page, 'pageSize' => $pageSize];
    }

    public function getDetail($rid)
    {
        $row = self::get($rid);
        if ($row->store_id) {
            $row['store_name'] = Db::name('store')->where('store_id', $row->store_id)->value('store_name');
        }
        $row['goods'] = Db::name('return_goods a')
            ->join('order_goods b', 'a.rec_id=b.rec_id')
            ->where('return_id', $rid)
            ->select();
        $orderModel   = new \app\home\model\Order;
        $row['order'] = $orderModel->get($row->order_id);
        $row['log']   = ReturnLog::where('rid', $rid)->order('id DESC')->select();
        return $row;
    }

    public function refund($detail, $status, $user_type, $admin_remark, $refund_way = 0, $gap = 0)
    {
        $username = '系统';
        if ($user_type == 2) {
            $username = '管理员';
        }
        $row = [
            'rid'       => $detail->return_id,
            'username'  => $username,
            'user_type' => $user_type
        ];
        switch ($status) {
            case -1:
                //驳回
                $res            = (bool)$this->where('return_id', $detail->return_id)->update(['status' => -1, 'remark' => $admin_remark, 'checktime' => time()]);
                $row['title']   = '不同意退货';
                $row['content'] = json_encode(['备注说明' => $admin_remark], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ReturnLog::create($row);
                break;
            case 6:
                //管理员仲裁判定
                $res            = (bool)$this->where('return_id', $detail->return_id)->update(['status' => 0, 'remark' => $admin_remark]);
                $row['title']   = '系统仲裁';
                $row['content'] = json_encode(['判定结果' => $admin_remark], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $log            = ReturnLog::create($row);
                $appeal         = ReturnLog::where(['rid' => $detail->return_id, 'is_appeal' => 1])->order('id DESC')->find();
                if ($appeal) {
                    ReturnLog::where('id', $appeal->id)->update(['pid' => $log->id]);
                }
                break;
            case 5:
                //操作退款
                #1.修改订单商品的退款状态
                Db::name('order_goods')->whereIn('rec_id', array_column($detail['goods'], 'rec_id'))->update(['is_refund' => 2]);
                #2.修改退款商品的状态
                Db::name('return_goods')->where('return_id', $detail->return_id)->update(['status' => 2]);
                $user_money   = floatval($detail['order']->user_money);
                $card_price   = floatval($detail['order']->card_price);
                $order_amount = floatval($detail['order']->order_amount);

                $time = time();
                $rids = [$detail->return_id];

                if ($refund_way > 0) {
                    if ($detail->is_entire != 1) {
                        $res = '非整单退款，不支持原路退回';
                        break;
                    }
                    //原路退回
                    $pay_code = $detail['order']->pay_code;
                    if (!in_array($pay_code, ['weixin', 'alipay', 'gift_card'])) {
                        $res = '支付方式非微信、支付宝，无法原路退款';
                        break;
                    }

                    $amount = $real_refund = floatval($detail->refund);

                    //不退物流费用
                    if ($order_amount > 0) {
                        if ($order_amount > $amount) {
                            $order_amount = $amount;//付款金额大于退款金额
                            $amount       = 0;
                        } else {
                            $amount -= $order_amount;
                        }
                    }

                    if ($amount > 0 && $card_price > 0) {
                        if ($card_price > $amount) {
                            $card_price = $amount;
                            $amount     = 0;
                        } else {
                            $amount -= $card_price;
                        }
                    }

                    if ($amount > 0 && $user_money > 0) {
                        if ($user_money > $amount) {
                            $user_money = $amount;
                            $amount     = 0;
                        } else {
                            $amount -= $user_money;
                        }
                    }

                    if ($amount > 0) {
                        $res = '请确认,退款金额超出支付金额（在线支付+余额抵扣+礼品卡抵扣）';
                        break;
                    }

                    #3.扣除商家余额、结算.
                    if ($detail->store_id > 0) {
                        //TODO
                    }

                    /**
                     * TODO
                     * 4.追回/删除 订单活动赠送的优惠券
                     * 5.追回/删除 商品活动赠送的优惠券
                     * 6.追回订单活动赠送的积分
                     */
                    trace('==$user_money:' . $user_money . PHP_EOL . '$card_price:' . $card_price . PHP_EOL . '$order_amount:' . $order_amount . PHP_EOL . '$real_refund:' . $real_refund);
                    #7.混合支付退还余额,积分、优惠券不退
                    if ($user_money > 0) {
                        $res = (new \app\home\model\AccountLog)->accountLog($detail->user_id, $user_money, '订单退款', 0, 0, $detail->order_id, $detail->order_sn);
                        if ($res !== true) break;
                    }

                    #8.退回礼品卡金额
                    if ($card_price > 0) {
                        $res = (new Giftcard)->refund($detail['order']->use_card, $card_price, $detail->order_id, $detail->user_id);
                        if ($res !== true) {
                            break;
                        }
                    }

                    $refund_trade_no = '';
                    #8.支付原路退回
                    if ($order_amount > 0 && in_array($detail->order['pay_code'], ['weixin', 'alipay'])) {
                        $data    = [
                            'refund_no'  => $detail->refund_no,
                            'trade_no'   => $detail['order']->transaction_id,
                            'total_fee'  => $order_amount,
                            'refund_fee' => $order_amount,
                        ];
                        $methods = ['weixin' => 'WxRefund', 'alipay' => 'AliRefund'];
                        $config  = json_decode(Db::name('payment')->where('pay_code', $detail['order']->pay_code)->value('pay_config'), true);
                        if ($detail['order']->pay_code == 'weixin') {
                            $config['sslcert_path'] = Env::get('config_path') . 'apiclient_cert.pem';
                            $config['sslkey_path']  = Env::get('config_path') . 'apiclient_key.pem';
                        }
                        $result = \Payment\Pay::run($methods[$detail['order']->pay_code], $config, $data);
                        trace(var_export($result, true));
                        if ($result['ret'] != 0) {
                            $res = $result['msg'];
                            break;
                        }
                        $refund_trade_no = $result['data']['refund_id'];
                    }

                    #9.修改退款单状态
                    $res = (bool)$this->isUpdate(true)->save([
                        'status'            => 5,
                        'real_refund'       => $real_refund,
                        'user_money_refund' => $user_money,
                        'card_refund'       => $card_price,
                        'refund_type'       => 1,
                        'gap'               => $gap,
                        'refund_trade_no'   => $refund_trade_no,
                        'refundtime'        => $time,
                        'admin_remark'      => $admin_remark,
                        'online_refund'     => $order_amount//在线退款金额
                    ], ['return_id' => $detail->return_id]);

                    #10.记录退款日志
                    $row['user_id'] = session('admin_user.id');
                    $row['title']   = '订单退款';
                    $content        = '';
                    if ($user_money > 0) {
                        $content = '退款至余额' . $user_money . '元;';
                    }
                    if ($card_price > 0) {
                        $content .= '退款至礼品卡' . $card_price . '元;';
                    }
                    if ($order_amount > 0) {
                        $returnWay = ['weixin' => '微信', 'alipay' => '支付宝'];
                        $content   .= '退款至' . $returnWay[$detail->order['pay_code']] . $order_amount . '元.';
                    }
                    $row['content'] = json_encode(['退款信息' => $content], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $log            = ReturnLog::create($row);
                } else {
                    $amount      = round(floatval($detail->refund) + floatval($gap), 2);
                    $real_refund = $amount;
                    if ($real_refund < 0.01) {
                        $res = '退款金额错误';
                        break;
                    }
                    $map                = [
                        ['order_id', '=', $detail->order_id],
                        ['return_id', '<>', $detail->return_id],
                        ['status', '=', 5]
                    ];
                    $otherRefund        = $this->where($map)->field('return_id,real_refund,online_refund,user_money_refund,card_refund')->select();
                    $refundOnlineAmount = 0;//已成功 原路退款的金额
                    $refundUserMoney    = 0;//已成功 退款的余额
                    $refundCard         = 0;//已成功 退款至礼品卡的金额
                    $refundRealRefund   = 0;//已成功 退款的总金额
                    //$shipping_price     = floatval($detail['order']->shipping_price);//不退运费

                    //多次退款，累计退款余额计算
                    foreach ($otherRefund as $index => $item) {
                        $refundOnlineAmount += floatval($item->online_refund);
                        $refundUserMoney    += floatval($item->user_money_refund);
                        $refundCard         += floatval($item->card_refund);
                        $refundRealRefund   += floatval($item->real_refund);
                        array_push($rids, $item->return_id);
                    }
                    $orderBalance   = $user_money - $refundUserMoney;//剩余未退款的余额
                    $orderAmount    = $order_amount - $refundOnlineAmount;//剩余未退款的 在线支付金额
                    $orderCard      = $card_price - $refundCard;//剩余未退款的 礼品卡的金额
                    $maxAllowRefund = $orderBalance + $orderAmount + $orderCard;
                    trace('$orderBalance:' . $orderBalance . PHP_EOL . '$orderAmount:' . $orderAmount . PHP_EOL . '$maxAllowRefund:' . $maxAllowRefund . PHP_EOL . '$amount:' . $amount . PHP_EOL . '$maxAllowRefund:' . $maxAllowRefund);
                    if ($real_refund > $maxAllowRefund) {
                        $res = '此订单已退款 ' . $refundRealRefund . ' 元，本次最多可退款 ' . $maxAllowRefund . ' 元';
                        break;
                    }

                    //退款至余额
                    #3.扣除商家余额
                    if ($detail->store_id > 0) {
                        //TODO
                    }

                    #4.退还礼品卡
                    $refund_card_cash = 0;
                    if ($orderCard > 0) {
                        if ($orderCard > $amount) {
                            $refund_card_cash = $amount;
                            $amount           = 0;
                        } else {
                            $refund_card_cash = $orderCard;
                            $amount           -= $orderCard;
                        }
                        $res = (new Giftcard)->refund($detail['order']->use_card, $refund_card_cash, $detail->order_id, $detail->user_id);
                        if ($res !== true) {
                            break;
                        }
                    }

                    #5.修改用户余额
                    $refund_user_money = 0;
                    if ($amount > 0) {
                        $refund_user_money = $amount;
                        $res               = (new \app\home\model\AccountLog)->accountLog($detail->user_id, $refund_user_money, '订单退款', 0, 0, $detail->order_id, $detail->order_sn);
                        if ($res !== true) break;
                    }

                    #6.修改退款单状态
                    $this->isUpdate(true)->save([
                        'status'            => 5,
                        'real_refund'       => $real_refund,
                        'user_money_refund' => $refund_user_money,
                        'card_refund'       => $refund_card_cash,
                        'refund_type'       => 0,
                        'gap'               => $gap,
                        'refundtime'        => time(),
                        'admin_remark'      => $admin_remark
                    ], ['return_id' => $detail->return_id]);

                    #7.记录退款日志
                    $row['user_id'] = session('admin_user.id');
                    $row['title']   = '订单退款';
                    $content        = '';
                    if ($refund_user_money > 0) {
                        $content .= '退款至余额' . $refund_user_money . '元;';
                    }
                    if ($refund_card_cash > 0) {
                        $content .= '退款至礼品卡' . $refund_card_cash . '元.';
                    }
                    $row['content'] = json_encode(['退款信息' => $content], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $log            = ReturnLog::create($row);
                }

                #检查并修改订单状态全部退货
                if ($detail->is_entire) {
                    Db::name('order')->where('order_id', $detail->order_id)->update(['order_status' => 5]);
                } else {
                    $rec_count    = Db::name('order_goods')->where('order_id', $detail->order_id)->count();
                    $return_count = Db::name('return_goods')->whereIn('return_id', $rids)->count();
                    if ($rec_count == $return_count) {
                        Db::name('order')->where('order_id', $detail->order_id)->update(['order_status' => 5]);
                    }
                }
                $res = true;

                $appeal = ReturnLog::where(['rid' => $detail->return_id, 'is_appeal' => 1])->order('id DESC')->find();
                if ($appeal) {
                    ReturnLog::where('id', $appeal->id)->update(['pid' => $log->id]);
                }

                break;
            default:
                $res = '参数错误';
        }

        return $res;
    }
}