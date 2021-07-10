<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 11:50
 */

namespace app\seller\controller;

use think\Db;
use think\facade\Cache;

/**
 * seller模块需要登录,不需要验证权限的公共接口
 * Class Api
 * @package app\seller\controller
 */
class Api extends Base
{
    /**
     * 文件上传七牛token
     * @return \think\response\Json
     */
    public function uploadtoken()
    {
        //return json(uploadtoken());//七牛上传
        $path = input('path', 'temp');
        return json(ossUploadToken($path));//阿里上传
    }

    public function seller_set()
    {
        $data  = input('post.');
        $allow = ['avatar'];
        try {
            if (!in_array($data['field'], $allow)) {
                return json('权限不足', 403);
            }
            Db::name('seller')->where('username', $this->seller['username'])->update([$data['field'] => $data['value']]);
            $this->seller[$data['field']] = $data['value'];
            session('seller', $this->seller);
            return json();
        } catch (\Exception $e) {
            return json($e->getMessage(), 503);
        }
    }

    /**
     * 清除缓存
     * @return \think\response\Json
     */
    public function clear_cache()
    {
        Cache::clear($this->tag);
        return json();
    }

    /**
     * 管理员搜索
     * @return \think\response\Json
     */
    public function admin_search()
    {
        $keyword = input('keyword/s');
        $map     = [
            'store_id' => $this->store_id,
            'is_admin' => 0,
        ];
        $list    = Db::name('seller')->where($map)->where('username', 'like', '%' . $keyword . '%')->field('id,username,email,add_time,last_login_time')->select();
        return json($list);
    }

    /**
     * 根据prent_id获取下级商品分类
     * @return \think\response\Json
     */
    public function get_category()
    {
        $parent_id = input('parent/d', 0);
        $list      = Db::name('category')->where(['is_delete' => 0, 'parent_id' => $parent_id])->order('sort_order asc')->column('cat_id,cat_name,parent_id');
        return json($list);
    }

    /**
     * 检查商品货号是否唯一
     * @return \think\response\Json
     */
    public function check_goods_sn()
    {
        $map[] = ['goods_sn', '=', input('goods_sn')];
        if (input('?goods_id')) $map[] = ['goods_id', '<>', input('goods_id')];
        $count = Db::name('goods')->where($map)->count();
        if ($count == 0) return json();
        return json('您输入的货号已存在，请换一个', 403);
    }

    /**
     * 获取商品属性分类
     * @return false|string
     */
    public function goods_type()
    {
        $list = Db::name('goods_type')->where('enabled', 1)->column('cat_name', 'cat_id');
        return json($list);
    }

    public function get_attribute()
    {
        $cat_id = input('get.cat_id/d');
        $list   = Db::name('attribute')->where('cat_id', $cat_id)->order('sort_order desc')->column('attr_id,attr_name,attr_type', 'attr_id');
        if ($list) {
            $attr_ids   = array_column($list, 'attr_id');
            $order      = '"' . implode(',', $attr_ids) . '"';
            $goods_attr = Db::name('goods_attr')
                ->where('attr_id', 'IN', $attr_ids)
                ->orderRaw('field(attr_id,' . $order . ') ,attr_sort  DESC')
                ->field('goods_attr_id,attr_id,attr_value')
                ->select();
            foreach ($goods_attr as $index => $item) {
                $list[$item['attr_id']]['attr_values'][] = $item;
            }
            return json($list);
        } else {
            return json();
        }
    }

    /**
     * 搜索本店商品
     * @return \think\response\Json
     */
    public function search_goods()
    {
        $keywords = input('keywords');
        $from     = input('from');

        if ($from == 'order_edit') {
            $list = Db::name('goods a')
                ->join('category b', 'a.cat_id=b.cat_id')
                ->join('brand c', 'a.brand_id=c.brand_id', 'LEFT')
                ->whereLike('a.goods_name|a.keywords|a.goods_sn|a.goods_brief', '%' . $keywords . '%')
                ->where(['a.store_id' => $this->store_id, 'review_status' => 2])
                ->field('a.goods_id,a.goods_name,a.goods_sn,b.cat_name,c.brand_name,a.shop_price,a.market_price,a.goods_number')
                ->limit(20)
                ->select();
            if ($list) {
                $goods_ids = array_column($list, 'goods_id');
                $product   = Db::name('products')->whereIn('goods_id', $goods_ids)->field('product_id,goods_id,tempvalue,product_number,product_price,product_market_price,product_warn_number')->select();
                if ($product) {
                    $spec_goods_id = array_column($product, 'goods_id');
                    $val_count     = array_count_values($spec_goods_id);
                    foreach ($list as $index => $goods) {
                        if (isset($val_count[$goods['goods_id']])) {
                            $list[$index]['spec'] = array_slice($product, array_search($goods['goods_id'], $val_count), $val_count[$goods['goods_id']]);
                        } else {
                            $list[$index]['spec'] = [];
                        }
                    }
                }
                $list = array_combine($goods_ids, $list);
            }
        } else {
            $list = Db::name('goods')->whereLike('goods_name|keywords|goods_sn|goods_brief', '%' . $keywords . '%')->where('store_id', $this->store_id)
                ->column('goods_name', 'goods_id');
        }


        return json($list);
    }

    /**
     * 规格分类选择接口
     */
    public function attributelistAjax()
    {
        attributelistAjax();
    }

    public function getProductTypeCate()
    {
        $id   = input('id/0');
        $list = Db::name('product_cate')->where('parent_id', $id)->order('sort DESC')->select();
        return $this->fetch('', ['list' => $list]);
    }

    /**
     * 属性分类选择接口
     */
    public function getProductType()
    {
        $id       = input('id/d');
        $store_id = input('type/d');
        if ($store_id > 0) {
            $store_id = $this->store_id;
        }
        $cat  = Db::name('product_cate')->where(['parent_id' => $id, 'store_id' => $store_id])->order('sort DESC')->select();
        $type = Db::name('product_type')->where('cate_id', $id)->select();

        return json(['cat' => $cat, 'type' => $type]);
    }

    public function getProductAttr()
    {
        $type_id = input('type_id');

        $result = [
            'other' => [],
            'group' => []
        ];
        $list   = Db::name('product_attr')->where('type_id', $type_id)->order('group_id ASC,sort DESC')->select();

        if ($list) {
            array_walk($list, function (&$v) {
                if ($v['attrvaluejson']) {
                    $v['attrvaluejson'] = json_decode($v['attrvaluejson'], true);
                }
            });
            $ids       = array_column($list, 'group_id');
            $temp      = array_unique($ids);
            $group_ids = [];
            array_walk($temp, function ($id) use (&$group_ids) {
                if ($id > 0) $group_ids[] = $id;
            });

            $val_count = array_count_values($ids);
            if (isset($val_count[0])) {
                $result['other'] = array_slice($list, 0, $val_count[0]);
            }

            if ($group_ids) {
                $group = Db::name('product_group')->whereIn('group_id', $group_ids)->orderRaw('field(group_id,' . implode(',', $group_ids) . ')')->column('name', 'group_id');
                foreach ($group as $gid => $gname) {
                    $result['group'][] = [
                        'group_name' => $gname,
                        'data'       => array_slice($list, array_search($gid, $ids), $val_count[$gid])
                    ];
                }
            }
        }
        return json($result);
    }

    /**
     * 品牌选择接口
     */
    public function brandListAjax()
    {
        $brand_first_char = input("brand_first_char");
        $brand_name       = trim(input("brand_name"));
        $own              = input("is_own");
        $map              = [
            ['a.is_delete', '=', 0]
        ];
        if ($own) {
            $map[] = ['b.store_id', '=', $this->store_id];
        }
        if ($brand_first_char) {
            $map[] = ['a.brand_first_char', 'eq', $brand_first_char];
        }
        if ($brand_name) {
            $map[] = ['a.brand_name', 'like', "%$brand_name%"];
        }
        $list = Db::name('brand a')
            ->join('store_bind_brand b', 'a.brand_id=b.brand_id', 'LEFT')
            ->where($map)
            ->order('a.sort_order')
            ->select();
        return json($list);
    }

    /**
     * 获取用户信息
     * @return mixed
     */
    public function get_user_info()
    {
        $user_id = input('user_id');
        $user    = Db::name('user a')->join('user_rank b', 'a.rank_id=b.rank_id', 'LEFT')
            ->where('a.user_id', $user_id)
            ->field('a.user_name,a.nick_name,a.mobile,a.avatar,b.rank_name')
            ->find();
        return $this->fetch('', ['user' => $user]);
    }

    /**
     * 根据parent_id获取店铺下级分类
     * @param $pid
     * @return array|\PDOStatement|string|\think\Collection|\think\response\Json
     */
    public function get_store_category($pid = null)
    {
        $map = ['store_id' => $this->store_id, 'is_delete' => 0];
        if (!is_null($pid)) {
            $map['parent_id'] = $pid;
        } elseif ($this->request->isAjax() && input('?parent_id')) {
            $map['parent_id'] = input('parent_id');
        }

        $cat = Db::name('store_category')->where($map)->order('sort desc')->select();
        if (!is_null($pid)) return $cat;
        return json($cat);
    }

    /**
     * 检查品牌名称唯一
     * @param string $name
     * @return float|string|\think\response\Json
     */
    public function check_brand_name($name = '')
    {
        $brand_name = input('brand_name');
        if ($name) $brand_name = $name;
        $count = Db::name('brand')->where('brand_name', $brand_name)->count();
        if ($name) return $count;
        if ($count) return json('品牌已存在,请从平台列表中选择', 403);
        return json();
    }

    /**
     * 获取下级地区列表
     * @return \think\response\Json
     */
    public function get_region_by_parentid()
    {
        return json(getRegionByParentId());
    }

    /**
     * 地图点选坐标
     * @return mixed
     */
    public function get_coordinate()
    {
        return $this->fetch('public/coordinate', ['callback' => input('callback')]);
    }

    /**
     * 修改订单页面,搜索会员
     * @return \think\response\Json
     */
    public function getUserList()
    {
        $keywords = input('keywords');
        if (!$keywords) {
            return json('请输入会员名称|手机号搜索', 403);
        }
        return json(searchUser($keywords, 20));
    }

    public function queryShipping()
    {
        $order_id = input('id/d');
        $order    = Db::name('order')->where('order_id', $order_id)->find();
        if ($order['shipping_status'] == 2) {
            $delivery_order = Db::name('delivery_order')->where('order_id', $order_id)->find();

            if ($delivery_order) {
                $no          = $delivery_order['shipping_no'];
                $shipcompany = Db::name('shipping')->where('shipping_id', $delivery_order['shipping_id'])->find();

                if ($shipcompany['shipping_code'] == 'shunfeng') {
                    $no = $no . ':' . substr($delivery_order['mobile'], -4);
                }
                $Queryshipping = expressQuery($no, $shipcompany['shipping_code']);
                $shipping      = is_array($Queryshipping) ? $Queryshipping[0] : $Queryshipping;
                exit($shipping);
            } else {
                return json('未找到相关物流信息或单号不存在', 403);
            }
        } else {
            return json('未找到相关物流信息或单号不存在', 403);
        }
    }
}