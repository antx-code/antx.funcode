<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/2/24 10:34
 */

namespace app\admin\model;

use think\Model;

class TransportFee extends Model
{
    protected $pk = 'transport_id';

    public function getList($map, $page, $pageSize, $order = 'transport_id desc')
    {
        $count = $this->where($map)->count();
        $list  = $this->where($map)->page($page, $pageSize)->order($order)->select();
        return ['list' => $list, 'total' => $count];
    }
}