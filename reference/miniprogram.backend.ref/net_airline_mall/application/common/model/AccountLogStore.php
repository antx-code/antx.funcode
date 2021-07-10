<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/12 11:34
 */

namespace app\common\model;

use think\Db;
use think\Model;
use app\home\model\Store;

class AccountLogStore extends Model
{
    protected $pk = 'log_id';

    public function getTypeAttr($value)
    {
        $text = ['平台结算', '提现', '充值'];
        return $text[$value];
    }

    public function getChangeTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * @param $amount int|float 金额
     * @param $account_type int 0店铺，1供应商
     * @param $store_id
     * @param string $desc
     * @return bool|mixed|string
     */
    public function settlement($amount, $account_type, $store_id, $order_sn, $desc = '')
    {
        if (is_numeric($amount) !== true) {
            return '金额必须为有效数字，可以带两位小数';
        }
        $amount = floatval($amount);

        if ($account_type) {
            $money = Supplier::where('id', $store_id)->value('seller_money');
        } else {
            $money = Store::where('store_id', $store_id)->value('seller_money');
        }


        $time        = time();
        $account_log = [
            'account_type'  => $account_type,
            'store_id'      => $store_id,
            'before_amount' => $money,
            'amount'        => $amount,
            'after_amount'  => $money + $amount,
            'desc'          => $desc,
            'type'          => 0,// 0：结算，1：提现，2：充值
            'is_paid'       => 1,//直接到账
            'trade_no'      => $order_sn,
            'add_time'      => $time
        ];
        $update_data = [
            'seller_money' => Db::raw('seller_money+' . $amount)
        ];
        if ($account_type) {
            $update = Supplier::where('id', $store_id)->update($update_data);
        } else {
            $update = Store::where('store_id', $store_id)->update($update_data);
        }
        if ($update) {
            $this->insert($account_log);
            return true;
        } else {
            return $this->getError();
        }
    }
}