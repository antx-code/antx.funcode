<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 10:59
 */

namespace app\seller\controller;

use think\Db;

class Sale extends Base
{
    public function coupon()
    {
        if ($this->request->isPost()) {
            $id    = input('post.id/d');
            $map   = [
                ['coupon_id', '=', $id],
                ['used_time', '=', 0],
                ['is_delete', '=', 0]
            ];
            $count = Db::name('coupon_list')->where($map)->count();
            if ($count > 0) return json('操作失败，还有[' . $count . ']张已领取且未使用的优惠券', 403);
            Db::name('coupon')->where('coupon_id', $id)->update(['is_delete' => time()]);
            return json();
        }
        if ($this->request->isAjax()) {
            $map = [
                ['store_id', '=', $this->store_id],
                ['is_delete', '=', 0]
            ];
            if (input('keywords')) $map[] = ['name', 'LIKE', input('keywords')];
            $list = Db::name('coupon')->where($map)->order('coupon_id desc')->select();
            return json($list);
        }
        return $this->fetch();
    }

    public function add_coupon()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'name'             => 'require',
                'money'            => 'require|gt:1',
                'min_goods_amount' => 'require|gt:1',
                'send_end_time'    => 'require|after:' . date('Y-m-d'),
                'use_end_time'     => 'require|after:' . date('Y-m-d'),
            ], [
                'name'                  => '优惠券名称必填',
                'money'                 => '面额数字必须大于1',
                'min_goods_amount'      => '最小订单金额必须大于1',
                'send_end_time.require' => '发放结束时间必须选择',
                'send_end_time.after'   => '发放结束时间不正确',
                'use_end_time.require'  => '发放结束时间必须选择',
                'use_end_time.after'    => '优惠券使用有效时间不正确',
            ]);
            if (true !== $result) {
                $this->error($result);
                exit;
            }

            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time']   = strtotime($data['send_end_time']);
            $data['use_start_time']  = strtotime($data['use_start_time']);
            $data['use_end_time']    = strtotime($data['use_end_time']);
            if (isset($data['coupon_id'])) {
                unset($data['store_id']);
                $flag = Db::name('coupon')->update($data);
            } else {
                $data['store_id'] = $this->store_id;
                $flag             = Db::name('coupon')->insert($data);
            }
            if ($flag) $this->success('保存完成', 'sale/coupon');
            $this->error('保存失败');
        }

        $coupon = Db::name('coupon')->where(['store_id' => $this->store_id, 'coupon_id' => input('id')])->find();
        return $this->fetch('', ['coupon' => $coupon]);
    }

    public function coupon_detail()
    {
        if ($this->request->isPost()) {
            $list_id = input('post.list_id');
            $flag    = Db::name('coupon_list')->where('list_id', $list_id)->update(['is_delete' => time()]);
            if (!$flag) return json('操作失败');
            return json();
        }

        $id   = input('id');
        $list = Db::name('coupon a')
            ->join('coupon_list b', 'a.coupon_id=b.coupon_id')
            ->join('user c', 'b.user_id=c.user_id', 'LEFT')
            ->where(['a.coupon_id' => $id, 'a.is_delete' => 0, 'b.is_delete' => 0])
            ->field('a.name,b.*,c.user_name')
            ->select();

        return $this->fetch('', ['list' => $list]);
    }
}
