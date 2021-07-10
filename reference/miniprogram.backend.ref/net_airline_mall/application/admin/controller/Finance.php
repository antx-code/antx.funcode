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

use app\admin\model\Brand;
use think\Config;
use think\console\command\make\Model;
use think\Db;
use think\exception\PDOException;
use think\facade\Request;

class Finance extends Common
{
    public function store()
    {
        if (Request::isAjax()) {
            $type      = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $date      = empty($_REQUEST['date']) ? '' : trim($_REQUEST['date']);
            $timezone  = 8;
            $time_diff = $timezone * 3600;
            $result    = ['error' => 0, 'message' => '', 'content' => ''];
            $data      = [];
            if ($date == 'week') {
                $day_num = 7;
            }

            if ($date == 'month') {
                $day_num = 30;
            }
            if ($date == 'year') {
                $day_num = 180;
            }

            $date_end   = local_mktime(0, 0, 0, local_date('m'), local_date('d') + 1, local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 * $day_num;
            $sql        = "SELECT
                DATE_FORMAT(
                    FROM_UNIXTIME(oi.add_time + {$time_diff}),
                    '%y-%m-%d'
                ) AS day,
                COUNT(*) AS count
                FROM
                    oc_store AS oi
                WHERE
		        oi.add_time BETWEEN {$date_start} AND {$date_end}";
            $result     = Db::query($sql);

            $orders_series_data = [];
            $sales_series_data  = [];
            foreach ($result as $k => $v) {
                $orders_series_data[$v['day']] = intval($v['count']);
                //$sales_series_data[$v['day']] = floatval($v['money']);
            }
            for ($i = 1; $i <= $day_num; $i++) {
                $day = local_date('y-m-d', local_strtotime(' - ' . ($day_num - $i) . ' days'));

                if (empty($orders_series_data[$day])) {
                    $orders_series_data[$day] = 0;
                    //$sales_series_data[$day] = 0;
                }

                $day                 = local_date('m-d', local_strtotime($day));
                $orders_xAxis_data[] = $day;
                $sales_xAxis_data[]  = $day;
            }

            $toolbox    = [
                'show'    => true,
                'orient'  => 'vertical',
                'x'       => 'right',
                'y'       => '60',
                'feature' => [
                    'magicType'   => [
                        'show' => true,
                        'type' => ['line', 'bar'],
                    ],
                    'saveAsImage' => ['show' => true],
                ],
            ];
            $tooltip    = [
                'trigger'     => 'axis',
                'axisPointer' => [
                    'lineStyle' => ['color' => '#6cbd40'],
                ],
            ];
            $xAxis      = [
                'type'        => 'category',
                'boundaryGap' => false,
                'axisLine'    => [
                    'lineStyle' => ['color' => '#ccc', 'width' => 0],
                ],
                'data'        => [],
            ];
            $yAxis      = [
                'type'      => 'value',
                'axisLine'  => [
                    'lineStyle' => ['color' => '#ccc', 'width' => 0],
                ],
                'axisLabel' => ['formatter' => ''],
            ];
            $series     = [
                [
                    'name'      => '',
                    'type'      => 'line',
                    'itemStyle' => [
                        'normal' => [
                            'color'     => '#6cbd40',
                            'lineStyle' => ['color' => '#6cbd40'],
                        ],
                    ],
                    'data'      => [],
                    'markPoint' => [
                        'itemStyle' => [
                            'normal' => ['color' => '#6cbd40'],
                        ],
                        'data'      => [
                            ['type' => 'max', 'name' => '最大值'],
                            ['type' => 'min', 'name' => '最小值'],
                        ],
                    ],
                ],
                [
                    'type'      => 'force',
                    'name'      => '',
                    'draggable' => false,
                    'nodes'     => ['draggable' => false],
                ],
            ];
            $calculable = true;
            $legend     = [
                'data' => [],
            ];

            if ($type == 'order') {
                $xAxis['data']      = $orders_xAxis_data;
                $yAxis['formatter'] = '个';
                ksort($orders_series_data);
                $series[0]['name'] = '店铺个数';
                $series[0]['data'] = array_values($orders_series_data);
                $data['series']    = $series;
            }

            if ($type == 'sale') {
                $xAxis['data']      = $sales_xAxis_data;
                $yAxis['formatter'] = '{value}' . '元';
                ksort($sales_series_data);
                $series[0]['name'] = '销售额';
                $series[0]['data'] = array_values($sales_series_data);
                $data['series']    = $series;
            }

            $data['tooltip']    = $tooltip;
            $data['legend']     = $legend;
            $data['toolbox']    = $toolbox;
            $data['calculable'] = $calculable;
            $data['xAxis']      = $xAxis;
            $data['yAxis']      = $yAxis;
            #$data['xy_file'] = get_dir_file_list();
            exit(json_encode($data));
        } else {
            return view();
        }
    }

    #店铺销售统计
    public function shopsale()
    {
        /**
         * table order
         * order_status：订单状态  0：待确认 1：已确认 2：待评价 3：已取消 4：已完成 5：退款 6：作废
         * pay_status：0：待支付，1：已付款
         * shipping_status: 发货状态  0：未发货 1：已发货 2：部分发货，3：已签收
         **/
        $map = [];
        if (Request::isAjax()) {
            $map[]           = ['a.store_id', 'gt', 0];
            $order_prom_type = input("order_prom_type", 0);
            $page            = input('page', 1);
            $rows            = input('rows', 10);
            $start           = ($page - 1) * $rows;
            $keywords        = trim(input("keywords"));
            $start_time      = strtotime(trim(input("start_time")));
            $end_time        = strtotime(trim(input("end_time")));

            $sqlattr = '';
            if ($keywords) {
                $sqlattr .= " and a.store_name like '{$keywords}'";
            }
            if ($end_time > $start_time) {
                $sqlattr .= " and c.add_time =>{$start_time} and c.add_time =< {$end_time}";
            }

            $sql = "SELECT a.store_id,a.store_name,b.orderCount,b.orderAmount,b.payAmount,c.userConut,c.add_time
                    FROM oc_store a 
                        
                    LEFT JOIN 
                    (SELECT order_id,store_id,order_status,pay_status,shipping_status,COUNT(order_id) as orderCount, SUM(total_amount) as orderAmount,SUM(order_amount) as payAmount
                    FROM oc_order where store_id>0 GROUP BY store_id) b 
                    on a.store_id=b.store_id
                        
                    LEFT JOIN 
                    (SELECT order_id,user_id,add_time,store_id,COUNT(user_id) as userConut 
                    FROM oc_order where store_id>0 GROUP BY store_id) c 
                    on a.store_id=c.store_id {$sqlattr}

                    where a.status=1 and b.order_status>0
                    LIMIT {$start},{$rows};";

            $list = Db::query($sql);

            $total = Db::name("order")
                ->group("store_id")
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }


    #订单销售统计
    public function salelist()
    {
        if (Request::isAjax()) {
            $map  = [];
            $page = input('page', 1);
            $rows = input('rows', 10);

            $start_time      = strtotime(trim(input("start_time")));
            $end_time        = strtotime(trim(input("end_time")));
            $order_status    = input("order_status");
            $shipping_status = input("shipping_status");

            if ($start_time && $end_time) {
                $map[] = ['a.add_time', 'between time', [$start_time, $end_time]];
            }
            if ($order_status !== '') {
                $map[] = ['a.order_status', 'in', $order_status];
            }
            if ($shipping_status !== '') {
                $map[] = ['a.shipping_status', 'in', $shipping_status];
            }
            $list  = Db::name("order")->alias("a")
                ->join("store b", 'a.store_id=b.store_id', 'LEFT')
                ->field("a.*,b.store_name")
                ->order("a.order_id desc")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("order")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    #提现管理
    public function withdraw()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['b.bank_mobile|b.store_name', "like", "%$keywords%"];
            }
            $statusIDS = input("statusIDS");
            if ($statusIDS !== '') {
                $map[] = ['a.is_paid', 'in', $statusIDS];
            }
            $start_time = strtotime(trim(input("start_time")));
            $end_time   = strtotime(trim(input("end_time")));
            if ($start_time && $end_time) {
                $map[] = ['a.add_time', 'between time', [$start_time, $end_time]];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("account_log_store")->alias("a")
                ->join("store b", "a.store_id=b.store_id", 'LEFT')
                ->where($map)
                ->field("a.*,b.store_name,b.store_id,b.seller_id,b.seller_money,b.frozen_money,b.pendding_money,
                b.account_name,b.bank_name,b.bank_card,b.bank_mobile")
                ->order("a.log_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("account_log_store")->alias("a")
                ->join("store b", "a.store_id=b.store_id", 'LEFT')
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function withdoing()
    {
        $log_id = input("log_id", 0);
        if (Request::isAjax()) {
            $pageparm = input("post.");
            $logData  = Db::name('account_log_store')->where('log_id', $log_id)->find();
            if ($logData['is_paid'] == 2) {
                ajaxmsg('重复操作', 0);
            }
            #数据表变动金额有正负区别
            switch ( $pageparm['is_paid'] ) {
                case 1:
                    Db::name('store')->where('store_id', $logData['store_id'])->update([
                        'frozen_money' => Db::raw('frozen_money' . $logData['amount']),
                    ]);
                    break;
                case 2:
                    $sql = Db::name('store')->where('store_id', $logData['store_id'])->update([
                        'seller_money' => Db::raw('seller_money' . $logData['amount']),
                        'frozen_money' => Db::raw('frozen_money' . $logData['amount']),
                    ]);
                    break;
            }
            $account_log_storeData = [
                'change_time' => time(),
                'reject_note' => $pageparm['reject_note'],
                'is_paid'     => $pageparm['is_paid'],
            ];
            Db::name('account_log_store')->where('log_id', $log_id)->update($account_log_storeData);
            ajaxmsg('操作成功', 1);
        } else {
            $fieldData           = "a.*,b.*";
            $info                = Db::name("account_log_store")->alias("a")
                ->join("store b", "a.store_id=b.store_id", 'LEFT')
                ->where('log_id', $log_id)
                ->field($fieldData)
                ->find();
            $info['addressTemp'] = getAddressWithOder($info);
            $this->assign('info', json_encode($info, 320));
            $this->assign('log_id', $log_id);
            return view();
        }
    }
}
