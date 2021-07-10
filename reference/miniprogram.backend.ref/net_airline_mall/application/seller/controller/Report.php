<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 10:59
 */

namespace app\seller\controller;

use think\Db;

class Report extends Base
{
    public function order_sumary()
    {
        if ($this->request->isAjax()) {
            $download = input('download', 0);
            $start    = input('start');
            $end      = input('end');

            $sql = "SELECT sum(order_amount)+sum(user_money) as order_amount,count(order_id) as order_num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from " . config('database.prefix') . "order ";
            $sql .= " where store_id={$this->store_id} and pay_status=1 and is_delete=0 AND add_time between {$start} and {$end} group by gap order by order_id";
            $res = Db::query($sql);//物流费,交易额,成本价
            foreach ($res as $val) {
                $order_amount[$val['gap']] = $val['order_amount'];
                $order_num[$val['gap']]    = $val['order_num'];
            }

            $amount_arr = $num_arr = $day = [];
            for ($i = $start; $i <= $end; $i = $i + 86400) {
                $date             = date('Y-m-d', $i);
                $day[]            = date('m-d', $i);
                $tmp_order_amount = empty($order_amount[$date]) ? 0 : (float)$order_amount[$date];
                $tmp_order_num    = empty($order_num[$date]) ? 0 : $order_num[$date];

                $amount_arr[] = $tmp_order_amount;
                $num_arr[]    = $tmp_order_num;
                $list[]       = [
                    'day'          => $date,
                    'order_amount' => $tmp_order_amount,
                    'order_num'    => $num_arr,
                    'end'          => date('Y-m-d', $i + 86400)
                ];
            }

            if ($download) {
                $title = ['时间', '订单商品总额', '订单优惠总额', '成本总额', '物流总额'];
                foreach ($list as $index => $item) {
                    unset($list[$index]['end']);
                }
                $result = toExcel($title, $list, '运营报表' . date('Y-m-d'));
                if ($result !== true)
                    return json($result, 403);
            }
            $count_map   = [
                ['store_id', '=', $this->store_id],
                ['date', 'between', [date('Y-m-d', $start), date('Y-m-d', $end)]],
            ];
            $click_count = Db::name('goods_click')->where($count_map)->sum('click_count');
            $region_map  = [
                ['store_id', '=', $this->store_id],
                ['pay_status', '=', 1],
                ['is_delete', '=', 0],
                ['add_time', 'between', [$start, $end]]
            ];
            $region      = Db::name('order a')->join('region b', 'a.province=b.region_id')
                ->where($region_map)
                ->field('count(a.order_id) as value,b.region_name as name')
                ->group('region_name')
                ->select();

            $result = ['order_amount' => $amount_arr, 'order_num' => $num_arr, 'timeline' => $day, 'click_count' => $click_count, 'region' => $region];
            return json($result);
        }
        return $this->fetch();
    }

    public function download()
    {
        $start = input('start');
        $end   = input('end');

        $sql = "SELECT FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap,sum(order_amount)+sum(user_money) as order_amount,count(order_id) as order_num,order_prom_amount,cost_amount,shipping_price from " . config('database.prefix') . "order ";
        $sql .= " where store_id={$this->store_id} and pay_status=1 and is_delete=0 AND add_time between {$start} and {$end} group by gap order by order_id";
        $res = Db::query($sql);//物流费,交易额,成本价
        if (!$res) {
            $res = [['', 0, 0, 0, 0, 0]];
        }

        $title  = ['时间', '订单商品总额', '商品数量', '订单优惠总额', '成本总额', '物流总额'];
        $result = toExcel($title, $res, '运营报表' . date('Y-m-d'));
        if ($result !== true) {
            $this->error($result);
        }
    }
}
