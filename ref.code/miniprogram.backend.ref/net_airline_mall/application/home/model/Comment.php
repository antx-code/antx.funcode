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

class Comment extends Model
{
    protected $pk = 'comment_id';
    protected $autoWriteTimestamp = true;

    public function getImgsAttr($value)
    {
        if ($value) {
            return explode(',', $value);
        }
        return [];
    }

    public function getTypeTextAttr($value, $data)
    {
        $text = ['用户评论', '商家回复', '管理员回复'];
        return $text[$data['type']];
    }

    public function addComment($data)
    {
        //暂时只能评论一次
        if ($this->where(['user_id' => $data['user_id'], 'rec_id' => $data['rec_id']])->count() > 0) {
            return ['msg' => '重复评论', 'status' => 0];
        }
        if ($data['is_anonymous']) {
            $len = mb_strlen($data['user_name']);
            if ($len > 2) {
                $data['user_name'] = mb_substr($data['user_name'], 0, 1) . str_pad('', $len - 2, '*') . mb_substr($data['user_name'], -1);
            } else {
                $data['user_name'] = mb_substr($data['user_name'], 0, 1) . str_pad('*', $len - 2);
            }
        }
        Db::name('order_goods')->where('rec_id', $data['rec_id'])->update(['is_comment' => 1]);
        $this->data($data, true)->isUpdate(false)->allowField(true)->save();
        $this->refreshGoodsSortOrder($data['goods_id']);
        return ['msg' => '评价成功', 'status' => 1];
    }

    public function addReply($data, $type = 1)
    {
        $comment = $this->field('order_id,store_id,goods_id,rec_id')->get($data['id'])->getData();
        if (!$comment) {
            return false;
        }
        $comment['user_id']   = $data['user_id'];
        $comment['user_name'] = $data['user_name'];
        $comment['content']   = $data['content'];
        $comment['type']      = $type;
        $comment['parent_id'] = $data['id'];
        return $this->isUpdate(false)->save($comment);
    }

    /**
     * 已评价
     * @param $user_id
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getListByUser($user_id, $page, $pageSize)
    {
        $count = $this->countCommented($user_id);
        if (!$count) {
            return ['wait_comment' => 0, 'list' => [], 'commented' => 0];
        }
        $list = [];
        if ($count) {
            $ids  = $this->where(['user_id' => $user_id, 'parent_id' => 0])->page($page, $pageSize)->order('create_time DESC')->column('rec_id');
            $list = Db::name('order_goods a')
                ->join('order b', 'a.order_id=b.order_id')
                ->whereIn('a.rec_id', $ids)
                ->where('b.user_id', $user_id)
                ->field('a.*,b.order_sn,b.add_time,b.total_amount')
                ->page($page, $pageSize)
                ->orderRaw('field(rec_id,' . implode(',', $ids) . ')')
                ->select();
        }
        return ['commented' => $count, 'list' => $list, 'wait_comment' => $this->countWaitComment($user_id)['count']];
    }

    /**
     * 待评价
     * @param $user_id
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function waitCommentByUser($user_id, $page, $pageSize)
    {
        $WaitComment = $this->countWaitComment($user_id);
        if ($WaitComment['count'] == 0) {
            return ['wait_comment' => 0, 'list' => [], 'commented' => 0];
        }
        $map = [['a.order_id', 'IN', $WaitComment['order_ids']], ['a.is_comment', '=', 0]];
        //正常订单必须有商品

        $list = Db::name('order_goods a')
            ->join('order b', 'a.order_id=b.order_id')
            ->where($map)
            ->field('a.*,b.order_sn,b.add_time,b.total_amount')
            ->page($page, $pageSize)
            ->order('rec_id DESC')
            ->select();
        return ['wait_comment' => $WaitComment['count'], 'list' => $list, 'commented' => $this->countCommented($user_id)];
    }

    //TODO 按评分计算商品排序权重
    public function refreshGoodsSortOrder($goods_id)
    {

    }

    public function countWaitComment($user_id)
    {
        $order_ids = Db::name('order')->where('user_id', $user_id)->where('order_status=2 OR order_status=4')->column('order_id');
        if (!$order_ids) {
            return ['count' => 0, 'order_ids' => []];
        }
        $map   = [['order_id', 'IN', $order_ids], ['is_comment', '=', 0]];
        $count = Db::name('order_goods')->where($map)->count();
        return ['count' => $count, 'order_ids' => $order_ids];
    }

    public function countCommented($user_id)
    {
        return $this->where(['user_id' => $user_id, 'parent_id' => 0])->count();
    }
}
