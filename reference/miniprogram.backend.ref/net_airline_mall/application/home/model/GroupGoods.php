<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/2/15 10:20
 */

namespace app\home\model;

use think\Db;
use think\Model;

class GroupGoods extends Model
{
    protected $pk = 'gid';

    protected function base($query)
    {
        $query->where(['a.review_status' => 2]);
    }

    public function getEndTimeCnAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['end_time']);
    }

    public function getExtInfoAttr($value)
    {
        return json_decode($value, true);
    }

    public function getList($page, $pageSize, $keywords = '', $cat_id = 0, $sort = 'gid', $order = 'DESC')
    {
        $map = [['b.review_status', '=', 2]];
        if ($keywords) {
            $map[] = ['b.goods_name', 'like', '%' . $keywords . '%'];
        }
        if ($cat_id) {
            $map[] = ['b.cat_id', '=', $cat_id];
        }

        return $this->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,b.cat_id,b.store_id,b.goods_sn,b.total_sale_num,b.virtual_sale_num,b.brand_id,b.keywords,b.original_img")
            ->where($map)
            ->order($sort, $order)
            ->page($page, $pageSize)
            ->select();
    }

    public function calcPrice($id, $num)
    {
        $group = $this->alias('a')->get($id);
        if (!$group) {
            return '团购活动已失效';
        }
        if ($group->end_time < time()) {
            return '团购活动已过期';
        }
        $ext   = $group->ext_info;
        $price = $group->shop_price;
        array_multisort(array_column($ext, 'num'), SORT_ASC, $ext, SORT_NUMERIC);
        foreach ($ext as $k => $v) {
            if ($num >= $v['num']) {
                $price = $v['price'];
            }
        }

        return $price;
    }
}