<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/3 11:22
 */

namespace app\home\controller;

use think\Db;
use think\facade\Request;
use app\home\model\Goods as GoodsModel;
use app\home\model\Store;

class Goods extends Common
{
    public function detail()
    {
        $id         = input('id');
        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            $this->error('您要查看的商品可能下架了,逛逛其它吧');
        }

        $user_id             = session('user.user_id');
        $goods['is_collect'] = $goodsModel->countFavorite($goods->goods_id, $user_id);
        $goods['breadCrumb'] = $goodsModel->getBreadCrumb($goods->cat_id);
        $goods['gallery']    = $goodsModel->getGallery($goods->goods_id);
        $goods['product']    = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']       = $goodsModel->getSpec($goods);
        $goods['attr']       = $goodsModel->getAttriBute($goods);
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
        $user_id    = session('user.user_id') ?? 0;
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
                'date'     => $date
            ];
            if ($user_id) {
                $row['user_id'] = $user_id;
            } else {
                $row['session_id'] = $session_id;
            }
            Db::name('goods_click')->insert($row);
        }
        $goodsModel->where('goods_id', $id)->setInc('click_count');
        $goods['history_goods'] = $goodsModel->whereIn('goods_id', $history)->where('goods_id', '<>', $id)->field('goods_id,goods_name,original_img,shop_price')->select();

        //关联商品
        $linkGoodsId = Db::name('goods_link')->where("goods_id={$id} OR (is_double=1 AND link_goods_id={$id})")->field("IF(goods_id={$id},link_goods_id,goods_id) AS goods_id")->select();
        if ($linkGoodsId) {
            $goods['relatedGoods'] = $goodsModel->whereIn('goods_id', array_unique(array_column($linkGoodsId, 'goods_id')))->field('goods_id,goods_name,original_img,shop_price')->select();
        }

        //商品评论
        $goods['comment'] = $goodsModel->getGoodsComment($id);
        $this->assign('list', $goods['comment']['list']);

        $store                = Store::field('seller_money,frozen_money,credit_money', true)->get($goods->store_id, $user_id);
        $store['storeCat']    = Store::storeCat($goods->store_id);
        $store['freeCoupons'] = Store::freeCoupons($goods->store_id);
        $store['is_collect']  = 0;
        if ($goods->store_id > 0) {
            $store['kf'] = $store['seller_id'];//FIXME 修改商家客服
        } else {
            $store['kf'] = 1;//FIXME 修改平台客服
        }
        if (session('user.user_id') > 0) {
            $store['is_collect'] = Db::name('store_collect')->where(['store_id' => $goods->store_id, 'user_id' => session('user.user_id')])->count();
        }
        $goods['wapUrl'] = urlencode(url('wap/index/goodsdetail', '', '', true) . '?pageParam={"goods_id":' . $id . '}');
        $this->assign('goods', $goods);
        $this->assign('store', $store);

        return view();
    }

    public function favorite()
    {
        $id      = input('id');
        $user_id = session('user.user_id');
        $collect = Db::name('goods_collect')
            ->where('user_id', $user_id)
            ->where('goods_id', $id)
            ->find();

        if (!$collect) {
            $row = [
                'user_id'  => $user_id,
                'goods_id' => $id,
                'add_time' => time()
            ];
            Db::name('goods_collect')->insert($row);
            Db::name('goods')->where('goods_id', $id)->setInc('collect_count');
            $result['msg']    = '您已成功收藏该商品';
            $result['status'] = 1;
        } else {
            Db::name('goods_collect')->where('rec_id', $collect['rec_id'])->delete();
            Db::name('goods')->where('goods_id', $id)->setDec('collect_count');
            $result['msg']    = '您已取消收藏该商品';
            $result['status'] = 0;
        }
        $result['count'] = Db::name('goods')->where('goods_id', $id)->value('collect_count');
        return json($result);
    }

    public function getComment()
    {
        $id   = input('post.id/d');
        $page = input('post.page/d', 1);
        $rank = input('post.rank');

        $goodsModel = new GoodsModel();
        $list       = $goodsModel->getCommentList($id, $page, 5, $rank);
        return view('ajax/comment', ['list' => $list]);
    }
}
