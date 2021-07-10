<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/10 8:41
 */

namespace app\sr\controller;

use think\Controller;
use app\sr\model\Goods as GoodsModel;

class Goods extends Controller
{
    public function index()
    {
        if ($this->request->isPost()) {
            $page     = input('page/d');
            $pageSize = input('pageSize/d');
            $field    = input('field', 'goods_id');
            $sort     = input('sort', 'desc');
            $keywords = input('keywords');
            $user_cat = input('user_cat/d');

            $map = [
                ['sr_id', '=', session('sr.id')]
            ];
            if ($keywords) $map[] = ['goods_id|goods_name', 'like', "%{$keywords}%"];
            if ($user_cat > 0) {
                $map[] = ['user_cat', '=', $user_cat];
            }
            $count = GoodsModel::where($map)->count();
            $list  = GoodsModel::where($map)->field('goods_desc', true)->page($page, $pageSize)->order($field, $sort)->select();
            $brand = [];
            //if ($list) $brand = get_brand_by_id($list->column('brand_id'));
            return json(['count' => $count, 'list' => $list, 'brand' => $brand]);
        }
        return view();
    }

}