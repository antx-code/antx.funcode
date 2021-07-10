<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/19 13:29
 */

namespace app\common\model;

use think\Model;

class ReturnLog extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';

    //protected $append = ['show_status', 'show_time', 'addtime'];

    public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getContentAttr($value)
    {
        return json_decode($value, true);
    }
}