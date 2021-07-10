<?php

namespace app\home\controller;

use think\Db;
use app\home\model\Store;
use app\home\model\Cart as CartModel;
use app\home\model\Goods as GoodsModel;
use app\home\model\Order as OrderModel;
use think\facade\Cache;
use think\facade\Request;

class Index extends Common
{
    public function index()
    {
        $time = time();
        //轮播图
        $carcousel = Db::name('adposition a')->join('ads b', 'a.position_id=b.position_id')
            ->where('a.position_id=1 AND b.start_time <' . $time . ' AND b.end_time > ' . $time)
            ->field('a.ad_width,a.ad_height,b.ad_img,b.ad_link')
            ->cache(86400)
            ->select();
        //公告
        $note = Db::name('article_cat a')->join('article b', 'a.cat_id=b.cat_id')
            ->where('a.cat_id=13 AND b.is_open=1')
            ->order('b.sort_order DESC')
            ->limit(5)
            ->cache(86400)
            ->column('b.title', 'b.article_id');

        $template = Db::name('shop_config')->where('code', 'skype')->value('value');//FIXME 增加首页模板字段
        $tpl      = 'tpl' . $template;
        $this->$tpl();

        $this->assign('carcousel', $carcousel);
        $this->assign('note', $note);

        return view($tpl);
    }

    private function tpl0()
    {
        $time = time();
        $this->comon_tpl_data();
        //楼层
        $floors = cache('homefloor');
        if (!$floors || config('app_debug') === true) {
            $floors = Db::name('home_floor')->where(['hometheme' => __FUNCTION__, 'is_show' => 1])->order('sort ASC')->select();
            foreach ($floors as $index => $floor) {
                $cat                        = Db::name('category')->where('cat_id', $floor['cat_id'])->field('cat_id,cat_name')->find();
                $floors[$index]['subtitle'] = $cat['cat_name'];
                $sec_cat                    = Db::name('category')->where(['parent_id' => $cat['cat_id'], 'is_delete' => 0, 'is_show' => 1])->order('is_home_show ASC')->column('cat_name', 'cat_id');
                $sec_catid                  = array_keys($sec_cat);
                $floors[$index]['tabs']     = array_combine(array_slice($sec_catid, 0, $floor['tab_num']), array_slice($sec_cat, 0, $floor['tab_num']));
                array_push($sec_catid, $cat['cat_id']);
                $third_catid = Db::name('category')->where(['is_delete' => 0, 'is_show' => 1])->where('parent_id', 'IN', $sec_catid)->column('cat_id');
                $ids         = array_merge($sec_catid, $third_catid);

                $ad_brand_ids             = Db::name('goods')->where(['is_delete' => 0, 'is_on_sale' => 1])->where('cat_id', 'IN', $ids)->column('brand_id');
                $floors[$index]['brands'] = Db::name('brand')->where('brand_id', 'IN', $ad_brand_ids)->order('sort_order desc')->limit(10)->column('brand_logo,brand_name', 'brand_id');
                $adv                      = Db::name('ads')
                    ->where(['floor_id' => $floor['id'], 'enabled' => 1])
                    ->where([['start_time', '<', $time], ['end_time', '>', $time]])
                    ->field('ad_id,position_id,ad_link,ad_code,ad_img,b_title,s_title')
                    ->select();
                foreach ($adv as $idx => $item) {
                    $floors[$index]['adv'][$item['ad_code']][] = $item;
                }
            }
            cache('homefloor', $floors);
        }

        $this->assign('floor', $floors);
    }

    private function tpl1()
    {
        $time = time();
        $this->comon_tpl_data();
        //楼层
        $floors = cache('homefloor');
        if (!$floors || config('app_debug') === true) {
            $floors = Db::name('home_floor')->where(['hometheme' => __FUNCTION__, 'is_show' => 1])->order('sort ASC')->select();
            foreach ($floors as $index => $floor) {
                $cat                        = Db::name('category')->where('cat_id', $floor['cat_id'])->field('cat_id,cat_name')->find();
                $floors[$index]['subtitle'] = $cat['cat_name'];
                $sec_cat                    = Db::name('category')->where(['parent_id' => $cat['cat_id'], 'is_delete' => 0, 'is_show' => 1])->order('is_home_show ASC')->column('cat_name', 'cat_id');
                $sec_catid                  = array_keys($sec_cat);
                $floors[$index]['tabs']     = array_combine(array_slice($sec_catid, 0, $floor['tab_num']), array_slice($sec_cat, 0, $floor['tab_num']));
                $adv                        = Db::name('ads')
                    ->where(['floor_id' => $floor['id'], 'enabled' => 1])
                    ->where([['start_time', '<', $time], ['end_time', '>', $time]])
                    ->field('ad_id,position_id,ad_link,ad_code,ad_img,b_title,s_title')
                    ->select();
                foreach ($adv as $idx => $item) {
                    $floors[$index]['adv'][$item['ad_code']][] = $item;
                }
            }
            //echo json_encode($floors,JSON_UNESCAPED_UNICODE);die;
            cache('homefloor', $floors);
        }

        $this->assign('floor', $floors);
    }

    private function tpl2()
    {
        $time   = time();
        $floors = cache('homefloor');
        if (!$floors || config('app_debug') === true) {
            $model  = new GoodsModel;
            $floors = Db::name('home_floor')->where(['hometheme' => __FUNCTION__, 'is_show' => 1])->order('sort ASC')->select();
            foreach ($floors as $index => $floor) {
                $floors[$index]['subtitle'] = Db::name('category')->where('cat_id', $floor['cat_id'])->value('cat_name');
                $sec_catid                  = Db::name('category')->where(['parent_id' => $floor['cat_id'], 'is_delete' => 0, 'is_show' => 1])->column('cat_id');
                $thir_catid                 = Db::name('category')->whereIn('parent_id', $sec_catid)->where(['is_delete' => 0, 'is_show' => 1])->column('cat_id');
                $ids                        = array_merge($sec_catid, $thir_catid);
                array_push($ids, $floor['cat_id']);
                $floors[$index]['goods'] = $model->whereIn('cat_id', $ids)
                    ->field('goods_id,goods_name,shop_price,original_img,market_price')
                    ->order('sort_order desc,click_count desc')
                    ->limit(8)
                    ->select();
                $floors[$index]['adv']   = Db::name('ads')
                    ->where(['floor_id' => $floor['id'], 'enabled' => 1])
                    ->where([['start_time', '<', $time], ['end_time', '>', $time]])
                    ->field('ad_id,position_id,ad_link,ad_code,ad_img,b_title,s_title')
                    ->limit(2)
                    ->select();
            }
            cache('homefloor', $floors);
        }

        $top_adv  = Db::name('ads')
            ->where('floor_id=0 AND ad_code="top_adv"')
            ->where('enabled=1')
            ->where([['start_time', '<', $time], ['end_time', '>', $time]])
            ->limit(15)
            ->cache(86400)
            ->select();
        $head_adv = Db::name('ads')->where('ad_code', 'home_top_adv')->where('enabled=1')->where([['start_time', '<', $time], ['end_time', '>', $time]])->find();
        $this->assign('head_adv', $head_adv);
        $this->assign('floor', $floors);
        $this->assign('top_adv', $top_adv);
    }

    private function comon_tpl_data()
    {
        //品牌
        $brand['list'] = Db::name('brand')->where('is_delete=0 AND is_show=1')->order('is_recommend DESC,sort_order DESC')->limit(15)->cache(86400)->column('brand_logo,index_img', 'brand_id');
        $brand_collect = Db::name('brand_collect')->where('brand_id', 'IN', array_keys($brand['list']))->field('count(rec_id) AS count,brand_id')->group('brand_id')->cache(86400)->select();
        $is_collect    = [];
        if (session('user')) {
            $is_collect = Db::name('brand_collect')->where('brand_id', 'IN', array_keys($brand['list']))->where('user_id', session('user.user_id'))->column('user_id', 'brand_id');
        }
        if ($brand_collect) {
            $brand_ids           = array_column($brand_collect, 'brand_id');
            $temp                = array_column($brand_collect, 'count');
            $brand['collect']    = array_combine($brand_ids, $temp);
            $brand['is_collect'] = $is_collect;
        }
        //店铺
        $store = Db::name('store')
            ->where(['is_delete' => 0, 'status' => 1])
            ->order('store_service_score DESC,store_delivery_score DESC,store_desc_score DESC,seller_money DESC,seller_money DESC')
            ->field('store_id,store_name,store_subtitle,logo,banner_mobile')
            ->limit(4)
            ->select();
        $this->assign('brands', $brand);
        $this->assign('store', $store);
    }

    public function ajaxChangeBrands()
    {
        $brand['list'] = Db::name('brand')->where('is_delete=0 AND is_show=1')->orderRaw('rand()')->limit(15)->column('brand_logo,index_img', 'brand_id');
        $brand_collect = Db::name('brand_collect')->where('brand_id', 'IN', array_keys($brand['list']))->field('count(rec_id) AS count,brand_id')->group('brand_id')->select();
        $is_collect    = [];
        if (session('user')) {
            $is_collect = Db::name('brand_collect')->where('brand_id', 'IN', array_keys($brand['list']))->where('user_id', session('user.user_id'))->column('user_id', 'brand_id');
        }
        //$is_collect = Db::name('brand_collect')->where('brand_id', 'IN', array_keys($brand['list']))->where('user_id', 120)->column('user_id', 'brand_id');
        if ($brand_collect) {
            $brand_ids           = array_column($brand_collect, 'brand_id');
            $temp                = array_column($brand_collect, 'count');
            $brand['collect']    = array_combine($brand_ids, $temp);
            $brand['is_collect'] = $is_collect;
        }
        $this->assign('brands', $brand);
        return view('ajax/change_brands');
    }

    public function ajaxFloorContent()
    {
        $cat_id = input('cat_id/d');
        $num    = input('num/d', 6);

        $cat2 = Db::name('category')->where('parent_id', $cat_id)->column('cat_id');
        array_push($cat2, $cat_id);
        $map   = [
            ['is_delete', '=', 0],
            ['is_show', '=', 1],
            ['cat_id', 'IN', $cat2],
        ];
        $goods = Db::name('goods')->where($map)->order('is_best DESC,is_new DESC,sort_order DESC')->limit($num)->field('goods_id,goods_name,market_price,shop_price,original_img')->select();
        $this->assign('goods', $goods);
        return view('ajax/floor_content');
    }

    public function ajaxGuessYouLike()
    {
        $limit = input('limit/d', 20);
        $list  = Db::name('goods')->where(['is_delete' => 0, 'is_on_sale' => 1])
            ->order('sort_order DESC,is_hot DESC,is_best DESC,goods_number DESC')
            ->field('goods_id,goods_name,original_img,shop_price,market_price')
            ->limit($limit)
            ->select();
        $this->assign('goodsList', $list);
        return view('ajax/guess_you_like');
    }

    public function ajaxRightBar()
    {
        $type     = input('type');
        $store_id = input('store_id/d');
        switch ($type) {
            case 'cart_list'://购物车
                $cartList = CartModel::cartList(session('user.user_id'), session_id());
                $this->assign('data', $cartList);
                $tempate = 'right_bar_cart';
                break;
            case 'mpbtn_order'://我的订单
                $user_id    = session('user.user_id') ?? 0;
                $order      = new OrderModel();
                $order_list = $order->getUserOrderList($user_id);
                $this->assign('order_list', $order_list);
                $tempate = 'right_bar_order';
                break;
            case 'mpbtn_yhq'://优惠券
                $coupon['free'] = Store::freeCoupons($store_id, 4);
                $coupon['user'] = Store::userCoupons(3);
                $this->assign('coupon', $coupon);
                $tempate = 'right_bar_coupon';
                break;
            case 'mpbtn_history'://足迹
                $list = Db::name('goods a')
                    ->join('goods_click b', 'a.goods_id=b.goods_id')
                    ->field('a.goods_id,a.goods_name,a.original_img,a.shop_price')
                    ->where('user_id', session('user.user_id') ?? 0)
                    ->whereOr('session_id', session_id())
                    ->order('b.click_id DESC')
                    ->select();
                $this->assign('list', $list);
                $tempate = 'right_bar_history';
                break;
            case 'mpbtn_collection'://我的收藏
                $user_id    = session('user.user_id') ?? 0;
                $goods_list = Db::name('goods_collect a')
                    ->join('goods b', 'a.goods_id=b.goods_id')
                    ->where('a.user_id', $user_id)
                    ->field('a.rec_id,a.goods_id,b.shop_price,b.original_img')
                    ->select();
                $store_list = Db::name('store_collect a')
                    ->join('store b', 'a.store_id=b.store_id')
                    ->where('a.user_id', $user_id)
                    ->field('a.rec_id,a.store_id,b.store_name,b.logo')
                    ->select();
                $this->assign('goods_list', $goods_list);
                $this->assign('store_list', $store_list);

                $tempate = 'right_bar_collection';
                break;
        }

        return view('ajax/' . $tempate);
    }

    public function ajaxTopCart()
    {
        $cartList = CartModel::cartList(session('user.user_id'), session_id());
        $this->assign('cartList', $cartList);
        return json(['cart_num' => $cartList['cart_num'], 'content' => $this->fetch('ajax/top_cart')]);
    }

    public function ajaxSeeMore()
    {
        $store_id   = input('store_id/d', 0);
        $goodsModel = new GoodsModel();
        $goodsList  = $goodsModel->where(['store_id' => $store_id])
            ->field('goods_id,goods_name,shop_price,original_img')
            ->order('sort_order DESC')
            ->limit(6)->select();
        $this->assign('goodsList', $goodsList);
        return view('ajax/see_more');
    }

    public function history()
    {

        $sort    = input("sort", 'goods_id');
        $order   = input('order');
        $orderby = '';
        switch ($sort) {
            case 'goods_id':
                $orderby = "b.goods_id {$order}";
                break;
            case 'total_sale_num':
                $orderby = "b.total_sale_num {$order}";
                break;
            case 'last_update':
                $orderby = "b.goods_id {$order}";
                break;
            case 'comment_num':
                $orderby = "b.comment_num {$order}";
                break;
            case 'shop_price':
                $orderby = "b.shop_price {$order}";
                break;
        }
        $this->assign('sort', $sort);
        $this->assign('order', $order);

        $map   = [];
        $map[] = ['a.user_id', '=', session('user.user_id') ?? 0];
        $map[] = ['b.review_status', '=', 2];
        $map[] = ['b.is_show', '=', 1];
        $map[] = ['b.is_on_sale', '=', 1];

        $ship = input('ship/d', 0);
        if ($ship) {
            if ($ship == 1) {
                $map[] = ['b.is_shipping', '=', $ship];
            }
        }
        $self = input('self', 1);
        if (isset($self)) {
            if ($self == 0) {
                $map[] = ['a.store_id', '=', $self];
            }
        }
        $have = input('have', 0);
        if (isset($have)) {
            if ($have > 0) {
                $map[] = ['b.goods_number', '>', 0];
            }
        }
        $this->assign('ship', $ship);
        $this->assign('self', $self);
        $this->assign('have', $have);


        $page   = input('page', 1);
        $rows   = input('rows', 20);
        $field1 = 'a.user_id,a.click_id,b.goods_id,b.store_id,b.goods_name,b.shop_price,b.market_price,b.total_sale_num,virtual_sale_num,b.original_img,c.store_name,d.product_id';
        $list   = Db::name("goods_click")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->join("store c", "a.store_id=c.store_id", "LEFT")
            ->join("products d", "a.goods_id=d.goods_id", "LEFT")
            ->field($field1)
            ->where($map)
            ->whereOr('session_id', session_id())
            ->order($orderby)
            ->group("a.goods_id")
            ->page($page, $rows)
            ->select();

        //echo Db::name("goods_click")->getLastSql();exit;
        //print_r($list);exit;
        $goods_gallery = Db::name('goods_gallery')
            ->where('goods_id', 'IN', array_column($list, 'goods_id'))
            ->order('img_sort desc')
            ->select();
        foreach ($list as $k => $v) {
            $ids      = array_column($goods_gallery, 'goods_id');
            $valCount = array_count_values($ids);
            if (isset($valCount[$v['goods_id']])) {
                $list[$k]['goods_gallery'] = array_slice($goods_gallery, array_search($v['goods_id'], $ids), $valCount[$v['goods_id']]);
            } else {
                $list[$k]['goods_gallery'] = [];
            }
            $list[$k]['duibi'] = false;
        }
        $total       = Db::name("goods_click")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->where($map)
            ->count();
        $admap       = [];
        $admap[]     = ['a.is_ad', '=', 1];
        $admap[]     = ['a.is_on_sale', '=', 1];
        $admap[]     = ['a.is_show', '=', 1];
        $admap[]     = ['a.review_status', '=', 2];
        $field2      = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
        a.original_img,a.total_sale_num,virtual_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,is_ad';
        $adgoodsList = Db::name("goods")->alias("a")
            ->field($field2)
            ->where($admap)
            ->limit(5)
            ->order("a.goods_id desc")
            ->select();
        $this->assign("adgoodsList", $adgoodsList);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('total', $total);
        return view();
    }

    /**
     *清除浏览历史记录
     * @return \think\response\Json
     */
    public function clearHistory()
    {
        $user_id    = session('user.user_id') ?? 0;
        $session_id = session_id();
        Db::name('goods_click')->where("(user_id={$user_id} OR session_id='{$session_id}')")->delete();
        return json();
    }

    /**
     * 智能推荐
     * @return \think\response\View
     */
    public function suggestions()
    {
        $keyword    = input('keyword', '');
        $cmap       = [];
        $cmap[]     = ['cat_name|keywords|cat_desc|pinyin_keyword', 'like', "%{$keyword}%"];
        $catList    = Db::name("category")
            ->field("cat_id,cat_name,parent_id")
            ->limit(20)
            ->where($cmap)
            ->select();
        $catCount   = count($catList);
        $gmap       = [];
        $gmap[]     = ['goods_name|pinyin_keyword|keywords', 'like', "%{$keyword}%"];
        $gmap[]     = ['is_on_sale', '=', 1];
        $gmap[]     = ['is_show', '=', 1];
        $gmap[]     = ['review_status', '=', 2];
        $goodsList  = Db::name("goods")
            ->field("goods_id,goods_name,store_id")
            ->where($gmap)
            ->limit(20)
            ->select();
        $goodsCount = count($goodsList);
        $this->assign('keyword', $keyword);
        $this->assign('catList', $catList);
        $this->assign('catCount', $catCount);
        $this->assign('goodsList', $goodsList);
        $this->assign('goodsCount', $goodsCount);
        return view('ajax/suggestions');
    }

    #品牌专区
    public function brand()
    {
        $map    = [];
        $map[]  = ["parent_id", "=", 0];
        $cat_id = input("cat_id", 0);
        if ($cat_id > 0) {
            $map[] = ['cat_id', '=', $cat_id];
        }
        $categoryListTemp = Db::name("category")
            ->field("cat_id,cat_name")
            ->where("parent_id", "=", 0)
            ->select();
        if ($cat_id > 0) {
            $md5brandid = md5('md5brandid_' . $cat_id);
        } else {
            $md5brandid = md5('md5brandid_0');
        }
        if (Cache::get($md5brandid)) {
            $categoryList = Cache::get($md5brandid);
        } else {
            $categoryList = Db::name("category")
                ->field("cat_id,cat_name")
                ->where($map)
                ->select();
            foreach ($categoryList as $k => $v) {
                $categoryList[$k]['ids'] = getChildrenData($v['cat_id'], 1);
            }
            foreach ($categoryList as $k => $v) {
                $categoryList[$k]['list'] = Db::name("goods")->alias("a")
                    ->leftJoin("brand b", "a.brand_id=b.brand_id")
                    ->field("b.*")
                    ->group("b.brand_id")
                    ->where("a.cat_id", "in", $v['ids'])
                    ->select();
            }
            Cache::set($md5brandid, $categoryList);
        }
        $this->assign('cat_id', $cat_id);
        $this->assign('categoryList', $categoryList);
        $this->assign('categoryListTemp', $categoryListTemp);
        return view();
    }

    #店铺街
    public function store_street()
    {
        $keywords = input('keywords');
        $page     = input('page', 1);
        $rows     = input('rows', 10);
        $province = input('province', 0);

        if (Request::isAjax()) {
            $map = [
                ['is_delete', '=', 0],
                ['status', '=', 1],
                ['store_close', '=', 1]
            ];
            if ($province > 0) {
                $map[] = ['city', '=', $province];
            }
            if ($keywords) {
                $map[] = ['store_name', 'like', '%' . $keywords . '%'];
            }
            $count = Db::name("store")->where($map)->count();
            $list  = Db::name("store")
                ->where($map)
                ->field('store_id,store_name,logo,store_subtitle,store_keyword,company')
                ->page($page, $rows)->select();
            if ($list) {
                $GoodsModel = new GoodsModel();
                foreach ($list as $index => $item) {
                    $list[$index]['goods_list'] = $GoodsModel->where('store_id', $item['store_id'])
                        ->field('goods_id,goods_name,shop_price,market_price,original_img')
                        ->limit(4)
                        ->select()
                        ->toArray();;
                }
            }

            ajaxmsg('ok', 1, ['list' => $list, 'count' => $count]);
        }


        $img = Db::name('website_nav')->whereRaw('url REGEXP "index/store_street"')->value('imgUrl');
        $this->assign('banner', $img ?: '');
        return view();
    }

    public function Im()
    {
        if (!session('user.user_id')) {
            //todo 未登录关闭聊天窗口
        }
        $to  = input('uid', 'admin-1');
        $uid = explode('-', $to);
        if ($uid[0] == 'seller') {
            $info = Db::name('seller a')->join('store b', 'a.store_id=b.store_id')->where('a.id', $uid[1])->field('a.id,b.store_name,b.logo')->find();
        } else {
            $siteInfo = Db::name('shop_config')->where('shop_group', 'site_config')->column('value', 'code');
            $info     = [
                'store_name' => $siteInfo['shop_name'],
                'logo'       => $siteInfo['shop_logo'],
                'id'         => $uid[1]
            ];
        }

        if (!$info) {
            //错误跳出;
            $this->error('客服信息不存在~');
        }
        $info['to'] = $to;
        $user       = session('user');
        $this->assign('user', $user);
        $this->assign('kf', $info);
        return view();
    }

    public function enong()
    {
        $pic = Db::name('website_nav')->where('id=11')->value('imgurl');
        $this->assign('pic', $pic);
        return view();
    }

    public function qrcode_jump()
    {
        $header = $this->request->header('user-agent');
        $link   = Db::name('shop_config')->where('`code`="wap_app"')->value('value');
        if (strpos(strtolower($header), 'micromessenger') === false) {
            $this->redirect($link);
        }

        return view();
    }

    /**
     * 扶贫
     * @return \think\response\View
     */
    public function pa()
    {
        $img = Db::name('website_nav')->whereRaw('url REGEXP "index/pa"')->value('imgUrl');
        $this->assign('banner', $img ?: '');
        return view();
    }

    public function gift_card()
    {
        $img = Db::name('website_nav')->whereRaw('url REGEXP "index/gift_card"')->value('imgUrl');
        $this->assign('banner', $img ?: '');
        return view();
    }
}
