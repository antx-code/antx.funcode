<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/8 10:32
 */

namespace app\seller\controller;

class Service extends Base
{
    public function index()
    {
        return $this->fetch();
    }
}