<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/1/10 14:14
 */

namespace app\home\model;

use think\Model;
use think\Db;

class OrderComment extends Model
{
    protected $pk = 'order_commemt_id';

    public function addComment($comment)
    {
        if ($this->where(['user_id' => $comment['user_id'], 'order_id' => $comment['order_id']])->count() > 0) {
            return ['msg' => '重复评论', 'status' => 0];
        } else {
            $this->save($comment);
            Db::name('order')->where(['order_id' => $comment['order_id']])->update(['order_status' => 4, 'is_comment' => 1]);
            $this->storeScoreRefresh($comment['store_id']);
            return ['msg' => '评价成功', 'status' => 1];
        }
    }

    public function storeScoreRefresh($store_id)
    {
        if ($store_id <= 0) {
            return;
        }
        //按180天内的订单评价
        $data = $this->where('store_id', $store_id)->where('create_time', '>', time() - 15552000)
            ->field('SUM(desc_rank) as desc_rank,SUM(service_rank) as service_rank,SUM(delivery_rank) as delivery_rank,count(order_commemt_id) as count')
            ->find();
        if ($data) {
            $update                        = [
                'store_desc_score'     => round($data['desc_rank'] / $data['count'], 1),
                'store_service_score'  => round($data['service_rank'] / $data['count'], 1),
                'store_delivery_score' => round($data['delivery_rank'] / $data['count'], 1),
            ];
            $update['store_average_score'] = round(array_sum($update) / 3, 1);
            Db::name('store')->where('store_id', $store_id)->update($update);
        }
    }
}
