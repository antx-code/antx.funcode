<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/9 15:44
 */

namespace app\common\middleware;

use app\common\model\Supplier;
use think\Db;
use think\facade\View;

class SupplierAuth
{
    public function handle($request, \Closure $next)
    {
        if (!in_array($request->action(), ['signin', 'signout']) && session('?sr') !== true) {
            if (cookie('?sr_user')) {
                $supplier = (new Supplier)->where(['username' => cookie('sr_user'), 'enabled' => 0])->find();
                if ($supplier && hash('sha256', $supplier->password) == cookie('sr_sign')) {
                    session('sr', $supplier);
                    redirect('index/index');
                }
            }

            if ($request->isAjax()) {
                json('请重新登录', 401)->send();
                exit();
            }
            return redirect('index/signin');
        }

        if (!$request->isAjax()) {
            View::assign('supplier', session('sr'));
            $logo = cache('seller_logo');
            if (!$logo) {
                $logo = Db::name('shop_config')->where('code', 'seller_logo')->cache(true)->value('value');
                $logo = $logo ? $logo : '/public/static/images/seller/logo.png';
                cache('seller_logo', $logo);
            }
            View::assign('logo', $logo);
            //$menu = config('srMenu')[$request->controller()];
            //dump($menu['left'][$request->action()]['title']);die;
            View::assign('menu', config('srMenu')[$request->controller()] ?? []);
        }
        return $next($request);
    }
}