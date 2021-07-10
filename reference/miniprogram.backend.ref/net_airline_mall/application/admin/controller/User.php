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
use app\home\model\AccountLog;
use app\home\model\UserRecharge;

class User extends Common
{
    public function index()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['user_name|nick_name|mobile', "like", "%$keywords%"];
            }
            $page           = input('page', 1);
            $rows           = input('rows', 10);
            $list           = Db::name("user")
                ->where($map)
                ->field("*,FROM_UNIXTIME(reg_time,'%Y-%m-%d %H:%i:%S') as reg_time_cn")
                ->order("user_id desc")
                ->page($page, $rows)
                ->select();
            $total          = Db::name("user")->where($map)->count();
            $user_money_sum = Db::name("user")->where($map)->sum('user_money');
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total, 'user_money_sum' => $user_money_sum]);
        } else {
            return view();
        }
    }

    public function useradd()
    {
        $user_id    = input("user_id", 0);
        $infoColumn = gettableColumn('user');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        $rankList = Db::name("user_rank")->select();
        $this->assign('rankList', json_encode($rankList, 320));
        if (Request::isGet()) {
            if ($user_id > 0) {
                $info = Db::name("user")->where("user_id={$user_id}")->find();
            } else {
                $info = gettableColumn('user');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();

            if ($pageparm['user_id'] > 0) {
                $user = Db::name('user')->where('user_id', $pageparm['user_id'])->find();
                if (!$user) ajaxmsg('用户不存在，无法编辑资料', 0);
                //操作金额
                if (isset($pageparm['change_amount'])) {
                    $pageparm['change_amount'] = floatval($pageparm['change_amount']);
                    $desc                      = '系统充值';
                    if ($pageparm['change_amount'] < 0) {
                        if (abs($pageparm['change_amount']) > abs(floatval($pageparm['user_money']))) ajaxmsg('扣款超过余额,用户余额不能为负数 !!', 0);
                        $desc = '系统扣除';
                    }
                    $check = (new AccountLog)->accountLog($pageparm['user_id'], $pageparm['change_amount'], $desc);
                    if (!$check) {
                        ajaxmsg($check, 0);
                    }
                }

                unset($pageparm['user_money'], $pageparm['change_amount']);
                $res = Db::name('user')->update($pageparm);
                $res = $res || $check;
            } else {
                $pageparm['reg_time'] = time();
                $pageparm['password'] = password_hash(trim($pageparm['password']), PASSWORD_BCRYPT);
                $res                  = Db::name('user')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function userupdatePwd()
    {
        $user             = $this->getPageparm();
        $user['password'] = password_hash(trim($user["password"]), PASSWORD_BCRYPT);
        $res              = Db::name('user')->strict(false)->update($user);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function userrank()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['rank_name', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("user_rank")
                ->where($map)
                ->order("rank_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("user_rank")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function rankadd()
    {
        $rank_id    = input("rank_id", 0);
        $infoColumn = gettableColumn('user_rank');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($rank_id > 0) {
                $info = Db::name("user_rank")->where("rank_id={$rank_id}")->find();
            } else {
                $info = gettableColumn('user_rank');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['rank_id'] > 0) {
                $res = Db::name('user_rank')->update($pageparm);
            } else {
                $res = Db::name('user_rank')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function addresslist()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.consignee', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("user_address")->alias("a")
                ->join("user b", 'a.user_id=b.user_id', "LEFT")
                ->field("a.*,b.user_name")
                ->where($map)
                ->order("a.address_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("user_address")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function addressadd()
    {
        $address_id = input("address_id", 0);
        $infoColumn = gettableColumn('user_address');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($address_id > 0) {
                $info = Db::name("user_address")->where("address_id={$address_id}")->find();
            } else {
                $info = gettableColumn('user_address');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['address_id'] > 0) {
                $res = Db::name('user_address')->update($pageparm);
            } else {
                $res = Db::name('user_address')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function feedback()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("feedback")
                ->where($map)
                ->order("id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("feedback")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function recharge()
    {
        if ($this->request->isPost()) {
            $page       = input('page/d', 1);
            $order_type = input('order_type/d', 0);
            $status     = input('status');
            $download   = input('download/d', 0);
            $pageSize   = input('rows/d', 10);
            $keywords   = input('keywords');
            $start_time = input('start_time');
            $end_time   = input('end_time');

            $map = [];
            if (is_numeric($status)) {
                $map[] = ['a.status', '=', $status];
            }
            if ($keywords) {
                $map[] = ['b.user_name|b.nick_name|b.mobile', 'LIKE', '%' . $keywords . '%'];
            }
            if ($order_type > 0) {
                $map[] = ['a.order_type', '=', $order_type];
            }

            if ($end_time) {
                $end_time = strtotime($end_time);
                if ($end_time === false) ajaxmsg('查询结束时间错误', 0);
                $map[] = ['a.create_time', '<=', $end_time];
            }
            if ($start_time) {
                $start_time = strtotime($start_time);
                if ($start_time === false) ajaxmsg('查询开始时间错误', 0);
                $map[] = ['a.create_time', '>', $start_time];
            }

            $model = new UserRecharge;
            $total = $model->alias('a')->join('user b', 'a.user_id=b.user_id')->where($map)->count();
            $list  = $model->alias('a')->join('user b', 'a.user_id=b.user_id')
                ->where($map)
                ->field('a.*,b.user_name,b.nick_name,b.mobile')
                ->page($page, $pageSize)
                ->select();
            if ($download) {
                $list       = $model->alias('a')->join('user b', 'a.user_id=b.user_id')
                    ->where($map)
                    ->field('a.*,b.user_name,b.nick_name,b.mobile')
                    ->select()->toArray();
                $title      = ['ID', '用户ID', '备注', '充值编号', '交易编号', '支付方式', '金额', '充值时间', '支付时间', '付款状态', '用户账号', '用户昵称', '用户手机号'];
                $order_type = ['未知', '支付宝', '微信', '微信小程序'];
                $status     = ['未付款', '支付成功'];
                foreach ($list as $index => $item) {
                    $list[$index]['order_type']  = $order_type[$item['order_type']];
                    $list[$index]['status']      = $status[$item['status']];
                    $list[$index]['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    $list[$index]['update_time'] = $item['update_time'] > 0 ? date('Y-m-d H:i:s', $item['update_time']) : '';
                }
                toExcel($title, $list, '充值记录' . date('Y-m-d_H_s_i'));
                exit;
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        }
        return view();
    }

    public function account_log()
    {
        if ($this->request->isPost()) {
            $page       = input('page/d', 1);
            $order_type = input('order_type/d', 0);
            $download   = input('download/d', 0);
            $pageSize   = input('rows/d', 10);
            $keywords   = input('keywords');
            $start_time = input('start_time');
            $end_time   = input('end_time');

            $map = [];

            if ($keywords) {
                $map[] = ['b.user_name|b.nick_name|b.mobile', 'LIKE', '%' . $keywords . '%'];
            }
            if ($order_type == 1) {
                $map[] = ['a.user_money', '>', 0];
            } else if ($order_type == 2) {
                $map[] = ['a.user_money', '<', 0];
            }

            if ($end_time) {
                $end_time = strtotime($end_time);
                if ($end_time === false) ajaxmsg('查询结束时间错误', 0);
                $map[] = ['a.change_time', '<=', $end_time];
            }
            if ($start_time) {
                $start_time = strtotime($start_time);
                if ($start_time === false) ajaxmsg('查询开始时间错误', 0);
                $map[] = ['a.change_time', '>', $start_time];
            }

            $model = new AccountLog;
            $total = $model->alias('a')->join('user b', 'a.user_id=b.user_id')->where($map)->count();
            $list  = $model->alias('a')->join('user b', 'a.user_id=b.user_id')
                ->where($map)
                ->field('a.log_id,a.user_money,a.desc,a.user_id,a.change_time,a.order_sn,b.user_name,b.nick_name,b.mobile')
                ->page($page, $pageSize)
                ->select();
            if ($download) {
                $list  = $model->alias('a')->join('user b', 'a.user_id=b.user_id')
                    ->where($map)
                    ->field('a.log_id,a.user_money,a.desc,a.user_id,a.change_time,a.order_sn,b.user_name,b.nick_name,b.mobile')
                    ->select()->toArray();
                $title = ['ID', '变动金额', '描述', '用户ID', '时间', '关联订单编号', '用户名', '用户昵称', '用户手机号'];
                foreach ($list as $index => $item) {
                    $list[$index]['change_time'] = date('Y-m-d H:i:s', $item['change_time']);
                }
                toExcel($title, $list, '用户余额日志' . date('Y-m-d_H_s_i'));
                exit;
            }
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        }
        return view();
    }
}
