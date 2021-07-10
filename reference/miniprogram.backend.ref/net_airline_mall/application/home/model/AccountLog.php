<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/2/14 18:13
 */

namespace app\home\model;

use think\Model;
use think\Db;

class AccountLog extends Model
{
    protected $pk = 'order_id';

    public function accountLog($user_id, $money, $desc = '', $frozen_money = 0, $points = 0, $order_id = 0, $order_sn = '')
    {
        if (is_numeric($money) !== true) {
            return '金额必须为有效数字，可以带两位小数';
        }
        $money = floatval($money);
        $user  = Db::name('user')->where('user_id', $user_id)->find();
        if (!$user) {
            return '用户不存在';
        }
        if ($user['user_money'] + $money < 0) {
            return '余额不足';
        }

        $account_log = [
            'user_id'      => $user_id,
            'user_money'   => $money,
            'frozen_money' => $frozen_money,
            'pay_points'   => $points,
            'change_time'  => time(),
            'desc'         => $desc,
            'order_id'     => $order_id,
            'order_sn'     => $order_sn,
        ];
        $update_data = [
            'user_money'   => Db::raw('user_money+' . $money),
            'points'       => Db::raw('points+' . $points),
            'frozen_money' => Db::raw('frozen_money+' . $frozen_money)
        ];
        $update      = Db::name('user')->where('user_id', $user_id)->update($update_data);
        if ($update) {
            //FIXME 更新 session('user.points', session('user.points') + $points);
            $this->insert($account_log);
            return true;
        } else {
            return false;
        }
    }
}