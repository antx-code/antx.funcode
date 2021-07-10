<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/30 14:21
 */

namespace app\home\controller;

use think\facade\Env;
use think\Db;

class Category extends Common
{
    public function index()
    {
        $cat_id       = input("id", 0);
        $categoryInfo = Db::name("category")
            ->where("cat_id={$cat_id}")
            ->find();
        if (!$categoryInfo['template_file']) {
            //if ($categoryInfo['parent_id'] > 0) {
            $this->redirect('/home/category/catgoodslist', ['id' => $cat_id]);
            exit;
            //}
        }
        $categoryTemp = categoryListAjax($cat_id, 7);
        $categoryList = $categoryTemp[0];

        //echo json_encode($categoryList,320);exit;
        if (isset($categoryList['children'])) {
            array_walk($categoryList['children'], function (&$item, $key) {
                $ids   = [];
                $ids[] = $item['cat_id'];

                if (isset($item['children'])) {
                    $ids = array_merge($ids, array_column($item['children'], 'cat_id'));
                }
                $item['goods_count'] = $this->categoryGoodsList($ids);
            });
            //扶农馆，单独处理
            if ($cat_id == 46624 && isset($categoryList['children'][0]['children'][0])) {
                $categoryList = $this->appendLevel($categoryList);
            }
        }

        $this->assign('category', $categoryList);
        $this->assign('categoryList', json_encode($categoryList, 320));
        $time = time();
        #轮播图
        $carcousel = Db::name('adposition a')->join('ads b', 'a.position_id=b.position_id')
            ->where('a.position_id=1 AND b.start_time <' . $time . ' AND b.end_time > ' . $time)
            ->field('a.ad_width,a.ad_height,b.ad_img,b.ad_link')
            ->select();
        $this->assign('carcousel', $carcousel);
        $gmap     = [];
        $gmap[]   = ['cat_id', 'in', $categoryList['catIds']];
        $gmap[]   = ['is_on_sale', '=', 1];
        $gmap[]   = ['is_show', '=', 1];
        $gmap[]   = ['review_status', '=', 2];
        $field    = 'goods_id,goods_name,cat_id,store_id,brand_id,shop_price,original_img';
        $hotList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_hot=1')
            ->limit(6)
            ->order('goods_id desc')
            ->select();
        $newList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_new=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $bestList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_best=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $this->assign('hotList', $hotList);
        $this->assign('newList', $newList);
        $this->assign('bestList', $bestList);
        $this->assign('cat_id', $cat_id);

        $suiList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where("is_sui=1")#随手购检索
            ->limit(18)
            ->order('goods_id desc')
            ->select();
        $this->assign('suiList', $suiList);
        if ($categoryInfo['template_file']) {
            return view($categoryInfo['template_file']);
        } else {
            return view('default');
        }

    }

    public function funong()
    {
        $cat_id       = input('id/d', 0);
        $categoryTemp = categoryListAjax($cat_id, 7);
        $categoryList = $categoryTemp[0];

        if (isset($categoryList['children'])) {
            array_walk($categoryList['children'], function (&$item, $key) {
                $ids   = [];
                $ids[] = $item['cat_id'];

                if (isset($item['children'])) {
                    $ids = array_merge($ids, array_column($item['children'], 'cat_id'));
                }
                $item['goods_count'] = $this->categoryGoodsList($ids);
            });
            //扶农馆,中国馆，单独处理
            if (in_array($cat_id, [46624, 47045]) && isset($categoryList['children'][0]['children'][0])) {
                $categoryList = $this->appendLevel($categoryList);
            }
        }
        //return json($categoryList);
        $this->assign('category', $categoryList);
        $this->assign('categoryList', json_encode($categoryList, 320));
        $time = time();
        #轮播图
        $carcousel = Db::name('adposition a')->join('ads b', 'a.position_id=b.position_id')
            ->where('a.position_id=8 AND b.start_time <' . $time . ' AND b.end_time > ' . $time)
            ->field('a.ad_width,a.ad_height,b.ad_img,b.ad_link')
            ->select();
        $this->assign('carcousel', $carcousel);
        $gmap     = [];
        $gmap[]   = ['cat_id', 'in', $categoryList['catIds']];
        $gmap[]   = ['is_on_sale', '=', 1];
        $gmap[]   = ['is_show', '=', 1];
        $gmap[]   = ['review_status', '=', 2];
        $field    = 'goods_id,goods_name,cat_id,store_id,brand_id,shop_price,original_img';
        $hotList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_hot=1')
            ->limit(6)
            ->order('goods_id desc')
            ->select();
        $newList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_new=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $bestList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_best=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $this->assign('hotList', $hotList);
        $this->assign('newList', $newList);
        $this->assign('bestList', $bestList);
        $this->assign('cat_id', $cat_id);

        $suiList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where("is_sui=1")#随手购检索
            ->limit(18)
            ->order('goods_id desc')
            ->select();
        $this->assign('suiList', $suiList);
        return view();
    }

    public function zhongguoguang()
    {
        $cat_id       = input('id/d', 0);
        $categoryTemp = categoryListAjax($cat_id, 7);
        $categoryList = $categoryTemp[0];

        if (isset($categoryList['children'])) {
            array_walk($categoryList['children'], function (&$item, $key) {
                $ids   = [];
                $ids[] = $item['cat_id'];

                if (isset($item['children'])) {
                    $ids = array_merge($ids, array_column($item['children'], 'cat_id'));
                }
                $item['goods_count'] = $this->categoryGoodsList($ids);
            });
            //扶农馆,中国馆，单独处理
            if (in_array($cat_id, [46624, 47045]) && isset($categoryList['children'][0]['children'][0])) {
                $categoryList = $this->appendLevel($categoryList);
            }
        }
        //return json($categoryList);
        $this->assign('category', $categoryList);
        $this->assign('categoryList', json_encode($categoryList, 320));
        $time = time();
        #轮播图
        $carcousel = Db::name('adposition a')->join('ads b', 'a.position_id=b.position_id')
            ->where('a.position_id=9 AND b.start_time <' . $time . ' AND b.end_time > ' . $time)
            ->field('a.ad_width,a.ad_height,b.ad_img,b.ad_link')
            ->select();
        $this->assign('carcousel', $carcousel);
        $gmap     = [];
        $gmap[]   = ['cat_id', 'in', $categoryList['catIds']];
        $gmap[]   = ['is_on_sale', '=', 1];
        $gmap[]   = ['is_show', '=', 1];
        $gmap[]   = ['review_status', '=', 2];
        $field    = 'goods_id,goods_name,cat_id,store_id,brand_id,shop_price,original_img';
        $hotList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_hot=1')
            ->limit(6)
            ->order('goods_id desc')
            ->select();
        $newList  = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_new=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $bestList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where('is_best=1')
            ->limit(6)
            ->order('goods_id asc')
            ->select();
        $this->assign('hotList', $hotList);
        $this->assign('newList', $newList);
        $this->assign('bestList', $bestList);
        $this->assign('cat_id', $cat_id);

        $suiList = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->where("is_sui=1")#随手购检索
            ->limit(18)
            ->order('goods_id desc')
            ->select();
        $this->assign('suiList', $suiList);
        return view();
    }

    /**
     * 增加第四级分类，扶农馆专用.
     * @param array $category
     * @return array
     */
    private function appendLevel(array $category): array
    {
        $id_lv3 = [];
        foreach ($category['children'] as $index2 => $cat2) {
            array_push($id_lv3, array_column($cat2['children'], 'cat_id'));
        }
        $id_lv3 = array_reduce($id_lv3, function ($carry, $item) {
            return array_merge($carry, $item);
        }, []);
        $cat    = Db::name('category')->where('is_show', 1)->whereIn('parent_id', $id_lv3)
            ->field('cat_id,cat_name,parent_id,sort_order,sort_order,style_icon')
            ->order('parent_id DESC,sort_order ASC')
            ->select();
        if (!$cat) {
            return $category;
        }
        $lv4      = array_column($cat, 'parent_id');
        $valCount = array_count_values($lv4);
        foreach ($category['children'] as $index2 => $cat2) {
            foreach ($cat2['children'] as $index3 => $cat3) {
                if (isset($valCount[$cat3['cat_id']])) {
                    $category['children'][$index2]['children'][$index3]['children'] = array_slice($cat, array_search($cat3['cat_id'], $lv4), $valCount[$cat3['cat_id']]);
                } else {
                    $category['children'][$index2]['children'][$index3]['children'] = [];
                }
            }
        }
        return $category;
    }

    public function categoryGoodsList($cid = 0)
    {
        $cat_id = input('cat_id', 0);
        $limit  = input('limit', 12);
        $gmap   = [
            ['is_on_sale', '=', 1],
            ['is_show', '=', 1],
            ['review_status', '=', 2]
        ];
        if ($cid) {
            $gmap[] = ['cat_id', 'IN', $cid];
            return Db::name('goods')->where($gmap)->count();
        }
        $gmap[] = ['cat_id', '=', $cat_id];
        $field  = 'goods_id,goods_name,cat_id,store_id,brand_id,shop_price,original_img';
        $list   = Db::name("goods")
            ->field($field)
            ->where($gmap)
            ->limit($limit)
            ->select();
        ajaxmsg('true', 1, $list);
    }

    public function getcatBrandList()
    {
        $cat_id = input('cat_id', 0);
        $list   = getcatBrandList($cat_id);
        $this->assign('list', $list);
        $this->assign('cat_id', $cat_id);
        return view('ajax/getcatbrandlist');
    }


    #聚合查询商品列表-启用
    public function catgoodslist()
    {
        $sort  = input("sort", 'goods_id');
        $order = input('order');
        switch ($sort) {
            case 'last_update':
            case 'goods_id':
                $orderby = "a.goods_id {$order}";
                break;
            case 'total_sale_num':
                $orderby = "a.total_sale_num {$order}";
                break;
            case 'comment_num':
                $orderby = "a.comment_num {$order}";
                break;
            case 'shop_price':
                $orderby = "a.shop_price {$order}";
                break;
        }
        $this->assign('sort', $sort);
        $this->assign('order', $order);

        $cat_id = input('id', 0);
        if ($cat_id > 0) {
            $getChildrenDataStr = getChildrenData($cat_id, 1);
        }
        $this->assign('id', $cat_id);
        $keywords = input('keywords');
        $brand_id = input('brand_id');
        $page     = input('page', 1);
        $rows     = input('rows', 30);
        if ($cat_id > 0) {
            $categoryInfo = Db::name("category")
                ->where("cat_id={$cat_id}")
                ->find();
            $pargoryInfo  = Db::name("category")
                ->where("cat_id={$categoryInfo['parent_id']}")
                ->find();
            $pcatList     = Db::name("category")
                ->where("parent_id={$categoryInfo['parent_id']}")
                ->where('is_delete=0 AND is_show=1')
                ->field("cat_id,cat_name,parent_id")
                ->order("sort_order desc")
                ->select();
            $scatList     = Db::name("category")
                ->where("parent_id={$categoryInfo['cat_id']}")
                ->where('is_delete=0 AND is_show=1')
                ->field("cat_id,cat_name,parent_id")
                ->order("sort_order desc")
                ->select();
            $this->assign("categoryInfo", $categoryInfo);
            $this->assign("pargoryInfo", $pargoryInfo);
            $this->assign("pcatList", $pcatList);
            $this->assign("scatList", $scatList);
        }

        #热门推荐开始
        $hmap = [];
        if ($cat_id > 0) {
            $hmap[] = ['cat_id', 'in', $getChildrenDataStr];
        }
        $hmap[]  = ['is_hot', '=', 1];
        $hmap[]  = ['is_on_sale', '=', 1];
        $hmap[]  = ['is_show', '=', 1];
        $hmap[]  = ['review_status', '=', 2];
        $hmap[]  = ['is_delete', '=', 0];
        $field   = 'goods_id,goods_name,cat_id,store_id,brand_id,shop_price,market_price,original_img,total_sale_num,virtual_sale_num,comment_num,store_id,is_new,is_hot,is_best';
        $hotList = Db::name("goods")
            ->field($field)
            ->where($hmap)
            ->order("total_sale_num desc")
            ->limit(4)
            ->select();
        #热门推荐结束

        $field2 = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
        a.original_img,a.total_sale_num,virtual_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,
        b.product_id';
        $gmap   = [];
        if ($cat_id > 0) {
            //$gmap[] = ['a.cat_id', '=', $cat_id];
            $gmap[] = ['a.cat_id', 'in', $getChildrenDataStr];
        }
        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        $gmap[] = ['a.is_delete', '=', 0];
        if ($keywords) {
            $gmap[] = ['a.goods_name|a.keywords|a.goods_brief|a.goods_tag|a.goods_product_tag', 'like', "%$keywords%"];
        }
        if ($brand_id > 0) {
            $gmap[] = ['a.brand_id', '=', $brand_id];
        }
        $this->assign("keywords", $keywords ? $keywords : '');
        $this->assign("brand_id", $brand_id ? $brand_id : '');
        $price_min = input('price_min', 0);
        $price_max = input('price_max', 0);
        if ($price_min > 0 && $price_max > 0) {
            $gmap[] = ['a.shop_price', 'between', [$price_min, $price_max]];
        }
        $this->assign('price_min', $price_min);
        $this->assign('price_max', $price_max);

        $ship = input('ship', 0);
        if (isset($ship)) {
            if ($ship == 1) {
                $gmap[] = ['a.is_shipping', '=', $ship];
            }
        }
        $self = input('self', 1);
        if (isset($self)) {
            if ($self == 0) {
                $gmap[] = ['a.store_id', '=', $self];
            }
        }
        $have = input('have', 0);
        if (isset($have)) {
            if ($have > 0) {
                $gmap[] = ['a.goods_number', '>', 0];
            }
        }
        $this->assign('ship', $ship);
        $this->assign('self', $self);
        $this->assign('have', $have);
        $user_id   = session('user.user_id') ?: 0;
        $goodsList = Db::name("goods")->alias("a")
            ->join("products b", "a.goods_id=b.goods_id", "LEFT")
            ->join('goods_collect c', 'a.goods_id=c.goods_id AND c.user_id=' . $user_id, 'LEFT')
            ->field($field2 . ',c.rec_id as is_collect')
            ->where($gmap)
            ->order($orderby)
            ->group("a.goods_id")
            ->page($page, $rows)
            ->select();

        //echo Db::name("goods")->getLastSql();exit;
        $total         = Db::name("goods")->alias("a")
            ->join("products b", "a.goods_id=b.goods_id", "LEFT")
            ->where($gmap)
            ->group("a.goods_id")
            ->count();
        $goods_gallery = Db::name('goods_gallery')
            ->where('goods_id', 'IN', array_column($goodsList, 'goods_id'))
            ->order('goods_id desc')
            ->select();
        foreach ($goodsList as $k => $v) {
            $ids      = array_column($goods_gallery, 'goods_id');
            $valCount = array_count_values($ids);
            if (isset($valCount[$v['goods_id']])) {
                $goodsList[$k]['goods_gallery'] = array_slice($goods_gallery, array_search($v['goods_id'], $ids), $valCount[$v['goods_id']]);
            } else {
                $goodsList[$k]['goods_gallery'] = [];
            }
        }


        $this->assign('page', $page);
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign("goodsList", $goodsList);
        $this->assign("hotList", $hotList);

        return view();
    }

    public function all()
    {
        $list = categoryListAjax();
        $this->assign('list', $list);
        return view();
    }
}
