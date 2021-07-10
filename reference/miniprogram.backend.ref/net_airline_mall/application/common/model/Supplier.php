<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/9 16:32
 */

namespace app\common\model;

use think\Model;

class Supplier extends Model
{

    public function getLastLoginTimeAttr($value)
    {
        return date('Y-m-d H:i:s');
    }
}