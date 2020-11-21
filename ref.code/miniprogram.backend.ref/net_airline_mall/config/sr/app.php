<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 9:20
 */
return [
    'srMenu'                => [
        'Goods'     => [
            'title' => '商品',
            'show'  => true,
            'left'  => [
                'index' => ['title' => '商品列表', 'show' => true],
            ]
        ],
        'Order'     => [
            'title' => '订单',
            'show'  => true,
            'left'  => [
                'index'         => ['title' => '订单列表', 'show' => true, 'group' => ['order_detail', 'order_edit']],
                'order_detail'  => ['title' => '订单详情', 'show' => false],
                'delivery_list' => ['title' => '发货单列表', 'show' => true, 'group' => ['delivery', 'make_delivery']],
                'delivery'      => ['title' => '发货单详情', 'show' => false],
                'make_delivery' => ['title' => '生成发货单', 'show' => false],
            ]
        ],
        'Merchants' => [
            'title' => '结算',
            'show'  => true,
            'left'  => [
                'index'          => ['title' => '结算记录', 'show' => true],
                'pendding'       => ['title' => '待结算订单', 'show' => true],
                'account'        => ['title' => '供应商账户', 'show' => true, 'group' => ['withdrawals', 'account_log', 'withdraw_apply']],
                'withdrawals'    => ['title' => '提现记录', 'show' => false],
                'withdraw_apply' => ['title' => '申请提现', 'show' => false],
                'account_log'    => ['title' => '收支明细', 'show' => false],
            ],
        ],
        'Setting'   => [
            'title' => '设置',
            'show'  => true,
            'left'  => [
                'index' => ['title' => '供应基本信息设置', 'show' => true],
            ],
        ]
    ],
    'pageSize'              => 10,
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => Env::get('app_path') . 'sr/view/jumppage.html',
    'dispatch_error_tmpl'   => Env::get('app_path') . 'sr/view/jumppage.html',
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'      => '\\app\\common\\exception\\Http',
    // 异常页面的模板文件
    'order_flow'            => [0 => '全部', 1 => '待确认', 2 => '待付款', 3 => '待发货', 4 => '已完成', 5 => '取消', 6 => '无效', 7 => '退货', 8 => '待收货'],
];
