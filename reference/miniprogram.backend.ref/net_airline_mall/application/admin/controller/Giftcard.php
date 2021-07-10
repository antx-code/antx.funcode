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

class Giftcard extends Common
{
    public function createpwd($length = 32, $char = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ')
    {
        if (!is_int($length) || $length < 0) {
            return false;
        }

        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }

        return $string;
    }

    public function index()
    {
        if (Request::isAjax()) {
            $map      = [];
            $bind_time = trim(input("bind_time"));
            if ($bind_time == 0) {
                $map[] = ['a.bind_time', "=", 0];
            }
            if ($bind_time == 1) {
                $map[] = ['a.bind_time', ">", 0];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("giftcard")->alias("a")
                ->leftJoin("user b","a.user_id=b.user_id")
                ->where($map)
                ->field("a.*,b.user_name,b.mobile")
                ->order("a.id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("giftcard")->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    #导出数据
    public function giftcardImport(){
        $map      = [];
        $bind_time = input("bind_time",10000);
        if ($bind_time == 0) {
            $map[] = ['a.user_id', "=", 0];
        }
        if ($bind_time == 1) {
            $map[] = ['a.user_id', ">", 0];
        }
        $list  = Db::name("giftcard")->alias("a")
            ->leftJoin("user b","a.user_id=b.user_id")
            ->where($map)
            ->field("a.price,a.cash,a.card_no,a.password,b.user_name,a.type,
            FROM_UNIXTIME(a.over_time,'%Y-%m-%d %H:%i:%S') as over_time_cn,
            FROM_UNIXTIME(a.bind_time,'%Y-%m-%d %H:%i:%S') as bind_time_cn")
            ->order("a.id desc")
            ->select();
        $title = ['面值', '余额', '卡号', '密码','绑定用户','来源（1：自动发放，2：自助购买，3：赠送）','截止有效期','绑定时间'];
        toExcel($title, $list, '礼品卡列表' . date('Y-m-d'));
    }

    public function add()
    {
        if (Request::isAjax()) {
            $price     = input("price/f", 1);
            $num       = input("num/d", 1);
            $time      = time();
            $over_time = $time + 86400 * 365;
            if ($price < 0 || $num < 0) {
                ajaxmsg("数据非法", 0);
            }
            for ($i = 0; $i < $num; $i++) {
                $giftcardData[] = [
                    'price'       => $price,
                    'cash'        => $price,
                    'card_no'     => 'ENEK' . mt_rand(100000000000, 999999999999),
                    'password'    => $this->createpwd(16),
                    'start_time'  => $time,
                    'over_time'   => $over_time,
                    'create_time' => $time,
                ];
            }
            $res = Db::name('giftcard')->data($giftcardData)->limit(100)->insertAll();
            if ($res > 0) {
                ajaxmsg("批量生成成功", 1);
            } else {
                ajaxmsg("批量生成失败，联系管理员！", 0);
            }

        } else {
            return view();
        }
    }

    public function giftlog()
    {
        $id = input("id");
        if (Request::isAjax()) {
            $data = Db::name("giftcard_log")->alias("a")
                ->leftJoin("order b", "a.order_id=b.order_id")
                ->field("b.*,a.*")
                ->where("a.gid", "=", $id)
                ->select();
            ajaxmsg("true", 1, $data);
        } else {
            $this->assign("id", $id);
            return view();
        }
    }
}
