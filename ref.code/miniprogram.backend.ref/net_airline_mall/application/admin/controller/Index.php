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

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use think\facade\Config;
use think\Db;
use think\Env;
use think\exception\PDOException;
use think\facade\Request;
use think\Model;

class Index extends Common
{
    #无权限通用提示页面
    public function noauth()
    {
        return view();
    }

    public function tbdelete()
    {
        $this->id = $_REQUEST['id'];
        $this->db = $_REQUEST['db'];
        $this->admin_log_add($reson = '', $dietime = '', $this->db, $this->id, $content = '');
        $this->delete();
    }

    #整体框架
    public function index()
    {
        if (Request::isAjax()) {
            $data               = [];
            $data['admin_user'] = $this->get_admin_user();
            ajaxmsg('true', 1, $data);
        } else {
            $this->assign('statisticinfo', getSellDataByCommon());
            $this->assign('logo', Db::name('shop_config')->where('code', 'admin_logo')->cache(true)->value('value'));
            return view();
        }
    }

    public function requestAuth()
    {
        $data = [
            'exp'  => time() + 10,
            'data' => ['username' => session('admin_user.username')]
        ];
        try {
            $jwt   = JWT::encode($data, config('auth_key'));
            $clent = new Client(['verify' => false]);
            $res   = json_decode($clent->post(config('ucenter') . 'index/auth', ['form_params' => ['jwt' => $jwt]])->getBody()->getContents(), true);
            if ($res['ret'] != 0) {
                trace(var_export($res, true));
                throw new \Exception('');
            }
            return $res['data'];
        } catch (\Exception $e) {
            trace('获取权限列表失败');
            return [];
        }
    }

    public function makeJwt()
    {
        $data = [
            'exp'  => time() + 10,
            'data' => ['username' => session('admin_user.username')]
        ];
        $jwt  = JWT::encode($data, config('auth_key'));
        return json(['jwt' => $jwt]);
    }

    public function home()
    {
        if (Request::isAjax()) {

            $type      = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $date      = empty($_REQUEST['date']) ? '' : trim($_REQUEST['date']);
            $timezone  = 8;
            $time_diff = $timezone * 3600;
            $result    = ['error' => 0, 'message' => '', 'content' => ''];
            $data      = [];
            if ($date == 'week') {
                $day_num = 7;
            }

            if ($date == 'month') {
                $day_num = 30;
            }
            if ($date == 'year') {
                $day_num = 180;
            }

            $date_end   = local_mktime(0, 0, 0, local_date('m'), local_date('d') + 1, local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 * $day_num;
            $sql        = "SELECT
                DATE_FORMAT(
                    FROM_UNIXTIME(oi.add_time + {$time_diff}),
                    '%y-%m-%d'
                ) AS day,
                COUNT(*) AS count,
                SUM(oi.order_amount) AS money
                FROM
                    oc_order AS oi
                WHERE
		        oi.add_time BETWEEN {$date_start} AND {$date_end}";
            $result     = Db::query($sql);

            $orders_series_data = [];
            $sales_series_data  = [];
            foreach ($result as $k => $v) {
                $orders_series_data[$v['day']] = intval($v['count']);
                $sales_series_data[$v['day']]  = floatval($v['money']);
            }
            for ($i = 1; $i <= $day_num; $i++) {
                $day = local_date('y-m-d', local_strtotime(' - ' . ($day_num - $i) . ' days'));

                if (empty($orders_series_data[$day])) {
                    $orders_series_data[$day] = 0;
                    $sales_series_data[$day]  = 0;
                }

                $day                 = local_date('m-d', local_strtotime($day));
                $orders_xAxis_data[] = $day;
                $sales_xAxis_data[]  = $day;
            }

            $toolbox    = [
                'show'    => true,
                'orient'  => 'vertical',
                'x'       => 'right',
                'y'       => '60',
                'feature' => [
                    'magicType'   => [
                        'show' => true,
                        'type' => ['line', 'bar'],
                    ],
                    'saveAsImage' => ['show' => true],
                ],
            ];
            $tooltip    = [
                'trigger'     => 'axis',
                'axisPointer' => [
                    'lineStyle' => ['color' => '#6cbd40'],
                ],
            ];
            $xAxis      = [
                'type'        => 'category',
                'boundaryGap' => false,
                'axisLine'    => [
                    'lineStyle' => ['color' => '#ccc', 'width' => 0],
                ],
                'data'        => [],
            ];
            $yAxis      = [
                'type'      => 'value',
                'axisLine'  => [
                    'lineStyle' => ['color' => '#ccc', 'width' => 0],
                ],
                'axisLabel' => ['formatter' => ''],
            ];
            $series     = [
                [
                    'name'      => '',
                    'type'      => 'line',
                    'itemStyle' => [
                        'normal' => [
                            'color'     => '#6cbd40',
                            'lineStyle' => ['color' => '#6cbd40'],
                        ],
                    ],
                    'data'      => [],
                    'markPoint' => [
                        'itemStyle' => [
                            'normal' => ['color' => '#6cbd40'],
                        ],
                        'data'      => [
                            ['type' => 'max', 'name' => '最大值'],
                            ['type' => 'min', 'name' => '最小值'],
                        ],
                    ],
                ],
                [
                    'type'      => 'force',
                    'name'      => '',
                    'draggable' => false,
                    'nodes'     => ['draggable' => false],
                ],
            ];
            $calculable = true;
            $legend     = [
                'data' => [],
            ];

            if ($type == 'order') {
                $xAxis['data']      = $orders_xAxis_data;
                $yAxis['formatter'] = '个';
                ksort($orders_series_data);
                $series[0]['name'] = '订单个数';
                $series[0]['data'] = array_values($orders_series_data);
                $data['series']    = $series;
            }

            if ($type == 'sale') {
                $xAxis['data']      = $sales_xAxis_data;
                $yAxis['formatter'] = '{value}' . '元';
                ksort($sales_series_data);
                $series[0]['name'] = '销售额';
                $series[0]['data'] = array_values($sales_series_data);
                $data['series']    = $series;
            }

            $data['tooltip']    = $tooltip;
            $data['legend']     = $legend;
            $data['toolbox']    = $toolbox;
            $data['calculable'] = $calculable;
            $data['xAxis']      = $xAxis;
            $data['yAxis']      = $yAxis;
            #$data['xy_file'] = get_dir_file_list();
            exit(json_encode($data));
        } else {
            $set_statistical_chart = Config::get('set_statistical_chart');
            $info                  = getSellDataByCommon();
            $info['auth']          = $this->requestAuth();

            $this->assign('info', $info);
            return view();
        }
    }

    public function hometip()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['a.title', "like", "%$keywords%"];
            }
            $list = Db::name("article")->alias("a")
                ->join('article_cat b', 'a.cat_id=b.cat_id', 'LEFT')
                ->field("a.*,b.cat_name,FROM_UNIXTIME(a.add_time,'%Y-%m-%d %H:%i:%S') as add_time_cn")
                ->where($map)
                ->order("a.article_id desc")
                ->limit(10)
                ->select();
            ajaxmsg('true', 1, $list);
        } else {
            return view();
        }
    }

    #业务流程
    public function workflow()
    {
        return view();
    }

    #新手指导
    public function noviceguide()
    {
        return view();
    }

    #更新管理密码
    public function updatePwd()
    {


        $admin_user                  = session('admin_user');
        $admin_user['password']      = password_hash(trim(input("password")), PASSWORD_BCRYPT);
        $res                         = Db::name('admin_user')->strict(false)->update($admin_user);
        $admin_logData               = [];
        $admin_logData['user_id']    = $admin_user['id'];
        $admin_logData['log_time']   = time();
        $admin_logData['log_info']   = '更改密码操作';
        $admin_logData['ip_address'] = Request::ip();
        Db::name('admin_log')->insertGetId($admin_logData);

        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    #删除缓存
    public function removeRuntime()
    {
        $result = $this->deleleteDirectory(RUNTIME_PATH . '/cache');
        if ($result) {
            return ['status' => 0, 'msg' => '缓存清理成功'];
        } else {
            return ['status' => 1, 'msg' => '缓存清理失败'];
        }
    }

    #上传图片
    public function uploadimage()
    {
        $id     = input("id");
        $upload = input('upload');
        if ($upload) {
            set_time_limit(0);
            $file             = Request::file('file');
            $info             = $file->move(\Env::get("ROOT_PATH") . '/upload/user');#移动到框架应用根目录/upload/excel 目录下
            $data             = [];
            $data['filepath'] = 'upload/user/' . $info->getSaveName();
            ajaxmsg('上传成功', 1, $data);
        } else {
            $this->assign("id", $id);
            return view();
        }
    }


    public function joinus()
    {
        if (Request::isAjax()) {
            $map      = [];
            $keywords = trim(input("keywords"));
            if ($keywords) {
                $map[] = ['companyname|username', "like", "%$keywords%"];
            }
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("joinus")
                ->where($map)
                ->field("*,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as create_time_cn")
                ->order("id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("joinus")->where($map)->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function joinusDelete()
    {
        $id  = input("id", 0);
        $res = Db::name("joinus")->where("id", "=", $id)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }
}
