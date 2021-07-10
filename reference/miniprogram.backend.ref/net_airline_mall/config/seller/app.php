<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 9:20
 */
return [
    'sellerMenu'            => [
        'goods'     => [
            'title'           => '商品',
            'show'            => true,
            'left'            => [
                'goods_list'      => ['title' => '商品列表', 'show' => true, 'group' => ['add_edit', 'del_recycle']],
                'add_edit'        => ['title' => '商品添加/编辑', 'show' => false],
                'del_recycle'     => ['title' => '商品删除/恢复', 'show' => false],
                'store_category'  => ['title' => '店铺商品分类', 'show' => true, 'group' => ['cat_edit', 'cat', 'category']],
                'category'        => ['title' => '平台商品分类', 'show' => false],
                'cat_edit'        => ['title' => '分类添加/编辑', 'show' => false],
                'comment'         => ['title' => '用户评论', 'show' => true, 'group' => ['comment_reply']],
                'comment_reply'   => ['title' => '评论详情', 'show' => false],
                'store_brand'     => ['title' => '商家品牌', 'show' => true, 'group' => ['brand_apply', 'add_brand_apply']],
                'brand_apply'     => ['title' => '商家品牌', 'show' => false],
                'add_brand_apply' => ['title' => '商家品牌', 'show' => false],
                'attr_type'       => ['title' => '商品类型', 'show' => true, 'group' => ['attr_type_add', 'attr_cate', 'attr_list', 'attr_cate_add', 'attr_group', 'attr_group_add', 'attr_edit']],
                'attr_type_add'   => ['title' => '添加/编辑商品类型', 'show' => false],
                'attr_group'      => ['title' => '属性分组', 'show' => false],
                'attr_group_add'  => ['title' => '添加/编辑属性分组', 'show' => false],
                'attr_cate'       => ['title' => '属性分类', 'show' => false],
                'attr_cate_add'   => ['title' => '添加/编辑分类', 'show' => false],
                'attr_list'       => ['title' => '属性列表', 'show' => false],
                'attr_edit'       => ['title' => '添加/编辑属性', 'show' => false],
                'stock_log'       => ['title' => '库存日志', 'show' => true],
            ],
            'privilege_title' => '商品管理',
            'privilege_list'  => [
                'goods_list,add_edit'                                                             => '商品添加/编辑',
                'goods_list,del_recycle'                                                          => '商品删除/恢复',
                'store_category,category,cat_edit'                                                => '分类添加/编辑',
                'comment,comment_reply'                                                           => '评论管理',
                'store_brand,brand_apply,add_brand_apply'                                         => '品牌管理',
                'attr_type,attr_type_add,attr_group,attr_group_add,attr_cate,attr_list,attr_edit' => '商品属性管理',
                'stock_log'                                                                       => '库存日志'
            ]
        ],
        'sale'      => [
            'title'           => '促销',
            'show'            => true,
            'left'            => [
                'coupon'         => ['title' => '优惠券', 'show' => true, 'group' => ['add_coupon', 'coupon_detail']],
                'coupon_detail'  => ['title' => '优惠券领取列表', 'show' => false],
                'add_coupon'     => ['title' => '添加优惠券', 'show' => false],
                //'promotion'      => ['title' => '优惠活动', 'show' => true],
                //'exchange_goods' => ['title' => '积分商城商品', 'show' => true],
                //'presale'        => ['title' => '预售活动', 'show' => true],
                //'gift_gard'      => ['title' => '礼品卡列表', 'show' => true]
            ],
            'privilege_title' => '促销管理',
            'privilege_list'  => [
                'coupon,coupon_detail,add_coupon' => '优惠券管理',
                //'promotion'                       => '优惠活动',
                //'exchange_goods'                  => '积分商城商品',
                //'presale'                         => '预售活动',
                //'gift_gard'                       => '礼品卡列表'
            ]
        ],
        'order'     => [
            'title'           => '订单',
            'show'            => true,
            'left'            => [
                'order_list'    => ['title' => '订单列表', 'show' => true, 'group' => ['order_detail', 'order_edit']],
                'order_detail'  => ['title' => '订单详情', 'show' => false],
                'order_edit'    => ['title' => '修改订单', 'show' => false],
                //'goods_booking' => ['title' => '缺货登记', 'show' => true],
                'delivery_list' => ['title' => '发货单列表', 'show' => true, 'group' => ['delivery', 'make_delivery']],
                'delivery'      => ['title' => '发货单详情', 'show' => false],
                'make_delivery' => ['title' => '生成发货单', 'show' => false],
            ],
            'privilege_title' => '订单管理',
            'privilege_list'  => [
                'order_list,order_detail,order_edit'   => '订单操作',
                //'goods_booking'                        => '缺货登记',
                'delivery_list,delivery,make_delivery' => '发货管理'
            ]
        ],
        'report'    => [
            'title'           => '报表',
            'show'            => true,
            'left'            => [
                'order_sumary' => ['title' => '订单统计', 'show' => true],
                //'sale_sumery'  => ['title' => '销售概况', 'show' => true],
                //'sale_detail'  => ['title' => '销售明细', 'show' => true],
                //'sale_order'   => ['title' => '销售排行', 'show' => true],
            ],
            'privilege_title' => '报表管理',
            'privilege_list'  => [
                'order_sumery' => '订单统计报表',
            ]
        ],
        'service'   => [
            'title'           => '客服',
            'show'            => true,
            'left'            => [
                'index'   => ['title' => '客服消息', 'show' => true],
                //'message' => ['title' => '系统消息', 'show' => true],
            ],
            'privilege_title' => '客服',
            'privilege_list'  => [
                'store_list' => '客服消息',
                //'message'    => '系统消息',
            ]
        ],
        'privilege' => [
            'title'           => '权限',
            'show'            => true,
            'left'            => [
                'admin_list' => ['title' => '下级管理员列表', 'show' => true, 'group' => ['add', 'allot', 'del']],
                'add'        => ['title' => '商家管理员权限添加/编辑', 'show' => false],
                'allot'      => ['title' => '商家权限分配', 'show' => false],
                'del'        => ['title' => '删除商家管理员', 'show' => false],
                'modify_pwd' => ['title' => '修改密码', 'show' => true],
            ],
            'privilege_title' => '后台权限管理',
            'privilege_list'  => [
                'admin_list,add'   => '商家管理员添加/编辑',
                'admin_list,allot' => '商家权限分配',
                'admin_list,del'   => '删除商家管理员',
            ]
        ],
        'system'    => [
            'title'           => '系统',
            'show'            => true,
            'left'            => [
                'shipping'     => ['title' => '物流配送', 'show' => true, 'group' => ['template', 'template_add']],
                'template'     => ['title' => '运费模板', 'show' => false],
                'template_add' => ['title' => '新增/编辑运费模板', 'show' => false],
                //'warehouse'    => ['title' => '仓库管理', 'show' => true],
            ],
            'privilege_title' => '系统设置',
            'privilege_list'  => [
                'shipping,template,template_add' => '配送方式管理',
                //'warehouse'                      => '配送区域管理',
            ]
        ],
        'merchants' => [
            'title'           => '商家',
            'show'            => true,
            'left'            => [
                'settlement'     => ['title' => '店铺结算记录', 'show' => true],
                'pendding'       => ['title' => '待结算订单', 'show' => true],
                'account'        => ['title' => '店铺账户', 'show' => true, 'group' => ['withdrawals', 'account_log', 'withdraw_apply']],
                'withdrawals'    => ['title' => '提现记录', 'show' => false],
                'withdraw_apply' => ['title' => '申请提现', 'show' => false],
                'account_log'    => ['title' => '收支明细', 'show' => false],
            ],
            'privilege_title' => '商家入驻管理',
            'privilege_list'  => [
                'settlement,pendding'                            => '店铺结算',
                'account,withdrawals,withdraw_apply,account_log' => '账户资金',
            ]
        ],
        'store'     => [
            'title'           => '店铺',
            'show'            => true,
            'left'            => [
                'setting'       => ['title' => '店铺基本信息设置', 'show' => true],
                'visual_edit'   => ['title' => '店铺可视化装修', 'show' => true],
                'navigator'     => ['title' => '店铺导航设置', 'show' => true, 'group' => ['navigator_add']],
                'navigator_add' => ['title' => '新增/编辑店铺导航', 'show' => false],
                'advertising'   => ['title' => '店铺广告位管理', 'show' => true],
            ],
            'privilege_title' => '店铺设置管理',
            'privilege_list'  => [
                'setting'                             => '店铺基本信息设置',
                'visual_edit,navigator,navigator_add' => '店铺可视化装修',
                'advertising'                         => '店铺广告位管理'
            ]
        ]
    ],
    'pageSize'              => 10,
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => Env::get('app_path') . 'seller/view/public/jumppage.html',
    'dispatch_error_tmpl'   => Env::get('app_path') . 'seller/view/public/jumppage.html',

    // 异常页面的模板文件
    'exception_tmpl'        => Env::get('think_path') . 'tpl/think_exception.tpl',
    'order_flow'            => [0 => '全部', 1 => '待确认', 2 => '待付款', 3 => '待发货', 4 => '已完成', 5 => '取消', 6 => '无效', 7 => '退货', 8 => '待收货'],
];
