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

class Article extends Common
{
    #栏目分类
    public function articlecat()
    {
        if (Request::isAjax()) {
            $map = array();
            $keywords = trim(input("keywords"));
            $cat_id = input("cat_id");
            if ($keywords) {
                $map[] = ['brand_name', "like", "%$keywords%"];
            }
            if ($cat_id > 0) {
                $map[] = ['parent_id', 'eq', $cat_id];
            }
            $page = input('page', 1);
            $rows = input('rows', 10);
            $list = Db::name("article_cat")->where($map)->page($page, $rows)->select();
            $total = Db::name("article_cat")->where($map)->count();
            ajaxmsg('true', 1, array('list' => $list, 'total' => $total));
        } else {
            $this->assign('uniqid', uniqid());
            return view();
        }
    }

    public function articlecatadd()
    {
        $cat_id = input("cat_id", 0);
        $parent_id = input("parent_id", 0);
        $infoColumn = gettableColumn('article_cat');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($cat_id > 0) {
                $info = Db::name("article_cat")->where("cat_id={$cat_id}")->find();
                $category_name = Db::name('article_cat')->where("cat_id={$info['parent_id']}")->value('cat_name');
            } else {
                $info = gettableColumn('article_cat');
                $category_name = '';
                if ($parent_id > 0) {
                    $category_name = Db::name('article_cat')->where("cat_id={$parent_id}")->value('cat_name');
                    $info['parent_id'] = $parent_id;
                }
            }
            $this->assign('category_name', $category_name);
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            if ($pageparm['cat_id'] > 0) {
                $res = Db::name('article_cat')->update($pageparm);
            } else {
                $res = Db::name('article_cat')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }
    public function catListInitAjax()
    {
        $map = array();
        $parent_id = input("parent_id");
        if ($parent_id > 0) {
            $map[] = ['parent_id', 'eq', $parent_id];
        } else {
            $map[] = ['parent_id', 'eq', 0];
        }
        $list = Db::name("article_cat")->where($map)->select();
        ajaxmsg('true', 1, $list);
    }


    #文章列表
    public function article()
    {
        if (Request::isAjax()) {
            $map = array();
            $keywords = trim(input("keywords"));
            $cat_id = input("cat_id");
            if ($keywords) {
                $map[] = ['a.title', "like", "%$keywords%"];
            }
            if ($cat_id > 0) {
                $map[] = ['a.cat_id', 'eq', $cat_id];
            }
            $page = input('page', 1);
            $rows = input('rows', 10);
            $list = Db::name("article")->alias("a")
                ->join('article_cat b', 'a.cat_id=b.cat_id', 'LEFT')
                ->field("a.*,b.cat_name,FROM_UNIXTIME(a.add_time,'%Y-%m-%d %H:%i:%S') as add_time_cn")
                ->where($map)
                ->order("a.article_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("article")->alias("a")->where($map)->count();
            ajaxmsg('true', 1, array('list' => $list, 'total' => $total));
        } else {
            return view();
        }
    }
    public function articleadd(){
        $article_id = input("article_id", 0);
        $infoColumn = gettableColumn('article');
        $this->assign('infoColumn', json_encode($infoColumn));
        if (Request::isGet()) {
            if ($article_id > 0) {
                $info = Db::name("article")->where("article_id={$article_id}")->find();
                $content = $info['content'];
                unset($info['content']);
                $category_name = Db::name('article_cat')->where("cat_id={$info['cat_id']}")->value('cat_name');
            } else {
                $info = gettableColumn('article');
                $category_name = '';
                $content = '';
            }
            $this->assign('content', preg_replace('#\r|\n|\t#','',$content));
            $this->assign('category_name', $category_name);
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm = $this->getPageparm();
            $pageparm['create_time'] = time();
            if ($pageparm['article_id'] > 0) {
                $res = Db::name('article')->update($pageparm);
            } else {
                $res = Db::name('article')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }


    public function doallupdate()
    {
        $map = array();
        $article_ids = input('article_ids');
        $is_delete = input('is_delete');
        $is_open = input('is_open');
        $map[] = ['article_id', 'in', $article_ids];
        if ($is_delete > 0) {
            $res = Db::name('article')->where($map)->delete();
        } else {
            $res = Db::name('article')->where($map)->update(array(
                'is_open' => $is_open,
            ));
        }
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

}
