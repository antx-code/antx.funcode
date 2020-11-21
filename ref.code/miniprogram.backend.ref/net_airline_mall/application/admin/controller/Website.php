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

use think\Db;
use think\exception\PDOException;
use think\facade\Request;
use think\facade\Config;
use app\admin\model\TransportFee;

class Website extends Common
{
    #友情链接
    public function friendlink()
    {
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("friend_link")->where($map)->page($page, $rows)->order("id desc")->select();
            $total = Db::name("friend_link")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function friendlinkadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('friend_link');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("friend_link")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('friend_link');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('friend_link')->update($pageparm);
            } else {
                $res = Db::name('friend_link')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #用户检索记录
    public function keywordslist()
    {
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("keywords")->where($map)->page($page, $rows)->order("id desc")->select();
            $total = Db::name("keywords")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }


    #商城设置
    public function shopconfig()
    {
        if (Request::isAjax()) {
            $map  = [];
            $info = Db::name("shop_config")->column('value', 'code');
            ajaxmsg('true', 1, $info);
        } else {
            return view();
        }
    }

    public function shopconfigupdate()
    {
        $pageparm = $this->getPageparm();
        $casestr  = '';
        $wherestr = '';
        foreach ($pageparm as $k => $v) {
            $casestr  .= " WHEN '{$k}' THEN '{$v}' ";
            $wherestr .= "'" . $k . "'" . ',';
        }
        $wherestr = trim($wherestr, ',');
        $prefix   = Config::get('database.prefix');
        $sql      = "update {$prefix}shop_config 
                set value = 
                case code 
                {$casestr} 
                end 
                where code in ({$wherestr})";
        $count    = Db::execute($sql);
        ajaxmsg("更新了{$count}项设置", 1);
    }

    public function shopconfigadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('shop_config');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("shop_config")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('shop_config');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('shop_config')->update($pageparm);
            } else {
                $res = Db::name('shop_config')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #广告位置
    public function adposition()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['position_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("adposition")->where($map)->page($page, $rows)->order("position_id desc")->select();
            $total = Db::name("adposition")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function adpositionlistAjax()
    {
        $list = Db::name("adposition")->field("position_id,position_name")->select();
        ajaxmsg('true', 1, $list);
    }

    public function adpositionadd()
    {
        $position_id = input("position_id", 0);
        $infoColumn  = gettableColumn('adposition');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($position_id > 0) {
                $info = Db::name("adposition")->where("position_id={$position_id}")->find();
            } else {
                $info = gettableColumn('adposition');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['position_id'] > 0) {
                $res = Db::name('adposition')->update($pageparm);
            } else {
                $res = Db::name('adposition')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }


    #广告管理
    public function adlist()
    {
        $position_id = input('position_id', 0);
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['ad_name', "like", "%$keywords%"];
            }
            if ($position_id > 0) {
                $map[] = ['position_id', 'eq', $position_id];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("ads")->where($map)
                ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                ->page($page, $rows)->order("ad_id desc")->select();
            $total = Db::name("ads")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('position_id', $position_id);
            return view();
        }
    }

    public function adadd()
    {
        $ad_id      = input("ad_id", 0);
        $infoColumn = gettableColumn('ads');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($ad_id > 0) {
                $info = Db::name("ads")
                    ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                    ->where("ad_id={$ad_id}")
                    ->find();
            } else {
                $info = gettableColumn('ads');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm               = $this->getPageparm();
            $pageparm['start_time'] = strtotime($pageparm['start_time']);
            $pageparm['end_time']   = strtotime($pageparm['end_time']);
            if ($pageparm['ad_id'] > 0) {
                $res = Db::name('ads')->strict(false)->update($pageparm);
            } else {
                $res = Db::name('ads')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #地区管理
    public function region()
    {
        $parent_id  = input('parent_id', 0);
        $region_id  = input('region_id', 0);
        $infoColumn = gettableColumn('region');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['region_name', "like", "%$keywords%"];
            }
            $map[] = ['parent_id', 'eq', $parent_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("region")->where($map)->page($page, $rows)->order("region_id desc")->select();
            $total = Db::name("region")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('region_id', $region_id);
            return view();
        }
    }

    public function setinitials()
    {
        $list = Db::name("region")->field("region_id,region_name,region_first_char")->select();
        foreach ($list as $k => $v) {
            Db::name("region")
                ->where("region_id={$v['region_id']} and region_first_char!=''")
                ->setField("region_first_char", getInitials(trim($v['region_name'])));
        }
        ajaxmsg('更新成功', 1, $list);
    }

    #区域管理
    public function adminregion()
    {
        $infoColumn = gettableColumn('admin_region');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("admin_region")
                ->field("*,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%S') as create_time_cn")
                ->where($map)->page($page, $rows)
                ->order("ra_sort desc")->select();
            $total = Db::name("admin_region")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function adminregionadd()
    {
        $ra_id      = input("ra_id", 0);
        $regionList = Db::name("region")->where("region_type=1")->select();
        $this->assign('regionList', json_encode($regionList, 320));
        $infoColumn = gettableColumn('admin_region');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isGet()) {
            if ($ra_id > 0) {
                $info = Db::name("admin_region")->where("ra_id={$ra_id}")->find();
            } else {
                $info = gettableColumn('admin_region');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm                = $this->getPageparm();
            $pageparm['create_time'] = time();
            if ($pageparm['ra_id'] > 0) {
                $res = Db::name('admin_region')->update($pageparm);
            } else {
                $res = Db::name('admin_region')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #OSS配置
    public function ossconfig()
    {
        $infoColumn = gettableColumn('ossconfig');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("ossconfig")
                ->where($map)->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("ossconfig")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function ossconfigadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('ossconfig');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("ossconfig")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('ossconfig');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('ossconfig')->update($pageparm);
            } else {
                $res = Db::name('ossconfig')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #短信配置
    public function smsconfig()
    {
        $infoColumn = gettableColumn('smsconfig');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("smsconfig")
                ->where($map)->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("smsconfig")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function smsconfigadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('smsconfig');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("smsconfig")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('smsconfig');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('smsconfig')->update($pageparm);
            } else {
                $res = Db::name('smsconfig')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #物流配送
    public function shipping()
    {
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("shipping")->where($map)->page($page, $rows)->order("shipping_id desc")->select();
            $total = Db::name("shipping")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function shippingadd()
    {
        $shipping_id = input("shipping_id", 0);
        $infoColumn  = gettableColumn('shipping');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($shipping_id > 0) {
                $info = Db::name("shipping")->where("shipping_id={$shipping_id}")->find();
            } else {
                $info = gettableColumn('shipping');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['shipping_id'] > 0) {
                $res = Db::name('shipping')->update($pageparm);
            } else {
                $res = Db::name('shipping')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #运费管理
    public function transport()
    {
        if (Request::isAjax()) {
            $map      = [
                ['store_id', '=', 0],
                ['is_delete', '=', 0]
            ];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("transport")->alias("a")
                ->join("shipping b", "a.shipping_id=b.shipping_id", "LEFT")
                ->field("a.*,b.shipping_name")
                ->where($map)
                ->page($page, $rows)
                ->order("transport_id desc")
                ->select();
            $total = Db::name("transport")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function transportadd()
    {
        $transport_id = input("transport_id", 0);
        $infoColumn   = gettableColumn('transport');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($transport_id > 0) {
                $info = Db::name("transport")->where("transport_id={$transport_id}")->find();
            } else {
                $info = gettableColumn('transport');
            }
            $this->assign('info', json_encode($info, 320));
            $this->assign('shippingList', json_encode(shippingListAjax(), 320));
            return view();
        } else {
            $pageparm                = $this->getPageparm();
            $pageparm['update_time'] = time();
            if ($pageparm['transport_id'] > 0) {
                $res = Db::name('transport')->update($pageparm);
            } else {
                $pageparm['create_time'] = time();
                $res                     = Db::name('transport')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function transport_fee()
    {
        if (Request::isAjax()) {
            $map      = [
                ['store_id', '=', 0],
                ['is_delete', '=', 0]
            ];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.title', "like", "%$keywords%"];
            }
            $page = input('page', 1);
            $rows = input('rows', 10);
            $res  = (new TransportFee)->getList($map, $page, $rows);
            ajaxmsg('true', 1, $res);
        } else {
            return view();
        }
    }

    public function transport_del()
    {
        $id = input('id');

        $model = new TransportFee;
        $res   = $model->whereIn('transport_id', $id)->update(['is_delete' => time()]);
        if ($res) return json();
        return json('操作失败,数据未删除', 403);
    }

    public function transport_fee_add()
    {
        if (Request::isPost()) {
            $pageparm                = $this->getPageparm();
            $pageparm['update_time'] = time();
            if ($pageparm['transport_id'] > 0) {
                $res = Db::name('transport')->update($pageparm);
            } else {
                $pageparm['create_time'] = time();
                $res                     = Db::name('transport')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }

        $transport_id = input('transport_id/d', 0);

        $model = new TransportFee;
        if ($transport_id > 0) {
            $info = Db::name('transport')->where('transport_id', $transport_id)->find();
        } else {
            $table = Db::query('SHOW COLUMNS FROM ' . config('database.prefix') . 'transport_fee');
            $info  = array_combine(array_column($table, 'Field'), array_column($table, 'Default'));
        }

        $resion = Db::name("region")->where("region_type=1 and parent_id=1")->column('region_name','region_id');
        $this->assign('info', $info);
        $this->assign('shippingList', shippingListAjax());
        $this->assign('region', $resion);
        return view();

    }

    public function payment()
    {
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("payment")->where($map)->page($page, $rows)->order("pay_id desc")->select();
            $total = Db::name("payment")->where($map)->count();
            ajaxmsg('true', 1, [
                'list'  => $list,
                'total' => $total,
            ]);
        } else {
            return view();
        }
    }

    public function paymadd()
    {
        $id         = input("pay_id", 0);
        $infoColumn = gettableColumn('payment');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("payment")->where("pay_id={$id}")->find();
            } else {
                $info = gettableColumn('payment');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['pay_id'] > 0) {
                $res = Db::name('payment')->update($pageparm);
            } else {
                $res = Db::name('payment')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function navlist()
    {
        if (Request::isAjax()) {
            $map   = [];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("website_nav")->where($map)
                ->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("website_nav")->where($map)->count();
            ajaxmsg('true', 1, [
                'list'  => $list,
                'total' => $total,
            ]);
        } else {
            return view();
        }
    }

    public function navladd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('payment');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("website_nav")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('website_nav');
            }
            $info['type'] = 1;
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('website_nav')->update($pageparm);
            } else {
                $res = Db::name('website_nav')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    #首页楼层设置
    public function home_floor()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("home_floor")->where($map)->page($page, $rows)->order("id desc")->select();
            $total = Db::name("home_floor")->where($map)->count();
            ajaxmsg('true', 1, [
                'list'  => $list,
                'total' => $total,
            ]);
        } else {
            return view();
        }
    }

    public function home_floor_add()
    {
        $id = input("id", 0);
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('home_floor')->update($pageparm);
            } else {
                $res = Db::name('home_floor')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($id > 0) {
                $info = Db::name("home_floor")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('home_floor');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }
}
