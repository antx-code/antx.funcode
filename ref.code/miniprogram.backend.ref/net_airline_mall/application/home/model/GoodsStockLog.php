<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/01/09 11:31
 */

namespace app\home\model;

use think\Model;
use think\Db;

class GoodsStockLog extends Model
{
    protected function base($query)
    {
        $query->where(['is_delete' => 0]);
    }

    /**
     * goods_tag 修改器
     * @param $value
     * @return array
     */
    public function getCtimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getList($page, $limit, $start_time, $end_time, $keywords, $direct = 'put')
    {
        $map = [
            ['store_id', '=', 0],
            ['stock', '>', 0]
        ];
        if ($direct != 'put') {
            array_pop($map);
            array_push($map, ['stock', '<', 0]);
        }
        if ($keywords) {
            array_push($map, ['goods_name', 'LIKE', '%' . $keywords . '%']);
        }
        if ($start_time) {
            $start_time = strtotime($start_time);
            if (!$end_time) {
                array_push($map, ['ctime', '>', $start_time]);
            } else {
                array_push($map, ['ctime', 'BETWEEN', [$start_time, strtotime($end_time)]]);
            }
        } else if ($end_time) {
            array_push($map, ['ctime', '<', strtotime($end_time)]);
        }

        $totla = $this->where($map)->count();
        $list  = $this->where($map)->order('id DESC')->page($page, $limit)->select()->toArray();
        $imgs  = [];
        if ($list) {
            $imgs = Db::name('goods')->whereIn('goods_id', array_column($list, 'goods_id'))->column('original_img', 'goods_id');
        }
        return ['total' => $totla, 'list' => $list, 'imgs' => $imgs];
    }
}
