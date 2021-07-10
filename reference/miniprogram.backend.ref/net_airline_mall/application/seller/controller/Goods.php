<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 10:59
 */

namespace app\seller\controller;

use think\Db;
use app\seller\validate\Goods as GoodsValidate;
use app\home\model\Comment;

class Goods extends Base
{
    /**
     * 商品列表
     * @return mixed
     */
    public function goods_list()
    {
        if ($this->request->isAjax()) {
            $cat_id   = input('cat_id/d');
            $user_cat = input('user_cat/d');
            $keywords = input('keywords');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'goods_id');
            $sort     = input('sort', 'desc');
            $act      = input('act', __FUNCTION__);
            $map      = [
                ['store_id', '=', $this->store_id],
                ['is_delete', '=', 0]
            ];
            if ($act == 'virtual_goods') {
                $map[] = ['is_real', '=', 0];
            } else {
                $map[] = ['is_real', '=', 1];
            }
            if ($keywords) $map[] = ['goods_id|goods_name', 'like', "%{$keywords}%"];
            if ($user_cat > 0) {
                $map[] = ['user_cat', '=', $user_cat];
            }
            if ($cat_id > 0) {
                $map[] = ['cat_id', '=', $cat_id];
            }
            $count = Db::name('goods')->where($map)->count();
            $list  = Db::name('goods')->where($map)->field('goods_desc', true)->order($field, $sort)->page($page, $pageSize)->select();
            $brand = [];
            if ($list) $brand = get_brand_by_id(array_column($list, 'brand_id'));
            $data = ['list' => $list, 'page' => $page, 'count' => $count, 'brand' => $brand];
            return json($data);
        }

        return $this->fetch(__FUNCTION__);
    }

    /**
     * 商品添加/编辑
     */
    public function add_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            //goods单字段修改编辑
            if (input('operate') == 'modify') {
                if ($data['field'] == 'goods_number') {
                    if (Db::name('products')->where(['goods_id' => $data['goods_id']])->count() > 0) {
                        return json(['msg' => '存在多规格,请使用编辑模式增加库存'], 403);
                    }
                    $goods = Db::name('goods')->where(['goods_id' => $data['goods_id'], 'store_id' => $this->store_id])->find();
                    goods_stock_log($data['goods_id'], $data['value'] - $goods['goods_number'], $goods['goods_name'], $this->store_id, $this->seller['id'], '', '', session('seller.username'));
                }
                $res = Db::name('goods')->where(['goods_id' => $data['goods_id'], 'store_id' => $this->store_id])->update([$data['field'] => $data['value']]);
                if (!$res) return json(['msg' => '操作失败', 403]);
                return json(['content' => $data['value']]);
            }

            //数据验证
            $validate = new GoodsValidate();
            $result   = $validate->check($data['goods']);
            if (!$result) {
                return json($validate->getError(), 403);
            }
            $time = time();
            try {
                Db::startTrans();
                $stock                       = 0;
                $data['goods']['is_show']    = 1;
                $data['goods']['product_id'] = '';//重置规格ID
                if ($data['goods']['freight'] == 1) {
                    $data['goods']['transport_id'] = 0;
                }
                if ($data['goods']['goods_id'] > 0) {
                    $data['goods']['last_update']   = $time;
                    $goods_id                       = $data['goods']['goods_id'];
                    $data['goods']['review_status'] = 1;//重置审核状态
                    $stock                          = Db::name('goods')->where('goods_id', $goods_id)->value('goods_number');//更新之前的库存
                    Db::name('goods')->update($data['goods']);
                    Db::name('goods_attr')->where("goods_id", $goods_id)->delete();
                    Db::name('goods_gallery')->where("goods_id", $goods_id)->delete();
                    $spec_stock = Db::name('products')->where("goods_id", $goods_id)->column('product_number', 'tempvalue');//更新之前多规格库存
                    Db::name('products')->where("goods_id", $goods_id)->delete();
                    Db::name('goods_link')->where("goods_id", $goods_id)->delete();
                    Db::name('goods_map')->where('goods_id', $goods_id)->delete();

                } else {
                    unset($data['goods']['goods_id']);
                    $data['goods']['add_time'] = $time;
                    $data['goods']['store_id'] = $this->store_id;
                    if (!trim($data['goods']['goods_sn'])) {
                        $data['goods']['goods_sn'] = makeGoodsSn();
                    }
                    $data['goods']['review_status'] = 1;
                    $goods_id                       = Db::name('goods')->strict(false)->insertGetId($data['goods']);
                }

                //更新相册
                if (isset($data['gallery'])) {
                    foreach ($data['gallery'] as $index => $gallery) {
                        unset($data['gallery'][$index]['img_id']);
                        $data['gallery'][$index]['goods_id'] = $goods_id;
                    }
                    Db::name('goods_gallery')->insertAll($data['gallery']);
                } else {
                    Db::name('goods_gallery')->insert([
                        'goods_id'     => $goods_id,
                        'img_original' => $data['goods']['original_img'],
                        'external_url' => '',
                    ]);
                }

                //更新商品规格列表
                if (isset($data['attribue'])) {
                    $goods_attr = [];
                    foreach ($data['attribue'] as $index => $attr) {
                        foreach ($attr['attr_values'] as $check) {
                            $goods_attr[] = [
                                'goods_id'     => $goods_id,
                                'attr_id'      => $attr['attr_id'],
                                'attr_value'   => $check['val'],
                                'attr_checked' => $check['checked'],
                            ];
                        }
                    }
                    $rss = Db::name('goods_attr')->insertAll($goods_attr);
                }

                //更新商品规格价格,库存表
                if (isset($data['attr'])) {
                    foreach ($data['attr'] as $index => $product) {
                        $data['attr'][$index]['goods_id']  = $goods_id;
                        $attr_order                        = '"' . str_replace(' ', '","', $product['tempvalue']) . '"';
                        $attr_ids                          = Db::name('goods_attr')
                            ->where(['goods_id' => $goods_id])
                            ->whereIn('attr_value', explode(' ', $product['tempvalue']))
                            ->orderRaw('field(attr_value,' . $attr_order . ')')
                            ->column('goods_attr_id');
                        $data['attr'][$index]['attr_keys'] = implode('_', $attr_ids);
                    }
                    Db::name('products')->insertAll($data['attr']);
                    $price        = min(array_column($data['attr'], 'product_price'));
                    $market_price = max(array_column($data['attr'], 'product_market_price'));
                    $number       = array_sum(array_column($data['attr'], 'product_number'));
                    $ids          = Db::name('products')->where('goods_id', $goods_id)->column('product_id');
                    Db::name('goods')->where('goods_id', $goods_id)->update(['shop_price' => $price, 'goods_number' => $number, 'market_price' => $market_price, 'product_id' => implode(',', $ids)]);

                    //多规格,库存日志
                    foreach ($data['attr'] as $idx => $item) {
                        if (isset($spec_stock[$item['tempvalue']])) {
                            if ($item['product_number'] - $spec_stock[$item['tempvalue']] != 0) {
                                goods_stock_log($goods_id, $item['product_number'] - $spec_stock[$item['tempvalue']], $data['goods']['goods_name'], $this->store_id, $this->seller['id'], $item['tempvalue'], '', session('seller.username'));
                            }
                        } else {
                            goods_stock_log($goods_id, $item['product_number'], $data['goods']['goods_name'], $this->store_id, $this->seller['id'], $item['tempvalue'], '', session('seller.username'));
                        }
                    }
                } else {
                    //普通商品库存日志
                    goods_stock_log($goods_id, $data['goods']['goods_number'] - $stock, $data['goods']['goods_name'], $this->store_id, $this->seller['id'], '', '', session('seller.username'));
                }

                //更新商品属性
                if (isset($data['product_attr'])) {
                    $product_attr = [];
                    array_walk($data['product_attr'], function ($v, $k) use (&$product_attr, $goods_id) {
                        $product_attr[] = [
                            'goods_id'       => $goods_id,
                            'product_arr_id' => $k,
                            'element'        => $v
                        ];
                    });

                    if (count($product_attr)) {
                        Db::name('goods_map')->insertAll($product_attr);
                    }
                }

                //更新关联商品
                if (isset($data['related'])) {
                    $goods_link = [];
                    foreach ($data['related'] as $link_id) {
                        $goods_link[] = [
                            'goods_id'      => $goods_id,
                            'link_goods_id' => $link_id,
                            'is_double'     => $data['is_double'],
                            'admin_id'      => $this->seller['id']
                        ];
                    }
                    Db::name('goods_link')->insertAll($goods_link);
                }

                Db::commit();
                return json();
            } catch (\Exception $e) {
                Db::rollback();
                return json($e->getMessage(), 403);
            }
        }

        //编辑商品，展示信息
        $id        = input('id/d', 0);
        $cat_ids   = [];
        $gallery   = [];
        $attr_list = [];
        $product   = [];
        $related   = [];
        $is_double = 1;
        $category1 = $category2 = $category3 = $cate_path = [];
        $attr_cat  = Db::name('product_cate')->where(['store_id' => 0, 'parent_id' => 0])->order('sort DESC')->select();
        if ($id) {
            $goods     = Db::name('goods')->where(['goods_id' => $id, 'store_id' => $this->store_id])->find();
            $cat       = Db::name('category')->where('cat_id', $goods['cat_id'])->find();
            $gallery   = Db::name('goods_gallery')->where('goods_id', $id)->select();
            $attr_list = attributelistGet($id);
            $product   = Db::name('products')->where('goods_id', $id)->field('tempvalue,attr_keys,product_sn,bar_code,product_number,product_price,product_promote_price,product_market_price,product_warn_number')->select();

            $categories[] = $cat;

            do {
                $cat          = Db::name('category')->where('cat_id', $cat['parent_id'])->find();
                $categories[] = $cat;
            } while ($cat['parent_id'] > 0);

            $rows = [];
            for ($i = 0; $i < 3; $i++) {
                if (isset($categories[$i])) {
                    $rows[$i] = Db::name('category')->where(['is_delete' => 0, 'parent_id' => $categories[$i]['parent_id']])->order('sort_order asc')->column('cat_id,cat_name,parent_id');
                } else {
                    break;
                }
            }
            $categories = array_reverse($categories);
            $cate_path  = array_column($categories, 'cat_name');
            $cat_ids    = array_column($categories, 'cat_id');

            list($category1, $category2, $category3) = array_pad(array_reverse($rows), 3, []);
            $related = Db::name('goods_link')->where('goods_id', $id)->column('is_double', 'link_goods_id');
            if ($related) {
                $is_double = current($related);
                $related   = Db::name('goods')->where('goods_id', 'IN', array_keys($related))->column('goods_name', 'goods_id');
            }
        } else {
            $table = Db::query('SHOW COLUMNS FROM ' . config('database.prefix') . 'goods');
            //不使用字段默认值，与后台保持一致
            //$goods = array_combine(array_column($table, 'Field'), array_column($table, 'Default'));
            $goods            = array_fill_keys(array_column($table, 'Field'), '');
            $goods['is_real'] = input('act') == 'virtual_goods' ? 0 : 1;
        }

        if ($goods['user_cat'] > 0) {
            $store_cat = storeCategoryPath($this->store_id, $goods['user_cat']);
        } else {
            $store_cat = storeCategoryPath($this->store_id, 0, 'parent_id');
        }

        $transport          = Db::name('transport')->where(['store_id' => $this->store_id, 'is_delete' => 0])->order('shipping_id desc')->column('transport_name', 'transport_id');
        $product_attr_value = Db::name('goods_map')->where('goods_id', $id)->column('element', 'product_arr_id');
        $product_attr_key   = ['other' => [], 'group' => []];
        if ($product_attr_value) {
            $product_attr_key = Db::name('product_attr')->whereIn('id', array_keys($product_attr_value))->order('group_id ASC,sort DESC')->select();
            $product_attr_key = fixProductAttr($product_attr_key);
        }

        $this->assign('product_attr_key', $product_attr_key);
        $this->assign('product_attr_value', $product_attr_value ? json_encode($product_attr_value, JSON_UNESCAPED_UNICODE) : '{}');
        $this->assign('attr_cat', $attr_cat);
        $this->assign('transport', $transport);
        $this->assign('category1', $category1);
        $this->assign('category2', $category2);
        $this->assign('category3', $category3);
        $this->assign('store_cat', $store_cat);
        $this->assign('cat_ids', $cat_ids);
        $this->assign('cate_path', $cate_path);
        $this->assign('attr_list', $attr_list);
        $this->assign('product', $product);
        $this->assign('gallery', $gallery);
        $this->assign('goods', $goods);
        $this->assign('related', $related);
        $this->assign('is_double', $is_double);
        return $this->fetch();
    }

    /**
     * 商品删除恢复
     * @return mixed|\think\response\Json
     */
    public function del_recycle()
    {
        if ($this->request->isPost()) {
            $goods_id = input('goods_id');
            if (input('?is_delete')) {
                //软删除
                $flag = Db::name('goods')->where(['goods_id' => $goods_id, 'store_id' => $this->store_id])->update(['is_delete' => 1]);
            } elseif (input('act') == 'real_del') {
                //物理删除
                $flag = Db::name('goods')->where(['goods_id' => $goods_id, 'store_id' => $this->store_id])->delete();
                Db::name('goods_attr')->where('goods_id', $goods_id)->delete();
                Db::name('goods_gallery')->where('goods_id', $goods_id)->delete();
                Db::name('products')->where('goods_id', $goods_id)->delete();
            } else {
                //还原
                $flag = Db::name('goods')->where(['goods_id' => $goods_id, 'store_id' => $this->store_id])->update(['is_delete' => 0]);
            }
            if (!$flag) return json('操作失败', 403);
            return json();
        }

        $page     = input('page/d', 1);
        $pageSize = input('pageSize/d', 15);
        $field    = input('field', 'goods_id');
        $sort     = input('sort', 'desc');
        $map      = [
            'is_delete' => 1,
            'store_id'  => $this->store_id
        ];
        $keywords = input('keywords');
        if ($keywords) $map = '(is_delete=1 AND store_id=' . $this->store_id . ') AND (goods_id LIKE "%' . $keywords . '%"' . ' OR goods_name LIKE "%' . $keywords . '%")';

        $count = Db::name('goods')->where($map)->count();
        $list  = Db::name('goods')->where($map)
            ->field('goods_id,goods_name,brand_id,is_real,goods_sn,shop_price,original_img')
            ->order($field, $sort)->page($page, $pageSize)
            ->select();
        $brand = [];
        if ($list) $brand = get_brand_by_id(array_column($list, 'brand_id'));
        $data = ['list' => $list, 'page' => $page, 'count' => $count, 'brand' => $brand];
        if ($this->request->isAjax()) return json($data);

        return $this->fetch();
    }

    /**
     * 店铺商品分类
     * @return mixed
     */
    public function store_category()
    {
        if ($this->request->isPost()) {
            if (input('operate') == 'modify') {
                $map   = [
                    ['store_id', '=', $this->store_id],
                    ['cat_id', '=', input('cat_id')]
                ];
                $value = input('value');
                Db::name('store_category')->where($map)->update([input('field') => $value]);
                return json(['content' => $value]);
            }
            if (input('operate') == 'del') {
                $cat_id = input('cat_id/d');
                $flag   = Db::name('store_category')->where(['cat_id' => $cat_id, 'store_id' => $this->store_id])->update(['is_delete' => time()]);
                if (!$flag) return json('操作失败', 403);
                return json();
            }
            return json('非法操作', 401);
        }

        if ($this->request->isAjax()) {
            $parent_id = input('parent_id/d', 0);
            $page      = input('page/d', 1);
            $pageSize  = input('pageSize/d', 15);
            $map       = ['store_id' => $this->store_id, 'is_delete' => 0, 'parent_id' => $parent_id];
            $count     = Db::name('store_category')->where($map)->count();
            $list      = Db::name('store_category')->where($map)->order('sort desc')->page($page, $pageSize)->select();
            if ($list) {
                $map       = [
                    ['user_cat', 'IN', array_column($list, 'cat_id')],
                    ['store_id', '=', $this->store_id]
                ];
                $cat_count = Db::name('goods')->where($map)->group('user_cat')->column('count(goods_id) as cat_count', 'user_cat');
                foreach ($list as $index => $item) {
                    $list[$index]['cat_count'] = isset($cat_count[$item['cat_id']]) ? $cat_count[$item['cat_id']] : 0;
                }
            }
            return json(['count' => $count, 'list' => $list, 'page' => $page]);
        }
        return $this->fetch();
    }

    /**
     * 店铺分类新增/编辑
     */
    public function cat_edit()
    {
        if ($this->request->isPost()) {
            $data   = input('param.');
            $result = $this->validate($data, [
                'cat_name'        => 'require',
                'pinyin_keywords' => 'require',
                'touch_icon'      => 'require|url'
            ], [
                'cat_name'        => '请填写分类名称',
                'pinyin_keywords' => '请填写分类拼音',
                'touch_icon'      => '请上传正确的分类图标'
            ]);
            if (true !== $result) {
                return json($result, 403);
            }
            $data['store_id'] = $this->store_id;
            if (isset($data['cat_id'])) {
                $res = Db::name('store_category')->update($data);
            } else {
                $data['add_time'] = time();
                $res              = Db::name('store_category')->insert($data);
            }
            if ($res) return json();
            return json('保存失败', 403);
        }

        $cat_id    = input('cat_id/d');
        $parent_id = input('parent_id/d');
        if ($cat_id > 0) {
            $category = storeCategoryPath($this->store_id, $cat_id);
        } else {
            $category = storeCategoryPath($this->store_id, $parent_id, 'parent_id');
        }
        return $this->fetch('', ['category' => $category]);
    }

    /**
     * 平台分类
     * @return mixed|\think\response\Json
     */
    public function category()
    {
        if ($this->request->isAjax()) {
            $parent_id = input('parent_id/d', 0);
            $page      = input('page/d', 1);
            $pageSize  = input('pageSize/d', 15);

            $map = [['is_delete', '=', 0]];

            //if ($parent_id == 0) {
            //    $ids   = Db::name('store_bind_category')->where('store_id', $this->store_id)->column('cat_id');
            //    $map[] = ['cat_id', 'IN', $ids];
            //}

            //if ($parent_id > 0) {
            $map[] = ['parent_id', '=', $parent_id];
            //}

            $count = Db::name('category')->where($map)->count();
            //dump($count);die;
            $list = Db::name('category')->where($map)->order('sort_order desc')->page($page, $pageSize)->select();

            if ($list) {
                $map       = [
                    ['cat_id', 'IN', array_column($list, 'cat_id')],
                    ['store_id', '=', $this->store_id]
                ];
                $cat_count = Db::name('goods')->where($map)->group('cat_id')->column('count(goods_id) as cat_count', 'cat_id');
                foreach ($list as $index => $item) {
                    $list[$index]['cat_count'] = isset($cat_count[$item['cat_id']]) ? $cat_count[$item['cat_id']] : 0;
                }
            }

            return json(['count' => $count, 'list' => $list, 'page' => $page]);
        }
        return $this->fetch();
    }

    /**
     * 评论
     * @return mixed|\think\response\Json
     */
    public function comment()
    {
        if ($this->request->isAjax()) {
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'comment_id');
            $sort     = input('sort', 'desc');
            $keywords = input('keyword');

            $map = [
                ['a.store_id', '=', $this->store_id]
            ];
            if ($keywords) {
                $map[] = ['a.content|b.goods_name', 'LIKE', '%' . $keywords . '%'];
            }
            $count = Db::name('comment a')->where($map)->count();
            $list  = Db::name('comment a')
                ->join('goods b', 'a.goods_id=b.goods_id')
                ->join('user c', 'a.user_id=c.user_id', 'LEFT')
                ->where($map)
                ->field('a.comment_id,a.type as type,a.comment_rank,a.store_id,a.parent_id,a.ip_address,a.user_id,a.goods_id,a.enabled,a.create_time,b.goods_name')
                ->order($field, $sort)
                ->page($page, $pageSize)
                ->select();

            return json(['count' => $count, 'page' => $page, 'list' => $list]);
        }
        return $this->fetch();
    }

    /**
     * 回复评论
     * @return mixed|\think\response\Json
     */
    public function comment_reply()
    {
        if ($this->request->isPost()) {
            $post = input('post.');
            //dump($post);die;
            $result = $this->validate($post, [
                'comment_id' => 'require|gt:0',
                'user_name'  => 'require',
                'content'    => 'require'
            ], [
                'comment_id' => '评论ID错误!',
                'user_name'  => '用户名不正确!',
                'content'    => '用户名不能为空!'
            ]);
            if (true !== $result) {
                return json($result, 403);
            }
            $comment = Db::name('comment')->where(['comment_id' => $post['comment_id'], 'store_id' => $this->store_id])->find();
            if (!$comment) return json('回复的评论不存在', 403);

            if ($post['reply_id'] > 0) {
                $res = Db::name('comment')->where(['type' => 1, 'comment_id' => $post['reply_id']])->update(['content' => $post['content']]);
            } else {
                $post['id']      = $post['comment_id'];
                $post['user_id'] = $this->seller['id'];
                $model           = new Comment;
                $res             = $model->addReply($post, 1);
            }

            if ($res) return json();
            return json('操作失败,未保存', 403);
        }

        $id      = input('id/d');
        $comment = Db::name('comment a')
            ->join('goods b', 'a.goods_id=b.goods_id')
            ->where(['a.store_id' => $this->store_id, 'a.comment_id' => $id])
            ->field('a.*,b.goods_name')
            ->find();
        if ($comment) {
            $comment['imgs'] = strlen($comment['imgs']) ? explode(',', $comment['imgs']) : [];
        } else {
            $this->error('评论不存在~');
        }
        $reply = Db::name('comment')->where(['parent_id' => $id, 'store_id' => $this->store_id, 'type' => 1])->find();
        $store = Db::name('store')->where('store_id', $this->store_id)->find();

        $this->assign('comment', $comment);
        $this->assign('reply', $reply);
        $this->assign('store', $store);
        return $this->fetch();
    }

    /**
     * 商家品牌
     * @return mixed|\think\response\Json
     */
    public function store_brand()
    {
        if ($this->request->isAjax()) {
            $keywords = input('keywords');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'sort_order');
            $sort     = input('sort', 'desc');

            $map = [
                ['a.store_id', '=', $this->store_id]
            ];
            if ($keywords) {
                $map[] = ['b.brand_name|b.brand_letter', 'like', '%' . $keywords . '%'];
            }

            $count = Db::name('store_bind_brand a')->join('brand b', 'a.brand_id=b.brand_id')->where($map)->count();
            $list  = Db::name('store_bind_brand a')->join('brand b', 'a.brand_id=b.brand_id')->where($map)
                ->order('b.' . $field, $sort)
                ->page($page, $pageSize)->select();

            return json(['list' => $list, 'count' => $count, 'page' => $page]);
        }
        return $this->fetch();
    }

    /**
     * 品牌申请记录
     * @return mixed|\think\response\Json
     */
    public function brand_apply()
    {
        if ($this->request->isPost()) {
            $apply_id = input('apply_id');
            if (input('act') == 'delete') {
                $flag = Db::name('store_brand_apply')->where('apply_id', $apply_id)->update(['is_delete' => time()]);
                if (!$flag) return json('操作失败', 403);
                return json();
            }
        }

        if ($this->request->isAjax()) {
            $keywords = input('keywords');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'apply_id');
            $sort     = input('sort', 'desc');

            $map = [
                ['is_delete', '=', 0],
                ['store_id', '=', $this->store_id]
            ];
            if ($keywords) {
                $map[] = ['brand_name|brand_letter', 'like', '%' . $keywords . '%'];
            }

            $count = Db::name('store_brand_apply')->where($map)->count();
            $list  = Db::name('store_brand_apply')->where($map)->order($field, $sort)->page($page, $pageSize)->select();

            return json(['list' => $list, 'count' => $count, 'page' => $page]);
        }
        return $this->fetch();
    }

    /**
     * 添加/编辑品牌申请
     * @return mixed|\think\response\Json
     */
    public function add_brand_apply()
    {
        if ($this->request->isPost()) {
            $data = input('post.param');
            if (is_string($data)) {
                //表单提交申请品牌
                parse_str($data, $row);
                $result = $this->validate($row, [
                    'brand_name'   => 'require',
                    'brand_letter' => 'require|alpha',
                    'brand_logo'   => 'require'
                ], [
                    'brand_name'   => '品牌名称必填',
                    'brand_letter' => '品牌拼音/英文必填',
                    'brand_logo'   => '品牌LOGO必须上传'
                ]);
                if (true !== $result) return json($result, 403);
                $row['brand_first_char'] = getInitials($row['brand_name']);
            } else {
                //选择申请品牌
                $brand = Db::name('brand')->where('brand_id', $data['brand_id'])->find();
                if (!$brand) return json('请选择品牌', 403);
                $row = [
                    'store_id'         => $this->store_id,
                    'brand_id'         => $data['brand_id'],
                    'brand_name'       => $brand['brand_name'],
                    'brand_first_char' => $brand['brand_first_char'],
                    'brand_letter'     => $brand['brand_letter'],
                    'brand_logo'       => $brand['brand_logo'],
                    'site_url'         => $brand['site_url'],
                    'sort_order'       => $brand['sort_order'],
                    'audit_status'     => 0,
                ];
                if (isset($data['apply_id'])) $row['apply_id'] = $data['apply_id'];
            }

            $map = [
                ['store_id', '=', $this->store_id],
                ['is_delete', '=', 0],
                ['brand_name', '=', $row['brand_name']]
            ];
            if ($row['apply_id']) {
                $map[] = ['apply_id', '<>', $row['apply_id']];
            }
            $exist = Db::name('store_brand_apply')->where($map)->count();
            if ($exist) {
                return json('您已提交过 【' . $row['brand_name'] . '】 品牌的申请,请确认', 403);
            }
            $row['audit_status'] = 0;
            if (isset($row['apply_id']) && $row['apply_id'] > 0) {
                $flag = Db::name('store_brand_apply')->update($row);
            } else {
                $row['add_time'] = time();
                $row['store_id'] = $this->store_id;
                $flag            = Db::name('store_brand_apply')->insert($row);
            }
            if (!$flag) return json('保存失败', 403);
            return json();
        }

        $apply = Db::name('store_brand_apply')->where('apply_id', input('id/d'))->find();
        return $this->fetch('', ['apply' => $apply]);
    }

    /**
     * 库存日志
     * @return mixed|\think\response\Json
     */
    public function stock_log()
    {
        if ($this->request->isPost()) {
            $ids = input('post.ids');
            Db::name('goods_stock_log')->where('id', 'IN', $ids)->update(['is_delete' => time()]);
            return json();
        }
        $time  = time();
        $start = $time - 2592000;//默认查询30天库存日志
        if ($this->request->isAjax()) {
            $status     = input('status/d');
            $goods_name = input('goods_name/s');
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $page       = input('page/d', 1);
            $pageSize   = input('pageSize/d', 15);
            $map        = [
                ['store_id', '=', $this->store_id],
                ['is_delete', '=', 0]
            ];
            if ($status > 0) {
                $map[] = ['stock', '>', 0];
            }
            if ($status < 0) {
                $map[] = ['stock', '<', 0];
            }
            if ($goods_name) {
                $map[] = ['goods_name', 'LIKE', "%$goods_name%"];
            }
            if ($start_time) {
                $map[] = ['ctime', '>', $start_time];
            } else {
                $map[] = ['ctime', '>', $start];
            }
            if ($end_time) {
                $map[] = ['ctime', '<', $end_time];
            } else {
                $map[] = ['ctime', '<', $time];
            }

            $user  = [];
            $count = Db::name('goods_stock_log')->where($map)->count();
            $list  = Db::name('goods_stock_log')->where($map)->order('id desc')->page($page, $pageSize)->select();
            return json(['list' => $list, 'page' => $page, 'count' => $count, 'user' => $user]);
        }
        return $this->fetch('', ['start' => date('Y-m-d H:i:s', $start), 'end' => date('Y-m-d H:i:s', $time)]);
    }

    /**
     * 商品类型
     * @return mixed|\think\response\Json
     */
    public function attr_type()
    {
        if ($this->request->isAjax()) {
            $keywords = input('keywords');
            $cate_id  = input('cate_id/d');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'sort');
            $sort     = input('sort', 'desc');
            $map      = [
                ['store_id', '=', $this->store_id],
            ];
            if ($cate_id > 0) {
                $map[] = ['cate_id', '=', $cate_id];
            }
            if ($keywords) {
                $map[] = ['name', 'LIKE', '%' . $keywords . '%'];
            }

            $count    = Db::name('product_type')->where($map)->count();
            $list     = Db::name('product_type')->where($map)->order($field, $sort)->page($page, $pageSize)->select();
            $type_cat = [];
            if ($list) {
                $map        = [
                    ['type_id', 'IN', array_column($list, 'id')],
                ];
                $type_cat   = Db::name('product_cate')->whereIn('id', array_column($list, 'cate_id'))->column('name', 'id');
                $attr_count = Db::name('product_attr')->where($map)->group('type_id')->column('count(id) as attr_count', 'type_id');
                foreach ($list as $index => $item) {
                    $list[$index]['attr_count'] = isset($attr_count[$item['id']]) ? $attr_count[$item['id']] : 0;
                }
            }

            $data = ['list' => $list, 'page' => $page, 'count' => $count, 'type_cat' => $type_cat];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 类型 编辑/新增
     * @return mixed
     */
    public function attr_type_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            //goods单字段修改编辑
            if (input('operate') == 'modify') {
                $res = Db::name('product_type')->where(['id' => $data['id'], 'store_id' => $this->store_id])->update([$data['field'] => $data['value']]);
                if (!$res) return json(['msg' => '操作失败', 403]);
                return json(['content' => $data['value']]);
            }

            if (input('operate') == 'del') {
                $id    = input('post.id/d');
                $count = Db::name('product_attr')->where('type_id', $id)->count();
                if ($count) {
                    return json('请先删除该类型下面的属性', 403);
                }

                $res = Db::name('product_type')->where('id', $id)->delete();
                if (!$res) return json('操作失败', 403);
                return json();
            }

            $result = $this->validate($data, [
                'name'    => 'require',
                'cate_id' => 'require|number',
            ], [
                'name'    => '商品类型名称必填',
                'cate_id' => '请选择类型分类',
            ]);
            if (!$result) {
                $this->error($result);
            }

            if ($data['id'] > 0) {
                $res = Db::name('product_type')->where(['id' => $data['id'], 'store_id' => $this->store_id])->update($data);
            } else {
                $exist = Db::name('product_type')->where(['name' => $data['name'], 'store_id' => $this->store_id, 'cate_id' => $data['cate_id']])->count();
                if ($exist) $this->error('该分类下面的类型名称已存在:' . $data['name']);
                $data['create_time'] = time();
                $data['store_id']    = $this->store_id;
                $res                 = Db::name('product_type')->insert($data);
            }
            if (!$res) $this->error('保存失败');
            $this->redirect('goods/attr_type');
        }

        $id   = input('id/d', 0);
        $type = Db::name('product_type')->where('id', $id)->find();
        $cat  = [
            'level1' => [],
            'level2' => [],
            'level3' => []
        ];
        $ids  = [];
        if ($type && $type['cate_id'] > 0) {
            $cate = Db::name('product_cate')->where('id', $type['cate_id'])->find();
            $ids  = [$cate['parent_id']];
            for ($i = $cate['level']; $i > 1; $i--) {
                $cate['parent_id'] = Db::name('product_cate')->where('id', $cate['parent_id'])->value('parent_id');
                $ids[]             = $cate['parent_id'];
            }
            $ids = array_reverse($ids);
            foreach ($ids as $index => $id) {
                $cat['level' . ($index + 1)] = Db::name('product_cate')->where(['store_id' => $this->store_id, 'parent_id' => $id])->select();
            }
        } else {
            $cat['level1'] = Db::name('product_cate')->where(['store_id' => $this->store_id, 'level' => 1])->order('sort DESC')->select();
        }
        $this->assign('ids', $ids);
        $this->assign('type', $type);
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    /**
     * 属性列表
     * @return mixed|\think\response\Json
     */
    public function attr_list()
    {
        $type_id  = input('type_id/d');
        $group_id = input('group_id/d');
        if ($this->request->isAjax()) {
            $keywords = input('keywords');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'sort');
            $sort     = input('sort', 'desc');
            $map      = [];
            if ($type_id > 0) {
                $map = [
                    ['type_id', '=', $type_id],
                ];
            } elseif ($group_id > 0) {
                $map = [
                    ['group_id', '=', $group_id],
                ];
            }

            if ($keywords) {
                $map[] = ['name', 'LIKE', '%' . $keywords . '%'];
            }
            $count = Db::name('product_attr')->where($map)->count();
            $list  = Db::name('product_attr')->where($map)->order($field, $sort)->page($page, $pageSize)->select();

            $data = ['list' => $list, 'page' => $page, 'count' => $count];
            return json($data);
        }

        if ($type_id > 0) {
            $name = Db::name('product_type')->where('id', $type_id)->value('name');
        } else {
            $name = Db::name('product_group')->where('group_id', $group_id)->value('name');
        }

        $data = [
            'type_name'  => $name,
            'value_type' => [
                'checkbox' => '多选',
                'radio'    => '单选',
                'datetime' => '时间控件',
                'select'   => '下拉菜单',
                'textarea' => '文本域',
                'text'     => '自定义'
            ]
        ];
        return $this->fetch('', $data);
    }

    /**
     * 属性 编辑/新增
     * @return mixed|\think\response\Json
     */
    public function attr_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            //goods单字段修改编辑
            if (input('operate') == 'modify') {
                $res = Db::name('product_attr')->where(['id' => $data['id']])->update([$data['field'] => $data['value']]);
                if (!$res) return json(['msg' => '操作失败', 403]);
                return json(['content' => $data['value']]);
            }
            if (input('operate') == 'del') {
                $id        = input('post.id/d');
                $goods_ids = Db::name('goods_map')->where('product_arr_id', $id)->column('goods_id');
                if ($goods_ids) return json('无法删除,该属性在使用商品id为' . implode(',', $goods_ids), 403);

                $res = Db::name('product_attr')->where('id', $id)->delete();
                if (!$res) return json('操作失败', 403);
                return json();
            }

            $data = $data['attr'];
            if ($data['attrvaluejson']) {
                $data['attrvaluejson'] = json_encode(explode(',', str_replace(',,', ',', $data['attrvaluejson'])), JSON_UNESCAPED_UNICODE);
            }
            if ($data['id'] > 0) {
                $res = Db::name('product_attr')->update($data);
            } else {
                $res = Db::name('product_attr')->insert($data);
            }
            if (!$res) return json('操作失败,信息未保存', 403);

            $param = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            parse_str($param, $param);
            $back = [];
            if (isset($param['type_id']) && $param['type_id'] > 0) {
                $back = ['type_id' => $data['type_id']];
            } elseif (isset($param['group_id']) && $param['group_id'] > 0) {
                $back = ['group_id' => $data['group_id']];
            }

            return json(url('goods/attr_list', $back));
        }

        $id = input('id/d');
        if ($id) {
            $attr = Db::name('product_attr')->where('id', $id)->find();
        } else {
            $table = Db::query('SHOW COLUMNS FROM ' . config('database.prefix') . 'product_attr');
            $attr  = array_fill_keys(array_column($table, 'Field'), '');
        }

        if ($attr['type_id']) {
            $type_id  = $attr['type_id'];
            $json_val = json_decode($attr['attrvaluejson'], true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $attr['attrvaluejson'] = implode(PHP_EOL, $json_val);
            } else {
                $attr['attrvaluejson'] = '';
            }
        } else {
            $type_id         = input('type_id/d');
            $attr['type_id'] = $type_id;
        }

        if ($type_id) {
            $cate_id = Db::name('product_type')->where('id', $type_id)->value('cate_id');
        } else {
            $group_id = input('group_id/d');
            $cate_id  = Db::name('product_group')->where('group_id', $group_id)->value('cate_id');
        }
        $map   = ['cate_id' => $cate_id, 'store_id' => $this->store_id];
        $group = Db::name('product_group')->where($map)->order('sort DESC')->select();
        $type  = Db::name('product_type')->where($map)->order('sort DESC')->select();

        $cate = [];
        //FIXME 编辑属性时是否支持重新选择分类,待定
        //for ($i = 0; $i < 3; $i++) {
        //    $cate[$i] = Db::name('product_cate')->where(['store_id' => $this->store_id, 'level' => $i + 1])->column('name,parent_id', 'id');
        //    if (count($cate[$i]) == 0) {
        //        break;
        //    }
        //}

        $data = [
            'attr'       => $attr,
            'group'      => $group,
            'cate'       => $cate,
            'type'       => $type,
            'value_type' => [
                'checkbox' => '多选',
                'radio'    => '单选',
                'datetime' => '时间控件',
                'select'   => '下拉菜单',
                'textarea' => '文本域',
                'text'     => '自定义'
            ]
        ];
        return $this->fetch('', ['data' => $data]);
    }

    /**
     * 属性分组
     * @return mixed|\think\response\Json
     */
    public function attr_group()
    {
        if ($this->request->isAjax()) {
            $keywords = input('keywords');
            $cate_id  = input('cate_id/d');
            $page     = input('page/d', 1);
            $pageSize = input('pageSize/d', 15);
            $field    = input('field', 'sort');
            $sort     = input('sort', 'desc');
            $map      = [
                ['store_id', '=', $this->store_id],
            ];
            if ($cate_id) {
                $map[] = ['cate_id', '=', $cate_id];
            }
            if ($keywords) {
                $map[] = ['name', 'LIKE', '%' . $keywords . '%'];
            }
            $count    = Db::name('product_group')->where($map)->count();
            $list     = Db::name('product_group')->where($map)->order($field, $sort)->page($page, $pageSize)->select();
            $type_cat = [];
            if ($list) {
                $map        = [
                    ['group_id', 'IN', array_column($list, 'group_id')],
                ];
                $type_cat   = Db::name('product_cate')->whereIn('id', array_column($list, 'cate_id'))->column('name', 'id');
                $attr_count = Db::name('product_attr')->where($map)->group('group_id')->column('count(id) as attr_count', 'group_id');
                foreach ($list as $index => $item) {
                    $list[$index]['attr_count'] = isset($attr_count[$item['group_id']]) ? $attr_count[$item['group_id']] : 0;
                }
            }

            $data = ['list' => $list, 'page' => $page, 'count' => $count, 'type_cat' => $type_cat];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 属性分组 编辑/新增
     * @return mixed
     */
    public function attr_group_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            //goods单字段修改编辑
            if (input('operate') == 'modify') {
                $res = Db::name('product_group')->where(['group_id' => $data['id'], 'store_id' => $this->store_id])->update([$data['field'] => $data['value']]);
                if (!$res) return json(['msg' => '操作失败', 403]);
                return json(['content' => $data['value']]);
            }

            if (input('operate') == 'del') {
                $id    = input('post.id/d');
                $count = Db::name('product_attr')->where('group_id', $id)->count();
                if ($count) {
                    return json('请先删除该分组下面的属性', 403);
                }

                $res = Db::name('product_group')->where('group_id', $id)->delete();
                if (!$res) return json('操作失败', 403);
                return json();
            }

            $result = $this->validate($data, [
                'name'    => 'require',
                'cate_id' => 'require|number',
            ], [
                'name'    => '商品分组名称必填',
                'cate_id' => '请选择类型分类',
            ]);
            if (!$result) {
                $this->error($result);
            }

            if ($data['group_id'] > 0) {
                $res = Db::name('product_group')->where(['group_id' => $data['group_id'], 'store_id' => $this->store_id])->update($data);
            } else {
                $exist = Db::name('product_group')->where(['store_id' => $this->store_id, 'name' => $data['name'], 'cate_id' => $data['cate_id']])->count();
                if ($exist) return json('该分类下面的分组名称已存在:' . $data['name'], 403);
                $data['store_id'] = $this->store_id;
                $res              = Db::name('product_group')->insert($data);
            }
            if (!$res) $this->error('保存失败');
            $this->redirect('goods/attr_group');
        }

        $id    = input('id/d', 0);
        $group = Db::name('product_group')->where('group_id', $id)->find();
        $cat   = [
            'level1' => [],
            'level2' => [],
            'level3' => []
        ];
        $ids   = [];
        if ($group && $group['cate_id'] > 0) {
            $cate = Db::name('product_cate')->where('id', $group['cate_id'])->find();
            $ids  = [$cate['parent_id']];
            for ($i = $cate['level']; $i > 1; $i--) {
                $cate['parent_id'] = Db::name('product_cate')->where('id', $cate['parent_id'])->value('parent_id');
                $ids[]             = $cate['parent_id'];
            }
            $ids = array_reverse($ids);
            foreach ($ids as $index => $id) {
                $cat['level' . ($index + 1)] = Db::name('product_cate')->where(['store_id' => $this->store_id, 'parent_id' => $id])->select();
            }
        } else {
            $cat['level1'] = Db::name('product_cate')->where(['store_id' => $this->store_id, 'level' => 1])->order('sort DESC')->select();
        }
        $this->assign('ids', $ids);
        $this->assign('group', $group);
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    /**
     * 属性分类
     * @return mixed|\think\response\Json
     */
    public function attr_cate()
    {
        if ($this->request->isAjax()) {
            $parent_id = input('parent_id/d', 0);
            $page      = input('page/d', 1);
            $pageSize  = input('pageSize/d', 15);
            $map       = ['store_id' => $this->store_id, 'parent_id' => $parent_id];
            $count     = Db::name('product_cate')->where($map)->count();
            $list      = Db::name('product_cate')->where($map)->order('sort desc')->page($page, $pageSize)->select();
            if ($list) {
                $map         = [
                    ['cate_id', 'IN', array_column($list, 'id')],
                    ['store_id', '=', $this->store_id]
                ];
                $type_count  = Db::name('product_type')->where($map)->group('cate_id')->column('count(id) as type_count', 'cate_id');
                $group_count = Db::name('product_group')->where($map)->group('cate_id')->column('count(group_id) as type_count', 'cate_id');

                foreach ($list as $index => $item) {
                    $list[$index]['type_count']  = isset($type_count[$item['id']]) ? $type_count[$item['id']] : 0;
                    $list[$index]['group_count'] = isset($group_count[$item['id']]) ? $group_count[$item['id']] : 0;
                }
            }
            return json(['count' => $count, 'list' => $list, 'page' => $page]);
        }
        return $this->fetch();
    }

    /**
     * 属性分类 编辑/新增
     * @return mixed|\think\response\Json
     */
    public function attr_cate_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            //goods单字段修改编辑
            if (input('operate') == 'modify') {
                $res = Db::name('product_cate')->where(['id' => $data['id'], 'store_id' => $this->store_id])->update([$data['field'] => $data['value']]);
                if (!$res) return json(['msg' => '操作失败', 403]);
                return json(['content' => $data['value']]);
            }

            if (input('operate') == 'del') {
                $id    = input('post.id/d');
                $count = Db::name('product_cate')->where('parent_id', $id)->count();
                if ($count) {
                    return json('请先删除该下级分类', 403);
                }
                $count = Db::name('product_type')->where('cate_id', $id)->count();
                if ($count) {
                    return json('请先删除该分类下面的属性类型', 403);
                }
                $count = Db::name('product_group')->where('cate_id', $id)->count();
                if ($count) {
                    return json('请先删除该分类下面的属性分组', 403);
                }

                $res = Db::name('product_cate')->where('id', $id)->delete();
                if (!$res) return json('操作失败', 403);
                return json();
            }


            $result = $this->validate($data, [
                'name'      => 'require',
                'parent_id' => 'require',
                'sort'      => 'require|number'
            ], [
                'name'      => '请填写属性分类名称',
                'parent_id' => '参数错误',
                'sort'      => '排序值必须为数字'
            ]);
            if (true !== $result) {
                return json($result, 403);
            }
            $data['store_id'] = $this->store_id;
            if ($data['id'] > 0) {
                Db::name('product_cate')->update($data);
            } else {
                Db::name('product_cate')->insert($data);
            }

            return json();
        }

        $id        = input('id/d');
        $parent_id = input('parent_id/d');

        if ($id > 0) {
            $map                 = ['store_id' => $this->store_id, 'id' => $id];
            $info                = Db::name('product_cate')->where($map)->order('sort DESC')->find();
            $info['parent_name'] = Db::name('product_cate')->where('id', $info['parent_id'])->value('name');
        } elseif ($parent_id > 0) {
            $info                = Db::query('SHOW COLUMNS FROM ' . config('database.prefix') . 'product_cate');
            $info                = array_fill_keys(array_column($info, 'Field'), '');
            $info['parent_id']   = $parent_id;
            $parent              = Db::name('product_cate')->where('id', $parent_id)->find();
            $info['parent_name'] = $parent['name'];
            $info['level']       = $parent['level'] + 1;
        } else {
            $info['parent_id']   = $parent_id;
            $info['parent_name'] = '顶级分类';
            $info['level']       = 1;
        }

        return $this->fetch('', ['info' => $info]);
    }
}
