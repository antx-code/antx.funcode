<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/3 13:59
 */

namespace app\home\model;

use think\Model;
use think\Db;

class Store extends Model
{
    protected $pk = 'store_id';

    protected function base($query)
    {
        $query->where(['is_delete' => 0, 'status' => 1]);
    }

    /**
     * 获取店铺可以免费领取的优惠券
     * @param $store_id
     * @param $limit
     * @return array
     */
    public static function freeCoupons($store_id = null, $limit = 20)
    {
        $time       = time();
        $store_name = cache('siteInfo')['shop_name'];
        $map        = [
            ['a.is_delete', '=', 0],
            ['a.send_type', '=', 2],
            ['a.send_start_time', '<', $time],
            ['a.send_end_time', '>', $time]
        ];
        if ($store_id && $store_id > 0) {
            $map[] = ['a.store_id', '=', $store_id];
        }
        return Db::name('coupon a')
            ->join('store b', 'a.store_id=b.store_id', 'LEFT')
            ->where($map)->order('a.coupon_id DESC')
            ->limit($limit)
            ->column('a.name,a.money,a.min_goods_amount,IFNULL(b.store_name,"' . $store_name . '") AS store_name ,a.use_start_time,a.use_end_time', 'a.coupon_id');
    }

    /**
     * 获取用户已领取的优惠券
     * @param int $limit
     * @return array|\PDOStatement|string|\think\Collection
     */
    public static function userCoupons($limit = 20)
    {
        $user_id = session('user.user_id');
        if (!$user_id) return [];
        $map = [
            ['a.user_id', '=', $user_id],
            ['a.is_delete', '=', 0],
            ['a.order_id', '=', 0]
        ];
        return Db::name('coupon_list a')->join('coupon b', 'a.coupon_id=b.coupon_id')
            ->where($map)
            ->field('a.list_id,b.name,b.money,b.min_goods_amount,b.use_start_time,b.use_end_time')
            ->order('a.list_id DESC')
            ->limit($limit)
            ->select();
    }

    /**
     * 获取店铺所有分类,平台自营返回空数组
     * @param int $store_id
     * @return array
     */
    public static function storeCat($store_id = 0): array
    {
        $map    = ['store_id' => $store_id, 'is_delete' => 0, 'is_show' => 1];
        $level1 = Db::name('store_category')->where($map)->where('parent_id', 0)->order('sort DESC')->column('parent_id,cat_name', 'cat_id');
        if (!$level1) return [];
        $level2 = Db::name('store_category')->where($map)->where('parent_id', 'IN', array_keys($level1))->order('sort DESC')->column('parent_id,cat_name', 'cat_id');
        if (!$level2) return $level1;

        $level3 = Db::name('store_category')->where($map)->where('parent_id', 'IN', array_keys($level2))->order('sort DESC')->column('parent_id,cat_name', 'cat_id');

        if ($level3) {
            foreach ($level3 as $index => $item) {
                $level2[$item['parent_id']]['children'][] = $level3[$index];
            }
        }
        foreach ($level2 as $index => $item) {
            $level1[$item['parent_id']]['children'][] = $level2[$index];
        }
        return $level1;
    }
}