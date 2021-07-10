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

class Market extends Common
{
    public function center()
    {
        return view();
    }

    #积分商城
    public function exgoods()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['b.goods_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("exchange_goods")->alias("a")
                ->join("goods b", 'a.goods_id=b.goods_id', 'LEFT')
                ->where($map)
                ->field("b.*,a.*")
                ->order("a.eid desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("exchange_goods")->alias("a")
                ->join("goods b", 'a.goods_id=b.goods_id', 'LEFT')
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function exgoodsadd()
    {
        $eid = input('eid', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            if ($eid > 0) {
                $map[] = ['b.eid', 'eq', $eid];
                $list  = Db::name("goods")->alias("a")
                    ->join("exchange_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->field("a.goods_name,a.store_id as user_id,a.goods_id,b.eid,b.review_status,b.exchange_integral,b.market_integral,b.is_exchange,b.is_hot,b.is_best")
                    ->where($map)
                    ->order("a.goods_id {$sort_order}")
                    ->page($page, $rows)
                    ->select();
                $total = Db::name("goods")->alias("a")
                    ->join("exchange_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->where($map)
                    ->count();
            } else {
                $list  = Db::name("goods")->alias("a")
                    ->join("exchange_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->field("a.goods_name,a.goods_id,a.store_id as user_id,b.review_status,b.exchange_integral,b.market_integral,b.is_exchange,b.is_hot,b.is_best")
                    ->where($map)
                    ->where('b.eid', 'null')
                    ->order("a.goods_id {$sort_order}")
                    ->page($page, $rows)
                    ->select();
                $total = Db::name("goods")->alias("a")
                    ->join("exchange_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->where($map)
                    ->where('b.eid', 'null')
                    ->count();
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('eid', $eid);
            return view();
        }
    }


    #拍卖专区
    public function paimaigoods()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['b.goods_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("paimai_goods")->alias("a")
                ->leftJoin("goods b", 'a.goods_id=b.goods_id')
                ->where($map)
                ->field("b.*,a.*")
                ->order("a.id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("paimai_goods")->alias("a")
                ->leftJoin("goods b", 'a.goods_id=b.goods_id')
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function paimaigoodsadd()
    {
        $id = input('id', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            if ($id > 0) {
                $map[] = ['b.id', 'eq', $id];
            }
            $list  = Db::name("goods")->alias("a")
                ->leftJoin("paimai_goods b", "a.goods_id=b.goods_id")
                ->field("b.*,a.goods_name,a.goods_id,a.shop_price")
                ->where($map)
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")
                ->leftJoin("paimai_goods b", "a.goods_id=b.goods_id")
                ->field("b.*,a.goods_name,a.goods_id,a.shop_price")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('id', $id);
            return view();
        }
    }

    #秒杀活动
    public function seckill()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['sec_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("seckill")
                ->order("sec_id desc")
                ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("seckill")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function seckilladd()
    {
        $sec_id     = input("sec_id", 0);
        $infoColumn = gettableColumn('seckill');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm               = $this->getPageparm();
            $pageparm['start_time'] = strtotime($pageparm['start_time']);
            $pageparm['end_time']   = strtotime($pageparm['end_time']);
            if ($pageparm['sec_id'] > 0) {
                $res = Db::name('seckill')->strict(false)->update($pageparm);
            } else {
                $res = Db::name('seckill')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($sec_id > 0) {
                $info = Db::name("seckill")
                    ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                    ->where("sec_id={$sec_id}")
                    ->find();
            } else {
                $info = gettableColumn('seckill');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function seckillgoodsadd()
    {
        $sec_id = input('sec_id', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods")->alias("a")
                ->join("seckill_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("a.goods_name,a.goods_id,a.shop_price,
            b.id,b.sec_id,b.sec_price,b.sec_num,b.sec_limit")
                ->where($map)
                ->where('b.sec_id', 'null')
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")
                ->join("seckill_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("a.goods_id")
                ->where($map)
                ->where('b.sec_id', 'null')
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('sec_id', $sec_id);
            return view();
        }
    }

    public function seckillgoodslist()
    {
        $sec_id = input('sec_id', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');

            if ($keywords) {
                $map[] = ['b.goods_name|b.goods_sn|b.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['b.is_delete', 'eq', 0];
            $map[] = ['b.is_on_sale', 'eq', 1];
            $map[] = ['a.sec_id', 'eq', $sec_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("seckill_goods a")
                ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("b.goods_name,a.goods_id,a.id,a.sec_id,a.sec_price,a.sec_num,a.sec_limit")
                ->where($map)
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();


            $total = Db::name("seckill_goods a")
                ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('sec_id', $sec_id);
            return view();
        }
    }

    #专题活动
    public function venue()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("venue")
                ->order("venue_id desc")
                ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("venue")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function venueadd()
    {
        $venue_id   = input("venue_id", 0);
        $infoColumn = gettableColumn('venue');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm               = $this->getPageparm();
            $pageparm['start_time'] = strtotime($pageparm['start_time']);
            $pageparm['end_time']   = strtotime($pageparm['end_time']);
            if ($pageparm['venue_id'] > 0) {
                $res = Db::name('venue')->strict(false)->update($pageparm);
            } else {
                $res = Db::name('venue')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($venue_id > 0) {
                $info = Db::name("venue")
                    ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                    ->where("venue_id={$venue_id}")
                    ->find();
            } else {
                $info = gettableColumn('venue');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function venuegoodslist()
    {
        $venue_id = input('venue_id', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $map[] = ['b.venue_id', 'eq', $venue_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods")->alias("a")
                ->join("venue_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("a.goods_name,a.goods_id,
            b.id,b.venue_id,b.venue_price,b.venue_num,b.venue_limit,b.sort,b.venue_thumb")
                ->where($map)
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")
                ->join("venue_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('venue_id', $venue_id);
            return view();
        }
    }

    public function venuegoodsadd()
    {
        $venue_id = input('venue_id', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods")->alias("a")
                ->join("venue_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("a.goods_name,a.goods_id,
            b.id,b.venue_id,b.venue_price,b.venue_num,b.venue_limit,b.venue_thumb")
                ->where($map)
                ->where('b.venue_id', 'null')
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")
                ->join("venue_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->where($map)
                ->where('b.venue_id', 'null')
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('venue_id', $venue_id);
            return view();
        }
    }

    #团购模块
    public function groupgoods()
    {
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $map[] = ['b.gid', 'gt', 0];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods")->alias("a")
                ->join("group_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->field("b.*,FROM_UNIXTIME(b.start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(b.end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                ->where($map)
                ->order("a.goods_id {$sort_order}")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods")->alias("a")
                ->join("group_goods b", "a.goods_id=b.goods_id", "LEFT")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function groupgoodsadd()
    {
        $gid = input('gid', 0);
        if (Request::isAjax()) {
            $map        = [];
            $sort_order = input('sort_order', 'desc');
            $keywords   = input('keywords');
            if ($keywords) {
                $map[] = ['a.goods_name|a.goods_sn|a.bar_code', 'like', "%{$keywords}%"];
            }
            $map[] = ['a.is_delete', 'eq', 0];
            $map[] = ['a.is_on_sale', 'eq', 1];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            if ($gid > 0) {
                $map[] = ['b.gid', 'eq', $gid];
            } else {
                $list  = Db::name("goods")->alias("a")
                    ->join("group_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->field("b.*,
                    a.goods_id,a.goods_name,a.shop_price,a.goods_brief")
                    ->where($map)
                    ->where('b.gid', 'null')
                    ->order("a.goods_id {$sort_order}")
                    ->page($page, $rows)
                    ->select();
                $total = Db::name("goods")->alias("a")
                    ->join("group_goods b", "a.goods_id=b.goods_id", "LEFT")
                    ->field("a.goods_id,a.goods_name,a.shop_price,a.goods_brief")
                    ->where($map)
                    ->where('b.gid', 'null')
                    ->count();
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            if ($gid > 0) {
                $info             = Db::name("group_goods")
                    ->field("*,FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
                    ->where("gid={$gid}")
                    ->find();
                $info['ext_info'] = json_decode($info['ext_info']) ? json_decode($info['ext_info']) : [];
            } else {
                $info                  = gettableColumn('group_goods');
                $info['ext_info']      = [];
                $info['review_status'] = 1;
            }
            $this->assign('info', json_encode($info, 320));
            $this->assign('gid', $gid);
            return view();
        }
    }

    #优惠券
    public function coupon()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("coupon")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->order("a.coupon_id desc")
                ->field("a.*,
                FROM_UNIXTIME(a.send_start_time) as start_time_cn,
                FROM_UNIXTIME(a.send_end_time) as end_time_cn,
                b.store_name")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("coupon")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function couponadd()
    {
        $coupon_id  = input("coupon_id", 0);
        $infoColumn = gettableColumn('coupon');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm                    = $this->getPageparm();
            $pageparm['send_start_time'] = strtotime($pageparm['send_start_time']);
            $pageparm['send_end_time']   = strtotime($pageparm['send_end_time']);
            $pageparm['use_start_time']  = strtotime($pageparm['use_start_time']);
            $pageparm['use_end_time']    = strtotime($pageparm['use_end_time']);
            if ($pageparm['coupon_id'] > 0) {
                $res = Db::name('coupon')->strict(false)->update($pageparm);
            } else {
                $res = Db::name('coupon')->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($coupon_id > 0) {
                $info = Db::name("coupon")
                    ->field("*,
                    FROM_UNIXTIME(send_start_time,'%Y-%m-%d %H:%i:%S') as send_start_time_cn,
                    FROM_UNIXTIME(send_end_time,'%Y-%m-%d %H:%i:%S') as send_end_time_cn,
                    FROM_UNIXTIME(use_start_time,'%Y-%m-%d %H:%i:%S') as use_start_time_cn,
                    FROM_UNIXTIME(use_end_time,'%Y-%m-%d %H:%i:%S') as use_end_time_cn")
                    ->where("coupon_id={$coupon_id}")
                    ->find();
            } else {
                $info = gettableColumn('coupon');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }


}
