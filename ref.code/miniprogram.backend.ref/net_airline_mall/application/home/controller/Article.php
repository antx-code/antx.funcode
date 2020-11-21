<?php

/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/1 11:19
 */

namespace app\home\controller;

use think\Db;
use think\facade\Request;
use think\Config;

class Article extends Common
{
    public function index()
    {
        $cat_id = input("cat_id", 0);
        if ($cat_id > 0) {
            $info                = Db::name('article_cat')
                ->where("cat_id={$cat_id}")
                ->find();
            $info['articleList'] = Db::name("article")
                ->where("cat_id = {$cat_id}")
                ->order("sort_order desc")
                ->select();
        } else {
            $info = [];
        }
        $keywords = input('keywords');
        $map[]    = ['title', 'like', "%$keywords%"];
        if ($keywords) {
            $info['cat_id']      = 0;
            $info['articleList'] = Db::name("article")
                ->where($map)
                ->order("sort_order desc")
                ->select();
            $info['cat_name']    = '搜索结果';
        }

        $this->assign('list', $this->buildTree());
        $this->assign('info', $info);
        return view();
    }

    public function content()
    {
        $article_id = input("article_id", 0);
        $info       = Db::name("article")->where("article_id={$article_id}")->find();

        $this->assign('list', $this->buildTree());
        $this->assign('info', $info);
        return view();
    }

    protected function buildTree()
    {
        $list = Db::name("article_cat")
            ->where("parent_id!=0 AND show_in_nav=1")
            ->order("cat_id asc")
            ->select();

        if ($list) {
            $article_list = Db::name("article")
                ->whereIn('cat_id', array_column($list, 'cat_id'))
                ->where('is_open=1')
                ->order("cat_id asc")
                ->select();
            foreach ($list as $index => $item) {
                $ids      = array_column($article_list, 'cat_id');
                $valCount = array_count_values($ids);
                if (isset($valCount[$item['cat_id']])) {
                    $list[$index]['childrenList'] = array_slice($article_list, array_search($item['cat_id'], $ids), $valCount[$item['cat_id']]);
                } else {
                    $list[$index]['childrenList'] = [];
                }
            }
        }
        return $list;
    }
}
