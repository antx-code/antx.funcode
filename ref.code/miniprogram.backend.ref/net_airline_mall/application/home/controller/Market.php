<?php
/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 老孟编程
 * Date: 2019/4/10 0010 14:55
 * @model: 促销活动
 * @controller: 积分商城控制器
 * @controller: 秒杀中心控制器
 */


namespace app\home\controller;

use think\Db;
use think\facade\Request;
use app\home\model\Goods as GoodsModel;
use app\home\model\Store;
use app\home\model\Order;

class Market extends Common
{

    public function index()
    {

        $sort  = input("sort", 'default');
        $order = input('order');
        $this->assign('sort', $sort);
        $this->assign('order', $order);
        if (Request::isAjax()) {
            $map    = [];
            $page   = input('page', 1);
            $rows   = input('rows', 10);
            $cat_id = input('cat_id', 0);
            if ($cat_id > 0) {
                $map[] = ['b.cat_id', '=', $cat_id];
            }
            switch ($sort) {
                case 'exchange_integral':
                    $orderby   = "a.exchange_integral {$order}";
                    $goodsList = Db::name("exchange_goods")->alias("a")
                        ->join("goods b", "a.goods_id=b.goods_id")
                        ->join("category c", "b.cat_id=c.cat_id")
                        ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img")
                        ->where("a.is_exchange=1 and a.review_status=2")
                        ->where($map)
                        ->order($orderby)
                        ->page($page, $rows)
                        ->select();
                    break;
                case 'sale_num':
                    $orderby   = "a.sale_num {$order}";
                    $goodsList = Db::name("exchange_goods")->alias("a")
                        ->join("goods b", "a.goods_id=b.goods_id")
                        ->join("category c", "b.cat_id=c.cat_id")
                        ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img")
                        ->where("a.is_exchange=1 and a.review_status=2")
                        ->where($map)
                        ->order($orderby)
                        ->page($page, $rows)
                        ->select();
                    break;
                default:
                    $goodsList = Db::name("exchange_goods")->alias("a")
                        ->join("goods b", "a.goods_id=b.goods_id")
                        ->join("category c", "b.cat_id=c.cat_id")
                        ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img")
                        ->where("a.is_exchange=1 and a.review_status=2")
                        ->where($map)
                        ->page($page, $rows)
                        ->select();
                    break;
            }
            $total = Db::name("exchange_goods")->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id")
                ->where("a.is_exchange=1 and a.review_status=2")
                ->where($map)
                ->count();

            $categoryList = Db::name("exchange_goods")->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id")
                ->join("category c", "b.cat_id=c.cat_id")
                ->field("c.*")
                ->group("b.cat_id")
                ->select();

            ajaxmsg('true', 1, [
                'categoryList' => $categoryList,
                'goodsList'    => $goodsList,
                'total'        => $total,
            ]);
        } else {
            $img = Db::name('website_nav')->whereRaw('url REGEXP "market/index"')->value('imgUrl');
            $this->assign('banner', $img ?: '');
            return view();
        }
    }

    #积分兑换商品详情页
    public function exdetail()
    {
        $id = input('id');

        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }
        $exchange_goods = Db::name("exchange_goods")->where("goods_id={$id} and is_exchange=1")->find();
        if (!$exchange_goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }

        $goods['breadCrumb'] = $goodsModel->getBreadCrumb($goods->cat_id);
        $goods['gallery']    = $goodsModel->getGallery($goods->goods_id);
        $goods['product']    = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']       = $goodsModel->getSpec($goods);
        $goods['brand']      = $goodsModel->getBrand($goods->brand_id);
        //关联分类
        if (isset(current(array_slice($goods['breadCrumb'], -2, 1))['children'])) {
            $goods['related_cat'] = current(array_slice($goods['breadCrumb'], -2, 1))['children'];
        }
        //关联品牌
        $relatedBrandIds = $goodsModel->where('cat_id', $goods->cat_id)->column('GROUP_CONCAT(brand_id)');
        if ($relatedBrandIds) {
            $goods['related_brand'] = Db::name('brand')->where('brand_id', 'IN', array_unique($relatedBrandIds))->where(['is_show' => 1, 'is_delete' => 0])->column('brand_name', 'brand_id');
        }

        // 用户足迹
        $user_id    = session('user.user_id') ? session('user.user_id') : 0;
        $session_id = session_id();
        $date       = date('Y-m-d');
        $click      = Db::name('goods_click')->where("goods_id={$id} AND (user_id={$user_id} OR session_id='{$session_id}')")->find();
        $history    = Db::name('goods_click')->where("(user_id={$user_id} OR session_id='{$session_id}')")->order('date DESC')->column('goods_id');
        if ($click) {
            Db::name('goods_click')->where('click_id', $click['click_id'])->setInc('click_count');
            if ($click['date'] != $date) Db::name('goods_click')->where('click_id', $click['click_id'])->update(['date' => $date]);
        } else {
            $row = [
                'goods_id' => $id,
                'store_id' => $goods['store_id'],
                'date'     => $date,
            ];
            if ($user_id) {
                $row['user_id'] = $user_id;
            } else {
                $row['session_id'] = $session_id;
            }
            Db::name('goods_click')->insert($row);
        }
        $goodsModel->where('goods_id', $id)->setInc('click_count');
        $goods['history_goods'] = $goodsModel->whereIn('goods_id', $history)->where('goods_id', '<>', $id)->field('goods_id,goods_name,original_img,shop_price')->limit(5)->select();

        //关联商品
        $linkGoodsId = Db::name('goods_link')->where("goods_id={$id} OR (is_double=1 AND link_goods_id={$id})")->field("IF(goods_id={$id},link_goods_id,goods_id) AS goods_id")->select();
        if ($linkGoodsId) {
            $goods['relatedGoods'] = $goodsModel->whereIn('goods_id', array_unique(array_column($linkGoodsId, 'goods_id')))->field('goods_id,goods_name,original_img,shop_price')->select();
        }

        //商品评论
        $goods['comment'] = $goodsModel->getGoodsComment($id);

        $store                       = Store::field('seller_money,frozen_money,credit_money', true)->get($goods->store_id, $user_id);
        $store['storeCat']           = Store::storeCat($goods->store_id);
        $store['freeCoupons']        = Store::freeCoupons($goods->store_id);
        $user_address                = Db::name("user_address")->where("user_id={$user_id} and is_default=1")->find();
        $user_address['addressTemp'] = getAddressWithOder($user_address);

        $this->assign('goods', $goods);
        $this->assign('store', $store);
        $this->assign('goods_id', $id);
        $this->assign('exchange_goods', $exchange_goods);
        $this->assign('user_address', $user_address);
        $this->assign('address_id', isset($user_address['address_id']) ? $user_address['address_id'] : 0);
        return view();
    }

    public function exgoodsmore()
    {
        $store_id  = input('store_id/d', 0);
        $goods_id  = input("goods_id", 0);
        $goodsList = Db::name("exchange_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->field('b.goods_id,b.goods_name,b.shop_price,b.original_img,
            a.exchange_integral,a.market_integral,a.is_exchange')
            ->order('a.sale_num DESC')
            ->where("a.is_exchange = 1 and a.goods_id!={$goods_id}")
            ->limit(6)
            ->select();
        $this->assign('goodsList', $goodsList);
        return view();
    }

    public function exorder()
    {
        $orderModel = new Order();
        $user_id    = session('user.user_id') ? session('user.user_id') : 0;
        $goods_id   = input("goods_id", 0);
        $address    = Db::name('user_address')
            ->where("user_id={$user_id} and is_default=1")
            ->find();
        if (!$address) {
            ajaxmsg('收货地址不存在~', 0);
        }
        $exchange_goods = Db::name("exchange_goods")
            ->where("goods_id={$goods_id}")
            ->find();
        $user           = Db::name("user")
            ->where("user_id={$user_id}")
            ->find();
        if ($user['points'] < $exchange_goods['exchange_integral']) {
            ajaxmsg('当前积分不够~', 0);
        }


        $res = $orderModel->exorder($user_id, $goods_id);
        if ($res > 0) {
            ajaxmsg('兑换成功', 1);
        } else {
            ajaxmsg('兑换失败', 0);
        }
    }

    #团购模块
    public function gpgoods()
    {

        $page = input('page', 1);
        $rows = input('rows', 10);
        $act = input('act', 'all');

        $list = (new \app\home\model\GroupGoods)->getList($page, $rows);
        $this->assign('act', $act);
        $this->assign('list', $list);
        return view();
    }

    public function gpdetail()
    {
        $id = input('id');

        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }
        $market_goods = Db::name("group_goods")
            ->field("*,FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->where("goods_id={$id} and review_status=2")
            ->find();
        if (!$market_goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }

        $goods['breadCrumb'] = $goodsModel->getBreadCrumb($goods->cat_id);
        $goods['gallery']    = $goodsModel->getGallery($goods->goods_id);
        $goods['product']    = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']       = $goodsModel->getSpec($goods);
        $goods['brand']      = $goodsModel->getBrand($goods->brand_id);
        //关联分类
        if (isset(current(array_slice($goods['breadCrumb'], -2, 1))['children'])) {
            $goods['related_cat'] = current(array_slice($goods['breadCrumb'], -2, 1))['children'];
        }
        //关联品牌
        $relatedBrandIds = $goodsModel->where('cat_id', $goods->cat_id)->column('GROUP_CONCAT(brand_id)');
        if ($relatedBrandIds) {
            $goods['related_brand'] = Db::name('brand')->where('brand_id', 'IN', array_unique($relatedBrandIds))->where(['is_show' => 1, 'is_delete' => 0])->column('brand_name', 'brand_id');
        }

        // 用户足迹
        $user_id    = session('user.user_id') ? session('user.user_id') : 0;
        $session_id = session_id();
        $date       = date('Y-m-d');
        $click      = Db::name('goods_click')->where("goods_id={$id} AND (user_id={$user_id} OR session_id='{$session_id}')")->find();
        $history    = Db::name('goods_click')->where("(user_id={$user_id} OR session_id='{$session_id}')")->order('date DESC')->column('goods_id');
        if ($click) {
            Db::name('goods_click')->where('click_id', $click['click_id'])->setInc('click_count');
            if ($click['date'] != $date) Db::name('goods_click')->where('click_id', $click['click_id'])->update(['date' => $date]);
        } else {
            $row = [
                'goods_id' => $id,
                'store_id' => $goods['store_id'],
                'date'     => $date,
            ];
            if ($user_id) {
                $row['user_id'] = $user_id;
            } else {
                $row['session_id'] = $session_id;
            }
            Db::name('goods_click')->insert($row);
        }
        $goodsModel->where('goods_id', $id)->setInc('click_count');
        $goods['history_goods'] = $goodsModel->whereIn('goods_id', $history)->where('goods_id', '<>', $id)->field('goods_id,goods_name,original_img,shop_price')->limit(5)->select();

        //关联商品
        $linkGoodsId = Db::name('goods_link')->where("goods_id={$id} OR (is_double=1 AND link_goods_id={$id})")->field("IF(goods_id={$id},link_goods_id,goods_id) AS goods_id")->select();
        if ($linkGoodsId) {
            $goods['relatedGoods'] = $goodsModel->whereIn('goods_id', array_unique(array_column($linkGoodsId, 'goods_id')))->field('goods_id,goods_name,original_img,shop_price')->select();
        }

        //商品评论
        $goods['comment'] = $goodsModel->getGoodsComment($id);

        $store                       = Store::field('seller_money,frozen_money,credit_money', true)->get($goods->store_id, $user_id);
        $store['storeCat']           = Store::storeCat($goods->store_id);
        $store['freeCoupons']        = Store::freeCoupons($goods->store_id);
        $user_address                = Db::name("user_address")->where("user_id={$user_id} and is_default=1")->find();
        $user_address['addressTemp'] = getAddressWithOder($user_address);

        $this->assign('goods', $goods);
        $this->assign('store', $store);
        $this->assign('goods_id', $id);
        $market_goods['ext_info'] = array_sort(json_decode($market_goods['ext_info'], true), 'num', SORT_DESC);


        //print_r($market_goods);exit;
        $this->assign('market_goods', $market_goods);
        $this->assign('user_address', $user_address);
        $this->assign('address_id', isset($user_address['address_id']) ? $user_address['address_id'] : 0);

        return view();
    }

    public function gpgoodsmore()
    {
        $store_id  = input('store_id/d', 0);
        $goods_id  = input("goods_id", 0);
        $goodsList = Db::name("group_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->field('b.goods_id,b.goods_name,b.shop_price,b.original_img,a.*')
            ->order('a.start_time DESC')
            ->where("a.goods_id!={$goods_id} and a.review_status=2")
            ->where('start_time', '< time', time())
            ->where('end_time', '> time', time())
            ->limit(6)
            ->select();
        $this->assign('goodsList', $goodsList);
        return view();
    }

    #秒杀模块
    public function skgoods()
    {
        $sklist = Db::name("seckill")
            ->field("*,FROM_UNIXTIME(start_time,'%H:%i') as start_time_temp,
            FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
            FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->order("sec_id asc")
            ->limit(1)
            ->select();

        $cat_id = input('cat_id', 0);
        $gmap   = [];
        $gmap[] = ['a.sec_id', 'IN', array_column($sklist, 'sec_id')];
        if ($cat_id > 0) {
            //$gmap[] = ['b.cat_id', '=', $cat_id];
        }

        $gmap[]    = ['b.is_on_sale', '=', 1];
        $gmap[]    = ['b.is_show', '=', 1];
        $gmap[]    = ['b.review_status', '=', 2];
        $gmap[]    = ['b.is_delete', '=', 0];
        $goodsList = Db::name('seckill_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img")
            ->where($gmap)
            ->order('a.sec_id asc')
            ->select();


        $gids   = implode(',', array_column($goodsList, 'goods_id'));
        $cmap   = [];
        $cmap[] = ['a.goods_id', 'in', $gids];

        $clist = Db::name("goods")->alias("a")
            ->join("category b", "a.cat_id=b.cat_id", 'LEFT')
            ->field("a.cat_id,b.cat_name")
            ->where($cmap)
            ->group("a.cat_id")
            ->order("b.sort_order desc")
            ->select();
        foreach ($sklist as $k => $v) {
            if ($v['start_time'] <= time() && time() <= $v['end_time']) {
                $sklist[$k]['curr'] = 1;
            } else {
                $sklist[$k]['curr'] = 0;
            }
            $ids      = array_column($goodsList, 'sec_id');
            $valCount = array_count_values($ids);
            if (isset($valCount[$v['sec_id']])) {
                $sklist[$k]['goods_list'] = array_slice(
                    $goodsList,
                    array_search($v['sec_id'], $ids),
                    $valCount[$v['sec_id']]
                );
            }

        }


        $img = Db::name('website_nav')->whereRaw('url REGEXP "market/skgoods"')->value('imgUrl');


        $this->assign('banner', $img ?: '');
        $this->assign('list', $sklist);
        $this->assign('clist', $clist);
        return view();
    }

    public function skdetail()
    {
        $id = input('id');

        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }
        $market_goods = Db::name("seckill_goods")->alias("a")
            ->join("seckill b", "a.sec_id=b.sec_id", "LEFT")
            ->field("*,FROM_UNIXTIME(b.end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn,
            b.start_time,b.end_time")
            ->where("goods_id={$id}")
            ->find();
        if (!$market_goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }

        $goods['breadCrumb'] = $goodsModel->getBreadCrumb($goods->cat_id);
        $goods['gallery']    = $goodsModel->getGallery($goods->goods_id);
        $goods['product']    = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']       = $goodsModel->getSpec($goods);
        $goods['brand']      = $goodsModel->getBrand($goods->brand_id);
        //关联分类
        if (isset(current(array_slice($goods['breadCrumb'], -2, 1))['children'])) {
            $goods['related_cat'] = current(array_slice($goods['breadCrumb'], -2, 1))['children'];
        }
        //关联品牌
        $relatedBrandIds = $goodsModel->where('cat_id', $goods->cat_id)->column('GROUP_CONCAT(brand_id)');
        if ($relatedBrandIds) {
            $goods['related_brand'] = Db::name('brand')->where('brand_id', 'IN', array_unique($relatedBrandIds))->where(['is_show' => 1, 'is_delete' => 0])->column('brand_name', 'brand_id');
        }

        // 用户足迹
        $user_id    = session('user.user_id') ? session('user.user_id') : 0;
        $session_id = session_id();
        $date       = date('Y-m-d');
        $click      = Db::name('goods_click')->where("goods_id={$id} AND (user_id={$user_id} OR session_id='{$session_id}')")->find();
        $history    = Db::name('goods_click')->where("(user_id={$user_id} OR session_id='{$session_id}')")->order('date DESC')->column('goods_id');
        if ($click) {
            Db::name('goods_click')->where('click_id', $click['click_id'])->setInc('click_count');
            if ($click['date'] != $date) Db::name('goods_click')->where('click_id', $click['click_id'])->update(['date' => $date]);
        } else {
            $row = [
                'goods_id' => $id,
                'store_id' => $goods['store_id'],
                'date'     => $date,
            ];
            if ($user_id) {
                $row['user_id'] = $user_id;
            } else {
                $row['session_id'] = $session_id;
            }
            Db::name('goods_click')->insert($row);
        }
        $goodsModel->where('goods_id', $id)->setInc('click_count');
        $goods['history_goods'] = $goodsModel->whereIn('goods_id', $history)->where('goods_id', '<>', $id)->field('goods_id,goods_name,original_img,shop_price')->limit(5)->select();

        //关联商品
        $linkGoodsId = Db::name('goods_link')->where("goods_id={$id} OR (is_double=1 AND link_goods_id={$id})")->field("IF(goods_id={$id},link_goods_id,goods_id) AS goods_id")->select();
        if ($linkGoodsId) {
            $goods['relatedGoods'] = $goodsModel->whereIn('goods_id', array_unique(array_column($linkGoodsId, 'goods_id')))->field('goods_id,goods_name,original_img,shop_price')->select();
        }

        //商品评论
        $goods['comment'] = $goodsModel->getGoodsComment($id);

        $store                       = Store::field('seller_money,frozen_money,credit_money', true)->get($goods->store_id, $user_id);
        $store['storeCat']           = Store::storeCat($goods->store_id);
        $store['freeCoupons']        = Store::freeCoupons($goods->store_id);
        $user_address                = Db::name("user_address")->where("user_id={$user_id} and is_default=1")->find();
        $user_address['addressTemp'] = getAddressWithOder($user_address);

        $this->assign('goods', $goods);
        $this->assign('store', $store);
        $this->assign('goods_id', $id);

        //print_r($market_goods);exit;
        $this->assign('market_goods', $market_goods);
        $this->assign('user_address', $user_address);
        $this->assign('address_id', isset($user_address['address_id']) ? $user_address['address_id'] : 0);

        $this->assign('nowtime', time());

        return view();
    }

    public function skgoodsmore()
    {
        $store_id = input('store_id/d', 0);
        $goods_id = input("goods_id", 0);

        $time = time();

        $goodsList = Db::name("seckill_goods")->alias("a")
            ->join("seckill c", "a.sec_id=c.sec_id", "LEFT")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field('b.goods_id,b.goods_name,b.shop_price,b.original_img,
            a.*')
            ->order('c.start_time DESC')
            ->where("a.goods_id!={$goods_id}")
            ->where("c.start_time<{$time} and c.end_time>{$time} ")
            ->limit(6)
            ->select();
        $this->assign('goodsList', $goodsList);
        return view();
    }

    #领券市场
    public function coupons()
    {
        $user_id = session('user.user_id');
        if (Request::isAjax()) {
            $coupon_id = input('coupon_id');
            $res       = getcoupon($coupon_id, $user_id);
            if ($res === true) {
                return json([
                    'status'  => '1',
                    'msg'     => '领取成功！感谢您的参与，祝您购物愉快~',
                    'content' => '买好货送实惠',
                ]);
            } else {
                return json([
                    'status'  => '0',
                    'msg'     => $res,
                    'content' => '买好货送实惠',
                ]);
            }

        } else {
            $time  = time();
            $plist = Db::name("coupon")
                ->field("*,ROUND( send_num / num * 100) as dataper,
                FROM_UNIXTIME(send_end_time) as send_end_time_cn")
                ->where('send_start_time', '<=', $time)
                ->where('send_end_time', '>', $time)
                ->where('num', '>', 'send_num')
                ->where("store_id=0")
                ->order("coupon_id desc")
                ->select();
            if ($plist && $user_id > 0) {
                $clist = Db::name("coupon_list")
                    ->where('user_id', $user_id)
                    ->whereIn('coupon_id', array_column($plist, 'coupon_id'))
                    ->order("coupon_id desc")
                    ->select();
                foreach ($plist as $k => $v) {
                    $ids      = array_column($clist, 'coupon_id');
                    $valCount = array_count_values($ids);
                    if (array_key_exists($v['coupon_id'], $valCount)) {
                        $plist[$k]['children'] = array_slice(
                            $clist,
                            array_search($v['coupon_id'], $ids),
                            $valCount[$v['coupon_id']]
                        );
                    }
                }
            }
            $this->assign("plist", $plist);
            return view();
        }
    }

}
