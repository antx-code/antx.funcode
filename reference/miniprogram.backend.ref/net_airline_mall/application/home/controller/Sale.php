<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/5 12:04
 */

namespace app\home\controller;

use think\Db;

class Sale extends Common
{
    public function allCoupon()
    {
        dump(__FUNCTION__);
    }

    /**
     * 领取优惠券
     * @return \think\response\Json
     */
    public function collectCoupon()
    {
        if (!session('user.user_id')) return json(['status' => false, 'msg' => '请重新登录']);
        $cid   = input('cid/d');
        $count = Db::name('coupon_list')->where(['user_id' => session('user.user_id'), 'coupon_id' => $cid])->count();
        if ($count) return json(['status' => false, 'msg' => '您已领取过该优惠券了哦~']);
        $time   = time();
        $coupon = Db::name('coupon')->where('coupon_id', $cid)->where('is_delete=0 AND send_start_time<' . $time . ' AND send_end_time>' . $time)->find();
        if (!$coupon || $coupon['send_type'] != 2) {
            return json(['status' => false, 'msg' => '优惠券不存在']);
        }
        if ($coupon['send_num'] >= $coupon['num']) {
            return json(['status' => false, 'msg' => '您来晚了哦,优惠券已全部发放完毕']);
        }

        $row = [
            'coupon_id' => $coupon['coupon_id'],
            'user_id'   => session('user.user_id'),
            'bind_time' => $time
        ];
        Db::name('coupon_list')->insert($row);
        Db::name('coupon')->where('coupon_id', $cid)->setInc('send_num');
        return json(['status' => true, 'msg' => '领取成功！感谢您的参与，祝您购物愉快~']);
    }
}