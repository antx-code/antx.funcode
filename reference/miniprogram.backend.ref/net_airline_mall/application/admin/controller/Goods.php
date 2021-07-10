<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://uu235.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

namespace app\admin\controller;

use app\admin\model\Brand;
use think\facade\Config;
use think\Db;
use think\facade\Env;
use think\facade\Request;
use app\home\model\Goods as GoodsModel;
use app\home\model\GoodsStockLog;
use app\home\model\Comment;
use app\home\logic\Miniprogram;
use app\common\model\Supplier;

class Goods extends Common
{
    #商品管理
    public function goods()
    {
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order');
            $is_delete  = input('is_delete', 0);
            $brand_id   = input('brand_id', 0);
            $cat_id     = input('cat_id', 0);
            $keywords   = input('keywords');
            $store_id   = input('store_id', 0);
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            if ($store_id > 0) {
                $map[] = ['a.store_id', '>', 0];
            } else {
                $map[] = ['a.store_id', '=', $store_id];
            }
            $map[] = ['a.is_delete', 'eq', $is_delete];
            if ($brand_id > 0) {
                $map[] = ['a.brand_id', 'eq', $brand_id];
            }
            if ($cat_id > 0) {
                $map[] = ['a.cat_id', 'eq', $cat_id];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods")->alias("a")
                ->join("brand b", "a.brand_id=b.brand_id", "LEFT")
                ->join("store c", "a.store_id=c.store_id", "LEFT")
                ->field("a.*,b.brand_name,c.store_name")
                ->where($map)
                ->order("a.goods_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function goodsImport()
    {
        $gmap   = [];
        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        $list   = Db::name("goods")->alias("a")
            ->leftJoin("products b", "a.goods_id=b.goods_id")
            ->where($gmap)
            ->where("a.area != ''")
            ->field("a.goods_id,
            
            case 
            when a.area='lushan' then '鲁山'
            when a.area='lushi' then '卢氏'
            when a.area='nanzhao' then '南召'
            when a.area='pingyu' then '平舆'
            when a.area='queshan' then '确山'
            when a.area='ruyang' then '汝阳'
            when a.area='shangcai' then '上蔡'
            when a.area='sheqi' then '社旗'
            when a.area='songxian' then '嵩县'
            when a.area='taiqian' then '台前'
            when a.area='tongbai' then '桐柏'
            when a.area='xichuan' then '淅川'
            when a.area='fanxian' then '范县'
            when a.area='huaibin' then '淮滨' 
            end,
            
            
            
            
            a.goods_name,b.tempvalue,if(b.goods_id,b.product_price,a.shop_price)")
            ->order("a.goods_id desc")
            ->select();
        //序号、地区、产品名称、规格、价格
        $title = ['序号', '地区', '产品名称', '规格', '价格'];
        toExcel($title, $list, '农产品列表' . date('Y-m-d'));
    }

    public function getProductType()
    {
        $id   = input('id/d');
        $cat  = Db::name('product_cate')->where(['parent_id' => $id, 'store_id' => 0])->order('sort DESC')->select();
        $type = Db::name('product_type')->where('cate_id', $id)->select();

        return json(['cat' => $cat, 'type' => $type]);
    }

    public function getProductAttr()
    {
        $type_id = input('type_id');

        $result = [
            'other' => [],
            'group' => [],
        ];
        $list   = Db::name('product_attr')->where('type_id', $type_id)->order('group_id ASC,sort DESC')->select();

        if ($list) {
            array_walk($list, function (&$v) {
                if ($v['attrvaluejson']) {
                    $v['attrvaluejson'] = json_decode($v['attrvaluejson'], true);
                }
            });
            $ids       = array_column($list, 'group_id');
            $temp      = array_unique($ids);
            $group_ids = [];
            array_walk($temp, function ($id) use (&$group_ids) {
                if ($id > 0) $group_ids[] = $id;
            });

            $val_count = array_count_values($ids);
            if (isset($val_count[0])) {
                $result['other'] = array_slice($list, 0, $val_count[0]);
            }

            if ($group_ids) {
                $group = Db::name('product_group')->whereIn('group_id', $group_ids)->orderRaw('field(group_id,' . implode(',', $group_ids) . ')')->column('name', 'group_id');
                foreach ($group as $gid => $gname) {
                    $result['group'][] = [
                        'group_name' => $gname,
                        'data'       => array_slice($list, array_search($gid, $ids), $val_count[$gid]),
                    ];
                }
            }
        }
        return json($result);
    }


    #商品彻底删除
    public function goodsDelete()
    {
        $goods_id = input("goods_id");
        Db::name("goods")->where("goods_id", "=", $goods_id)->delete();
        Db::name("goods_attr")->where("goods_id", "=", $goods_id)->delete();
        Db::name("goods_click")->where("goods_id", "=", $goods_id)->delete();
        Db::name("goods_collect")->where("goods_id", "=", $goods_id)->delete();
        Db::name("goods_gallery")->where("goods_id", "=", $goods_id)->delete();
        ajaxmsg("数据清理完毕", 1);

    }

    public function goodsadd()
    {
        $goods_id   = input('goods_id/d', 0);
        $infoColumn = gettableColumn('goods');
        $this->assign('infoColumn', json_encode($infoColumn, 320));


        $attr_cat           = Db::name('product_cate')->where(['store_id' => 0, 'parent_id' => 0])->order('sort DESC')->select();
        $product_attr_value = Db::name('goods_map')->where('goods_id', $goods_id)->column('element', 'product_arr_id');
        $product_attr_key   = ['other' => [], 'group' => []];
        if ($product_attr_value) {
            $product_attr_key = Db::name('product_attr')->whereIn('id', array_keys($product_attr_value))->order('group_id ASC,sort DESC')->select();
            $product_attr_key = fixProductAttr($product_attr_key);
        }

        $this->assign('product_attr_key', json_encode($product_attr_key, 320));
        $this->assign('product_attr_value', $product_attr_value ? json_encode($product_attr_value, 320) : '{}');
        $this->assign('attr_cat', json_encode($attr_cat, 320));

        if (Request::isGet()) {
            $goodsattrList                  = [];
            $goodsattrList['goodstypeList'] = [];
            $goodsattrList['attributeList'] = [];
            $goodsattrList['productsList']  = [];
            $goodsattrList['cat_id']        = 0;
            $goodsattrList['cat_name']      = '请选择商品类型';
            $srList                         = Supplier::where('enabled', 0)->column('name', 'id');

            if ($goods_id > 0) {
                $info                           = Db::name("goods")->where("goods_id={$goods_id}")->find();
                $goodsgalleryList               = Db::name('goods_gallery')->where("goods_id={$goods_id}")->select();
                $goodsattrList['attributeList'] = attributelistGet($info['goods_id']);
                if (count($goodsattrList['attributeList'])) {
                    $attr = Db::name('goods_type')->where('cat_id', $goodsattrList['attributeList'][0]['cat_id'])->find();
                    if ($attr) {
                        $goodsattrList['cat_id']   = $attr['cat_id'];
                        $goodsattrList['cat_name'] = $attr['cat_name'];
                    }
                }
                $goodsattrList['productsList'] = Db::name("products")->where("goods_id={$goods_id}")->select();
                $categoryList                  = $this->getcategoryListDefault($goods_id);
                $categoryInfo                  = Db::name("category")->where("cat_id={$info['cat_id']}")->find();
                $this->assign('cat_name', $categoryInfo['cat_name']);
                $brand_name = Db::name("brand")->where('brand_id', '=', $info['brand_id'])->value('brand_name');

                if ($categoryInfo['parent_id'] > 0) {
                    $pid2info = Db::name("category")->where('cat_id', '=', $categoryInfo['parent_id'])->find();
                    $pid2     = $categoryInfo['parent_id'];
                    if ($pid2info['parent_id'] > 0) {
                        $pid1 = $pid2info['parent_id'];
                    } else {
                        $pid1 = 0;
                    }
                } else {
                    $pid1 = 0;
                    $pid2 = 0;
                }


            } else {
                $info             = gettableColumn('goods');
                $goodsgalleryList = [];
                $categoryList     = $this->getcategoryListDefault(0);
                $this->assign('cat_name', '');
                $brand_name = '';
                $pid1       = 0;
                $pid2       = 0;
            }
            $this->assign('brand_name', $brand_name);
            $this->assign('srList', json_encode($srList, 320));
            $this->assign('goods_desc', $info['goods_desc']);
            $this->assign('categoryList', json_encode($categoryList, 320));
            $this->assign('info', json_encode($info, 320));
            $this->assign('goodsgalleryList', json_encode($goodsgalleryList, 320));
            $this->assign('goodsattrList', json_encode($goodsattrList, 320));
            $this->assign('transportList', json_encode(transportListAjax(), 320));
            $this->assign('areaList', json_encode(config('areaList'), 320));
            $this->assign('pid1', $pid1);
            $this->assign('pid2', $pid2);
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['sr_id'] > 0 && $pageparm['cost_price'] < 0.01) {
                ajaxmsg('请填写成本价', 0);
            }
            $pageparm['last_update']   = time();
            $pageparm['product_id']    = '';//重置规格ID
            $pageparm['goods_sn']      = $pageparm['goods_sn'] ? $pageparm['goods_sn'] : create_goods_sn();
            $pageparm['review_status'] = 2;#自营商品默认审核通过
            if ($pageparm['freight'] == 1) {
                $pageparm['transport_id'] = 0;
            }
            $goodsattrList    = json_decode($_REQUEST['goodsattrList'], true);
            $goodsgalleryList = json_decode($_REQUEST['goodsgalleryList'], true);
            $stock            = 0;
            if ($pageparm['goods_id'] > 0) {
                $goods_id = $pageparm['goods_id'];
                unset($pageparm['goods_id']);

                $stock = Db::name('goods')->where('goods_id', $goods_id)->value('goods_number');//更新之前的库存
                Db::name('goods')->strict(false)->where("goods_id={$goods_id}")->update($pageparm);
                Db::name('goods_attr')->where("goods_id={$goods_id}")->delete();
                Db::name('goods_gallery')->where("goods_id={$goods_id}")->delete();

                $spec_stock_num = Db::name('products')->where("goods_id", $goods_id)->column('product_number', 'tempvalue');//更新之前多规格库存
                Db::name('products')->where("goods_id={$goods_id}")->delete();
                Db::name('goods_attr')->where("goods_id", $goods_id)->delete();
                Db::name('goods_map')->where('goods_id', $goods_id)->delete();
            } else {
                $pageparm['add_time'] = $pageparm['last_update'];
                $goods_id             = Db::name('goods')->strict(false)->insertGetId($pageparm);
            }
            #add to goods_gallery table
            foreach ($goodsgalleryList as $k => $v) {
                $goodsgalleryList[$k]['goods_id'] = $goods_id;
                if (isset($v['img_id'])) {
                    unset($goodsgalleryList[$k]['img_id']);
                }
            }
            foreach ($goodsgalleryList as $k => $v) {
                Db::name('goods_gallery')->insert($v);
            }
            #add to goods_attr table
            $attributeList   = $goodsattrList['attributeList'];
            $goods_attr_Data = [];
            foreach ($attributeList as $k => $v) {
                foreach ($v['attr_values'] as $a => $b) {
                    $goods_attr_Data[] = [
                        'goods_id'     => $goods_id,
                        'attr_id'      => $v['attr_id'],
                        'attr_value'   => $b['val'],
                        'attr_checked' => $b['checked'],
                    ];
                }
            }
            Db::name('goods_attr')->strict(false)->insertAll($goods_attr_Data);
            #add to products table, it has a bug, waiting for next accept
            $productsData = $goodsattrList['productsList'];
            $min_price    = null;
            $spec_stock   = 0;
            foreach ($productsData as $k => $v) {
                if (is_null($min_price)) {
                    $min_price = $v['product_price'];
                } else {
                    $min_price = min($min_price, $v['product_price']);
                }
                $spec_stock                    += $v['product_number'];
                $productsData[$k]['goods_id']  = $goods_id;
                $productsData[$k]['attr_keys'] = get_goods_attr_ids($v['tempvalue'], $goods_id);

                //多规格,库存日志
                if (isset($spec_stock_num[$v['tempvalue']])) {
                    if ($v['product_number'] - $spec_stock_num[$v['tempvalue']] != 0) {
                        goods_stock_log($goods_id, $v['product_number'] - $spec_stock_num[$v['tempvalue']], $pageparm['goods_name'], 0, session('admin_user.id'), $v['tempvalue'], '', session('admin_user.username'));
                    }
                } else {
                    goods_stock_log($goods_id, $v['product_number'], $pageparm['goods_name'], 0, session('admin_user.id'), $v['tempvalue'], '', session('admin_user.username'));
                }
            }
            Db::name('products')->strict(false)->insertAll($productsData);
            if (is_numeric($min_price)) {
                $ids = Db::name('products')->where('goods_id', $goods_id)->column('product_id');
                Db::name('goods')->where('goods_id', $goods_id)->update(['shop_price' => $min_price, 'goods_number' => $spec_stock, 'product_id' => implode(',', $ids)]);
            } else {
                //普通商品库存日志
                goods_stock_log($goods_id, $pageparm['goods_number'] - $stock, $pageparm['goods_name'], 0, session('admin_user.id'), '', '', session('admin_user.username'));
            }

            $product_attr_temp = input('product_attr');
            if (isset($product_attr_temp)) {
                $product_attr = [];
                foreach ($product_attr_temp as $k => $v) {
                    $product_attr[] = [
                        'goods_id'       => $goods_id,
                        'product_arr_id' => $k,
                        'element'        => $v,
                    ];
                }
                Db::name('goods_map')->insertAll($product_attr);
            }
            if ($goods_id > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function goods_stock_chage()
    {
        $id    = input('goods_id/d');
        $num   = input('num/d');
        $goods = Db::name('goods')->where('goods_id', $id)->field('goods_name,goods_number')->find();
        $res   = Db::name('goods')->where('goods_id', $id)->update(['goods_number' => $num]);
        if ($res > 0) {
            goods_stock_log($id, $num - $goods['goods_number'], $goods['goods_name'], 0, session('admin_user.id'), '', '', session('admin_user.username'));
            return json();
        } else {
            return json('操作失败', 403);
        }
    }

    public function goodsCopy()
    {

        Db::transaction(function () {
            $goods_id              = input('goods_id', 0);
            $goodsData             = Db::name("goods")->where('goods_id', $goods_id)->find();
            $goodsData['goods_sn'] = create_goods_sn();
            unset($goodsData['goods_id'], $goodsData['miniprograme_qrcode'], $goodsData['total_sale_num'], $goodsData['virtual_sale_num']);
            $gid        = Db::name("goods")->insertGetId($goodsData);
            $miniQrcode = (new Miniprogram)->makeQrcodeWithGoods($gid);
            Db::name("goods")->where('goods_id', $gid)->update(['miniprograme_qrcode' => $miniQrcode]);
            $goods_attrData = Db::name('goods_attr')->where('goods_id', $goods_id)->select();
            if (count($goods_attrData) > 0) {
                foreach ($goods_attrData as $k => $v) {
                    $goods_attrData[$k]['goods_id'] = $gid;
                    unset($goods_attrData[$k]['goods_attr_id']);
                }
                Db::name("goods_attr")->insertAll($goods_attrData);
            }
            $productsData = Db::name("products")->where('goods_id', $goods_id)->select();
            if (count($productsData) > 0) {
                foreach ($productsData as $k => $v) {
                    $productsData[$k]['goods_id'] = $gid;
                    unset($productsData[$k]['product_id']);
                }
                Db::name("products")->insertAll($productsData);
            }
            $goods_galleryData = Db::name("goods_gallery")->where('goods_id', $goods_id)->select();
            if (count($goods_galleryData) > 0) {
                foreach ($goods_galleryData as $k => $v) {
                    $goods_galleryData[$k]['goods_id'] = $gid;
                    unset($goods_galleryData[$k]['img_id']);
                }
                Db::name("goods_gallery")->insertAll($goods_galleryData);
            }
        });
        ajaxmsg('操作', 1);
    }

    public function products()
    {
        $goods_id = input("goods_id");
        if (Request::isPost()) {
            $pageparm       = $this->getPageparm();
            $product_number = Db::name('products')->where('product_id', $pageparm['product_id'])->value('product_number');
            $goods_name     = Db::name('goods')->where('goods_id', $pageparm['goods_id'])->value('goods_name');
            $res            = Db::name('products')->update($pageparm);
            if ($pageparm['product_number'] - $product_number != 0) {
                goods_stock_log($pageparm['goods_id'], $pageparm['product_number'] - $product_number, $goods_name, 0, session('admin_user.id'), $pageparm['tempvalue'], '', session('admin_user.username'));
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }

        if (Request::isAjax()) {
            $map      = [];
            $map[]    = ['goods_id', 'eq', $goods_id];
            $keywords = input('keywords');
            if ($keywords) {
                $map[] = ['product_sn', 'eq', "$keywords"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 20);
            $list  = Db::name("products")->where($map)->page($page, $rows)->select();
            $total = Db::name("products")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $info = Db::name("goods")->where("goods_id={$goods_id}")->field("goods_id,goods_sn")->find();
            $this->assign('info', json_encode($info, 320));
            $this->assign('goods_id', $goods_id);
            return view();
        }
    }

    public function categoryListInitAjax()
    {
        $data            = [];
        $cat_id          = input("cat_id", 0);
        $cat_level       = input("cat_level", 0);
        $data['oneList'] = Db::name("category")->where("parent_id=0 and is_delete=0")->order('sort_order asc')->select();
        switch ($cat_level) {
            case 0:
                $data['twoList']  = [];
                $data['thrList']  = [];
                $data['fourList'] = [];
                break;
            case 1:
                $data['twoList']  = Db::name("category")->where("parent_id={$cat_id} and is_delete=0")->order('sort_order asc')->select();
                $data['thrList']  = [];
                $data['fourList'] = [];
                break;
            case 2:
                $data['thrList']  = Db::name("category")->where("parent_id={$cat_id} and is_delete=0")->order('sort_order asc')->select();
                $data['fourList'] = [];
                break;
            case 3:
                $data['fourList'] = Db::name("category")->where("parent_id={$cat_id} and is_delete=0")->order('sort_order asc')->select();
                break;
        }
        ajaxmsg('true', 1, $data);
    }

    public function cateTreeAjax()
    {
        $cat_id = input("cat_id", 0);

        $data['rows']  = categoryListAjax($cat_id);
        $data['total'] = Db::name("category")->count("cat_id");

        echo json_encode($data, 320);
        exit;
    }

    #编辑商品获取选中分类的数据源
    public function getcategoryListDefault($goods_id = 0)
    {
        $data            = [];
        $data['oneList'] = Db::name("category")->where("parent_id=0 and is_delete=0")
            ->field("cat_id,cat_name")
            ->order('sort_order asc')
            ->select();
        if ($goods_id == 0) {
            $data['oneList']  = [];
            $data['twoList']  = [];
            $data['thrList']  = [];
            $data['fourList'] = [];
            return $data;
        }
        $cat_id = Db::name("goods")->where("goods_id={$goods_id}")->value('cat_id');
        #暂定向上查两级
        $b = Db::name("category")->where("cat_id={$cat_id}")->value("parent_id");
        if ($b > 0) {
            $a = Db::name("category")->where("cat_id={$b}")->value("parent_id");
            if ($a > 0) {
                $data['twoList'] = Db::name("category")
                    ->field("cat_id,cat_name")
                    ->where("parent_id={$a}")
                    ->order('sort_order asc')
                    ->select();
                $data['thrList'] = Db::name("category")
                    ->field("cat_id,cat_name")
                    ->where("parent_id={$b}")
                    ->order('sort_order asc')
                    ->select();
            } else {
                $data['twoList'] = Db::name("category")
                    ->field("cat_id,cat_name")
                    ->where("parent_id={$b}")
                    ->order('sort_order asc')
                    ->select();
                $data['thrList'] = [];
            }
            $data['fourList'] = [];
        }
        return $data;
    }

    public function brandListAjax()
    {
        brandListAjax();
    }

    #商品类型-不同于商品分类
    public function goodstype()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = input("keywords");
            $page     = input('page', 1);
            $rows     = input('rows', 10);
            $start    = ($page - 1) * $rows;
            if ($keywords) {
                $sql = "SELECT a.*,b.count_cat_id FROM oc_goods_type a 
                        LEFT JOIN (SELECT COUNT(cat_id) as count_cat_id,cat_id FROM oc_attribute GROUP BY cat_id) b 
                        ON a.cat_id=b.cat_id where a.cat_name like '%{$keywords}%'
                        LIMIT {$start},{$rows}";
            } else {
                $sql = "SELECT a.*,b.count_cat_id FROM oc_goods_type a 
                        LEFT JOIN (SELECT COUNT(cat_id) as count_cat_id,cat_id FROM oc_attribute GROUP BY cat_id) b 
                        ON a.cat_id=b.cat_id LIMIT {$start},{$rows};";
            }
            $list  = Db::query($sql);
            $total = Db::name("goods_type")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function goodstypeadd()
    {

        $cat_id     = input("cat_id", 0);
        $infoColumn = gettableColumn('goods_type');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isGet()) {
            if ($cat_id > 0) {
                $info = Db::name("goods_type")->where("cat_id={$cat_id}")->find();
            } else {
                $info            = gettableColumn('goods_type');
                $info['enabled'] = 1;
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            $map      = [['cat_name', '=', $pageparm['cat_name']]];
            if ($pageparm['cat_id'] > 0) {
                $map[] = ['cat_id', '<>', $pageparm['cat_id']];
            }
            $count = Db::name('goods_type')->where($map)->count();
            if ($count) {
                ajaxmsg('规格类型名称不能重复，请重新输入', 0);
            }

            if ($pageparm['cat_id'] > 0) {
                $res = Db::name('goods_type')->strict(false)->update($pageparm);
            } else {
                $res = Db::name('goods_type')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function goodstypeListAjax()
    {
        goodstypeListAjax();
    }

    public function attributelistAjax()
    {
        attributelistAjax();
    }

    public function goods_type_delete()
    {
        $cat_id = input("cat_id");
        Db::name("goods_type")->where("cat_id={$cat_id}")->delete();
        Db::name("attribute")->where("cat_id={$cat_id}")->delete();
        ajaxmsg('删除成功', 1);
    }

    #商品类型-关联属性
    public function attributelist()
    {
        $cat_id = input("cat_id");
        $sort   = input("sort");
        if (Request::isPost()) {
            $map   = [];
            $map[] = ['a.cat_id', 'eq', $cat_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("attribute")->alias("a")
                ->join("goods_type b", "a.cat_id=b.cat_id", "LEFT")
                ->where($map)
                ->field("a.*,b.cat_name")
                ->page($page, $rows)
                ->order("a.attr_id {$sort}")
                ->select();
            $total = Db::name("attribute")->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('cat_id', $cat_id);
            return view();
        }
    }

    public function attributeadd()
    {
        $attr_id         = input("attr_id", 0);
        $cat_id          = input("cat_id", 0);
        $goods_type_list = Db::name("goods_type")->field("cat_id,cat_name")->select();
        $this->assign('goods_type_list', json_encode($goods_type_list, 320));
        $infoColumn = gettableColumn('attribute');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            $map      = [
                ['attr_name', '=', $pageparm['attr_name']],
                ['cat_id', '=', $pageparm['cat_id']]
            ];
            if ($pageparm['attr_id'] > 0) {
                $map[] = ['attr_id', '<>', $pageparm['attr_id']];
            }
            $count = Db::name('attribute')->where($map)->count();
            if ($count) {
                ajaxmsg('名称重复，请重新输入', 0);
            }

            $attr_values_temp = explode("\n", $pageparm['attr_values']);
            $attr_values      = [];
            foreach ($attr_values_temp as $k => $v) {
                if ($v) {
                    $attr_values[] = $v;
                }
            }
            $pageparm['attr_values'] = json_encode($attr_values, 320);
            if ($pageparm['attr_id'] > 0) {
                $res = Db::name('attribute')->update($pageparm);
            } else {
                $res = Db::name('attribute')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($attr_id > 0) {
                $info                = Db::name("attribute")->where("attr_id={$attr_id}")->find();
                $info['attr_values'] = json_decode($info['attr_values'], true);
            } else {
                $info = gettableColumn('attribute');
            }
            $this->assign('cat_id', $cat_id);
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function attributedelete()
    {
        $attr_id = input("attr_id");
        Db::name("attribute")->where("attr_id={$attr_id}")->delete();
        ajaxmsg('删除成功', 1);
    }

    #品牌管理
    public function brandlist()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = input("keywords");
            if ($keywords) {
                $map[] = ['brand_name', "like", "%$keywords%"];
            }
            $map[] = ['is_delete', 'eq', 0];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("brand")->where($map)->page($page, $rows)->order("brand_id desc")->select();
            $total = Db::name("brand")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {

            return view();
        }
    }

    public function brandadd()
    {
        $brand_id   = input("brand_id", 0);
        $infoColumn = gettableColumn('brand');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($brand_id > 0) {
                $info = Db::name("brand")->where("brand_id={$brand_id}")->find();
            } else {
                $info = gettableColumn('brand');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['brand_id'] > 0) {
                $res = Db::name('brand')->update($pageparm);
            } else {
                $brand_name = Db::name("brand")->where("brand_name='{$pageparm['brand_name']}'")->value("brand_name");
                if ($brand_name) {
                    ajaxmsg('品牌名称重复', 0);
                }
                $res = Db::name('brand')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function setinitials()
    {
        $table = input("table");
        $list  = Db::name($table)->field("brand_id,brand_name,brand_first_char")->select();
        foreach ($list as $k => $v) {
            //$list[$k]['brand_first_char'] = getInitials($v['brand_name']);
            Db::name($table)->where("brand_id={$v['brand_id']}")->setField("brand_first_char", getInitials($v['brand_name']));
        }
        ajaxmsg('更新成功', 1);
    }

    public function storebrand()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['brand_name', "like", "%$keywords%"];
            }
            $map[] = ['is_delete', 'eq', 0];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_brand_apply")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("store_brand_apply")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function auditStorebrand()
    {
        $pageparm = $this->getPageparm();
        $brand    = Db::name("brand")->where("brand_name='{$pageparm['brand_name']}'")->find();
        if ($brand) {
            ajaxmsg('操作失败，重复添加品牌', 0);
        }
        if ($pageparm['brand_id'] > 0 && $pageparm['audit_status'] == 1) {
            $res                              = Db::name("store_brand_apply")->strict(false)->update($pageparm);
            $store_bind_brandData['brand_id'] = $pageparm['brand_id'];
            $store_bind_brandData['store_id'] = $pageparm['store_id'];
            $store_bind_brandData['add_time'] = time();
            Db::name("store_bind_brand")->insertGetId($store_bind_brandData);
        }
        if ($pageparm['brand_id'] == 0 && $pageparm['audit_status'] == 1) {
            $res = Db::name("store_brand_apply")->strict(false)->update($pageparm);
            #添加到本地品牌库
            $brandData = $pageparm;
            unset($brandData['brand_id']);
            $brand_id                         = Db::name("brand")->strict(false)->insertGetId($brandData);
            $store_bind_brandData['brand_id'] = $brand_id;
            $store_bind_brandData['store_id'] = $pageparm['store_id'];
            $store_bind_brandData['add_time'] = time();
            Db::name("store_bind_brand")->insertGetId($store_bind_brandData);
        }
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #商品分类
    public function category()
    {
        if (Request::isAjax()) {
            $map       = [];
            $parent_id = input("parent_id", 0);
            $is_delete = input("is_delete", 0);
            $keywords  = input("keywords");
            if ($keywords) {
                $map[] = ['cat_name', "like", "%$keywords%"];
            }
            $map[] = ['parent_id', 'eq', $parent_id];
            $map[] = ['is_delete', 'eq', $is_delete];
            $page  = input('page', 1);
            $rows  = input('rows', 50);
            $list  = Db::name("category")
                ->where($map)
                ->page($page, $rows)
                ->order('sort_order asc')
                ->select();
            $total = Db::name("category")->where($map)->count();
            if ($parent_id > 0) {
                $pid = Db::name("category")->where("cat_id={$parent_id}")->value('parent_id');
            } else {
                $pid = 0;
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total, 'pid' => $pid]);
        } else {

            return view();
        }
    }

    public function storecategory()
    {
        if (Request::isPost()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.cat_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_category")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_name")
                ->where($map)
                ->page($page, $rows)
                ->order("cat_id desc")
                ->select();
            $total = Db::name("store_category")->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function categoryadd()
    {
        $iconList = Config::get('iconList');
        $this->assign('iconlist', $iconList);

        $cat_id     = input("cat_id", 0);
        $infoColumn = gettableColumn('brand');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($cat_id > 0) {
                $filter_attr     = [];
                $info            = Db::name("category")->where("cat_id={$cat_id}")->find();
                $category_name   = Db::name("category")->where('cat_id', '=', $info['parent_id'])->value('cat_name');
                $filter_attr_arr = explode(',', $info['filter_attr']);
                foreach ($filter_attr_arr as $k => $v) {
                    $filter_attr[] = [
                        'attr_id'   => $v,
                        'attr_name' => '',
                        'isshow'    => 1,
                    ];
                }
                $info['filter_attr'] = $filter_attr;
            } else {
                $info          = gettableColumn('category');
                $category_name = '';
            }
            $templateList = Config::get('templateList');
            $this->assign('templateList', $templateList);
            $this->assign('cat_info', $info);
            $this->assign('category_name', $category_name ? $category_name : '顶级分类');
            $this->assign('info', json_encode($info, 320));

            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['cat_id'] > 0) {
                $res = Db::name('category')->update($pageparm);
            } else {
                $res = Db::name('category')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function getcategoryList()
    {
        $parent_id = input("parent_id", 0);
        $table     = input('table');
        $map       = [];
        $map[]     = ['is_delete', '=', 0];
        $map[]     = ['parent_id', '=', $parent_id];
        $list      = Db::name($table)->where($map)->order('sort_order asc')->select();
        ajaxmsg('true', 1, $list);
    }


    #本地商品库分类
    public function libcatlist()
    {
        if (Request::isAjax()) {
            $map       = [];
            $parent_id = input("parent_id", 0);
            $keywords  = input("keywords");
            if ($keywords) {
                $map[] = ['cat_name', "like", "%$keywords%"];
            }
            $map[] = ['parent_id', 'eq', $parent_id];
            $page  = input('page', 1);
            $rows  = input('rows', 50);
            $list  = Db::name("goods_lib_cat")
                ->where($map)
                ->page($page, $rows)
                ->order('sort_order asc')
                ->select();
            $total = Db::name("goods_lib_cat")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function libcatadd()
    {
        $cat_id     = input("cat_id", 0);
        $infoColumn = gettableColumn('goods_lib_cat');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($cat_id > 0) {
                $info = Db::name("goods_lib_cat")->where("cat_id={$cat_id}")->find();
            } else {
                $info = gettableColumn('goods_lib_cat');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['cat_id'] > 0) {
                $res = Db::name('goods_lib_cat')->update($pageparm);
            } else {
                $res = Db::name('goods_lib_cat')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function libgoods()
    {
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order');
            $is_delete  = input('is_delete', 0);
            $brand_id   = input('brand_id', 0);
            $cat_id     = input('cat_id', 0);
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            if ($brand_id > 0) {
                $map[] = ['a.brand_id', 'eq', $brand_id];
            }
            if ($cat_id > 0) {
                $map[] = ['a.cat_id', 'eq', $cat_id];
            }
            $map[] = ['a.is_delete', 'eq', $is_delete];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods_lib")->alias("a")
                ->join("brand b", "a.brand_id=b.brand_id", "LEFT")
                ->field("a.*,b.brand_name")
                ->where($map)
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods_lib")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function goodsallexecute()
    {
        $map            = [];
        $ids            = input('ids');
        $table          = trim(input('table'));
        $index          = trim(input('index'));
        $lib_goods_Data = [];
        switch ($index) {
            case 'trash':
                $lib_goods_Data = ['is_delete' => 1];
                break;
            case 'on_sale':
                $lib_goods_Data = ['is_on_sale' => 1];
                break;
            case 'not_on_sale':
                $lib_goods_Data = ['is_on_sale' => 0];
                break;
        }
        $pk    = Db::name($table)->getPk();#获取主键
        $map[] = [$pk, 'in', $ids];
        $res   = Db::name($table)->where($map)->update($lib_goods_Data);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function libgoodsadd()
    {
        $goods_id   = input("goods_id", 0);
        $infoColumn = gettableColumn('goods_lib');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isGet()) {
            $goodsattrList                  = [];
            $goodsattrList['goodstypeList'] = [];
            if ($goods_id > 0) {
                $info             = Db::name("goods_lib")->where("goods_id={$goods_id}")->find();
                $goodsgalleryList = Db::name('goods_lib_gallery')->where("goods_id={$goods_id}")->select();
            } else {
                $info             = gettableColumn('goods_lib');
                $goodsgalleryList = [];
            }
            $this->assign('goods_desc', $info['goods_desc']);
            $info['goods_desc'] = '';
            $this->assign('info', json_encode($info, 320));
            $this->assign('goodsgalleryList', json_encode($goodsgalleryList, 320));
            $this->assign('goodsattrList', json_encode($goodsattrList, 320));
            return view();
        } else {

            $pageparm         = $this->getPageparm();
            $goodsgalleryList = json_decode($_REQUEST['goodsgalleryList'], true);

            if ($pageparm['goods_id'] > 0) {
                $goods_id = $pageparm['goods_id'];
                unset($pageparm['goods_id']);
                Db::name('goods_lib')->strict(false)->where("goods_id={$goods_id}")->update($pageparm);
                Db::name('goods_lib_gallery')->where("goods_id={$goods_id}")->delete();
            } else {
                $goods_id = Db::name('goods_lib')->strict(false)->insertGetId($pageparm);
            }
            #add to goods_gallery table
            foreach ($goodsgalleryList as $k => $v) {
                $goodsgalleryList[$k]['goods_id'] = $goods_id;
            }
            Db::name('goods_lib_gallery')->strict(false)->insertAll($goodsgalleryList);
            if ($goods_id > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #附加服务
    public function insurance()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['insurance_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("insurance")->where($map)->page($page, $rows)->order("insurance_id desc")->select();
            $total = Db::name("insurance")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function insuranceadd()
    {
        $insurance_id = input("insurance_id", 0);
        $infoColumn   = gettableColumn('insurance');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($insurance_id > 0) {
                $info = Db::name("insurance")->where("insurance_id={$insurance_id}")->find();
            } else {
                $info = gettableColumn('insurance');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['insurance_id'] > 0) {
                $res = Db::name('insurance')->update($pageparm);
            } else {
                $pageparm['create_time'] = time();
                $res                     = Db::name('insurance')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #商品评论
    public function comment()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.user_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $model = new Comment;
            $list  = $model->alias('a')
                ->join('order_goods b', 'a.rec_id=b.rec_id')
                ->where($map)
                ->page($page, $rows)
                ->field('a.*,b.goods_name,b.goods_img')
                ->order("a.comment_id desc")
                ->select();
            $total = Db::name("comment")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    #评论详情
    public function comment_detail()
    {
        $id    = input('id/d');
        $model = new Comment;
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'id'        => 'require|gt:0',
                'user_name' => 'require',
                'content'   => 'require'
            ], [
                'id'        => '评论ID错误!',
                'user_name' => '用户名不正确!',
                'content'   => '用户名不能为空!'
            ]);
            if (true !== $result) {
                return json($result, 403);
            }

            $data['user_id'] = session('admin_user.id');
            if (false === $model->addReply($data, 2)) {
                return json('评论不存在', 403);
            }
            return json($data);
        }
        $comment             = $model->get($id);
        $order_goods         = Db::name('order_goods')->where('rec_id', $comment['rec_id'])->find();
        $comment->goods_name = $order_goods['goods_name'];
        $comment->goods_img  = $order_goods['goods_img'];
        $comment->reply      = $model->where(['type' => 2, 'parent_id' => $comment->comment_id])->value('content');
        $this->assign('comment', $comment);
        return view();
    }

    #参数分类
    public function product_type()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("product_type")->alias("a")
                ->join("product_cate b", "a.cate_id=b.id", "LEFT")
                ->join("store c", "a.store_id=c.store_id", "LEFT")
                ->field("a.*,b.name as cate_name,c.store_name")
                ->where($map)
                ->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("product_type")->alias("a")
                ->join("product_cate b", "a.cate_id=b.id", "LEFT")
                ->join("store c", "a.store_id=c.store_id", "LEFT")
                ->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function product_typeadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('product_type');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();

            $con = Db::name("product_type")
                ->where("cate_id", '=', $pageparm['cate_id'])
                ->where("name", '=', $pageparm['name'])
                ->count("id");

            if ($con > 0) {
                ajaxmsg('名称已存在', 0);
            }


            if ($pageparm['id'] > 0) {
                $res = Db::name('product_type')->strict(false)->update($pageparm);
            } else {
                $pageparm['create_time'] = time();
                $res                     = Db::name('product_type')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($id > 0) {
                $info              = Db::name("product_type")->where("id={$id}")->find();
                $info['cate_name'] = Db::name("product_cate")->where('id', '=', $info['cate_id'])->value("name");
            } else {
                $info = gettableColumn('product_type');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function product_typeAjax()
    {
        $type_id = input('type_id/d');
        $list    = Db::name("product_type")->where("type_id", '=', $type_id)->select();
        ajaxmsg('true', 1, $list);
    }

    public function product_attrAjax()
    {
        $type_id = input('type_id', 0);
        $list    = Db::name("product_attr")->where("type_id", '=', $type_id)->select();
        ajaxmsg('true', 1, $list);
    }

    public function product_attr()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.name', "like", "%$keywords%"];
            }
            $type_id = input('type_id');
            if ($type_id > 0) {
                $map[] = ['a.type_id', '=', $type_id];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("product_attr")->alias("a")
                ->join("product_type b", "a.type_id=b.id", "LEFT")
                ->join("product_group c", "a.group_id=a.group_id", "LEFT")
                ->field("a.*,b.name as typename,c.name as group_name")
                ->where($map)
                ->page($page, $rows)
                ->order("a.id desc")
                ->group("a.id")
                ->select();
            $total = Db::name("product_attr")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {

            $this->assign('type_id', input('type_id'));
            return view();
        }
    }

    public function product_attradd()
    {
        //呈现方式 checkbox：多选，radio：单选，datetime：时间控件，select：下拉菜单，textarea：文本域，text：自定义
        $id         = input("id", 0);
        $infoColumn = gettableColumn('product_attr');
        $this->assign('infoColumn', json_encode($infoColumn));
        $product_type_list = Db::name("product_type")->order("id desc")->select();
        $this->assign('product_type_list', json_encode($product_type_list, 320));
        if (Request::isGet()) {
            if ($id > 0) {
                $info                  = Db::name("product_attr")->where("id={$id}")->find();
                $info['attrvaluejson'] = json_decode($info['attrvaluejson'], true);
            } else {
                $info                  = gettableColumn('product_attr');
                $info['attrvaluejson'] = [];
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('product_attr')->strict(false)->update($pageparm);
            } else {
                $pageparm['create_time'] = time();
                $res                     = Db::name('product_attr')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function product_type_delete()
    {
        $pageparm = $this->getPageparm();
        $map      = [];
        $map[]    = ['id', '=', $pageparm['id']];
        $res      = Db::name('product_type')->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function product_attr_delete()
    {
        $pageparm = $this->getPageparm();
        $map      = [];
        $map[]    = ['id', '=', $pageparm['id']];
        $res      = Db::name('product_attr')->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function product_cate()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['name', "like", "%$keywords%"];
            }
            $partent_id = input('parent_id', 0);
            $map[]      = ['parent_id', '=', $partent_id];
            $page       = input('page', 1);
            $rows       = input('rows', 10);
            $list       = Db::name("product_cate")->where($map)->page($page, $rows)->order("id desc")->select();
            $total      = Db::name("product_cate")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function product_cateAjax()
    {
        $id = input('id', 0);

        $parent_id = input("parent_id", 0);
        $table     = input('table');
        if ($id > 0) {
            $list = Db::name($table)
                ->where("parent_id={$parent_id}")
                ->where("id != {$id}")
                ->order('sort_order asc')
                ->select();
        } else {
            $list = Db::name($table)
                ->where("parent_id={$parent_id}")
                ->order('sort_order asc')
                ->select();
        }

        ajaxmsg('true', 1, $list);
    }

    public function product_cateadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('product_cate');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('product_cate')->strict(false)->update($pageparm);
            } else {

                $res = Db::name('product_cate')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($id > 0) {
                $info              = Db::name("product_cate")
                    ->where("id={$id}")->find();
                $info['cate_name'] = Db::name("product_cate")->where('id', '=', $info['parent_id'])->value('name');
            } else {
                $info = gettableColumn('product_cate');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function product_group()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("product_group")->where($map)->page($page, $rows)->order("group_id desc")->select();
            $total = Db::name("product_group")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function product_groupAjax()
    {
        $id      = input('id', 0);
        $cate_id = Db::name("product_type")->where("id", '=', $id)->value("cate_id");
        $list    = Db::name('product_group')->where('cate_id', '=', $cate_id)->order("sort desc")->select();
        ajaxmsg('true', 1, $list);
    }

    public function product_groupadd()
    {
        $group_id   = input("group_id", 0);
        $infoColumn = gettableColumn('product_group');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            if ($pageparm['group_id'] > 0) {
                $res = Db::name('product_group')->strict(false)->update($pageparm);
            } else {

                $res = Db::name('product_group')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($group_id > 0) {
                $info              = Db::name("product_group")
                    ->where("group_id={$group_id}")->find();
                $info['cate_name'] = Db::name("product_cate")->where('id', '=', $info['cate_id'])->value('name');
            } else {
                $info = gettableColumn('product_group');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function miniprograme_qrcode()
    {
        $goods_id   = input('goods_id/d');
        $goodsModel = new GoodsModel;
        $goods      = $goodsModel->get($goods_id);
        if ($goods['miniprograme_qrcode']) {
            return json($goods['miniprograme_qrcode']);
        }

        $name = (new Miniprogram)->makeQrcodeWithGoods($goods_id);
        $goodsModel->where('goods_id', $goods_id)->update(['miniprograme_qrcode' => $name]);
        return json($name);
    }

    public function goods_storage_put()
    {
        if ($this->request->isPost()) {
            $logModel   = new GoodsStockLog;
            $page       = input('page/d', 1);
            $limit      = input('rows/d', 10);
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $keywords   = input('keywords');

            $data = $logModel->getList($page, $limit, $start_time, $end_time, $keywords);
            return json($data);
        }

        return view();
    }

    public function goods_storage_out()
    {
        if ($this->request->isPost()) {
            $logModel   = new GoodsStockLog;
            $page       = input('page/d', 1);
            $limit      = input('rows/d', 10);
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $keywords   = input('keywords');

            $data = $logModel->getList($page, $limit, $start_time, $end_time, $keywords, 'out');
            return json($data);
        }

        return view();
    }

    public function goods_storage_del()
    {
        $ids      = input('ids');
        $logModel = new GoodsStockLog;
        $count    = $logModel->whereIn('id', $ids)->update(['is_delete' => time()]);
        if ($count) {
            return json();
        }
        return json('操作失败,删除0条', 403);
    }

    public function make_miniqrcdoe()
    {
        $goods_id = input('goods_id/d');
        $name     = Db::name('goods')->where('goods_id', $goods_id)->value('miniprograme_qrcode');
        $name     = Env::get('root_path') . 'uploads' . '/' . $name;
        if (is_file($name)) {
            @unlink($name);
        }
        $name = (new Miniprogram)->makeQrcodeWithGoods($goods_id);
        Db::name('goods')->where('goods_id', $goods_id)->update(['miniprograme_qrcode' => $name]);
        return json($name);
    }
}
