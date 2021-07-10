<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/5/14 11:02
 */

namespace app\home\controller;

use think\Request;

class Error extends Common
{
    public function index(Request $request)
    {
        return view('public/error');
    }
}
