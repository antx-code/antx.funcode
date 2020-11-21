<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/3 11:31
 */

namespace app\home\model;

use think\Model;
use think\Db;

class Goods extends Model
{
    protected $pk = 'goods_id';

    //protected $jsonAssoc = true;

    protected static function init()
    {
        //self::$map[] = ;todo 地域、仓库条件
    }

    protected function base($query)
    {
        $query->where(['is_delete' => 0, 'is_on_sale' => 1, 'review_status' => 2]);
    }

    /**
     * goods_tag 修改器
     * @param $value
     * @return array
     */
    public function getGoodsTagAttr($value)
    {
        return strpos($value, ',') === false ? [] : explode(',', $value);
    }

    /**
     * 商品详情面包屑
     * @param $cid 分类id
     * @return array
     */
    public function getBreadCrumb($cid)
    {
        //todo 缓存
        $breadCrumb = [];
        $field      = 'cat_id,cat_name,parent_id';
        $cat        = Db::name('category')->where('cat_id', $cid)->field($field)->find();
        if (!$cat) return [];
        $cat['children'] = Db::name('category')->where('parent_id', $cid)->field($field)->select();
        array_push($breadCrumb, $cat);
        if ($cat['parent_id'] > 0) {
            $cat2             = Db::name('category')->where('cat_id', $cat['parent_id'])->field($field)->find();
            $cat2['children'] = Db::name('category')->where('parent_id', $cat2['cat_id'])->field($field)->select();
            array_push($breadCrumb, $cat2);
            if ($cat2['parent_id'] > 0) {
                $cat3             = Db::name('category')->where('cat_id', $cat2['parent_id'])->field($field)->find();
                $cat3['children'] = Db::name('category')->where('parent_id', $cat3['cat_id'])->field($field)->select();
                array_push($breadCrumb, $cat3);
            }
        }

        return array_reverse($breadCrumb);
    }

    /**
     * 获取商品关联品牌
     * @param $cid
     */
    public function getRelatedBrand($cid)
    {
        $parent = Db::name('goods')->where('parent_id', $cid)->order('')->column('cat_name', 'cat_id');
    }

    /**
     * 获取商品规格
     * @param $goods_id
     * @return array
     */
    public function getProduct($goods_id)
    {
        $spec = Db::name('products')->where('goods_id', $goods_id)->column('product_id,tempvalue,product_sn,product_number,product_price,product_market_price', 'attr_keys');
        if (!$spec) return [];
        $goods_attr_ids = array_unique(explode('_', implode('_', array_keys($spec))));

        $filterSpec = [];
        $attr       = Db::name('goods_attr a')
            ->join('attribute b', 'a.attr_id=b.attr_id')
            //->where(['a.goods_id' => $goods_id])
            ->whereIn('a.goods_attr_id', $goods_attr_ids)
            ->field('a.goods_attr_id,a.attr_value,a.attr_id,b.attr_name')
            ->select();

        foreach ($attr as $index => $item) {
            $filterSpec[$item['attr_name']][] = [
                'attr_id'    => $item['goods_attr_id'],
                'attr_value' => $item['attr_value'],
                'img'        => '',//TODO 规格对应的图片
                'active'     => 0,
            ];
        }
        return ['spec' => $spec, 'filterSpec' => $filterSpec];
    }

    /**
     * 获取商品轮播图
     * @param $goods_id
     * @return array
     */
    public function getGallery($goods_id)
    {
        $imgs = Db::name('goods_gallery')->where('goods_id', $goods_id)->order('img_sort asc')->column('img_original');
        if (count($imgs) == 0) {
            array_push($imgs, 'http://image.hngpmall.com/upload/image/20191029/c241c4186f4ed6b7fbfebaa859898b93.png');
        }
        return $imgs;
    }

    /**
     * 获取商品品牌信息
     * @param $brand_id
     * @return array|\PDOStatement|string|Model|null
     */
    public function getBrand($brand_id)
    {
        return Db::name('brand')->where('brand_id', $brand_id)->where('is_delete', 0)->find();
    }

    /**
     * 获取商品多规格价格/库存
     * @param $goods
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getSpec($goods)
    {
        $list = [];
        if ($goods['product_id'] > 0) {
            $list = Db::name('products')
                ->where('goods_id', $goods['goods_id'])
                ->field('product_id,tempvalue,attr_keys,product_sn,bar_code,product_number,product_price')
                ->select();
        }
        return $list;
    }

    /**
     * 获取商品属性
     * @param array $goods
     * @return array
     */
    public function getAttriBute($goods)
    {
        $result = ['other' => [], 'group' => []];
        $list   = Db::name('goods_map a')
            ->join('product_attr b', 'a.product_arr_id=b.id')
            ->where('a.goods_id', $goods['goods_id'])
            ->field('a.element,b.id,b.group_id,b.name')
            ->order('b.group_id ASC,b.sort DESC')
            ->select();
        if ($list) {
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
                        'data'       => array_slice($list, array_search($gid, $ids), $val_count[$gid]),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 用户收藏商品数量
     * @param $goods_id
     * @param int $user_id
     * @return float|int|string
     */
    public function countFavorite($goods_id, $user_id = 0)
    {
        return Db::name('goods_collect')->where('goods_id', $goods_id)->where('user_id', $user_id)->count();
    }

    /**
     * 详情页获取商品评论
     * @param $goods_id
     * @return array
     */
    public function getGoodsComment($goods_id)
    {
        $result  = [
            'score'       => 5,
            'stars'       => 5,
            'allReview'   => 100,
            'badReview'   => 0,
            'middlReview' => 0,
            'goodReview'  => 100,
            'allmen'      => 0,
            'badmen'      => 0,
            'middlemen'   => 0,
            'goodmen'     => 0,
            'list'        => [],
        ];
        $comment = Db::name('comment')
            ->where(['goods_id' => $goods_id, 'enabled' => 0, 'parent_id' => 0])
            ->field('comment_id,comment_rank,store_id,user_id,goods_id,create_time,imgs,content')
            ->select();
        if (!$comment) return $result;
        $result['allmen']      = count($comment);
        $rank                  = array_count_values(array_column($comment, 'comment_rank'));
        $result['badmen']      = isset($rank[1]) ? $rank[1] : 0;
        $result['middlemen']   = (isset($rank[2]) ? $rank[2] : 0) + (isset($rank[3]) ? $rank[3] : 0);
        $result['goodmen']     = (isset($rank[4]) ? $rank[4] : 0) + (isset($rank[5]) ? $rank[5] : 0);
        $result['badReview']   = round($result['badmen'] / $result['allmen'] * 100, 1);
        $result['middlReview'] = round($result['middlemen'] / $result['allmen'] * 100, 1);
        $result['goodReview']  = round($result['goodmen'] / $result['allmen'] * 100, 1);

        $result['list'] = $this->getCommentList($goods_id, 1, 5);
        return $result;
    }

    public function getCommentList($goods_id, $page = 1, $pageSize = 5, $type = 'all'): array
    {
        $map = [1, 2, 3, 4, 5];
        if ($type == 'good') {
            $map = [4, 5];
        } elseif ($type == 'middle') {
            $map = [2, 3];
        } elseif ($type == 'bad') {
            $map = [1];
        }

        $count   = Db::name('comment')->where(['goods_id' => $goods_id, 'enabled' => 0, 'parent_id' => 0])->whereIn('comment_rank', $map)->count();
        $comment = Db::name('comment a')
            ->join('user b', 'a.user_id=b.user_id', 'LEFT')
            ->where(['a.goods_id' => $goods_id, 'a.enabled' => 0, 'a.parent_id' => 0])
            ->whereIn('a.comment_rank', $map)
            ->fieldRaw('a.*,FROM_UNIXTIME(a.create_time) AS create_time_text,IF(ISNULL(b.avatar),"http://image.hngpmall.com/upload/image/20200119/21721117ec28bbaabd87dd39251d9828.png",b.avatar) as avatar')
            ->order('a.comment_id DESC')
            ->page($page, $pageSize)
            ->select();
        if (count($comment) < 1) return [];
        $ids   = array_column($comment, 'comment_id');
        $reply = Db::name('comment')->where('enabled', 0)->whereIn('parent_id', $ids)->order('parent_id DESC,create_time ASC')->select();
        if ($reply) {
            $re_ids   = array_column($reply, 'parent_id');
            $valCount = array_count_values($re_ids);
        }
        foreach ($comment as $index => &$val) {
            if ($val['imgs']) {
                $val['imgs'] = explode(',', $val['imgs']);
            } else {
                $val['imgs'] = [];
            }
            //if (strlen($val['user_name'])) {
            //    $val['user_name'] = substr_replace($val['user_name'], str_repeat('*', strlen($val['user_name']) - 2), 1, strlen($val['user_name']) - 2);
            //}
            $val['reply'] = isset($valCount[$val['comment_id']]) ? array_slice($reply, array_search($val['comment_id'], $re_ids), $valCount[$val['comment_id']]) : [];
        }
        return ['data' => $comment, 'count' => $count, 'current' => $page, 'pageSize' => $pageSize];
    }
}
