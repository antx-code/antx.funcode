<?php

/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/4 15:51
 */

namespace app\seller\controller;

use Symfony\Component\Translation\Dumper\DumperInterface;
use think\Db;

use app\seller\model\Order;

class Index extends Base
{
    public function index()
    {
        $quick_link = session('seller')['seller_quicklink'];
        if ($this->request->isPost()) {
            $status = input('post.status/d');
            $action = input('post.action');
            $text   = input('post.text');
            if ($status > 0) {
                unset($quick_link[$action]);
            } else {
                $quick_link[$action] = $text;
            }
            session('seller.seller_quicklink', $quick_link);
            $quick_link = json_encode($quick_link, JSON_UNESCAPED_UNICODE);
            Db::name('seller')->where('id', session('seller')['id'])->update(['seller_quicklink' => $quick_link]);
            return json($action);
        }
        //dump(session('seller')['menu']);die;
        $store       = Db::name('store')->where('store_id', $this->store_id)->find();
        $order_count = Order::whereIn('order_status', '0,1,2,4')->group('order_status')->column('count(order_id) as count', 'order_status');

        $msg_count = Db::name('store_msg')->where(['store_id' => $this->store_id, 'open' => 0])->count();
        $help      = Db::name('article_cat a')->join('article b', 'a.cat_id=b.cat_id')->where('a.cat_name LIKE "%帮助%"')->limit(6)->column('title', 'article_id');
        $goods     = Db::name('goods')->where(['store_id' => $this->store_id])->column('is_on_sale,review_status,is_delete', 'goods_id');
        if ($goods) {
            $goods_count['is_delete'] = $goods_count['review_status'] = 0;
            foreach ($goods as $index => $good) {
                if ($good['is_delete']) {
                    $goods_count['is_delete']++;//商品回收站 数量
                    unset($goods[$index]);
                } else if ($good['review_status'] == 1) {
                    $goods_count['review_status']++;
                    unset($goods[$index]);
                }
            }

            $goods_count['is_on_sale'] = array_count_values(array_column($goods, 'is_on_sale'));//在线销售的商品 数量
        }
        $sale_rank  = Db::name('goods')->where('store_id', $this->store_id)->order('total_sale_num DESC')->limit(6)->column('goods_name,total_sale_num', 'goods_id');
        $yesterday  = strtotime('yesterday');
        $store_data = Order::where([['pay_status', '=', 1], ['pay_time', '>', $yesterday]])
            ->field('count(order_id) as order_num,(sum(order_amount)+sum(user_money)) as amount,FROM_UNIXTIME(pay_time,"%Y-%m-%d") as days,IF(_source="PC","pc","mobile") as `sourceb`')
            ->group('days,`sourceb`')->order('days DESC')
            ->select()->toArray();

        $report = [
            'mobile' => [
                'today'     => ['order_num' => null, 'amount' => 0],
                'yesterday' => ['order_num' => null, 'amount' => 0],
            ],
            'pc'     => [
                'today'     => ['order_num' => null, 'amount' => 0],
                'yesterday' => ['order_num' => null, 'amount' => 0]
            ],
        ];
        if ($store_data) {
            $days = [
                date('Y-m-d', $yesterday) => 'yesterday',
                date('Y-m-d')             => 'today'
            ];
            foreach ($store_data as $index => $s_data) {
                $report[$s_data['sourceb']][$days[$s_data['days']]]['order_num'] = $s_data['order_num'];
                $report[$s_data['sourceb']][$days[$s_data['days']]]['amount']    = $s_data['amount'];
            }
        }
        $today            = array_column($report, 'today');
        $today_amount_sum = array_sum(array_column($today, 'amount'));
        $today_order_sum  = array_sum(array_column($today, 'order_num'));
        $userview         = Db::name('goods_click')->where(['store_id' => $this->store_id, 'date' => date('Y-m-d')])->count();//统计pv时,count('click_count')
        $userview         = $userview > 0 ? $userview : 1;
        $conversion_rate  = round($today_order_sum / $userview * 100, 3);
        $data = [
            'store'            => $store,
            'order_count'      => $order_count,
            'status_text'      => config('order_status'),
            'msg_count'        => $msg_count,
            'goods_count'      => $goods_count ?? [],
            'help'             => $help,
            'sale_rank'        => $sale_rank,
            'report'           => $report,
            'today_amount_sum' => $today_amount_sum,
            'conversion_rate'  => $conversion_rate,
            'quicklink_key'    => array_keys($quick_link),

        ];
        return $this->fetch('', $data);
    }

}
