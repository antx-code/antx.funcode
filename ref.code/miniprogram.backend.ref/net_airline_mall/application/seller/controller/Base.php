<?php

/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/4 16:12
 */

namespace app\seller\controller;

use think\Controller;
use think\Db;

class Base extends Controller
{
    protected $tag;
    protected $seller;
    protected $store_id;

    public function initialize()
    {
        $this->seller   = session('seller');
        $this->store_id = $this->seller['store_id'];
        $this->tag      = 'seller_' . $this->seller['username'];
        //TODO 访问权限验证
        $this->checkAuth();
        $logo = cache('seller_logo');
        if (!$this->request->isAjax()) {
            if (!$logo) {
                $logo = Db::name('shop_config')->where('code', 'seller_logo')->cache(true)->value('value');
                $logo = $logo ? $logo : '/public/static/images/seller/logo.png';
                cache('seller_logo', $logo);
            }
            $this->assign('logo', $logo);
        }
    }

    /**
     * 权限控制
     */
    private function checkAuth()
    {
        $ingnore    = ['index', 'api'];//公共接口忽略权限验证
        $controller = strtolower($this->request->controller());
        $action     = strtolower($this->request->action());
        //非管理员,检查controller/action访问权限
        if ($this->seller['controller'] != 'all' && !in_array($controller, $ingnore)) {
            if (in_array($controller, $this->seller['controller'])) {
                if (in_array($action, $this->seller['action'][$controller])) {
                    if (!isset($this->seller['menu'])) {
                        //隐藏用户权限菜单
                        $this->hide_menu();
                    }
                } else {
                    if ($this->request->isAjax()) {
                        json('权限不足', 401)->send();
                        exit;
                    }
                    $this->error('权限不足');
                }
            } else {
                if ($this->request->isAjax()) {
                    json('权限不足', 401)->send();
                    exit;
                }
                $this->error('权限不足');
            }
        }

        if ($controller == 'index') {
            if (!isset($this->seller['menu'])) {
                //隐藏用户权限菜单
                $this->hide_menu();
            }
            $this->assign('shortcut', $this->seller['seller_quicklink']);//快捷菜单
        }
        $this->assign('menu', $this->seller['menu']);
        $this->assign('ctrl', $controller);
        $this->assign('actname', $action);
    }

    /**
     * 隐藏导航菜单
     */
    private function hide_menu()
    {
        $sellerMenu = config('sellerMenu');
        if ($this->seller['controller'] != 'all') {
            $disCotroller = array_diff(array_keys($sellerMenu), $this->seller['controller']);
            foreach ($disCotroller as $item) {
                unset($sellerMenu[$item]);
            }

            //隐藏左侧菜单
            foreach ($sellerMenu as $ctrl => $menu) {
                $disAction = array_diff(array_keys($menu['left']), $this->seller['action'][$ctrl]);
                foreach ($disAction as $item) {
                    unset($sellerMenu[$ctrl]['left'][$item]);
                }
            }
        }

        $this->seller['menu'] = $sellerMenu;
        session('seller', $this->seller);
    }

    public function _empty()
    {
        if ($this->request->isAjax()) {
            return json('您请求的资源不存在~', 403);
        } else {
            $this->error('此页面待开发~~~');
        }
    }
}