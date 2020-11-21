<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/7/3 10:21
 */

namespace app\home\controller;

use Exception as Except;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ErrorException;
use think\facade\Response;

class Exception extends Handle
{

    public function render(Except $e)
    {
        if (true !== config('app_debug')) {
            // ajax异常
            if ($e instanceof HttpException && request()->isAjax()) {
                return response($e->getMessage(), $e->getStatusCode());
            }
            //页面错误异常
            if ($e instanceof ErrorException) {
                Response::create('/home/error/error', 'redirect', 200)->send();
            }
        } else {
            // 其他错误交给系统处理
            return parent::render($e);
        }
    }
}