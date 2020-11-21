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
use Firebase\JWT\JWT;
use GuzzleHttp\Client;

class system extends Common
{

    #管理员操作
    public function userslist()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['username', "like", "%$keywords%"];
            }
            $map[] = ['id', 'gt', 1];
            $page  = input("request.page", 1);
            $rows  = input("request.rows", 10);
            $list  = Db::name("admin_user")
                ->where($map)
                ->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("admin_user")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('uniqid', uniqid());
            return view();
        }
    }

    public function useradd()
    {
        $id       = input('id/d', 0);
        $rolelist = Db::name("admin_role")->field('role_id,role_name')->select();
        $this->assign('rolelist', json_encode($rolelist));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("admin_user")->where("id={$id}")->find();
            } else {
                $info = gettableColumn('admin_user');
            }
            $info['password'] = '';
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm                  = $this->getPageparm();
            $pageparm['lastlogintime'] = date("Y-m-d H:i:s");
            $pageparm['create_time']   = date("Y-m-d H:i:s");
            $pageparm['update_time']   = date("Y-m-d H:i:s");
            unset($pageparm['lastlogintime']);
            unset($pageparm['password']);

            if ($pageparm['id'] > 0) {
                $pageparm['create_time'] = date('Y-m-d H:i:s');
                $res                     = Db::name('admin_user')->update($pageparm);
            } else {
                $pageparm['update_time'] = date('Y-m-d H:i:s');
                $res                     = Db::name('admin_user')->insertGetId($pageparm);
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
        if ($this->request->isPost()) {
            //$admin_user = $this->getPageparm();
            //$admin_user['password'] = password_hash(trim($admin_user['password']), PASSWORD_BCRYPT);
            //$res                    = Db::name('admin_user')->strict(false)->update($admin_user);

            $data             = input('post.');
            $data['username'] = session('admin_user.username');
            try {
                $jwt    = JWT::encode([
                    'exp'  => time() + 10,
                    'data' => $data
                ], config('auth_key'));
                $client = new Client(['verify' => false]);
                $res    = json_decode($client->post(config('ucenter') . 'index/password', ['form_params' => ['jwt' => $jwt]])->getBody()->getContents(), true);

                if (isset($res['ret']) && $res['ret'] == 0) {
                    ajaxmsg('操作成功', 1);
                } else {
                    ajaxmsg($res['msg'], 0);
                }
            } catch (\Exception $e) {
                ajaxmsg($e->getMessage(), 0);
            }
        }
        return view();
    }

    #角色操作
    public function rolelist()
    {
        if (Request::isAjax()) {
            //超级管理员role_id固定为1,不允许编辑
            $map      = [
                ['role_id', '>', 1]
            ];
            $keywords = input("keywords");
            if ($keywords) {
                $map[] = ['role_name', "like", "%$keywords%"];
            }
            $page  = input("request.page", 1);
            $rows  = input("request.rows", 10);
            $list  = Db::name("admin_role")->where($map)->page($page, $rows)->select();
            $total = Db::name("admin_role")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        }

        return view();
    }

    public function roleadd()
    {
        $role_id    = input("role_id", 0);
        $infoColumn = gettableColumn('admin_role');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($role_id > 0) {
                $info = Db::name("admin_role")->where("role_id={$role_id}")->find();
            } else {
                $info = gettableColumn('admin_role');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['role_id'] > 1) {
                //超级管理员role_id固定为1,不允许修改
                $res = Db::name('admin_role')->update($pageparm);
            } else {
                $res = Db::name('admin_role')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function menulistAjax()
    {
        $role_id = input('role_id', 0);
        #菜单暂定两级
        $data      = [];
        $list      = Db::name("admin_menu")->where("parent_id=0")->order("action_id")->select();
        $childlist = Db::name("admin_menu")->where("0<parent_id")->order("parent_id")->select();

        if ($role_id > 0) {
            $action_list = Db::name("admin_role")->where("role_id={$role_id}")->value("action_list");
        } else {
            $action_list = '';
        }
        foreach ($list as $k => $v) {
            if (strpos($action_list, (string)$v['action_id'])) {
                $list[$k]['check'] = true;
            }
        }
        foreach ($childlist as $k => $v) {
            if (strpos($action_list, (string)$v['action_id'])) {
                $childlist[$k]['check'] = true;
            }
        }
        $data['list']      = $list;
        $data['childlist'] = $childlist;
        ajaxmsg('true', 1, $data);

    }


    #菜单管理
    public function menulist()
    {
        if (Request::isAjax()) {
            $map       = [];
            $keywords  = input("keywords");
            $action_id = input("action_id", 0);
            if ($keywords) {
                $map[] = ['action_name', "like", "%$keywords%"];
            }
            if ($action_id > 0) {
                $map[] = ['parent_id', 'eq', $action_id];
            } else {
                $map[] = ['parent_id', 'eq', 0];
            }
            $page  = input("request.page", 1);
            $rows  = input("request.rows", 10);
            $list  = Db::name("admin_menu")->where($map)->page($page, $rows)->select();
            $total = Db::name("admin_menu")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            $this->assign('uniqid', uniqid());
            return view();
        }
    }

    public function menuadd()
    {
        $action_id  = input("action_id", 0);
        $infoColumn = gettableColumn('admin_menu');
        $parent_id  = input("parent_id", 0);
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($action_id > 0) {
                $info          = Db::name("admin_menu")->where("action_id={$action_id}")->find();
                $category_name = Db::name('admin_menu')->where("action_id={$info['parent_id']}")->value('action_name');
            } else {
                $info          = gettableColumn('admin_menu');
                $category_name = '';
                if ($parent_id > 0) {
                    $category_name     = Db::name('admin_menu')->where("action_id={$parent_id}")->value('action_name');
                    $info['parent_id'] = $parent_id;
                }
            }

            $this->assign('category_name', $category_name);
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['action_id'] > 0) {
                if ($pageparm['parent_id'] == $pageparm['action_id']) {
                    ajaxmsg('上级分类不能为自已');
                }
                $parent = Db::name('admin_menu')->where('action_id', $pageparm['parent_id'])->value('parent_id');
                if ($parent > 0) {
                    ajaxmsg('上级分类仅能选择顶级分类');
                }
                $res = Db::name('admin_menu')->update($pageparm);
            } else {
                $res = Db::name('admin_menu')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function menuListInitAjax()
    {
        $list = Db::name("admin_menu")->where('parent_id', 0)->select();
        ajaxmsg('true', 1, $list);
    }


    #管理员日志表
    public function adminlog()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = input("keywords");
            if ($keywords) {
                $map[] = ['b.username', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("admin_log")->alias("a")
                ->join('admin_user b', 'a.user_id=b.id', 'LEFT')
                ->field("a.*,b.username,FROM_UNIXTIME(a.log_time,'%Y-%m-%d %H:%i:%S') as log_time_cn")
                ->where($map)
                ->order("a.log_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("admin_log")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    #管理员推送留言
    public function msglist()
    {
        if (Request::isAjax()) {
            $map     = [];
            $is_read = input("is_read", 0);
            switch ($is_read) {
                case 3:
                    $map[] = ['is_read', 'eq', 0];
                    break;
                case 4:
                    $map[] = ['is_read', 'eq', 1];
                    break;
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("admin_msg")
                ->field("*,FROM_UNIXTIME(send_time,'%Y-%m-%d %H:%i:%S') as send_time_cn,
                FROM_UNIXTIME(read_time,'%Y-%m-%d %H:%i:%S') as read_time_cn")
                ->where($map)
                ->page($page, $rows)
                ->order("id desc")
                ->select();
            $total = Db::name("admin_msg")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    #数据表优化
    #SHOW TABLE STATUS LIKE 'oc\_%'
    public function optimize()
    {
        if (Request::isAjax()) {
            $prefix = Config::get('database.prefix');
            $sql    = "SHOW TABLE STATUS LIKE 'oc\_%'";
            $list   = Db::query($sql);
            ajaxmsg('true', 1, ['list' => $list, 'total' => count($list)]);
        } else {
            return view();
        }
    }

    public function repairTable()
    {
        $prefix = Config::get('database.prefix');
        $sql    = "SHOW TABLE STATUS LIKE 'oc\_%'";
        $list   = Db::query($sql);

        $sql = "REPAIR TABLE ";
        $tbl = "";
        foreach ($list as $k => $v) {
            $tbl = $tbl . ',' . $v['Name'];
        }
        $str      = $sql . trim($tbl, ',');
        $OPTIMIZE = "OPTIMIZE TABLE ";
        Db::query($str);
        Db::query($OPTIMIZE . trim($tbl, ','));
        ajaxmsg('优化完毕', 1);
    }

}
