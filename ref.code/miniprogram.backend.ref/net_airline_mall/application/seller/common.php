<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/6 8:47
 */

use think\Db;

/**
 * 检查登录状态
 * @return bool
 */
function check_login_state()
{
    if (session('seller')) {
        return true;
    } elseif (cookie('seller') && cookie('seller_sign')) {
        $seller = Db::name('seller')->where('username', cookie('seller'))->find();
        if ($seller && cookie('seller_sign') == hash('sha256', $seller['password'])) {
            session('seller', $seller);
            return true;
        } else {
            cookie('seller', null);
            cookie('seller_sign', null);
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 获取所有权限code,对应seller表action字段
 * @param $menu
 * @return array
 */
function auth_list($menu)
{
    $authList = [];
    foreach ($menu as $controller => $item) {
        $authList[$controller] = array_keys($item['left']);
    }
    return $authList;
}

function get_brand_by_id($ids)
{
    return Db::name('brand')->where('brand_id', 'IN', $ids)->column('brand_name', 'brand_id');
}

/**
 * 组装订单查询条件
 * @param $flow_index
 * @return array
 */
function fixorderwhere($flow_index)
{
    //[0 => '请选择', 1 => '待确认', 2 => '待付款', 3 => '待发货', 4 => '已完成', 5 => '取消', 6 => '无效', 7 => '退货', 8 => '待收货']
    $flow  = config('flow_index');
    $where = [];
    switch ($flow_index) {
        case 1:
            $where[] = ['order_status', '=', 0];
            break;
        case 2:
            $where[] = ['order_status', '<', 2];
            $where[] = ['pay_status', '=', 0];
            break;
        case 3:
            $where[] = ['order_status', '<', 2];
            $where[] = ['pay_status', '=', 1];
            $where[] = ['shipping_status', '=', 0];
            break;
        case 4:
            $where[] = ['order_status', '=', 4];
            break;
        case 5:
            $where[] = ['order_status', '=', 3];
            break;
        case 6:
            $where[] = ['order_status', '=', 6];
            break;
        case 7:
            $where[] = ['order_status', '=', 5];
            break;
        case 8:
            $where[] = ['order_status', '<', 2];
            $where[] = ['pay_status', '=', 1];
            $where[] = ['shipping_status', '=', 2];
            break;
    }
    return $where;
}

/**
 * 获取店铺分类选择列表
 * @param $store_id
 * @param $cid
 * @param string $mode
 * @return mixed
 */
function storeCategoryPath($store_id, $cid, $mode = 'cat_id')
{
    $map                  = ['store_id' => $store_id, 'is_delete' => 0];
    $map[$mode]           = $cid;
    $category['cat_path'] = [];
    $category['info']     = Db::name('store_category')->where($map)->find();
    unset($map[$mode]);

    if ($category['info']['parent_id'] > 0) {
        $map['cat_id']           = $category['info']['parent_id'];
        $cat                     = Db::name('store_category')->where($map)->find();
        $category['level']       = $cat['level'] + 1;
        $category['parent_id']   = $cat['cat_id'];
        $grand_id                = $cat['parent_id'];
        $category['parent_name'] = $cat['cat_name'];
        array_push($category['cat_path'], $cat['cat_name']);
        for ($i = 1; $i < $cat['level']; $i++) {
            $map ['cat_id'] = $cat['parent_id'];
            $cat            = Db::name('store_category')->where($map)->find();
            array_push($category['cat_path'], $cat['cat_name']);
        }
    } else {
        $category['level']       = 1;
        $category['parent_id']   = 0;
        $category['parent_name'] = '';
        $grand_id                = 0;
    }

    $category['cat_path'] = array_reverse($category['cat_path']);

    unset($map['cat_id']);

    $map['parent_id'] = $grand_id;
    $category['list'] = Db::name('store_category')->where($map)->order('sort desc')->select();
    if ($mode !== 'cat_id') {
        unset($category['info']);
    }
    return $category;
}
