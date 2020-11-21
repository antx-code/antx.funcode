<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/5/5 11:30
 */

namespace app\seller\model;

use think\Model;
use think\Db;
use app\common\model\Delivery as adminDelivery;

class Delivery extends adminDelivery
{
    protected function base($query)
    {
        $query->where(['store_id' => session('seller')['store_id']]);
    }
}
