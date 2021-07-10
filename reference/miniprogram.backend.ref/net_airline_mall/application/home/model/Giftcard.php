<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/12/2 下午5:02
 */

namespace app\home\model;

use think\Model;
use think\Db;

class Giftcard extends Model
{
    public function get_list($user_id, $page, $pageSize)
    {
        $map  = [
            ['user_id', '=', $user_id],
            ['cash', '>', 0],
            ['over_time', '>', time()]
        ];
        $list = $this->where($map)->order('id desc')->page($page, $pageSize)->select();
        return $list;
    }

    public function getById($user_id, $id, $over = true)
    {
        $map = [
            ['user_id', '=', $user_id],
            ['id', '=', $id],
            ['over_time', '>', time()]
        ];
        return $this->where($map)->find();
    }

    public function useCardCalc($amount, $card)
    {
        if ($amount > $card['cash']) return $card['cash'];
        return $amount;
    }

    public function bonding($card, $uid)
    {
        if (!$card) {
            $this->error = '礼品卡不存在';
            return false;
        }
        if ($card['user_id'] > 0) {
            $this->error = '错误：礼品卡已被绑定';
            return false;
        }
        $time = time();
        if ($card['start_time'] > $time || $card['over_time'] < $time) {
            $this->error = '不在有效期限内';
            return false;
        }
        Db::name('giftcard')->where('id', $card['id'])->update(['user_id' => $uid, 'bind_time' => $time]);
        return ['uid' => $uid];
    }

    public function refund($ids, $amount, $order_id, $user_id)
    {
        $time            = time();
        $cardList        = $this->whereIn('id', $ids)->select();
        $card_update     = [];
        $card_log        = [];
        $card_price_temp = $amount;
        foreach ($cardList as $idx => $card) {
            if ($card_price_temp <= 0) {
                break;
            }
            $card_log[$idx] = [
                'gid'      => $card->id,
                'order_id' => $order_id,
                'user_id'  => $user_id,
                'use_time' => $time,
                'status'   => 2,
            ];
            $used           = $card->price - $card->cash;

            if ($card_price_temp > $used) {
                $card_price_temp        -= $used;
                $card_update[]          = [
                    'id'   => $card->id,
                    'cash' => Db::raw('cash=price')
                ];
                $card_log[$idx]['cash'] = $used;
            } else {
                $card_update[]          = [
                    'id'   => $card->id,
                    'cash' => Db::raw('cash+' . $card_price_temp)
                ];
                $card_log[$idx]['cash'] = $card_price_temp;
                break;
            }
        }
        foreach ($card_update as $ud) {
            $this->where('id', $ud['id'])->update(['cash' => $ud['cash']]);
        }
        Db::name("giftcard_log")->insertAll($card_log);
        return true;
    }
}
