<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://heimishop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Config;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\Response;
use think\response\Redirect;
use think\Url;
use think\View as ViewTemplate;
use think\facade\Env;


class Common extends Controller
{
    public $id;
    public $pageparm;
    public $page = 1;
    public $rows = 10;

    public function initialize()
    {
        $this->page = input('page', 1);
        $this->rows = input('rows', 10);
        if (!session('admin_user')) {
            $this->redirect('admin/login/login');
            return false;
        } else {
            $admin_user = session('admin_user');
            $this->assign("admin_user", $admin_user);
        }

        $current_url = strtolower('/' . Request::module() . '/' . Request::controller() . '/' . Request::action());//强制小写

        #超级管理员
        if ($admin_user['role_id'] == 1 || strtolower(Request::controller()) == 'index' || strtolower(Request::controller()) == 'common') {
            //todo
        } else {
            $this->checkMenuAuth($current_url);
        }
    }

    #检查权限-控制菜单权限
    public function checkMenuAuth($current_url)
    {
        $admin_user = session('admin_user');
        $admin_role = Db::name("admin_role")->where("role_id={$admin_user['role_id']}")->value('action_list');
        $res        = Db::name("admin_menu")->whereIn('action_id', $admin_role)->whereRaw('LOWER(action_url)="' . $current_url . '"')->count();
        if (!$res) {
            if (Request::isAjax()) {
                ajaxmsg('无权限', 0);
            } else {
                $this->redirect('/admin/index/noauth');
            }
        }
    }

    public function getPageparm()
    {
        $pageparm = json_decode($_REQUEST['pageparm'], true);
        foreach ($pageparm as $k => $v) {
            if (is_array($v)) {
                $pageparm[$k] = json_encode($v, 320);
            }
        }
        return $pageparm;
    }

    public function goodstype_ajax()
    {
        $list = Db::name("goods_type")->where('enabled=1')->select();
        ajaxmsg('true', 1, $list);
    }

    public function attribute_ajax()
    {
        $cat_id = input("cat_id");
        $list   = Db::name("attribute")->where("cat_id={$cat_id}")->select();
        ajaxmsg('true', 1, $list);
    }

    #清除缓存
    public function deleleteDirectory($dirName)
    {
        if (!file_exists($dirName)) {
            return false;
        }
        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file)) {
                    $this->deleleteDirectory($file);
                } else {
                    unlink($file);
                }
            }
        }
        closedir($dir);
        return rmdir($dirName);
    }

    #单表插入
    public function doadd()
    {
        $pageparm = $this->getPageparm();
        $table    = trim(input('table'));
        $res      = Db::name($table)->strict(false)->insertGetId($pageparm);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #更新单表
    public function doupdate()
    {
        $pageparm = $this->getPageparm();
        $table    = trim(input('table'));
        $res      = Db::name($table)->strict(false)->update($pageparm);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #单表删除
    public function dodelete()
    {
        $pageparm = $this->getPageparm();
        $table    = trim(input('table'));
        $pk       = Db::name($table)->getPk();#获取主键
        $res      = Db::name($table)->where("$pk={$pageparm[$pk]}")->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #删除单表多条数据
    public function doalldelete()
    {
        $map   = [];
        $ids   = input('ids');
        $table = trim(input('table'));
        $pk    = Db::name($table)->getPk();#获取主键
        $map[] = [$pk, 'in', $ids];
        $res   = Db::name($table)->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #获取表字段注释
    public function get_table_columns($table)
    {
        $list  = Db::query('SHOW FULL COLUMNS FROM ' . config('database.prefix') . $table);
        $title = [];
        if ($list) {
            foreach ($list as $k => $row) {
                $title[] = $row['Comment'];
            }
        }
        return $title;
    }

    #获取后台登录管理员基本信息-及时同步
    public function get_admin_user()
    {
        $admin_user = session('admin_user');
        $admin_user = Db::name("admin_user")->where("id={$admin_user['id']}")->find();
        return $admin_user;
    }

    #区域三级联动
    public function getregionList()
    {
        $level     = input('level', 0);
        $parent_id = input('parent_id', 0);
        $data      = [];
        switch ($level) {
            case 0:
                $data['oneList'] = Db::name("region")->where("region_type=1 and parent_id=1")->select();
                $data['twoList'] = [];
                $data['thrList'] = [];
                break;
            case 1:
                $data['twoList'] = Db::name("region")->where("parent_id={$parent_id}")->select();
                $data['thrList'] = [];
                break;
            case 2:
                $data['thrList'] = Db::name("region")->where("parent_id={$parent_id}")->select();
                break;
        }

        ajaxmsg('true', 1, $data);
    }

}
