<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/5 14:14
 */

namespace app\home\model;

use think\Model;
use think\Db;

class Cart extends Model
{

    /**
     * 刷新购物车列表
     * @param int $user_id
     * @param string $session_id
     * @param int $selected
     * @param int $invalid
     * @return array
     */
    public static function cartList($user_id = 0, $session_id = '', $selected = 0, $invalid = 0)
    {
        if ($user_id) {
            $where = [['a.user_id', '=', $user_id]];
            //如果用户已经登录则按照用户id查询
        } else {
            $where = [['a.session_id', '=', $session_id]];
        }

        // 获取购物车商品
        $cartList = Db::name('cart a')
            ->join('goods b', 'a.goods_id=b.goods_id', 'LEFT')
            ->where($where)
            ->field("a.*,b.original_img,b.goods_number,a.prom_type as cart_prom_type,a.prom_id")
            ->group('a.goods_id,a.prom_id,a.product_id,a.status')
            ->select();

        $anum = $total_price = $cut_fee = 0;
        foreach ($cartList as $k => $val) {
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['goods_price'];
            //订单确认页面过滤失效商品
            if ($invalid && $val['status'] > 0) {
                unset($cartList[$k]);
                continue;
            }
            //不计算失效商品
            if ($val['status'] > 0)
                continue;
            // 如果要求只计算购物车选中商品的价格和数量 并且 当前商品没选择, 则跳过
            if ($selected == 1 && $val['selected'] == 0)
                continue;

            $cartList[$k]['store_count'] = getGoodNum($val['goods_id'], $val['product_id']); // 最多可购买的库存数量
            $anum                        += $val['goods_num'];

            //TODO 计算优惠活动
            //TODO 商品超出配送范围提醒
            $cut_fee     += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price'];
            $total_price += $val['goods_num'] * $val['goods_price'];
        }

        $total_price = ['total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $anum]; // 总计
        session('cart_num', $anum);
        return ['cartList' => $cartList, 'total_price' => $total_price, 'cart_num' => $anum];
    }

    public static function addCart($goods_id, $num, $user_id, $session_id, $attr_keys = null, $buyNow = false, $shareMan = 0)
    {
        $goods = Db::name('goods')->where(['goods_id' => $goods_id, 'is_delete' => 0, 'is_on_sale' => 1])->find();
        if (!$goods) return ['status' => -1, 'msg' => '您要购买的商品可能不存在或已下架,再逛逛其它吧~'];
        $product = Db::name('products')->where('goods_id', $goods_id)->column('product_id,tempvalue,product_number,product_price,product_market_price', 'attr_keys');

        if ($product) {
            if (!isset($product[$attr_keys])) return ['status' => -2, 'msg' => '商品存在多种规格,请选择~'];
            $realStock = $product[$attr_keys]['product_number'];
            if ($num > $realStock) return ['status' => -3, 'msg' => $product[$attr_keys]['tempvalue'] . '库存不足~'];
            $specPrice = $product[$attr_keys]['product_price'];
        } else {
            $realStock = $goods['goods_number'];
            if ($num > $goods['goods_number']) return ['status' => -3, 'msg' => '库存不足~'];
        }

        $price     = $specPrice ?? $goods['shop_price'];
        $prom_type = 0;
        $prom_id   = 0;
        $res       = promtypeInit($user_id);#团购秒杀路由中转
        if (is_array($res)) {
            $price     = $res['goods_price'];
            $prom_type = $res['prom_type'];
            $prom_id   = $res['prom_id'];
        }
        //TODO 活动价格计算

        $product_id = $attr_keys ? $product[$attr_keys]['product_id'] : 0;
        if ($shareMan == $user_id) {
            $shareMan = 0;
        }
        $data = [
            'user_id'            => $user_id,   // 用户id
            'session_id'         => $user_id > 0 ? '' : $session_id,   // sessionid
            'goods_id'           => $goods_id,   // 商品id
            'goods_sn'           => $goods['goods_sn'],   // 商品货号
            'goods_name'         => $goods['goods_name'],   // 商品名称
            'market_price'       => $goods['market_price'],   // 市场价
            'goods_price'        => $price,  // 购买价
            'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
            'goods_num'          => $num, // 购买数量
            'product_id'         => $product_id, // 规格id
            'tempvalue'          => $product[$attr_keys]['tempvalue'] ?? '', // 规格名称
            'sku'                => $goods['goods_sn'], // 商品条形码
            'add_time'           => time(), // 加入购物车时间
            'prom_type'          => $prom_type,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id'            => $prom_id,   // 活动id
            'store_id'           => $goods['store_id'],   // 店铺id
            'share_man'          => $shareMan
        ];
        //直接购买，返回虚拟购物车
        if ($buyNow) {
            return ['status' => 1, 'result' => $data];
        }

        // 查询购物车是否已经存在相同规格商品
        $map = [
            "goods_id"   => $goods_id,
            "product_id" => $product_id,
            'status'     => 0
        ];
        if ($user_id > 0) {
            $map['user_id'] = $user_id;
        } else {
            $map['session_id'] = $session_id;
        }

        $cart_goods = Db::name('cart')->where($map)->find();
        if ($cart_goods) {
            $cart_id = $cart_goods['id'];
            //购物车中存在相同规格的商品时，仅更新数量
            // 如果购物车的已有数量加上 这次要购买的数量 大于 库存数 则不再增加数量
            $sum_sum = $num + $cart_goods['goods_num'];
            if ($sum_sum > $realStock) {
                return ['status' => -4, 'msg' => '您的购物车中已存在' . $cart_goods['goods_num'] . '，库存仅有' . $realStock];
            }
            // 数量相加
            Db::name('cart')->where("id", $cart_goods['id'])->update([
                "goods_num"          => ($cart_goods['goods_num'] + $num),
                'goods_price'        => $price,
                'member_goods_price' => $price
            ]);
        } else {
            $cart_id = Db::name('cart')->insert($data);
        }

        $cart_goods_calc = self::cart_goods_calc($user_id, $session_id);
        session('cart_num', $cart_goods_calc['goods_num']);// 更新购物车数量
        return ['status' => 1, 'msg' => '成功加入购物车', 'cart_num' => $cart_goods_calc['goods_num'], 'total_fee' => $cart_goods_calc['total_fee']];
    }

    /**
     *购物车summery,刷新购物车数量
     * @param $user_id
     * @param string $session_id
     * @return array|\PDOStatement|string|Model|null
     */
    public static function cart_goods_calc($user_id = 0, $session_id = '')
    {
        if ($user_id) {
            $map = [['user_id', '=', $user_id]];
        } elseif ($session_id) {
            $map = [['session_id', '=', $session_id]];
        } else {
            $map = false;
        }
        $res = Db::name('cart')->where($map)->field('SUM(goods_num) as goods_num,SUM(goods_price*goods_num) as total_fee')->find();
        session('cart_num', $res['goods_num'] ?? 0);
        return $res;
    }
}