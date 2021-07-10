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
use app\admin\model\Tbo;


class Store extends Common
{

    public function index()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['store_name', "like", "%$keywords%"];
            }
            $page     = input('page', 1);
            $rows     = input('rows', 10);
            $list     = Db::name("store")
                ->where($map)
                ->field("*,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time_cn")
                ->order("store_id desc")
                ->page($page, $rows)
                ->select();
            $total    = Db::name("store")->where($map)->count();
            $merchant = Db::name("merchant_apply")->where(['status' => [0, 1]])->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total, 'merchant' => $merchant]);
        } else {
            return view();
        }
    }

    public function store_add()
    {
        $store_id   = input("store_id", 0);
        $infoColumn = gettableColumn('store');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isAjax()) {
            $pageparm                = $this->getPageparm();
            $pageparm['create_time'] = time();
            if ($pageparm['store_id'] > 0) {
                $res = Db::name('store')->strict(false)->update($pageparm);
            } else {

                $user                  = Db::name("user")->where("user_id={$pageparm['user_id']}")->find();
                $sellerData            = [
                    'username' => $user['user_name'],
                    'password' => $user['password'],
                    'user_id'  => $user['user_id'],
                    'enabled'  => 1,
                ];
                $seller_id             = Db::name("seller")->insertGetId($sellerData);
                $pageparm['seller_id'] = $seller_id;
                $store_id              = Db::name('store')->strict(false)->insertGetId($pageparm);
                $res                   = Db::name("seller")->where("id={$seller_id}")->update([
                    'store_id'   => $store_id,
                    'controller' => 'all',
                    'action'     => 'all',
                ]);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            if ($store_id > 0) {
                $info = Db::name("store")->where("store_id={$store_id}")->find();
            } else {
                $info = gettableColumn('store');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        }
    }

    public function getSellerList()
    {
        $list = getSellerListAjax();
        ajaxmsg('true', 1, $list);
    }

    #店铺申请
    public function storeapply()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['b.user_name|b.mobile', "like", "%$keywords%"];
            }
            $statusIDS = input("statusIDS");
            if ($statusIDS !== '') {
                $map[] = ['a.status', 'in', $statusIDS];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("merchant_apply")->alias("a")
                ->join("user b", "a.user_id=b.user_id", 'LEFT')
                ->where($map)
                ->field("a.*,FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as create_time_cn,b.user_name,b.mobile,b.email")
                ->order("a.apply_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("merchant_apply")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function applydetail()
    {

        $apply_id = input("apply_id", 0);
        if (Request::isPost()) {
            $merchants_status = input('merchants_status', 0);
            if ($merchants_status == 2) {
                $merchant_apply = Db::name("merchant_apply")->where('apply_id', $apply_id)->find();
                $user           = Db::name("user")->where('user_id', $merchant_apply['user_id'])->find();

                $seller = Db::name("seller")->where('user_id', $user['user_id'])->find();

                $storeData                   = [];
                $storeData['store_name']     = input('store_name', input('store_name'));
                $storeData['store_close']    = input('store_close', 0);
                $storeData['company']        = input('company', '');
                $storeData['store_subtitle'] = input('store_subtitle', '');

                #存在update
                if ($seller) {
                    $storeData              = [];
                    $storeData['seller_id'] = $seller['id'];
                } else {
                    $sellerData             = [
                        'username'   => $user['user_name'],
                        'password'   => $user['password'],
                        'user_id'    => $user['user_id'],
                        'controller' => 'all',
                        'action'     => 'all',
                        'is_admin'   => 1,
                        'enabled'    => 1,
                    ];
                    $seller_id              = Db::name("seller")->insertGetId($sellerData);
                    $storeData['seller_id'] = $seller_id;
                }
                $store_id = Db::name('store')->strict(false)->insertGetId($storeData);

                Db::name("seller")->where('id', $seller_id)->update(['store_id' => $store_id]);
                //绑定经营分类
                $apply_cat_id = Db::name('store_process_steps_content')->where('apply_id', $apply_id)->where('fields_name', 'shop_main_category')->value('value');
                Db::name('store_bind_category')->insert([
                    'store_id'    => $store_id,
                    'cat_id'      => $apply_cat_id,
                    'commis_rate' => 0,
                ]);
            }
            Db::name("merchant_apply")->where('apply_id', $apply_id)->update([
                'status' => $merchants_status
            ]);
            ajaxmsg('操作成功', 1);
        } else {
            $list           = Db::name("store_process_steps_content")->alias("a")
                ->join("store_process_steps b", "a.fields_name=b.fieldsName", "LEFT")
                ->field("a.*,b.fieldsName,b.fieldsDateType,b.fieldsFormTitle,b.fieldsFormSpecial")
                ->where('a.apply_id', $apply_id)
                ->group("a.id")
                ->select();
            $merchant_apply = Db::name("merchant_apply")->where('apply_id', $apply_id)->find();
            $this->assign('list', $list);
            $this->assign('apply_id', $apply_id);
            $this->assign('merchant_apply', $merchant_apply);
            return view();
        }
    }

    public function storeapplyDelete()
    {
        $apply_id = input("apply_id", 0);

        Db::name("merchant_apply")->delete($apply_id);
        $res = Db::name("store_process_steps_content")->where("apply_id={$apply_id}")->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }

    }


    #入驻流程
    public function store_process()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['process_title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_process")
                ->where($map)
                ->order("sort_order desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("store_process")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function store_process_add()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('store_process');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("store_process")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('store_process');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('store_process')->update($pageparm);
            } else {
                $res = Db::name('store_process')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function store_process_steps()
    {
        $process_id = input('process_id');
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['fieldsFormTitle', "like", "%$keywords%"];
            }
            $map[] = ['process_id', 'eq', $process_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_process_steps")
                ->where($map)
                ->order("sort_order desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("store_process_steps")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('process_id', $process_id);
            return view();
        }
    }

    public function store_process_steps_add()
    {
        $id         = input("id", 0);
        $process_id = input("process_id", 0);
        $infoColumn = gettableColumn('store_process_steps');
        $this->assign('infoColumn', json_encode($infoColumn));
        $this->assign('process_id', $process_id);
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("store_process_steps")->where("id={$id}")->find();
            } else {
                $info               = gettableColumn('store_process_steps');
                $info['process_id'] = $process_id;
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            array_walk($pageparm, function (&$item, $key) {
                $item = json_decode($item, true);
            });
            //dump($pageparm);exit;
            if ($pageparm[0]['id'] > 0) {
                $tbo = new Tbo;
                $res = count($tbo->saveAll($pageparm));
            } else {
                $res = Db::name('store_process_steps')->insertAll($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }


    public function store_grade()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['process_title', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_grade")
                ->where($map)
                ->page($page, $rows)
                ->select();
            $total = Db::name("store_grade")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function store_grade_add()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('store_grade');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("store_grade")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('store_grade');

            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['id'] > 0) {
                $res = Db::name('store_grade')->update($pageparm);
            } else {
                $res = Db::name('store_grade')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function seller()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['username', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("seller")
                ->where($map)
                ->field("*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%S') as add_time_cn")
                ->page($page, $rows)
                ->select();
            $total = Db::name("seller")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function userupdatePwd()
    {
        $seller             = $this->getPageparm();
        $seller['password'] = password_hash(trim($seller["password"]), PASSWORD_BCRYPT);
        $res                = Db::name('seller')->strict(false)->update($seller);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function seller_add()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('seller');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("seller")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('seller');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm             = $this->getPageparm();
            $pageparm['add_time'] = time();
            if ($pageparm['id'] > 0) {
                $res = Db::name('seller')->update($pageparm);
            } else {
                $pageparm['password'] = password_hash(trim($pageparm['password']), PASSWORD_BCRYPT);
                $res                  = Db::name('seller')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

}
