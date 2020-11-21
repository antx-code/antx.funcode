<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/10 10:15
 */

namespace app\common\exception;

use think\exception\Handle;
use think\exception\HttpException;
use Exception;

class Http extends Handle
{
    public function render(Exception $e)
    {
        // 请求异常
        if ($e instanceof HttpException) {
            if (request()->isAjax()) {
                return response($e->getMessage(), $e->getStatusCode());
            }
            return redirect('index/_empty', ['msg' => $e->getMessage()]);
        }

        // 其他错误交给系统处理
        return parent::render($e);
    }

}