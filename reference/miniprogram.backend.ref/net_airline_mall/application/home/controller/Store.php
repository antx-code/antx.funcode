<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/25 10:08
 */

namespace app\home\controller;

use think\Db;
use think\facade\Cache;
use app\home\model\Store as StoreModel;
use app\home\model\Goods as GoodsModel;

class Store extends Common
{
    protected $store_id;
    protected $store;
    protected $model;
    protected $beforeActionList = [
        'storeAssign' => ['except' => 'collect']
    ];

    protected function storeAssign()
    {
        //店铺信息
        $this->store_id = input('id/d');
        $this->model    = new StoreModel();
        $this->store    = $this->model->alias('a')->join('store_grade b', 'a.grade_id=b.id', 'LEFT')->field('a.*,b.grade_name')->find($this->store_id);
        if (!$this->store) $this->error('您要查看的店铺不存在~');

        //店铺导航
        $this->store->collect_store = Db::name('store_collect')->where('user_id', session('user.user_id'))->where('store_id', $this->store_id)->count();
        $this->store->nav           = Db::name('store_nav')->where(['store_id' => $this->store_id, 'is_show' => 1])->select();
        if (strpos($this->store->slide, ',') && strpos($this->store->slide_url, ',')) {
            $temp['img']            = explode(',', $this->store->slide);
            $temp['url']            = explode(',', $this->store->slide_url);
            $this->store->carcousel = $temp;
        }

        //同行业店铺平均分
        if (!$avg = Cache::get('store_score_avg')) {
            $cat_id    = Db::name('store_bind_category')->where('store_id', $this->store_id)->column('cat_id');
            $store_ids = Db::name('store_bind_category')->whereIn('cat_id', $cat_id)->column('store_id');
            if ($store_ids) {
                $allScore                    = $this->model->whereIn('store_id', $store_ids)->field('store_desc_score,store_service_score,store_delivery_score')->select()->toArray();
                $desc_score                  = array_column($allScore, 'store_desc_score');
                $service_score               = array_column($allScore, 'store_service_score');
                $delivery_score              = array_column($allScore, 'store_delivery_score');
                $avg['store_desc_score']     = round(array_sum($desc_score) / count($desc_score), 1);
                $avg['store_service_score']  = round(array_sum($service_score) / count($service_score), 1);
                $avg['store_delivery_score'] = round(array_sum($delivery_score) / count($delivery_score), 1);
            } else {
                $avg['store_desc_score']     = 5.0;
                $avg['store_service_score']  = 5.0;
                $avg['store_delivery_score'] = 5.0;
            }

            Cache::set('store_score_avg', $avg, 86400);
        }

        //店铺自定义分类
        $limit = 10;
        if ($this->request->action() != 'index') $limit = 30;
        $map = ['store_id' => $this->store_id, 'is_delete' => 0, 'parent_id' => 0, 'is_show' => 1];
        $cat = Db::name('store_category')->field('cat_name,cat_id')->where($map)->order('sort desc')->limit($limit)->select();
        if ($cat) {
            $map  = [
                ['parent_id', 'IN', array_column($cat, 'cat_id')],
                ['is_show', '=', 1],
                ['is_delete', '=', 0],
            ];
            $cat2 = Db::name('store_category')->where($map)->order('parent_id ASC')->field('cat_id,parent_id,cat_name')->select();
            if ($cat2) {
                $cat2_parent_ids = array_column($cat2, 'parent_id');
                $count_val       = array_count_values($cat2_parent_ids);
                array_walk($cat, function (&$item, $index) use ($cat2, $cat2_parent_ids, $count_val) {
                    if (isset($count_val[$item['cat_id']])) $item['children'] = array_slice($cat2, array_search($item['cat_id'], $cat2_parent_ids), $count_val[$item['cat_id']]);
                    else $item['children'] = [];
                });
            }
        }

        $this->assign('store', $this->store);
        $this->assign('avg', $avg);
        $this->assign('cat', $cat);
    }

    public function index()
    {
        $store = $this->store;
        unset($store['seller_money'], $store['frozen_money']);

        //店铺商品 FIXME 更换模板时，数据组装方式不同
        $data = $this->{'template_' . $store['template']}();

        $this->assign('data', $data);
        return view('store_tpl_' . $store['template']);
    }

    public function category()
    {
        $cid      = input('cid/d');
        $page     = input('page/d', 1);
        $sort     = input('sort', 'sort_order');
        $order    = input('order', 'DESC');
        $keywords = input('keyword');
        $tag      = input('tag');

        $store      = $this->store;
        $goodsModel = new GoodsModel();
        $map        = [
            ['store_id', '=', $this->store_id],

        ];
        if ($keywords) {
            $map[] = ['goods_name', 'like', '%' . $keywords . '%'];
        }

        if ($tag && in_array($map, ['store_hot', 'store_best', 'store_new'])) {
            $sort  = $tag;
            $order = 'DESC';
        }

        if ($cid > 0) {
            $map[]    = ['user_cat', '=', $cid];
            $cat_name = Db::name('store_category')->where('cat_id', $cid)->value('cat_name');
            $this->assign('cat_name', $cat_name);
        }

        $goodsList = $goodsModel->where($map)->field('goods_id,goods_name,original_img,shop_price,total_sale_num')->order($sort, $order)->paginate(12);

        $param     = [
            'page'  => $page,
            'sort'  => $sort,
            'order' => $order
        ];
        $paginor   = $goodsList->appends($param)->render();
        $goodsList = $goodsList->toArray()['data'];

        if ($goodsList) {
            $goods_ids = array_column($goodsList, 'goods_id');
            $gallery   = Db::name('goods_gallery')->whereIn('goods_id', $goods_ids)->order('img_sort DESC')->field('goods_id,img_original')->select();
            $ids       = array_column($gallery, 'goods_id');
            $count_ids = array_count_values($ids);

            foreach ($goodsList as $index => $goods) {
                if (isset($count_ids[$goods['goods_id']])) {
                    $goodsList[$index]['gallery'] = array_slice($gallery, array_search($goods['goods_id'], $ids), $count_ids[$goods['goods_id']]);
                } else {
                    $goodsList[$index]['gallery'] = [];
                }
            }
        }

        $this->assign('goodsList', $goodsList);
        $this->assign('paginor', $paginor);
        $this->assign('param', $param);
        return view('category_' . $store['template']);
    }

    public function collect()
    {
        $store_id = input('store_id/d');
        $user_id  = input('user_id/d');
        $type     = input('type/d');//type=0收藏店铺，type=1删除收藏
        if ($type > 0) {
            Db::name('store_collect')->where(['user_id' => $user_id, 'store_id' => $store_id])->delete();
        } else {
            $row = [
                'user_id'  => $user_id,
                'store_id' => $store_id,
                'add_time' => time()
            ];
            Db::name('store_collect')->insert($row);
        }
        return json();
    }

    private function template_1()
    {
        $goodsModel    = new GoodsModel();
        $goods['hot']  = $goodsModel->where('store_id', $this->store_id)->order('store_hot DESC')->limit(6)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();
        $goods['sale'] = $goodsModel->where('store_id', $this->store_id)->order('total_sale_num DESC,is_new DESC')->limit(8)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();
        $goods['new']  = $goodsModel->where('store_id', $this->store_id)->order('store_new DESC')->limit(10)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();

        //店铺广告
        $adv = Db::name('store_adv')->where(['store_id' => $this->store_id, 'is_show' => 1])->select();
        if ($adv) {
            $top = $bottom = [];
            foreach ($adv as $vo) {
                if ($vo['position'] == 0) {
                    $top['img'] = explode(',', $vo['img']);
                    $top['url'] = explode(',', $vo['url']);
                }
                if ($vo['position'] == 1) {
                    $bottom['img']    = explode(',', $vo['img']);
                    $bottom['url']    = explode(',', $vo['url']);
                    $bottom['effect'] = $vo['effect'];
                }
            }
            unset($adv);
            $adv['top']    = $top;
            $adv['bottom'] = $bottom;
        }

        //TODO 店铺促销活动 首页展示
        //店铺优惠券
        $coupon_map = [
            ['store_id', '=', $this->store_id],
            ['is_delete', '=', 0],
            ['send_type', '=', 2],
            ['num', '>', 'send_num'],
            ['send_start_time', '<', time()],
            ['send_end_time', '>', time()]
        ];
        $coupon     = Db::name('coupon')->where($coupon_map)->field('coupon_id,name,money,min_goods_amount')->limit(5)->select();
        return ['adv' => $adv, 'coupon' => $coupon, 'goods' => $goods];
    }

    private function template_2()
    {
        $goodsModel        = new GoodsModel();
        $goods['hot']      = $goodsModel->where('store_id', $this->store_id)->order('store_hot DESC')->limit(8)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();
        $goods['best']     = $goodsModel->where('store_id', $this->store_id)->order('store_best DESC,is_new DESC')->limit(10)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();
        $goods['new']      = $goodsModel->where('store_id', $this->store_id)->order('store_new DESC')->limit(10)->field('goods_id,goods_name,shop_price,original_img,virtual_sale_num')->select();
        $goods['sale']     = $goodsModel->where('store_id', $this->store_id)->order('total_sale_num DESC,store_best DESC')->limit(8)->field('goods_id,goods_name,shop_price,original_img,total_sale_num,virtual_sale_num')->select();
        $goods['favorite'] = $goodsModel->where('store_id', $this->store_id)->order('collect_count DESC,store_hot DESC')->limit(8)->field('goods_id,goods_name,shop_price,original_img,collect_count,virtual_sale_num')->select();

        //店铺广告
        $adv = Db::name('store_adv')->where(['store_id' => $this->store_id, 'is_show' => 1])->select();
        if ($adv) {
            $top = $bottom = [];
            foreach ($adv as $vo) {
                if ($vo['position'] == 0) {
                    $top['img'] = explode(',', $vo['img']);
                    $top['url'] = explode(',', $vo['url']);
                }
                if ($vo['position'] == 1) {
                    $bottom['img']    = explode(',', $vo['img']);
                    $bottom['url']    = explode(',', $vo['url']);
                    $bottom['effect'] = $vo['effect'];
                }
            }
            unset($adv);
            $adv['top']    = $top;
            $adv['bottom'] = $bottom;
        }

        //TODO 店铺促销活动 首页展示
        //店铺优惠券
        $coupon_map = [
            ['store_id', '=', $this->store_id],
            ['is_delete', '=', 0],
            ['send_type', '=', 2],
            ['num', '>', 'send_num'],
            ['send_start_time', '<', time()],
            ['send_end_time', '>', time()]
        ];
        $coupon     = Db::name('coupon')->where($coupon_map)->field('coupon_id,name,money,min_goods_amount')->limit(5)->select();
        return ['adv' => $adv, 'coupon' => $coupon, 'goodsList' => $goods];
    }
}
