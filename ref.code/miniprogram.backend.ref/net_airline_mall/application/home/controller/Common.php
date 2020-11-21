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
 * @controller: 商城模块父类控制器
 */

namespace app\home\controller;

use think\facade\Cache;
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
use think\captcha\Captcha;

class Common extends Controller
{
    public $id;
    public $pageparm;

    public function initialize()
    {
        if ($this->request->isMobile() && $this->request->action() != 'qrcode_jump') {
            $this->redirect('/wap/index');
            return;
        }
        $shop_config = Db::name("shop_config")->cache(true)->column('value', 'code');
        $this->assign('shop_config', $shop_config);
        $beforeUrl = $_SERVER['HTTP_REFERER'] ?? '';
        $this->assign('beforeUrl', $beforeUrl);
        $this->assign('currenturl', Request::url());
        //初始化模板路径
        $this->initTheme();

        if (!$this->request->isAjax()) {
            $this->initSite();
        }
    }

    protected function initTheme()
    {
        $theme = cache('hometheme');
        if (!$theme || config('app_debug') === true) {
            $theme = Db::name('shop_config')->where('code', 'hometheme')->value('value');
            cache('hometheme', $theme);
        }
        $hometheme['tpl_replace_string']['__STATIC__'] = '/public/static';
        $hometheme['tpl_replace_string']['__THEME__']  = $hometheme['tpl_replace_string']['__STATIC__'] . '/' . $theme;
        $hometheme['view_path']                        = $this->request->module() . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;
        $this->view->config('view_path', Env::get('app_path') . $hometheme['view_path']);
        $this->view->config('tpl_replace_string', $hometheme['tpl_replace_string']);

    }

    protected function initSite()
    {
        //网站公共信息
        $siteInfo = cache('siteInfo');
        if (!$siteInfo || config('app_debug') === true) {
            $siteInfo                    = Db::name('shop_config')->where('shop_group', 'site_config')->column('value', 'code');
            $siteInfo['search_keywords'] = explode(',', $siteInfo['search_keywords']);
            $siteInfo['nav']             = Db::name('website_nav')->where('is_show=1 AND `type`=1')->order('sort DESC')->select();
            $article_cat                 = Db::name('article_cat')->where('parent_id', 4)->order('sort_order')->limit(5)->column('cat_name,sort_order', 'cat_id');
            if ($article_cat) {
                $cat_ids = array_keys($article_cat);
                $article = Db::name('article')->whereIn('cat_id', $cat_ids)->where('is_open=1')->orderRaw('field(cat_id,' . implode(',', $cat_ids) . '),sort_order ASC')->field('article_id,title,cat_id,link_url')->select();
                if ($article) {
                    $cat_id_arr = array_column($article, 'cat_id');
                    $id_count   = array_count_values($cat_id_arr);
                    foreach ($article_cat as $index => $item) {
                        if (isset($id_count[$index]))
                            $article_cat[$index]['list'] = array_slice($article, array_search($index, $cat_id_arr), $id_count[$index]);
                        else $article_cat[$index]['list'] = [];
                    }
                }
            }
            $siteInfo['footer'] = $article_cat;
            cache('siteInfo', $siteInfo);
        }

        $this->assign('category', categoryListAjax(0, 7));
        $this->assign('siteInfo', $siteInfo);

        $user = session('user');
        if ($user) {
            $user = Db::name("user")->where("user_id={$user['user_id']}")->find();
        }
        $this->assign('user', $user);
    }

    public function getPageparm()
    {
        $pageparm = json_decode($_REQUEST['pageparm'], true);
        foreach ($pageparm as $k => $v) {
            if (is_array($v)) {
                $pageparm[$k] = json_encode($v);
            }
        }
        return $pageparm;
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
        $user     = getUserInfo();
        $map      = [];
        $map[]    = ['user_id', '=', $user['user_id']];
        $pageparm = $this->getPageparm();
        $table    = trim(input('table'));
        $pk       = Db::name($table)->getPk();#获取主键
        $res      = Db::name($table)->where("$pk={$pageparm[$pk]}")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #删除单表多条数据
    public function doalldelete()
    {
        $user  = getUserInfo();
        $map   = [];
        $map[] = ['user_id', '=', $user['user_id']];
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

    public function categoryListAjax()
    {
        ajaxmsg('true', 1, categoryListAjax());
    }

    #验证码
    public function verify($fontSize = 18, $length = 2, $imageW = 112, $imageH = 37)
    {
        $config  = [
            'fontSize' => $fontSize,
            'length'   => $length,
            'imageW'   => $imageW,
            'imageH'   => $imageH,
            'useCurve' => false,
            'useNoise' => false,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    public function _empty()
    {
        if ($this->request->isAjax()) {
            return json('您请求的资源不存在~', 404);
        } else {
            $this->error('您要查看的内容可能已经被更新了');
        }
    }
}
